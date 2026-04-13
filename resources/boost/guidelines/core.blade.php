## moonlydays/laravel-mno

Phone number parsing, validation, and normalization for Laravel MNOs. Namespace: `MoonlyDays\MNO`.

**When to reference the skill:** Load the `laravel-mno-development` skill when working with phone number fields, validation rules, `PhoneNumberCast`, the `MNO` facade, or the `PhoneNumberFormatResource`. The skill contains full API signatures, code examples, and configuration reference.

### Quick Reference

- **Parse:** `PhoneNumber::from($number)` (throws) or `PhoneNumber::tryFrom($number)` (null). Always use `tryFrom` for user input.
- **Format:** `->e164()`, `->national()`, `->international()`. Casting and `__toString` use E.164.
- **Extract:** `->countryCode()`, `->countryIso()`, `->nationalNumber()`, `->networkCode()`, `->subscriberNumber()`.
- **Validate:** `Rule::phoneNumber()` for config defaults, or `(new PhoneNumberRule())->country()->networkCodes()->minLength()->maxLength()` for custom rules.
- **Cast:** `PhoneNumberCast::class` on Eloquent models, or use `PhoneNumber::class` directly (implements `Castable`). Stores E.164, hydrates as `PhoneNumber`.
- **Config (facade):** `MNO::country()`, `MNO::networkCodes()`, `MNO::minLength()`, `MNO::maxLength()`, `MNO::numberTypes()`. Lengths auto-infer from libphonenumber metadata when not set.
- **Helper:** `phoneNumber($number, $region)` — equivalent to `PhoneNumber::from()`.
- **Request:** `$request->phoneNumber($key)` — macro to extract and parse a phone number from a request.
- **Resource:** `PhoneNumberFormatResource::make()` exposes `{countryCode, country, minLength, maxLength, networkCodes}` as JSON.
- **Command:** `php artisan mno:show [country] [carrier]` — inspect the configured MNO, a country, or a specific carrier.

### Rules

- Store phone numbers as E.164 in the database. Never store national format.
- Use `PhoneNumberCast` (or `PhoneNumber::class` directly via `Castable`) for phone columns — do not manually format on get/set.
- Use `Rule::phoneNumber()` (or `PhoneNumberRule::default()`) unless you need custom constraints.
- A configured `mno.country` is required for length inference. Without it, `PhoneNumberLengthException` is thrown.
- `PhoneNumber` is immutable — create a new instance rather than trying to modify one.
- The config file is `config/mno.php` and keys live under the `mno.*` namespace (e.g., `config('mno.country')`, `config('mno.network_codes')`, `config('mno.min_length')`).
- Env vars are prefixed `MNO_*` (e.g., `MNO_COUNTRY`, `MNO_NETWORK_CODES`, `MNO_MIN_LENGTH`).
