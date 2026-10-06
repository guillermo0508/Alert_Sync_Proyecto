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
              <h1 style="font-size:21px; font-weight:800; margin:0 0 12px; color:#F1F5F9;">¡Bienvenido a ALERTSYNC, {{ $userName }}!</h1>
              <p style="font-size:15px; line-height:1.7; color:#94A3B8; margin:0 0 20px;">
                Un administrador acaba de registrarte en el sistema de seguridad personal <strong style="color:#9FD32D;">ALERTSYNC</strong>. Estos son los datos con los que quedó tu cuenta:
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
                        <td style="padding:6px 0; color:#94A3B8; width:110px;">Nombre</td>
                        <td style="padding:6px 0; color:#F1F5F9; font-weight:700;">{{ $userName }}</td>
                      </tr>
                      <tr>
                        <td style="padding:6px 0; color:#94A3B8;">Correo</td>
                        <td style="padding:6px 0; color:#F1F5F9; font-weight:700;">{{ $userEmail }}</td>
                      </tr>
                      @if($username)
                      <tr>
                        <td style="padding:6px 0; color:#94A3B8;">Usuario</td>
                        <td style="padding:6px 0; color:#9FD32D; font-weight:800;">{{ $username }}</td>
                      </tr>
                      @endif
                      <tr>
                        <td style="padding:6px 0; color:#94A3B8;">Teléfono</td>
                        <td style="padding:6px 0; color:#F1F5F9; font-weight:700;">{{ $userPhone ?: 'No especificado' }}</td>
                      </tr>
                      @if(!$username)
                      <tr>
                        <td style="padding:6px 0; color:#94A3B8;">Plan asignado</td>
                        <td style="padding:6px 0; color:#9FD32D; font-weight:800;">{{ $plan }}</td>
                      </tr>
                      @endif
                    </table>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr>
            <td style="padding: 0 40px 8px; font-family: 'Segoe UI', Helvetica, Arial, sans-serif;">
              <p style="font-size:15px; line-height:1.7; color:#94A3B8; margin:0 0 8px;">
                Tu cuenta todavía <strong style="color:#F1F5F9;">no está activada</strong>. Para activarla y crear tu contraseña:
              </p>
              <ol style="font-size:14px; line-height:1.9; color:#94A3B8; margin:0 0 8px; padding-left:20px;">
                <li>Ve a la página de inicio de sesión.</li>
                @if($username)
                <li>Ingresa tu usuario (<strong style="color:#F1F5F9;">{{ $username }}</strong>) — no el correo, ya que este correo puede ser compartido por varios administradores.</li>
                @else
                <li>Ingresa tu correo ({{ $userEmail }}).</li>
                @endif
                <li>Te enviaremos un código de verificación al correo {{ $userEmail }}.</li>
                <li>Ingresa ese código y crea tu contraseña.</li>
              </ol>
            </td>
          </tr>

          <tr>
            <td style="padding: 8px 40px 32px;">
              <table role="presentation" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="background-color:#9FD32D; border-radius:999px;">
                    <a href="{{ config('app.frontend_url', 'http://localhost:5173') . '/login' }}"
                       style="display:inline-block; padding:14px 32px; color:#101835; font-weight:800; font-size:14px; text-decoration:none; font-family: 'Segoe UI', Helvetica, Arial, sans-serif;">
                      Ir a Iniciar Sesión
                    </a>
                  </td>
                </tr>
              </table>
              <p style="font-size:13px; color:#64748B; margin:20px 0 0;">
                Si tú no esperabas este registro, puedes ignorar este correo.
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
