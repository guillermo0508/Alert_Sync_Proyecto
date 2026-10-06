<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

const name = ref('')
const email = ref('')
const phone = ref('')

const cardName = ref('')
const cardNumber = ref('')
const cardExpiry = ref('')
const cardCvv = ref('')
const autoRenew = ref(true)

const profileLoading = ref(false)
const paymentLoading = ref(false)
const autoRenewLoading = ref(false)
const profileError = ref('')
const paymentError = ref('')
const profileToast = ref('')
const paymentToast = ref('')

const hasPaidPlan = computed(() => {
  const plan = auth.user?.plan
  return plan && plan !== 'Demo'
})

const savedCardLabel = computed(() => {
  const pm = auth.user?.payment_method
  if (!pm?.has_card) return null
  return `•••• •••• •••• ${pm.card_last_four} · ${pm.card_expiry}`
})

onMounted(async () => {
  try {
    await auth.fetchMe()
  } catch {
    // use cached user
  }
  syncFromUser()
})

function syncFromUser() {
  name.value = auth.user?.name || ''
  email.value = auth.user?.email || ''
  phone.value = auth.user?.phone || ''
  cardName.value = auth.user?.payment_method?.card_name || auth.user?.name || ''
  autoRenew.value = auth.user?.payment_method?.auto_renew ?? false
  cardNumber.value = ''
  cardExpiry.value = auth.user?.payment_method?.card_expiry || ''
  cardCvv.value = ''
}

function handleCardNumberInput(e) {
  let value = e.target.value.replace(/\D/g, '')
  if (value.length > 16) value = value.slice(0, 16)
  const parts = value.match(/.{1,4}/g) || []
  cardNumber.value = parts.join(' ')
}

function handleCardExpiryInput(e) {
  let value = e.target.value.replace(/\D/g, '')
  if (value.length > 4) value = value.slice(0, 4)
  if (value.length > 2) {
    cardExpiry.value = value.slice(0, 2) + '/' + value.slice(2)
  } else {
    cardExpiry.value = value
  }
}

async function saveProfile() {
  profileError.value = ''
  profileToast.value = ''
  profileLoading.value = true
  try {
    const { data } = await api.put('/auth/profile', {
      name: name.value.trim(),
      email: email.value.trim(),
      phone: phone.value.trim() || null,
    })
    auth.applyUserUpdate(data.user)
    profileToast.value = data.message
  } catch (e) {
    profileError.value = e.response?.data?.message
      || e.response?.data?.errors?.email?.[0]
      || 'No se pudieron guardar tus datos.'
  } finally {
    profileLoading.value = false
  }
}

async function savePaymentMethod() {
  paymentError.value = ''
  paymentToast.value = ''
  paymentLoading.value = true
  try {
    const { data } = await api.put('/auth/payment-method', {
      card_number: cardNumber.value.replace(/\s+/g, ''),
      card_expiry: cardExpiry.value,
      card_cvv: cardCvv.value,
      card_name: cardName.value.trim(),
      auto_renew: autoRenew.value,
    })
    auth.applyUserUpdate(data.user)
    cardNumber.value = ''
    cardCvv.value = ''
    paymentToast.value = data.message
  } catch (e) {
    paymentError.value = e.response?.data?.message
      || Object.values(e.response?.data?.errors || {}).flat()[0]
      || 'No se pudo actualizar la forma de pago.'
  } finally {
    paymentLoading.value = false
  }
}

async function onAutoRenewChange() {
  if (!auth.user?.payment_method?.has_card) return
  autoRenewLoading.value = true
  try {
    const { data } = await api.put('/auth/auto-renew', { auto_renew: autoRenew.value })
    auth.applyUserUpdate(data.user)
    paymentToast.value = data.message
  } catch (e) {
    autoRenew.value = !autoRenew.value
    paymentError.value = e.response?.data?.message || 'No se pudo actualizar el cobro automático.'
  } finally {
    autoRenewLoading.value = false
  }
}
</script>

