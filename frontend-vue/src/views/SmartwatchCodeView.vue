<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

const auth = useAuthStore()

// State for the Access Code
const code = ref('')
const expiresInSeconds = ref(900)
const loadingCode = ref(true)
const regenerating = ref(false)
const copied = ref(false)
const errorMessage = ref('')

// State for live pairing check
const isWatchLinked = computed(() => !!auth.user?.devices?.watch)
const watchDeviceName = computed(() => auth.user?.device_names?.watch || 'Smartwatch ALERTSYNC')
let countdownTimer = null
let pollingTimer = null

// Controls whether to show the code panel when a watch is already linked
const showNewCodeForm = ref(false)

// Formatted remaining time MM:SS
const formattedTimeRemaining = computed(() => {
  const mins = Math.floor(expiresInSeconds.value / 60)
  const secs = expiresInSeconds.value % 60
  return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
})

const digits = computed(() => {
  if (!code.value) return ['-', '-', '-', '-', '-', '-']
  return code.value.toString().padStart(6, '0').split('')
})

async function fetchCode() {
  loadingCode.value = true
  errorMessage.value = ''
  try {
    const { data } = await api.get('/auth/watch-code')
    code.value = data.code
    expiresInSeconds.value = data.expires_in_seconds || 900
    if (data.is_linked && !auth.user?.devices?.watch) {
      await auth.fetchMe()
    }
  } catch (err) {
    if (auth.token === 'demo-token' || !err.response) {
      code.value = Math.floor(100000 + Math.random() * 900000).toString()
      expiresInSeconds.value = 900
    } else {
      errorMessage.value = err.response?.data?.message || 'Error al obtener el código de acceso.'
    }
  } finally {
    loadingCode.value = false
  }
}

async function regenerateCode() {
  regenerating.value = true
  errorMessage.value = ''
  try {
    const { data } = await api.post('/auth/watch-code/regenerate')
    code.value = data.code
    expiresInSeconds.value = data.expires_in_seconds || 900
  } catch (err) {
    if (auth.token === 'demo-token' || !err.response) {
      code.value = Math.floor(100000 + Math.random() * 900000).toString()
      expiresInSeconds.value = 900
    } else {
      errorMessage.value = err.response?.data?.message || 'Error al regenerar código.'
    }
  } finally {
    regenerating.value = false
  }
}

async function addAnotherWatch() {
  showNewCodeForm.value = true
  await regenerateCode()
}

function copyCode() {
  if (!code.value) return
  navigator.clipboard.writeText(code.value)
  copied.value = true
  setTimeout(() => { copied.value = false }, 2500)
}

async function unlinkWatch() {
  if (!confirm('¿Deseas desvincular tu Smartwatch?')) return
  try {
    const { data } = await api.put('/auth/devices', {
      device: 'watch',
      linked: false,
    })
    auth.applyUserUpdate(data.user)
  } catch (err) {
    alert('No se pudo desvincular el reloj')
  }
}

// Check status periodically to detect when smartwatch links
async function checkWatchStatus() {
  try {
    await auth.fetchMe()
  } catch (e) {
    // silent fail
  }
}

onMounted(async () => {
  // Only fetch code automatically if the watch is NOT yet linked
  if (!isWatchLinked.value) {
    fetchCode()
  } else {
    loadingCode.value = false
  }

  countdownTimer = setInterval(() => {
    // Corre el countdown si: (1) el reloj aún no está vinculado, O (2) se está mostrando el form de agregar otro reloj
    if (expiresInSeconds.value > 0 && (!isWatchLinked.value || showNewCodeForm.value)) {
      expiresInSeconds.value -= 1
    }
  }, 1000)

  // Poll every 4s to detect when watch links
  pollingTimer = setInterval(checkWatchStatus, 4000)
})

onUnmounted(() => {
  if (countdownTimer) clearInterval(countdownTimer)
  if (pollingTimer) clearInterval(pollingTimer)
})
</script>

