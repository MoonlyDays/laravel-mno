---
name: laravel-mno-development
description: >-
  Use this skill when working with phone number fields, PhoneNumberRule validation,
  PhoneNumberCast, the MNO facade, or PhoneNumberFormatResource. Contains full API
  signatures, code examples, and configuration reference.
---

# moonlydays/laravel-mno

Laravel package for validating, normalizing, and working with phone numbers for Mobile Network Operators (MNOs). Wraps `giggsey/libphonenumber-for-php` and integrates with Laravel validation, Eloquent casting, and facades. Requires PHP 8.2+ and Laravel 11/12/13.

Namespace: `MoonlyDays\MNO`

## PhoneNumber Value Object

The core of the package. An immutable, immediately-validated phone number representation.

**Creating instances:**

- `PhoneNumber::from(string $number, ?string $region = null): PhoneNumber` — parse and validate. Throws `InvalidPhoneNumberException` on failure. Use when input is trusted or you want to fail loudly.
- `PhoneNumber::tryFrom(string $number, ?string $region = null): ?PhoneNumber` — returns `null` on failure. Use for user input.
- `phoneNumber(string $number, ?string $region = null): PhoneNumber` — global helper, equivalent to `PhoneNumber::from()`.

When `$region` is omitted, the configured country (`config('mno.country')`, via `MNO::countryIsoCode()`) is used as the default parse region.

```php
use MoonlyDays\MNO\Values\PhoneNumber;

// Throws on invalid input
$phone = PhoneNumber::from('+255712345678');
$phone = PhoneNumber::from('0712345678', 'TZ');

// Returns null on invalid input — use for user input
$phone = PhoneNumber::tryFrom($request->input('phone'));

// Global helper
$phone = phoneNumber('+255712345678');
```

**Formatting outputs:**

- `e164(): string` — E.164 format: `+255712345678`
- `national(): string` — national format: `0712 345 678`
- `international(): string` — international format: `+255 712 345 678`
- `__toString()` returns E.164.

**Component extraction:**

- `countryCode(): int` — calling code (e.g., `255`)
- `countryIso(): string` — ISO 3166-1 alpha-2 code (e.g., `TZ`)
- `nationalNumber(): string` — digits without country code (e.g., `712345678`)
- `networkCode(): string` — NDC prefix using libphonenumber's destination code length (e.g., `712`)
- `subscriberNumber(): string` — digits after NDC (e.g., `345678`)

**Other methods:**

- `toPhoneNumber(): libphonenumber\PhoneNumber` — underlying libphonenumber instance
- `equals(PhoneNumber $other): bool` — comparison by E.164

`PhoneNumber` uses `Macroable` and `Tappable` traits.

## MNO Facade and MnoService

`MnoService` is a singleton registered by `MnoServiceProvider` with the container alias `mno`. Access it via the `MNO` facade.

```php
use MoonlyDays\MNO\Facades\MNO;

MNO::countryIsoCode(); // "TZ" — configured ISO country code
MNO::country();        // Country value object for configured ISO code
MNO::countryCode();    // 255 — calling code for configured country
MNO::carrierName();    // "Vodacom" — configured operator name
MNO::carrier();        // Carrier value object for configured MNO
MNO::networkCodes();   // ["71", "74", "75"] — configured NDC prefixes
MNO::minLength();      // 9 — minimum national number length
MNO::maxLength();      // 9 — maximum national number length
MNO::exampleNumber();  // PhoneNumber|null — example number for country
MNO::numberTypes();    // [NumberType::Mobile, NumberType::General]
```

**Smart length inference:** When `mno.validation.min_length` or `mno.validation.max_length` are not explicitly configured, `MnoService` infers them from libphonenumber metadata. It iterates through configured `number_types` (default: Mobile, then General) and returns the length from the first type with a single unambiguous value. If ambiguous, it throws `PhoneNumberLengthException`. If `min_length` is not set, it falls back to `max_length`. The inferred length is cached for the lifetime of the singleton.

## Validation Rule

`PhoneNumberRule` implements Laravel's `ValidationRule` interface with a fluent API.

```php
use Illuminate\Validation\Rule;
use MoonlyDays\MNO\Rules\PhoneNumberRule;

// Default rule — pre-configured from config (country, networkCodes, min/maxLength)
$request->validate([
    'phone' => ['required', Rule::phoneNumber()],
]);

// Custom rule with fluent API
$request->validate([
    'phone' => [
        'required',
        (new PhoneNumberRule())
            ->country('TZ', 'KE', 'UG')
            ->networkCodes('71', '74', '75')
            ->minLength(9)
            ->maxLength(9),
    ],
]);
```

**Fluent methods:**

