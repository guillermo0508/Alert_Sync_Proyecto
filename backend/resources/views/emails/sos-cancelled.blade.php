<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Alerta cancelada - ALERTSYNC</title>
</head>
<body style="margin:0; padding:0; background-color:#0B1330; font-family: 'Segoe UI', Helvetica, Arial, sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#0B1330; padding: 40px 16px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px; background-color:#151D3B; border-radius:20px; overflow:hidden; border:1px solid rgba(159,211,45,0.5);">
          @include('emails.partials.header')

          <tr>
            <td style="padding: 24px 40px 0;">
              <div style="background-color:#9FD32D; color:#101835; border-radius:12px; padding:14px 18px; font-size:18px; font-weight:800; text-align:center;">
                ✅ ALERTA SOS CANCELADA
              </div>
            </td>
          </tr>

          <tr>
            <td style="padding: 24px 40px 32px; color:#F1F5F9;">
              <h1 style="font-size:21px; font-weight:800; margin:0 0 12px; color:#F1F5F9;">Hola, {{ $contactName }}</h1>
              <p style="font-size:15px; line-height:1.7; color:#CBD5E1; margin:0 0 16px;">
                <strong style="color:#9FD32D;">{{ $ownerName }}</strong> canceló la alerta SOS que te llegó anteriormente. Ya no necesita ayuda.
              </p>
              <p style="font-size:14px; color:#94A3B8; margin:0 0 6px;">
                🕒 Alerta enviada: <strong style="color:#F1F5F9;">{{ $sentAt }}</strong>
              </p>
              <p style="font-size:14px; color:#94A3B8; margin:0;">
                ✅ Cancelada: <strong style="color:#F1F5F9;">{{ $cancelledAt }}</strong>
              </p>
              <p style="font-size:13px; color:#64748B; margin:20px 0 0;">
                Si tienes dudas, comunícate directamente con {{ $ownerName }}.
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
