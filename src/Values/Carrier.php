<?php

declare(strict_types=1);

namespace MoonlyDays\MNO\Values;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use MoonlyDays\MNO\Exceptions\InvalidCarrierException;
use MoonlyDays\MNO\Exceptions\InvalidCountryException;
use Stringable;

class Carrier implements Stringable
{
    use Macroable;
    use Tappable;

    /**
     * @param  array<int, string>  $networkCodes  NDC portion only (no country calling code)
     */
    public function __construct(
        protected Country $country,
        protected string $name,
        protected array $networkCodes = [],
    ) {}

    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * Look up a carrier by country and name.
     *
     * @param  Country|string  $country  A Country instance or an ISO 3166-1 alpha-2 code
     *
     * @throws InvalidCarrierException
     */
    public static function from(Country|string $country, string $name): self
    {
        if (is_string($country)) {
            $country = Country::from($country);
        }

        return $country->carrier($name);
    }

    /**
     * Try to look up a carrier; returns null on miss.
     */
    public static function tryFrom(Country|string $country, string $name): ?self
    {
        try {
            return self::from($country, $name);
        } catch (InvalidCarrierException|InvalidCountryException) {
            return null;
        }
    }

    /**
     * Get the country this carrier operates in.
     */
    public function country(): Country
    {
        return $this->country;
    }

    /**
     * Get the display name of the carrier in its country's locale.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Get the network codes (NDCs) allocated to this carrier.
     *
     * @return array<int, string>
     */
    public function networkCodes(): array
    {
        return $this->networkCodes;
    }

    /**
     * Get the full prefixes (country calling code + NDC) allocated to this carrier.
     *
     * @return array<int, string>
     */
    public function prefixes(): array
    {
        $callingCode = (string) $this->country->countryCode();

        return Arr::map(
            $this->networkCodes,
            fn (string $ndc): string => $callingCode.$ndc,
        );
    }

    /**
     * Count the distinct prefix blocks allocated to this carrier.
     */
    public function networkCodeCount(): int
    {
        return count($this->networkCodes);
    }

    /**
     * Determine whether the given phone number belongs to this carrier.
     *
     * Matches by country and national-number prefix. In mobile-number-portable
     * regions this reflects the *original* allocation, not the subscriber's
     * current network — use $carrier->country()->isMobileNumberPortable() to
     * decide how much to trust the result.
     */
    public function matches(Msisdn $number): bool
    {
        if ($number->countryIso() !== $this->country->isoCode()) {
            return false;
        }

        $nationalNumber = $number->nationalNumber();

        foreach ($this->networkCodes as $code) {
            if (Str::startsWith($nationalNumber, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the given network code belongs to this carrier.
     */
    public function owns(string $networkCode): bool
    {
        return in_array($networkCode, $this->networkCodes, true);
    }

    /**
     * Determine whether two Carrier instances represent the same carrier.
     *
     * Equality is by (country, name). Two carriers constructed in different
     * locales will not be equal even if they refer to the same real-world
     * operator.
     */
    public function equals(self $other): bool
    {
        return $this->country->equals($other->country)
            && strcasecmp($this->name, $other->name) === 0;
    }
}
