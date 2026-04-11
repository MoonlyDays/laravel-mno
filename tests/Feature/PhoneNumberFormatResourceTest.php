<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use MoonlyDays\MNO\MnoService;
use MoonlyDays\MNO\Resources\PhoneNumberFormatResource;

describe('PhoneNumberFormatResource', function (): void {
    beforeEach(function (): void {
        config()->set('mno.country', 'TZ');
        config()->set('mno.network_codes', ['74', '75', '76']);
        config()->set('mno.validation.min_length', 9);
        config()->set('mno.validation.max_length', 9);
    });

    it('exposes operator metadata via toAttributes', function (): void {
        $service = app(MnoService::class);
        $resource = new PhoneNumberFormatResource($service);

        $attributes = $resource->toAttributes(Request::create('/'));

        expect($attributes)->toBe([
            'countryCode' => 255,
            'country' => 'TZ',
            'minLength' => 9,
            'maxLength' => 9,
            'networkCodes' => ['74', '75', '76'],
        ]);
    });

    it('can be instantiated through the make() factory', function (): void {
        $resource = PhoneNumberFormatResource::make();

        expect($resource)->toBeInstanceOf(PhoneNumberFormatResource::class);
    });

    it('resolves to an array containing operator metadata', function (): void {
        $service = app(MnoService::class);
        $resource = new PhoneNumberFormatResource($service);

        $array = $resource->resolve(Request::create('/'));

        expect($array)
            ->toHaveKey('countryCode', 255)
            ->toHaveKey('country', 'TZ')
            ->toHaveKey('minLength', 9)
            ->toHaveKey('maxLength', 9)
            ->toHaveKey('networkCodes', ['74', '75', '76']);
    });
});
