<script setup>
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import DashboardDemo from '@/components/dashboards/DashboardDemo.vue'
import DashboardBasico from '@/components/dashboards/DashboardBasico.vue'
import DashboardPremium from '@/components/dashboards/DashboardPremium.vue'

const auth = useAuthStore()

const activeDashboard = computed(() => {
  const plan = auth.user?.plan || 'Demo'
  if (plan === 'Premium') return DashboardPremium
  if (plan === 'Básico') return DashboardBasico
  return DashboardDemo
})
</script>

<template>
  <component :is="activeDashboard" />
</template>
