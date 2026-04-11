<?php

declare(strict_types=1);

namespace MoonlyDays\MNO\Exceptions;

use InvalidArgumentException;

final class InvalidCountryException extends InvalidArgumentException
{
    public static function unknownIsoCode(string $isoCode): self
    {
        return new self(sprintf(
            'Unknown ISO 3166-1 alpha-2 country code: "%s".',
            $isoCode,
        ));
    }
}
