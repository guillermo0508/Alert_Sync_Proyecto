<script setup>
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import LogoBrand from '@/components/LogoBrand.vue'
import ThemeToggle from '@/components/ThemeToggle.vue'
import { useSessionHeartbeat } from '@/composables/useSessionHeartbeat'
import { onMounted, onUnmounted } from 'vue'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const { start: startHeartbeat, stop: stopHeartbeat } = useSessionHeartbeat()
onMounted(startHeartbeat)
onUnmounted(stopHeartbeat)

const navItems = [
  { to: '/admin', name: 'admin-dashboard', icon: '📊', label: 'Resumen' },
  { to: '/admin/users', name: 'admin-users', icon: '👥', label: 'Usuarios' },
  { to: '/admin/payments', name: 'admin-payments', icon: '💳', label: 'Pagos' },
  { to: '/admin/alerts', name: 'admin-alerts', icon: '🚨', label: 'Alertas SOS' },
  { to: '/admin/content', name: 'admin-content', icon: '📝', label: 'Contenido Web' },
  { to: '/admin/plans', name: 'admin-plans', icon: '💲', label: 'Planes' },
]

async function logout() {
  await auth.logout()
  router.push('/login')
}
</script>

<template>
  <div class="admin-layout">
    <aside class="sidebar">
      <div class="sidebar-header">
        <RouterLink to="/admin" class="sidebar-brand">
          <LogoBrand size="xl" />
        </RouterLink>
        <div class="flex gap-2 items-center">
          <ThemeToggle />
          <span class="admin-tag">ADMIN</span>
        </div>
      </div>

      <div class="sidebar-nav-section">
        <div class="nav-section-label">Administración</div>
        <ul class="sidebar-nav-list">
          <li v-for="item in navItems" :key="item.name">
            <RouterLink :to="item.to" class="sidebar-link" :class="{ active: route.name === item.name }">
              <span class="nav-icon">{{ item.icon }}</span>
              {{ item.label }}
            </RouterLink>
          </li>
        </ul>

        <div class="nav-section-label" style="margin-top: var(--space-5)">Cuenta</div>
        <ul class="sidebar-nav-list">
          <li>
            <button class="sidebar-link" style="width:100%; border:none; background:none; cursor:pointer" @click="logout">
              <span class="nav-icon">🚪</span> Cerrar sesión
            </button>
          </li>
        </ul>
      </div>

      <div class="sidebar-footer">
        <div class="user-pill">
          <div class="user-avatar">🛠️</div>
          <div class="user-info">
            <div class="user-name">{{ auth.user?.name || 'Admin' }}</div>
            <div class="user-role">Administrador</div>
          </div>
        </div>
      </div>
    </aside>

    <main class="main-content">
      <RouterView />
    </main>
  </div>
</template>

<style scoped>
.admin-layout { display: flex; min-height: 100vh; }
.sidebar {
  position: fixed; left: 0; top: 0; bottom: 0; width: 240px;
  background: var(--surface-nav); backdrop-filter: blur(20px);
  border-right: 1px solid var(--glass-border);
  display: flex; flex-direction: column; z-index: 50;
}
.sidebar-header {
  padding: var(--space-6); border-bottom: 1px solid var(--glass-border);
  display: flex; align-items: center; justify-content: space-between;
}
.sidebar-brand { display: flex; align-items: center; text-decoration: none; }
.admin-tag {
  font-size: 0.65rem; font-weight: 800; letter-spacing: 0.1em;
  color: var(--brand-lime); background: rgba(159,211,45,0.12);
  border: 1px solid rgba(159,211,45,0.3); border-radius: var(--radius-full);
  padding: 2px 8px;
}
.sidebar-nav-section { padding: var(--space-4) var(--space-3); flex: 1; }
.nav-section-label {
  font-size: 0.7rem; font-weight: 700; color: var(--text-muted);
  letter-spacing: 0.1em; text-transform: uppercase;
  padding: var(--space-2) var(--space-3); margin-bottom: var(--space-1);
}
.sidebar-nav-list { list-style: none; display: flex; flex-direction: column; gap: 2px; }
.sidebar-link {
  display: flex; align-items: center; gap: var(--space-3);
  padding: var(--space-3) var(--space-4); border-radius: var(--radius-md);
  font-size: 0.875rem; font-weight: 500; color: var(--text-secondary);
  text-decoration: none; transition: all var(--transition-fast); position: relative;
}
.sidebar-link:hover { color: var(--text-primary); background: var(--glass-bg); }
.sidebar-link.active {
  color: var(--brand-lime); background: rgba(159,211,45,0.08); font-weight: 600;
}
.sidebar-link.active::before {
  content: ''; position: absolute; left: 0; top: 20%; bottom: 20%;
  width: 2px; background: var(--brand-lime); border-radius: 0 2px 2px 0;
}
.nav-icon { font-size: 1rem; width: 20px; text-align: center; }
.sidebar-footer { padding: var(--space-4) var(--space-3); border-top: 1px solid var(--glass-border); }
.user-pill {
  display: flex; align-items: center; gap: var(--space-3);
  padding: var(--space-3); border-radius: var(--radius-md); background: var(--glass-bg);
}
.user-avatar {
  width: 36px; height: 36px;
  background: linear-gradient(135deg, var(--brand-lime), var(--brand-teal-light));
  border-radius: 50%; display: flex; align-items: center; justify-content: center;
}
.user-name { font-size: 0.85rem; font-weight: 600; }
.user-role { font-size: 0.75rem; color: var(--text-muted); }
.main-content { margin-left: 240px; flex: 1; min-width: 0; min-height: 100vh; }
@media (max-width: 1024px) {
  .sidebar { transform: translateX(-100%); }
  .main-content { margin-left: 0; }
}
</style>
