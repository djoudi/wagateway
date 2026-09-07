<?php
// Merge this into config/services.php

return [
    // ... existing services ...

    'wa_node' => [
        'url'    => env('WA_SERVICE_URL', 'http://127.0.0.1:3000'),
        'secret' => env('WA_SERVICE_SECRET', ''),
    ],
];
