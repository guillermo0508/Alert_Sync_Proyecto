import { ref, watch, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'

/**
 * Menú lateral en celular: se abre con el botón ☰, se cierra al navegar,
 * al tocar fuera o con la tecla Escape, y bloquea el scroll del fondo mientras está abierto.
 */
export function useMobileMenu() {
  const open = ref(false)
  const route = useRoute()

  watch(() => route.fullPath, () => { open.value = false })
  watch(open, (value) => { document.body.style.overflow = value ? 'hidden' : '' })

  function onKey(e) { if (e.key === 'Escape') open.value = false }
  onMounted(() => window.addEventListener('keydown', onKey))
  onUnmounted(() => {
    window.removeEventListener('keydown', onKey)
    document.body.style.overflow = ''
  })

  return { open, toggle: () => { open.value = !open.value }, close: () => { open.value = false } }
}
