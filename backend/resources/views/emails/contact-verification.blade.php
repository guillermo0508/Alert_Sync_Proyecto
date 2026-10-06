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
              <h1 style="font-size:21px; font-weight:800; margin:0 0 12px; color:#F1F5F9;">Hola, {{ $contactName }}</h1>
              <p style="font-size:15px; line-height:1.7; color:#94A3B8; margin:0 0 20px;">
                <strong style="color:#9FD32D;">{{ $ownerName }}</strong> te agregó como contacto de emergencia en <strong style="color:#9FD32D;">ALERTSYNC</strong>, un sistema de seguridad personal.
              </p>
              <p style="font-size:15px; line-height:1.7; color:#94A3B8; margin:0 0 20px;">
                Esto significa que si {{ $ownerName }} activa una alerta SOS, recibirás un aviso con su ubicación para que puedas ayudar. Confirma que aceptas recibir estas alertas:
              </p>
            </td>
          </tr>

          <tr>
            <td style="padding: 0 40px 32px;">
              <table role="presentation" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="background-color:#9FD32D; border-radius:999px;">
                    <a href="{{ $verifyUrl }}"
                       style="display:inline-block; padding:14px 32px; color:#101835; font-weight:800; font-size:14px; text-decoration:none; font-family: 'Segoe UI', Helvetica, Arial, sans-serif;">
                      ✅ Confirmar que acepto
                    </a>
                  </td>
                </tr>
              </table>
              <p style="font-size:13px; color:#64748B; margin:20px 0 0;">
                Si no conoces a {{ $ownerName }} o no quieres recibir estas alertas, simplemente ignora este correo.
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
