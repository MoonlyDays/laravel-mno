# Laravel MNO

[![Tests](https://img.shields.io/github/actions/workflow/status/MoonlyDays/laravel-mno/tests.yml?branch=main&label=tests&style=flat-square&logo=github)](https://github.com/MoonlyDays/laravel-mno/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/moonlydays/laravel-mno.svg?style=flat-square&logo=packagist&logoColor=white)](https://packagist.org/packages/moonlydays/laravel-mno)
[![Total Downloads](https://img.shields.io/packagist/dt/moonlydays/laravel-mno.svg?style=flat-square&logo=packagist&logoColor=white)](https://packagist.org/packages/moonlydays/laravel-mno)
[![PHP Version](https://img.shields.io/packagist/php-v/moonlydays/laravel-mno.svg?style=flat-square&logo=php&logoColor=white)](https://packagist.org/packages/moonlydays/laravel-mno)
[![Laravel](https://img.shields.io/badge/Laravel-11%20%7C%2012%20%7C%2013-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![License](https://img.shields.io/packagist/l/moonlydays/laravel-mno.svg?style=flat-square)](LICENSE)

Laravel package for validating, normalizing, and working with MSISDN phone numbers tied to a single Mobile Network
Operator. A wrapper around [`giggsey/libphonenumber-for-php`](https://github.com/giggsey/libphonenumber-for-php) with
integration into Laravel's validation system, Eloquent casts, and facades.

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
MNO_CARRIER_LOCALE=ru_RU
MNO_PHONE_MIN_LENGTH=10
MNO_PHONE_MAX_LENGTH=10
```

| Variable                | Description                                                                        |
|-------------------------|------------------------------------------------------------------------------------|
| `MNO_NAME`              | Name of the mobile network operator                                                |
| `MNO_COUNTRY`           | ISO 3166-1 alpha-2 country code (e.g., `RU`, `TZ`)                                 |
| `MNO_NETWORK_CODES`     | Comma-separated National Destination Code (NDC) prefixes for the operator          |
| `MNO_CARRIER_LOCALE`    | Locale for libphonenumber carrier name lookups (IETF BCP 47)                       |
| `MNO_PHONE_MIN_LENGTH`  | Minimum national number length (optional — inferred from libphonenumber metadata)  |
| `MNO_PHONE_MAX_LENGTH`  | Maximum national number length (optional — inferred from libphonenumber metadata)  |

When `MNO_PHONE_MIN_LENGTH` / `MNO_PHONE_MAX_LENGTH` are unset, the package infers the length from libphonenumber
metadata for the configured country, walking the `number_types` priority list in `config/mno.php`
(defaults to `Mobile`, then `General`).

## Usage

### Creating a PhoneNumber

```php
use MoonlyDays\MNO\PhoneNumber;

// Parse, throwing InvalidPhoneNumberException on failure
$phone = PhoneNumber::from('+79101234567');
$phone = PhoneNumber::from('9101234567', 'RU');

// Safe parse, returning null on failure
$phone = PhoneNumber::tryFrom('invalid'); // null

// Global helper
$phone = phoneNumber('+79101234567');
```

`PhoneNumber` is a lightweight value object wrapping libphonenumber's native `PhoneNumber`. It implements
`Stringable` (casting to string produces the E.164 form) and uses Laravel's `Macroable` and `Tappable` traits.

### Formatting

```php
$phone = PhoneNumber::from('+79101234567');

$phone->e164();          // "+79101234567"
$phone->national();      // "8 (910) 123-45-67"
$phone->international(); // "+7 910 123-45-67"
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

### Eloquent cast

```php
use Illuminate\Database\Eloquent\Model;
use MoonlyDays\MNO\Casts\PhoneNumberCast;
use MoonlyDays\MNO\PhoneNumber;

class User extends Model
{
    protected $casts = [
        'phone' => PhoneNumberCast::class,
    ];
}

$user->phone = '+79101234567';
$user->save(); // Stored as E.164: "+79101234567"

$user->phone instanceof PhoneNumber; // true
$user->phone->national();            // "8 (910) 123-45-67"
```

The cast accepts either a string or a `PhoneNumber` instance when setting, and always persists the E.164 form.

### MNO facade

```php
use MoonlyDays\MNO\Facades\MNO;

MNO::country();       // "RU"
MNO::countryCode();   // 7
MNO::name();          // "MTS"
MNO::networkCodes();  // ["910", "911", "912"]
MNO::carrierLocale(); // "ru_RU"
MNO::minLength();     // 10
MNO::maxLength();     // 10
MNO::exampleNumber(); // PhoneNumber|null
MNO::numberTypes();   // array<NumberType>
```

The facade resolves the `MnoService` singleton, which is also bound to the container alias `mno` and can be
injected directly.

### Extending via macros

`PhoneNumber` uses the `Macroable` trait, so you can add project-specific helpers:

```php
use MoonlyDays\MNO\PhoneNumber;

PhoneNumber::macro('isRussian', function (): bool {
    /** @var PhoneNumber $this */
    return $this->countryIso() === 'RU';
});

PhoneNumber::from('+79101234567')->isRussian(); // true
```

## Testing

```bash
composer test       # Pest
composer analyse    # PHPStan (level 5)
composer lint       # Laravel Pint
```
