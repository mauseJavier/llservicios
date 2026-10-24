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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'mercadopago' => [
        'public_key' => env('MERCADOPAGO_PUBLIC_KEY'),
        'access_token' => env('MERCADOPAGO_ACCESS_TOKEN'),
        'app_id' => env('MERCADOPAGO_APP_ID'),
        'client_secret' => env('MERCADOPAGO_CLIENT_SECRET'),
        'sandbox' => env('MERCADOPAGO_SANDBOX', true),
        'user_id' => env('MERCADOPAGO_USER_ID', '105046639'),
        // Cuando está activo se usa public_url (ngrok en desarrollo) para las
        // back_urls y el webhook, en lugar de app.url. En producción dejar
        // MERCADOPAGO_TEST=false para usar el dominio de APP_URL.
        'test_mode' => env('MERCADOPAGO_TEST', false),
        // URL pública por la que MercadoPago puede alcanzar la app.
        // Desarrollo: URL de ngrok. Producción: dejar vacío y usar APP_URL.
        'public_url' => env('MERCADOPAGO_PUBLIC_URL'),
        // El secreto de firma de webhooks se configura por empresa (MP_WEBHOOK_SECRET),
        // porque cada una opera con su propia cuenta de Mercado Pago.
        'webhook_signature_enforce' => env('MERCADOPAGO_WEBHOOK_SIGNATURE_ENFORCE', false),
        'webhook_tolerance' => env('MERCADOPAGO_WEBHOOK_TOLERANCE', 600),
    ],

    'whatsapp' => [
        'api_url' => env('WHATSAPP_API_URL'),
        'api_key' => env('WHATSAPP_API_KEY'),
        'instance_id' => env('WHATSAPP_INSTANCE_ID'),
    ],

];
