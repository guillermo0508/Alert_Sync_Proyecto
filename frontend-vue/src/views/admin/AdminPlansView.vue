<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'

const loading = ref(true)
const error = ref('')
const toast = ref('')
const savingId = ref(null)

const plans = ref([])

async function load() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get('/plans')
    plans.value = data.plans
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudieron cargar los planes.'
  } finally {
    loading.value = false
  }
}

onMounted(load)

function addFeature(plan) {
  plan.features.push('')
}

function removeFeature(plan, idx) {
  plan.features.splice(idx, 1)
}

async function savePlan(plan) {
  savingId.value = plan.id
  error.value = ''
  try {
    const priceAmount = Number(String(plan.price).replace(/[^0-9]/g, ''))
    const { data } = await api.put(`/admin/plans/${plan.id}`, {
      name: plan.name,
      price_amount: priceAmount,
      features: plan.features.filter((f) => f.trim() !== ''),
      max_contacts: Number(plan.max_contacts),
    })
    plans.value = data.plans
    toast.value = `Plan ${plan.name} actualizado. Ya se refleja en la web pública y en Suscripción.`
    setTimeout(() => { toast.value = '' }, 4000)
  } catch (e) {
    error.value = e.response?.data?.message
      || Object.values(e.response?.data?.errors || {}).flat().join(' ')
      || 'No se pudo guardar el plan.'
  } finally {
    savingId.value = null
  }
}
</script>

<template>
  <div>
    <div class="topbar">
      <div class="topbar-title">
        <h1>Planes</h1>
        <p>Edita precios, contactos máximos y características de Básico y Premium</p>
      </div>
    </div>

    <div class="page-content">
      <div v-if="toast" class="toast-banner success">{{ toast }}</div>
      <div v-if="error" class="toast-banner">{{ error }}</div>
      <div v-if="loading" class="text-secondary">Cargando planes...</div>

      <div v-else class="plans-grid">
        <div v-for="plan in plans" :key="plan.id" class="plan-card">
          <div class="plan-card-header">
            <h3>{{ plan.id === 'premium' ? '⭐' : '🛡️' }} {{ plan.name }}</h3>
          </div>

          <div class="form-group">
            <label class="form-label">Nombre del plan</label>
            <input v-model="plan.name" class="form-input" maxlength="50" />
          </div>

          <div class="form-row">
            <div class="form-group flex-1">
              <label class="form-label">Precio (MXN/mes)</label>
              <input v-model="plan.price" class="form-input" placeholder="$100/mes" />
            </div>
            <div class="form-group flex-1">
              <label class="form-label">Contactos máximos</label>
              <input v-model="plan.max_contacts" type="number" min="0" max="100" class="form-input" />
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Características</label>
            <div v-for="(feat, idx) in plan.features" :key="idx" class="feature-row">
              <input v-model="plan.features[idx]" class="form-input" maxlength="150" />
              <button type="button" class="link-btn danger" @click="removeFeature(plan, idx)">Eliminar</button>
            </div>
            <button type="button" class="btn btn-secondary btn-sm" @click="addFeature(plan)">+ Agregar característica</button>
          </div>

          <button
            type="button"
            class="btn btn-primary w-full"
            :disabled="savingId === plan.id"
            @click="savePlan(plan)"
          >
            {{ savingId === plan.id ? 'Guardando...' : `Guardar ${plan.name}` }}
          </button>
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

.plans-grid {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
  gap: var(--space-6); align-items: start;
}
.plan-card {
  background: var(--glass-bg); border: 1px solid var(--glass-border);
  border-radius: var(--radius-xl); padding: var(--space-6);
  display: flex; flex-direction: column; gap: var(--space-4);
}
.plan-card-header h3 { font-size: 1.15rem; font-weight: 800; }
.form-row { display: flex; gap: var(--space-4); }
.flex-1 { flex: 1; }
.feature-row { display: flex; gap: var(--space-3); align-items: center; margin-bottom: var(--space-2); }
.feature-row .form-input { flex: 1; }
.link-btn { color: var(--brand-teal-light); font-weight: 600; font-size: 0.85rem; background: none; border: none; cursor: pointer; white-space: nowrap; }
.link-btn.danger { color: var(--red-500); }
.w-full { width: 100%; }
</style>
