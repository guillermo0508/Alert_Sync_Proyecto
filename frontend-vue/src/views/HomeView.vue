<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import LogoBrand from '@/components/LogoBrand.vue'
import ThemeToggle from '@/components/ThemeToggle.vue'
import api from '@/services/api'

const paidPlans = ref([])

const content = ref({
  hero_badge: 'Sistema activo y listo',
  hero_title: 'Tu seguridad, siempre conectada',
  hero_subtitle: 'ALERTSYNC vincula tu smartwatch y tu Alexa para enviar alertas SOS a tus contactos de confianza en segundos. Un toque. Una voz. Protección total.',
  features: [
    { icon: '⌚', title: 'Integración con Smartwatch', text: 'Activa el botón SOS con un solo toque desde tu reloj inteligente vinculado.' },
    { icon: '🗣️', title: 'Control por Voz con Alexa', text: 'Di "Alexa, pedir ayuda" y el sistema enviará alertas a tus contactos de emergencia.' },
    { icon: '👥', title: 'Contactos de Emergencia', text: 'Gestiona contactos de confianza con notificaciones por SMS, correo o llamada.' },
  ],
  faqs: [
    { q: '¿Qué pasa si no tengo un plan contratado?', a: 'Puedes explorar el sistema en modo Demo, sin costo, para conocer los menús de los planes Básico y Premium antes de contratar uno.' },
    { q: '¿Puedo cambiar de plan más adelante?', a: 'Sí. Desde la sección "Suscripción" de tu panel puedes actualizar o programar un cambio de plan cuando lo necesites.' },
    { q: '¿Cómo se notifica a mis contactos de emergencia?', a: 'Al activar el SOS, tus contactos configurados reciben la alerta por SMS, llamada o correo, junto con tu ubicación si está disponible.' },
  ],
  cta_title: '¿Listo para proteger a los que más quieres?',
  cta_subtitle: 'Crea tu cuenta hoy y activa tu red de seguridad personal en minutos.',
})

function splitTitle(title) {
  const words = title.trim().split(' ')
  const mid = Math.ceil(words.length / 2)
  return { first: words.slice(0, mid).join(' '), rest: words.slice(mid).join(' ') }
}

onMounted(async () => {
  try {
    const { data } = await api.get('/plans')
    paidPlans.value = data.plans
  } catch (e) {
    console.error('Error cargando planes', e)
  }
  try {
    const { data } = await api.get('/site-content')
    content.value = data.content
  } catch (e) {
    console.error('Error cargando contenido del sitio', e)
  }
})
</script>

