<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Invoice Payment Details
    |--------------------------------------------------------------------------
    |
    | These settings control the payment information displayed on invoices.
    |
    */

    'payment_full_name' => env('INVOICE_PAYMENT_FULL_NAME', 'Contact us for payment details'),

    'payment_account' => env('INVOICE_PAYMENT_ACCOUNT', 'Contact us for payment details'),

    'payment_address' => env('INVOICE_PAYMENT_ADDRESS', ''),

    'payment_surcharge' => env('INVOICE_PAYMENT_SURCHARGE', ''),

    /*
    |--------------------------------------------------------------------------
    | Invoice Number Format
    |--------------------------------------------------------------------------
    |
    | The format for generating invoice numbers. Currently uses YYYYMM0001
    |
    */

    'number_format' => env('INVOICE_NUMBER_FORMAT', 'Ym'),

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The default currency to use when creating new invoices.
    |
    */

    'default_currency' => env('INVOICE_DEFAULT_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Default Payment Terms (Days)
    |--------------------------------------------------------------------------
    |
    | The default number of days until payment is due.
    |
    */

    'default_payment_terms' => env('INVOICE_DEFAULT_PAYMENT_TERMS', 30),

];
