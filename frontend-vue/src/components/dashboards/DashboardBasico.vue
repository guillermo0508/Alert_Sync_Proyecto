<script setup>
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

const auth = useAuthStore()
const contacts = ref([])
const alerts = ref([])

const firstName = computed(() => (auth.user?.name || 'Usuario').split(' ')[0])
const greeting = computed(() => {
  const hour = new Date().getHours()
  if (hour < 12) return 'Buenos días'
  if (hour < 18) return 'Buenas tardes'
  return 'Buenas noches'
})

onMounted(async () => {
  if (auth.token === 'demo-token') return
  try {
    const [contactsRes, alertsRes] = await Promise.all([
      api.get('/contacts'),
      api.get('/alerts'),
    ])
    contacts.value = contactsRes.data.contacts
    alerts.value = alertsRes.data.alerts
  } catch {
    // demo/offline mode
  }
})

function formatDate(value) {
  if (!value) return 'Ahora'
  return new Date(value).toLocaleString('es-MX', { dateStyle: 'short', timeStyle: 'short' })
}
</script>

<template>
  <div>
    <div class="topbar">
      <div class="topbar-title">
        <h1>Menú</h1>
        <p>{{ greeting }} — Sistema activo ✓</p>
      </div>
    </div>

    <div class="page-content">
      <div class="welcome-banner">
        <div>
          <h2>¡Hola, {{ firstName }}! 👋</h2>
          <p>Tu sistema de seguridad está activo. Smartwatch listo para emergencias.</p>
          <div class="flex gap-3 mt-4">
            <span class="badge badge-green">Sistema activo</span>
            <span class="badge badge-cyan">{{ contacts.length || 0 }} contactos</span>
          </div>
        </div>
      </div>

      <div class="stats-grid">
        <div class="stat-card red">
          <div class="stat-value">{{ alerts.length }}</div>
          <div class="stat-name">Alertas SOS enviadas</div>
        </div>
        <div class="stat-card cyan">
          <div class="stat-value">{{ contacts.length }}</div>
          <div class="stat-name">Contactos de emergencia</div>
        </div>
        <div class="stat-card green">
          <div class="stat-value">1</div>
          <div class="stat-name">Dispositivos vinculados</div>
        </div>
        <div class="stat-card orange">
          <div class="stat-value">&lt;3s</div>
          <div class="stat-name">Tiempo de respuesta</div>
        </div>
      </div>

      <div class="sos-panel">
        <div class="sos-left">
          <div class="sos-badge">🚨</div>
          <p class="text-secondary text-sm">Protocolo de emergencia</p>
        </div>
        <div class="sos-right">
          <h3>🚨 Panel de Alerta SOS</h3>
          <p>El botón SOS solo se activa desde tu <strong>Smart Watch</strong> vinculado o la <strong>app móvil</strong> de ALERTSYNC. Al activarse, se notificará a tus contactos con ubicación y mensaje de ayuda.</p>
          <div v-if="contacts.length" class="contacts-preview">
            <div v-for="c in contacts.slice(0, 3)" :key="c._id" class="contact-row">
              <span>{{ c.name }}</span>
              <span class="text-muted">{{ c.phone || c.email }}</span>
            </div>
          </div>
          <p v-else class="text-muted">Agrega contactos en la sección Contactos.</p>
        </div>
      </div>

      <h3 class="section-title">📋 Historial de alertas</h3>
      <div v-if="alerts.length" class="alerts-list">
        <div v-for="alert in alerts" :key="alert._id || alert.created_at" class="alert-item">
          <div class="alert-icon">🚨</div>
          <div>
            <strong>Alerta SOS — {{ alert.source }}</strong>
            <p>{{ alert.message }} • {{ alert.contacts_notified?.length || 0 }} contactos</p>
          </div>
          <span class="text-muted">{{ formatDate(alert.created_at) }}</span>
        </div>
      </div>
      <p v-else class="text-muted text-center" style="padding: var(--space-8)">Sin alertas registradas aún</p>
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
.welcome-banner {
  background: linear-gradient(135deg, rgba(159,211,45,0.1), rgba(29,93,110,0.12));
  border: 1px solid rgba(159,211,45,0.2); border-radius: var(--radius-xl);
  padding: var(--space-8); margin-bottom: var(--space-8);
}
.welcome-banner h2 { font-size: 1.5rem; font-weight: 800; margin-bottom: var(--space-2); }
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
.sos-panel {
  display: flex; gap: var(--space-8); align-items: center;
  background: linear-gradient(135deg, rgba(255,59,59,0.08), rgba(255,107,53,0.04));
  border: 1px solid rgba(255,59,59,0.2); border-radius: var(--radius-xl);
  padding: var(--space-8); margin-bottom: var(--space-8);
}
.sos-left { display: flex; flex-direction: column; align-items: center; gap: var(--space-3); }
.sos-badge {
  width: 120px; height: 120px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 2.5rem; background: rgba(255,59,59,0.12);
  border: 2px solid rgba(255,59,59,0.3);
}
.sos-right h3 { font-size: 1.25rem; font-weight: 800; margin-bottom: var(--space-3); }
.contacts-preview { display: flex; flex-direction: column; gap: var(--space-2); margin-top: var(--space-4); }
.contact-row {
  display: flex; justify-content: space-between; padding: var(--space-3);
  background: var(--glass-bg); border-radius: var(--radius-md); font-size: 0.85rem;
}
.section-title { font-weight: 700; margin-bottom: var(--space-4); }
.alerts-list { display: flex; flex-direction: column; gap: var(--space-3); }
.alert-item {
  display: flex; align-items: center; gap: var(--space-4);
  padding: var(--space-4); background: var(--glass-bg);
  border: 1px solid var(--glass-border); border-radius: var(--radius-lg);
}
.alert-icon {
  width: 40px; height: 40px; border-radius: var(--radius-md);
  background: rgba(255,59,59,0.12); display: flex; align-items: center; justify-content: center;
}
@media (max-width: 768px) {
  .stats-grid { grid-template-columns: 1fr 1fr; }
  .sos-panel { flex-direction: column; }
}
</style>
