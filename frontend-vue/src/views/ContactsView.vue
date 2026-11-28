<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const contacts = ref([])

// Límite de contactos según el plan (igual que en la app del iPhone): Básico 5, Premium 15.
const planName = computed(() => {
  const plan = (auth.user?.plan || 'Demo').toLowerCase()
  if (plan === 'premium') return 'Premium'
  if (plan === 'básico' || plan === 'basico') return 'Básico'
  return 'Demo'
})
const maxContacts = computed(() => ({ Premium: 15, 'Básico': 5 })[planName.value] || 0)
const atLimit = computed(() => contacts.value.length >= maxContacts.value)
const limitText = computed(() => {
  if (maxContacts.value === 0) return 'Contrata un plan para agregar contactos de emergencia.'
  if (atLimit.value) {
    return planName.value === 'Premium'
      ? 'Llegaste al límite de tu plan Premium.'
      : 'Llegaste al límite de tu plan Básico. Con Premium puedes tener hasta 15.'
  }
  const left = maxContacts.value - contacts.value.length
  return `Puedes agregar ${left} contacto${left === 1 ? '' : 's'} más.`
})
const loading = ref(true)
const error = ref('')
const showForm = ref(false)
const form = ref({
  name: '',
  phone: '',
  email: '',
  relationship: '',
  notify_sms: true,
  notify_call: false,
  notify_email: false,
  priority: 1,
})

// ── Tiempo real ─────────────────────────────────────────────
// Mientras haya invitaciones pendientes se consulta cada 5 s (para ver al momento
// "Confirmado" o "Rechazado"); si no, cada 20 s (cambios hechos desde la app).
const FAST_MS = 5_000
const SLOW_MS = 20_000
const now = ref(Date.now())
let pollTimer = null
let clockTimer = null

const hasPending = computed(() => contacts.value.some((c) => statusOf(c) === 'pending'))

function schedulePoll() {
  clearTimeout(pollTimer)
  pollTimer = setTimeout(async () => {
    if (!document.hidden && auth.token !== 'demo-token') await loadContacts({ silent: true })
    schedulePoll()
  }, hasPending.value ? FAST_MS : SLOW_MS)
}
function onVisibility() { if (!document.hidden) loadContacts({ silent: true }) }

onMounted(async () => {
  await loadContacts()
  schedulePoll()
  clockTimer = setInterval(() => { now.value = Date.now() }, 1000)
  document.addEventListener('visibilitychange', onVisibility)
})
onBeforeUnmount(() => {
  clearTimeout(pollTimer)
  clearInterval(clockTimer)
  document.removeEventListener('visibilitychange', onVisibility)
})

/** confirmed · pending · rejected · no_email (el "pendiente" vencido cuenta como rechazado). */
function statusOf(contact) {
  if (contact.verified) return 'confirmed'
  if (!contact.email) return 'no_email'
  if (contact.status === 'rejected') return 'rejected'
  const expires = contact.verification_token_expires_at ? Date.parse(contact.verification_token_expires_at) : 0
  return expires > now.value ? 'pending' : 'rejected'
}

function timeLeft(contact) {
  const ms = Date.parse(contact.verification_token_expires_at) - now.value
  if (!(ms > 0)) return ''
  const m = Math.floor(ms / 60000), s = Math.floor((ms % 60000) / 1000)
  return `${m}:${String(s).padStart(2, '0')}`
}

const STATUS_BADGE = {
  confirmed: ['badge-green', '✅ Confirmado'],
  pending: ['badge-orange', '⏳ Pendiente'],
  rejected: ['badge-red', '❌ Rechazado'],
}

async function loadContacts({ silent = false } = {}) {
  if (!silent) loading.value = true
  if (auth.token === 'demo-token') {
    contacts.value = [
      { id: '1', name: 'Mamá', phone: '+52 33 1111 2222', notify_sms: true, notify_call: true },
      { id: '2', name: 'Papá', phone: '+52 33 3333 4444', notify_sms: true },
    ]
    loading.value = false
    return
  }
  try {
    const { data } = await api.get('/contacts')
    contacts.value = data.contacts
  } catch {
    if (!silent) error.value = 'No se pudieron cargar los contactos.'
  } finally {
    loading.value = false
  }
}

