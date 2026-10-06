<script setup>
defineProps({
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md', 'lg', 'xl'].includes(v),
  },
})
</script>

<template>
  <img
    src="/logo.png"
    alt="ALERTSYNC"
    class="brand-logo"
    :class="`brand-logo--${size}`"
  />
</template>

<style scoped>
.brand-logo {
  display: block;
  object-fit: contain;
  aspect-ratio: 1 / 1;
  flex-shrink: 0;
}

.brand-logo--sm { width: 32px; height: 32px; }
.brand-logo--md { width: 48px; height: 48px; }
.brand-logo--lg { width: 68px; height: 68px; }
.brand-logo--xl { width: 96px; height: 96px; }
</style>
