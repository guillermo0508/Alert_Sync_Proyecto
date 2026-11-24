<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import api from '@/services/api'

/** Tiempo real: lo que se borre o envíe desde el celular o el reloj aparece solo. */
const REFRESH_MS = 15_000

const alerts = ref([])
const deletedCount = ref(0)
const loading = ref(true)
const error = ref('')
const notice = ref('')
const busy = ref(false)
let timer = null

async function load({ silent = false } = {}) {
  try {
    const { data } = await api.get('/alerts')
    alerts.value = data.alerts || []
    deletedCount.value = data.deleted_count || 0
    if (openMap.value && !alerts.value.some((a) => a.id === openMap.value)) openMap.value = null
    error.value = ''
  } catch (e) {
    if (!silent) error.value = e.response?.data?.message || 'No se pudo cargar tu historial de alertas.'
  } finally {
    loading.value = false
  }
}

function onVisibility() { if (!document.hidden) load({ silent: true }) }

onMounted(() => {
  load()
  timer = setInterval(() => { if (!document.hidden && !busy.value) load({ silent: true }) }, REFRESH_MS)
  document.addEventListener('visibilitychange', onVisibility)
})
onBeforeUnmount(() => {
  clearInterval(timer)
  document.removeEventListener('visibilitychange', onVisibility)
})

async function run(action, message) {
  busy.value = true
  notice.value = ''
  try {
    await action()
    notice.value = message
    await load({ silent: true })
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudo actualizar el historial.'
  } finally {
    busy.value = false
  }
}

function deleteAlert(alert) {
  if (!confirm('¿Borrar esta alerta? Desaparecerá también del celular y del reloj.')) return
  alerts.value = alerts.value.filter((a) => a.id !== alert.id)
  run(() => api.delete(`/alerts/${alert.id}`), 'Alerta borrada en la web, el celular y el reloj.')
}

function deleteAll() {
  if (!confirm(`¿Borrar todo tu historial (${alerts.value.length} alertas)? Desaparecerá también del celular y del reloj.`)) return
  alerts.value = []
  run(() => api.post('/alerts/clear'), 'Historial borrado en la web, el celular y el reloj.')
}

function restoreAll() {
  run(() => api.post('/alerts/restore'), 'Se recuperaron las alertas borradas.')
}

const sourceLabels = {
  watch: '⌚ Smartwatch',
  alexa: '🔊 Alexa',
  dashboard: '🌐 Dashboard',
  'dashboard-premium': '🌐 Dashboard Premium',
  mobile: '📱 App móvil',
  web: '🌐 Web',
  simulator: '🖥️ Simulador',
}

function sourceLabel(source) {
  return sourceLabels[source] || source
}

const openMap = ref(null)

function mapEmbedUrl(alert) {
  const lat = Number(alert.latitude), lng = Number(alert.longitude)
  const d = 0.004
  return `https://www.openstreetmap.org/export/embed.html?bbox=${lng - d},${lat - d},${lng + d},${lat + d}&marker=${lat},${lng}&layer=mapnik`
}

function mapsUrl(alert) {
  return `https://www.google.com/maps?q=${alert.latitude},${alert.longitude}`
}

function formatDate(value) {
  if (!value) return '—'
  return new Date(value).toLocaleString('es-MX', { dateStyle: 'long', timeStyle: 'short' })
}
</script>

