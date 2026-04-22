<?php

declare(strict_types=1);

use MoonlyDays\MNO\Exceptions\InvalidCarrierException;
use MoonlyDays\MNO\Exceptions\InvalidCountryException;
use MoonlyDays\MNO\Values\Carrier;
use MoonlyDays\MNO\Values\Country;
use MoonlyDays\MNO\Values\Msisdn;

describe('Country::from', function (): void {
    it('builds a Country for a valid ISO code', function (): void {
        $country = Country::from('TZ');

        expect($country->isoCode())->toBe('TZ')
            ->and($country->countryCode())->toBe(255);
    });

    it('uppercases the ISO code', function (): void {
        expect(Country::from('tz')->isoCode())->toBe('TZ');
    });

    it('throws for an unknown ISO code', function (): void {
        Country::from('ZZ');
    })->throws(InvalidCountryException::class);

    it('returns null from tryFrom for an unknown ISO code', function (): void {
        expect(Country::tryFrom('ZZ'))->toBeNull();
    });

    it('returns a Country from tryFrom for a valid ISO code', function (): void {
        expect(Country::tryFrom('GB'))
            ->toBeInstanceOf(Country::class)
            ->and(Country::tryFrom('GB')->countryCode())->toBe(44);
    });
});

describe('Country carriers', function (): void {
    it('lists known carriers for TZ', function (): void {
        $country = Country::from('TZ');
        $carriers = $country->carriers();

        $names = array_map(static fn (Carrier $c): string => $c->name(), $carriers);

        // These are the MNOs shipped in libphonenumber's Tanzania data.
        expect($names)
            ->toContain('Vodacom')
            ->toContain('Airtel')
            ->toContain('Yas');
    });

    it('carrier lookup is case-insensitive', function (): void {
        $country = Country::from('TZ');

        expect($country->carrier('vodacom')->name())->toBe('Vodacom')
            ->and($country->carrier('VODACOM')->name())->toBe('Vodacom');
    });

    it('tryCarrier returns null for an unknown name', function (): void {
        expect(Country::from('TZ')->tryCarrier('Nonexistent'))->toBeNull();
    });

    it('carrier throws InvalidCarrierException for an unknown name', function (): void {
        Country::from('TZ')->carrier('Nonexistent');
    })->throws(InvalidCarrierException::class);

    it('hasCarrier returns true for a known carrier and false otherwise', function (): void {
        $country = Country::from('TZ');

        expect($country->hasCarrier('Vodacom'))->toBeTrue()
            ->and($country->hasCarrier('Nonexistent'))->toBeFalse();
    });

    it('memoizes the carrier list', function (): void {
        $country = Country::from('TZ');

        expect($country->carriers())->toBe($country->carriers());
    });
});

describe('Country metadata passthroughs', function (): void {
    it('exposes supportsCarrierData for regions libphonenumber covers', function (): void {
        expect(Country::from('TZ')->supportsCarrierData())->toBeTrue();
    });

    it('returns an example phone number for the region', function (): void {
        $example = Country::from('TZ')->exampleNumber();

        expect($example)->toBeInstanceOf(Msisdn::class)
            ->and($example->countryIso())->toBe('TZ');
    });

    it('reports mobile-number-portable regions', function (): void {
        expect(Country::from('GB')->isMobileNumberPortable())->toBeTrue();
    });
});

describe('Country::name', function (): void {
    it('returns the English display name by default', function (): void {
        expect(Country::from('TZ')->name())->toBe('Tanzania')
            ->and(Country::from('GB')->name())->toBe('United Kingdom');
    });

    it('honors an explicit display locale', function (): void {
        expect(Country::from('TZ')->name('fr'))->toBe('Tanzanie');
    });

    it('falls back through parent locales when the exact locale is unavailable', function (): void {
        // "en-US" should fall back to "en" since CLDR ships the base English
        // region data rather than a US-specific variant.
        expect(Country::from('TZ')->name('en-US'))->toBe('Tanzania');
    });
});

describe('Country equality and stringification', function (): void {
    it('equals another Country with the same ISO code', function (): void {
        expect(Country::from('TZ')->equals(Country::from('TZ')))->toBeTrue()
            ->and(Country::from('TZ')->equals(Country::from('GB')))->toBeFalse();
    });

    it('stringifies to the ISO code', function (): void {
        expect((string) Country::from('tz'))->toBe('TZ');
    });
});

describe('Country::timezones', function (): void {
    it('returns timezone identifiers for a country', function (): void {
        $country = Country::from('TZ');

        $timezones = $country->timezones();

        expect($timezones)->toBeArray()
            ->and($timezones)->not->toBeEmpty()
            ->and($timezones)->each->toBeString();
    });

    it('does not include Etc/Unknown', function (): void {
        expect(Country::from('TZ')->timezones())->not->toContain('Etc/Unknown');
    });

    it('returns Africa/Dar_es_Salaam for Tanzania', function (): void {
        expect(Country::from('TZ')->timezones())->toContain('Africa/Dar_es_Salaam');
    });

    it('returns multiple timezones for countries spanning zones', function (): void {
        $timezones = Country::from('US')->timezones();

        expect(count($timezones))->toBeGreaterThan(1);
    });
});

describe('Country::timezone', function (): void {
    it('returns the primary timezone as a string', function (): void {
        $country = Country::from('TZ');

        expect($country->timezone())->toBeString()
            ->and($country->timezone())->toBe($country->timezones()[0]);
    });
});
