---
name: laravel-mno-development
description: >-
  Use this skill when working with phone number fields, PhoneNumberRule validation,
  PhoneNumberCast, the MNO facade, or PhoneNumberFormatResource. Contains full API
  signatures, code examples, and configuration reference.
---

# moonlydays/laravel-mno

Laravel package for validating, normalizing, and working with phone numbers for Mobile Network Operators (MNOs). Wraps
`giggsey/libphonenumber-for-php` and integrates with Laravel validation, Eloquent casting, and facades. Requires PHP
8.2+ and Laravel 11/12/13.

Namespace: `MoonlyDays\MNO`

## PhoneNumber Value Object

The core of the package. An immutable, immediately-validated phone number representation implementing `Castable`,
`JsonSerializable`, and `Stringable`.

**Creating instances:**

- `PhoneNumber::from(string $number, ?string $region = null): PhoneNumber` — parse and validate. Throws
  `InvalidPhoneNumberException` on failure. Use when input is trusted or you want to fail loudly.
- `PhoneNumber::tryFrom(string $number, ?string $region = null): ?PhoneNumber` — returns `null` on failure. Use for user
  input.
- `phoneNumber(string $number, ?string $region = null): PhoneNumber` — global helper, equivalent to
  `PhoneNumber::from()`.
- `PhoneNumber::castUsing(array $arguments): string` — returns `PhoneNumberCast::class`, enabling direct use as an
  Eloquent cast via the `Castable` interface.

When `$region` is omitted, the configured country (`config('mno.country')`, via `MNO::countryIsoCode()`) is used as the
default parse region.

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
- `jsonSerialize(): string` — returns E.164, enabling `json_encode()` support

`PhoneNumber` uses `Macroable` and `Tappable` traits.

## Country Value Object

ISO 3166-1 alpha-2 country wrapper with carrier data and phone number metadata.

**Creating instances:**

- `Country::from(string $isoCode): Country` — throws `InvalidCountryException` for unknown codes.
- `Country::tryFrom(string $isoCode): ?Country` — returns `null` on failure.

**Accessors:**

- `isoCode(): string` — ISO 3166-1 alpha-2 code (e.g., `"TZ"`)
- `countryCode(): int` — E.164 calling code (e.g., `255`), 0 if unknown
- `name(string $locale = 'en'): string` — localized display name from CLDR
- `__toString(): string` — returns ISO code

**Carrier management:**

- `carriers(): array<string, Carrier>` — all carriers with allocations (lazy-loaded & memoized)
- `carrier(string $name): Carrier` — get carrier by name (case-insensitive, throws `InvalidCarrierException`)
- `tryCarrier(string $name): ?Carrier` — safe carrier lookup
- `hasCarrier(string $name): bool` — check if carrier exists
- `supportsCarrierData(string $locale = 'en_US'): bool` — check if libphonenumber has carrier data

**Phone number metadata:**

- `exampleNumber(): ?PhoneNumber` — example number from libphonenumber
- `minPhoneNumberLength(array<NumberType> $numberTypes): int` — minimum national length
- `maxPhoneNumberLength(array<NumberType> $numberTypes): int` — maximum national length
- `possiblePhoneNumberLengths(array<NumberType> $numberTypes): array<int>` — all possible lengths (throws
  `PhoneNumberLengthException`)
- `isMobileNumberPortable(): bool` — does region support MNP?
- `equals(self $other): bool` — compare Country instances

`Country` uses `Macroable` and `Tappable` traits.

## Carrier Value Object

Carrier within a country, holding NDC network codes.

**Creating instances:**

- `Carrier::from(Country|string $country, string $name): Carrier` — throws `InvalidCarrierException` on miss.
- `Carrier::tryFrom(Country|string $country, string $name): ?Carrier` — returns `null` on failure.

**Accessors:**

- `country(): Country` — parent Country instance
- `name(): string` — carrier display name
- `networkCodes(): array<int, string>` — NDC codes (without country code)
- `prefixes(): array<int, string>` — full prefixes (country code + NDC)
- `networkCodeCount(): int` — count of distinct prefix blocks
- `__toString(): string` — returns carrier name

**Matching:**

- `matches(PhoneNumber $number): bool` — check if phone number belongs to carrier
- `owns(string $networkCode): bool` — check if carrier owns given NDC
- `equals(self $other): bool` — compare carriers (by country + name)

`Carrier` uses `Macroable` and `Tappable` traits.

## Request Macro

The service provider registers a `phoneNumber` macro on `Illuminate\Http\Request`:

```php
$phone = $request->phoneNumber('phone');          // PhoneNumber or null
$phone = $request->phoneNumber('phone', $default); // with fallback
```

Extracts the value from the request and parses it via `PhoneNumber::tryFrom()`.

## MNO Facade and MnoService

`MnoService` is a singleton registered by `MnoServiceProvider` with the container alias `mno`. Access it via the `MNO`
facade.

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

**Smart length inference:** When `mno.min_length` or `mno.max_length` are not explicitly configured, `MnoService`
delegates to `Country::minPhoneNumberLength()` / `Country::maxPhoneNumberLength()`, which call
`Country::possiblePhoneNumberLengths()`. This method iterates through the configured `number_types` (default: Mobile,
then General) and returns the lengths from the first type whose libphonenumber metadata exposes usable possible lengths.
If no metadata is available or no type exposes usable lengths, `PhoneNumberLengthException` is thrown.

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

