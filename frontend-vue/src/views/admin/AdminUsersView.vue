<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const users = ref([])
const loading = ref(true)
const error = ref('')
const toast = ref('')
const search = ref('')
const planFilter = ref('')

const showModal = ref(false)
const modalMode = ref('create-user') // 'create-user' | 'create-admin' | 'edit'
const modalError = ref('')
const saving = ref(false)
const form = ref({ id: null, name: '', email: '', username: '', phone: '', plan: 'Demo', role: 'user', devices: { watch: false, alexa: false } })

const showPasswordModal = ref(false)
const passwordTarget = ref(null)
const newPassword = ref('')
const passwordError = ref('')

const showDetailModal = ref(false)
const detailLoading = ref(false)
const detailError = ref('')
const detailUser = ref(null)
const detailContacts = ref([])
const detailAlerts = ref([])
const detailPayments = ref([])

async function loadUsers() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get('/admin/users', {
      params: { q: search.value || undefined, plan: planFilter.value || undefined },
    })
    users.value = data.users
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudieron cargar los usuarios.'
  } finally {
    loading.value = false
  }
}

onMounted(loadUsers)

let searchTimeout = null
function onSearchInput() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(loadUsers, 350)
}

function openCreateUser() {
  modalMode.value = 'create-user'
  modalError.value = ''
  form.value = { id: null, name: '', email: '', username: '', phone: '', plan: 'Demo', role: 'user', devices: { watch: false, alexa: false } }
  showModal.value = true
}

const ADMIN_SHARED_EMAIL = 'alertsync26@gmail.com'

function openCreateAdmin() {
  modalMode.value = 'create-admin'
  modalError.value = ''
  form.value = { id: null, name: '', email: ADMIN_SHARED_EMAIL, username: '', phone: '', plan: 'Demo', role: 'admin', devices: { watch: false, alexa: false } }
  showModal.value = true
}

function openEdit(user) {
  modalMode.value = 'edit'
  modalError.value = ''
  form.value = {
    id: user.id,
    name: user.name,
    email: user.email,
    username: user.username || '',
    phone: user.phone || '',
    plan: user.plan,
    role: user.role,
    devices: { watch: !!user.devices?.watch, alexa: !!user.devices?.alexa },
  }
  showModal.value = true
}

function closeModal() {
  showModal.value = false
}

async function submitModal() {
  saving.value = true
  modalError.value = ''
  try {
    if (modalMode.value === 'create-user') {
      await api.post('/admin/users', {
        name: form.value.name,
        email: form.value.email,
        phone: form.value.phone || null,
        plan: form.value.plan,
        role: 'user',
      })
      toast.value = 'Usuario creado correctamente.'
    } else if (modalMode.value === 'create-admin') {
      await api.post('/admin/users', {
        name: form.value.name,
        email: form.value.email,
        username: form.value.username,
        phone: form.value.phone || null,
        role: 'admin',
      })
      toast.value = 'Administrador creado correctamente.'
    } else {
      await api.put(`/admin/users/${form.value.id}`, {
        name: form.value.name,
        email: form.value.email,
        username: form.value.role === 'admin' ? (form.value.username || null) : undefined,
        phone: form.value.phone || null,
        plan: form.value.plan,
        role: form.value.role,
        devices: form.value.devices,
      })
      toast.value = 'Usuario actualizado correctamente.'
    }
    showModal.value = false
    await loadUsers()
    setTimeout(() => { toast.value = '' }, 3000)
  } catch (e) {
    modalError.value = e.response?.data?.message
      || Object.values(e.response?.data?.errors || {}).flat().join(' ')
      || 'Ocurrió un error al guardar.'
  } finally {
    saving.value = false
  }
}

