<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'polymarket' => [
        'api_key' => env('POLYMARKET_API_KEY'),
        'api_secret' => env('POLYMARKET_API_SECRET'),
        'api_passphrase' => env('POLYMARKET_API_PASSPHRASE'),
        'wallet_address' => env('POLYMARKET_WALLET_ADDRESS'),
        'private_key' => env('POLYMARKET_PRIVATE_KEY'),
        'base_url' => env('POLYMARKET_BASE_URL', 'https://clob.polymarket.com'),
        'gamma_url' => env('POLYMARKET_GAMMA_URL', 'https://gamma-api.polymarket.com'),
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
    ],

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1'),
    ],

    'binance' => [
        'base_url' => env('BINANCE_BASE_URL', 'https://api.binance.com/api/v3'),
    ],

    'coinbase' => [
        'base_url' => env('COINBASE_BASE_URL', 'https://api.exchange.coinbase.com'),
    ],

    'kraken' => [
        'base_url' => env('KRAKEN_BASE_URL', 'https://api.kraken.com/0/public'),
    ],

    'coingecko' => [
        'base_url' => env('COINGECKO_BASE_URL', 'https://api.coingecko.com/api/v3'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
    ],

    'nowpayments' => [
        'api_key' => env('NOWPAYMENTS_API_KEY'),
        'ipn_secret' => env('NOWPAYMENTS_IPN_SECRET'),
        'sandbox' => env('NOWPAYMENTS_SANDBOX', true),
        'base_url' => env('NOWPAYMENTS_BASE_URL', 'https://api-sandbox.nowpayments.io/v1'),
    ],

    'turnstile' => [
        'enabled' => env('TURNSTILE_ENABLED', false),
        'site_key' => env('TURNSTILE_SITE_KEY', env('CLOUDFLARE_TURNSTILE_SITE_KEY')),
        'secret_key' => env('TURNSTILE_SECRET_KEY', env('CLOUDFLARE_TURNSTILE_SECRET_KEY')),
    ],

    'queues' => [
        'email' => env('MAIL_QUEUE', 'emails'),
        'telegram' => env('TELEGRAM_QUEUE', 'telegram'),
    ],

];
