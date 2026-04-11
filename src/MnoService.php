<?php

declare(strict_types=1);

namespace MoonlyDays\MNO;

use Illuminate\Config\Repository;
use libphonenumber\PhoneNumber as BasePhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use MoonlyDays\MNO\Enums\NumberType;
use MoonlyDays\MNO\Exceptions\InvalidCarrierException;
use MoonlyDays\MNO\Exceptions\PhoneNumberLengthException;
use MoonlyDays\MNO\Values\Carrier;
use MoonlyDays\MNO\Values\Country;
use MoonlyDays\MNO\Values\PhoneNumber;

class MnoService
{
    protected string $countryIsoCode;

    protected string $carrierName;

    protected mixed $minLength;

    protected mixed $maxLength;

    /** @var array<NumberType> */
    protected array $numberTypes;

    /** @var array<string> */
    protected array $networkCodes;

    public function __construct(
        protected PhoneNumberUtil $phoneNumberUtil,
        protected Repository $config,
    ) {
        $this->countryIsoCode = $this->config->string('mno.country');
        $this->carrierName = $this->config->string('mno.name');
        $this->networkCodes = $this->config->array('mno.network_codes');
        $this->minLength = $this->config->get('mno.min_length');
        $this->maxLength = $this->config->get('mno.max_length');
        $this->numberTypes = $this->config->array('mno.number_types');
    }

    /**
     * Get the configured country ISO code (e.g., "TZ").
     */
    public function countryIsoCode(): string
    {
        return $this->countryIsoCode;
    }

    /**
     * Get the configured operator name.
     */
    public function carrierName(): string
    {
        return $this->carrierName;
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
        if ($this->networkCodes !== []) {
            return $this->networkCodes;
        }

        return $this->carrier()->networkCodes();
    }

    /**
     * Get the configured number types used when inferring possible
     * national-number lengths from libphonenumber metadata.
     *
     * @return array<NumberType>
     */
    public function numberTypes(): array
    {
        return $this->numberTypes;
    }

    /**
     * Minimum national number length used for validation.
     *
     * Returns `mno.min_length` when explicitly configured; otherwise
     * delegates to {@see Country::minPhoneNumberLength()} for the
     * configured country, which infers the value from libphonenumber
     * metadata.
     *
     * @throws PhoneNumberLengthException when inference is required but
     *                                    libphonenumber has no usable metadata for the country.
     */
    public function minLength(): int
    {
        if (is_numeric($this->minLength)) {
            return (int) $this->minLength;
        }

        return $this->country()->minPhoneNumberLength($this->numberTypes());
    }

    /**
     * Maximum national number length used for validation.
     *
     * Returns `mno.max_length` when explicitly configured; otherwise
     * delegates to {@see Country::maxPhoneNumberLength()} for the
     * configured country, which infers the value from libphonenumber
     * metadata.
     *
     * @throws PhoneNumberLengthException when inference is required but
     *                                    libphonenumber has no usable metadata for the country.
     */
    public function maxLength(): int
    {
        if (is_numeric($this->maxLength)) {
            return (int) $this->maxLength;
        }

        return $this->country()->maxPhoneNumberLength($this->numberTypes());
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

    /**
     * Coerce a raw config value (string from env, int from runtime overrides,
     * or null) into an int or null. Empty strings become null so that an
     * unset `MNO_MIN_LENGTH=` env line doesn't pin the bound at zero.
     */
    protected function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
