<?php

declare(strict_types=1);

use MoonlyDays\MNO\Enums\NumberType;

return [

    /*
    |--------------------------------------------------------------------------
    | Operator
    |--------------------------------------------------------------------------
    |
    | The name of the mobile network operator this deployment is associated
    | with. This is used for default validation and operator verification.
    |
    */

    'name' => env('MNO_NAME', ''),

    /*
    |--------------------------------------------------------------------------
    | Country
    |--------------------------------------------------------------------------
    |
    | The ISO 3166-1 alpha-2 country code for the operator's primary market.
    | Used as the default region when parsing MSISDN numbers.
    |
    */

    'country' => env('MNO_COUNTRY', ''),

    /*
    |--------------------------------------------------------------------------
    | Network Codes
    |--------------------------------------------------------------------------
    |
    | National Destination Codes (NDC) or number prefixes associated with
    | the configured operator. These are used during validation to verify
    | that a given MSISDN belongs to the expected operator network.
    |
    | Example: ['71', '74', '75']
    |
    */

    'network_codes' => array_filter(explode(',', (string) env('MNO_NETWORK_CODES', ''))),

    /*
    |--------------------------------------------------------------------------
    | Carrier Locale
    |--------------------------------------------------------------------------
    |
    | The locale used when retrieving carrier names from libphonenumber's
    | carrier mapping database. Uses an IETF BCP 47 language tag format.
    |
    */

    'carrier_locale' => env('MNO_CARRIER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    |
    | Default constraints applied when using PhoneNumberRule::default(). When
    | set to null, the length is inferred from libphonenumber metadata
    | for the configured country. The number_types array controls which
    | description types are checked and in what order. The first type
    | that yields a valid, unambiguous length wins. If no type resolves
    | to a single length, an exception is thrown.
    |
    */

    'validation' => [
        'min_length' => env('MNO_PHONE_MIN_LENGTH'),
        'max_length' => env('MNO_PHONE_MAX_LENGTH'),
        'number_types' => [
            NumberType::Mobile,
            NumberType::General,
        ],
    ],

];