async function revertPlan(user) {
  if (!confirm(`¿Revertir el plan de ${user.email} a Demo?`)) return
  try {
    await api.post(`/admin/users/${user.id}/revert-plan`)
    toast.value = `Plan de ${user.email} revertido a Demo.`
    await loadUsers()
    setTimeout(() => { toast.value = '' }, 3000)
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudo revertir el plan.'
  }
}

async function deleteUser(user) {
  if (!confirm(`¿Eliminar a ${user.email}? Esto borrará también sus contactos, alertas y pagos.`)) return
  try {
    await api.delete(`/admin/users/${user.id}`)
    toast.value = 'Usuario eliminado.'
    await loadUsers()
    setTimeout(() => { toast.value = '' }, 3000)
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudo eliminar al usuario.'
  }
}

function openPasswordModal(user) {
  passwordTarget.value = user
  newPassword.value = ''
  passwordError.value = ''
  showPasswordModal.value = true
}

async function submitPasswordReset() {
  passwordError.value = ''
  try {
    await api.post(`/admin/users/${passwordTarget.value.id}/reset-password`, { password: newPassword.value })
    toast.value = `Contraseña de ${passwordTarget.value.email} restablecida.`
    showPasswordModal.value = false
    setTimeout(() => { toast.value = '' }, 3000)
  } catch (e) {
    passwordError.value = e.response?.data?.message
      || Object.values(e.response?.data?.errors || {}).flat().join(' ')
      || 'No se pudo restablecer la contraseña.'
  }
}

function isSelf(user) {
  return user.id === auth.user?.id
}

async function openDetail(user) {
  showDetailModal.value = true
  detailLoading.value = true
  detailError.value = ''
  detailUser.value = user
  detailContacts.value = []
  detailAlerts.value = []
  detailPayments.value = []
  try {
    const { data } = await api.get(`/admin/users/${user.id}`)
    detailUser.value = data.user
    detailContacts.value = data.contacts || []
    detailAlerts.value = data.alerts || []
    detailPayments.value = data.payments || []
  } catch (e) {
    detailError.value = e.response?.data?.message || 'No se pudo cargar el detalle del usuario.'
  } finally {
    detailLoading.value = false
  }
}

async function deleteDetailContact(contact) {
  if (!confirm(`¿Eliminar el contacto "${contact.name}" de ${detailUser.value.email}?`)) return
  try {
    await api.delete(`/admin/users/${detailUser.value.id}/contacts/${contact.id}`)
    detailContacts.value = detailContacts.value.filter((c) => c.id !== contact.id)
    toast.value = 'Contacto eliminado.'
    setTimeout(() => { toast.value = '' }, 3000)
  } catch (e) {
    detailError.value = e.response?.data?.message || 'No se pudo eliminar el contacto.'
  }
}
</script>

