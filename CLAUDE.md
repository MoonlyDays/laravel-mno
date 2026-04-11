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

**Core value object:** `PhoneNumber` — immutable phone number representation. Created via `PhoneNumber::from()`
(throws `InvalidPhoneNumberException`) or `PhoneNumber::tryFrom()` (returns null). Provides format outputs:
E.164, national, international. Global helper: `phoneNumber($number, $region)`. Extensible via `Macroable` and
`Tappable` traits.

**Service layer:** `MnoService` — singleton registered by `MnoServiceProvider`. Configured via
`config/operator.php` under the `mno.*` config namespace (operator name, country, network codes, carrier
locale, validation lengths, number types). Accessible through the `MNO` facade (also aliased as the `mno`
container binding).

**Laravel integration:**

- `PhoneNumberCast` — Eloquent attribute cast, stores as E.164 format
- `PhoneNumberRule` — validation rule with fluent API (`country()`, `networkCodes()`, `minLength()`,
  `maxLength()`), registered as `Rule::phoneNumber()` macro
- `PhoneNumberFormatResource` — JSON API resource exposing operator format metadata
  (`countryCode`, `country`, `minLength`, `maxLength`, `networkCodes`) from `MnoService`
- `NumberType` enum — maps phone number types to libphonenumber metadata descriptors for length inference

## Configuration

The config file is `config/operator.php`, but keys are loaded under the `mno.*` namespace (the service
provider names the package `mno`). Env vars are prefixed `MNO_*` (e.g., `MNO_COUNTRY`, `MNO_NETWORK_CODES`,
`MNO_PHONE_MIN_LENGTH`, `MNO_PHONE_MAX_LENGTH`, `MNO_CARRIER_LOCALE`, `MNO_NAME`).

When `mno.validation.min_length` / `max_length` are null, `MnoService` infers the length from libphonenumber
metadata by iterating through `mno.validation.number_types` in order. If no country is configured or the
lengths are ambiguous, `PhoneNumberLengthException` is thrown.

## Testing

Uses Pest PHP with Orchestra Testbench.
