<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'

const payments = ref([])
const totalAmount = ref(0)
const loading = ref(true)
const error = ref('')
const toast = ref('')
const statusFilter = ref('')
const planFilter = ref('')

async function loadPayments() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get('/admin/payments', {
      params: { status: statusFilter.value || undefined, plan: planFilter.value || undefined },
    })
    payments.value = data.payments
    totalAmount.value = data.total_amount
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudieron cargar los pagos.'
  } finally {
    loading.value = false
  }
}

onMounted(loadPayments)

async function refund(payment) {
  if (!confirm(`¿Marcar el pago de ${payment.user_email} (${payment.plan}) como reembolsado?`)) return
  try {
    await api.post(`/admin/payments/${payment.id}/refund`)
    toast.value = 'Pago marcado como reembolsado.'
    await loadPayments()
    setTimeout(() => { toast.value = '' }, 3000)
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudo reembolsar el pago.'
  }
}

async function removePayment(payment) {
  if (!confirm('¿Eliminar este registro de pago permanentemente?')) return
  try {
    await api.delete(`/admin/payments/${payment.id}`)
    toast.value = 'Registro de pago eliminado.'
    await loadPayments()
    setTimeout(() => { toast.value = '' }, 3000)
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudo eliminar el registro.'
  }
}

function formatDate(value) {
  if (!value) return '—'
  return new Date(value).toLocaleString('es-MX', { dateStyle: 'medium', timeStyle: 'short' })
}

function formatMoney(value) {
  return `$${Number(value || 0).toLocaleString('es-MX', { minimumFractionDigits: 2 })}`
}
</script>

<template>
  <div>
    <div class="topbar">
      <div class="topbar-title">
        <h1>Pagos</h1>
        <p>Historial de transacciones y suscripciones</p>
      </div>
      <div class="total-badge">Total cobrado: <strong>{{ formatMoney(totalAmount) }}</strong></div>
    </div>

    <div class="page-content">
      <div v-if="toast" class="toast-banner success">{{ toast }}</div>
      <div v-if="error" class="toast-banner">{{ error }}</div>

      <div class="filters">
        <select v-model="statusFilter" @change="loadPayments" class="form-input">
          <option value="">Todos los estados</option>
          <option value="completed">Completado</option>
          <option value="refunded">Reembolsado</option>
        </select>
        <select v-model="planFilter" @change="loadPayments" class="form-input">
          <option value="">Todos los planes</option>
          <option value="Básico">Básico</option>
          <option value="Premium">Premium</option>
        </select>
      </div>

      <div v-if="loading" class="text-secondary">Cargando pagos...</div>
      <div v-else class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th>Usuario</th>
              <th>Plan</th>
              <th>Monto</th>
              <th>Tarjeta</th>
              <th>Estado</th>
              <th>Fecha</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="payment in payments" :key="payment.id">
              <td>
                <div>{{ payment.user_name }}</div>
                <div class="text-muted text-sm">{{ payment.user_email }}</div>
              </td>
              <td>{{ payment.plan }}</td>
              <td>{{ formatMoney(payment.amount) }} {{ payment.currency }}</td>
              <td>•••• {{ payment.card_last_four }}</td>
              <td>
                <span class="badge" :class="payment.status === 'refunded' ? 'badge-orange' : 'badge-lime'">
                  {{ payment.status === 'refunded' ? 'Reembolsado' : 'Completado' }}
                </span>
              </td>
              <td>{{ formatDate(payment.created_at) }}</td>
              <td class="actions-cell">
                <button v-if="payment.status !== 'refunded'" class="link-btn" @click="refund(payment)">Reembolsar</button>
                <button class="link-btn danger" @click="removePayment(payment)">Eliminar</button>
              </td>
            </tr>
            <tr v-if="!payments.length">
              <td colspan="7" class="text-center text-muted" style="padding: var(--space-8)">Sin pagos registrados.</td>
            </tr>
          </tbody>
        </table>
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
.total-badge { font-size: 0.85rem; color: var(--text-secondary); }
.total-badge strong { color: var(--brand-lime); font-size: 1.1rem; }
.page-content { padding: var(--space-8); }
.toast-banner {
  margin-bottom: var(--space-4); padding: var(--space-4);
  background: rgba(255,59,59,0.1); border: 1px solid rgba(255,59,59,0.2);
  border-radius: var(--radius-md);
}
.toast-banner.success { background: rgba(159,211,45,0.12); border-color: rgba(159,211,45,0.3); }
.filters { display: flex; gap: var(--space-3); margin-bottom: var(--space-5); }
.filters .form-input { max-width: 220px; }
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
</style>