<template>
  <nav class="navbar">
    <RouterLink to="/" class="navbar-brand">
      <LogoBrand size="xl" />
    </RouterLink>
    <ul class="navbar-nav">
      <li><a href="#features" class="nav-link">Características</a></li>
      <li><a href="#how-it-works" class="nav-link">Cómo funciona</a></li>
      <li><a href="#plans" class="nav-link">Planes</a></li>
      <li><a href="#faq" class="nav-link">Preguntas</a></li>
    </ul>
    <div class="flex gap-3 items-center">
      <ThemeToggle />
      <RouterLink to="/login" class="btn btn-secondary btn-sm">Iniciar Sesión</RouterLink>
      <RouterLink to="/register" class="btn btn-primary btn-sm">Registrarse</RouterLink>
    </div>
  </nav>

  <section class="hero">
    <div class="hero-badge">
      <span class="dot dot-pulse"></span>
      {{ content.hero_badge }}
    </div>
    <h1 class="hero-title">
      {{ splitTitle(content.hero_title).first }}<br>
      <span class="gradient-text">{{ splitTitle(content.hero_title).rest }}</span>
    </h1>
    <p class="hero-subtitle">
      {{ content.hero_subtitle }}
    </p>
    <div class="hero-actions">
      <RouterLink to="/register" class="btn btn-primary btn-xl">🚀 Comenzar ahora</RouterLink>
      <a href="#how-it-works" class="btn btn-secondary btn-xl">Ver cómo funciona</a>
    </div>
  </section>

  <section id="features" class="section">
    <div class="container">
      <div class="text-center">
        <div class="section-label">✨ Características</div>
        <h2 class="text-4xl font-bold mb-4">
          Todo lo que necesitas para estar<br>
          <span class="gradient-text">siempre protegido</span>
        </h2>
      </div>
      <div class="features-grid">
        <div v-for="(f, idx) in content.features" :key="idx" class="feature-card">
          <div class="feature-icon">{{ f.icon }}</div>
          <h3>{{ f.title }}</h3>
          <p>{{ f.text }}</p>
        </div>
      </div>
    </div>
  </section>

  <section id="how-it-works" class="section">
    <div class="container text-center">
      <div class="section-label">⚡ Proceso</div>
      <h2 class="text-4xl font-bold mb-8">¿Cómo funciona <span class="gradient-text">ALERTSYNC</span>?</h2>
      <div class="steps">
        <div class="step"><div class="step-number">1</div><h3>Regístrate</h3><p>Crea tu cuenta en minutos.</p></div>
        <div class="step"><div class="step-number">2</div><h3>Agrega contactos</h3><p>Configura tus contactos de emergencia.</p></div>
        <div class="step"><div class="step-number">3</div><h3>Vincula dispositivos</h3><p>Conecta smartwatch y Alexa.</p></div>
        <div class="step"><div class="step-number">4</div><h3>¡Estás protegido!</h3><p>Un toque o voz activa la alerta SOS.</p></div>
      </div>
    </div>
  </section>

  <section id="plans" class="section">
    <div class="container">
      <div class="text-center">
        <div class="section-label">⭐ Planes</div>
        <h2 class="text-4xl font-bold mb-4">
          Elige el plan que se ajuste<br>
          <span class="gradient-text">a tu tranquilidad</span>
        </h2>
        <p class="text-secondary" style="max-width:560px; margin:0 auto">
          Empieza gratis en modo Demo o contrata un plan para activar tu red de seguridad real.
        </p>
      </div>

      <div class="plans-grid">
        <div class="plan-card">
          <div class="plan-header">
            <h3>Demo</h3>
            <div class="plan-price">Gratis</div>
          </div>
          <ul class="plan-features">
            <li>✓ Explora los menús de Básico y Premium</li>
            <li>✓ Conoce el flujo completo del sistema</li>
            <li>✓ Sin necesidad de tarjeta</li>
          </ul>
          <RouterLink to="/register" class="btn btn-secondary w-full">Probar Demo</RouterLink>
        </div>

        <div v-for="plan in paidPlans" :key="plan.id" class="plan-card" :class="{ 'is-premium': plan.id === 'premium' }">
          <div v-if="plan.id === 'premium'" class="plan-badge">Más popular</div>
          <div class="plan-header">
            <h3>{{ plan.name }}</h3>
            <div class="plan-price">{{ plan.price }}</div>
          </div>
          <ul class="plan-features">
            <li v-for="(feat, idx) in plan.features" :key="idx">✓ {{ feat }}</li>
          </ul>
          <RouterLink to="/register" class="btn w-full" :class="plan.id === 'premium' ? 'btn-primary' : 'btn-secondary'">
            Contratar {{ plan.name }}
          </RouterLink>
        </div>
      </div>
    </div>
  </section>

  <section id="faq" class="section">
    <div class="container container-md">
      <div class="text-center">
        <div class="section-label">❓ Preguntas frecuentes</div>
        <h2 class="text-4xl font-bold mb-8">Todo lo que debes saber</h2>
      </div>
      <div class="faq-list">
        <details v-for="(item, idx) in content.faqs" :key="idx" class="faq-item">
          <summary>{{ item.q }}</summary>
          <p>{{ item.a }}</p>
        </details>
      </div>
    </div>
  </section>

  <section class="cta-banner">
    <div class="container text-center">
      <h2 class="text-4xl font-bold mb-4">{{ content.cta_title }}</h2>
      <p class="text-secondary mb-8" style="max-width:520px; margin:0 auto var(--space-8)">
        {{ content.cta_subtitle }}
      </p>
      <RouterLink to="/register" class="btn btn-primary btn-xl">🚀 Comenzar ahora</RouterLink>
    </div>
  </section>

  <footer class="site-footer">
    <div class="container footer-grid">
      <div class="footer-brand">
        <LogoBrand size="lg" />
        <p>Sistema de seguridad personal que vincula smartwatch y Alexa para enviar alertas SOS a tus contactos de confianza.</p>
      </div>
      <div class="footer-col">
        <h4>Producto</h4>
        <a href="#features">Características</a>
        <a href="#how-it-works">Cómo funciona</a>
        <a href="#plans">Planes</a>
      </div>
      <div class="footer-col">
        <h4>Cuenta</h4>
        <RouterLink to="/login">Iniciar Sesión</RouterLink>
        <RouterLink to="/register">Registrarse</RouterLink>
      </div>
      <div class="footer-col">
        <h4>Ayuda</h4>
        <a href="#faq">Preguntas frecuentes</a>
      </div>
      <div class="footer-col">
        <h4>Legal</h4>
        <RouterLink to="/terms">Términos y Condiciones</RouterLink>
        <RouterLink to="/privacy">Aviso de Privacidad</RouterLink>
      </div>
    </div>
    <div class="footer-bottom">
      © 2026 ALERTSYNC — Proyecto Integrador
    </div>
  </footer>
</template>

