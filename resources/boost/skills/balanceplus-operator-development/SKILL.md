---
name: balanceplus-operator-development
description: >-
  Use this skill when working with phone number fields, MSISDN validation rules,
  MsisdnCast, Subscriber components, or the Operator facade. Contains full API
  signatures, code examples, and configuration reference.
---

# balanceplus/operator

Laravel package for validating, normalizing, and working with MSISDN (phone numbers). Wraps `giggsey/libphonenumber-for-php` and integrates with Laravel validation, Eloquent casting, and facades. Requires PHP 8.2+ and Laravel 11/12/13.

Namespace: `BalancePlus\Operator`

## Msisdn Value Object

The core of the package. An immutable, immediately-validated phone number representation.

**Creating instances:**

- `Msisdn::from(string $number, ?string $region = null): Msisdn` — parse and validate. Throws `InvalidMsisdnException` on failure. Use when input is trusted or you want to fail loudly.
- `Msisdn::tryFrom(string $number, ?string $region = null): ?Msisdn` — returns `null` on failure. Use for user input.
- `msisdn(string $number, ?string $region = null): Msisdn` — global helper, equivalent to `Msisdn::from()`.

When `$region` is omitted, the configured country (`config('operator.country')`) is used as default.

```php
use BalancePlus\Operator\PhoneNumber;

// Throws on invalid input
$msisdn = PhoneNumber::from('+255712345678');
$msisdn = PhoneNumber::from('0712345678', 'TZ');

// Returns null on invalid input — use for user input
$msisdn = PhoneNumber::tryFrom($request->input('phone'));

// Global helper
$msisdn = msisdn('+255712345678');
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

- `toPhoneNumber(): PhoneNumber` — underlying libphonenumber instance
- `equals(Msisdn $other): bool` — comparison by E.164

Msisdn uses `Macroable` and `Tappable` traits.

## Operator Facade and OperatorService

`OperatorService` is a singleton registered with alias `msisdn`. Access it via the `Operator` facade.

```php
use BalancePlus\Operator\Facades\Operator;

Operator::country();       // "TZ" — configured ISO country code
Operator::countryCode();   // 255 — calling code for configured country
Operator::name();          // "Vodacom" — configured operator name
Operator::networkCodes();  // ["71", "74", "75"] — configured NDC prefixes
Operator::carrierLocale(); // "en_US" — locale for carrier name lookups
Operator::minLength();     // 9 — minimum national number length
Operator::maxLength();     // 9 — maximum national number length
Operator::exampleNumber(); // Msisdn|null — example number for country
Operator::numberTypes();   // [NumberType::Mobile, NumberType::General]
```

**Smart length inference:** When `min_length` or `max_length` are not explicitly configured, `OperatorService` infers them from libphonenumber metadata. It iterates through configured `number_types` (default: Mobile, then General) and returns the length from the first type with a single unambiguous value. If ambiguous, it throws `MsisdnLengthException`. If `min_length` is not set, it falls back to `max_length`.

## Validation Rule

`MsisdnRule` implements Laravel's `ValidationRule` interface with a fluent API.

```php
use Illuminate\Validation\Rule;
use BalancePlus\Operator\Rules\PhoneNumberRule;