<template>
  <div>
    <div class="topbar">
      <div class="topbar-title">
        <h1>Usuarios</h1>
        <p>Gestiona cuentas, planes y roles</p>
      </div>
      <div class="flex gap-3">
        <button class="btn btn-secondary btn-sm" @click="openCreateAdmin">🛠️ Nuevo administrador</button>
        <button class="btn btn-primary btn-sm" @click="openCreateUser">+ Nuevo usuario</button>
      </div>
    </div>

    <div class="page-content">
      <div v-if="toast" class="toast-banner success">{{ toast }}</div>
      <div v-if="error" class="toast-banner">{{ error }}</div>

      <div class="filters">
        <input v-model="search" @input="onSearchInput" class="form-input" placeholder="Buscar por nombre o correo..." />
        <select v-model="planFilter" @change="loadUsers" class="form-input plan-select">
          <option value="">Todos los planes</option>
          <option value="Demo">Demo</option>
          <option value="Básico">Básico</option>
          <option value="Premium">Premium</option>
        </select>
      </div>

      <div v-if="loading" class="text-secondary">Cargando usuarios...</div>
      <div v-else class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Correo</th>
              <th>Plan</th>
              <th>Rol</th>
              <th>Estado</th>
              <th>Contactos</th>
              <th>Alertas</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users" :key="user.id">
              <td>{{ user.name }}</td>
              <td>
                {{ user.email }}
                <div v-if="user.username" class="text-muted text-sm">usuario: {{ user.username }}</div>
              </td>
              <td><span class="badge" :class="user.plan === 'Premium' ? 'badge-lime' : (user.plan === 'Básico' ? 'badge-cyan' : 'badge-gray')">{{ user.plan }}</span></td>
              <td><span class="badge" :class="user.role === 'admin' ? 'badge-orange' : 'badge-gray'">{{ user.role }}</span></td>
              <td><span class="badge" :class="user.status === 'pending' ? 'badge-orange' : 'badge-green'">{{ user.status === 'pending' ? 'Pendiente' : 'Activo' }}</span></td>
              <td>{{ user.contacts_count }}</td>
              <td>{{ user.alerts_count }}</td>
              <td class="actions-cell">
                <button class="link-btn" @click="openDetail(user)">Detalle</button>
                <button class="link-btn" @click="openEdit(user)">Editar</button>
                <button class="link-btn" @click="openPasswordModal(user)">Contraseña</button>
                <button v-if="user.plan !== 'Demo'" class="link-btn" @click="revertPlan(user)">Revertir a Demo</button>
                <button class="link-btn danger" :disabled="isSelf(user)" @click="deleteUser(user)">Eliminar</button>
              </td>
            </tr>
            <tr v-if="!users.length">
              <td colspan="8" class="text-center text-muted" style="padding: var(--space-8)">Sin usuarios encontrados.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
      <div class="modal-box">
        <div class="modal-header">
          <h2>
            {{ modalMode === 'create-user' ? 'Nuevo usuario' : (modalMode === 'create-admin' ? 'Nuevo administrador' : 'Editar usuario') }}
          </h2>
          <button class="close-btn" @click="closeModal">×</button>
        </div>
        <div v-if="modalError" class="toast-banner">{{ modalError }}</div>
        <form class="modal-form" @submit.prevent="submitModal">
          <div class="form-group">
            <label class="form-label">Nombre</label>
            <input v-model="form.name" class="form-input" required />
          </div>
          <div class="form-group">
            <label class="form-label">Correo</label>
            <input v-model="form.email" type="email" class="form-input" required :readonly="modalMode === 'create-admin'" />
            <p v-if="modalMode === 'create-admin' || (modalMode === 'edit' && form.role === 'admin')" class="hint-text">
              Este correo puede repetirse entre varios administradores (ej. un correo de soporte compartido). Lo que identifica a cada administrador al iniciar sesión es su <strong>usuario</strong>, no el correo.
            </p>
          </div>
          <div v-if="modalMode === 'create-admin' || (modalMode === 'edit' && form.role === 'admin')" class="form-group">
            <label class="form-label">Usuario (para iniciar sesión)</label>
            <input v-model="form.username" class="form-input" placeholder="ej. admin_juan" required pattern="[A-Za-z0-9_-]+" />
          </div>
          <div v-if="modalMode !== 'create-admin'" class="form-group">
            <label class="form-label">Teléfono</label>
            <input v-model="form.phone" class="form-input" />
          </div>
          <p v-if="modalMode === 'create-user' || modalMode === 'create-admin'" class="hint-text">
            📧 No se define contraseña aquí. Se enviará un correo a
            {{ modalMode === 'create-admin' ? 'este administrador' : 'este usuario' }}
            con sus datos; él mismo la creará al activar su cuenta desde el inicio de sesión.
          </p>
          <div v-if="modalMode === 'create-user'" class="form-group">
            <label class="form-label">Plan</label>
            <select v-model="form.plan" class="form-input">
              <option value="Demo">Demo</option>
              <option value="Básico">Básico</option>
              <option value="Premium">Premium</option>
            </select>
          </div>
          <div v-if="modalMode === 'edit'" class="form-row">
            <div class="form-group flex-1">
              <label class="form-label">Plan</label>
              <select v-model="form.plan" class="form-input">
                <option value="Demo">Demo</option>
                <option value="Básico">Básico</option>
                <option value="Premium">Premium</option>
              </select>
            </div>
            <div class="form-group flex-1">
              <label class="form-label">Rol</label>
              <select v-model="form.role" class="form-input">
                <option value="user">Usuario</option>
                <option value="admin">Administrador</option>
              </select>
            </div>
          </div>
          <div v-if="modalMode === 'edit' && form.plan !== 'Demo'" class="form-group">
            <label class="form-label">Dispositivos</label>
            <div class="device-checks">
              <label class="check-row">
                <input type="checkbox" v-model="form.devices.watch" /> ⌚ Smartwatch vinculado
              </label>
              <label class="check-row">
                <input type="checkbox" v-model="form.devices.alexa" /> 🔊 Alexa vinculada
              </label>
            </div>
          </div>
          <button type="submit" class="btn btn-primary w-full" :disabled="saving">
            {{ saving ? 'Guardando...' : 'Guardar' }}
          </button>
        </form>
      </div>
    </div>

    <!-- Password Reset Modal -->
    <div v-if="showPasswordModal" class="modal-backdrop" @click.self="showPasswordModal = false">
      <div class="modal-box">
        <div class="modal-header">
          <h2>Restablecer contraseña</h2>
          <button class="close-btn" @click="showPasswordModal = false">×</button>
        </div>
        <p class="text-secondary" style="margin-bottom: var(--space-4)">Para {{ passwordTarget?.email }}</p>
        <div v-if="passwordError" class="toast-banner">{{ passwordError }}</div>
        <form class="modal-form" @submit.prevent="submitPasswordReset">
          <div class="form-group">
            <label class="form-label">Nueva contraseña</label>
            <input v-model="newPassword" type="password" class="form-input" required minlength="8" />
          </div>
          <button type="submit" class="btn btn-primary w-full">Actualizar contraseña</button>
        </form>
      </div>
    </div>

    <!-- Detail Modal -->
    <div v-if="showDetailModal" class="modal-backdrop" @click.self="showDetailModal = false">
      <div class="modal-box detail-box">
        <div class="modal-header">
          <h2>{{ detailUser?.name }}</h2>
          <button class="close-btn" @click="showDetailModal = false">×</button>
        </div>
        <p class="text-secondary" style="margin-bottom: var(--space-5)">{{ detailUser?.email }}</p>

        <div v-if="detailLoading" class="text-secondary">Cargando detalle...</div>
        <div v-else>
          <div v-if="detailError" class="toast-banner">{{ detailError }}</div>

          <div class="detail-section">
            <h4>Dispositivos</h4>
            <p class="text-secondary">
              ⌚ Smartwatch: {{ detailUser?.devices?.watch ? `Vinculado (${detailUser?.device_names?.watch || 's/n'})` : 'No vinculado' }}<br>
              🔊 Alexa: {{ detailUser?.devices?.alexa ? `Vinculada (${detailUser?.device_names?.alexa || 's/n'})` : 'No vinculada' }}
            </p>
          </div>

          <div class="detail-section">
            <h4>Contactos de emergencia ({{ detailContacts.length }})</h4>
            <div v-if="!detailContacts.length" class="text-muted" style="font-size:0.85rem">Sin contactos registrados.</div>
            <ul v-else class="detail-list">
              <li v-for="c in detailContacts" :key="c.id">
                <div>
                  <strong>{{ c.name }}</strong> — {{ c.phone }}
                  <span class="badge" :class="c.verified ? 'badge-green' : 'badge-orange'" style="margin-left:6px">
                    {{ c.verified ? 'Verificado' : 'Sin verificar' }}
                  </span>
                </div>
                <button class="link-btn danger" @click="deleteDetailContact(c)">Eliminar</button>
              </li>
            </ul>
          </div>

          <div class="detail-section">
            <h4>Últimas alertas ({{ detailAlerts.length }})</h4>
            <div v-if="!detailAlerts.length" class="text-muted" style="font-size:0.85rem">Sin alertas registradas.</div>
            <ul v-else class="detail-list">
              <li v-for="a in detailAlerts" :key="a.id">
                <span>{{ a.source }} — {{ a.status }}</span>
              </li>
            </ul>
          </div>

          <div class="detail-section">
            <h4>Pagos ({{ detailPayments.length }})</h4>
            <div v-if="!detailPayments.length" class="text-muted" style="font-size:0.85rem">Sin pagos registrados.</div>
            <ul v-else class="detail-list">
              <li v-for="p in detailPayments" :key="p.id">
                <span>{{ p.plan }} — ${{ p.amount }} {{ p.currency }} ({{ p.status }})</span>
              </li>
            </ul>
          </div>
        </div>
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
.filters .form-input { max-width: 320px; }
.plan-select { max-width: 200px; }

