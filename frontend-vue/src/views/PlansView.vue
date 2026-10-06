<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const plans = ref([])
const loading = ref(false)
const toast = ref('')

// Checkout Modal State
const showCheckoutModal = ref(false)
const selectedPlan = ref(null)
const cardNumber = ref('')
const cardExpiry = ref('')
const cardCvv = ref('')
const cardName = ref('')
const checkoutError = ref('')

const handleCardNumberInput = (e) => {
  let value = e.target.value.replace(/\D/g, '')
  if (value.length > 16) value = value.slice(0, 16)
  const parts = value.match(/.{1,4}/g) || []
  cardNumber.value = parts.join(' ')
}

const handleCardExpiryInput = (e) => {
  let value = e.target.value.replace(/\D/g, '')
  if (value.length > 4) value = value.slice(0, 4)
  if (value.length > 2) {
    cardExpiry.value = value.slice(0, 2) + '/' + value.slice(2)
  } else {
    cardExpiry.value = value
  }
}

const currentPlan = computed(() => auth.user?.plan || 'Demo')
const expiresAt = computed(() => {
  if (!auth.user?.plan_expires_at) return null
  return new Date(auth.user.plan_expires_at).toLocaleDateString('es-MX', { year: 'numeric', month: 'long', day: 'numeric' })
})
const nextPlan = computed(() => auth.user?.next_plan)

onMounted(async () => {
  try {
    const { data } = await api.get('/plans')
    plans.value = data.plans
  } catch (e) {
    console.error('Error cargando planes', e)
  }
})

function openCheckout(plan) {
  selectedPlan.value = plan
  cardName.value = auth.user?.name || ''
  cardNumber.value = ''
  cardExpiry.value = ''
  cardCvv.value = ''
  checkoutError.value = ''
  showCheckoutModal.value = true
}

function closeCheckout() {
  showCheckoutModal.value = false
  selectedPlan.value = null
}

