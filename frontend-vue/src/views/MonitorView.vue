<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'
import { Chart, registerables } from 'chart.js'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

Chart.register(...registerables)

/** Cada cuánto se vuelve a consultar el servidor mientras la página está visible. */
const REFRESH_MS = 20_000

const auth = useAuthStore()
const isPremium = computed(() => auth.user?.plan === 'Premium')

const latest = ref(null)
const history = ref([])
const historyLocked = ref(false)
const range = ref('24h')
const loading = ref(true)
const error = ref('')
const lastUpdated = ref(null)
const now = ref(Date.now())

const chartCanvas = ref(null)
let chart = null
let timer = null
let clock = null

async function loadLatest() {
  const { data } = await api.get('/readings/latest')
  latest.value = data
}

async function loadHistory() {
  const { data } = await api.get('/readings', { params: { range: range.value } })
  historyLocked.value = data.locked
  history.value = data.readings || []
}

async function refresh() {
  try {
    await Promise.all([loadLatest(), isPremium.value ? loadHistory() : Promise.resolve()])
    lastUpdated.value = Date.now()
    error.value = ''
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudieron cargar los datos de tus dispositivos.'
  } finally {
    loading.value = false
  }
}

function startPolling() {
  stopPolling()
  timer = setInterval(() => { if (!document.hidden) refresh() }, REFRESH_MS)
}
function stopPolling() { if (timer) clearInterval(timer); timer = null }
function onVisibility() { if (!document.hidden) refresh() }

onMounted(() => {
  refresh()
  startPolling()
  clock = setInterval(() => { now.value = Date.now() }, 30_000)
  document.addEventListener('visibilitychange', onVisibility)
})
onBeforeUnmount(() => {
  stopPolling()
  clearInterval(clock)
  document.removeEventListener('visibilitychange', onVisibility)
  chart?.destroy()
})
watch(range, () => loadHistory().catch(() => {}))
// El lienzo solo existe cuando hay datos y ya terminó la carga: se dibuja después de que Vue lo monta.
watch([() => history.value, loading, historyLocked], async () => { await nextTick(); renderChart() })

// ── Formato ─────────────────────────────────────────────────
function ago(value) {
  if (!value) return 'Sin datos'
  const mins = Math.round((now.value - new Date(value).getTime()) / 60000)
  if (mins < 1) return 'Hace un momento'
  if (mins < 60) return `Hace ${mins} min`
  const hours = Math.round(mins / 60)
  if (hours < 24) return `Hace ${hours} h`
  return new Date(value).toLocaleString('es-MX', { dateStyle: 'medium', timeStyle: 'short' })
}
const deviceLabel = { watch: '⌚ Reloj', mobile: '📱 Celular' }

const vitals = computed(() => {
  const v = latest.value?.vitals || {}
  const hr = v.heart_rate?.value
  return [
    {
      key: 'heart_rate', icon: '❤️', label: 'Ritmo cardíaco', unit: 'lpm', data: v.heart_rate,
      status: hr == null ? null : hr > 120 ? ['Elevado', 'danger'] : hr < 50 ? ['Bajo', 'warning'] : ['Normal', 'success'],
    },
    {
      key: 'spo2', icon: '🫁', label: 'Oxígeno en sangre', unit: '%', data: v.spo2,
      status: v.spo2?.value == null ? null : v.spo2.value < 92 ? ['Bajo', 'danger'] : ['Normal', 'success'],
    },
    { key: 'steps', icon: '👣', label: 'Pasos de hoy', unit: '', data: v.steps, status: null },
    { key: 'skin_temp', icon: '🌡️', label: 'Temperatura de piel', unit: '°C', data: v.skin_temp, status: null },
  ]
})

const devices = computed(() => {
  const d = latest.value?.devices || {}
  return ['watch', 'mobile'].map((key) => ({ key, label: deviceLabel[key], ...(d[key] || {}) }))
})

const location = computed(() => latest.value?.location)
const mapEmbedUrl = computed(() => {
  if (!location.value) return null
  const { lat, lng } = location.value
  const delta = 0.004
  const bbox = `${lng - delta},${lat - delta},${lng + delta},${lat + delta}`
  return `https://www.openstreetmap.org/export/embed.html?bbox=${bbox}&marker=${lat},${lng}&layer=mapnik`
})
const mapsUrl = computed(() => location.value
  ? `https://www.google.com/maps?q=${location.value.lat},${location.value.lng}` : null)

// ── Gráfica de ritmo cardíaco y oxígeno ─────────────────────
const chartPoints = computed(() => history.value.filter((r) => r.heart_rate != null || r.spo2 != null))

