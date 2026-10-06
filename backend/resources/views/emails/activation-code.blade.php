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
              <h1 style="font-size:21px; font-weight:800; margin:0 0 12px; color:#F1F5F9;">Hola, {{ $userName }}</h1>
              <p style="font-size:15px; line-height:1.7; color:#94A3B8; margin:0 0 24px;">
                Usa este código para verificar tu cuenta en <strong style="color:#9FD32D;">ALERTSYNC</strong>:
              </p>
            </td>
          </tr>

          <tr>
            <td style="padding: 0 40px 24px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center" style="background-color:rgba(159,211,45,0.08); border:1px solid rgba(159,211,45,0.4); border-radius:16px; padding: 26px 12px;">
                    <span style="font-size:34px; font-weight:900; letter-spacing:10px; color:#9FD32D; font-family: 'Courier New', Courier, monospace;">{{ $code }}</span>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr>
            <td style="padding: 0 40px 32px; font-family: 'Segoe UI', Helvetica, Arial, sans-serif;">
              <p style="font-size:14px; color:#94A3B8; margin:0 0 6px;">
                ⏱️ Este código vence en <strong style="color:#F1F5F9;">{{ $expiresInMinutes }} minutos</strong>.
              </p>
              <p style="font-size:13px; color:#64748B; margin:16px 0 0;">
                Si tú no solicitaste esto, puedes ignorar este correo.
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
