<script setup>
import { ref, onMounted, computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

const auth = useAuthStore()
const contacts = ref([])
const alerts = ref([])
const location = ref(null)
const locationError = ref('')
const locatingUser = ref(false)

const firstName = computed(() => (auth.user?.name || 'Usuario').split(' ')[0])
const greeting = computed(() => {
  const hour = new Date().getHours()
  if (hour < 12) return 'Buenos días'
  if (hour < 18) return 'Buenas tardes'
  return 'Buenas noches'
})
const mapsUrl = computed(() => location.value
  ? `https://www.google.com/maps?q=${location.value.lat},${location.value.lng}`
  : null)
const mapEmbedUrl = computed(() => {
  if (!location.value) return null
  const { lat, lng } = location.value
  const delta = 0.006
  const bbox = `${lng - delta},${lat - delta},${lng + delta},${lat + delta}`
  return `https://www.openstreetmap.org/export/embed.html?bbox=${bbox}&marker=${lat},${lng}&layer=mapnik`
})

function locateUser() {
  if (!navigator.geolocation) {
    locationError.value = 'Tu navegador no soporta geolocalización'
    return
  }
  locatingUser.value = true
  locationError.value = ''
  navigator.geolocation.getCurrentPosition(
    (pos) => {
      location.value = {
        lat: pos.coords.latitude,
        lng: pos.coords.longitude,
        accuracy: pos.coords.accuracy,
      }
      locatingUser.value = false
    },
    () => {
      locationError.value = 'No se pudo obtener tu ubicación'
      locatingUser.value = false
    },
    { enableHighAccuracy: true, timeout: 10000 },
  )
}

onMounted(async () => {
  try {
    const [contactsRes, alertsRes] = await Promise.all([
      api.get('/contacts'),
      api.get('/alerts'),
    ])
    contacts.value = contactsRes.data.contacts
    alerts.value = alertsRes.data.alerts
  } catch {
    console.error('Error fetching dashboard data')
  }
  locateUser()
})

function formatDate(value) {
  if (!value) return 'Ahora'
  return new Date(value).toLocaleString('es-MX', { dateStyle: 'long', timeStyle: 'short' })
}
</script>

<template>
  <div class="premium-layout">
    <!-- Premium Header -->
    <header class="premium-header">
      <div class="header-content">
        <div>
          <h1 class="premium-title">Menú <span>Premium</span></h1>
          <p class="premium-subtitle">{{ greeting }}, {{ firstName }}. Tu red de seguridad inteligente está operando al 100%.</p>
        </div>
        <div class="header-actions">
          <div class="status-indicator">
            <div class="pulse-dot"></div>
            <span>Protección Activa</span>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <div class="premium-content">
      <div class="grid-layout">
        <!-- Emergency Protocol Status -->
        <div class="sos-card glass-panel">
          <div class="sos-glow"></div>
          <h2>Protocolo de Emergencia</h2>
          <p>Activa una alerta inmediata a todos tus dispositivos (Smart Watch y Alexa) y contactos.</p>
          <div class="sos-status-badge">
            <span class="sos-status-icon">🛡️</span>
            <span class="sos-status-text">PROTOCOLO LISTO</span>
            <span class="sos-status-subtext">Se activa desde tu Smart Watch o la app móvil</span>
          </div>
        </div>

        <!-- Quick Stats -->
        <div class="stats-container">
          <div class="stat-box glass-panel accent-teal">
            <div class="stat-icon">👥</div>
            <div class="stat-info">
              <h3>{{ contacts.length }} / 15</h3>
              <p>Contactos Seguros</p>
            </div>
            <RouterLink to="/dashboard/contacts" class="stat-link">→</RouterLink>
          </div>
          
          <div class="stat-box glass-panel accent-lime">
            <div class="stat-icon">📡</div>
            <div class="stat-info">
              <h3>2</h3>
              <p>Dispositivos (Watch + Alexa)</p>
            </div>
          </div>
          
          <div class="stat-box glass-panel accent-red">
            <div class="stat-icon">🛡️</div>
            <div class="stat-info">
              <h3>{{ alerts.length }}</h3>
              <p>Alertas Históricas</p>
            </div>
          </div>

          <div class="stat-box glass-panel accent-teal">
            <div class="stat-icon">📍</div>
            <div class="stat-info" v-if="location">
              <h3>{{ location.lat.toFixed(5) }}, {{ location.lng.toFixed(5) }}</h3>
              <p>Ubicación actual · ±{{ Math.round(location.accuracy) }}m</p>
            </div>
            <div class="stat-info" v-else-if="locatingUser">
              <h3>Localizando…</h3>
              <p>Obteniendo tu ubicación exacta</p>
            </div>
            <div class="stat-info" v-else>
              <h3>—</h3>
              <p>{{ locationError || 'Ubicación no disponible' }}</p>
            </div>
            <a v-if="mapsUrl" :href="mapsUrl" target="_blank" rel="noopener" class="stat-link">→</a>
          </div>
        </div>
      </div>

      <!-- Historical Data -->
      <div class="history-section glass-panel">
        <div class="history-header">
          <h3>Registro de Actividad</h3>
          <span class="badge-premium">Monitoreo 24/7</span>
        </div>
        
        <div class="history-list" v-if="alerts.length">
          <div v-for="alert in alerts" :key="alert._id" class="history-item">
            <div class="history-icon">⚡</div>
            <div class="history-details">
              <h4>Alerta {{ alert.source }}</h4>
              <p>{{ alert.message }}</p>
            </div>
            <div class="history-meta">
              <span class="time">{{ formatDate(alert.created_at) }}</span>
              <span class="contacts-notified">{{ alert.contacts_notified?.length || 0 }} notificados</span>
            </div>
          </div>
        </div>
        <div v-else class="empty-state">
          <div class="empty-icon">✓</div>
          <p>No se han registrado incidentes recientes. Todo está tranquilo.</p>
        </div>
      </div>

      <!-- Location Map -->
      <div class="map-section glass-panel">
        <div class="history-header">
          <h3>📍 Ubicación en tiempo real</h3>
          <a v-if="mapsUrl" :href="mapsUrl" target="_blank" rel="noopener" class="badge-premium">Abrir en Maps</a>
        </div>

        <iframe
          v-if="mapEmbedUrl"
          class="map-frame"
          :src="mapEmbedUrl"
          loading="lazy"
          referrerpolicy="no-referrer-when-downgrade"
        ></iframe>
        <div v-else class="empty-state">
          <div class="empty-icon">📍</div>
          <p>{{ locatingUser ? 'Localizando…' : (locationError || 'Ubicación no disponible') }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.premium-layout {
  min-height: 100vh;
  background: radial-gradient(circle at top right, rgba(29,93,110,0.15) 0%, transparent 40%),
              radial-gradient(circle at bottom left, rgba(159,211,45,0.1) 0%, transparent 40%);
}

.premium-header {
  padding: 3rem 3rem 1.5rem;
  border-bottom: 1px solid rgba(255,255,255,0.05);
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
}

.premium-title {
  font-size: 2.5rem;
  font-weight: 900;
  letter-spacing: -0.02em;
  margin-bottom: 0.5rem;
}

.premium-title span {
  background: linear-gradient(135deg, var(--brand-lime), var(--brand-teal-light));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.premium-subtitle {
  color: var(--text-secondary);
  font-size: 1.1rem;
}

.status-indicator {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: rgba(159,211,45,0.1);
  border: 1px solid rgba(159,211,45,0.2);
  padding: 0.5rem 1rem;
  border-radius: 50px;
  color: var(--brand-lime);
  font-weight: 600;
  font-size: 0.9rem;
}

.pulse-dot {
  width: 10px;
  height: 10px;
  background: var(--brand-lime);
  border-radius: 50%;
  box-shadow: 0 0 10px var(--brand-lime);
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% { box-shadow: 0 0 0 0 rgba(159,211,45,0.4); }
  70% { box-shadow: 0 0 0 10px rgba(159,211,45,0); }
  100% { box-shadow: 0 0 0 0 rgba(159,211,45,0); }
}

.premium-content {
  padding: 2rem 3rem;
  max-width: 1400px;
  margin: 0 auto;
}

.glass-panel {
  background: var(--surface-panel);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255,255,255,0.05);
  border-radius: var(--radius-xl);
  padding: 2rem;
  box-shadow: 0 8px 32px rgba(0,0,0,0.2);
  position: relative;
  overflow: hidden;
}

.grid-layout {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
  margin-bottom: 2rem;
}

.sos-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  min-height: 400px;
}

.sos-card h2 { font-size: 1.8rem; font-weight: 800; margin-bottom: 1rem; }
.sos-card p { color: var(--text-secondary); max-width: 300px; margin-bottom: 3rem; }

.sos-glow {
  position: absolute;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  width: 200px; height: 200px;
  background: radial-gradient(circle, rgba(255,59,59,0.2) 0%, transparent 70%);
  z-index: 0;
}

.sos-status-badge {
  position: relative;
  z-index: 1;
  width: 220px;
  height: 220px;
  border-radius: 50%;
  background: rgba(159,211,45,0.08);
  border: 2px solid rgba(159,211,45,0.3);
  color: var(--brand-lime);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 1.5rem;
}

.sos-status-icon { font-size: 2.5rem; }
.sos-status-text { font-size: 1.3rem; font-weight: 900; letter-spacing: 0.1em; margin-top: 0.5rem; }
.sos-status-subtext { font-size: 0.75rem; opacity: 0.8; margin-top: 0.75rem; color: var(--text-secondary); line-height: 1.4; }

.stats-container {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.stat-box {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding: 1.5rem 2rem;
  transition: transform 0.3s ease;
}

.stat-box:hover { transform: translateX(10px); }

.stat-box::before {
  content: '';
  position: absolute;
  left: 0; top: 0; bottom: 0;
  width: 4px;
}

.stat-box.accent-teal::before { background: var(--brand-teal-light); }
.stat-box.accent-lime::before { background: var(--brand-lime); }
.stat-box.accent-red::before { background: #FF3B3B; }

.stat-icon {
  font-size: 2.5rem;
  background: rgba(255,255,255,0.05);
  width: 60px; height: 60px;
  display: flex; align-items: center; justify-content: center;
  border-radius: 16px;
}

.stat-info h3 { font-size: 1.8rem; font-weight: 800; line-height: 1.2; }
.stat-info p { color: var(--text-secondary); font-size: 0.9rem; }

.stat-link {
  margin-left: auto;
  width: 40px; height: 40px;
  border-radius: 50%;
  background: rgba(255,255,255,0.05);
  color: white; text-decoration: none;
  display: flex; align-items: center; justify-content: center;
  transition: background 0.3s ease;
}
.stat-link:hover { background: rgba(255,255,255,0.1); }

.history-section {
  padding: 2.5rem;
}

.map-section {
  padding: 2.5rem;
  margin-top: 2rem;
}

.map-frame {
  width: 100%;
  height: 360px;
  border: none;
  border-radius: var(--radius-lg);
}

.history-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.history-header h3 { font-size: 1.5rem; font-weight: 800; }

.badge-premium {
  background: linear-gradient(135deg, var(--brand-lime), var(--brand-teal));
  color: #000;
  padding: 0.4rem 1rem;
  border-radius: 50px;
  font-weight: 700;
  font-size: 0.8rem;
  letter-spacing: 0.05em;
}

.history-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.history-item {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding: 1.5rem;
  background: rgba(0,0,0,0.2);
  border-radius: var(--radius-lg);
  border: 1px solid rgba(255,255,255,0.02);
  transition: background 0.3s ease;
}

.history-item:hover { background: rgba(0,0,0,0.4); }

.history-icon {
  width: 50px; height: 50px;
  background: rgba(255,59,59,0.15);
  color: #FF3B3B;
  border-radius: 14px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.5rem;
}

.history-details h4 { font-size: 1.1rem; font-weight: 700; margin-bottom: 0.25rem; }
.history-details p { color: var(--text-secondary); font-size: 0.95rem; }

.history-meta {
  margin-left: auto;
  text-align: right;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.history-meta .time { font-weight: 600; color: var(--text-primary); }
.history-meta .contacts-notified { font-size: 0.85rem; color: var(--brand-lime); }

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 4rem 2rem;
  text-align: center;
}

.empty-icon {
  width: 80px; height: 80px;
  background: rgba(159,211,45,0.1);
  color: var(--brand-lime);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 2.5rem;
  margin-bottom: 1.5rem;
}

.empty-state p { color: var(--text-secondary); font-size: 1.1rem; }

@media (max-width: 1024px) {
  .grid-layout { grid-template-columns: 1fr; }
  .premium-header { padding: 2rem; }
  .premium-content { padding: 1.5rem; }
}
</style>
