<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const contacts = ref([])
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

onMounted(loadContacts)

async function loadContacts() {
  loading.value = true
  if (auth.token === 'demo-token') {
    contacts.value = [
      { _id: '1', name: 'Mamá', phone: '+52 33 1111 2222', notify_sms: true, notify_call: true },
      { _id: '2', name: 'Papá', phone: '+52 33 3333 4444', notify_sms: true },
    ]
    loading.value = false
    return
  }
  try {
    const { data } = await api.get('/contacts')
    contacts.value = data.contacts
  } catch {
    error.value = 'No se pudieron cargar los contactos.'
  } finally {
    loading.value = false
  }
}

async function saveContact() {
  error.value = ''
  try {
    if (auth.token === 'demo-token') {
      contacts.value.push({ ...form.value, _id: String(Date.now()) })
    } else {
      const { data } = await api.post('/contacts', form.value)
      contacts.value.push(data.contact)
    }
    form.value = { name: '', phone: '', email: '', relationship: '', notify_sms: true, notify_call: false, notify_email: false, priority: 1 }
    showForm.value = false
  } catch (e) {
    error.value = e.response?.data?.message || 'Error al guardar contacto.'
  }
}

async function removeContact(id) {
  if (auth.token === 'demo-token') {
    contacts.value = contacts.value.filter(c => c._id !== id)
    return
  }
  await api.delete(`/contacts/${id}`)
  contacts.value = contacts.value.filter(c => c._id !== id)
}

const resendingId = ref(null)
const toast = ref('')

async function resendVerification(contact) {
  resendingId.value = contact._id
  error.value = ''
  toast.value = ''
  try {
    const { data } = await api.post(`/contacts/${contact._id}/resend-verification`)
    toast.value = data.message
    setTimeout(() => { toast.value = '' }, 4000)
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
        <p>Gestiona quién recibirá tus alertas SOS</p>
      </div>
      <button class="btn btn-primary btn-sm" @click="showForm = !showForm">
        {{ showForm ? 'Cancelar' : '+ Agregar contacto' }}
      </button>
    </div>

    <div class="page-content">
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
        <div v-for="contact in contacts" :key="contact._id" class="contact-card">
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
            <p v-if="contact.email" class="badge" :class="contact.verified ? 'badge-green' : 'badge-orange'">
              {{ contact.verified ? '✅ Confirmado' : '⏳ Pendiente' }}
            </p>
          </div>
          <button
            v-if="contact.email && !contact.verified"
            class="link-btn mt-2"
            :disabled="resendingId === contact._id"
            @click="resendVerification(contact)"
          >
            {{ resendingId === contact._id ? 'Enviando...' : 'Reenviar confirmación' }}
          </button>
          <button class="btn btn-secondary btn-sm mt-4" @click="removeContact(contact._id)">Eliminar</button>
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
</style>
