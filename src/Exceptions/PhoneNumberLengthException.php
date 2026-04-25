<?php

declare(strict_types=1);

namespace MoonlyDays\MNO\Exceptions;

use RuntimeException;

/**
 * Raised by {@see \MoonlyDays\MNO\Values\Country::possiblePhoneNumberLengths()}
 * when national-number length bounds cannot be derived from libphonenumber
 * metadata. Callers that want a hard contract should configure
 * `mno.min_length` / `mno.max_length` explicitly to bypass inference.
 */
final class PhoneNumberLengthException extends RuntimeException
{
    /**
     * The region is unknown to libphonenumber — no PhoneMetadata bundle
     * exists for the given ISO code. Typically means the country code
     * is malformed or points at an unsupported territory.
     */
    public static function missingMetadata(string $country): self
    {
        return new self(
            \sprintf('Cannot infer phone number length for country [%s]: no libphonenumber metadata available.', $country),
        );
    }

    /**
     * Metadata exists for the region, but none of its NumberType
     * descriptors expose a usable possible-length list (everything was
     * missing or filtered out as a -1/0 sentinel).
     */
    public static function undefined(string $country): self
    {
        return new self(
            \sprintf('Could not infer possible phone number lengths for country [%s]: libphonenumber metadata did not expose any usable lengths.', $country),
        );
    }
}
