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

it('generates a valid Msisdn value object', function (): void {
    $faker = app(FakerGenerator::class);

    $phone = $faker->msisdn();

    expect($phone)->toBeInstanceOf(Msisdn::class)
        ->and($phone->countryIso())->toBe('TZ')
        ->and($phone->e164())->toStartWith('+255');
});

it('generates different numbers on successive calls', function (): void {
    $faker = app(FakerGenerator::class);

    $numbers = collect(range(1, 10))->map(fn () => $faker->msisdn()->e164());

    expect($numbers->unique()->count())->toBeGreaterThan(1);
});
