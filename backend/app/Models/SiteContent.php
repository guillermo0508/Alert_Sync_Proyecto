<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'hero_badge', 'hero_title', 'hero_subtitle',
    'features', 'faqs',
    'cta_title', 'cta_subtitle',
])]
class SiteContent extends Model
{
    protected function casts(): array
    {
        return [
            'features' => 'array',
            'faqs' => 'array',
        ];
    }

    public static function defaults(): array
    {
        return [
            'hero_badge' => 'Sistema activo y listo',
            'hero_title' => 'Tu seguridad, siempre conectada',
            'hero_subtitle' => 'ALERTSYNC conecta tu celular y tu smartwatch para enviar alertas SOS con tu ubicación a tus contactos de confianza en segundos y vigilar tus signos vitales en tiempo real.',
            'features' => [
                ['icon' => '⌚', 'title' => 'Integración con Smartwatch', 'text' => 'Activa el botón SOS con un solo toque desde tu reloj inteligente vinculado.'],
                ['icon' => '❤️', 'title' => 'Signos Vitales en Tiempo Real', 'text' => 'Tu reloj envía tu pulso y otros signos vitales cada 5 segundos y, con Premium, avisa a tus contactos si tu pulso es anormal.'],
                ['icon' => '👥', 'title' => 'Contactos de Emergencia', 'text' => 'Gestiona contactos de confianza con notificaciones por SMS, correo o llamada.'],
            ],
            'faqs' => [
                ['q' => '¿Qué pasa si no tengo un plan contratado?', 'a' => 'Puedes explorar el sistema en modo Demo, sin costo, para conocer los menús de los planes Básico y Premium antes de contratar uno.'],
                ['q' => '¿Puedo cambiar de plan más adelante?', 'a' => 'Sí. Desde la sección "Suscripción" de tu panel puedes actualizar o programar un cambio de plan cuando lo necesites.'],
                ['q' => '¿Cómo se notifica a mis contactos de emergencia?', 'a' => 'Al activar el SOS, tus contactos configurados reciben la alerta por SMS, llamada o correo, junto con tu ubicación si está disponible.'],
            ],
            'cta_title' => '¿Listo para proteger a los que más quieres?',
            'cta_subtitle' => 'Crea tu cuenta hoy y activa tu red de seguridad personal en minutos.',
        ];
    }
}
