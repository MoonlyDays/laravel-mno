<?php

declare(strict_types=1);

namespace MoonlyDays\MNO\Values;

use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use libphonenumber\PhoneMetadata;
use libphonenumber\PhoneNumber as BasePhoneNumber;
use libphonenumber\PhoneNumberDesc;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use MoonlyDays\MNO\Enums\NumberType;
use MoonlyDays\MNO\Exceptions\InvalidCarrierException;
use MoonlyDays\MNO\Exceptions\InvalidCountryException;
use MoonlyDays\MNO\Exceptions\PhoneNumberLengthException;
use MoonlyDays\MNO\Support\CarrierDataRepository;
use Stringable;

class Country implements Stringable
{
    use Macroable;
    use Tappable;

    protected string $isoCode;

    protected PhoneNumberUtil $phoneNumberUtil;

    protected ?array $cachedPossibleLengths = null;

    /**
     * Memoized carriers, keyed by display name. Null until first load.
     *
     * @var array<string, Carrier>|null
     */
    protected ?array $carriers = null;

    public function __construct(string $isoCode)
    {
        $this->isoCode = Str::upper($isoCode);
        $this->phoneNumberUtil = PhoneNumberUtil::getInstance();
    }

    public function __toString(): string
    {
        return $this->isoCode;
    }

    /**
     * Build a Country for the given ISO 3166-1 alpha-2 code.
     *
     * @throws InvalidCountryException
     */
    public static function from(string $isoCode): self
    {
        $country = new self($isoCode);

        if ($country->countryCode() === 0) {
            throw InvalidCountryException::unknownIsoCode($isoCode);
        }

        return $country;
    }

    /**
     * Try to build a Country; returns null on invalid input.
     */
    public static function tryFrom(string $isoCode): ?self
    {
        try {
            return self::from($isoCode);
        } catch (InvalidCountryException) {
            return null;
        }
    }

    /**
     * Get the ISO 3166-1 alpha-2 country code (e.g., "TZ").
     */
    public function isoCode(): string
    {
        return $this->isoCode;
    }

    /**
     * Get the E.164 country calling code (e.g., 255 for Tanzania).
     *
     * Returns 0 if the ISO code is not recognized by libphonenumber.
     */
    public function countryCode(): int
    {
        return $this->phoneNumberUtil->getCountryCodeForRegion($this->isoCode);
    }

    /**
     * Get all carriers with number allocations in this country.
     *
     * Lazily loaded from libphonenumber carrier data on first access and
     * memoized for the lifetime of this instance.
     *
     * @return array<string, Carrier>
     */
    public function carriers(): array
    {
        return $this->carriers ??= $this->loadCarriers();
    }

    /**
     * Get a carrier by name (case-insensitive lookup).
     *
     * @throws InvalidCarrierException when no carrier matches
     */
    public function carrier(string $name): Carrier
    {
        return $this->tryCarrier($name) ?? throw InvalidCarrierException::notFoundIn($this, $name);
    }

    /**
     * Try to get a carrier by name (case-insensitive); null on miss.
     */
    public function tryCarrier(string $name): ?Carrier
    {
        foreach ($this->carriers() as $carrier) {
            if (strcasecmp($carrier->name(), $name) === 0) {
                return $carrier;
            }
        }

        return null;
    }

    /**
     * Determine whether this country has a carrier with the given name.
     */
    public function hasCarrier(string $name): bool
    {
        return $this->tryCarrier($name) instanceof Carrier;
    }

    /**
     * Determine whether libphonenumber ships carrier data for this country.
     *
     * Cheaper than calling carriers() and checking for emptiness because
     * it only resolves the data file, not its contents.
     */
    public function supportsCarrierData(string $locale = 'en_US'): bool
    {
        return (new CarrierDataRepository())->has($this->countryCode(), $locale);
    }

    /**
     * Get an example phone number for this country if libphonenumber has one.
     */
    public function exampleNumber(): ?PhoneNumber
    {
        $example = $this->phoneNumberUtil->getExampleNumber($this->isoCode);

        if (! $example instanceof BasePhoneNumber) {
            return null;
        }

        return PhoneNumber::tryFrom(
            $this->phoneNumberUtil->format($example, PhoneNumberFormat::E164),
        );
    }

    public function minPhoneNumberLength(): int
    {
        return min($this->possiblePhoneNumberLengths());
    }

    public function maxPhoneNumberLength(): int
    {
        return max($this->possiblePhoneNumberLengths());
    }

    /**
     * Determine whether this region supports mobile number portability.
     *
     * In MNP regions, the carrier attribution from libphonenumber reflects
     * the *original* number allocation, not the subscriber's current network.
     */
    public function isMobileNumberPortable(): bool
    {
        return $this->phoneNumberUtil->isMobileNumberPortableRegion($this->isoCode);
    }

    /**
     * Determine whether two Country instances represent the same region.
     */
    public function equals(self $other): bool
    {
        return $this->isoCode === $other->isoCode;
    }

    public function possiblePhoneNumberLengths(): array
    {
        if ($this->cachedPossibleLengths !== null) {
            return $this->cachedPossibleLengths;
        }

        $metadata = $this->phoneNumberUtil->getMetadataForRegion($this->isoCode);
        if (! $metadata instanceof PhoneMetadata) {
            throw PhoneNumberLengthException::missingMetadata();
        }

        $lengths = [];

        foreach (NumberType::cases() as $numberType) {
            $phoneNumberDesc = $numberType->descriptionFrom($metadata);
            if (! $phoneNumberDesc instanceof PhoneNumberDesc) {
                continue;
            }

            $lengths = array_merge($lengths, $phoneNumberDesc->getPossibleLength());
        }

        $lengths = collect($lengths)
            ->unique()
            ->filter(fn (int $length) => $length > 0)
            ->values()
            ->all();

        if ($lengths === []) {
            throw PhoneNumberLengthException::undefined($this->isoCode);
        }

        return $this->cachedPossibleLengths = $lengths;
    }

    /**
     * Load and group carrier data from libphonenumber into Carrier instances.
     *
     * @return array<string, Carrier>
     */
    protected function loadCarriers(string $locale = 'en_US'): array
    {
        $countryCode = $this->countryCode();
        if ($countryCode === 0) {
            return [];
        }

        $data = (new CarrierDataRepository())->load($countryCode, $locale);
        if ($data === null) {
            return [];
        }

        $countryCodeLen = Str::length((string) $countryCode);

        /** @var array<string, array<string, true>> $grouped */
        $grouped = [];

        foreach ($data as $prefix => $name) {
            if ($name === '') {
                continue;
            }

            $networkCode = Str::substr((string) $prefix, $countryCodeLen);
            if ($networkCode === '') {
                continue;
            }

            $grouped[$name][] = $networkCode;
        }

        $carriers = [];
        foreach ($grouped as $name => $networkCodes) {
            sort($networkCodes);
            $carriers[$name] = new Carrier($this, $name, $networkCodes);
        }

        ksort($carriers);

        return $carriers;
    }
}
