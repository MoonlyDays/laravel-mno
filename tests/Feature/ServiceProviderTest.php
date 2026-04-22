<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use libphonenumber\PhoneNumberToCarrierMapper;
use libphonenumber\PhoneNumberUtil;
use MoonlyDays\MNO\MnoService;
use MoonlyDays\MNO\Rules\PhoneNumberRule;

it('publishes the operator config under the mno namespace', function (): void {
    expect(config('mno.country'))->toBe('TZ')
        ->and(config('mno.name'))->toBe('Vodacom');
});

it('registers MnoService as a singleton bound to the "mno" alias', function (): void {
    expect(app('mno'))->toBeInstanceOf(MnoService::class)
        ->and(app('mno'))->toBe(app(MnoService::class));
});

it('binds PhoneNumberUtil to the shared instance', function (): void {
    expect(app(PhoneNumberUtil::class))->toBe(PhoneNumberUtil::getInstance());
});

it('binds PhoneNumberToCarrierMapper to the shared instance', function (): void {
    expect(app(PhoneNumberToCarrierMapper::class))->toBe(PhoneNumberToCarrierMapper::getInstance());
});

it('registers the Rule::phoneNumber macro', function (): void {
    expect(Rule::phoneNumber())->toBeInstanceOf(PhoneNumberRule::class);
});

it('registers the Request::phoneNumber macro', function (): void {
    expect(Request::hasMacro('phoneNumber'))->toBeTrue();
});

it('registers the Blueprint::phoneNumber macro', function (): void {
    expect(Blueprint::hasMacro('phoneNumber'))->toBeTrue();
});
