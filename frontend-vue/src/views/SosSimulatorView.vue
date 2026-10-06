<script setup>
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import DashboardBasico from '@/components/dashboards/DashboardBasico.vue'
import DashboardPremium from '@/components/dashboards/DashboardPremium.vue'

const activeTab = ref('basico')

const tabs = [
  { id: 'basico', label: 'Básico' },
  { id: 'premium', label: 'Premium' },
]
</script>

<template>
  <div>
    <div class="topbar">
      <div>
        <h1>Explorar Planes</h1>
        <p>Así se ve el menú de cada plan una vez contratado</p>
      </div>
    </div>

    <div class="page-content">
      <div class="tab-switcher">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          class="tab-btn"
          :class="{ active: activeTab === tab.id }"
          @click="activeTab = tab.id"
        >
          {{ tab.label }}
        </button>
      </div>

      <div class="preview-banner">
        <div>
          👀 Vista previa del plan <strong>{{ activeTab === 'basico' ? 'Básico' : 'Premium' }}</strong>.
          El botón SOS solo se activa desde tu Smart Watch vinculado o la app móvil, no desde la web.
        </div>
        <RouterLink to="/dashboard/plans" class="btn btn-primary btn-sm">
          Contratar {{ activeTab === 'basico' ? 'Básico' : 'Premium' }}
        </RouterLink>
      </div>

      <div class="preview-frame">
        <DashboardBasico v-if="activeTab === 'basico'" />
        <DashboardPremium v-else />
      </div>
    </div>
  </div>
</template>

<style scoped>
.topbar { padding: var(--space-4) var(--space-8); border-bottom: 1px solid var(--glass-border); }
.page-content { padding: var(--space-8); }

.tab-switcher {
  display: inline-flex; gap: var(--space-2); padding: var(--space-1);
  background: var(--glass-bg); border: 1px solid var(--glass-border);
  border-radius: var(--radius-full); margin-bottom: var(--space-6);
}
.tab-btn {
  padding: var(--space-2) var(--space-6); border-radius: var(--radius-full);
  font-weight: 700; font-size: 0.9rem; color: var(--text-secondary);
  background: none; border: none; cursor: pointer; transition: all var(--transition-fast);
}
.tab-btn.active { background: var(--brand-lime); color: var(--brand-navy); }

.preview-banner {
  display: flex; align-items: center; justify-content: space-between; gap: var(--space-4);
  background: rgba(159,211,45,0.08); border: 1px solid rgba(159,211,45,0.25);
  border-radius: var(--radius-lg); padding: var(--space-4) var(--space-6);
  margin-bottom: var(--space-6); color: var(--text-secondary); font-size: 0.9rem; line-height: 1.6;
}
.preview-banner strong { color: var(--brand-lime); }

.preview-frame {
  height: 720px; overflow-y: auto;
  border: 1px solid var(--glass-border); border-radius: var(--radius-xl);
  background: var(--bg-base);
}

@media (max-width: 768px) {
  .preview-banner { flex-direction: column; align-items: flex-start; }
}
</style>
