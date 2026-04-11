<?php

declare(strict_types=1);

namespace MoonlyDays\MNO\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use MoonlyDays\MNO\PhoneNumber;

/**
 * @implements CastsAttributes<PhoneNumber, string>
 */
class PhoneNumberCast implements CastsAttributes
{
    /**
     * Cast the given value to a PhoneNumber value object.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?PhoneNumber
    {
        if ($value === null) {
            return null;
        }

        return PhoneNumber::from($value);
    }

    /**
     * Prepare the given value for storage in E.164 format.
     *
     * @param  PhoneNumber|string|null  $value
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof PhoneNumber) {
            return $value->e164();
        }

        return PhoneNumber::from($value)->e164();
    }
}
