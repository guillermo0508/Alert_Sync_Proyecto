<script setup>
import { useTheme } from '@/composables/useTheme'

const { theme, toggleTheme } = useTheme()
</script>

<template>
  <button
    type="button"
    class="theme-toggle"
    :title="theme === 'dark' ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
    @click="toggleTheme"
  >
    {{ theme === 'dark' ? '☀️' : '🌙' }}
  </button>
</template>

<style scoped>
.theme-toggle {
  display: inline-flex; align-items: center; justify-content: center;
  width: 38px; height: 38px; border-radius: var(--radius-full);
  background: var(--glass-bg); border: 1px solid var(--glass-border);
  font-size: 1.1rem; cursor: pointer; transition: all var(--transition-fast);
}
.theme-toggle:hover { background: var(--glass-hover); border-color: var(--brand-lime); }
</style>
