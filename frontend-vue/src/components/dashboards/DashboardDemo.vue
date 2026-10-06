<script setup>
import { RouterLink } from 'vue-router'
</script>

<template>
  <div class="demo-wrapper">
    <!-- Static fake dashboard behind -->
    <div class="fake-dashboard">
      <div class="topbar">
        <h1>Menú</h1>
        <p>Demo — Sistema inactivo</p>
      </div>
      <div class="page-content">
        <div class="stats-grid">
          <div class="stat-card"><h2>0</h2><p>Alertas</p></div>
          <div class="stat-card"><h2>0</h2><p>Contactos</p></div>
          <div class="stat-card"><h2>0</h2><p>Dispositivos</p></div>
        </div>
      </div>
    </div>

    <!-- Overlay content -->
    <div class="demo-overlay">
      <div class="demo-card glass-panel">
        <div class="lock-icon">🔒</div>
        <h2>Modo Demo Activo</h2>
        <p>Estás viendo una vista previa interactiva del menú de Alert Sync.</p>
        <p class="desc-secondary">Para comenzar a agregar tus contactos de emergencia, configurar tu Smart Watch o Alexa y activar el protocolo de alerta SOS, necesitas contratar un plan de suscripción.</p>
        
        <RouterLink to="/dashboard/plans" class="btn btn-primary mt-6">
          ⭐ Ver Planes de Suscripción
        </RouterLink>
      </div>
    </div>
  </div>
</template>

<style scoped>
.demo-wrapper {
  position: relative;
  min-height: 100vh;
  overflow: hidden;
}
.fake-dashboard {
  filter: blur(8px) grayscale(50%);
  opacity: 0.3;
  pointer-events: none;
  min-height: 100vh;
  background: var(--bg-color);
}
.topbar { padding: 2rem; }
.stats-grid { display: flex; gap: 1rem; padding: 2rem; }
.stat-card { background: var(--glass-bg); padding: 2rem; flex: 1; border-radius: 12px; }

.demo-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  z-index: 10;
  background: rgba(10, 15, 30, 0.5);
  backdrop-filter: blur(4px);
}

.demo-card {
  text-align: center;
  max-width: 550px;
  width: 100%;
  padding: 3rem 2.5rem;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.lock-icon {
  font-size: 3rem;
  margin-bottom: 1.5rem;
  animation: pulse 2s infinite;
}

.glass-panel {
  background: var(--surface-nav);
  border: 1px solid var(--glass-border);
  border-radius: var(--radius-xl);
  backdrop-filter: blur(20px);
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
}

.demo-card h2 {
  font-size: 2.2rem;
  font-weight: 800;
  color: var(--brand-lime);
  margin-bottom: 1rem;
}

.demo-card p {
  font-size: 1.1rem;
  color: var(--text-primary);
  line-height: 1.6;
  margin-bottom: 1rem;
}

.demo-card .desc-secondary {
  font-size: 0.95rem;
  color: var(--text-secondary);
}

.btn-primary {
  display: inline-block;
  text-decoration: none;
  font-weight: 700;
  padding: 1rem 2rem;
  font-size: 1.1rem;
}

@keyframes pulse {
  0% { transform: scale(1); }
  50% { transform: scale(1.1); }
  100% { transform: scale(1); }
}
</style>
