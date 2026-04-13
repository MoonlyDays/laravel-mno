# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Laravel PHP package (`moonlydays/laravel-mno`) for validating, normalizing, and working with phone number data
for Mobile Network Operators (MNOs). Wraps `giggsey/libphonenumber-for-php` and integrates with Laravel's
validation, Eloquent casting, and facade systems.

**Namespace:** `MoonlyDays\MNO`

Requires PHP 8.2+ and Laravel 11/12/13.

## Common Commands

```bash
# Run tests (Pest PHP)
composer test                       # or: vendor/bin/pest
vendor/bin/pest --filter=TestName   # run a single test

# Static analysis (Larastan / PHPStan)
composer analyse                    # or: vendor/bin/phpstan analyse

# Code style fixing (Laravel Pint)
composer lint                       # or: pint --parallel

# Package discovery for test environment
composer run prepare
```

## Architecture

**Core value objects** (under `MoonlyDays\MNO\Values`):

- `PhoneNumber` — immutable phone number representation implementing `Castable`, `JsonSerializable`, and
  `Stringable`. Created via `PhoneNumber::from()` (throws `InvalidPhoneNumberException`) or
  `PhoneNumber::tryFrom()` (returns null). Provides format outputs: E.164, national, international. Global
  helper: `phoneNumber($number, $region)`. Extensible via `Macroable` and `Tappable` traits.
- `Country` — ISO 3166-1 alpha-2 country wrapper. `Country::from('TZ')` (throws `InvalidCountryException`)
  or `Country::tryFrom('TZ')` (returns null). Exposes `countryCode()`, `name()`, `carriers()`,
  `carrier($name)`, `tryCarrier($name)`, `hasCarrier($name)`, `exampleNumber()`,
  `isMobileNumberPortable()`, `minPhoneNumberLength()`, `maxPhoneNumberLength()`,
  `possiblePhoneNumberLengths()`. Carrier data is lazy-loaded from libphonenumber via `CarrierDataRepository`.
- `Carrier` — carrier within a country, holding NDC network codes. `Carrier::from($country, $name)` throws
  `InvalidCarrierException` on miss. Supports `matches(PhoneNumber)`, `owns($networkCode)`, `prefixes()`.

**Service layer:** `MnoService` — singleton registered by `MnoServiceProvider`. Configured via
`config/mno.php` under the `mno.*` config namespace (operator name, country, network codes,
lengths, number types). Accessible through the `MNO` facade (also aliased as the `mno`
container binding). Key accessors: `countryIsoCode()`, `country()`, `carrierName()`, `carrier()`,
`countryCode()`, `networkCodes()`, `minLength()`, `maxLength()`, `exampleNumber()`, `numberTypes()`.

**Console:** `php artisan mno:show [country?] [carrier?]` — `ShowCommand` inspects the configured MNO,
an arbitrary country, or a specific carrier within a country.

**Laravel integration:**

- `PhoneNumberCast` — Eloquent attribute cast, stores as E.164 format. Also available via `PhoneNumber`'s
  `Castable` interface (`castUsing()`)
- `PhoneNumberRule` — validation rule with fluent API (`country()`, `networkCodes()`, `minLength()`,
  `maxLength()`), registered as `Rule::phoneNumber()` macro
- `Request::phoneNumber($key, $default)` — macro to extract and parse a phone number from a request
- `PhoneNumberFormatResource` — JSON API resource exposing operator format metadata
  (`countryCode`, `country`, `minLength`, `maxLength`, `networkCodes`) from `MnoService`
- `NumberType` enum — maps phone number types to libphonenumber metadata descriptors for length inference

## Configuration

The config file is `config/mno.php`, and keys are loaded under the `mno.*` namespace (the service
provider names the package `mno`). Env vars are prefixed `MNO_*` (e.g., `MNO_COUNTRY`, `MNO_NETWORK_CODES`,
`MNO_MIN_LENGTH`, `MNO_MAX_LENGTH`, `MNO_NAME`).

When `mno.min_length` / `max_length` are null, `MnoService` infers the length from libphonenumber
metadata via `Country::possiblePhoneNumberLengths()`, iterating through `mno.number_types` in order.
If no country is configured or the metadata exposes no usable lengths, `PhoneNumberLengthException` is thrown.

## Testing

Uses Pest PHP with Orchestra Testbench.
