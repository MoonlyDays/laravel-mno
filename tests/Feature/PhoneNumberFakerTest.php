<?php

declare(strict_types=1);

use Faker\Generator as FakerGenerator;
use MoonlyDays\MNO\Faker\MsisdnFaker;
use MoonlyDays\MNO\Values\Msisdn;

it('registers the faker provider via the service provider', function (): void {
    $faker = app(FakerGenerator::class);

    $providers = array_map('get_class', $faker->getProviders());

    expect($providers)->toContain(MsisdnFaker::class);
});

it('generates a valid PhoneNumber value object', function (): void {
    $faker = app(FakerGenerator::class);

    $phone = $faker->phoneNumberObject();

    expect($phone)->toBeInstanceOf(Msisdn::class)
        ->and($phone->countryIso())->toBe('TZ');
});

it('generates an E.164 formatted string', function (): void {
    $faker = app(FakerGenerator::class);

    $e164 = $faker->e164PhoneNumber();

    expect($e164)->toStartWith('+255')
        ->and(Msisdn::tryFrom($e164))->not->toBeNull();
});

it('generates a national formatted string', function (): void {
    $faker = app(FakerGenerator::class);

    $national = $faker->nationalPhoneNumber();

    expect($national)->toBeString()
        ->and($national)->toStartWith('0');
});

it('generates an international formatted string', function (): void {
    $faker = app(FakerGenerator::class);

    $international = $faker->internationalPhoneNumber();

    expect($international)->toBeString()
        ->and($international)->toStartWith('+255');
});

it('generates different numbers on successive calls', function (): void {
    $faker = app(FakerGenerator::class);

    $numbers = collect(range(1, 10))->map(fn () => $faker->e164PhoneNumber());

    expect($numbers->unique()->count())->toBeGreaterThan(1);
});