.table-wrapper {
  background: var(--glass-bg); border: 1px solid var(--glass-border);
  border-radius: var(--radius-xl); overflow-x: auto;
}
.data-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
.data-table th, .data-table td {
  padding: var(--space-4); text-align: left; border-bottom: 1px solid var(--glass-border);
  white-space: nowrap;
}
.data-table th { color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; }
.actions-cell { display: flex; gap: var(--space-3); }
.link-btn { color: var(--brand-teal-light); font-weight: 600; font-size: 0.85rem; background: none; border: none; cursor: pointer; }
.link-btn:hover { color: var(--brand-lime); }
.link-btn.danger { color: var(--red-500); }
.link-btn:disabled { opacity: 0.4; cursor: not-allowed; }

.modal-backdrop {
  position: fixed; inset: 0; background: rgba(10,15,30,0.7); backdrop-filter: blur(8px);
  display: flex; align-items: center; justify-content: center; z-index: 100; padding: var(--space-6);
}
.modal-box {
  background: var(--bg-surface); border: 1px solid var(--glass-border); border-radius: var(--radius-xl);
  padding: var(--space-8); max-width: 480px; width: 100%; max-height: 90vh; overflow-y: auto;
  box-shadow: var(--shadow-lg);
}
.modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-5); }
.modal-header h2 { font-size: 1.3rem; font-weight: 800; }
.close-btn { background: none; border: none; color: var(--text-secondary); font-size: 1.8rem; cursor: pointer; }
.close-btn:hover { color: #fff; }
.modal-form { display: flex; flex-direction: column; gap: var(--space-4); }
.hint-text {
  font-size: 0.85rem; color: var(--text-secondary); background: rgba(159,211,45,0.08);
  border: 1px solid rgba(159,211,45,0.2); border-radius: var(--radius-md); padding: var(--space-3) var(--space-4);
  line-height: 1.5;
}
.form-row { display: flex; gap: var(--space-4); }
.flex-1 { flex: 1; }
.form-input[readonly] { opacity: 0.6; cursor: not-allowed; }
.device-checks { display: flex; flex-direction: column; gap: var(--space-2); }
.check-row { display: flex; align-items: center; gap: var(--space-2); font-size: 0.9rem; color: var(--text-secondary); cursor: pointer; }

.detail-box { max-width: 560px; }
.detail-section { margin-bottom: var(--space-6); }
.detail-section h4 { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); margin-bottom: var(--space-3); }
.detail-list { list-style: none; display: flex; flex-direction: column; gap: var(--space-2); }
.detail-list li {
  display: flex; align-items: center; justify-content: space-between; gap: var(--space-3);
  padding: var(--space-3); background: var(--glass-bg); border: 1px solid var(--glass-border);
  border-radius: var(--radius-md); font-size: 0.85rem;
}
</style>
