<?php

declare(strict_types=1);

namespace MoonlyDays\MNO\Values;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use JsonSerializable;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumber as BasePhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberToTimeZonesMapper;
use libphonenumber\PhoneNumberUtil;
use MoonlyDays\MNO\Casts\MsisdnCast;
use MoonlyDays\MNO\Exceptions\InvalidMsisdnException;
use MoonlyDays\MNO\Facades\MNO;
use Stringable;

class Msisdn implements Castable, JsonSerializable, Stringable
{
    use Macroable;
    use Tappable;

    protected PhoneNumberUtil $phoneNumberUtil;

    public function __construct(
        protected BasePhoneNumber $phoneNumber,
    ) {
        $this->phoneNumberUtil = PhoneNumberUtil::getInstance();
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Parse the given number into a Msisdn value object.
     *
     * @throws InvalidMsisdnException
     */
    public static function from(string|int $number, ?string $region = null): self
    {
        $number = (string) $number;
        $region ??= MNO::countryIsoCode();
        $util = PhoneNumberUtil::getInstance();

        try {
            $parsed = $util->parse($number, $region);
        } catch (NumberParseException $numberParseException) {
            throw InvalidMsisdnException::forNumber($number, $numberParseException);
        }

        if (! $util->isValidNumber($parsed)) {
            throw InvalidMsisdnException::forNumber($number);
        }

        return new self($parsed);
    }

    /**
     * Try to parse the given number, returning null on failure.
     */
    public static function tryFrom(string|int $number, ?string $region = null): ?self
    {
        try {
            return self::from($number, $region);
        } catch (InvalidMsisdnException) {
            return null;
        }
    }

    public static function castUsing(array $arguments): string
    {
        return MsisdnCast::class;
    }

    /**
     * Get the number in E.164 format (e.g., +255712345678).
     */
    public function e164(): string
    {
        return $this->phoneNumberUtil->format($this->phoneNumber, PhoneNumberFormat::E164);
    }

    /**
     * Converts the phone number to an integer representation by removing the plus sign
     * from its E.164 formatted string and casting it to an integer.
     *
     * @return int The integer representation of the phone number.
     */
    public function toInteger(): int
    {
        return (int) (Str::replaceStart(PhoneNumberUtil::PLUS_SIGN, '', $this->e164()));
    }

    /**
     * Get the number in national format (e.g., 0712 345 678).
     */
    public function national(): string
    {
        return $this->phoneNumberUtil->format($this->phoneNumber, PhoneNumberFormat::NATIONAL);
    }

    /**
     * Get the number in international format (e.g., +255 712 345 678).
     */
    public function international(): string
    {
        return $this->phoneNumberUtil->format($this->phoneNumber, PhoneNumberFormat::INTERNATIONAL);
    }

    /**
     * Get the country calling code (e.g., 255 for Tanzania).
     */
    public function countryCode(): int
    {
        return $this->phoneNumber->getCountryCode();
    }

    /**
     * Get the ISO 3166-1 alpha-2 country code (e.g., "TZ").
     */
    public function countryIso(): string
    {
        return $this->phoneNumberUtil->getRegionCodeForNumber($this->phoneNumber);
    }

    /**
     * Get the national (significant) number without the country code.
     */
    public function nationalNumber(): string
    {
        return (string) $this->phoneNumber->getNationalNumber();
    }

    /**
     * Get the network code (NDC) prefix portion of the national number.
     *
     * Uses libphonenumber's length-of-national-destination-code to split
     * the national number into NDC and subscriber parts.
     */
    public function networkCode(): string
    {
        $ndcLength = $this->phoneNumberUtil->getLengthOfNationalDestinationCode($this->phoneNumber);

        if ($ndcLength === 0) {
            return '';
        }

        return Str::substr($this->nationalNumber(), 0, $ndcLength);
    }

    /**
     * Get the subscriber number (the part after the network code).
     */
    public function subscriberNumber(): string
    {
        $ndcLength = $this->phoneNumberUtil->getLengthOfNationalDestinationCode($this->phoneNumber);

        return Str::substr($this->nationalNumber(), $ndcLength);
    }

    /**
     * Get all IANA timezone identifiers for this phone number.
     *
     * @return array<string>
     */
    public function timezones(): array
    {
        $mapper = PhoneNumberToTimeZonesMapper::getInstance();

        return array_values(array_filter(
            $mapper->getTimeZonesForNumber($this->phoneNumber),
            static fn (string $tz): bool => $tz !== PhoneNumberToTimeZonesMapper::UNKNOWN_TIMEZONE,
        ));
    }

    /**
     * Get the primary IANA timezone identifier for this phone number,
     * or null if the timezone cannot be determined.
     */
    public function timezone(): ?string
    {
        return $this->timezones()[0] ?? null;
    }

    /**
     * Get the underlying libphonenumber PhoneNumber instance.
     */
    public function toPhoneNumber(): BasePhoneNumber
    {
        return $this->phoneNumber;
    }

    public function toString(): string
    {
        return $this->e164();
    }

    /**
     * Determine if two Msisdn instances represent the same number.
     */
    public function equals(self $other): bool
    {
        return $this->toString() === $other->toString();
    }

    /**
     * Serialize to JSON as the E.164 form, so API responses and Eloquent
     * JSON casts produce a canonical, region-independent string.
     */
    public function jsonSerialize(): string
    {
        return $this->toString();
    }
}
