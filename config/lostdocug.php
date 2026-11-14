<?php

return [
    'encryption_key_version' => env('LOSTDOCUG_ENCRYPTION_KEY_VERSION', 1),
    'ocr_shared_secret' => env('OCR_SHARED_SECRET', ''),
    'retention' => [
        'unresolved_reports_days' => 90,
        'recovered_reports_days' => 30,
        'audit_events_days' => 365,
    ],
];
