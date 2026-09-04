<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Reverb Server
    |--------------------------------------------------------------------------
    */
    'default' => env('REVERB_SERVER', 'reverb'),

    /*
    |--------------------------------------------------------------------------
    | Reverb Servers
    |--------------------------------------------------------------------------
    | The internal bind address/port the Reverb process listens on inside
    | the container. This is what `php artisan reverb:start` uses — it is
    | NOT the public-facing address browsers connect to (that's nginx,
    | proxying WebSocket upgrades on /app/* to this internal port — see
    | docker/nginx.conf and docker/nginx.coolify.conf).
    */
    'servers' => [
        'reverb' => [
            'host'     => env('REVERB_SERVER_HOST', '0.0.0.0'),
            'port'     => env('REVERB_SERVER_PORT', 8080),
            'path'     => env('REVERB_SERVER_PATH', ''),
            'hostname' => env('REVERB_HOST'),
            'options' => [
                'tls' => [],
            ],
            'max_request_size' => env('REVERB_MAX_REQUEST_SIZE', 10_000),
            'scaling' => [
                'enabled' => env('REVERB_SCALING_ENABLED', false),
                'channel' => env('REVERB_SCALING_CHANNEL', 'reverb'),
                'server'  => [
                    'url'      => env('REDIS_URL'),
                    'host'     => env('REDIS_HOST', '127.0.0.1'),
                    'port'     => env('REDIS_PORT', '6379'),
                    'username' => env('REDIS_USERNAME'),
                    'password' => env('REDIS_PASSWORD'),
                    'database' => env('REVERB_REDIS_DB', env('REDIS_DB', '0')),
                    'timeout'  => env('REDIS_TIMEOUT', 60),
                ],
            ],
            'pulse_ingest_interval'     => env('REVERB_PULSE_INGEST_INTERVAL', 15),
            'telescope_ingest_interval' => env('REVERB_TELESCOPE_INGEST_INTERVAL', 15),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Reverb Applications
    |--------------------------------------------------------------------------
    | The `options` block here is what the PHP side (broadcasting FROM the
    | app, e.g. `broadcast(new DeviceStatusChanged(...))`) uses to reach the
    | Reverb server's internal HTTP API — so `host`/`port` here should stay
    | pointed at the internal container address (see REVERB_HOST/REVERB_PORT
    | in .env, which default to the `reverb` service name on port 8080).
    |
    | This is a SEPARATE concern from VITE_REVERB_* in .env, which configure
    | the BROWSER's public-facing WebSocket connection (through nginx, over
    | your real domain and port 443) — the two must not be confused.
    */
    'apps' => [
        'provider' => 'config',
        'apps' => [
            [
                'app_id'     => env('REVERB_APP_ID'),
                'key'        => env('REVERB_APP_KEY'),
                'secret'     => env('REVERB_APP_SECRET'),
                'app_key'    => env('REVERB_APP_KEY'),
                'app_secret' => env('REVERB_APP_SECRET'),
                'options'    => [
                    'host'   => env('REVERB_HOST', 'reverb'),
                    'port'   => env('REVERB_PORT', 8080),
                    'scheme' => env('REVERB_SCHEME', 'http'),
                    'useTLS' => false,
                ],
                'allowed_origins'  => explode(',', env('REVERB_ALLOWED_ORIGINS', '*')),
                'ping_interval'    => env('REVERB_APP_PING_INTERVAL', 60),
                'activity_timeout' => env('REVERB_APP_ACTIVITY_TIMEOUT', 30),
                'max_connections'  => env('REVERB_APP_MAX_CONNECTIONS'),
                'max_message_size' => env('REVERB_APP_MAX_MESSAGE_SIZE', 10_000),
                'accept_client_events_from' => env('REVERB_APP_ACCEPT_CLIENT_EVENTS_FROM', 'members'),
            ],
        ],
    ],

];
