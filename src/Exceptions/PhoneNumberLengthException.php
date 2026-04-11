<?php

declare(strict_types=1);

namespace MoonlyDays\MNO\Exceptions;

use RuntimeException;

final class PhoneNumberLengthException extends RuntimeException
{
    /**
     * @param  array<int>  $lengths
     */
    public static function ambiguous(string $country, array $lengths): self
    {
        $possibleLengths = collect($lengths)->implode(', ');

        return new self(
            sprintf('Cannot infer a single mobile number length for country [%s]. Possible lengths: [%s]. Please configure operator.validation.min_length and operator.validation.max_length explicitly.', $country, $possibleLengths),
        );
    }

    public static function missingCountry(): self
    {
        return new self(
            'Cannot infer mobile number length because no country is configured. Please set operator.country or configure operator.validation.min_length and operator.validation.max_length explicitly.',
        );
    }

    public static function missingMetadata(string $country): self
    {
        return new self(
            sprintf('Cannot infer mobile number length for country [%s]: no metadata available. Please configure operator.validation.min_length and operator.validation.max_length explicitly.', $country),
        );
    }
}
