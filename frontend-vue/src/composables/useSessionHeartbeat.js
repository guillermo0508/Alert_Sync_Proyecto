import api from '@/services/api'

const HEARTBEAT_INTERVAL_MS = 20000

/**
 * Revisa la sesión cada 20 s y al volver a la pestaña. Con `onUser` se aprovecha la respuesta
 * para mantener al día el usuario (p. ej. un cambio de plan hecho desde la app o el reloj).
 */
export function useSessionHeartbeat({ onUser } = {}) {
  let timer = null

  function check() {
    api.get('/auth/me')
      .then(({ data }) => { if (data?.user && onUser) onUser(data.user) })
      .catch(() => {
        // 401 is handled globally by the axios response interceptor (forced logout).
      })
  }

  function onVisibility() {
    if (!document.hidden) check()
  }

  function start() {
    if (timer) return
    check()
    timer = setInterval(check, HEARTBEAT_INTERVAL_MS)
    document.addEventListener('visibilitychange', onVisibility)
  }

  function stop() {
    if (timer) {
      clearInterval(timer)
      timer = null
    }
    document.removeEventListener('visibilitychange', onVisibility)
  }

  return { start, stop, check }
}
