<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Proxies de confianza
    |--------------------------------------------------------------------------
    |
    | Si la app va detrás de un proxy (Docker/nginx/traefik), indica aquí su
    | IP/CIDR o "*" para confiar en todos. Sirve para detectar bien la IP real
    | del cliente (importante para el límite de intentos de login) y el HTTPS.
    | Déjalo sin definir si la app se expone directamente a Internet.
    |
    | Se lee desde un archivo de config (no con env() en bootstrap) para que
    | siga funcionando al ejecutar `php artisan config:cache`.
    |
    */

    'proxies' => env('TRUSTED_PROXIES'),

];
