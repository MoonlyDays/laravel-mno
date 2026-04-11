# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Laravel PHP package (`balanceplus/operator`) for validating, normalizing, and working with MSISDN (phone number) data.
Wraps `giggsey/libphonenumber-for-php` and integrates with Laravel's validation, Eloquent casting, and facade systems.

**Namespace:** `BalancePlus\Operator`

Requires PHP 8.2+ and Laravel 11/12/13.

## Common Commands

```bash
# Run tests (Pest PHP)
composer test                       # or: vendor/bin/pest
vendor/bin/pest --filter=TestName   # run a single test

# Static analysis (PHPStan level 5)
composer analyse                    # or: vendor/bin/phpstan analyse

# Code style fixing (Laravel Pint)
composer lint                       # or: pint --parallel

# Package discovery for test environment
composer run prepare
```

## Architecture

**Core value object:** `Msisdn` — immutable phone number representation. Created via `Msisdn::from()` (throws) or
`Msisdn::tryFrom()` (returns null). Provides format outputs: E.164, national, international. Global helper:
`msisdn($number, $region)`. Extensible via `Macroable` trait.

**Service layer:** `OperatorService` — singleton registered by `OperatorServiceProvider`. Configured via
`config/operator.php` (operator name, country, network codes, carrier locale, validation lengths, number types).
Accessible through the `Operator` facade.

**Subscriber model:** `Subscriber` — wraps an `Msisdn` instance, uses `HasComponents` trait for lazy-loaded dynamic
component resolution. Components are resolved by class name or registered callable.

**Laravel integration:**

- `MsisdnCast` — Eloquent attribute cast, stores as E.164 format
- `MsisdnRule` — validation rule with fluent API (`country()`, `operator()`, `networkCodes()`, `minLength()`,
  `maxLength()`), registered as `Rule::msisdn()` macro
- `NumberType` enum — maps phone number types to libphonenumber metadata descriptors for length inference

## Testing

Uses Pest PHP with Orchestra Testbench. Architecture tests in `tests/ArchTest.php`.
