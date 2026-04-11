## balanceplus/operator

MSISDN parsing, validation, normalization, and subscriber wrapping for Laravel. Namespace: `BalancePlus\Operator`.

**When to reference the skill:** Load the `balanceplus/operator` skill when working with phone number fields, MSISDN validation rules, `MsisdnCast`, `Subscriber` components, or the `Operator` facade. The skill contains full API signatures, code examples, and configuration reference.

### Quick Reference

- **Parse:** `Msisdn::from($number)` (throws) or `Msisdn::tryFrom($number)` (null). Always use `tryFrom` for user input.
- **Format:** `->e164()`, `->national()`, `->international()`. Casting and `__toString` use E.164.
- **Extract:** `->countryCode()`, `->countryIso()`, `->nationalNumber()`, `->networkCode()`, `->subscriberNumber()`.
- **Validate:** `Rule::msisdn()` for config defaults, or `(new MsisdnRule())->country()->networkCodes()->minLength()->maxLength()` for custom rules.
- **Cast:** `MsisdnCast::class` on Eloquent models. Stores E.164, hydrates as `Msisdn`.
- **Config:** `Operator::country()`, `Operator::networkCodes()`, `Operator::minLength()`, `Operator::maxLength()`. Lengths auto-infer from libphonenumber metadata when not set.
- **Subscriber:** `new Subscriber($msisdn)` with lazy-loaded components via `Subscriber::registerComponent()` and `->component($name)`.

### Rules

- Store phone numbers as E.164 in the database. Never store national format.
- Use `MsisdnCast` for phone columns — do not manually format on get/set.
- Use `Rule::msisdn()` (or `MsisdnRule::default()`) unless you need custom constraints.
- A configured `operator.country` is required for length inference. Without it, `MsisdnLengthException` is thrown.
- Msisdn is immutable — create a new instance rather than trying to modify one.
- Component class names auto-normalize: strip `Component` suffix, kebab-case the rest.
