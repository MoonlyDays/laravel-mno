<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use libphonenumber\PhoneNumberToCarrierMapper;
use libphonenumber\PhoneNumberUtil;
use MoonlyDays\MNO\MnoService;
use MoonlyDays\MNO\Rules\MsisdnRule;

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

it('registers the Rule::msisdn macro', function (): void {
    expect(Rule::msisdn())->toBeInstanceOf(MsisdnRule::class);
});

it('registers the Request::msisdn macro', function (): void {
    expect(Request::hasMacro('msisdn'))->toBeTrue();
});