async function saveContact() {
  error.value = ''
  try {
    if (auth.token === 'demo-token') {
      contacts.value.push({ ...form.value, id: String(Date.now()) })
    } else {
      const { data } = await api.post('/contacts', form.value)
      contacts.value.push(data.contact)
      if (data.contact?.email) {
        toast.value = `Le enviamos un correo a ${data.contact.email}. Tiene 15 minutos para aceptar.`
        setTimeout(() => { toast.value = '' }, 6000)
      }
      schedulePoll()
    }
    form.value = { name: '', phone: '', email: '', relationship: '', notify_sms: true, notify_call: false, notify_email: false, priority: 1 }
    showForm.value = false
  } catch (e) {
    error.value = e.response?.data?.message || 'Error al guardar contacto.'
  }
}

async function removeContact(id) {
  if (auth.token === 'demo-token') {
    contacts.value = contacts.value.filter(c => c.id !== id)
    return
  }
  await api.delete(`/contacts/${id}`)
  contacts.value = contacts.value.filter(c => c.id !== id)
}

const resendingId = ref(null)
const toast = ref('')

async function resendVerification(contact) {
  resendingId.value = contact.id
  error.value = ''
  toast.value = ''
  try {
    const { data } = await api.post(`/contacts/${contact.id}/resend-verification`)
    toast.value = data.message
    setTimeout(() => { toast.value = '' }, 4000)
    await loadContacts({ silent: true })
    schedulePoll()
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudo reenviar la confirmación.'
  } finally {
    resendingId.value = null
  }
}

function channels(contact) {
  return [
    contact.notify_sms ? 'SMS' : null,
    contact.notify_call ? 'Llamada' : null,
    contact.notify_email ? 'Email' : null,
  ].filter(Boolean).join(' + ') || 'Sin canal'
}
</script>

