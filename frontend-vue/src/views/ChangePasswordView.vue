<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const currentPassword = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const loading = ref(false)
const error = ref('')

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await auth.changePassword(
      currentPassword.value,
      password.value,
      passwordConfirmation.value,
    )
    router.push({ name: 'login', query: { passwordChanged: '1' } })
  } catch (e) {
    error.value = e.response?.data?.message
      || e.response?.data?.errors?.current_password?.[0]
      || e.response?.data?.errors?.password?.[0]
      || Object.values(e.response?.data?.errors || {}).flat().join(' ')
      || 'No se pudo cambiar la contraseña.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div>
    <div class="topbar">
      <div class="topbar-title">
        <h1>Cambiar contraseña</h1>
        <p>Actualiza tu contraseña de acceso</p>
      </div>
    </div>

    <div class="page-content">
      <div class="password-card">
        <p class="text-secondary text-sm" style="margin-bottom: var(--space-5)">
          Al cambiar tu contraseña se cerrará tu sesión automáticamente. Deberás iniciar sesión de nuevo con la nueva contraseña.
        </p>

        <div v-if="error" class="toast-banner">{{ error }}</div>

        <form class="password-form" @submit.prevent="submit">
          <div class="form-group">
            <label class="form-label">Contraseña actual</label>
            <input
              v-model="currentPassword"
              type="password"
              class="form-input"
              required
              autocomplete="current-password"
            />
          </div>
          <div class="form-group">
            <label class="form-label">Nueva contraseña</label>
            <input
              v-model="password"
              type="password"
              class="form-input"
              required
              minlength="8"
              autocomplete="new-password"
            />
            <p class="hint-text">Mínimo 8 caracteres: una mayúscula, minúsculas, número y carácter especial.</p>
          </div>
          <div class="form-group">
            <label class="form-label">Confirmar nueva contraseña</label>
            <input
              v-model="passwordConfirmation"
              type="password"
              class="form-input"
              required
              minlength="8"
              autocomplete="new-password"
            />
          </div>
          <button type="submit" class="btn btn-primary w-full" :disabled="loading">
            {{ loading ? 'Guardando…' : 'Cambiar contraseña' }}
          </button>
        </form>
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
.page-content { padding: var(--space-8); max-width: 520px; }
.password-card {
  background: var(--glass-bg); border: 1px solid var(--glass-border);
  border-radius: var(--radius-xl); padding: var(--space-8);
}
.toast-banner {
  margin-bottom: var(--space-4); padding: var(--space-4);
  background: rgba(255,59,59,0.1); border: 1px solid rgba(255,59,59,0.2);
  border-radius: var(--radius-md); color: var(--text-primary);
}
.password-form { display: flex; flex-direction: column; gap: var(--space-4); }
.hint-text {
  margin-top: var(--space-2); font-size: 0.8rem; color: var(--text-muted);
}
</style>