<template>
  <div class="smartwatch-view">
    <!-- Topbar Header -->
    <header class="topbar">
      <div>
        <div class="topbar-tag">VINCULACIÓN RÁPIDA</div>
        <h1 class="topbar-title">Código de Acceso para <span>Smartwatch</span> ⌚</h1>
        <p class="topbar-subtitle">Inicia sesión en la app Wear OS de tu reloj sin necesidad de escribir correos ni contraseñas complejas.</p>
      </div>

      <div class="watch-status-pill" :class="{ linked: isWatchLinked }">
        <span class="status-indicator-dot"></span>
        <span>{{ isWatchLinked ? 'Smartwatch Conectado' : 'Esperando Conexión' }}</span>
      </div>
    </header>

    <div class="content-container">
      <!-- ===================== ESTADO: RELOJ YA VINCULADO ===================== -->
      <template v-if="isWatchLinked && !showNewCodeForm">
        <!-- Connected Card -->
        <div class="card glass-card connected-hero-card">
          <div class="connected-hero-inner">
            <div class="connected-icon-wrap">
              <span class="connected-icon">⌚</span>
              <span class="connected-badge">✓</span>
            </div>
            <div class="connected-info">
              <h2>¡Smartwatch Conectado!</h2>
              <p>Tu dispositivo <strong>{{ watchDeviceName }}</strong> está activo y puede enviar alertas SOS de emergencia en cualquier momento.</p>
            </div>
          </div>

          <div class="connected-actions">
            <button class="btn btn-primary action-btn" @click="addAnotherWatch" :disabled="regenerating">
              <span v-if="regenerating">⏳ Generando código…</span>
              <span v-else>➕ Agregar otro Smartwatch</span>
            </button>
            <button class="btn btn-danger action-btn" @click="unlinkWatch">
              🔗 Desvincular reloj actual
            </button>
          </div>
        </div>
      </template>

      <!-- ===================== ESTADO: MOSTRAR CÓDIGO (no vinculado o agregar otro) ===================== -->
      <template v-else>
        <!-- Main Code Card -->
        <div class="card glass-card code-main-card">
          <div class="card-header">
            <div class="header-badge">
              <span class="badge-icon">🔐</span>
              {{ showNewCodeForm ? 'CÓDIGO PARA NUEVO SMARTWATCH' : 'CÓDIGO DE VINCULACIÓN' }}
            </div>
            <div v-if="showNewCodeForm" class="back-link" @click="showNewCodeForm = false">← Volver</div>
            <div v-else-if="expiresInSeconds > 0" class="timer-badge">
              ⏱️ Vence en: <strong>{{ formattedTimeRemaining }}</strong>
            </div>
            <div v-else class="timer-badge expired">
              ⚠️ Código Expirado
            </div>
          </div>

          <div v-if="loadingCode || regenerating" class="loading-box">
            <div class="spinner"></div>
            <p>Generando código seguro de vinculación…</p>
          </div>

          <div v-else class="code-display-section">
            <p class="instructions-prompt">Ingresa este código en la pantalla inicial de tu Smartwatch:</p>

            <!-- 6 Digit Box Grid -->
            <div class="digits-grid">
              <div
                v-for="(digit, idx) in digits"
                :key="idx"
                class="digit-box"
                :class="{ pulse: !isWatchLinked }"
              >
                {{ digit }}
              </div>
            </div>

            <!-- Action buttons -->
            <div class="code-actions">
              <button class="btn btn-secondary action-btn" @click="copyCode" :disabled="!code">
                <span v-if="copied">✅ ¡Copiado!</span>
                <span v-else>📋 Copiar código</span>
              </button>

              <button class="btn btn-primary action-btn" @click="regenerateCode" :disabled="regenerating">
                <span v-if="regenerating">Generando…</span>
                <span v-else>🔄 Generar nuevo código</span>
              </button>
            </div>

            <div v-if="errorMessage" class="error-banner">
              ⚠️ {{ errorMessage }}
            </div>
          </div>

          <!-- Connection Status Box -->
          <div class="connection-status-box" :class="{ 'box-connected': isWatchLinked }">
            <div class="status-icon">
              {{ isWatchLinked ? '✅' : '📡' }}
            </div>
            <div class="status-details">
              <h4>{{ isWatchLinked ? '¡Smartwatch Vinculado!' : 'Esperando a que ingreses el código en tu reloj' }}</h4>
              <p>
                {{ isWatchLinked
                  ? `Dispositivo activo: ${watchDeviceName}. Tu reloj puede enviar alertas SOS de emergencia.`
                  : 'Al poner el código en tu reloj inteligente, iniciará sesión automáticamente con tu usuario.'
                }}
              </p>
            </div>
            <button v-if="isWatchLinked" class="unlink-btn" @click="unlinkWatch" title="Desvincular reloj">
              Desvincular
            </button>
          </div>
        </div>
      </template>

      <!-- How It Works Card -->
      <div class="card glass-card guide-card">
        <h3 class="guide-title">📖 ¿Cómo funciona la vinculación?</h3>
        <div class="steps-grid">
          <div class="step-card">
            <div class="step-num">1</div>
            <h4>Abre tu Smartwatch</h4>
            <p>Abre la aplicación ALERTSYNC en tu reloj inteligente Wear OS.</p>
          </div>
          <div class="step-card">
            <div class="step-num">2</div>
            <h4>Ingresa los 6 dígitos</h4>
            <p>Escribe el código mostrado arriba usando el teclado numérico del reloj.</p>
          </div>
          <div class="step-card">
            <div class="step-num">3</div>
            <h4>¡Listo y Protegido!</h4>
            <p>La sesión inicia automáticamente con tu perfil y tus contactos de emergencia.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.smartwatch-view {
  min-height: 100vh;
  padding-bottom: var(--space-8);
  background: radial-gradient(circle at 10% 20%, rgba(29, 93, 110, 0.15) 0%, transparent 40%),
              radial-gradient(circle at 90% 80%, rgba(159, 211, 45, 0.1) 0%, transparent 40%);
}

.topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 2.5rem 3rem 1.5rem;
  border-bottom: 1px solid var(--glass-border);
  gap: 1.5rem;
}

.topbar-tag {
  display: inline-block;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  color: var(--brand-lime);
  background: rgba(159, 211, 45, 0.12);
  border: 1px solid rgba(159, 211, 45, 0.3);
  padding: 3px 10px;
  border-radius: var(--radius-full);
  margin-bottom: 0.5rem;
}

.topbar-title {
  font-size: 2.2rem;
  font-weight: 900;
  letter-spacing: -0.02em;
}

.topbar-title span {
  background: linear-gradient(135deg, var(--brand-lime), var(--brand-teal-light));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.topbar-subtitle {
  color: var(--text-secondary);
  font-size: 0.95rem;
  margin-top: 0.25rem;
  max-width: 600px;
}

.watch-status-pill {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.6rem 1.2rem;
  border-radius: var(--radius-full);
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid var(--glass-border);
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--text-muted);
}

.watch-status-pill.linked {
  background: rgba(34, 197, 94, 0.1);
  border-color: rgba(34, 197, 94, 0.3);
  color: #4ade80;
}

.status-indicator-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #94a3b8;
}

.watch-status-pill.linked .status-indicator-dot {
  background: #22c55e;
  box-shadow: 0 0 10px rgba(34, 197, 94, 0.8);
}

.content-container {
  display: flex;
  flex-direction: column;
  gap: 2rem;
  padding: 2.5rem 3rem;
  max-width: 900px;
  margin: 0 auto;
}

/* Glass Card */
.glass-card {
  background: var(--surface-panel);
  backdrop-filter: blur(20px);
  border: 1px solid var(--glass-border);
  border-radius: var(--radius-xl);
  padding: 2.5rem;
  box-shadow: 0 12px 35px rgba(0,0,0,0.25);
}

.code-main-card {
  position: relative;
  overflow: hidden;
}

.code-main-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 4px;
  background: linear-gradient(90deg, var(--brand-lime), var(--brand-teal));
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 2rem;
}

.header-badge {
  font-size: 0.82rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  color: var(--brand-lime);
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.timer-badge {
  font-size: 0.85rem;
  color: var(--text-secondary);
  background: rgba(255, 255, 255, 0.05);
  padding: 5px 14px;
  border-radius: var(--radius-full);
  border: 1px solid var(--glass-border);
}

.timer-badge.expired {
  background: rgba(239, 68, 68, 0.1);
  border-color: rgba(239, 68, 68, 0.3);
  color: #f87171;
}

.instructions-prompt {
  font-size: 1.05rem;
  color: var(--text-secondary);
  margin-bottom: 1.75rem;
  text-align: center;
}

/* 6 Digits Grid */
.digits-grid {
  display: flex;
  justify-content: center;
  gap: 1rem;
  margin-bottom: 2.5rem;
}

.digit-box {
  width: 68px;
  height: 84px;
  background: rgba(10, 15, 30, 0.85);
  border: 2px solid rgba(159, 211, 45, 0.35);
  border-radius: var(--radius-lg);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.6rem;
  font-weight: 900;
  font-family: monospace;
  color: var(--brand-lime);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.35);
  transition: all 0.25s ease;
}

.digit-box:hover {
  transform: translateY(-4px);
  border-color: var(--brand-lime);
  box-shadow: 0 0 24px rgba(159, 211, 45, 0.45);
}

.code-actions {
  display: flex;
  gap: 1.25rem;
  justify-content: center;
  margin-bottom: 2.5rem;
}

.action-btn {
  padding: 0.85rem 1.8rem;
  font-weight: 700;
  font-size: 0.95rem;
}

.connection-status-box {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  padding: 1.25rem 1.75rem;
  border-radius: var(--radius-lg);
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid var(--glass-border);
  transition: all 0.3s ease;
}

.connection-status-box.box-connected {
  background: rgba(34, 197, 94, 0.08);
  border-color: rgba(34, 197, 94, 0.3);
}

.status-icon {
  font-size: 2rem;
  flex-shrink: 0;
}