<template>
  <div>
    <div class="topbar">
      <div>
        <h1>Contactos de Emergencia</h1>
        <p>{{ contacts.length }}/{{ maxContacts }} contactos · Gestiona quién recibirá tus alertas SOS</p>
      </div>
      <button class="btn btn-primary btn-sm" :disabled="!showForm && atLimit"
              :title="atLimit ? limitText : ''" @click="showForm = !showForm">
        {{ showForm ? 'Cancelar' : '+ Agregar contacto' }}
      </button>
    </div>

    <div class="page-content">
      <div class="limit-card" :class="{ premium: planName === 'Premium', full: atLimit }">
        <div class="limit-head">
          <strong class="limit-count">{{ contacts.length }} / {{ maxContacts }}</strong>
          <span class="limit-label">contactos</span>
          <span class="limit-plan">Plan {{ planName }}</span>
        </div>
        <div class="limit-bar"><div class="limit-fill" :style="{ width: (maxContacts ? Math.min(contacts.length / maxContacts, 1) * 100 : 0) + '%' }"></div></div>
        <p class="limit-text">{{ limitText }}</p>
      </div>

      <div v-if="error" class="error-box">{{ error }}</div>
      <div v-if="toast" class="toast-box">{{ toast }}</div>

      <form v-if="showForm" class="contact-form" @submit.prevent="saveContact">
        <div class="form-grid">
          <input v-model="form.name" class="form-input" placeholder="Nombre *" required />
          <input v-model="form.phone" class="form-input" placeholder="Teléfono" />
          <input v-model="form.email" type="email" class="form-input" placeholder="Email" />
          <input v-model="form.relationship" class="form-input" placeholder="Relación (ej. Mamá)" />
        </div>
        <div class="checkboxes">
          <label><input v-model="form.notify_sms" type="checkbox" /> SMS</label>
          <label><input v-model="form.notify_call" type="checkbox" /> Llamada</label>
          <label><input v-model="form.notify_email" type="checkbox" /> Email</label>
        </div>
        <button type="submit" class="btn btn-primary">Guardar contacto</button>
      </form>

      <div v-if="loading" class="text-muted">Cargando contactos...</div>

      <div v-else-if="contacts.length" class="contacts-grid">
        <div v-for="contact in contacts" :key="contact.id" class="contact-card">
          <div class="contact-header">
            <div class="avatar">{{ contact.name.charAt(0) }}</div>
            <div>
              <h3>{{ contact.name }}</h3>
              <p>{{ contact.relationship || 'Contacto de emergencia' }}</p>
            </div>
          </div>
          <p class="contact-detail">📱 {{ contact.phone || 'Sin teléfono' }}</p>
          <p class="contact-detail">✉️ {{ contact.email || 'Sin email' }}</p>
          <div class="badge-row">
            <p class="badge badge-cyan">{{ channels(contact) }}</p>
            <p v-if="STATUS_BADGE[statusOf(contact)]" class="badge" :class="STATUS_BADGE[statusOf(contact)][0]">
              {{ STATUS_BADGE[statusOf(contact)][1] }}
            </p>
          </div>
          <p v-if="statusOf(contact) === 'pending'" class="status-note text-sm" aria-live="polite">
            Esperando respuesta · se rechaza en {{ timeLeft(contact) }}
          </p>
          <p v-else-if="statusOf(contact) === 'rejected'" class="status-note text-sm">
            {{ contact.declined_at ? 'Rechazó la invitación.' : 'No respondió en 15 minutos.' }} No aparece en tu reloj.
          </p>
          <button
            v-if="['pending', 'rejected'].includes(statusOf(contact))"
            class="link-btn mt-2"
            :disabled="resendingId === contact.id"
            @click="resendVerification(contact)"
          >
            {{ resendingId === contact.id ? 'Enviando...' : statusOf(contact) === 'rejected' ? 'Volver a invitar' : 'Reenviar confirmación' }}
          </button>
          <button class="btn btn-secondary btn-sm mt-4" @click="removeContact(contact.id)">Eliminar</button>
        </div>
      </div>

      <div v-else class="empty-state">
        <p>No tienes contactos registrados. Agrega al menos uno para recibir alertas SOS.</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.topbar {
  display: flex; align-items: center; justify-content: space-between;
  padding: var(--space-4) var(--space-8);
  border-bottom: 1px solid var(--glass-border);
}
.page-content { padding: var(--space-8); }
.error-box {
  padding: var(--space-4); margin-bottom: var(--space-4);
  background: rgba(239,68,68,0.1); border-radius: var(--radius-md); color: #FCA5A5;
}
.toast-box {
  padding: var(--space-4); margin-bottom: var(--space-4);
  background: rgba(159,211,45,0.12); border: 1px solid rgba(159,211,45,0.3);
  border-radius: var(--radius-md); color: var(--brand-lime);
}
.badge-row { display: flex; gap: var(--space-2); flex-wrap: wrap; margin-bottom: var(--space-2); }
.link-btn {
  display: block; background: none; border: none; cursor: pointer;
  color: var(--brand-teal-light); font-weight: 600; font-size: 0.8rem; padding: 0;
}
.link-btn:hover { color: var(--brand-lime); }
.link-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.contact-form {
  background: var(--glass-bg); border: 1px solid var(--glass-border);
  border-radius: var(--radius-xl); padding: var(--space-6); margin-bottom: var(--space-8);
  display: flex; flex-direction: column; gap: var(--space-4);
}
.form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: var(--space-4); }
.checkboxes { display: flex; gap: var(--space-4); color: var(--text-secondary); }
.contacts-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: var(--space-5);
}
.contact-card {
  background: var(--glass-bg); border: 1px solid var(--glass-border);
  border-radius: var(--radius-xl); padding: var(--space-6);
}
.contact-header { display: flex; gap: var(--space-4); align-items: center; margin-bottom: var(--space-4); }
.avatar {
  width: 48px; height: 48px; border-radius: 50%;
  background: linear-gradient(135deg, var(--brand-teal), var(--brand-lime));
  display: flex; align-items: center; justify-content: center; font-weight: 700;
}
.contact-detail { font-size: 0.85rem; color: var(--text-secondary); margin-bottom: var(--space-2); }
.empty-state {
  text-align: center; padding: var(--space-16); color: var(--text-muted);
  background: var(--glass-bg); border-radius: var(--radius-xl);
}
@media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } }
.limit-card {
  margin-bottom: var(--space-5); padding: var(--space-4) var(--space-5);
  background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: var(--radius-lg);
  --limit-accent: var(--brand-lime);
}
.limit-card.premium { --limit-accent: #F5C542; }
.limit-card.full { --limit-accent: #F59E0B; }
.limit-head { display: flex; align-items: baseline; gap: 0.5rem; margin-bottom: 0.6rem; }
.limit-count { font-size: 1.5rem; font-weight: 900; }
.limit-label { color: var(--text-secondary); font-size: 0.85rem; }
.limit-plan {
  margin-left: auto; padding: 2px 10px; border-radius: 999px; font-size: 0.75rem; font-weight: 800;
  background: var(--limit-accent); color: #10140A;
}
.limit-bar { height: 8px; border-radius: 999px; background: rgba(255,255,255,0.08); overflow: hidden; }
.limit-fill { height: 100%; background: var(--limit-accent); transition: width 0.3s ease; }
.limit-text { margin-top: 0.5rem; font-size: 0.85rem; color: var(--text-secondary); }
.limit-card.full .limit-text { color: #F59E0B; }
.status-note { color: var(--text-secondary); margin-top: var(--space-2); }
</style>
