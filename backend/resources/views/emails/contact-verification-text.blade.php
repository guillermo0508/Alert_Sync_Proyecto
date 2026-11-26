Hola, {{ $contactName }}

{{ $ownerName }} te agregó como contacto de emergencia en ALERTSYNC, un sistema de seguridad personal.

Si {{ $ownerName }} activa una alerta SOS, recibirás un aviso con su ubicación para que puedas ayudar.

Tienes {{ $responseMinutes }} minutos para responder. Si no respondes a tiempo, la invitación se marcará como rechazada.

Confirmar que acepto:
{{ $verifyUrl }}
@if ($declineUrl)

No acepto:
{{ $declineUrl }}
@endif

Si no conoces a {{ $ownerName }} o no quieres recibir estas alertas, ignora este correo.

ALERTSYNC