async function processPayment() {
  if (!selectedPlan.value) return
  
  loading.value = true
  checkoutError.value = ''
  
  try {
    const { data } = await api.post('/plans/change', {
      plan_id: selectedPlan.value.id,
      card_number: cardNumber.value.replace(/\s+/g, ''),
      card_expiry: cardExpiry.value,
      card_cvv: cardCvv.value,
      card_name: cardName.value
    })
    
    auth.user = data.user
    toast.value = data.message
    closeCheckout()
    
    // Redirect to dashboard immediately based on the selected plan
    setTimeout(() => {
      router.push('/dashboard')
    }, 1500)
    
  } catch (e) {
    checkoutError.value = e.response?.data?.message || 'Error al procesar el pago. Revisa los datos de tu tarjeta.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="plans-layout">
    <div class="topbar">
      <h1>Mi Suscripción</h1>
    </div>

    <div class="page-content">
      <div v-if="toast" class="toast-banner">{{ toast }}</div>

      <!-- Current Subscription Status -->
      <div class="status-card glass-panel">
        <div class="status-header">
          <h2>Plan Actual: <span class="highlight">{{ currentPlan }}</span></h2>
          <span v-if="currentPlan !== 'Demo'" class="status-badge">Activo</span>
        </div>
        
        <div v-if="currentPlan !== 'Demo'" class="status-details">
          <p><strong>Fecha de corte:</strong> {{ expiresAt }}</p>
          <div v-if="nextPlan" class="next-plan-alert">
            ℹ️ Tienes programado un cambio al plan <strong>{{ nextPlan }}</strong> para el {{ expiresAt }}.
          </div>
        </div>
        <div v-else class="status-details">
          <p>Actualmente estás en modo demostración. Contrata un plan para activar tu sistema de seguridad y poder vincular contactos y dispositivos.</p>
        </div>
      </div>

      <!-- Available Plans -->
      <h3 class="section-title">Planes Disponibles</h3>
      
      <div class="plans-grid">
        <div v-for="plan in plans" :key="plan.id" class="plan-card glass-panel" :class="{ 'is-current': plan.name === currentPlan, 'is-premium': plan.id === 'premium' }">
          <div v-if="plan.name === currentPlan" class="current-badge">Tu Plan Actual</div>
          <div v-if="plan.name === nextPlan" class="next-badge">Próximo Plan</div>
          
          <div class="plan-header">
            <h3>{{ plan.name }}</h3>
            <div class="plan-price">{{ plan.price }}</div>
          </div>
          
          <ul class="plan-features">
            <li v-for="(feat, idx) in plan.features" :key="idx">✓ {{ feat }}</li>
          </ul>
          
          <button 
            class="btn w-full mt-4" 
            :class="plan.id === 'premium' ? 'btn-primary' : 'btn-outline'"
            :disabled="loading || plan.name === currentPlan || plan.name === nextPlan" 
            @click="openCheckout(plan)"
          >
            {{ plan.name === currentPlan ? 'Plan Activo' : (plan.name === nextPlan ? 'Programado' : 'Contratar ' + plan.name) }}
          </button>
        </div>
      </div>
    </div>

    <!-- Checkout Modal -->
    <div v-if="showCheckoutModal" class="modal-backdrop">
      <div class="checkout-modal glass-panel">
        <div class="modal-header">
          <h2>💳 Checkout - {{ selectedPlan?.name }}</h2>
          <button class="close-btn" @click="closeCheckout">×</button>
        </div>
        
        <div v-if="checkoutError" class="error-banner">⚠️ {{ checkoutError }}</div>

        <form @submit.prevent="processPayment" class="checkout-form">
          <!-- Prefilled User Details -->
          <div class="prefilled-section">
            <h4>Datos del Cliente</h4>
            <div class="form-row">
              <div class="form-group flex-1">
                <label>Nombre</label>
                <input type="text" :value="auth.user?.name" readonly class="form-input read-only" />
              </div>
            </div>
            <div class="form-row">
              <div class="form-group flex-1">
                <label>Correo Electrónico</label>
                <input type="email" :value="auth.user?.email" readonly class="form-input read-only" />
              </div>
              <div class="form-group flex-1" v-if="auth.user?.phone">
                <label>Teléfono</label>
                <input type="text" :value="auth.user?.phone" readonly class="form-input read-only" />
              </div>
            </div>
          </div>

          <!-- Payment Card Details -->
          <div class="payment-section">
            <h4>Detalles del Pago</h4>
            <div class="form-group">
              <label>Titular de la Tarjeta</label>
              <input type="text" v-model="cardName" required class="form-input" placeholder="Nombre completo" />
            </div>
            
            <div class="form-group">
              <label>Número de Tarjeta</label>
              <input type="text" :value="cardNumber" @input="handleCardNumberInput" required class="form-input" placeholder="4111 2222 3333 4444" maxlength="19" />
            </div>

            <div class="form-row">
              <div class="form-group flex-1">
                <label>Expiración (MM/YY)</label>
                <input type="text" :value="cardExpiry" @input="handleCardExpiryInput" required class="form-input" placeholder="MM/YY" maxlength="5" />
              </div>
              <div class="form-group flex-1">
                <label>CVV</label>
                <input type="password" v-model="cardCvv" required class="form-input" placeholder="•••" maxlength="4" />
              </div>
            </div>
          </div>

          <div class="summary-section">
            <div class="summary-row">
              <span>Subtotal:</span>
              <span>{{ selectedPlan?.price }}</span>
            </div>
            <div class="summary-row total">
              <span>Total a pagar:</span>
              <span>{{ selectedPlan?.price }}</span>
            </div>
          </div>

          <button type="submit" class="btn btn-primary w-full mt-4" :disabled="loading">
            {{ loading ? 'Procesando pago...' : '🔒 Confirmar Pago Seguro' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
.plans-layout { min-height: 100vh; position: relative; }
.topbar {
  padding: 1.5rem 2rem;
  border-bottom: 1px solid var(--glass-border);
  background: var(--surface-panel);
  backdrop-filter: blur(10px);
}
.page-content { padding: 2rem; max-width: 1000px; margin: 0 auto; }
.toast-banner {
  background: rgba(159,211,45,0.2);
  border: 1px solid var(--brand-lime);
  color: #fff;
  padding: 1rem;
  border-radius: var(--radius-md);
  margin-bottom: 2rem;
  text-align: center;
}
.error-banner {
  background: rgba(255, 59, 59, 0.15);
  border: 1px solid #FF3B3B;
  color: #FF8888;
  padding: 1rem;
  border-radius: var(--radius-md);
  margin-bottom: 1.5rem;
}

.glass-panel {
  background: var(--surface-panel);
  border: 1px solid var(--glass-border);
  border-radius: var(--radius-xl);
  backdrop-filter: blur(20px);
}

.status-card {
  padding: 2rem;
  margin-bottom: 3rem;
  background: linear-gradient(135deg, rgba(29,93,110,0.2), var(--surface-panel));
}
.status-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}
.highlight { color: var(--brand-lime); font-weight: 800; }
.status-badge {
  background: var(--brand-lime);
  color: #000;
  padding: 0.25rem 1rem;
  border-radius: 50px;
  font-weight: 700;
  font-size: 0.85rem;
}
.next-plan-alert {
  margin-top: 1rem;
  padding: 1rem;
  background: rgba(29,93,110,0.3);
  border-left: 4px solid var(--brand-teal-light);
  border-radius: var(--radius-sm);
}

.section-title { font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem; }

.plans-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
}

.plan-card {
  padding: 2.5rem;
  display: flex;
  flex-direction: column;
  position: relative;
  overflow: hidden;
  transition: transform 0.3s;
}
.plan-card:hover { transform: translateY(-5px); }

.is-current { border-color: rgba(255,255,255,0.2); }
.is-premium { background: linear-gradient(135deg, rgba(29,93,110,0.3), rgba(159,211,45,0.05)); }

.current-badge, .next-badge {
  position: absolute;
  top: 1rem; right: -2rem;
  background: var(--text-secondary);
  color: #000;
  padding: 0.25rem 3rem;
  transform: rotate(45deg);
  font-size: 0.7rem;
  font-weight: 800;
  letter-spacing: 0.05em;
}
.current-badge { background: #fff; }
.next-badge { background: var(--brand-teal-light); }

.plan-header { text-align: center; margin-bottom: 2rem; }
.plan-header h3 { font-size: 1.5rem; font-weight: 700; color: var(--text-secondary); }
.is-premium .plan-header h3 { color: var(--brand-lime); }
.plan-price { font-size: 2.5rem; font-weight: 900; margin-top: 0.5rem; }

.plan-features {
  list-style: none;
  padding: 0;
  margin-bottom: 2rem;
  flex: 1;
}
.plan-features li {
  padding: 0.75rem 0;
  border-bottom: 1px solid rgba(255,255,255,0.05);
  color: var(--text-secondary);
}

.btn-outline {
  background: transparent;
  border: 1px solid var(--text-secondary);
  color: white;
}
.btn-outline:hover:not(:disabled) {
  background: rgba(255,255,255,0.1);
}

/* Modal Styling */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(10, 15, 30, 0.7);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 100;
  padding: 2rem;
}

.checkout-modal {
  max-width: 600px;
  width: 100%;
  padding: 2.5rem;
  box-shadow: 0 20px 50px rgba(0,0,0,0.5);
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  border-bottom: 1px solid rgba(255,255,255,0.1);
  padding-bottom: 1rem;
}
.modal-header h2 { font-size: 1.6rem; font-weight: 800; }
.close-btn {
  background: transparent;
  border: none;
  color: var(--text-secondary);
  font-size: 2rem;
  cursor: pointer;
}
.close-btn:hover { color: #fff; }

.checkout-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.prefilled-section, .payment-section {
  border-bottom: 1px solid rgba(255,255,255,0.05);
  padding-bottom: 1.5rem;
}

.prefilled-section h4, .payment-section h4 {
  font-size: 1rem;
  color: var(--brand-lime);
  margin-bottom: 1rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.form-row {
  display: flex;
  gap: 1rem;
}
.flex-1 { flex: 1; }

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 1rem;
}
.form-group label {
  font-size: 0.85rem;
  color: var(--text-secondary);
  font-weight: 600;
}
.form-input {
  background: rgba(0,0,0,0.3);
  border: 1px solid var(--glass-border);
  border-radius: var(--radius-md);
  padding: 0.8rem 1rem;
  color: #fff;
  font-size: 0.95rem;
  outline: none;
  transition: border-color 0.3s;
}
.form-input:focus {
  border-color: var(--brand-lime);
}
.form-input.read-only {
  background: rgba(255,255,255,0.05);
  color: var(--text-secondary);
  border-style: dashed;
  cursor: not-allowed;
}

.summary-section {
  background: rgba(0,0,0,0.2);
  padding: 1.25rem;
  border-radius: var(--radius-lg);
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.summary-row {
  display: flex;
  justify-content: space-between;
  color: var(--text-secondary);
  font-size: 0.95rem;
}
.summary-row.total {
  border-top: 1px solid rgba(255,255,255,0.1);
  padding-top: 0.75rem;
  font-size: 1.2rem;
  font-weight: 800;
  color: var(--brand-lime);
}
</style>
