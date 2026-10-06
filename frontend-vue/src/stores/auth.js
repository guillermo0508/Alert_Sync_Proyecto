import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api, { manualLogout } from '@/services/api'

const REMEMBER_KEY = 'alertsync_remember'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(null)

  const isAuthenticated = computed(() => !!token.value)

  function loadFromStorage() {
    token.value = localStorage.getItem('alertsync_token') || sessionStorage.getItem('alertsync_token')
    const raw = localStorage.getItem('alertsync_user') || sessionStorage.getItem('alertsync_user')
    user.value = raw ? JSON.parse(raw) : null
  }

  function saveRememberedCredentials(email, password) {
    localStorage.setItem(REMEMBER_KEY, JSON.stringify({ email, password }))
  }

  function clearRememberedCredentials() {
    localStorage.removeItem(REMEMBER_KEY)
  }

  function loadRememberedCredentials() {
    const raw = localStorage.getItem(REMEMBER_KEY)
    if (!raw) return null
    try {
      return JSON.parse(raw)
    } catch {
      return null
    }
  }

  function persistRememberedCredentials(email, password, remember) {
    if (remember) {
      saveRememberedCredentials(email, password)
    } else {
      clearRememberedCredentials()
    }
  }

  function persistAuth(data, remember = false) {
    const storage = remember ? localStorage : sessionStorage
    storage.setItem('alertsync_token', data.token)
    storage.setItem('alertsync_user', JSON.stringify(data.user))
    token.value = data.token
    user.value = data.user
  }

  async function login(email, password, remember = false) {
    const { data } = await api.post('/auth/login', { email, password })
    persistAuth(data, remember)
    persistRememberedCredentials(email, password, remember)
    return data
  }

  async function register(payload) {
    const { data } = await api.post('/auth/register', payload)
    persistAuth(data, false)
    return data
  }

  async function checkEmail(email) {
    const { data } = await api.post('/auth/check-email', { email })
    return data
  }

  async function verifyCode(email, code) {
    const { data } = await api.post('/auth/verify-code', { email, code })
    return data
  }

  async function activate(email, activationToken, password, passwordConfirmation, remember = false) {
    const { data } = await api.post('/auth/activate', {
      email,
      activation_token: activationToken,
      password,
      password_confirmation: passwordConfirmation,
    })
    persistAuth(data, remember)
    persistRememberedCredentials(email, password, remember)
    return data
  }

  async function logout() {
    manualLogout.active = true
    try {
      await api.post('/auth/logout')
    } catch {
      // ignore network errors on logout
    } finally {
      localStorage.removeItem('alertsync_token')
      localStorage.removeItem('alertsync_user')
      sessionStorage.removeItem('alertsync_token')
      sessionStorage.removeItem('alertsync_user')
      token.value = null
      user.value = null
      manualLogout.active = false
    }
  }

  function persistUser(updatedUser) {
    user.value = updatedUser
    if (localStorage.getItem('alertsync_token')) {
      localStorage.setItem('alertsync_user', JSON.stringify(updatedUser))
    } else if (sessionStorage.getItem('alertsync_token')) {
      sessionStorage.setItem('alertsync_user', JSON.stringify(updatedUser))
    }
  }

  function applyUserUpdate(updatedUser) {
    persistUser(updatedUser)
  }

  async function fetchMe() {
    const { data } = await api.get('/auth/me')
    persistUser(data.user)
    return data.user
  }

  async function forgotPassword(email) {
    const { data } = await api.post('/auth/forgot-password', { email })
    return data
  }

  async function verifyResetCode(email, code) {
    const { data } = await api.post('/auth/verify-reset-code', { email, code })
    return data
  }

  async function resetPassword(email, resetToken, password, passwordConfirmation, remember = false) {
    const { data } = await api.post('/auth/reset-password', {
      email,
      reset_token: resetToken,
      password,
      password_confirmation: passwordConfirmation,
    })
    persistAuth(data, remember)
    persistRememberedCredentials(email, password, remember)
    return data
  }

  async function changePassword(currentPassword, password, passwordConfirmation) {
    manualLogout.active = true

    let response
    try {
      response = await api.post('/auth/change-password', {
        current_password: currentPassword,
        password,
        password_confirmation: passwordConfirmation,
      })
    } catch (e) {
      manualLogout.active = false
      throw e
    }

    // Only reached on success: the backend just revoked this token, so log out locally too.
    clearRememberedCredentials()
    localStorage.removeItem('alertsync_token')
    localStorage.removeItem('alertsync_user')
    sessionStorage.removeItem('alertsync_token')
    sessionStorage.removeItem('alertsync_user')
    token.value = null
    user.value = null
    manualLogout.active = false
    return response.data
  }

  loadFromStorage()

  return {
    user,
    token,
    isAuthenticated,
    login,
    register,
    checkEmail,
    verifyCode,
    activate,
    forgotPassword,
    verifyResetCode,
    resetPassword,
    logout,
    fetchMe,
    applyUserUpdate,
    changePassword,
    loadFromStorage,
    loadRememberedCredentials,
    clearRememberedCredentials,
  }
})
