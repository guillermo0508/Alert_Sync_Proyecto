<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import api from '@/services/api'
import { Chart, registerables } from 'chart.js'

Chart.register(...registerables)

const stats = ref(null)
const recentAlerts = ref([])
const recentPayments = ref([])
const alertsByDay = ref([])
const revenueByMonth = ref([])
const loading = ref(true)
const error = ref('')

const backingUp = ref(false)
const backupError = ref('')

async function downloadBackup() {
  backingUp.value = true
  backupError.value = ''
  try {
    const response = await api.post('/admin/backup', {}, { responseType: 'blob' })
    const contentDisposition = response.headers['content-disposition'] || ''
    const match = contentDisposition.match(/filename="?([^"]+)"?/)
    const filename = match ? match[1] : `alertsync-backup-${Date.now()}.zip`

    const url = window.URL.createObjectURL(response.data)
    const link = document.createElement('a')
    link.href = url
    link.download = filename
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (e) {
    backupError.value = e.response?.data?.message || 'No se pudo generar el respaldo.'
  } finally {
    backingUp.value = false
  }
}

const planChartRef = ref(null)
const alertsChartRef = ref(null)
const revenueChartRef = ref(null)
let planChart = null
let alertsChart = null
let revenueChart = null

const COLOR_LIME = '#9FD32D'
const COLOR_TEAL = '#2A7A8F'
const COLOR_MUTED = '#94A3B8'
const COLOR_GRID = 'rgba(255,255,255,0.06)'

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/dashboard')
    stats.value = data.stats
    recentAlerts.value = data.recent_alerts
    recentPayments.value = data.recent_payments
    alertsByDay.value = data.alerts_by_day || []
    revenueByMonth.value = data.revenue_by_month || []
    loading.value = false
    await nextTick()
    renderCharts()
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudo cargar el panel de administración.'
    loading.value = false
  }
})

onUnmounted(() => {
  planChart?.destroy()
  alertsChart?.destroy()
  revenueChart?.destroy()
})

function renderCharts() {
  if (planChartRef.value) {
    planChart = new Chart(planChartRef.value, {
      type: 'doughnut',
      data: {
        labels: Object.keys(stats.value.users_by_plan),
        datasets: [{
          data: Object.values(stats.value.users_by_plan),
          backgroundColor: ['#475569', COLOR_TEAL, COLOR_LIME],
          borderWidth: 0,
        }],
      },
      options: {
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { color: COLOR_MUTED, padding: 12 } } },
        cutout: '65%',
      },
    })
  }

  if (alertsChartRef.value) {
    alertsChart = new Chart(alertsChartRef.value, {
      type: 'line',
      data: {
        labels: alertsByDay.value.map(d => d.date.slice(5)),
        datasets: [{
          label: 'Alertas',
          data: alertsByDay.value.map(d => d.count),
          borderColor: COLOR_LIME,
          backgroundColor: 'rgba(159,211,45,0.15)',
          fill: true,
          tension: 0.3,
          pointRadius: 2,
        }],
      },
      options: {
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { ticks: { color: COLOR_MUTED }, grid: { color: COLOR_GRID } },
          y: { ticks: { color: COLOR_MUTED, precision: 0 }, grid: { color: COLOR_GRID }, beginAtZero: true },
        },
      },
    })
  }

  if (revenueChartRef.value) {
    revenueChart = new Chart(revenueChartRef.value, {
      type: 'bar',
      data: {
        labels: revenueByMonth.value.map(m => m.month),
        datasets: [{
          label: 'Ingresos',
          data: revenueByMonth.value.map(m => m.total),
          backgroundColor: COLOR_TEAL,
          borderRadius: 6,
          maxBarThickness: 40,
        }],
      },
      options: {
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { ticks: { color: COLOR_MUTED }, grid: { display: false } },
          y: { ticks: { color: COLOR_MUTED }, grid: { color: COLOR_GRID }, beginAtZero: true },
        },
      },
    })
  }
}

function formatDate(value) {
  if (!value) return '—'
  return new Date(value).toLocaleString('es-MX', { dateStyle: 'short', timeStyle: 'short' })
}

function formatMoney(value) {
  return `$${Number(value || 0).toLocaleString('es-MX', { minimumFractionDigits: 2 })}`
}
</script>