- `country(array|string $country, string ...$countries): static` — restrict to ISO country codes
- `networkCodes(array|string $code, string ...$codes): static` — restrict to NDC prefixes
- `minLength(int $length): static` — minimum national number length
- `maxLength(int $length): static` — maximum national number length

**`PhoneNumberRule::default()`** creates a rule pre-configured from the `mno.*` config (country, networkCodes, minLength, maxLength).

**`PhoneNumberRule::defaults(?callable $resolver)`** sets a custom resolver for `default()`:

```php
PhoneNumberRule::defaults(fn () => (new PhoneNumberRule())
    ->country('US', 'CA')
    ->minLength(10)
    ->maxLength(10)
);
```

**Validation order:** parse as phone number, check country, check min length, check max length, check network code prefix.

**Validation error keys:** `validation.msisdn.invalid`, `validation.msisdn.country`, `validation.msisdn.min_length`, `validation.msisdn.max_length`, `validation.msisdn.network_code`.

## Eloquent Cast

`PhoneNumberCast` stores phone numbers as E.164 in the database and hydrates them as `PhoneNumber` instances.

```php
use MoonlyDays\MNO\Casts\PhoneNumberCast;
use MoonlyDays\MNO\Values\PhoneNumber;

class User extends Model
{
    protected $casts = [
        'phone' => PhoneNumberCast::class,
    ];
}

// Setting — accepts string or PhoneNumber, stores as E.164
$user->phone = '0712345678';
$user->phone = PhoneNumber::from('+255712345678');
$user->save(); // Stored as "+255712345678"

// Getting — returns PhoneNumber instance (or null)
$user->phone->national();    // "0712 345 678"
$user->phone->countryIso();  // "TZ"
```

Always stores as E.164. Returns `null` when the database value is `null`.

## PhoneNumberFormatResource

JSON API resource for exposing operator format configuration to frontends or external APIs:

```php
use MoonlyDays\MNO\Resources\PhoneNumberFormatResource;

// In a controller
return PhoneNumberFormatResource::make();

// Output:
// {
//   "countryCode": 255,
//   "country": "TZ",
//   "minLength": 9,
//   "maxLength": 9,
//   "networkCodes": ["71", "74", "75"]
// }
```

The resource is constructed with an injected `MnoService` — `::make()` resolves it from the container.

## NumberType Enum

`MoonlyDays\MNO\Enums\NumberType` maps phone number types to libphonenumber metadata descriptors. Used by `MnoService` for length inference.

Cases: `Mobile`, `FixedLine`, `General`, `TollFree`, `PremiumRate`, `SharedCost`, `Voip`, `PersonalNumber`, `Pager`, `Uan`, `Voicemail`.

Key method: `descriptionFrom(PhoneMetadata $metadata): ?PhoneNumberDesc`.

## Configuration

Config file: `config/operator.php`. The service provider names the package `mno`, so keys are accessed under the `mno.*` config namespace (e.g., `config('mno.country')`).

| Config Key | Env Variable | Type | Default | Purpose |
|---|---|---|---|---|
| `name` | `MNO_NAME` | `string` | `""` | Operator name |
| `country` | `MNO_COUNTRY` | `string` | `""` | ISO 3166-1 alpha-2 country code, used as default parse region |
| `network_codes` | `MNO_NETWORK_CODES` | `array` | `[]` | Comma-separated NDC prefixes |
| `validation.min_length` | `MNO_PHONE_MIN_LENGTH` | `int\|null` | `null` | Min national number length (inferred if null) |
| `validation.max_length` | `MNO_PHONE_MAX_LENGTH` | `int\|null` | `null` | Max national number length (inferred if null) |
| `validation.number_types` | — | `array` | `[Mobile, General]` | NumberType priority for length inference |

## Exceptions

- `InvalidPhoneNumberException` (extends `InvalidArgumentException`) — thrown by `PhoneNumber::from()` when parsing or validation fails. Factory: `InvalidPhoneNumberException::forNumber(string $number, ?Throwable $previous = null)`.
- `PhoneNumberLengthException` (extends `RuntimeException`) — thrown by `MnoService::maxLength()` during length inference. Factories: `ambiguous(string $country, array $lengths)`, `missingCountry()`, `missingMetadata(string $country)`.

## Important Patterns

- Always use `PhoneNumber::tryFrom()` for user-provided input, `PhoneNumber::from()` when the source is trusted.
- Always store phone numbers in E.164 format in the database. Use `PhoneNumberCast` for automatic handling.
- Length inference requires a configured country (`mno.country`). Without it, `PhoneNumberLengthException` is thrown.
- `Rule::phoneNumber()` is a macro registered by the service provider — equivalent to `PhoneNumberRule::default()`.
- `PhoneNumber` instances are immutable. There is no way to modify a parsed number; create a new one instead.
