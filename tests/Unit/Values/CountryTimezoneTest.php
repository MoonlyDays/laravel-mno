<?php

declare(strict_types=1);

use MoonlyDays\MNO\Values\Country;

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
