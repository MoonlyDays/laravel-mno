<?php

declare(strict_types=1);

namespace MoonlyDays\MNO\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use MoonlyDays\MNO\Values\PhoneNumber;

/**
 * @implements CastsAttributes<PhoneNumber, int>
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
     * Prepare the given value for storage as the E.164 digits as an integer.
     *
     * @param  PhoneNumber|string|int|null  $value
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        if ($value === null) {
            return null;
        }

        if (! $value instanceof PhoneNumber) {
            $value = PhoneNumber::from($value);
        }

        return $value->toInteger();
    }
}
