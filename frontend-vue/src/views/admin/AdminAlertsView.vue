<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'

const alerts = ref([])
const loading = ref(true)
const error = ref('')
const toast = ref('')
const sourceFilter = ref('')

async function loadAlerts() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get('/admin/alerts', {
      params: { source: sourceFilter.value || undefined },
    })
    alerts.value = data.alerts
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudieron cargar las alertas.'
  } finally {
    loading.value = false
  }
}

onMounted(loadAlerts)

async function removeAlert(alert) {
  if (!confirm(`¿Eliminar la alerta de ${alert.user_email}?`)) return
  try {
    await api.delete(`/admin/alerts/${alert.id}`)
    toast.value = 'Alerta eliminada.'
    await loadAlerts()
    setTimeout(() => { toast.value = '' }, 3000)
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudo eliminar la alerta.'
  }
}

function formatDate(value) {
  if (!value) return '—'
  return new Date(value).toLocaleString('es-MX', { dateStyle: 'medium', timeStyle: 'short' })
}
</script>

<template>
  <div>
    <div class="topbar">
      <div class="topbar-title">
        <h1>Alertas SOS</h1>
        <p>Historial completo de activaciones de emergencia</p>
      </div>
    </div>

    <div class="page-content">
      <div v-if="toast" class="toast-banner success">{{ toast }}</div>
      <div v-if="error" class="toast-banner">{{ error }}</div>

      <div class="filters">
        <select v-model="sourceFilter" @change="loadAlerts" class="form-input">
          <option value="">Todas las fuentes</option>
          <option value="watch">Smartwatch</option>
          <option value="alexa">Alexa</option>
          <option value="dashboard">Dashboard</option>
          <option value="dashboard-premium">Dashboard Premium</option>
          <option value="web">Web</option>
          <option value="simulator">Simulador</option>
        </select>
      </div>

      <div v-if="loading" class="text-secondary">Cargando alertas...</div>
      <div v-else class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th>Usuario</th>
              <th>Fuente</th>
              <th>Mensaje</th>
              <th>Contactos notificados</th>
              <th>Fecha</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="alert in alerts" :key="alert.id">
              <td>
                <div>{{ alert.user_name }}</div>
                <div class="text-muted text-sm">{{ alert.user_email }}</div>
              </td>
              <td><span class="badge badge-cyan">{{ alert.source }}</span></td>
              <td class="message-cell">{{ alert.message }}</td>
              <td>{{ alert.contacts_notified?.length || 0 }}</td>
              <td>{{ formatDate(alert.created_at) }}</td>
              <td class="actions-cell">
                <button class="link-btn danger" @click="removeAlert(alert)">Eliminar</button>
              </td>
            </tr>
            <tr v-if="!alerts.length">
              <td colspan="6" class="text-center text-muted" style="padding: var(--space-8)">Sin alertas registradas.</td>
            </tr>
          </tbody>
        </table>
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
.topbar-title h1 { font-size: 1.25rem; font-weight: 700; }
.topbar-title p { font-size: 0.8rem; color: var(--text-muted); }
.page-content { padding: var(--space-8); }
.toast-banner {
  margin-bottom: var(--space-4); padding: var(--space-4);
  background: rgba(255,59,59,0.1); border: 1px solid rgba(255,59,59,0.2);
  border-radius: var(--radius-md);
}
.toast-banner.success { background: rgba(159,211,45,0.12); border-color: rgba(159,211,45,0.3); }
.filters { display: flex; gap: var(--space-3); margin-bottom: var(--space-5); }
.filters .form-input { max-width: 220px; }
.table-wrapper {
  background: var(--glass-bg); border: 1px solid var(--glass-border);
  border-radius: var(--radius-xl); overflow-x: auto;
}
.data-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
.data-table th, .data-table td {
  padding: var(--space-4); text-align: left; border-bottom: 1px solid var(--glass-border);
}
.data-table th { color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap; }
.message-cell { max-width: 280px; white-space: normal; color: var(--text-secondary); }
.actions-cell { white-space: nowrap; }
.link-btn { color: var(--brand-teal-light); font-weight: 600; font-size: 0.85rem; background: none; border: none; cursor: pointer; }
.link-btn.danger { color: var(--red-500); }
</style>
