<?php

declare(strict_types=1);

use MoonlyDays\MNO\Values\PhoneNumber;

describe('PhoneNumber::timezones', function (): void {
    it('returns timezone identifiers for a valid number', function (): void {
        $phone = PhoneNumber::from(mobileExampleFor('TZ'));

        $timezones = $phone->timezones();

        expect($timezones)->toBeArray()
            ->and($timezones)->not->toBeEmpty()
            ->and($timezones)->each->toBeString();
    });

    it('does not include Etc/Unknown', function (): void {
        $phone = PhoneNumber::from(mobileExampleFor('TZ'));

        expect($phone->timezones())->not->toContain('Etc/Unknown');
    });

    it('returns Africa/Dar_es_Salaam for a Tanzanian number', function (): void {
        $phone = PhoneNumber::from(mobileExampleFor('TZ'));

        expect($phone->timezones())->toContain('Africa/Dar_es_Salaam');
    });

    it('returns Europe/London for a UK number', function (): void {
        $phone = PhoneNumber::from(mobileExampleFor('GB'));

        expect($phone->timezones())->toContain('Europe/London');
    });
});

describe('PhoneNumber::timezone', function (): void {
    it('returns the primary timezone as a string', function (): void {
        $phone = PhoneNumber::from(mobileExampleFor('TZ'));

        expect($phone->timezone())->toBeString()
            ->and($phone->timezone())->toBe($phone->timezones()[0]);
    });
});
