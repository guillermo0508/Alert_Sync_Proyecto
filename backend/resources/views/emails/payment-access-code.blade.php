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
            <td style="padding: 32px 40px 8px; color:#F1F5F9;">
              <h1 style="font-size:21px; font-weight:800; margin:0 0 12px; color:#F1F5F9;">Hola, {{ $userName }}</h1>
              <p style="font-size:15px; line-height:1.7; color:#94A3B8; margin:0 0 20px;">
                Alguien (probablemente tú) quiere ver o cambiar el <strong style="color:#9FD32D;">método de pago</strong> en la app de ALERTSYNC. Usa este código para continuar:
              </p>
            </td>
          </tr>
          <tr>
            <td align="center" style="padding: 0 40px 16px;">
              <div style="display:inline-block; background-color:#0B1330; border:2px solid #9FD32D; border-radius:14px; padding:14px 28px;">
                <span style="font-size:34px; font-weight:900; letter-spacing:10px; color:#9FD32D; font-family: 'Courier New', Courier, monospace;">{{ $code }}</span>
              </div>
            </td>
          </tr>
          <tr>
            <td style="padding: 0 40px 32px;">
              <p style="font-size:13px; color:#64748B; margin:8px 0 0;">
                El código vence en {{ $expiresInMinutes }} minutos y sólo se puede usar una vez. Si no fuiste tú, ignora este correo y considera cambiar tu contraseña.
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
