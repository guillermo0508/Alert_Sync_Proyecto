import api from '@/services/api'

const HEARTBEAT_INTERVAL_MS = 20000

export function useSessionHeartbeat() {
  let timer = null

  function start() {
    if (timer) return
    timer = setInterval(() => {
      api.get('/auth/me').catch(() => {
        // 401 is handled globally by the axios response interceptor (forced logout).
      })
    }, HEARTBEAT_INTERVAL_MS)
  }

  function stop() {
    if (timer) {
      clearInterval(timer)
      timer = null
    }
  }

  return { start, stop }
}
