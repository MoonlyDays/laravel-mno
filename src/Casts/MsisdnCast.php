<?php

declare(strict_types=1);

namespace MoonlyDays\MNO\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use MoonlyDays\MNO\Values\Msisdn;

/**
 * @implements CastsAttributes<Msisdn, int>
 */
class MsisdnCast implements CastsAttributes
{
    /**
     * Cast the given value to a Msisdn value object.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Msisdn
    {
        if ($value === null) {
            return null;
        }

        return Msisdn::from($value);
    }

    /**
     * Prepare the given value for storage as the E.164 digits as an integer.
     *
     * @param  Msisdn|string|int|null  $value
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        if ($value === null) {
            return null;
        }

        if (! $value instanceof Msisdn) {
            $value = Msisdn::from($value);
        }

        return $value->toInteger();
    }
}
