# Laravel MNO

[![Tests](https://img.shields.io/github/actions/workflow/status/MoonlyDays/laravel-mno/tests.yml?branch=main&label=tests&style=flat-square&logo=github)](https://github.com/MoonlyDays/laravel-mno/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/moonlydays/laravel-mno.svg?style=flat-square&logo=packagist&logoColor=white)](https://packagist.org/packages/moonlydays/laravel-mno)
[![Total Downloads](https://img.shields.io/packagist/dt/moonlydays/laravel-mno.svg?style=flat-square&logo=packagist&logoColor=white)](https://packagist.org/packages/moonlydays/laravel-mno)
[![PHP Version](https://img.shields.io/packagist/php-v/moonlydays/laravel-mno.svg?style=flat-square&logo=php&logoColor=white)](https://packagist.org/packages/moonlydays/laravel-mno)
[![Laravel](https://img.shields.io/badge/Laravel-11%20%7C%2012%20%7C%2013-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![License](https://img.shields.io/packagist/l/moonlydays/laravel-mno.svg?style=flat-square)](LICENSE)

Laravel package for validating, normalizing, and working with MSISDN phone numbers tied to a single Mobile Network
Operator. A wrapper around [`giggsey/libphonenumber-for-php`](https://github.com/giggsey/libphonenumber-for-php) with
integration into Laravel's validation system, Eloquent casts, Faker, schema builder, and facades.

Released under the [MIT License](LICENSE).

## Requirements

- PHP 8.2+
- Laravel 11, 12, or 13

## Installation

```bash
composer require moonlydays/laravel-mno
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="mno-config"
```

## Configuration

Set up the environment variables:

```env
MNO_NAME=MTS
MNO_COUNTRY=RU
MNO_NETWORK_CODES=910,911,912
MNO_MIN_LENGTH=10
MNO_MAX_LENGTH=10
```

| Variable            | Description                                                                       |
|---------------------|-----------------------------------------------------------------------------------|
| `MNO_NAME`          | Name of the mobile network operator                                               |
| `MNO_COUNTRY`       | ISO 3166-1 alpha-2 country code (e.g., `RU`, `TZ`)                                |
| `MNO_NETWORK_CODES` | Comma-separated National Destination Code (NDC) prefixes for the operator         |
| `MNO_MIN_LENGTH`    | Minimum national number length (optional — inferred from libphonenumber metadata) |
| `MNO_MAX_LENGTH`    | Maximum national number length (optional — inferred from libphonenumber metadata) |

When `MNO_MIN_LENGTH` / `MNO_MAX_LENGTH` are unset, the package infers the length from libphonenumber
metadata for the configured country, walking the `number_types` priority list in `config/mno.php`
(defaults to `Mobile`, then `General`).

## Usage

### Creating a PhoneNumber

```php
use MoonlyDays\MNO\Values\PhoneNumber;

// Parse, throwing InvalidPhoneNumberException on failure
$phone = PhoneNumber::from('+79101234567');
$phone = PhoneNumber::from('9101234567', 'RU');
$phone = PhoneNumber::from(79101234567, 'RU'); // integers are accepted

// Safe parse, returning null on failure
$phone = PhoneNumber::tryFrom('invalid'); // null

// Global helper
$phone = phoneNumber('+79101234567');
```

`PhoneNumber` is a lightweight value object wrapping libphonenumber's native `PhoneNumber`. It implements
`Stringable` (casting to string produces the E.164 form), `JsonSerializable` (serializes as E.164), and
`Castable` (can be used directly as an Eloquent cast). It also uses Laravel's `Macroable` and `Tappable` traits.

### Formatting

```php
$phone = PhoneNumber::from('+79101234567');

$phone->e164();          // "+79101234567"
$phone->national();      // "8 (910) 123-45-67"
$phone->international(); // "+7 910 123-45-67"
$phone->toInteger();     // 79101234567  (E.164 digits without the leading plus)
(string) $phone;         // "+79101234567"
```

### Retrieving number components

```php
$phone = PhoneNumber::from('+79101234567');

$phone->countryCode();      // 7
$phone->countryIso();       // "RU"
$phone->nationalNumber();   // "9101234567"
$phone->networkCode();      // "910"
$phone->subscriberNumber(); // "1234567"
$phone->toPhoneNumber();    // underlying libphonenumber\PhoneNumber
```

Two `PhoneNumber` instances can be compared via `$a->equals($b)` (equality is based on the E.164 form).

### Timezones

```php
$phone = PhoneNumber::from('+79101234567');

$phone->timezone();  // "Europe/Moscow" — primary IANA identifier, or null if unknown
$phone->timezones(); // ["Europe/Moscow", ...] — all IANA identifiers for the number
```

### Validation

```php
use Illuminate\Validation\Rule;

// Use the Rule::phoneNumber() macro — picks up defaults from config
$request->validate([
    'phone' => ['required', Rule::phoneNumber()],
]);
```

```php
use MoonlyDays\MNO\Rules\PhoneNumberRule;

// Customize the rule fluently
$request->validate([
    'phone' => [
        'required',
        (new PhoneNumberRule())
            ->country('RU', 'BY', 'KZ')
            ->networkCodes('910', '911')
            ->minLength(10)
            ->maxLength(10),
    ],
]);
```

Validation failures translate the following keys, which you can publish or override in your own language files:

- `validation.msisdn.invalid`
- `validation.msisdn.country`
- `validation.msisdn.min_length` (receives `:min`)
- `validation.msisdn.max_length` (receives `:max`)
- `validation.msisdn.network_code`

#### Overriding the default rule

`PhoneNumberRule::defaults()` lets you swap in a custom resolver used by `Rule::phoneNumber()`:

```php
use MoonlyDays\MNO\Rules\PhoneNumberRule;

PhoneNumberRule::defaults(fn () => (new PhoneNumberRule())
    ->country('RU')
    ->minLength(10)
    ->maxLength(10));
```

### Request macro

The service provider registers a `phoneNumber` macro on `Illuminate\Http\Request`:

```php
$phone = $request->phoneNumber('phone');           // PhoneNumber or null
$phone = $request->phoneNumber('phone', $default); // with fallback (value or closure)
```

### Eloquent cast

Since `PhoneNumber` implements `Castable`, you can use it directly as an Eloquent cast. `PhoneNumberCast` is
also available if you prefer to be explicit:

```php
use Illuminate\Database\Eloquent\Model;
use MoonlyDays\MNO\Values\PhoneNumber;

class User extends Model
{
    protected $casts = [
        'phone' => PhoneNumber::class, // or PhoneNumberCast::class
    ];
}

$user->phone = '+79101234567';
$user->save(); // Stored as unsigned bigInteger: 79101234567

$user->phone instanceof PhoneNumber; // true
$user->phone->national();            // "8 (910) 123-45-67"
```

The cast accepts a string, integer, or `PhoneNumber` instance when setting, and persists the E.164 digits
as an unsigned integer (the leading `+` is stripped). When reading back, the configured `MNO_COUNTRY` is
used as the default region for parsing, so make sure it is set.

### Schema macro

A `phoneNumber` macro on `Illuminate\Database\Schema\Blueprint` defines an `unsigned bigInteger` column that
matches the storage format used by the cast:

```php
use Illuminate\Database\Schema\Blueprint;

Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->phoneNumber('phone')->unique();
    $table->timestamps();
});
```

### Faker provider

When the Faker generator resolves from the container, the package registers a provider for generating valid
numbers within the configured MNO (country, network codes, and length constraints):

```php
$faker = fake();

$faker->phoneNumberObject();       // PhoneNumber
$faker->phoneNumber();             // "+79101234567" (E.164)
$faker->e164PhoneNumber();         // "+79101234567"
$faker->nationalPhoneNumber();     // "8 (910) 123-45-67"
$faker->internationalPhoneNumber();// "+7 910 123-45-67"
```

### JSON resource

`PhoneNumberFormatResource` exposes the operator's format metadata as a JSON resource, for API responses
that need to tell clients about expected number shape:

```php
use MoonlyDays\MNO\Resources\PhoneNumberFormatResource;

return [
    'format' => PhoneNumberFormatResource::make(),
];
// {
//   "countryCode": 7,
//   "country": "RU",
//   "minLength": 10,
//   "maxLength": 10,
//   "networkCodes": ["910", "911", "912"]
// }
```

### MNO facade

```php
use MoonlyDays\MNO\Facades\MNO;

MNO::countryIsoCode(); // "RU"
MNO::country();        // Country instance for "RU"
MNO::countryCode();    // 7
MNO::carrierName();    // "MTS"
MNO::carrier();        // Carrier instance for the configured MNO
MNO::networkCodes();   // ["910", "911", "912"]
MNO::minLength();      // 10
MNO::maxLength();      // 10
MNO::exampleNumber();  // PhoneNumber|null
MNO::numberTypes();    // array<NumberType>
```

The facade resolves the `MnoService` singleton, which is also bound to the container alias `mno` and can be
injected directly.

### Country and Carrier value objects

```php
use MoonlyDays\MNO\Values\Country;
use MoonlyDays\MNO\Values\Carrier;

// Country — wraps an ISO 3166-1 alpha-2 code
$country = Country::from('RU');       // throws InvalidCountryException on unknown code
$country = Country::tryFrom('RU');    // returns null on failure

$country->isoCode();                // "RU"
$country->countryCode();            // 7
$country->name();                   // "Russia"
$country->exampleNumber();          // PhoneNumber|null
$country->isMobileNumberPortable(); // bool
$country->carriers();               // array<string, Carrier> — all carriers with allocations

// Carrier — a carrier within a country
$carrier = Carrier::from('RU', 'MTS');    // throws InvalidCarrierException on miss
$carrier = Carrier::tryFrom('RU', 'MTS'); // returns null on failure

$carrier->name();             // "MTS"
$carrier->country();          // Country instance
$carrier->networkCodes();     // ["910", "911", "912"] — NDC prefixes
$carrier->prefixes();         // ["7910", "7911", "7912"] — with country code
$carrier->matches($phone);    // true if the phone number belongs to this carrier
$carrier->owns('910');        // true if the carrier owns this NDC
```

### Artisan command

Inspect the configured MNO, a country, or a specific carrier:

```bash
php artisan mno:show              # show configured operator details
php artisan mno:show RU           # show country info with carrier list
php artisan mno:show RU MTS       # show carrier details with network codes
```

### Extending via macros

`PhoneNumber` uses the `Macroable` trait, so you can add project-specific helpers:

```php
use MoonlyDays\MNO\Values\PhoneNumber;

PhoneNumber::macro('isRussian', function (): bool {
    /** @var PhoneNumber $this */
    return $this->countryIso() === 'RU';
});

PhoneNumber::from('+79101234567')->isRussian(); // true
```