<template>
  <div>
    <div class="topbar">
      <div class="topbar-title">
        <h1>Mi perfil</h1>
        <p>Consulta y edita tus datos personales y forma de pago</p>
      </div>
    </div>

    <div class="page-content">
      <section class="profile-card">
        <h2>Datos personales</h2>
        <p class="section-desc">Actualiza tu nombre, correo o teléfono si algo cambió.</p>

        <div v-if="profileToast" class="toast success">{{ profileToast }}</div>
        <div v-if="profileError" class="toast error">{{ profileError }}</div>

        <form class="profile-form" @submit.prevent="saveProfile">
          <div class="form-group">
            <label class="form-label">Nombre completo</label>
            <input v-model="name" type="text" class="form-input" required maxlength="120" />
          </div>
          <div class="form-group">
            <label class="form-label">Correo electrónico</label>
            <input v-model="email" type="email" class="form-input" required maxlength="255" />
          </div>
          <div class="form-group">
            <label class="form-label">Teléfono</label>
            <input v-model="phone" type="tel" class="form-input" maxlength="20" placeholder="+52 55 1234 5678" />
          </div>
          <button type="submit" class="btn btn-primary" :disabled="profileLoading">
            {{ profileLoading ? 'Guardando…' : 'Guardar datos personales' }}
          </button>
        </form>
      </section>

      <section class="profile-card">
        <h2>Forma de pago</h2>
        <p class="section-desc">
          <template v-if="hasPaidPlan">
            Esta tarjeta se usará para el cobro automático mensual de tu plan {{ auth.user?.plan }}.
          </template>
          <template v-else>
            Guarda tu tarjeta para que, al contratar un plan, el cobro se renueve automáticamente cada mes.
          </template>
        </p>

        <div v-if="savedCardLabel" class="saved-card">
          <span class="saved-card-label">Tarjeta registrada</span>
          <span>{{ savedCardLabel }}</span>
        </div>

        <div v-if="paymentToast" class="toast success">{{ paymentToast }}</div>
        <div v-if="paymentError" class="toast error">{{ paymentError }}</div>

        <form class="profile-form" @submit.prevent="savePaymentMethod">
          <div class="form-group">
            <label class="form-label">Titular de la tarjeta</label>
            <input v-model="cardName" type="text" class="form-input" required placeholder="Nombre como aparece en la tarjeta" />
          </div>
          <div class="form-group">
            <label class="form-label">Número de tarjeta</label>
            <input
              type="text"
              :value="cardNumber"
              required
              class="form-input"
              placeholder="4111 2222 3333 4444"
              maxlength="19"
              @input="handleCardNumberInput"
            />
            <p v-if="savedCardLabel" class="hint-text">Ingresa el número completo para reemplazar la tarjeta guardada.</p>
          </div>
          <div class="form-row">
            <div class="form-group flex-1">
              <label class="form-label">Expiración (MM/YY)</label>
              <input
                type="text"
                :value="cardExpiry"
                required
                class="form-input"
                placeholder="MM/YY"
                maxlength="5"
                @input="handleCardExpiryInput"
              />
            </div>
            <div class="form-group flex-1">
              <label class="form-label">CVV</label>
              <input v-model="cardCvv" type="password" required class="form-input" placeholder="•••" maxlength="4" />
            </div>
          </div>

          <label class="checkbox-row">
            <input
              v-model="autoRenew"
              type="checkbox"
              :disabled="autoRenewLoading"
              @change="auth.user?.payment_method?.has_card ? onAutoRenewChange() : null"
            />
            <span>Activar cobro automático al renovar mi suscripción</span>
          </label>

          <button type="submit" class="btn btn-primary" :disabled="paymentLoading">
            {{ paymentLoading ? 'Guardando…' : 'Guardar forma de pago' }}
          </button>
        </form>
      </section>
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
.page-content {
  padding: var(--space-8);
  max-width: 640px;
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
}
.profile-card {
  background: var(--glass-bg); border: 1px solid var(--glass-border);
  border-radius: var(--radius-xl); padding: var(--space-8);
}
.profile-card h2 { font-size: 1.15rem; font-weight: 700; margin-bottom: var(--space-2); }
.section-desc { color: var(--text-secondary); font-size: 0.9rem; margin-bottom: var(--space-5); line-height: 1.6; }
.profile-form { display: flex; flex-direction: column; gap: var(--space-4); }
.form-row { display: flex; gap: var(--space-4); flex-wrap: wrap; }
.flex-1 { flex: 1; min-width: 140px; }
.toast {
  padding: var(--space-3) var(--space-4);
  border-radius: var(--radius-md);
  margin-bottom: var(--space-4);
  font-size: 0.9rem;
}
.toast.success {
  background: rgba(159,211,45,0.12);
  border: 1px solid rgba(159,211,45,0.35);
  color: var(--brand-lime);
}
.toast.error {
  background: rgba(239,68,68,0.1);
  border: 1px solid rgba(239,68,68,0.25);
  color: #fca5a5;
}
.saved-card {
  display: flex; flex-direction: column; gap: var(--space-1);
  padding: var(--space-4); margin-bottom: var(--space-4);
  background: rgba(29,93,110,0.15); border: 1px solid rgba(29,93,110,0.35);
  border-radius: var(--radius-md); font-weight: 600;
}
.saved-card-label { font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
.checkbox-row {
  display: flex; align-items: flex-start; gap: var(--space-3);
  color: var(--text-secondary); font-size: 0.9rem; cursor: pointer;
}
.hint-text { margin-top: var(--space-2); font-size: 0.8rem; color: var(--text-muted); }
</style>
