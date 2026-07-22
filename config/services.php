<?php
return [
    'postmark' => ['token' => env('POSTMARK_TOKEN')],
    'ses'      => ['key' => env('AWS_ACCESS_KEY_ID'), 'secret' => env('AWS_SECRET_ACCESS_KEY'), 'region' => env('AWS_DEFAULT_REGION', 'us-east-1')],
    'resend'   => ['key' => env('RESEND_KEY')],
    'slack'    => ['notifications' => ['bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'), 'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL')]],
    'wa_node'  => [
        'url'    => env('WA_SERVICE_URL', 'http://wa-service:3000'),
        'secret' => env('WA_SERVICE_SECRET', ''),
    ],
    'chargily' => [
        'mode'           => env('CHARGILY_MODE', 'test'), // test | live
        'api_key'        => env('CHARGILY_API_KEY', ''),
        'webhook_secret' => env('CHARGILY_WEBHOOK_SECRET', ''),
    ],
];
