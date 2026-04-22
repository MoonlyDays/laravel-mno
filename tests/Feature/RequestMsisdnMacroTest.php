<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;
use MoonlyDays\MNO\Values\Msisdn;

describe('Request::msisdn macro', function (): void {
    it('returns a Msisdn for a valid value on the request', function (): void {
        $e164 = mobileExampleFor('TZ');
        $request = Request::create('/', 'GET', ['phone' => $e164]);

        $phone = $request->msisdn('phone');

        expect($phone)->toBeInstanceOf(Msisdn::class)
            ->and($phone->e164())->toBe($e164);
    });

    it('resolves national-format input using the configured MNO country', function (): void {
        config()->set('mno.country', 'TZ');

        $util = PhoneNumberUtil::getInstance();
        $example = $util->getExampleNumberForType('TZ', PhoneNumberType::MOBILE);
        $national = $util->format($example, PhoneNumberFormat::NATIONAL);
        $expected = $util->format($example, PhoneNumberFormat::E164);

        $request = Request::create('/', 'GET', ['phone' => $national]);

        expect($request->msisdn('phone')->e164())->toBe($expected);
    });

    it('returns null by default when the key is missing', function (): void {
        $request = Request::create('/', 'GET');

        expect($request->msisdn('phone'))->toBeNull();
    });

    it('returns null by default when the key is present but empty', function (): void {
        $request = Request::create('/', 'GET', ['phone' => '']);

        expect($request->msisdn('phone'))->toBeNull();
    });

    it('returns the provided default when the key is not filled', function (): void {
        $request = Request::create('/', 'GET');
        $fallback = Msisdn::from(mobileExampleFor('GB'));

        expect($request->msisdn('phone', $fallback))->toBe($fallback);
    });

    it('resolves a closure default lazily when the key is not filled', function (): void {
        $request = Request::create('/', 'GET');
        $fallback = Msisdn::from(mobileExampleFor('GB'));

        $result = $request->msisdn('phone', fn () => $fallback);

        expect($result)->toBe($fallback);
    });

    it('returns the default when the value is present but not a valid phone number', function (): void {
        $request = Request::create('/', 'GET', ['phone' => 'not-a-number']);

        expect($request->msisdn('phone', 'fallback'))->toBe('fallback');
    });

    it('returns null when the value is present but invalid and no default is given', function (): void {
        $request = Request::create('/', 'GET', ['phone' => 'not-a-number']);

        expect($request->msisdn('phone'))->toBeNull();
    });

    it('reads values from the JSON body', function (): void {
        $e164 = mobileExampleFor('TZ');
        $request = Request::create(
            '/',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['phone' => $e164]),
        );

        expect($request->msisdn('phone'))->toBeInstanceOf(Msisdn::class)
            ->and($request->msisdn('phone')->e164())->toBe($e164);
    });
});