function renderChart() {
  if (!chartCanvas.value) { chart?.destroy(); chart = null; return }
  const css = getComputedStyle(document.documentElement)
  const text = css.getPropertyValue('--text-secondary').trim() || '#94A3B8'
  const grid = css.getPropertyValue('--glass-border').trim() || 'rgba(255,255,255,0.08)'
  const fmt = range.value === '7d'
    ? { weekday: 'short', hour: '2-digit', minute: '2-digit' }
    : { hour: '2-digit', minute: '2-digit' }
  const labels = chartPoints.value.map((r) => new Date(r.recorded_at).toLocaleString('es-MX', fmt))

  chart?.destroy()
  chart = new Chart(chartCanvas.value, {
    type: 'line',
    data: {
      labels,
      datasets: [
        {
          label: 'Ritmo cardíaco (lpm)', data: chartPoints.value.map((r) => r.heart_rate),
          borderColor: '#EF4444', backgroundColor: 'rgba(239,68,68,0.12)', fill: true,
          tension: 0.3, pointRadius: 0, spanGaps: true, yAxisID: 'y',
        },
        {
          label: 'Oxígeno (%)', data: chartPoints.value.map((r) => r.spo2),
          borderColor: '#22D3EE', backgroundColor: 'transparent',
          tension: 0.3, pointRadius: 0, spanGaps: true, yAxisID: 'y1',
        },
      ],
    },
    options: {
      responsive: true, maintainAspectRatio: false, animation: false,
      interaction: { mode: 'index', intersect: false },
      plugins: { legend: { labels: { color: text } } },
      scales: {
        x: { ticks: { color: text, maxTicksLimit: 8 }, grid: { color: grid } },
        y: { position: 'left', ticks: { color: text }, grid: { color: grid }, suggestedMin: 50, suggestedMax: 120 },
        y1: { position: 'right', ticks: { color: text }, grid: { drawOnChartArea: false }, suggestedMin: 85, max: 100 },
      },
    },
  })
}

const hasAnyData = computed(() => {
  const l = latest.value
  return !!(l && (l.location || Object.values(l.vitals || {}).some(Boolean)))
})
</script>

<template>
  <div>
    <div class="topbar">
      <div>
        <h1>En vivo</h1>
        <p>Datos que envían tu reloj y tu celular · se actualiza cada 20 segundos</p>
      </div>
      <button class="btn btn-secondary btn-sm" :disabled="loading" @click="refresh">
        🔄 {{ lastUpdated ? `Actualizado ${ago(lastUpdated).toLowerCase()}` : 'Actualizar' }}
      </button>
    </div>

    <div class="page-content">
      <div v-if="error" class="error-box">{{ error }}</div>
      <div v-if="loading" class="text-muted">Cargando datos de tus dispositivos...</div>

      <template v-else>
        <!-- Estado de dispositivos -->
        <div class="devices-row">
          <div v-for="d in devices" :key="d.key" class="device-card">
            <span class="dot" :class="d.online ? 'dot-on' : 'dot-off'" />
            <div class="device-info">
              <strong>{{ d.label }}</strong>
              <span class="text-muted text-sm">
                {{ d.online ? 'Conectado' : d.last_seen ? 'Sin conexión' : 'Nunca ha enviado datos' }}
                <template v-if="d.last_seen"> · {{ ago(d.last_seen) }}</template>
              </span>
            </div>
            <span v-if="d.battery != null" class="battery" :class="{ low: d.battery <= 20 }">🔋 {{ d.battery }}%</span>
          </div>
        </div>

        <div v-if="!hasAnyData" class="empty-state">
          <div class="empty-icon">📡</div>
          <p>
            Todavía no hay datos. Abre AlertSync en tu celular con la sesión iniciada y permite la ubicación;
            con el reloj vinculado, abre la pantalla de <strong>Ritmo cardíaco</strong> en el reloj para enviar tus signos vitales.
          </p>
        </div>

        <template v-else>
          <!-- Signos vitales -->
          <div class="vitals-grid">
            <div v-for="v in vitals" :key="v.key" class="vital-card">
              <div class="vital-top">
                <span class="vital-icon">{{ v.icon }}</span>
                <span v-if="v.status" class="badge" :class="`badge-${v.status[1]}`">{{ v.status[0] }}</span>
              </div>
              <div class="vital-value">
                {{ v.data?.value != null ? Number(v.data.value).toLocaleString('es-MX') : '--' }}
                <small v-if="v.data?.value != null">{{ v.unit }}</small>
              </div>
              <div class="vital-label">{{ v.label }}</div>
              <div class="text-muted text-xs">{{ v.data ? `${deviceLabel[v.data.device]} · ${ago(v.data.recorded_at)}` : 'Sin lectura' }}</div>
            </div>
          </div>

          <!-- Ubicación -->
          <div class="panel">
            <div class="panel-head">
              <div>
                <h2>📍 Última ubicación</h2>
                <p v-if="location" class="text-muted text-sm">
                  {{ deviceLabel[location.device] }} · {{ ago(location.recorded_at) }}
                  <template v-if="location.accuracy"> · precisión ±{{ location.accuracy }} m</template>
                </p>
              </div>
              <a v-if="mapsUrl" :href="mapsUrl" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">Abrir en Google Maps</a>
            </div>
            <iframe v-if="mapEmbedUrl" :src="mapEmbedUrl" class="map" loading="lazy" title="Mapa de la última ubicación" />
            <p v-else class="text-muted">El celular aún no ha enviado su ubicación. Abre la app y permite el acceso a la ubicación.</p>
          </div>

          <!-- Historial (Premium) -->
          <div class="panel">
            <div class="panel-head">
              <h2>📈 Ritmo cardíaco y oxígeno</h2>
              <div v-if="isPremium" class="range-tabs">
                <button v-for="r in ['6h', '24h', '7d']" :key="r" :class="{ active: range === r }" @click="range = r">
                  {{ r === '7d' ? '7 días' : r.replace('h', ' h') }}
                </button>
              </div>
            </div>
            <div v-if="!isPremium || historyLocked" class="locked">
              <p>🔒 El historial y las gráficas de signos vitales están disponibles en el plan <strong>Premium</strong>.</p>
              <RouterLink to="/dashboard/plans" class="btn btn-primary btn-sm">Ver planes</RouterLink>
            </div>
            <div v-else-if="!chartPoints.length" class="text-muted">No hay lecturas en este periodo.</div>
            <div v-else class="chart-box"><canvas ref="chartCanvas" /></div>
          </div>
        </template>
      </template>
    </div>
  </div>
