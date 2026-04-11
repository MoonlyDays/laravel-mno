<?php

declare(strict_types=1);

namespace MoonlyDays\MNO\Values;

use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumber as BasePhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use MoonlyDays\MNO\Exceptions\InvalidPhoneNumberException;
use MoonlyDays\MNO\Facades\MNO;
use Stringable;

class PhoneNumber implements Stringable
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
        return $this->e164();
    }

    /**
     * Parse the given number into a PhoneNumber value object.
     *
     * @throws InvalidPhoneNumberException
     */
    public static function from(string $number, ?string $region = null): self
    {
        $region ??= MNO::countryIsoCode();
        $util = PhoneNumberUtil::getInstance();

        try {
            $parsed = $util->parse($number, $region);
        } catch (NumberParseException $numberParseException) {
            throw InvalidPhoneNumberException::forNumber($number, $numberParseException);
        }

        if (! $util->isValidNumber($parsed)) {
            throw InvalidPhoneNumberException::forNumber($number);
        }

        return new self($parsed);
    }

    /**
     * Try to parse the given number, returning null on failure.
     */
    public static function tryFrom(string $number, ?string $region = null): ?self
    {
        try {
            return self::from($number, $region);
        } catch (InvalidPhoneNumberException) {
            return null;
        }
    }

    /**
     * Get the number in E.164 format (e.g., +255712345678).
     */
    public function e164(): string
    {
        return $this->phoneNumberUtil->format($this->phoneNumber, PhoneNumberFormat::E164);
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
     * Get the underlying libphonenumber PhoneNumber instance.
     */
    public function toPhoneNumber(): BasePhoneNumber
    {
        return $this->phoneNumber;
    }

    /**
     * Determine if two PhoneNumber instances represent the same number.
     */
    public function equals(self $other): bool
    {
        return $this->e164() === $other->e164();
    }
}
