<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * URL pública de la página web, para los enlaces que van en los correos
 * (confirmar contacto, iniciar sesión, ver panel...).
 *
 * 1. Si la petición viene de la página web, se usa su propio origen (siempre que
 *    sea un origen permitido por CORS), así el enlace apunta al sitio real.
 * 2. Si no (p. ej. desde la app del celular), se usa FRONTEND_URL.
 * En producción nunca se devuelve un enlace a localhost si hay otra opción.
 */
class FrontendUrl
{
    public static function resolve(?Request $request = null): string
    {
        $configured = self::configured();

        $origin = $request?->headers->get('Origin');
        if ($origin && self::isAllowedOrigin($origin) && ! (app()->isProduction() && self::isLocal($origin))) {
            return rtrim($origin, '/');
        }

        return $configured;
    }

    /** Primer valor de FRONTEND_URL (puede ser una lista separada por comas para CORS). */
    public static function configured(): string
    {
        $first = trim(explode(',', (string) config('app.frontend_url', 'http://localhost:5173'))[0]);
        $url = rtrim($first !== '' ? $first : 'http://localhost:5173', '/');

        // Sin FRONTEND_URL, los enlaces de los correos apuntan a localhost: no funcionan para el
        // contacto y Gmail los trata como sospechosos. Se deja aviso en los Logs de Render.
        if (app()->isProduction() && self::isLocal($url)) {
            \Illuminate\Support\Facades\Log::warning('FRONTEND_URL no está configurada: los enlaces de los correos apuntan a ' . $url);
        }

        return $url;
    }

    public static function to(string $path, ?Request $request = null): string
    {
        return self::resolve($request).'/'.ltrim($path, '/');
    }

    private static function isAllowedOrigin(string $origin): bool
    {
        if (in_array(rtrim($origin, '/'), array_map(fn ($o) => rtrim($o, '/'), config('cors.allowed_origins', [])), true)) {
            return true;
        }
        foreach (config('cors.allowed_origins_patterns', []) as $pattern) {
            if (@preg_match($pattern, $origin) === 1) {
                return true;
            }
        }

        return false;
    }

    private static function isLocal(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST) ?? '';

        return in_array($host, ['localhost', '127.0.0.1', '::1'], true) || str_ends_with($host, '.localhost');
    }
}
