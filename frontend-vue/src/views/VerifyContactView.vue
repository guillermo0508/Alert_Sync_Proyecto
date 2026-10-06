<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import LogoBrand from '@/components/LogoBrand.vue'
import api from '@/services/api'

const route = useRoute()
const loading = ref(true)
const success = ref(false)
const message = ref('')
const contactName = ref('')

onMounted(async () => {
  const token = route.query.token
  const id = route.params.id

  if (!token || !id) {
    message.value = 'Enlace de confirmación inválido o incompleto.'
    loading.value = false
    return
  }

  try {
    const { data } = await api.post(`/contacts/${id}/verify`, { token })
    success.value = true
    message.value = data.message
    contactName.value = data.contact_name || ''
  } catch (e) {
    message.value = e.response?.data?.message || 'No se pudo confirmar el contacto.'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="verify-layout">
    <RouterLink to="/" class="verify-brand">
      <LogoBrand size="lg" />
    </RouterLink>

    <div class="verify-card">
      <div v-if="loading" class="verify-icon">⏳</div>
      <div v-else-if="success" class="verify-icon success">✅</div>
      <div v-else class="verify-icon error">⚠️</div>

      <h1 v-if="loading">Confirmando...</h1>
      <h1 v-else-if="success">¡Confirmado{{ contactName ? `, gracias` : '' }}!</h1>
      <h1 v-else>No se pudo confirmar</h1>

      <p>{{ loading ? 'Un momento por favor.' : message }}</p>

      <RouterLink to="/" class="btn btn-primary mt-6">Ir a ALERTSYNC</RouterLink>
    </div>
  </div>
</template>

<style scoped>
.verify-layout {
  min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center;
  padding: var(--space-8); gap: var(--space-10);
}
.verify-brand { display: flex; }
.verify-card {
  max-width: 480px; width: 100%; text-align: center;
  background: var(--glass-bg); border: 1px solid var(--glass-border);
  border-radius: var(--radius-xl); padding: var(--space-10) var(--space-8);
  display: flex; flex-direction: column; align-items: center;
}
.verify-icon { font-size: 3rem; margin-bottom: var(--space-5); }
.verify-card h1 { font-size: 1.5rem; font-weight: 800; margin-bottom: var(--space-3); }
.verify-card p { color: var(--text-secondary); line-height: 1.7; margin-bottom: var(--space-2); }
</style>
