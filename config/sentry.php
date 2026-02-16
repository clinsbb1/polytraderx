<?php

declare(strict_types=1);

return [
    'dsn' => env('SENTRY_LARAVEL_DSN', env('SENTRY_DSN')),
    'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV', 'production')),
    'release' => env('SENTRY_RELEASE'),
    'sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.0),
    'send_default_pii' => (bool) env('SENTRY_SEND_DEFAULT_PII', false),
];

