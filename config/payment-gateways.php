<?php

/**
 * Payment Gateway Configuration
 * ==============================
 * Each gateway can have its own configuration block here.
 * Values are driven by environment variables so secrets never live in source.
 *
 * To force a specific gateway outcome in testing / development set:
 *   CREDIT_CARD_FORCE_STATUS=successful
 *   PAYPAL_FORCE_STATUS=failed
 *   STRIPE_FORCE_STATUS=successful
 *
 * Valid values: successful | failed | pending | null (= let gateway decide)
 */
return [

    'credit_card' => [
        'api_key' => env('CREDIT_CARD_API_KEY'),
        'api_secret' => env('CREDIT_CARD_API_SECRET'),
        'force_status' => env('CREDIT_CARD_FORCE_STATUS'),
    ],

    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'sandbox' => env('PAYPAL_SANDBOX', true),
        'force_status' => env('PAYPAL_FORCE_STATUS'),
    ],

    'stripe' => [
        'api_key' => env('STRIPE_API_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'force_status' => env('STRIPE_FORCE_STATUS'),
    ],

];
