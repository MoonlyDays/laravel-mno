## moonlydays/laravel-mno

Phone number parsing, validation, and normalization for Laravel MNOs. Namespace: `MoonlyDays\MNO`.

**When to reference the skill:** Load the `laravel-mno-development` skill when working with phone number fields, validation rules, `PhoneNumberCast`, the `MNO` facade, the `PhoneNumberFormatResource`, the `Blueprint::phoneNumber()` schema macro, or the Faker provider. The skill contains full API signatures, code examples, and configuration reference.

### Quick Reference

- **Parse:** `PhoneNumber::from($number)` (throws, accepts `string|int`) or `PhoneNumber::tryFrom($number)` (null). Always use `tryFrom` for user input.
- **Format:** `->e164()`, `->national()`, `->international()`, `->toInteger()`. Casting and `__toString` use E.164.
- **Extract:** `->countryCode()`, `->countryIso()`, `->nationalNumber()`, `->networkCode()`, `->subscriberNumber()`.
- **Timezones:** `->timezone()` (primary IANA id or null), `->timezones()` (all IANA ids).
- **Validate:** `Rule::phoneNumber()` for config defaults, or `(new PhoneNumberRule())->country()->networkCodes()->minLength()->maxLength()` for custom rules.
- **Cast:** `PhoneNumberCast::class` on Eloquent models, or use `PhoneNumber::class` directly (implements `Castable`). Stores as **unsigned bigInteger** (E.164 digits without the leading `+`), hydrates as `PhoneNumber`.
- **Schema:** `$table->phoneNumber('phone')` macro — defines the `unsigned bigInteger` column the cast expects.
- **Faker:** `fake()->phoneNumber()`, `phoneNumberObject()`, `e164PhoneNumber()`, `nationalPhoneNumber()`, `internationalPhoneNumber()` — generates valid numbers within the configured MNO.
- **Config (facade):** `MNO::country()`, `MNO::networkCodes()`, `MNO::minLength()`, `MNO::maxLength()`, `MNO::numberTypes()`. Lengths auto-infer from libphonenumber metadata when not set.
- **Helper:** `phoneNumber($number, $region)` — equivalent to `PhoneNumber::from()`.
- **Request:** `$request->phoneNumber($key)` — macro to extract and parse a phone number from a request.
- **Resource:** `PhoneNumberFormatResource::make()` exposes `{countryCode, country, minLength, maxLength, networkCodes}` as JSON.
- **Command:** `php artisan mno:show [country] [carrier]` — inspect the configured MNO, a country, or a specific carrier.

### Rules

- Use `$table->phoneNumber(...)` (or `unsignedBigInteger`) for phone columns — the cast stores an integer, not a string. A `VARCHAR` column will break writes.
- A configured `mno.country` is **required** for cast reads. The stored integer has no `+`, so libphonenumber needs a default region to parse it back.
- Use `PhoneNumberCast` (or `PhoneNumber::class` directly via `Castable`) for phone columns — do not manually format on get/set.
- Use `Rule::phoneNumber()` (or `PhoneNumberRule::default()`) unless you need custom constraints.
- A configured `mno.country` is also required for length inference. Without it, `PhoneNumberLengthException` is thrown.
- `PhoneNumber` is immutable — create a new instance rather than trying to modify one.
- The config file is `config/mno.php` and keys live under the `mno.*` namespace (e.g., `config('mno.country')`, `config('mno.network_codes')`, `config('mno.min_length')`).
- Env vars are prefixed `MNO_*` (e.g., `MNO_COUNTRY`, `MNO_NETWORK_CODES`, `MNO_MIN_LENGTH`).
