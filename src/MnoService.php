<?php

declare(strict_types=1);

namespace MoonlyDays\MNO;

use libphonenumber\PhoneMetadata;
use libphonenumber\PhoneNumber as BasePhoneNumber;
use libphonenumber\PhoneNumberDesc;
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
    protected ?int $cachedInferredLength = null;

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
        $value = config('mno.validation.min_length');

        if ($value !== null) {
            return (int) $value;
        }

        return $this->maxLength();
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
        $value = config('mno.validation.max_length');

        if ($value !== null) {
            return (int) $value;
        }

        return $this->inferredMobileLength();
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
     * Get the configured number types used for length inference, in priority order.
     *
     * @return array<NumberType>
     */
    public function numberTypes(): array
    {
        /** @var array<NumberType> */
        return config('mno.validation.number_types', [
            NumberType::Mobile,
            NumberType::General,
        ]);
    }

    /**
     * Infer the national number length from libphonenumber metadata.
     *
     * Iterates through the configured number types in order, returning
     * the length from the first type that yields a single unambiguous value.
     *
     * The result is cached for the lifetime of this singleton instance.
     *
     * @throws PhoneNumberLengthException
     */
    protected function inferredMobileLength(): int
    {
        if ($this->cachedInferredLength !== null) {
            return $this->cachedInferredLength;
        }

        $country = $this->countryIsoCode();
        if (! $country) {
            throw PhoneNumberLengthException::missingCountry();
        }

        $metadata = $this->phoneNumberUtil->getMetadataForRegion($country);
        if (! $metadata instanceof PhoneMetadata) {
            throw PhoneNumberLengthException::missingMetadata($country);
        }

        foreach ($this->numberTypes() as $type) {
            $lengths = $this->possibleLengthsFrom($type->descriptionFrom($metadata));

            if ($lengths === []) {
                continue;
            }

            if (count($lengths) === 1) {
                return $this->cachedInferredLength = $lengths[0];
            }

            throw PhoneNumberLengthException::ambiguous($country, $lengths);
        }

        throw PhoneNumberLengthException::missingMetadata($country);
    }

    /**
     * Extract valid possible lengths from a phone number description.
     *
     * @return array<int>
     */
    protected function possibleLengthsFrom(?PhoneNumberDesc $desc): array
    {
        if (! $desc instanceof PhoneNumberDesc) {
            return [];
        }

        return collect($desc->getPossibleLength())
            ->filter(fn (int $length): bool => $length > 0)
            ->values()
            ->all();
    }
}
