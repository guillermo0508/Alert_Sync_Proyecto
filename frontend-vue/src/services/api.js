import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api',
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('alertsync_token') || sessionStorage.getItem('alertsync_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

let forcingLogout = false

// Set to true for the duration of a user-initiated logout, so a 401 from some other
// in-flight request (e.g. the session heartbeat) racing against the logout call doesn't
// get misread as "your session was forcibly invalidated" and show that message instead.
export const manualLogout = { active: false }

api.interceptors.response.use(
  (response) => response,
  (error) => {
    const hadToken = localStorage.getItem('alertsync_token') || sessionStorage.getItem('alertsync_token')
    if (error.response?.status === 401 && hadToken && !forcingLogout && !manualLogout.active) {
      forcingLogout = true
      localStorage.removeItem('alertsync_token')
      localStorage.removeItem('alertsync_user')
      sessionStorage.removeItem('alertsync_token')
      sessionStorage.removeItem('alertsync_user')
      window.location.href = '/login?sessionExpired=1'
    }
    return Promise.reject(error)
  }
)

export default api
