import { ref } from 'vue'

const THEME_KEY = 'alertsync_theme'

const theme = ref(localStorage.getItem(THEME_KEY) || 'dark')

function applyTheme(value) {
  document.documentElement.setAttribute('data-theme', value)
}

applyTheme(theme.value)

export function useTheme() {
  function toggleTheme() {
    theme.value = theme.value === 'dark' ? 'light' : 'dark'
    localStorage.setItem(THEME_KEY, theme.value)
    applyTheme(theme.value)
  }

  return { theme, toggleTheme }
}