**`PhoneNumberRule::default()`** creates a rule pre-configured from the `mno.*` config (country, networkCodes,
minLength, maxLength).

**`PhoneNumberRule::defaults(?callable $resolver)`** sets a custom resolver for `default()`:

```php
PhoneNumberRule::defaults(fn () => (new PhoneNumberRule())
    ->country('US', 'CA')
    ->minLength(10)
    ->maxLength(10)
);
```

**Validation order:** parse as phone number, check country, check min length, check max length, check network code
prefix.

**Validation error keys:** `validation.msisdn.invalid`, `validation.msisdn.country`, `validation.msisdn.min_length`,
`validation.msisdn.max_length`, `validation.msisdn.network_code`.

## Eloquent Cast

`PhoneNumberCast` stores phone numbers as E.164 in the database and hydrates them as `PhoneNumber` instances. Since
`PhoneNumber` implements `Castable`, you can use either `PhoneNumberCast::class` or `PhoneNumber::class` directly:

```php
use MoonlyDays\MNO\Values\PhoneNumber;

class User extends Model
{
    protected $casts = [
        'phone' => PhoneNumber::class, // or PhoneNumberCast::class
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

## Artisan Command

`php artisan mno:show {country?} {carrier?}` — inspects the configured MNO, a country, or a specific carrier.

- No arguments: shows configured operator (carrier) details including network codes with formatted prefixes.
- Country only (e.g., `mno:show RU`): shows country info — ISO code, name, calling code, MNP support, example number,
  min/max lengths, and a list of all carriers with their allocation counts.
- Country + carrier (e.g., `mno:show RU MTS`): shows carrier details with all network codes rendered as
  `+<cc> <ndc> XXX…` placeholders.

## NumberType Enum

`MoonlyDays\MNO\Enums\NumberType` maps phone number types to libphonenumber metadata descriptors. Used by `MnoService`
for length inference.

Cases: `Mobile`, `FixedLine`, `General`, `TollFree`, `PremiumRate`, `SharedCost`, `Voip`, `PersonalNumber`, `Pager`,
`Uan`, `Voicemail`.

Key method: `descriptionFrom(PhoneMetadata $metadata): ?PhoneNumberDesc`.

## Configuration

Config file: `config/mno.php`. The service provider names the package `mno`, so keys are accessed under the `mno.*`
config namespace (e.g., `config('mno.country')`).

| Config Key      | Env Variable        | Type        | Default             | Purpose                                                       |
|-----------------|---------------------|-------------|---------------------|---------------------------------------------------------------|
| `name`          | `MNO_NAME`          | `string`    | `""`                | Operator name                                                 |
| `country`       | `MNO_COUNTRY`       | `string`    | `""`                | ISO 3166-1 alpha-2 country code, used as default parse region |
| `network_codes` | `MNO_NETWORK_CODES` | `array`     | `[]`                | Comma-separated NDC prefixes                                  |
| `min_length`    | `MNO_MIN_LENGTH`    | `int\|null` | `null`              | Min national number length (inferred if null)                 |
| `max_length`    | `MNO_MAX_LENGTH`    | `int\|null` | `null`              | Max national number length (inferred if null)                 |
| `number_types`  | —                   | `array`     | `[Mobile, General]` | NumberType priority for length inference                      |

## Exceptions

- `InvalidPhoneNumberException` (extends `InvalidArgumentException`) — thrown by `PhoneNumber::from()` when parsing or
  validation fails. Factory: `InvalidPhoneNumberException::forNumber(string $number, ?Throwable $previous = null)`.
- `InvalidCountryException` (extends `InvalidArgumentException`) — thrown by `Country::from()` for unknown ISO codes.
  Factory: `InvalidCountryException::unknownIsoCode(string $isoCode)`.
- `InvalidCarrierException` (extends `InvalidArgumentException`) — thrown by `Carrier::from()` on carrier miss.
  Factories: `missingArguments(?Throwable $previous = null)`, `notFoundIn(Country $country, string $name)`.
- `PhoneNumberLengthException` (extends `RuntimeException`) — thrown by `Country::possiblePhoneNumberLengths()` during
  length inference. Factories: `missingMetadata(string $country)`, `undefined(string $country)`.

## Important Patterns

- Always use `PhoneNumber::tryFrom()` for user-provided input, `PhoneNumber::from()` when the source is trusted.
- Always store phone numbers in E.164 format in the database. Use `PhoneNumberCast` (or `PhoneNumber::class` directly)
  for automatic handling.
- Length inference requires a configured country (`mno.country`). Without it, `PhoneNumberLengthException` is thrown.
- `Rule::phoneNumber()` is a macro registered by the service provider — equivalent to `PhoneNumberRule::default()`.
- `Request::phoneNumber($key, $default)` is a macro registered by the service provider — extracts and parses a phone
  number from the request.
- `PhoneNumber` instances are immutable. There is no way to modify a parsed number; create a new one instead.
- `PhoneNumber` implements `JsonSerializable` (serializes as E.164) and `Castable` (enables direct Eloquent casting).