// Default rule — pre-configured from config (country, networkCodes, min/maxLength)
$request->validate([
    'phone' => ['required', Rule::msisdn()],
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

**`MsisdnRule::default()`** creates a rule pre-configured from `config/operator.php` (country, networkCodes, minLength, maxLength).

**`MsisdnRule::defaults(?callable $resolver)`** sets a custom resolver for `default()`:

```php
MsisdnRule::defaults(fn () => (new MsisdnRule())
    ->country('US', 'CA')
    ->minLength(10)
    ->maxLength(10)
);
```

**Validation order:** parses as MSISDN, checks country, checks min length, checks max length, checks network code prefix.

**Validation error keys:** `validation.msisdn.invalid`, `validation.msisdn.country`, `validation.msisdn.min_length`, `validation.msisdn.max_length`, `validation.msisdn.network_code`.

## Eloquent Cast

`MsisdnCast` stores phone numbers as E.164 in the database and hydrates them as `Msisdn` instances.

```php
use BalancePlus\Operator\Casts\PhoneNumberCast;

class User extends Model
{
    protected $casts = [
        'phone' => PhoneNumberCast::class,
    ];
}

// Setting — accepts string or Msisdn, stores as E.164
$user->phone = '0712345678';
$user->phone = Msisdn::from('+255712345678');
$user->save(); // Stored as "+255712345678"

// Getting — returns Msisdn instance (or null)
$user->phone->national();    // "0712 345 678"
$user->phone->countryIso();  // "TZ"
```

Always stores as E.164. Returns `null` when the database value is `null`.

## Subscriber and Components

`Subscriber` wraps an `Msisdn` and provides lazy-loaded, cacheable components via the `HasComponents` trait.

```php
use BalancePlus\Operator\Subscriber;
use BalancePlus\Operator\PhoneNumber;

// Register components globally (callable or class string)
Subscriber::registerComponent('balance', fn (Subscriber $s) => fetchBalance($s->msisdn()));
Subscriber::registerComponent('profile', ProfileComponent::class);

$subscriber = new Subscriber(PhoneNumber::from('+255712345678'));

// Lazy-load on demand (resolved only when accessed, then cached)
$balance = $subscriber->component('balance');

// Eager-load multiple components
$subscriber->load('balance', 'profile');

// Load only if not already loaded
$subscriber->loadMissing('balance');

// Check/get cached value
if ($subscriber->componentLoaded('balance')) {
    $cached = $subscriber->loadedComponent('balance');
}
```

**Component name normalization:** When registering a class string, the name is derived automatically — the class basename has its `Component` suffix stripped and is converted to kebab-case (e.g., `BalanceComponent` becomes `balance`, `AccountProfileComponent` becomes `account-profile`).

**DI resolution:** When a class string is registered, it is resolved via the Laravel container with the `Subscriber` instance passed as a named constructor parameter (camelCase of the owner class basename, i.e., `$subscriber`).

Throws `ComponentNotFoundException` if the component is not registered.

Subscriber uses `Conditionable`, `Macroable`, and `Tappable` traits.

## MsisdnFormatResource

JSON API resource for exposing operator format configuration to frontends or external APIs:

```php
use BalancePlus\Operator\Resources\PhoneNumberFormatResource;

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

## NumberType Enum

`BalancePlus\Operator\Enums\NumberType` maps phone number types to libphonenumber metadata descriptors. Used by `OperatorService` for length inference.

Cases: `Mobile`, `FixedLine`, `General`, `TollFree`, `PremiumRate`, `SharedCost`, `Voip`, `PersonalNumber`, `Pager`, `Uan`, `Voicemail`.

Key method: `descriptionFrom(PhoneMetadata $metadata): ?PhoneNumberDesc`.

## Configuration

Config file: `config/operator.php` (publishable via `operator-config` tag).

| Config Key | Env Variable | Type | Default | Purpose |
|---|---|---|---|---|
| `name` | `OPERATOR_NAME` | `string` | `""` | Operator name |
| `country` | `OPERATOR_COUNTRY` | `string` | `""` | ISO 3166-1 alpha-2 country code, used as default parse region |
| `network_codes` | `OPERATOR_NETWORK_CODES` | `array` | `[]` | Comma-separated NDC prefixes |
| `carrier_locale` | `OPERATOR_CARRIER_LOCALE` | `string` | `en_US` | IETF BCP 47 locale |
| `validation.min_length` | `OPERATOR_MSISDN_MIN_LENGTH` | `int\|null` | `null` | Min national number length (inferred if null) |
| `validation.max_length` | `OPERATOR_MSISDN_MAX_LENGTH` | `int\|null` | `null` | Max national number length (inferred if null) |
| `validation.number_types` | — | `array` | `[Mobile, General]` | NumberType priority for length inference |

## Exceptions

- `InvalidMsisdnException` (extends `InvalidArgumentException`) — thrown by `Msisdn::from()` when parsing or validation fails. Factory: `InvalidMsisdnException::forNumber(string $number, ?Throwable $previous = null)`.
- `MsisdnLengthException` (extends `RuntimeException`) — thrown by `OperatorService::maxLength()` during length inference. Factories: `ambiguous(string $country, array $lengths)`, `missingCountry()`, `missingMetadata(string $country)`.
- `ComponentNotFoundException` (extends `RuntimeException`) — thrown by `HasComponents::component()` when a component is not registered. Factory: `ComponentNotFoundException::named(string $name)`.

## Important Patterns

- Always use `Msisdn::tryFrom()` for user-provided input, `Msisdn::from()` when the source is trusted.
- Always store phone numbers in E.164 format in the database. Use `MsisdnCast` for automatic handling.
- Length inference requires a configured country (`operator.country`). Without it, `MsisdnLengthException` is thrown.
- Component names are auto-normalized: `MyBalanceComponent` class registers as `my-balance`.
- `Rule::msisdn()` is a macro registered by the service provider — equivalent to `MsisdnRule::default()`.
- Msisdn instances are immutable. There is no way to modify a parsed number; create a new one instead.
