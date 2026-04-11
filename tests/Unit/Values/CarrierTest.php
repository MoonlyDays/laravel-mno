<?php

declare(strict_types=1);

use MoonlyDays\MNO\Exceptions\InvalidCarrierException;
use MoonlyDays\MNO\Values\Carrier;
use MoonlyDays\MNO\Values\Country;
use MoonlyDays\MNO\Values\PhoneNumber;

describe('Carrier construction', function (): void {
    it('can be looked up via Carrier::from with an ISO string', function (): void {
        $carrier = Carrier::from('TZ', 'Vodacom');

        expect($carrier)->toBeInstanceOf(Carrier::class)
            ->and($carrier->name())->toBe('Vodacom')
            ->and($carrier->country()->isoCode())->toBe('TZ');
    });

    it('can be looked up via Carrier::from with a Country instance', function (): void {
        $country = Country::from('TZ');
        $carrier = Carrier::from($country, 'Vodacom');

        expect($carrier->country())->toBe($country);
    });

    it('throws InvalidCarrierException for an unknown carrier via from', function (): void {
        Carrier::from('TZ', 'Nonexistent');
    })->throws(InvalidCarrierException::class);

    it('returns null from tryFrom for an unknown carrier', function (): void {
        expect(Carrier::tryFrom('TZ', 'Nonexistent'))->toBeNull();
    });

    it('returns null from tryFrom for an unknown country', function (): void {
        expect(Carrier::tryFrom('ZZ', 'Vodacom'))->toBeNull();
    });
});

describe('Carrier properties', function (): void {
    it('exposes the full network code list for TZ Vodacom', function (): void {
        $carrier = Carrier::from('TZ', 'Vodacom');

        // Upstream libphonenumber data for Vodacom Tanzania:
        // 25574, 25575, 25576, 25579 → NDCs 74, 75, 76, 79.
        expect($carrier->networkCodes())
            ->toEqualCanonicalizing(['74', '75', '76', '79'])
            ->and($carrier->networkCodeCount())->toBe(4);
    });

    it('derives prefixes by prepending the calling code', function (): void {
        $carrier = Carrier::from('TZ', 'Vodacom');

        expect($carrier->prefixes())
            ->toEqualCanonicalizing(['25574', '25575', '25576', '25579']);
    });
});

describe('Carrier::matches', function (): void {
    it('matches a Vodacom TZ number', function (): void {
        $carrier = Carrier::from('TZ', 'Vodacom');
        $number = PhoneNumber::from('+255745123456');

        expect($carrier->matches($number))->toBeTrue();
    });

    it('does not match a number from a different carrier in the same country', function (): void {
        $vodacom = Carrier::from('TZ', 'Vodacom');
        $airtelNumber = PhoneNumber::from('+255685123456'); // 68 belongs to Airtel in TZ

        expect($vodacom->matches($airtelNumber))->toBeFalse();
    });

    it('does not match a number from a different country', function (): void {
        $carrier = Carrier::from('TZ', 'Vodacom');
        $ukNumber = PhoneNumber::from('+447400123456');

        expect($carrier->matches($ukNumber))->toBeFalse();
    });
});

describe('Carrier::owns', function (): void {
    it('returns true for a network code the carrier owns', function (): void {
        $carrier = Carrier::from('TZ', 'Vodacom');

        expect($carrier->owns('74'))->toBeTrue()
            ->and($carrier->owns('79'))->toBeTrue();
    });

    it('returns false for a network code the carrier does not own', function (): void {
        $carrier = Carrier::from('TZ', 'Vodacom');

        expect($carrier->owns('68'))->toBeFalse()
            ->and($carrier->owns('99'))->toBeFalse();
    });
});

describe('Carrier equality and stringification', function (): void {
    it('equals another Carrier with the same country and name', function (): void {
        expect(Carrier::from('TZ', 'Vodacom')->equals(Carrier::from('TZ', 'Vodacom')))
            ->toBeTrue();
    });

    it('does not equal a carrier with the same name in a different country', function (): void {
        $tz = Carrier::from('TZ', 'Vodacom');
        $gbVodafone = new Carrier(Country::from('GB'), 'Vodacom');

        expect($tz->equals($gbVodafone))->toBeFalse();
    });

    it('is case-insensitive when comparing names', function (): void {
        $a = new Carrier(Country::from('TZ'), 'Vodacom', ['74']);
        $b = new Carrier(Country::from('TZ'), 'VODACOM', ['74']);

        expect($a->equals($b))->toBeTrue();
    });

    it('stringifies to the carrier name', function (): void {
        expect((string) Carrier::from('TZ', 'Vodacom'))->toBe('Vodacom');
    });
});