<template>
  <div>
    <div class="topbar">
      <div class="topbar-title">
        <h1>Panel de Administración</h1>
        <p>Resumen general del sistema</p>
      </div>
      <button class="btn btn-secondary btn-sm" :disabled="backingUp" @click="downloadBackup">
        {{ backingUp ? 'Generando respaldo...' : '📦 Generar respaldo de la BD' }}
      </button>
    </div>

    <div class="page-content">
      <div v-if="error" class="toast-banner">{{ error }}</div>
      <div v-if="backupError" class="toast-banner">{{ backupError }}</div>
      <div v-if="loading" class="text-secondary">Cargando...</div>

      <template v-else-if="stats">
        <div class="stats-grid">
          <div class="stat-card cyan">
            <div class="stat-value">{{ stats.total_users }}</div>
            <div class="stat-name">Usuarios totales</div>
          </div>
          <div class="stat-card green">
            <div class="stat-value">{{ formatMoney(stats.revenue_net) }}</div>
            <div class="stat-name">Ingresos netos</div>
          </div>
          <div class="stat-card red">
            <div class="stat-value">{{ stats.total_alerts }}</div>
            <div class="stat-name">Alertas SOS totales</div>
          </div>
          <div class="stat-card orange">
            <div class="stat-value">{{ stats.alerts_last_24h }}</div>
            <div class="stat-name">Alertas últimas 24h</div>
          </div>
        </div>

        <div class="grid-2col">
          <div class="panel">
            <h3 class="section-title">👥 Usuarios por plan</h3>
            <div class="chart-box chart-box-sm">
              <canvas ref="planChartRef"></canvas>
            </div>
            <p class="text-muted text-sm" style="margin-top: var(--space-4)">
              {{ stats.admins }} administrador(es) · {{ stats.total_contacts }} contactos registrados · {{ stats.total_payments }} pagos procesados
            </p>
          </div>

          <div class="panel">
            <h3 class="section-title">📈 Alertas — últimos 14 días</h3>
            <div class="chart-box">
              <canvas ref="alertsChartRef"></canvas>
            </div>
          </div>
        </div>

        <div class="panel" style="margin-top: var(--space-6)">
          <h3 class="section-title">💰 Ingresos por mes</h3>
          <div class="chart-box">
            <canvas ref="revenueChartRef"></canvas>
          </div>
        </div>

        <div class="grid-2col" style="margin-top: var(--space-6)">
          <div class="panel">
            <h3 class="section-title">💳 Pagos recientes</h3>
            <div v-if="recentPayments.length" class="mini-list">
              <div v-for="p in recentPayments" :key="p._id" class="mini-row">
                <div>
                  <strong>{{ p.plan }}</strong>
                  <p class="text-muted text-sm">{{ formatDate(p.created_at) }}</p>
                </div>
                <span class="badge" :class="p.status === 'refunded' ? 'badge-orange' : 'badge-lime'">
                  {{ formatMoney(p.amount) }}
                </span>
              </div>
            </div>
            <p v-else class="text-muted">Sin pagos registrados aún.</p>
          </div>

          <div class="panel">
            <h3 class="section-title">🚨 Alertas SOS recientes</h3>
            <div v-if="recentAlerts.length" class="mini-list">
              <div v-for="a in recentAlerts" :key="a._id" class="mini-row">
                <div>
                  <strong>{{ a.source }}</strong>
                  <p class="text-muted text-sm">{{ a.message }}</p>
                </div>
                <span class="text-muted text-sm">{{ formatDate(a.created_at) }}</span>
              </div>
            </div>
            <p v-else class="text-muted">Sin alertas registradas aún.</p>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<style scoped>
.topbar {
  display: flex; align-items: center; justify-content: space-between;
  padding: var(--space-4) var(--space-8);
  background: var(--surface-nav); border-bottom: 1px solid var(--glass-border);
  position: sticky; top: 0; z-index: 40;
}
.topbar-title h1 { font-size: 1.25rem; font-weight: 700; }
.topbar-title p { font-size: 0.8rem; color: var(--text-muted); }
.page-content { padding: var(--space-8); }
.toast-banner {
  margin-bottom: var(--space-4); padding: var(--space-4);
  background: rgba(255,59,59,0.1); border: 1px solid rgba(255,59,59,0.2);
  border-radius: var(--radius-md);
}
.stats-grid {
  display: grid; grid-template-columns: repeat(4, 1fr);
  gap: var(--space-5); margin-bottom: var(--space-8);
}
.stat-card {
  background: var(--glass-bg); border: 1px solid var(--glass-border);
  border-radius: var(--radius-lg); padding: var(--space-5);
}
.stat-card.red .stat-value { color: var(--brand-lime); }
.stat-card.cyan .stat-value { color: var(--brand-teal-light); }
.stat-card.green .stat-value { color: var(--success); }
.stat-card.orange .stat-value { color: var(--brand-lime); }
.stat-value { font-size: 2rem; font-weight: 900; }
.stat-name { font-size: 0.8rem; color: var(--text-secondary); }

.grid-2col { display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); }
.panel {
  background: var(--glass-bg); border: 1px solid var(--glass-border);
  border-radius: var(--radius-xl); padding: var(--space-6);
}
.section-title { font-weight: 700; margin-bottom: var(--space-5); }

.chart-box { position: relative; height: 220px; }
.chart-box-sm { height: 200px; }

.mini-list { display: flex; flex-direction: column; gap: var(--space-3); }
.mini-row {
  display: flex; align-items: center; justify-content: space-between;
  padding: var(--space-3) var(--space-4); background: rgba(255,255,255,0.02);
  border-radius: var(--radius-md); border: 1px solid var(--glass-border);
}

@media (max-width: 1024px) {
  .stats-grid { grid-template-columns: 1fr 1fr; }
  .grid-2col { grid-template-columns: 1fr; }
}
</style>
