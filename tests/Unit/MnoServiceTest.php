<?php

declare(strict_types=1);

use libphonenumber\PhoneNumberUtil;
use MoonlyDays\MNO\Enums\NumberType;
use MoonlyDays\MNO\Exceptions\InvalidCountryException;
use MoonlyDays\MNO\Facades\MNO;
use MoonlyDays\MNO\MnoService;
use MoonlyDays\MNO\Values\PhoneNumber;

dataset('countries', fn () => PhoneNumberUtil::getInstance()->getSupportedRegions());

describe('MnoService config accessors', function (): void {
    it('returns the configured operator name', function (): void {
        config()->set('mno.name', 'Acme Telecom');

        expect(app(MnoService::class)->carrierName())->toBe('Acme Telecom');
    });

    it('returns the configured country', function (): void {
        config()->set('mno.country', 'KE');

        expect(app(MnoService::class)->countryIsoCode())->toBe('KE');
    });

    it('returns the calling code for the configured country', function (): void {
        config()->set('mno.country', 'TZ');

        expect(app(MnoService::class)->countryCode())->toBe(255);
    });

    it('returns the configured network codes', function (): void {
        config()->set('mno.network_codes', ['74', '75', '76']);

        expect(app(MnoService::class)->networkCodes())->toBe(['74', '75', '76']);
    });

    it('returns the configured number types', function (): void {
        config()->set('mno.number_types', [NumberType::FixedLine, NumberType::Mobile]);

        expect(app(MnoService::class)->numberTypes())
            ->toBe([NumberType::FixedLine, NumberType::Mobile]);
    });
});

describe('MnoService length resolution', function (): void {
    it('uses explicit min/max length from config when set', function (): void {
        config()->set('mno.min_length', 7);
        config()->set('mno.max_length', 9);

        $service = app(MnoService::class);

        expect($service->minLength())->toBe(7)
            ->and($service->maxLength())->toBe(9);
    });

    it('infers the length from libphonenumber metadata for the configured country', function (): void {
        config()->set('mno.country', 'TZ');
        config()->set('mno.min_length', null);
        config()->set('mno.max_length', null);

        app()->forgetInstance(MnoService::class);
        $service = app(MnoService::class);

        expect($service->minLength())->toBeInt()
            ->and($service->minLength())->toBeGreaterThan(0)
            ->and($service->maxLength())->toBeInt()
            ->and($service->maxLength())->toBeGreaterThanOrEqual($service->minLength());
    });

    it('throws when no country is configured and length must be inferred', function (): void {
        config()->set('mno.country', '');
        config()->set('mno.min_length', null);
        config()->set('mno.max_length', null);

        app(MnoService::class)->maxLength();
    })->throws(InvalidCountryException::class);

    it('throws for an unknown country code during inference', function (): void {
        config()->set('mno.country', 'ZZ');
        config()->set('mno.min_length', null);
        config()->set('mno.max_length', null);

        app(MnoService::class)->maxLength();
    })->throws(InvalidCountryException::class);

    it('infers mobile min/max length correctly for every supported region', function (string $region): void {
        config()->set('mno.country', $region);
        config()->set('mno.min_length', null);
        config()->set('mno.max_length', null);

        $service = app(MnoService::class);

        $minLength = $service->minLength();
        $maxLength = $service->maxLength();

        expect($minLength)->toBeInt()->and($maxLength)->toBeInt();
    })->with('countries');
});

describe('MnoService::exampleNumber', function (): void {
    it('returns a PhoneNumber for a known country', function (): void {
        config()->set('mno.country', 'TZ');

        $example = app(MnoService::class)->exampleNumber();

        expect($example)->toBeInstanceOf(PhoneNumber::class)
            ->and($example->countryIso())->toBe('TZ');
    });

    it('returns null for an unknown country', function (): void {
        config()->set('mno.country', 'ZZ');

        expect(app(MnoService::class)->exampleNumber())->toBeNull();
    });
});

describe('MNO facade', function (): void {
    it('is bound as a singleton', function (): void {
        expect(app(MnoService::class))->toBe(app(MnoService::class))
            ->and(app('mno'))->toBe(app(MnoService::class));
    });

    it('proxies to the underlying MnoService', function (): void {
        config()->set('mno.country', 'TZ');
        config()->set('mno.name', 'Facade Test');

        expect(MNO::countryIsoCode())->toBe('TZ')
            ->and(MNO::carrierName())->toBe('Facade Test')
            ->and(MNO::countryCode())->toBe(255);
    });
});