.status-details h4 {
  font-size: 1.05rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
}

.status-details p {
  font-size: 0.88rem;
  color: var(--text-secondary);
  line-height: 1.45;
}

.unlink-btn {
  margin-left: auto;
  background: transparent;
  border: 1px solid rgba(239, 68, 68, 0.4);
  color: #fca5a5;
  padding: 0.45rem 0.9rem;
  border-radius: var(--radius-md);
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}

.unlink-btn:hover {
  background: rgba(239, 68, 68, 0.15);
}

/* Guide Card */
.guide-title {
  font-size: 1.2rem;
  font-weight: 800;
  margin-bottom: 1.5rem;
}

.steps-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.5rem;
}

.step-card {
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: var(--radius-lg);
  padding: 1.5rem 1.25rem;
  position: relative;
}

.step-num {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--brand-lime);
  color: #0b1329;
  font-weight: 900;
  font-size: 0.95rem;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 0.85rem;
}

.step-card h4 {
  font-size: 0.98rem;
  font-weight: 700;
  margin-bottom: 0.4rem;
}

.step-card p {
  font-size: 0.83rem;
  color: var(--text-secondary);
  line-height: 1.45;
}

.loading-box {
  text-align: center;
  padding: 3rem 0;
  color: var(--text-secondary);
}

.spinner {
  width: 36px;
  height: 36px;
  border: 3px solid rgba(255,255,255,0.1);
  border-top-color: var(--brand-lime);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error-banner {
  margin-top: 1rem;
  background: rgba(239, 68, 68, 0.15);
  border: 1px solid rgba(239, 68, 68, 0.3);
  color: #fca5a5;
  padding: 0.75rem 1rem;
  border-radius: var(--radius-md);
  font-size: 0.85rem;
  text-align: center;
}

/* =========================================================
   Connected Hero Card (watch already linked)
   ========================================================= */
.connected-hero-card {
  border-color: rgba(34, 197, 94, 0.35) !important;
  background: linear-gradient(135deg,
    rgba(34, 197, 94, 0.06) 0%,
    rgba(10, 15, 30, 0.7) 100%) !important;
}

.connected-hero-inner {
  display: flex;
  align-items: center;
  gap: 2rem;
  padding-bottom: 2rem;
  border-bottom: 1px solid rgba(34, 197, 94, 0.15);
  margin-bottom: 2rem;
}

.connected-icon-wrap {
  position: relative;
  flex-shrink: 0;
}

.connected-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: rgba(34, 197, 94, 0.12);
  border: 2px solid rgba(34, 197, 94, 0.4);
  font-size: 2.5rem;
  box-shadow: 0 0 30px rgba(34, 197, 94, 0.2);
}

.connected-badge {
  position: absolute;
  bottom: -4px;
  right: -4px;
  width: 26px;
  height: 26px;
  border-radius: 50%;
  background: #22c55e;
  color: #0a0f1e;
  font-weight: 900;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid var(--surface-card, #0f172a);
}

.connected-info h2 {
  font-size: 1.7rem;
  font-weight: 900;
  color: #86efac;
  margin-bottom: 0.5rem;
}

.connected-info p {
  color: var(--text-secondary);
  line-height: 1.55;
}

.connected-info strong {
  color: var(--text-primary, #e2e8f0);
}

.connected-actions {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

/* Danger button */
.btn-danger {
  background: transparent;
  border: 1px solid rgba(239, 68, 68, 0.45);
  color: #fca5a5;
  transition: all 0.2s;
}

.btn-danger:hover {
  background: rgba(239, 68, 68, 0.12);
  border-color: rgba(239, 68, 68, 0.7);
}

/* Back link */
.back-link {
  font-size: 0.85rem;
  color: var(--text-secondary);
  cursor: pointer;
  padding: 4px 10px;
  border-radius: var(--radius-md);
  border: 1px solid var(--glass-border);
  transition: all 0.2s;
  user-select: none;
}

.back-link:hover {
  color: var(--brand-lime);
  border-color: rgba(159, 211, 45, 0.4);
  background: rgba(159, 211, 45, 0.05);
}

@media (max-width: 768px) {
  .topbar {
    padding: 1.5rem;
    flex-direction: column;
    align-items: flex-start;
  }
  .content-container {
    padding: 1.5rem;
  }
  .steps-grid {
    grid-template-columns: 1fr;
  }
  .digits-grid {
    gap: 0.5rem;
  }
  .digit-box {
    width: 46px;
    height: 60px;
    font-size: 1.8rem;
  }
  .connected-hero-inner {
    flex-direction: column;
    text-align: center;
  }
  .connected-actions {
    flex-direction: column;
  }
}
</style>
