<?php

declare(strict_types=1);

namespace MoonlyDays\MNO\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use MoonlyDays\MNO\Facades\MNO;
use MoonlyDays\MNO\Values\Msisdn;

class MsisdnRule implements ValidationRule
{
    /**
     * @var (callable(): static)|null
     */
    protected static $defaultResolver;

    /** @var array<string> */
    protected array $countries = [];

    /** @var array<string> */
    protected array $networkCodes = [];

    protected ?int $minLength = null;

    protected ?int $maxLength = null;

    /**
     * Get the default rule instance, populated from config.
     */
    public static function default(): self
    {
        if (static::$defaultResolver !== null) {
            return call_user_func(static::$defaultResolver);
        }

        return (new self())
            ->country(MNO::countryIsoCode())
            ->networkCodes(MNO::networkCodes())
            ->minLength(MNO::minLength())
            ->maxLength(MNO::maxLength());
    }

    /**
     * Set the default callback to resolve the rule instance.
     *
     * @param  (callable(): static)|null  $resolver
     */
    public static function defaults(?callable $resolver): void
    {
        static::$defaultResolver = $resolver;
    }

    /**
     * Set the allowed country codes (ISO 3166-1 alpha-2).
     */
    public function country(array|string $country, string ...$countries): static
    {
        $this->countries = Arr::flatten([$country, ...$countries]);

        return $this;
    }

    /**
     * Set the allowed network codes (NDC prefixes).
     */
    public function networkCodes(array|string $code, string ...$codes): static
    {
        $this->networkCodes = Arr::flatten([$code, ...$codes]);

        return $this;
    }

    /**
     * Set the minimum national number length.
     */
    public function minLength(int $length): static
    {
        $this->minLength = $length;

        return $this;
    }

    /**
     * Set the maximum national number length.
     */
    public function maxLength(int $length): static
    {
        $this->maxLength = $length;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('validation.msisdn.invalid')->translate();

            return;
        }

        $phoneNumber = Msisdn::tryFrom($value);
        if (! $phoneNumber instanceof Msisdn) {
            $fail('validation.msisdn.invalid')->translate();

            return;
        }

        if ($this->countries !== [] && collect($this->countries)->doesntContain($phoneNumber->countryIso())) {
            $fail('validation.msisdn.country')->translate();

            return;
        }

        $nationalNumber = $phoneNumber->nationalNumber();

        if ($this->minLength !== null && Str::length($nationalNumber) < $this->minLength) {
            $fail('validation.msisdn.min_length')->translate([
                'min' => $this->minLength,
            ]);

            return;
        }

        if ($this->maxLength !== null && Str::length($nationalNumber) > $this->maxLength) {
            $fail('validation.msisdn.max_length')->translate([
                'max' => $this->maxLength,
            ]);

            return;
        }

        if ($this->networkCodes !== [] && ! Str::startsWith($nationalNumber, $this->networkCodes)) {
            $fail('validation.msisdn.network_code')->translate();

            return;
        }
    }
}