<template>
  <div>
    <div class="topbar">
      <div>
        <h1>Mi Historial de Alertas</h1>
        <p>Registro de todas tus activaciones SOS · sincronizado con tu celular y tu reloj</p>
      </div>
      <div class="topbar-actions">
        <button v-if="deletedCount" class="btn btn-secondary btn-sm" :disabled="busy" @click="restoreAll">
          ↩️ Recuperar borradas ({{ deletedCount }})
        </button>
        <button v-if="alerts.length" class="btn btn-secondary btn-sm btn-delete" :disabled="busy" @click="deleteAll">
          🗑️ Borrar todo
        </button>
      </div>
    </div>

    <div class="page-content">
      <div v-if="error" class="error-box">{{ error }}</div>
      <div v-if="notice" class="notice-box" @click="notice = ''">{{ notice }}</div>
      <div v-if="loading" class="text-muted">Cargando historial...</div>

      <div v-else-if="alerts.length" class="alerts-list">
        <div v-for="alert in alerts" :key="alert.id" class="alert-item">
          <div class="alert-icon">🚨</div>
          <div class="alert-body">
            <div class="alert-top">
              <strong>{{ sourceLabel(alert.source) }}</strong>
              <span class="text-muted text-sm">{{ formatDate(alert.created_at) }}</span>
            </div>
            <p class="alert-message">{{ alert.message }}</p>
            <div class="alert-meta">
              <span class="badge badge-cyan">{{ alert.contacts_notified?.length || 0 }} contactos notificados</span>
              <button
                v-if="alert.latitude && alert.longitude"
                type="button"
                class="badge badge-gray map-toggle"
                :aria-expanded="openMap === alert.id"
                @click="openMap = openMap === alert.id ? null : alert.id"
              >
                📍 {{ openMap === alert.id ? 'Ocultar mapa' : 'Ver ubicación' }}
              </button>
              <span v-if="alert.status === 'cancelled'" class="badge badge-gray">Cancelada</span>
              <button type="button" class="badge badge-gray map-toggle delete-one" :disabled="busy" @click="deleteAlert(alert)">
                🗑️ Borrar
              </button>
            </div>
            <div v-if="openMap === alert.id" class="alert-map">
              <iframe :src="mapEmbedUrl(alert)" loading="lazy" title="Ubicación de la alerta" />
              <a :href="mapsUrl(alert)" target="_blank" rel="noopener" class="text-sm">Abrir en Google Maps ↗</a>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="empty-state">
        <div class="empty-icon">🛡️</div>
        <p>Aún no tienes alertas registradas. Cuando actives el SOS desde tu Smart Watch o app móvil, aparecerá aquí.</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.topbar {
  display: flex; align-items: center; justify-content: space-between; gap: var(--space-4); flex-wrap: wrap;
  padding: var(--space-4) var(--space-8);
  background: var(--surface-nav); border-bottom: 1px solid var(--glass-border);
  position: sticky; top: 0; z-index: 40;
}
.topbar h1 { font-size: 1.25rem; font-weight: 700; }
.topbar p { font-size: 0.8rem; color: var(--text-muted); }
.page-content { padding: var(--space-8); }
.error-box {
  padding: var(--space-4); margin-bottom: var(--space-4);
  background: rgba(239,68,68,0.1); border-radius: var(--radius-md); color: #FCA5A5;
}
.alerts-list { display: flex; flex-direction: column; gap: var(--space-4); }
.alert-item {
  display: flex; gap: var(--space-4); align-items: flex-start;
  padding: var(--space-5); background: var(--glass-bg);
  border: 1px solid var(--glass-border); border-radius: var(--radius-lg);
}
.alert-icon {
  width: 44px; height: 44px; flex-shrink: 0; border-radius: var(--radius-md);
  background: rgba(255,59,59,0.12); display: flex; align-items: center; justify-content: center;
  font-size: 1.3rem;
}
.alert-body { flex: 1; }
.alert-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: var(--space-2); }
.alert-message { color: var(--text-secondary); font-size: 0.9rem; margin-bottom: var(--space-3); }
.alert-meta { display: flex; gap: var(--space-2); flex-wrap: wrap; }
.topbar-actions { display: flex; gap: var(--space-2); flex-wrap: wrap; }
.btn-delete { color: var(--danger); }
.delete-one { margin-left: auto; }
.delete-one:hover { color: var(--danger); }
.notice-box {
  padding: var(--space-3) var(--space-4); margin-bottom: var(--space-4); cursor: pointer;
  background: rgba(16,185,129,0.1); border-radius: var(--radius-md); color: var(--success);
}
.map-toggle { cursor: pointer; font: inherit; font-size: inherit; }
.alert-map { margin-top: var(--space-3); display: flex; flex-direction: column; gap: var(--space-2); }
.alert-map iframe { width: 100%; height: 260px; border: 0; border-radius: var(--radius-md); }
.empty-state {
  text-align: center; padding: var(--space-16); color: var(--text-muted);
  background: var(--glass-bg); border-radius: var(--radius-xl);
}
.empty-icon { font-size: 2.5rem; margin-bottom: var(--space-4); }
</style>
