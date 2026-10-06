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
      <div class="section-label">🔒 Legal</div>
      <h1 class="text-4xl font-bold mb-4">Aviso de Privacidad</h1>
      <p class="text-muted last-update">Última actualización: julio 2026</p>

      <h2>1. Responsable del tratamiento</h2>
      <p>ALERTSYNC es responsable del tratamiento de los datos personales que nos proporcionas al usar el Servicio, conforme a la legislación aplicable en materia de protección de datos personales.</p>

      <h2>2. Datos que recabamos</h2>
      <ul>
        <li>Datos de identificación: nombre, correo electrónico, teléfono.</li>
        <li>Datos de tus contactos de emergencia: nombre, teléfono, correo, relación.</li>
        <li>Datos de ubicación (GPS) al momento de activar una alerta SOS.</li>
        <li>Datos de dispositivos vinculados (Smart Watch, Alexa).</li>
        <li>Últimos 4 dígitos de tarjeta y nombre del titular, para procesar pagos de suscripción. No almacenamos el número completo de tarjeta ni el CVV.</li>
      </ul>

      <h2>3. Finalidades del tratamiento</h2>
      <ul>
        <li>Crear y administrar tu cuenta.</li>
        <li>Enviar alertas SOS a tus contactos de emergencia con tu ubicación.</li>
        <li>Procesar el pago y la renovación de tu plan de suscripción.</li>
        <li>Enviarte correos operativos: activación de cuenta, restablecimiento de contraseña, recibos de pago.</li>
      </ul>

      <h2>4. Datos de ubicación y emergencia</h2>
      <p>Tu ubicación solo se captura y comparte con tus contactos de emergencia en el momento en que activas una alerta SOS desde tu Smart Watch o app móvil. No monitoreamos tu ubicación de forma continua.</p>

      <h2>5. Con quién compartimos tus datos</h2>
      <p>Compartimos la información necesaria de tu alerta (mensaje y ubicación) únicamente con los contactos de emergencia que tú configuraste. No vendemos ni compartimos tus datos personales con terceros para fines publicitarios.</p>

      <h2>6. Medidas de seguridad</h2>
      <p>Tu contraseña se almacena cifrada. El acceso a la plataforma requiere autenticación por token. Los administradores del sistema pueden ver tu información de cuenta, contactos y alertas con fines de soporte y operación del servicio.</p>

      <h2>7. Derechos ARCO</h2>
      <p>Tienes derecho a Acceder, Rectificar, Cancelar u Oponerte (ARCO) al tratamiento de tus datos personales. Puedes ejercer estos derechos actualizando tu información desde tu panel de cuenta o solicitando la eliminación de tu cuenta, lo cual borra tus contactos, alertas y pagos asociados.</p>

      <h2>8. Cambios a este aviso</h2>
      <p>Podemos actualizar este Aviso de Privacidad. Cualquier cambio se publicará en esta página con su fecha de actualización.</p>

      <h2>9. Contacto</h2>
      <p>Para ejercer tus derechos ARCO o resolver dudas sobre el tratamiento de tus datos, contáctanos a través de los canales indicados en tu panel de cuenta.</p>

      <div class="legal-links">
        <RouterLink to="/terms">Ver Términos y Condiciones →</RouterLink>
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
