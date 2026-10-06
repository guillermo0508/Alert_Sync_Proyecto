<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import { RouterLink, useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import LogoBrand from '@/components/LogoBrand.vue'
import ThemeToggle from '@/components/ThemeToggle.vue'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()

// step: 'email' -> 'password' (cuenta activa) | 'code' -> 'newPassword' (cuenta pendiente de activar)
//       'adminUsername' -> ... (el correo pertenece a un admin, pide su usuario para desambiguar)
//       'forgotConfirm' -> 'forgotCode' -> 'forgotNewPassword' (olvidé mi contraseña)
const step = ref('email')

const email = ref('')
const adminUsername = ref('')
const password = ref('')
const code = ref('')
const newPassword = ref('')
const newPasswordConfirmation = ref('')
const activationToken = ref('')
const resetToken = ref('')

const remember = ref(false)
const showPassword = ref(false)
const loading = ref(false)
const error = ref('')
const info = ref('')

onMounted(() => {
  if (route.query.passwordChanged === '1') {
    info.value = 'Contraseña actualizada correctamente. Inicia sesión con tu nueva contraseña.'
    auth.clearRememberedCredentials()
    return
  }

  if (route.query.sessionExpired === '1') {
    info.value = 'Tu sesión se cerró porque un administrador actualizó la contraseña de tu cuenta. Inicia sesión con tu nueva contraseña.'
    auth.clearRememberedCredentials()
    return
  }

  const saved = auth.loadRememberedCredentials()
  if (saved?.email) {
    email.value = saved.email
    password.value = saved.password || ''
    remember.value = true
    step.value = 'password'
  }
})

watch(remember, (checked) => {
  if (!checked) {
    auth.clearRememberedCredentials()
  }
})

onBeforeUnmount(() => {
  if (!remember.value) {
    auth.clearRememberedCredentials()
  }
})

function backToEmail() {
  step.value = 'email'
  adminUsername.value = ''
  password.value = ''
  code.value = ''
  newPassword.value = ''
  newPasswordConfirmation.value = ''
  activationToken.value = ''
  resetToken.value = ''
  error.value = ''
  info.value = ''
}

function backToPassword() {
  step.value = 'password'
  code.value = ''
  newPassword.value = ''
  newPasswordConfirmation.value = ''
  resetToken.value = ''
  error.value = ''
  info.value = ''
}

function proceedFromAccountStatus(data, identifier) {
  if (data.status === 'active') {
    const saved = auth.loadRememberedCredentials()
    if (saved?.email === identifier) {
      password.value = saved.password || ''
      remember.value = true
    } else {
      password.value = ''
      remember.value = false
    }
    step.value = 'password'
  } else if (data.status === 'pending') {
    step.value = 'code'
    info.value = data.message || 'Te enviamos un código de verificación a tu correo.'
  }
}

async function submitEmail() {
  error.value = ''
  info.value = ''
  loading.value = true
  try {
    const data = await auth.checkEmail(email.value.trim())
    if (data.status === 'needs_username') {
      step.value = 'adminUsername'
      info.value = data.message || 'Este correo pertenece a un administrador. Ingresa tu usuario para continuar.'
    } else {
      proceedFromAccountStatus(data, email.value.trim())
    }
  } catch (e) {
    error.value = e.response?.data?.message || 'No existe una cuenta con ese correo.'
  } finally {
    loading.value = false
  }
}

async function submitAdminUsername() {
  error.value = ''
  loading.value = true
  try {
    const identifier = adminUsername.value.trim()
    const data = await auth.checkEmail(identifier)
    // De aquí en adelante, el usuario reemplaza al correo como identificador de la cuenta.
    email.value = identifier
    info.value = ''
    proceedFromAccountStatus(data, identifier)
  } catch (e) {
    error.value = e.response?.data?.message || 'Ese usuario no existe.'
  } finally {
    loading.value = false
  }
}

async function submitPassword() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(email.value.trim(), password.value, remember.value)
    router.push(route.query.redirect || '/dashboard')
  } catch (e) {
    error.value = e.response?.data?.message
      || e.response?.data?.errors?.email?.[0]
      || 'No se pudo iniciar sesión. Verifica tus credenciales.'
  } finally {
    loading.value = false
  }
}

function startForgotPassword() {
  step.value = 'forgotConfirm'
  error.value = ''
  info.value = ''
}

async function submitForgotConfirm() {
  error.value = ''
  info.value = ''
  loading.value = true
  try {
    const data = await auth.forgotPassword(email.value.trim())
    info.value = data.message || 'Te enviamos un código para restablecer tu contraseña.'
    step.value = 'forgotCode'
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudo enviar el código.'
  } finally {
    loading.value = false
  }
}

