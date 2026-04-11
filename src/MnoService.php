<?php

declare(strict_types=1);

namespace MoonlyDays\MNO;

use libphonenumber\PhoneNumber as BasePhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use MoonlyDays\MNO\Exceptions\InvalidCarrierException;
use MoonlyDays\MNO\Exceptions\PhoneNumberLengthException;
use MoonlyDays\MNO\Values\Carrier;
use MoonlyDays\MNO\Values\Country;
use MoonlyDays\MNO\Values\PhoneNumber;

class MnoService
{
    public function __construct(
        protected PhoneNumberUtil $phoneNumberUtil,
    ) {}

    /**
     * Get the configured country ISO code (e.g., "TZ").
     */
    public function countryIsoCode(): string
    {
        return config('mno.country', '');
    }

    /**
     * Get the configured operator name.
     */
    public function carrierName(): string
    {
        return config('mno.name', '');
    }

    public function country(?string $code = null): Country
    {
        return Country::from($code ?? $this->countryIsoCode());
    }

    public function carrier(?string $country = null, ?string $name = null): Carrier
    {
        if ($country === null) {
            $country = $this->countryIsoCode();
            $name = $this->carrierName();
        }

        if (is_null($name)) {
            throw InvalidCarrierException::missingArguments();
        }

        return $this->country($country)->carrier($name);
    }

    /**
     * Get the calling code for the configured country (e.g., 255 for TZ).
     */
    public function countryCode(): int
    {
        return $this->country()->countryCode();
    }

    /**
     * Get the configured network codes.
     *
     * @return array<string>
     */
    public function networkCodes(): array
    {
        $networkCodes = (array) config('mno.network_codes', []);
        if ($networkCodes !== []) {
            return $networkCodes;
        }

        return $this->carrier()->networkCodes();
    }

    /**
     * Get the minimum national number length for validation.
     *
     * Returns the configured value if explicitly set, otherwise
     * falls back to maxLength().
     *
     * @throws PhoneNumberLengthException
     */
    public function minLength(): int
    {
        $length = config('mno.min_length');

        return $length === null
            ? $this->country()->minPhoneNumberLength()
            : (int) $length;
    }

    /**
     * Get the maximum national number length for validation.
     *
     * Returns the configured value if set, otherwise infers from
     * libphonenumber's mobile metadata for the configured country.
     *
     * @throws PhoneNumberLengthException
     */
    public function maxLength(): int
    {
        $length = config('mno.max_length');

        return $length === null
            ? $this->country()->maxPhoneNumberLength()
            : (int) $length;
    }

    /**
     * Get an example national number for the configured country.
     */
    public function exampleNumber(): ?PhoneNumber
    {
        $example = $this->phoneNumberUtil->getExampleNumber($this->countryIsoCode());

        if (! $example instanceof BasePhoneNumber) {
            return null;
        }

        return PhoneNumber::from(
            $this->phoneNumberUtil->format($example, PhoneNumberFormat::E164),
        );
    }
}
