<?php

declare(strict_types=1);

namespace MoonlyDays\MNO\Exceptions;

use InvalidArgumentException;
use MoonlyDays\MNO\Values\Country;
use Throwable;

final class InvalidCarrierException extends InvalidArgumentException
{
    public static function missingArguments(?Throwable $previous = null): self
    {
        return new self(
            message: 'You must provide both ISO country code and carrier name to create a Carrier instance.',
            previous: $previous,
        );
    }

    public static function notFoundIn(Country $country, string $name): self
    {
        return new self(sprintf(
            'Carrier "%s" was not found in %s.',
            $name,
            $country->isoCode(),
        ));
    }
}
