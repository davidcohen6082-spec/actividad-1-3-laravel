<?php

/*
| CORS · Actividad 5.1. El cliente frontend corre en otro origen (p. ej. http://localhost:5173) y el
| navegador solo deja leer las respuestas de la API si Laravel responde con Access-Control-Allow-Origin.
| Los orígenes permitidos se configuran en .env (CORS_ALLOWED_ORIGINS, separados por coma).
*/
return [
    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter(array_map('trim', explode(',', env(
        'CORS_ALLOWED_ORIGINS',
        'http://localhost:5173,http://127.0.0.1:5173,http://localhost:3000,http://127.0.0.1:3000'
    )))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Autenticación por token Bearer (no por cookies): no hace falta compartir credenciales.
    'supports_credentials' => false,
];
