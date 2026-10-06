<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ALERTSYNC</title>
</head>
<body style="margin:0; padding:0; background-color:#0B1330; font-family: 'Segoe UI', Helvetica, Arial, sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#0B1330; padding: 40px 16px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px; background-color:#151D3B; border-radius:20px; overflow:hidden; border:1px solid rgba(255,255,255,0.08);">
          @include('emails.partials.header')

          <tr>
            <td style="padding: 32px 40px 8px; color:#F1F5F9; font-family: 'Segoe UI', Helvetica, Arial, sans-serif;">
              <h1 style="font-size:21px; font-weight:800; margin:0 0 12px; color:#F1F5F9;">¡Gracias por tu compra, {{ $userName }}!</h1>
              <p style="font-size:15px; line-height:1.7; color:#94A3B8; margin:0 0 20px;">
                Hemos procesado tu pago correctamente. Tu suscripción a <strong style="color:#9FD32D;">ALERTSYNC</strong> ya está activa.
              </p>
            </td>
          </tr>

          <tr>
            <td style="padding: 0 40px 24px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:14px;">
                <tr>
                  <td style="padding: 20px 24px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px; font-family: 'Segoe UI', Helvetica, Arial, sans-serif;">
                      <tr>
                        <td style="padding:6px 0; color:#94A3B8; width:150px;">Plan contratado</td>
                        <td style="padding:6px 0; color:#9FD32D; font-weight:800;">{{ $planName }}</td>
                      </tr>
                      <tr>
                        <td style="padding:6px 0; color:#94A3B8;">Monto</td>
                        <td style="padding:6px 0; color:#F1F5F9; font-weight:700;">{{ $planPrice }}</td>
                      </tr>
                      <tr>
                        <td style="padding:6px 0; color:#94A3B8;">Método de pago</td>
                        <td style="padding:6px 0; color:#F1F5F9; font-weight:700;">Tarjeta terminada en {{ $lastFour }}</td>
                      </tr>
                      <tr>
                        <td style="padding:6px 0; color:#94A3B8;">Próximo cargo / expiración</td>
                        <td style="padding:6px 0; color:#F1F5F9; font-weight:700;">{{ $expiresAt }}</td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr>
            <td style="padding: 0 40px 8px; font-family: 'Segoe UI', Helvetica, Arial, sans-serif;">
              <p style="font-size:15px; line-height:1.7; color:#94A3B8; margin:0;">
                A partir de ahora puedes acceder a todas las funciones que incluye tu plan, como agregar más contactos de emergencia, configurar tus dispositivos o visualizar tu dashboard exclusivo.
              </p>
            </td>
          </tr>

          <tr>
            <td style="padding: 8px 40px 32px;">
              <table role="presentation" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="background-color:#9FD32D; border-radius:999px;">
                    <a href="{{ config('app.frontend_url', 'http://localhost:5173') . '/dashboard' }}"
                       style="display:inline-block; padding:14px 32px; color:#101835; font-weight:800; font-size:14px; text-decoration:none; font-family: 'Segoe UI', Helvetica, Arial, sans-serif;">
                      Ir a mi Dashboard
                    </a>
                  </td>
                </tr>
              </table>
              <p style="font-size:13px; color:#64748B; margin:20px 0 0;">
                Si tienes alguna pregunta o necesitas ayuda con la configuración, ponte en contacto con nosotros.
              </p>
            </td>
          </tr>

          @include('emails.partials.footer')
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
