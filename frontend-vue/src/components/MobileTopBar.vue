<script setup>
import { RouterLink } from 'vue-router'
import LogoBrand from '@/components/LogoBrand.vue'
import ThemeToggle from '@/components/ThemeToggle.vue'

defineProps({
  open: { type: Boolean, default: false },
  home: { type: String, default: '/' },
  title: { type: String, default: '' },
})
defineEmits(['toggle'])
</script>

<template>
  <!-- Solo se muestra en pantallas angostas (celular / tableta), ver estilos. -->
  <header class="mobile-topbar">
    <button
      type="button"
      class="menu-btn"
      :aria-label="open ? 'Cerrar menú' : 'Abrir menú'"
      :aria-expanded="open"
      aria-controls="app-sidebar"
      @click="$emit('toggle')"
    >
      <span class="menu-icon" :class="{ open }" aria-hidden="true"><i /><i /><i /></span>
    </button>
    <RouterLink :to="home" class="mobile-brand">
      <LogoBrand size="sm" />
      <span v-if="title">{{ title }}</span>
    </RouterLink>
    <ThemeToggle />
  </header>
</template>

<style scoped>
.mobile-topbar {
  display: none;
  position: sticky; top: 0; z-index: 45;
  align-items: center; gap: var(--space-3);
  padding: var(--space-2) var(--space-4);
  padding-top: max(var(--space-2), env(safe-area-inset-top));
  background: var(--surface-nav); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
  border-bottom: 1px solid var(--glass-border);
}
.mobile-brand {
  flex: 1; display: flex; align-items: center; gap: var(--space-2);
  text-decoration: none; color: var(--text-primary); font-weight: 700; font-size: 0.95rem; min-width: 0;
}
.mobile-brand span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.menu-btn {
  width: 44px; height: 44px; flex-shrink: 0; display: grid; place-items: center;
  background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: var(--radius-md);
  color: var(--text-primary); cursor: pointer;
}
.menu-icon { width: 20px; height: 14px; position: relative; display: block; }
.menu-icon i {
  position: absolute; left: 0; width: 100%; height: 2px; border-radius: 2px; background: currentColor;
  transition: transform 0.2s ease, opacity 0.2s ease, top 0.2s ease;
}
.menu-icon i:nth-child(1) { top: 0; }
.menu-icon i:nth-child(2) { top: 6px; }
.menu-icon i:nth-child(3) { top: 12px; }
.menu-icon.open i:nth-child(1) { top: 6px; transform: rotate(45deg); }
.menu-icon.open i:nth-child(2) { opacity: 0; }
.menu-icon.open i:nth-child(3) { top: 6px; transform: rotate(-45deg); }

@media (max-width: 1024px) {
  .mobile-topbar { display: flex; }
}
</style>