<style scoped>
.navbar-brand :deep(.brand-logo) {
  height: clamp(64px, 12vw, 96px);
}
.hero {
  min-height: 90vh; display: flex; flex-direction: column; align-items: center;
  justify-content: center; text-align: center; padding: var(--space-20) var(--space-6);
}
.hero-badge {
  display: inline-flex; align-items: center; gap: var(--space-2);
  padding: var(--space-2) var(--space-5); background: rgba(159,211,45,0.1);
  border: 1px solid rgba(159,211,45,0.25); border-radius: var(--radius-full);
  font-size: 0.8rem; font-weight: 700; color: var(--brand-lime);
  letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: var(--space-6);
}
.hero-title {
  font-size: clamp(2.8rem, 8vw, 5rem); font-weight: 900; line-height: 1.05;
  letter-spacing: -0.02em; margin-bottom: var(--space-6);
}
.hero-subtitle {
  font-size: clamp(1rem, 2.5vw, 1.25rem); color: var(--text-secondary);
  max-width: 600px; margin: 0 auto var(--space-10); line-height: 1.7;
}
.hero-actions { display: flex; align-items: center; gap: var(--space-4); flex-wrap: wrap; justify-content: center; }
.features-grid {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: var(--space-6); margin-top: var(--space-12);
}
.feature-card {
  background: var(--glass-bg); border: 1px solid var(--glass-border);
  border-radius: var(--radius-xl); padding: var(--space-8);
}
.feature-icon {
  width: 64px; height: 64px; border-radius: var(--radius-lg);
  display: flex; align-items: center; justify-content: center;
  font-size: 2rem; margin-bottom: var(--space-5);
  background: rgba(159,211,45,0.12);
}
.feature-card h3 { font-size: 1.15rem; font-weight: 700; margin-bottom: var(--space-3); }
.feature-card p { font-size: 0.9rem; color: var(--text-secondary); line-height: 1.7; }
.steps {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: var(--space-6); margin-top: var(--space-8);
}
.step {
  text-align: center; padding: var(--space-8) var(--space-6);
  background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: var(--radius-xl);
}
.step-number {
  width: 48px; height: 48px;
  background: linear-gradient(135deg, var(--brand-lime), var(--brand-teal-light));
  color: var(--brand-navy);
  border-radius: 50%; display: flex; align-items: center; justify-content: center;
  font-size: 1.2rem; font-weight: 800; margin: 0 auto var(--space-5);
}

.plans-grid {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: var(--space-6); margin-top: var(--space-12); align-items: stretch;
}
.plan-card {
  position: relative; display: flex; flex-direction: column;
  background: var(--glass-bg); border: 1px solid var(--glass-border);
  border-radius: var(--radius-xl); padding: var(--space-8);
}
.plan-card.is-premium {
  border-color: rgba(159,211,45,0.35);
  background: linear-gradient(160deg, rgba(159,211,45,0.08), rgba(29,93,110,0.12));
}
.plan-badge {
  position: absolute; top: var(--space-5); right: var(--space-5);
  background: var(--brand-lime); color: var(--brand-navy);
  font-size: 0.7rem; font-weight: 800; letter-spacing: 0.05em; text-transform: uppercase;
  padding: 2px 10px; border-radius: var(--radius-full);
}
.plan-header { text-align: center; margin-bottom: var(--space-6); }
.plan-header h3 { font-size: 1.25rem; font-weight: 700; color: var(--text-secondary); }
.is-premium .plan-header h3 { color: var(--brand-lime); }
.plan-price { font-size: 2rem; font-weight: 900; margin-top: var(--space-2); }
.plan-features { list-style: none; flex: 1; margin-bottom: var(--space-6); }
.plan-features li {
  padding: var(--space-3) 0; border-bottom: 1px solid rgba(255,255,255,0.05);
  color: var(--text-secondary); font-size: 0.9rem;
}

.faq-list { display: flex; flex-direction: column; gap: var(--space-4); margin-top: var(--space-10); }
.faq-item {
  background: var(--glass-bg); border: 1px solid var(--glass-border);
  border-radius: var(--radius-lg); padding: var(--space-5) var(--space-6);
}
.faq-item summary {
  cursor: pointer; font-weight: 700; list-style: none;
}
.faq-item summary::-webkit-details-marker { display: none; }
.faq-item summary::after { content: '+'; float: right; color: var(--brand-lime); font-weight: 800; }
.faq-item[open] summary::after { content: '−'; }
.faq-item p { margin-top: var(--space-4); color: var(--text-secondary); line-height: 1.7; }

.cta-banner {
  padding: var(--space-20) var(--space-6);
  background: linear-gradient(135deg, rgba(159,211,45,0.1), rgba(29,93,110,0.15));
  border-top: 1px solid var(--glass-border); border-bottom: 1px solid var(--glass-border);
}

.site-footer { border-top: 1px solid var(--glass-border); padding: var(--space-16) var(--space-6) var(--space-8); }
.footer-grid {
  display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr;
  gap: var(--space-8); margin-bottom: var(--space-10);
}
.footer-brand p { color: var(--text-muted); margin-top: var(--space-4); max-width: 320px; line-height: 1.7; font-size: 0.9rem; }
.footer-col { display: flex; flex-direction: column; gap: var(--space-3); }
.footer-col h4 { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); margin-bottom: var(--space-2); }
.footer-col a { color: var(--text-secondary); text-decoration: none; font-size: 0.9rem; }
.footer-col a:hover { color: var(--brand-lime); }
.footer-bottom {
  text-align: center; color: var(--text-muted); font-size: 0.85rem;
  padding-top: var(--space-8); border-top: 1px solid var(--glass-border);
}
@media (max-width: 768px) {
  .footer-grid { grid-template-columns: 1fr; }
}
</style>
