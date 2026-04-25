<?php

declare(strict_types=1);

namespace MoonlyDays\MNO\Exceptions;

use InvalidArgumentException;
use Throwable;

final class InvalidMsisdnException extends InvalidArgumentException
{
    public static function forNumber(string $number, ?Throwable $previous = null): self
    {
        return new self(
            message: \sprintf('The value [%s] is not a valid phone number.', $number),
            previous: $previous,
        );
    }
}