async function resendForgotCode() {
  error.value = ''
  info.value = ''
  loading.value = true
  try {
    const data = await auth.forgotPassword(email.value.trim())
    info.value = data.message || 'Te reenviamos el código a tu correo.'
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudo reenviar el código.'
  } finally {
    loading.value = false
  }
}

async function submitForgotCode() {
  error.value = ''
  loading.value = true
  try {
    const data = await auth.verifyResetCode(email.value.trim(), code.value.trim())
    resetToken.value = data.reset_token
    step.value = 'forgotNewPassword'
  } catch (e) {
    error.value = e.response?.data?.message
      || e.response?.data?.errors?.code?.[0]
      || 'El código es incorrecto o expiró.'
    code.value = ''
  } finally {
    loading.value = false
  }
}

async function submitForgotNewPassword() {
  error.value = ''
  loading.value = true
  try {
    await auth.resetPassword(email.value.trim(), resetToken.value, newPassword.value, newPasswordConfirmation.value, remember.value)
    router.push('/dashboard')
  } catch (e) {
    error.value = e.response?.data?.message
      || e.response?.data?.errors?.password?.[0]
      || e.response?.data?.errors?.reset_token?.[0]
      || 'No se pudo restablecer la contraseña.'
  } finally {
    loading.value = false
  }
}

async function resendCode() {
  error.value = ''
  info.value = ''
  loading.value = true
  try {
    const data = await auth.checkEmail(email.value.trim())
    info.value = data.message || 'Te reenviamos el código a tu correo.'
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudo reenviar el código.'
  } finally {
    loading.value = false
  }
}

async function submitCode() {
  error.value = ''
  loading.value = true
  try {
    const data = await auth.verifyCode(email.value.trim(), code.value.trim())
    activationToken.value = data.activation_token
    step.value = 'newPassword'
  } catch (e) {
    error.value = e.response?.data?.message
      || e.response?.data?.errors?.code?.[0]
      || 'El código es incorrecto o expiró.'
    code.value = ''
  } finally {
    loading.value = false
  }
}

