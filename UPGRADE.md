# Upgrade Guide

## From 1.x to 2.0

Version 2.0 changes how `PhoneNumberCast` persists values. The rest of the 1.x API is backwards compatible;
if you do not use the Eloquent cast, the upgrade is drop-in.

### Breaking changes

#### `PhoneNumberCast` now stores phone numbers as unsigned integers

In 1.x, the cast persisted the E.164 form as a string (e.g. `"+79101234567"`).
In 2.0, it persists the E.164 digits as an unsigned integer (e.g. `79101234567`), matching the new
`Blueprint::phoneNumber()` schema macro.

Consequences:

- Columns backing cast attributes must be `UNSIGNED BIGINT` (use `$table->phoneNumber(...)` or
  `$table->unsignedBigInteger(...)`), not `VARCHAR`.
- `PhoneNumberCast::set()` return type changed from `?string` to `?int`. Subclasses overriding `set()` must
  update their signature.
- `MNO_COUNTRY` **must** be configured. On read, the stored integer has no leading `+`, so libphonenumber
  needs a default region to parse it.

#### Migration path for existing data

1. **Set `MNO_COUNTRY`** in your environment if it was not already set.

2. **Migrate the column type and strip the leading `+`.** Example migration:

    ```php
    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        public function up(): void
        {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('phone_new')->nullable()->after('phone');
            });

            DB::table('users')->whereNotNull('phone')->orderBy('id')->chunkById(1000, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('users')
                        ->where('id', $row->id)
                        ->update(['phone_new' => (int) ltrim((string) $row->phone, '+')]);
                }
            });

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('phone');
            });

            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('phone_new', 'phone');
            });
        }
    };
    ```

    Adjust the table and column names to match your schema. For new tables, use `$table->phoneNumber('phone')`
    directly.

3. **Verify reads** after the migration. The round-trip is `stored int → PhoneNumber::from(int, MNO_COUNTRY)`;
   hydration throws `InvalidPhoneNumberException` if the stored number does not match the configured region.
   Numbers from other countries must be stored together with a region hint in your own schema, or kept in
   a country you parse against at read time.

### Non-breaking additions

These require no action but are new in 2.0:

- `PhoneNumber::from()` now accepts `string|int`.
- `PhoneNumber::toInteger()` returns the E.164 digits as an `int`.
- `PhoneNumber::timezone()` / `PhoneNumber::timezones()` return IANA timezone identifiers.
- `Blueprint::phoneNumber($column)` schema macro for migrations.
- Faker provider methods: `phoneNumberObject`, `phoneNumber`, `e164PhoneNumber`, `nationalPhoneNumber`,
  `internationalPhoneNumber`.
- `PhoneNumberFormatResource` JSON resource exposing operator format metadata.
