<script setup>
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

const props = defineProps({
  device: { type: String, required: true }, // 'watch' | 'alexa'
  label: { type: String, required: true },
  icon: { type: String, required: true },
  linked: { type: Boolean, default: false },
  deviceName: { type: String, default: null },
})

const emit = defineEmits(['close'])

const auth = useAuthStore()

const SIMULATED_DEVICES = {
  watch: ['ALERTSYNC Watch Pro', 'Apple Watch Series 9', 'Galaxy Watch 6'],
  alexa: ['Echo Dot (4ª Gen)', 'Echo Show 5', 'Echo Auto'],
}

// idle | scanning | found | linking | success | error
const phase = ref('idle')
const usedRealBluetooth = ref(false)
const foundDevices = ref([])
const selected = ref(null)
const fallbackNotice = ref('')
const errorMessage = ref('')

function close() {
  emit('close')
}

async function startScan() {
  errorMessage.value = ''
  fallbackNotice.value = ''
  selected.value = null
  phase.value = 'scanning'

  if (navigator.bluetooth && typeof navigator.bluetooth.requestDevice === 'function') {
    try {
      const btDevice = await navigator.bluetooth.requestDevice({ acceptAllDevices: true })
      usedRealBluetooth.value = true
      foundDevices.value = [{ id: btDevice.id, name: btDevice.name || 'Dispositivo Bluetooth' }]
      selected.value = foundDevices.value[0]
      phase.value = 'found'
      return
    } catch (e) {
      // Usuario canceló el selector nativo o no se encontró ningún dispositivo real cerca.
      fallbackNotice.value = 'No se seleccionó ningún dispositivo Bluetooth real. Mostrando dispositivos simulados para la vinculación.'
      runSimulatedScan()
      return
    }
  }

  fallbackNotice.value = 'Tu navegador no soporta Bluetooth Web (disponible en Chrome/Edge). Mostrando vinculación simulada.'
  runSimulatedScan()
}

function runSimulatedScan() {
  usedRealBluetooth.value = false
  phase.value = 'scanning'
  setTimeout(() => {
    foundDevices.value = (SIMULATED_DEVICES[props.device] || []).map((name, i) => ({ id: `sim-${i}`, name }))
    phase.value = 'found'
  }, 1400)
}

function selectDevice(d) {
  selected.value = d
}

async function confirmLink() {
  if (!selected.value) return
  phase.value = 'linking'
  errorMessage.value = ''
  try {
    const { data } = await api.put('/auth/devices', {
      device: props.device,
      linked: true,
      name: selected.value.name,
    })
    auth.applyUserUpdate(data.user)
    phase.value = 'success'
  } catch (e) {
    errorMessage.value = e.response?.data?.message || 'No se pudo vincular el dispositivo.'
    phase.value = 'found'
  }
}

async function unlink() {
  phase.value = 'linking'
  try {
    const { data } = await api.put('/auth/devices', {
      device: props.device,
      linked: false,
    })
    auth.applyUserUpdate(data.user)
    close()
  } catch (e) {
    errorMessage.value = e.response?.data?.message || 'No se pudo desvincular el dispositivo.'
    phase.value = 'idle'
  }
}
</script>

<template>
  <div class="modal-backdrop" @click.self="close">
    <div class="device-modal">
      <div class="modal-header">
        <h2>{{ icon }} {{ label }}</h2>
        <button class="close-btn" @click="close">×</button>
      </div>

      <!-- Ya vinculado, estado inicial -->
      <div v-if="phase === 'idle' && linked" class="linked-state">
        <div class="status-row">
          <span class="status-dot on"></span>
          <div>
            <div class="status-title">Vinculado</div>
            <div class="status-sub">{{ deviceName || 'Dispositivo conectado' }}</div>
          </div>
        </div>
        <div class="modal-actions">
          <button class="btn btn-secondary" @click="startScan">Vincular otro dispositivo</button>
          <button class="btn btn-danger-outline" @click="unlink">Desvincular</button>
        </div>
      </div>

      <!-- No vinculado, estado inicial -->
      <div v-else-if="phase === 'idle'" class="idle-state">
        <p class="hint-text">Vincula tu {{ label }} para recibir alertas SOS desde el dispositivo.</p>
        <button class="btn btn-primary w-full" @click="startScan">🔗 Buscar dispositivos</button>
      </div>

      <!-- Escaneando -->
      <div v-else-if="phase === 'scanning'" class="scanning-state">
        <div class="spinner"></div>
        <p>Buscando dispositivos Bluetooth cercanos…</p>
      </div>

      <!-- Encontrados -->
      <div v-else-if="phase === 'found'" class="found-state">
        <p v-if="fallbackNotice" class="fallback-notice">⚠️ {{ fallbackNotice }}</p>
        <p v-if="errorMessage" class="error-notice">{{ errorMessage }}</p>
        <p class="hint-text">Selecciona un dispositivo para vincular:</p>
        <ul class="device-list">
          <li v-for="d in foundDevices" :key="d.id">
            <button
              class="device-option"
              :class="{ selected: selected?.id === d.id }"
              @click="selectDevice(d)"
            >
              <span>{{ icon }}</span>
              {{ d.name }}
              <span v-if="selected?.id === d.id" class="check">✓</span>
            </button>
          </li>
        </ul>
        <button class="btn btn-primary w-full" :disabled="!selected" @click="confirmLink">
          Vincular {{ selected?.name || 'dispositivo' }}
        </button>
      </div>

      <!-- Vinculando -->
      <div v-else-if="phase === 'linking'" class="scanning-state">
        <div class="spinner"></div>
        <p>Vinculando…</p>
      </div>

      <!-- Éxito -->
      <div v-else-if="phase === 'success'" class="success-state">
        <div class="success-icon">✅</div>
        <p>¡{{ label }} vinculado correctamente!</p>
        <button class="btn btn-primary w-full" @click="close">Listo</button>
      </div>

      <p class="method-note">
        {{ usedRealBluetooth ? 'Vinculado vía Bluetooth real de tu dispositivo.' : 'Compatible con Bluetooth real en Chrome/Edge · vinculación simulada en otros navegadores.' }}
      </p>
    </div>
  </div>
