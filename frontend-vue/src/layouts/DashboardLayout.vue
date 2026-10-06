<script setup>
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import LogoBrand from '@/components/LogoBrand.vue'
import ThemeToggle from '@/components/ThemeToggle.vue'
import DeviceLinkModal from '@/components/DeviceLinkModal.vue'
import { useSessionHeartbeat } from '@/composables/useSessionHeartbeat'

import { computed, ref, onMounted, onUnmounted } from 'vue'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const { start: startHeartbeat, stop: stopHeartbeat } = useSessionHeartbeat()
onMounted(startHeartbeat)
onUnmounted(stopHeartbeat)

const isDemo = computed(() => auth.user?.plan === 'Demo' || !auth.user?.plan)
const isPremium = computed(() => auth.user?.plan === 'Premium')

const navItems = computed(() => {
  const items = []

  if (isDemo.value) {
    items.push({ to: '/dashboard/sos', name: 'sos', icon: '👀', label: 'Explorar Planes', badge: 'DEMO' })
  } else {
    items.push(
      { to: '/dashboard', name: 'dashboard', icon: '🏠', label: 'Menú' },
      { to: '/dashboard/contacts', name: 'contacts', icon: '👥', label: 'Contactos' },
      { to: '/dashboard/alerts', name: 'my-alerts', icon: '📋', label: 'Historial' },
    )
  }

  items.push({ to: '/dashboard/plans', name: 'plans', icon: '⭐', label: 'Suscripción' })
  return items
})

const activeDeviceModal = ref(null) // null | 'watch' | 'alexa'

const DEVICE_META = {
  watch: { label: 'Smartwatch', icon: '⌚' },
  alexa: { label: 'Alexa', icon: '🔊' },
}

const activeDeviceMeta = computed(() => activeDeviceModal.value ? DEVICE_META[activeDeviceModal.value] : null)
const activeDeviceLinked = computed(() => !!auth.user?.devices?.[activeDeviceModal.value])
const activeDeviceName = computed(() => auth.user?.device_names?.[activeDeviceModal.value] || null)

function openDeviceModal(device) {
  activeDeviceModal.value = device
}

async function logout() {
  await auth.logout()
  router.push('/login')
}
</script>

<template>
  <div class="dashboard-layout">
    <aside class="sidebar">
      <div class="sidebar-header">
        <RouterLink to="/" class="sidebar-brand">
          <LogoBrand size="xl" />
        </RouterLink>
        <ThemeToggle />
      </div>

      <div class="sidebar-nav-section">
        <div class="nav-section-label">Principal</div>
        <ul class="sidebar-nav-list">
          <li v-for="item in navItems" :key="item.name">
            <RouterLink
              :to="item.to"
              class="sidebar-link"
              :class="{ active: route.name === item.name }"
            >
              <span class="nav-icon">{{ item.icon }}</span>
              {{ item.label }}
              <span v-if="item.badge" class="nav-badge">{{ item.badge }}</span>
            </RouterLink>
          </li>
        </ul>

        <template v-if="!isDemo">
          <div class="nav-section-label" style="margin-top: var(--space-5)">Dispositivos</div>
          <ul class="sidebar-nav-list">
            <li>
              <button class="sidebar-link device-link" @click="openDeviceModal('watch')">
                <span class="nav-icon">⌚</span> Smartwatch
                <span style="margin-left: auto" :class="{ 'dot-off': !auth.user?.devices?.watch }">
                  {{ auth.user?.devices?.watch ? '🟢' : '⚪' }}
                </span>
              </button>
            </li>
            <li v-if="isPremium">
              <button class="sidebar-link device-link" @click="openDeviceModal('alexa')">
                <span class="nav-icon">🔊</span> Alexa
                <span style="margin-left: auto" :class="{ 'dot-off': !auth.user?.devices?.alexa }">
                  {{ auth.user?.devices?.alexa ? '🟢' : '⚪' }}
                </span>
              </button>
            </li>
          </ul>
        </template>

        <div class="nav-section-label" style="margin-top: var(--space-5)">Cuenta</div>
        <ul class="sidebar-nav-list">
          <li v-if="auth.user?.role === 'admin'">
            <RouterLink to="/admin" class="sidebar-link">
              <span class="nav-icon">🛠️</span> Panel Admin
            </RouterLink>
          </li>
          <li>
            <RouterLink
              to="/dashboard/change-password"
              class="sidebar-link"
              :class="{ active: route.name === 'change-password' }"
            >
              <span class="nav-icon">🔑</span> Cambiar contraseña
            </RouterLink>
          </li>
          <li>
            <button class="sidebar-link" style="width:100%; border:none; background:none; cursor:pointer" @click="logout">
              <span class="nav-icon">🚪</span> Cerrar sesión
            </button>
          </li>
        </ul>
      </div>

      <div class="sidebar-footer">
        <RouterLink
          to="/dashboard/profile"
          class="user-pill"
          :class="{ active: route.name === 'profile' }"
        >
          <div class="user-avatar">👤</div>
          <div class="user-info">
            <div class="user-name">{{ auth.user?.name || 'Usuario' }}</div>
            <div class="user-role">Plan {{ auth.user?.plan || 'Demo' }}</div>
          </div>
        </RouterLink>
      </div>
    </aside>

    <main class="main-content">
      <RouterView />
    </main>

    <DeviceLinkModal
      v-if="activeDeviceModal"
      :device="activeDeviceModal"
      :label="activeDeviceMeta.label"
      :icon="activeDeviceMeta.icon"
      :linked="activeDeviceLinked"
      :device-name="activeDeviceName"
      @close="activeDeviceModal = null"
    />
  </div>
</template>

<style scoped>
.dashboard-layout { display: flex; min-height: 100vh; }
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
.sidebar-nav-section { padding: var(--space-4) var(--space-3); flex: 1; min-height: 0; overflow-y: auto; }
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
.device-link { width: 100%; border: none; background: none; cursor: pointer; }
.dot-off { opacity: 0.5; }
.sidebar-link.active {
  color: var(--brand-lime); background: rgba(159,211,45,0.08); font-weight: 600;
}
.sidebar-link.active::before {
  content: ''; position: absolute; left: 0; top: 20%; bottom: 20%;
  width: 2px; background: var(--brand-lime); border-radius: 0 2px 2px 0;
}
.nav-icon { font-size: 1rem; width: 20px; text-align: center; }
.nav-badge {
  margin-left: auto; background: var(--red-500); color: #fff;
  font-size: 0.7rem; font-weight: 700; padding: 1px 7px; border-radius: var(--radius-full);
}
.sidebar-footer { padding: var(--space-4) var(--space-3); border-top: 1px solid var(--glass-border); }
.user-pill {
  display: flex; align-items: center; gap: var(--space-3);
  padding: var(--space-3); border-radius: var(--radius-md); background: var(--glass-bg);
  text-decoration: none; color: inherit; transition: all var(--transition-fast);
  border: 1px solid transparent;
}
.user-pill:hover {
  border-color: var(--glass-border);
  background: rgba(255,255,255,0.04);
}
.user-pill.active {
  border-color: rgba(159,211,45,0.35);
  background: rgba(159,211,45,0.08);
}
.user-avatar {
  width: 36px; height: 36px;
  background: linear-gradient(135deg, var(--brand-teal), var(--brand-teal-light));
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
