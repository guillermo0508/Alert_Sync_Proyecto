<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'

const loading = ref(true)
const saving = ref(false)
const error = ref('')
const toast = ref('')

const form = ref({
  hero_badge: '',
  hero_title: '',
  hero_subtitle: '',
  features: [],
  faqs: [],
  cta_title: '',
  cta_subtitle: '',
})

async function load() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get('/site-content')
    form.value = data.content
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudo cargar el contenido del sitio.'
  } finally {
    loading.value = false
  }
}

onMounted(load)

function addFaq() {
  form.value.faqs.push({ q: '', a: '' })
}

function removeFaq(idx) {
  form.value.faqs.splice(idx, 1)
}

async function save() {
  saving.value = true
  error.value = ''
  try {
    const { data } = await api.put('/admin/site-content', form.value)
    form.value = data.content
    toast.value = 'Contenido del sitio actualizado. Ya se refleja en la página pública.'
    setTimeout(() => { toast.value = '' }, 4000)
  } catch (e) {
    error.value = e.response?.data?.message
      || Object.values(e.response?.data?.errors || {}).flat().join(' ')
      || 'No se pudo guardar el contenido.'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div>
    <div class="topbar">
      <div class="topbar-title">
        <h1>Contenido Web</h1>
        <p>Edita los textos que ven los visitantes en la página pública (/)</p>
      </div>
      <a href="/" target="_blank" class="btn btn-secondary btn-sm">Ver página pública ↗</a>
    </div>

    <div class="page-content">
      <div v-if="toast" class="toast-banner success">{{ toast }}</div>
      <div v-if="error" class="toast-banner">{{ error }}</div>
      <div v-if="loading" class="text-secondary">Cargando contenido...</div>

      <form v-else class="content-form" @submit.prevent="save">
        <section class="form-section">
          <h3>Hero (portada)</h3>
          <div class="form-group">
            <label class="form-label">Insignia</label>
            <input v-model="form.hero_badge" class="form-input" maxlength="80" required />
          </div>
          <div class="form-group">
            <label class="form-label">Título</label>
            <input v-model="form.hero_title" class="form-input" maxlength="150" required />
          </div>
          <div class="form-group">
            <label class="form-label">Subtítulo</label>
            <textarea v-model="form.hero_subtitle" class="form-input" rows="3" maxlength="400" required></textarea>
          </div>
        </section>

        <section class="form-section">
          <h3>Características (3 tarjetas)</h3>
          <div v-for="(f, idx) in form.features" :key="idx" class="feature-row">
            <input v-model="f.icon" class="form-input icon-input" maxlength="10" placeholder="🔒" required />
            <input v-model="f.title" class="form-input" maxlength="80" placeholder="Título" required />
            <input v-model="f.text" class="form-input" maxlength="200" placeholder="Descripción" required />
          </div>
        </section>

        <section class="form-section">
          <h3>Preguntas frecuentes</h3>
          <div v-for="(item, idx) in form.faqs" :key="idx" class="faq-row">
            <div class="faq-fields">
              <input v-model="item.q" class="form-input" maxlength="150" placeholder="Pregunta" required />
              <textarea v-model="item.a" class="form-input" rows="2" maxlength="500" placeholder="Respuesta" required></textarea>
            </div>
            <button type="button" class="link-btn danger" @click="removeFaq(idx)">Eliminar</button>
          </div>
          <button type="button" class="btn btn-secondary btn-sm" @click="addFaq">+ Agregar pregunta</button>
        </section>

        <section class="form-section">
          <h3>Llamado a la acción final</h3>
          <div class="form-group">
            <label class="form-label">Título</label>
            <input v-model="form.cta_title" class="form-input" maxlength="150" required />
          </div>
          <div class="form-group">
            <label class="form-label">Subtítulo</label>
            <input v-model="form.cta_subtitle" class="form-input" maxlength="300" required />
          </div>
        </section>

        <button type="submit" class="btn btn-primary" :disabled="saving">
          {{ saving ? 'Guardando...' : 'Guardar cambios' }}
        </button>
      </form>
    </div>
  </div>
</template>

<style scoped>
.topbar {
  display: flex; align-items: center; justify-content: space-between;
  padding: var(--space-4) var(--space-8);
  background: var(--surface-nav); border-bottom: 1px solid var(--glass-border);
  position: sticky; top: 0; z-index: 40;
}
.topbar-title h1 { font-size: 1.25rem; font-weight: 700; }
.topbar-title p { font-size: 0.8rem; color: var(--text-muted); }
.page-content { padding: var(--space-8); max-width: 760px; }
.toast-banner {
  margin-bottom: var(--space-4); padding: var(--space-4);
  background: rgba(255,59,59,0.1); border: 1px solid rgba(255,59,59,0.2);
  border-radius: var(--radius-md);
}
.toast-banner.success { background: rgba(159,211,45,0.12); border-color: rgba(159,211,45,0.3); }

.content-form { display: flex; flex-direction: column; gap: var(--space-8); }
.form-section {
  background: var(--glass-bg); border: 1px solid var(--glass-border);
  border-radius: var(--radius-xl); padding: var(--space-6);
  display: flex; flex-direction: column; gap: var(--space-4);
}
.form-section h3 { font-size: 1rem; font-weight: 700; margin-bottom: var(--space-2); }

.feature-row { display: grid; grid-template-columns: 60px 1fr 2fr; gap: var(--space-3); }
.icon-input { text-align: center; }

.faq-row { display: flex; gap: var(--space-3); align-items: flex-start; padding-bottom: var(--space-4); border-bottom: 1px solid var(--glass-border); }
.faq-row:last-of-type { border-bottom: none; }
.faq-fields { flex: 1; display: flex; flex-direction: column; gap: var(--space-2); }
.link-btn { color: var(--brand-teal-light); font-weight: 600; font-size: 0.85rem; background: none; border: none; cursor: pointer; white-space: nowrap; }
.link-btn.danger { color: var(--red-500); }

@media (max-width: 700px) {
  .feature-row { grid-template-columns: 1fr; }
}
</style>
