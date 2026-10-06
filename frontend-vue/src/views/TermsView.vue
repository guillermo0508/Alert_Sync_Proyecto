<script setup>
import { RouterLink } from 'vue-router'
import LogoBrand from '@/components/LogoBrand.vue'
import ThemeToggle from '@/components/ThemeToggle.vue'
</script>

<template>
  <nav class="navbar">
    <RouterLink to="/" class="navbar-brand">
      <LogoBrand size="md" />
    </RouterLink>
    <div class="flex gap-3 items-center">
      <RouterLink to="/" class="back-home-link">← Volver al inicio</RouterLink>
      <ThemeToggle />
      <RouterLink to="/login" class="btn btn-secondary btn-sm">Iniciar Sesión</RouterLink>
      <RouterLink to="/register" class="btn btn-primary btn-sm">Registrarse</RouterLink>
    </div>
  </nav>

  <section class="section">
    <div class="container container-md legal-doc">
      <div class="section-label">📄 Legal</div>
      <h1 class="text-4xl font-bold mb-4">Términos y Condiciones</h1>
      <p class="text-muted last-update">Última actualización: julio 2026</p>

      <h2>1. Aceptación de los términos</h2>
      <p>Al crear una cuenta o usar ALERTSYNC ("el Servicio"), aceptas estos Términos y Condiciones. Si no estás de acuerdo, no debes usar el Servicio.</p>

      <h2>2. Descripción del servicio</h2>
      <p>ALERTSYNC es un sistema de seguridad personal que vincula smartwatch, Alexa y una app web para enviar alertas SOS a contactos de emergencia configurados por el usuario, incluyendo ubicación cuando esté disponible.</p>

      <h2>3. Cuenta de usuario</h2>
      <p>Eres responsable de mantener la confidencialidad de tu contraseña y de toda actividad realizada desde tu cuenta. Debes proporcionar información veraz al registrarte.</p>

      <h2>4. Planes y pagos</h2>
      <ul>
        <li><strong>Demo:</strong> gratuito, sin acceso a activación real de alertas ni dispositivos.</li>
        <li><strong>Básico:</strong> $100 MXN/mes — hasta 5 contactos de emergencia, soporte Smart Watch.</li>
        <li><strong>Premium:</strong> $200 MXN/mes — hasta 15 contactos de emergencia, soporte Smart Watch y Alexa.</li>
      </ul>
      <p>Los precios están en pesos mexicanos (MXN) e incluyen renovación mensual automática salvo cancelación. Puedes cambiar o cancelar tu plan en cualquier momento desde tu panel; los cambios aplican en el siguiente ciclo de facturación salvo que se indique lo contrario.</p>

      <h2>5. Uso del servicio de alertas SOS</h2>
      <p><strong>ALERTSYNC no sustituye a los servicios de emergencia oficiales (911).</strong> El envío y la recepción de alertas dependen de la conectividad a internet, el estado del dispositivo vinculado y la disponibilidad de tus contactos de emergencia. No garantizamos tiempos de entrega ni la disponibilidad ininterrumpida del servicio.</p>

      <h2>6. Responsabilidades del usuario</h2>
      <p>Debes mantener actualizados tus contactos de emergencia y la información de tus dispositivos vinculados. El uso indebido del botón SOS (activaciones falsas o de mala fe) puede resultar en la suspensión de tu cuenta.</p>

      <h2>7. Propiedad intelectual</h2>
      <p>El software, diseño, marca y logotipo de ALERTSYNC son propiedad de sus desarrolladores. No está permitida su reproducción sin autorización.</p>

      <h2>8. Cancelación y terminación</h2>
      <p>Puedes cancelar tu cuenta en cualquier momento. Nos reservamos el derecho de suspender cuentas que incumplan estos términos.</p>

      <h2>9. Limitación de responsabilidad</h2>
      <p>ALERTSYNC se ofrece "tal cual". En la máxima medida permitida por la ley, no somos responsables por daños derivados de fallas de conectividad, del dispositivo, o de la falta de respuesta de un contacto de emergencia.</p>

      <h2>10. Modificaciones</h2>
      <p>Podemos actualizar estos Términos en cualquier momento. Los cambios se publicarán en esta página con su fecha de actualización.</p>

      <h2>11. Contacto</h2>
      <p>Para dudas sobre estos Términos, contáctanos a través de los canales indicados en tu panel de cuenta.</p>

      <div class="legal-links">
        <RouterLink to="/privacy">Ver Aviso de Privacidad →</RouterLink>
      </div>
    </div>
  </section>

  <footer class="legal-footer">
    © 2026 ALERTSYNC — Proyecto Integrador
  </footer>
</template>

<style scoped>
.back-home-link {
  display: inline-flex; align-items: center; gap: var(--space-2);
  color: var(--text-secondary); font-size: 0.9rem; font-weight: 600;
  text-decoration: none;
}
.back-home-link:hover { color: var(--brand-lime); }
.legal-doc h1 { margin-bottom: var(--space-2); }
.last-update { margin-bottom: var(--space-10); font-size: 0.85rem; }
.legal-doc h2 {
  font-size: 1.15rem; font-weight: 800; margin: var(--space-8) 0 var(--space-3);
  color: var(--brand-lime);
}
.legal-doc p, .legal-doc li {
  color: var(--text-secondary); line-height: 1.8; font-size: 0.95rem;
}
.legal-doc ul { padding-left: var(--space-6); margin-bottom: var(--space-3); }
.legal-doc li { margin-bottom: var(--space-2); }
.legal-links { margin-top: var(--space-10); padding-top: var(--space-6); border-top: 1px solid var(--glass-border); }
.legal-links a { color: var(--brand-lime); font-weight: 600; text-decoration: none; }
.legal-footer {
  border-top: 1px solid var(--glass-border); padding: var(--space-8);
  text-align: center; color: var(--text-muted);
}
</style>