</template>

<style scoped>
.topbar {
  display: flex; align-items: center; justify-content: space-between; gap: var(--space-4);
  padding: var(--space-4) var(--space-8);
  background: var(--surface-nav); border-bottom: 1px solid var(--glass-border);
  position: sticky; top: 0; z-index: 40;
}
.topbar h1 { font-size: 1.25rem; font-weight: 700; }
.topbar p { font-size: 0.8rem; color: var(--text-muted); }
.page-content { padding: var(--space-8); display: flex; flex-direction: column; gap: var(--space-6); }
.error-box { padding: var(--space-4); background: rgba(239,68,68,0.1); border-radius: var(--radius-md); color: #FCA5A5; }

.devices-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: var(--space-4); }
.device-card, .vital-card, .panel {
  background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: var(--radius-lg);
}
.device-card { display: flex; align-items: center; gap: var(--space-3); padding: var(--space-4) var(--space-5); }
.device-info { display: flex; flex-direction: column; flex: 1; min-width: 0; }
.dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.dot-on { background: var(--success); box-shadow: 0 0 6px var(--success); }
.dot-off { background: var(--text-muted); }
.battery { font-size: 0.85rem; color: var(--text-secondary); }
.battery.low { color: var(--danger); }

.vitals-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: var(--space-4); }
.vital-card { padding: var(--space-5); display: flex; flex-direction: column; gap: var(--space-1); }
.vital-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-2); }
.vital-icon { font-size: 1.4rem; }
.vital-value { font-size: 2rem; font-weight: 800; line-height: 1.1; }
.vital-value small { font-size: 0.9rem; font-weight: 600; color: var(--text-secondary); }
.vital-label { color: var(--text-secondary); font-size: 0.9rem; }
.badge-success { background: rgba(16,185,129,0.15); color: var(--success); border: 1px solid rgba(16,185,129,0.3); }
.badge-warning { background: rgba(245,158,11,0.15); color: var(--warning); border: 1px solid rgba(245,158,11,0.3); }
.badge-danger { background: rgba(239,68,68,0.15); color: var(--danger); border: 1px solid rgba(239,68,68,0.3); }

.panel { padding: var(--space-6); }
.panel-head { display: flex; justify-content: space-between; align-items: flex-start; gap: var(--space-4); margin-bottom: var(--space-4); flex-wrap: wrap; }
.panel-head h2 { font-size: 1.05rem; font-weight: 700; }
.map { width: 100%; height: 340px; border: 0; border-radius: var(--radius-md); }
.chart-box { height: 300px; position: relative; }
.range-tabs { display: flex; gap: var(--space-1); background: var(--glass-bg); border-radius: var(--radius-md); padding: 3px; }
.range-tabs button {
  border: 0; background: transparent; color: var(--text-secondary); padding: 6px 12px;
  border-radius: var(--radius-sm); cursor: pointer; font: inherit; font-size: 0.85rem;
}
.range-tabs button.active { background: var(--glass-hover); color: var(--text-primary); font-weight: 600; }
.locked { display: flex; align-items: center; justify-content: space-between; gap: var(--space-4); flex-wrap: wrap; color: var(--text-secondary); }

.empty-state { text-align: center; padding: var(--space-12); color: var(--text-muted); background: var(--glass-bg); border-radius: var(--radius-xl); }
.empty-icon { font-size: 2.5rem; margin-bottom: var(--space-4); }

@media (max-width: 900px) { .vitals-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 600px) {
  .topbar { padding: var(--space-4); flex-wrap: wrap; }
  .page-content { padding: var(--space-4); }
  .devices-row, .vitals-grid { grid-template-columns: 1fr; }
  .map { height: 260px; }
}
</style>
