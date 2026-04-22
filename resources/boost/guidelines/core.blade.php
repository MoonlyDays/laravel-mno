## moonlydays/laravel-mno

Phone number parsing, validation, and normalization for Laravel MNOs. Namespace: `MoonlyDays\MNO`.

**When to reference the skill:** Load the `laravel-mno-development` skill when working with phone number fields, validation rules, `MsisdnCast`, the `MNO` facade, the `MsisdnFormatResource`, or the Faker provider. The skill contains full API signatures, code examples, and configuration reference.

### Quick Reference

- **Parse:** `Msisdn::from($number)` (throws, accepts `string|int`) or `Msisdn::tryFrom($number)` (null). Always use `tryFrom` for user input.
- **Format:** `->e164()`, `->national()`, `->international()`, `->toInteger()`. Casting and `__toString` use E.164.
- **Extract:** `->countryCode()`, `->countryIso()`, `->nationalNumber()`, `->networkCode()`, `->subscriberNumber()`.
- **Timezones:** `->timezone()` (primary IANA id or null), `->timezones()` (all IANA ids).
- **Validate:** `Rule::msisdn()` for config defaults, or `(new MsisdnRule())->country()->networkCodes()->minLength()->maxLength()` for custom rules.
- **Cast:** `MsisdnCast::class` on Eloquent models, or use `Msisdn::class` directly (implements `Castable`). Stores as **unsigned bigInteger** (E.164 digits without the leading `+`), hydrates as `Msisdn`. Back the column with `$table->unsignedBigInteger(...)`.
- **Faker:** `fake()->msisdn()` — returns a `Msisdn` value object within the configured MNO; call `->e164()`, `->national()`, `->international()` on it for string forms.
- **Config (facade):** `MNO::country()`, `MNO::networkCodes()`, `MNO::minLength()`, `MNO::maxLength()`, `MNO::numberTypes()`. Lengths auto-infer from libphonenumber metadata when not set.
- **Helper:** `msisdn($number, $region)` — equivalent to `Msisdn::from()`.
- **Request:** `$request->msisdn($key)` — macro to extract and parse a phone number from a request.
- **Resource:** `MsisdnFormatResource::make()` exposes `{countryCode, country, minLength, maxLength, networkCodes}` as JSON.
- **Command:** `php artisan mno:show [country] [carrier]` — inspect the configured MNO, a country, or a specific carrier.

### Rules

- Use `$table->unsignedBigInteger(...)` for phone columns — the cast stores an integer, not a string. A `VARCHAR` column will break writes.
- A configured `mno.country` is **required** for cast reads. The stored integer has no `+`, so libphonenumber needs a default region to parse it back.
- Use `MsisdnCast` (or `Msisdn::class` directly via `Castable`) for phone columns — do not manually format on get/set.
- Use `Rule::msisdn()` (or `MsisdnRule::default()`) unless you need custom constraints.
- A configured `mno.country` is also required for length inference. Without it, `PhoneNumberLengthException` is thrown.
- `Msisdn` is immutable — create a new instance rather than trying to modify one.
- The config file is `config/mno.php` and keys live under the `mno.*` namespace (e.g., `config('mno.country')`, `config('mno.network_codes')`, `config('mno.min_length')`).
- Env vars are prefixed `MNO_*` (e.g., `MNO_COUNTRY`, `MNO_NETWORK_CODES`, `MNO_MIN_LENGTH`).
