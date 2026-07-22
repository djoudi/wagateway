<?php
return [
    'admin_emails'                  => explode(',', env('ADMIN_EMAILS', '')),
    'bulk_delay_min'                => env('BULK_DELAY_MIN', 1),
    'bulk_delay_max'                => env('BULK_DELAY_MAX', 3),
    'device_idle_threshold_minutes' => env('DEVICE_IDLE_MINUTES', 60),
    'subscription_grace_days'       => env('SUBSCRIPTION_GRACE_DAYS', 3),
];