async function submitNewPassword() {
  error.value = ''
  loading.value = true
  try {
    await auth.activate(email.value.trim(), activationToken.value, newPassword.value, newPasswordConfirmation.value, remember.value)
    router.push('/dashboard')
  } catch (e) {
    error.value = e.response?.data?.message
      || e.response?.data?.errors?.password?.[0]
      || e.response?.data?.errors?.activation_token?.[0]
      || 'No se pudo activar la cuenta.'
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
        <h2 class="auth-tagline">Bienvenido de vuelta.<br><span class="gradient-text">Tu seguridad te espera.</span></h2>
        <p class="auth-desc">Accede a tu panel para gestionar contactos, dispositivos y alertas SOS.</p>
      </div>
    </div>

    <div class="auth-right">
      <div class="auth-form-container">
        <div class="auth-top-row">
          <RouterLink to="/" class="back-home-link">← Volver al inicio</RouterLink>
          <ThemeToggle />
        </div>

        <div class="auth-form-header">
          <h1>Iniciar Sesión</h1>
          <p v-if="step === 'email'">Ingresa tu correo para continuar</p>
          <p v-else-if="step === 'adminUsername'">Ingresa tu usuario de administrador para continuar</p>
          <p v-else-if="step === 'password'">Ingresa tu contraseña para acceder a tu cuenta</p>
          <p v-else-if="step === 'code'">Verifica tu cuenta con el código que te enviamos</p>
          <p v-else-if="step === 'newPassword'">Crea tu contraseña para activar tu cuenta</p>
          <p v-else-if="step === 'forgotConfirm'">Te enviaremos un código para restablecer tu contraseña</p>
          <p v-else-if="step === 'forgotCode'">Ingresa el código que te enviamos por correo</p>
          <p v-else>Crea tu nueva contraseña</p>
        </div>

        <div v-if="error" class="form-alert form-alert-error visible mb-4">
          <span>⚠️</span><span>{{ error }}</span>
        </div>
        <div v-if="info" class="form-alert form-alert-info visible mb-4">
          <span>📧</span><span>{{ info }}</span>
        </div>

        <!-- Paso 1: correo -->
        <form v-if="step === 'email'" class="auth-form" @submit.prevent="submitEmail">
          <div class="form-group">
            <label class="form-label" for="email">Correo electrónico</label>
            <div class="input-wrapper">
              <span class="input-icon">✉️</span>
              <input id="email" v-model="email" type="email" class="form-input" placeholder="tu@correo.com" autocomplete="username" required />
            </div>
          </div>

          <button type="submit" class="btn btn-primary w-full" :disabled="loading">
            {{ loading ? 'Verificando...' : 'Continuar' }}
          </button>
        </form>

        <!-- Paso 1b: el correo es de un administrador -> pide su usuario -->
        <form v-else-if="step === 'adminUsername'" class="auth-form" @submit.prevent="submitAdminUsername">
          <div class="form-group">
            <label class="form-label" for="admin-username">Usuario</label>
            <div class="input-wrapper">
              <span class="input-icon">👤</span>
              <input id="admin-username" v-model="adminUsername" class="form-input" placeholder="ej. admin_juan" autocomplete="username" required autofocus />
            </div>
          </div>

          <button type="submit" class="btn btn-primary w-full" :disabled="loading">
            {{ loading ? 'Verificando...' : 'Continuar' }}
          </button>

          <button type="button" class="link-btn resend-link" @click="backToEmail">← Volver</button>
        </form>

        <!-- Paso 2a: cuenta activa -> contraseña -->
        <form v-else-if="step === 'password'" class="auth-form" @submit.prevent="submitPassword">
          <div class="form-group">
            <label class="form-label">Correo o usuario</label>
            <div class="static-value">
              {{ email }}
              <button type="button" class="link-btn" @click="backToEmail">Cambiar</button>
            </div>
          </div>

          <input type="text" :value="email" autocomplete="username" readonly hidden />

          <div class="form-group">
            <label class="form-label" for="password">Contraseña</label>
            <div class="input-wrapper">
              <span class="input-icon">🔑</span>
              <input id="password" v-model="password" :type="showPassword ? 'text' : 'password'" class="form-input" placeholder="••••••••" autocomplete="current-password" required autofocus />
              <span class="input-toggle" @click="showPassword = !showPassword">{{ showPassword ? '🙈' : '👁️' }}</span>
            </div>
          </div>

          <div class="forgot-row">
            <label class="checkbox-label">
              <input v-model="remember" type="checkbox" /> Recordarme
            </label>
            <button type="button" class="link-btn" @click="startForgotPassword">¿Olvidaste tu contraseña?</button>
          </div>

          <button type="submit" class="btn btn-primary w-full" :disabled="loading">
            {{ loading ? 'Iniciando...' : '🔐 Iniciar Sesión' }}
          </button>
        </form>

        <!-- Paso 2b: cuenta pendiente -> código de verificación -->
        <form v-else-if="step === 'code'" class="auth-form" @submit.prevent="submitCode">
          <div class="form-group">
            <label class="form-label">Correo o usuario</label>
            <div class="static-value">
              {{ email }}
              <button type="button" class="link-btn" @click="backToEmail">Cambiar</button>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="code">Código de verificación</label>
            <input id="code" v-model="code" class="form-input" placeholder="000000" maxlength="6" required autofocus />
          </div>

          <button type="submit" class="btn btn-primary w-full" :disabled="loading">
            {{ loading ? 'Verificando...' : 'Verificar código' }}
          </button>

          <button type="button" class="link-btn resend-link" :disabled="loading" @click="resendCode">Reenviar código</button>
        </form>

        <!-- Paso 3: nueva contraseña (activación) -->
        <form v-else-if="step === 'newPassword'" class="auth-form" @submit.prevent="submitNewPassword">
          <div class="form-group">
            <label class="form-label" for="newPassword">Nueva contraseña</label>
            <div class="input-wrapper">
              <span class="input-icon">🔑</span>
              <input id="newPassword" v-model="newPassword" :type="showPassword ? 'text' : 'password'" class="form-input" placeholder="Mínimo 8 caracteres" required autofocus />
              <span class="input-toggle" @click="showPassword = !showPassword">{{ showPassword ? '🙈' : '👁️' }}</span>
            </div>
            <p class="hint-text">Mínimo 8 caracteres: una mayúscula, minúsculas, número y carácter especial.</p>
          </div>

          <div class="form-group">
            <label class="form-label" for="newPasswordConfirmation">Repetir contraseña</label>
            <input id="newPasswordConfirmation" v-model="newPasswordConfirmation" :type="showPassword ? 'text' : 'password'" class="form-input" placeholder="Repite tu contraseña" required />
          </div>

          <label class="checkbox-label">
            <input v-model="remember" type="checkbox" /> Recordarme
          </label>

          <button type="submit" class="btn btn-primary w-full" :disabled="loading">
            {{ loading ? 'Activando...' : '✅ Activar cuenta' }}
          </button>
        </form>

        <!-- Paso olvidé contraseña 1: confirmar envío de código -->
        <form v-else-if="step === 'forgotConfirm'" class="auth-form" @submit.prevent="submitForgotConfirm">
          <div class="form-group">
            <label class="form-label">Correo o usuario</label>
            <div class="static-value">
              {{ email }}
              <button type="button" class="link-btn" @click="backToPassword">Cambiar</button>
            </div>
          </div>

          <p class="text-secondary text-sm">Te enviaremos un código de verificación a este correo para que puedas crear una nueva contraseña.</p>

          <button type="submit" class="btn btn-primary w-full" :disabled="loading">
            {{ loading ? 'Enviando...' : 'Enviar código' }}
          </button>

          <button type="button" class="link-btn resend-link" @click="backToPassword">← Volver a iniciar sesión</button>
        </form>

        <!-- Paso olvidé contraseña 2: código de verificación -->
        <form v-else-if="step === 'forgotCode'" class="auth-form" @submit.prevent="submitForgotCode">
          <div class="form-group">
            <label class="form-label">Correo o usuario</label>
            <div class="static-value">
              {{ email }}
              <button type="button" class="link-btn" @click="backToPassword">Cambiar</button>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="forgotCode">Código de verificación</label>
            <input id="forgotCode" v-model="code" class="form-input" placeholder="000000" maxlength="6" required autofocus />
          </div>

          <button type="submit" class="btn btn-primary w-full" :disabled="loading">
            {{ loading ? 'Verificando...' : 'Verificar código' }}
          </button>

          <button type="button" class="link-btn resend-link" :disabled="loading" @click="resendForgotCode">Reenviar código</button>
        </form>

        <!-- Paso olvidé contraseña 3: nueva contraseña -->
        <form v-else-if="step === 'forgotNewPassword'" class="auth-form" @submit.prevent="submitForgotNewPassword">
          <div class="form-group">
            <label class="form-label" for="forgotNewPassword">Nueva contraseña</label>
            <div class="input-wrapper">
              <span class="input-icon">🔑</span>
              <input id="forgotNewPassword" v-model="newPassword" :type="showPassword ? 'text' : 'password'" class="form-input" placeholder="Mínimo 8 caracteres" required autofocus />
              <span class="input-toggle" @click="showPassword = !showPassword">{{ showPassword ? '🙈' : '👁️' }}</span>
            </div>
            <p class="hint-text">Mínimo 8 caracteres: una mayúscula, minúsculas, número y carácter especial.</p>
          </div>

          <div class="form-group">
            <label class="form-label" for="forgotNewPasswordConfirmation">Repetir contraseña</label>
            <input id="forgotNewPasswordConfirmation" v-model="newPasswordConfirmation" :type="showPassword ? 'text' : 'password'" class="form-input" placeholder="Repite tu contraseña" required />
          </div>

          <label class="checkbox-label">
            <input v-model="remember" type="checkbox" /> Recordarme
          </label>

          <button type="submit" class="btn btn-primary w-full" :disabled="loading">
            {{ loading ? 'Guardando...' : '✅ Restablecer contraseña' }}
          </button>
        </form>

        <div class="auth-footer">
          ¿No tienes cuenta? <RouterLink to="/register">Regístrate gratis</RouterLink>
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
.auth-tagline { font-size: clamp(1.8rem, 3vw, 2.5rem); font-weight: 800; line-height: 1.2; margin-bottom: var(--space-5); }
.auth-desc { color: var(--text-secondary); max-width: 380px; line-height: 1.7; }
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
.auth-form-header p { color: var(--text-secondary); }
.auth-form { display: flex; flex-direction: column; gap: var(--space-5); margin-top: var(--space-6); }
.auth-footer { margin-top: var(--space-6); text-align: center; color: var(--text-secondary); }
.auth-footer a { color: var(--brand-lime); font-weight: 600; }
.form-alert { padding: var(--space-4); border-radius: var(--radius-md); display: flex; gap: var(--space-3); }
.form-alert-error { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.25); color: #FCA5A5; }
.form-alert-info { background: rgba(159,211,45,0.1); border: 1px solid rgba(159,211,45,0.25); color: var(--brand-lime); }
.checkbox-label { display: flex; align-items: center; gap: var(--space-2); color: var(--text-secondary); }
.forgot-row { display: flex; align-items: center; justify-content: space-between; gap: var(--space-3); }
.hint-text { margin-top: var(--space-2); font-size: 0.8rem; color: var(--text-muted); }
.static-value {
  display: flex; align-items: center; justify-content: space-between;
  padding: var(--space-3) var(--space-4); border-radius: var(--radius-md);
  background: var(--glass-bg); border: 1px solid var(--glass-border); color: var(--text-primary);
}
.link-btn {
  background: none; border: none; color: var(--brand-teal-light);
  font-weight: 600; font-size: 0.85rem; cursor: pointer; padding: 0;
}
.link-btn:hover { color: var(--brand-lime); }
.link-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.resend-link { align-self: center; margin-top: var(--space-1); }
@media (max-width: 768px) {
  .auth-layout { grid-template-columns: 1fr; }
  .auth-left { display: none; }
}
</style>
