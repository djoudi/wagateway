<?php
// Merge this into config/services.php

return [
    // ... existing services ...

    'wa_node' => [
        'url'    => env('WA_SERVICE_URL', 'http://wa-service:3000'),
        'secret' => env('WA_SERVICE_SECRET', ''),
    ],
];
