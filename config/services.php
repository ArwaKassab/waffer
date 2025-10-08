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

    'smschef' => [
        'base_url'     => env('SMSCHEF_BASE_URL', 'https://www.cloud.smschef.com'),
        'secret'       => env('SMSCHEF_SECRET'),
        'device_uuid'  => env('SMSCHEF_DEVICE_UUID'),
        'sim'          => (int) env('SMSCHEF_SIM', 1),
        'priority'     => (int) env('SMSCHEF_PRIORITY', 1),
        'expire'       => (int) env('SMSCHEF_EXPIRE', 300),
    ],

    'firebase_web' => [
        'api_key'          => env('FIREBASE_WEB_API_KEY'),
        'auth_domain'      => env('FIREBASE_WEB_AUTH_DOMAIN'),
        'project_id'       => env('FIREBASE_WEB_PROJECT_ID'),
        'sender_id'        => env('FIREBASE_WEB_SENDER_ID'),
        'app_id'           => env('FIREBASE_WEB_APP_ID'),
        'vapid_public_key' => env('FIREBASE_WEB_VAPID_KEY'),
    ],



];