</template>

<style scoped>
.modal-backdrop {
  position: fixed; inset: 0; background: rgba(10, 15, 30, 0.7);
  backdrop-filter: blur(4px);
  display: flex; align-items: center; justify-content: center;
  z-index: 200; padding: var(--space-6);
}
.device-modal {
  width: 100%; max-width: 400px;
  background: var(--bg-elevated); border: 1px solid var(--glass-border);
  border-radius: var(--radius-lg); padding: var(--space-6);
}
.modal-header {
  display: flex; justify-content: space-between; align-items: center;
  margin-bottom: var(--space-5);
}
.modal-header h2 { font-size: 1.2rem; font-weight: 800; }
.close-btn {
  background: transparent; border: none; font-size: 1.5rem; line-height: 1;
  color: var(--text-muted); cursor: pointer; padding: 0 var(--space-2);
}
.close-btn:hover { color: var(--text-primary); }

.hint-text { color: var(--text-secondary); font-size: 0.9rem; margin-bottom: var(--space-4); line-height: 1.6; }
.w-full { width: 100%; }

.status-row { display: flex; align-items: center; gap: var(--space-3); margin-bottom: var(--space-5); }
.status-dot { width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0; }
.status-dot.on { background: #22c55e; box-shadow: 0 0 8px rgba(34,197,94,0.6); }
.status-title { font-weight: 700; }
.status-sub { font-size: 0.85rem; color: var(--text-muted); }
.modal-actions { display: flex; flex-direction: column; gap: var(--space-3); }
.btn-danger-outline {
  background: transparent; border: 1px solid rgba(239,68,68,0.4); color: #FCA5A5;
  padding: var(--space-3) var(--space-4); border-radius: var(--radius-md);
  font-weight: 600; cursor: pointer; font-size: 0.875rem;
}
.btn-danger-outline:hover { background: rgba(239,68,68,0.1); }

.scanning-state { text-align: center; padding: var(--space-6) 0; color: var(--text-secondary); }
.spinner {
  width: 36px; height: 36px; margin: 0 auto var(--space-4);
  border: 3px solid var(--glass-border); border-top-color: var(--brand-lime);
  border-radius: 50%; animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.device-list { list-style: none; display: flex; flex-direction: column; gap: var(--space-2); margin-bottom: var(--space-5); }
.device-option {
  width: 100%; display: flex; align-items: center; gap: var(--space-3);
  padding: var(--space-3) var(--space-4); border-radius: var(--radius-md);
  background: var(--glass-bg); border: 1px solid var(--glass-border);
  color: var(--text-primary); font-size: 0.9rem; cursor: pointer; text-align: left;
}
.device-option:hover { border-color: var(--brand-lime); }
.device-option.selected { border-color: var(--brand-lime); background: rgba(159,211,45,0.08); }
.device-option .check { margin-left: auto; color: var(--brand-lime); font-weight: 700; }

.fallback-notice {
  font-size: 0.8rem; color: #f0b429; background: rgba(240,180,41,0.1);
  border: 1px solid rgba(240,180,41,0.25); border-radius: var(--radius-md);
  padding: var(--space-3); margin-bottom: var(--space-4); line-height: 1.5;
}
.error-notice {
  font-size: 0.8rem; color: #FCA5A5; background: rgba(239,68,68,0.1);
  border: 1px solid rgba(239,68,68,0.25); border-radius: var(--radius-md);
  padding: var(--space-3); margin-bottom: var(--space-4);
}

.success-state { text-align: center; padding: var(--space-4) 0; }
.success-icon { font-size: 2.5rem; margin-bottom: var(--space-3); }
.success-state p { margin-bottom: var(--space-5); color: var(--text-secondary); }

.method-note { margin-top: var(--space-5); font-size: 0.7rem; color: var(--text-muted); text-align: center; }
</style>
