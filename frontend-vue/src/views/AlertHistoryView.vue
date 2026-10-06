<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'

const alerts = ref([])
const loading = ref(true)
const error = ref('')

onMounted(async () => {
  try {
    const { data } = await api.get('/alerts')
    alerts.value = data.alerts
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudo cargar tu historial de alertas.'
  } finally {
    loading.value = false
  }
})

const sourceLabels = {
  watch: '⌚ Smartwatch',
  alexa: '🔊 Alexa',
  dashboard: '🌐 Dashboard',
  'dashboard-premium': '🌐 Dashboard Premium',
  web: '🌐 Web',
  simulator: '🖥️ Simulador',
}

function sourceLabel(source) {
  return sourceLabels[source] || source
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
        <p>Registro de todas tus activaciones SOS</p>
      </div>
    </div>

    <div class="page-content">
      <div v-if="error" class="error-box">{{ error }}</div>
      <div v-if="loading" class="text-muted">Cargando historial...</div>

      <div v-else-if="alerts.length" class="alerts-list">
        <div v-for="alert in alerts" :key="alert._id" class="alert-item">
          <div class="alert-icon">🚨</div>
          <div class="alert-body">
            <div class="alert-top">
              <strong>{{ sourceLabel(alert.source) }}</strong>
              <span class="text-muted text-sm">{{ formatDate(alert.created_at) }}</span>
            </div>
            <p class="alert-message">{{ alert.message }}</p>
            <div class="alert-meta">
              <span class="badge badge-cyan">{{ alert.contacts_notified?.length || 0 }} contactos notificados</span>
              <span v-if="alert.latitude && alert.longitude" class="badge badge-gray">📍 Con ubicación</span>
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
  display: flex; align-items: center; justify-content: space-between;
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
.empty-state {
  text-align: center; padding: var(--space-16); color: var(--text-muted);
  background: var(--glass-bg); border-radius: var(--radius-xl);
}
.empty-icon { font-size: 2.5rem; margin-bottom: var(--space-4); }
</style>
