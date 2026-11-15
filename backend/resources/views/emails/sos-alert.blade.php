<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ALERTA SOS - ALERTSYNC</title>
</head>
<body style="margin:0; padding:0; background-color:#0B1330; font-family: 'Segoe UI', Helvetica, Arial, sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#0B1330; padding: 40px 16px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px; background-color:#151D3B; border-radius:20px; overflow:hidden; border:2px solid #E53935;">
          @include('emails.partials.header')

          <tr>
            <td style="padding: 24px 40px 0;">
              <div style="background-color:#E53935; color:#FFFFFF; border-radius:12px; padding:14px 18px; font-size:18px; font-weight:800; text-align:center;">
                🚨 ALERTA SOS DE EMERGENCIA
              </div>
            </td>
          </tr>

          <tr>
            <td style="padding: 24px 40px 8px; color:#F1F5F9;">
              <h1 style="font-size:21px; font-weight:800; margin:0 0 12px; color:#F1F5F9;">Hola, {{ $contactName }}</h1>
              <p style="font-size:15px; line-height:1.7; color:#CBD5E1; margin:0 0 16px;">
                <strong style="color:#FF6B6B;">{{ $ownerName }}</strong> activó una alerta SOS y te tiene como contacto de emergencia. Por favor comunícate con {{ $ownerName }} lo antes posible.
              </p>
              <p style="font-size:15px; line-height:1.7; color:#F1F5F9; margin:0 0 8px; background-color:#0B1330; border-radius:10px; padding:12px 16px;">
                “{{ $alertMessage }}”
              </p>
              <p style="font-size:14px; color:#94A3B8; margin:12px 0 0;">
                🕒 Enviada: <strong style="color:#F1F5F9;">{{ $sentAt }}</strong>
              </p>
              @if ($ownerPhone)
                <p style="font-size:14px; color:#94A3B8; margin:6px 0 0;">
                  📞 Teléfono: <a href="tel:{{ $ownerPhone }}" style="color:#9FD32D; font-weight:700;">{{ $ownerPhone }}</a>
                </p>
              @endif
              @if ($bloodType || $allergies)
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:16px; background-color:#0B1330; border-radius:12px; border:1px solid rgba(229,57,53,0.5);">
                  <tr>
                    <td style="padding:12px 16px;">
                      <p style="font-size:13px; font-weight:800; color:#FF6B6B; margin:0 0 8px;">🩺 FICHA MÉDICA</p>
                      @if ($bloodType)
                        <p style="font-size:14px; color:#94A3B8; margin:0 0 4px;">
                          Tipo de sangre: <strong style="font-size:18px; color:#F1F5F9;">{{ $bloodType }}</strong>
                        </p>
                      @endif
                      @if ($allergies)
                        <p style="font-size:14px; color:#94A3B8; margin:0;">
                          Alergias: <strong style="color:#F1F5F9;">{{ $allergies }}</strong>
                        </p>
                      @endif
                      <p style="font-size:12px; color:#64748B; margin:8px 0 0;">Comparte esta información con los servicios de emergencia si es necesario.</p>
                    </td>
                  </tr>
                </table>
              @endif
            </td>
          </tr>

          <tr>
            <td style="padding: 20px 40px 32px;">
              @if ($mapsUrl)
                <table role="presentation" cellpadding="0" cellspacing="0">
                  <tr>
                    <td style="background-color:#9FD32D; border-radius:999px;">
                      <a href="{{ $mapsUrl }}"
                         style="display:inline-block; padding:14px 32px; color:#101835; font-weight:800; font-size:14px; text-decoration:none;">
                        📍 Ver ubicación en el mapa
                      </a>
                    </td>
                  </tr>
                </table>
              @else
                <p style="font-size:14px; color:#FFB300; margin:0;">
                  ⚠️ No se pudo obtener la ubicación GPS en el momento de la alerta.
                </p>
              @endif
              <p style="font-size:13px; color:#64748B; margin:20px 0 0;">
                Si crees que la persona está en peligro inmediato, llama al <strong style="color:#F1F5F9;">911</strong>.
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
