<?php
return [
    'admin_emails'                  => array_values(array_filter(array_map(
        static fn (string $email) => strtolower(trim($email)),
        explode(',', (string) env('ADMIN_EMAILS', '')),
    ))),
    'bulk_delay_min'                => env('BULK_DELAY_MIN', 1),
    'bulk_delay_max'                => env('BULK_DELAY_MAX', 3),
    'device_idle_threshold_minutes' => env('DEVICE_IDLE_MINUTES', 60),
    'subscription_grace_days'       => env('SUBSCRIPTION_GRACE_DAYS', 3),
];
