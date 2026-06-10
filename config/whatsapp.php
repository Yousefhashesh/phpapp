<?php

return [
    'enabled' => env('WHATSAPP_ENABLED', false),
    'service_url' => env('WHATSAPP_SERVICE_URL', 'http://127.0.0.1:3001'),
    'api_secret' => env('WHATSAPP_API_SECRET', ''),
    'group_id' => env('WHATSAPP_GROUP_ID', ''),
];
