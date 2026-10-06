<script setup>
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import LogoBrand from '@/components/LogoBrand.vue'
import ThemeToggle from '@/components/ThemeToggle.vue'
import { VueTelInput } from 'vue-tel-input'
import 'vue-tel-input/vue-tel-input.css'

const router = useRouter()
const auth = useAuthStore()

const form = ref({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
})
const loading = ref(false)
const error = ref('')

const phoneInput = ref('')
const phoneValid = ref(false)
const phoneCountry = ref('')

const FULL_NAME_PATTERN = /^\p{L}+(\s\p{L}+)+$/u

function onNameInput(e) {
  form.value.name = e.target.value.replace(/[^\p{L}\s]/gu, '')
}

function onPhoneValidate(phoneObject) {
  phoneValid.value = !!phoneObject?.valid
  phoneCountry.value = phoneObject?.country?.name || ''
  form.value.phone = phoneObject?.valid ? phoneObject.number : ''
}

async function submit() {
  error.value = ''

  if (!FULL_NAME_PATTERN.test(form.value.name.trim())) {
    error.value = 'Ingresa tu nombre completo (nombre y apellido), sin números.'
    return
  }
  if (!phoneValid.value || !form.value.phone) {
    error.value = 'Ingresa un número de teléfono válido.'
    return
  }

  loading.value = true
  try {
    await auth.register(form.value)
    router.push('/dashboard')
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors
      ? Object.values(errors).flat().join(' ')
      : e.response?.data?.message || 'No se pudo crear la cuenta.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="auth-layout">
    <div class="auth-left">
      <RouterLink to="/" class="auth-brand">
        <LogoBrand size="xl" />
      </RouterLink>
      <div class="auth-hero">
        <h2 class="auth-tagline">Únete a<br><span class="gradient-text">ALERTSYNC</span></h2>
        <p class="auth-desc">Crea tu cuenta y configura tu red de seguridad personal en minutos.</p>
      </div>
    </div>

    <div class="auth-right">
      <div class="auth-form-container">
        <div class="auth-top-row">
          <RouterLink to="/" class="back-home-link">← Volver al inicio</RouterLink>
          <ThemeToggle />
        </div>

        <div class="auth-form-header">
          <h1>Crear Cuenta</h1>
          <p>Regístrate para comenzar a protegerte</p>
        </div>

        <div v-if="error" class="form-alert form-alert-error visible mb-4">
          <span>⚠️</span><span>{{ error }}</span>
        </div>

        <form class="auth-form" @submit.prevent="submit">
          <div class="form-group">
            <label class="form-label">Nombre completo</label>
            <input
              :value="form.name"
              @input="onNameInput"
              class="form-input"
              placeholder="Nombre y apellido"
              autocomplete="name"
              required
            />
            <p class="hint-text">Nombre y apellido, sin números.</p>
          </div>
          <div class="form-group">
            <label class="form-label">Correo electrónico</label>
            <input v-model="form.email" type="email" class="form-input" placeholder="tu@correo.com" autocomplete="email" required />
          </div>
          <div class="form-group">
            <label class="form-label">Teléfono</label>
            <vue-tel-input
              v-model="phoneInput"
              mode="international"
              default-country="MX"
              class="tel-input"
              @validate="onPhoneValidate"
            ></vue-tel-input>
            <p class="hint-text">
              <span v-if="phoneCountry">📍 País detectado: {{ phoneCountry }}</span>
              <span v-else>Escribe tu número de teléfono</span>
            </p>
          </div>
          <div class="form-group">
            <label class="form-label">Contraseña</label>
            <input v-model="form.password" type="password" class="form-input" placeholder="Mínimo 8 caracteres" required />
            <p class="hint-text">Mínimo 8 caracteres: una mayúscula, minúsculas, número y carácter especial.</p>
          </div>
          <div class="form-group">
            <label class="form-label">Confirmar contraseña</label>
            <input v-model="form.password_confirmation" type="password" class="form-input" required />
          </div>

          <p class="consent-text">
            Al crear tu cuenta aceptas nuestros
            <RouterLink to="/terms" target="_blank">Términos y Condiciones</RouterLink>
            y nuestro
            <RouterLink to="/privacy" target="_blank">Aviso de Privacidad</RouterLink>.
          </p>

          <button type="submit" class="btn btn-primary w-full" :disabled="loading">
            {{ loading ? 'Creando cuenta...' : '🛡️ Crear mi cuenta' }}
          </button>
        </form>

        <div class="auth-footer">
          ¿Ya tienes cuenta? <RouterLink to="/login">Inicia sesión</RouterLink>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.auth-layout { min-height: 100vh; display: grid; grid-template-columns: 1fr 1fr; }
.auth-left {
  display: flex; flex-direction: column; justify-content: space-between;
  padding: var(--space-10);
  background: linear-gradient(160deg, rgba(159,211,45,0.08), rgba(29,93,110,0.12));
  border-right: 1px solid var(--glass-border);
}
.auth-brand { display: flex; align-items: center; text-decoration: none; }
.auth-tagline { font-size: clamp(1.8rem, 3vw, 2.5rem); font-weight: 800; line-height: 1.2; }
.auth-desc { color: var(--text-secondary); max-width: 380px; line-height: 1.7; margin-top: var(--space-5); }
.auth-right { display: flex; align-items: center; justify-content: center; padding: var(--space-10); }
.auth-form-container { width: 100%; max-width: 420px; }
.auth-top-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: var(--space-6); }
.back-home-link {
  display: inline-flex; align-items: center; gap: var(--space-2);
  color: var(--text-secondary); font-size: 0.9rem; font-weight: 600;
  text-decoration: none;
}
.back-home-link:hover { color: var(--brand-lime); }
.auth-form-header h1 { font-size: 1.8rem; font-weight: 800; margin-bottom: var(--space-2); }
.auth-form { display: flex; flex-direction: column; gap: var(--space-4); margin-top: var(--space-6); }
.auth-footer { margin-top: var(--space-6); text-align: center; color: var(--text-secondary); }
.auth-footer a { color: var(--brand-lime); font-weight: 600; }
.form-alert { padding: var(--space-4); border-radius: var(--radius-md); display: flex; gap: var(--space-3); }
.form-alert-error { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.25); color: #FCA5A5; }
.hint-text { margin-top: var(--space-2); font-size: 0.8rem; color: var(--text-muted); }
.consent-text { font-size: 0.8rem; color: var(--text-muted); line-height: 1.6; }
.consent-text a { color: var(--brand-lime); font-weight: 600; }

.tel-input {
  background: rgba(255,255,255,0.04) !important;
  border: 1px solid var(--glass-border) !important;
  border-radius: var(--radius-md) !important;
}
.tel-input:focus-within {
  border-color: var(--brand-lime) !important;
  box-shadow: none !important;
}
.tel-input :deep(.vti__input) {
  background: transparent;
  color: var(--text-primary);
  font-size: 0.95rem;
  font-family: inherit;
  padding: var(--space-3) var(--space-4);
}
.tel-input :deep(.vti__dropdown) {
  padding: 0 var(--space-2) 0 var(--space-3);
}
.tel-input :deep(.vti__dropdown:hover),
.tel-input :deep(.vti__dropdown.open) {
  background: var(--glass-hover);
}
.tel-input :deep(.vti__selection) {
  color: var(--text-primary);
}
.tel-input :deep(.vti__country-code) {
  color: var(--text-secondary);
}
.tel-input :deep(.vti__dropdown-list) {
  background: var(--bg-elevated);
  border: 1px solid var(--glass-border);
  color: var(--text-primary);
  border-radius: var(--radius-md);
}
.tel-input :deep(.vti__dropdown-item.highlighted) {
  background: rgba(159,211,45,0.12);
}
.tel-input :deep(.vti__search_box) {
  background: rgba(255,255,255,0.05);
  border: 1px solid var(--glass-border);
  color: var(--text-primary);
}

@media (max-width: 768px) {
  .auth-layout { grid-template-columns: 1fr; }
  .auth-left { display: none; }
}
</style>
