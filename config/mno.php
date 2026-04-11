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
    | National Number Length
    |--------------------------------------------------------------------------
    |
    | Minimum and maximum national (post country-code) digit counts used by
    | PhoneNumberRule::default() and MNO::minLength() / MNO::maxLength().
    |
    | When left null, the bounds are inferred from libphonenumber metadata
    | for the configured country via Country::possiblePhoneNumberLengths().
    | Set these explicitly to pin the bounds and skip metadata inference,
    | or to validate against a tighter range than the country advertises.
    |
    */

    'min_length' => env('MNO_MIN_LENGTH'),

    'max_length' => env('MNO_MAX_LENGTH'),

    /*
    |--------------------------------------------------------------------------
    | Number Types
    |--------------------------------------------------------------------------
    |
    | Priority-ordered list of libphonenumber NumberType descriptors consulted
    | when inferring possible national-number lengths for the configured
    | country. The list is walked in order and the first descriptor whose
    | metadata exposes usable possible lengths wins — remaining entries act as
    | fallbacks for regions where the preferred type has no metadata.
    |
    | The default prefers Mobile (since this package targets Mobile Network
    | Operators) and falls back to General for regions where libphonenumber
    | does not ship a dedicated mobile descriptor.
    |
    */

    'number_types' => [
        NumberType::Mobile,
        NumberType::General,
    ],
];
