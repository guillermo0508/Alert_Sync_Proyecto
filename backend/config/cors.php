<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter(array_map('trim', explode(',', env('FRONTEND_URL', 'http://localhost:5173')))),

    'allowed_origins_patterns' => [
        '#^http://localhost:\d+$#',
        '#^https://.*\.onrender\.com$#',
        // Página web desplegada en Vercel (producción y vistas previas del proyecto alertsync-frontend).
        '#^https://alertsync[a-z0-9-]*\.vercel\.app$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
