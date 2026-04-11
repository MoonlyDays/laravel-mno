<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;
use MoonlyDays\MNO\Values\PhoneNumber;

function requestMobileExample(string $region = 'TZ'): string
{
    $util = PhoneNumberUtil::getInstance();
    $example = $util->getExampleNumberForType($region, PhoneNumberType::MOBILE);

    return $util->format($example, PhoneNumberFormat::E164);
}

describe('Request::phoneNumber macro', function (): void {
    it('returns a PhoneNumber for a valid value on the request', function (): void {
        $e164 = requestMobileExample('TZ');
        $request = Request::create('/', 'GET', ['phone' => $e164]);

        $phone = $request->phoneNumber('phone');

        expect($phone)->toBeInstanceOf(PhoneNumber::class)
            ->and($phone->e164())->toBe($e164);
    });

    it('resolves national-format input using the configured MNO country', function (): void {
        config()->set('mno.country', 'TZ');

        $util = PhoneNumberUtil::getInstance();
        $example = $util->getExampleNumberForType('TZ', PhoneNumberType::MOBILE);
        $national = $util->format($example, PhoneNumberFormat::NATIONAL);
        $expected = $util->format($example, PhoneNumberFormat::E164);

        $request = Request::create('/', 'GET', ['phone' => $national]);

        expect($request->phoneNumber('phone')->e164())->toBe($expected);
    });

    it('returns null by default when the key is missing', function (): void {
        $request = Request::create('/', 'GET');

        expect($request->phoneNumber('phone'))->toBeNull();
    });

    it('returns null by default when the key is present but empty', function (): void {
        $request = Request::create('/', 'GET', ['phone' => '']);

        expect($request->phoneNumber('phone'))->toBeNull();
    });

    it('returns the provided default when the key is not filled', function (): void {
        $request = Request::create('/', 'GET');
        $fallback = PhoneNumber::from(requestMobileExample('GB'));

        expect($request->phoneNumber('phone', $fallback))->toBe($fallback);
    });

    it('resolves a closure default lazily when the key is not filled', function (): void {
        $request = Request::create('/', 'GET');
        $fallback = PhoneNumber::from(requestMobileExample('GB'));

        $result = $request->phoneNumber('phone', fn () => $fallback);

        expect($result)->toBe($fallback);
    });

    it('returns the default when the value is present but not a valid phone number', function (): void {
        $request = Request::create('/', 'GET', ['phone' => 'not-a-number']);

        expect($request->phoneNumber('phone', 'fallback'))->toBe('fallback');
    });

    it('returns null when the value is present but invalid and no default is given', function (): void {
        $request = Request::create('/', 'GET', ['phone' => 'not-a-number']);

        expect($request->phoneNumber('phone'))->toBeNull();
    });

    it('reads values from the JSON body', function (): void {
        $e164 = requestMobileExample('TZ');
        $request = Request::create(
            '/',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['phone' => $e164]),
        );

        expect($request->phoneNumber('phone'))->toBeInstanceOf(PhoneNumber::class)
            ->and($request->phoneNumber('phone')->e164())->toBe($e164);
    });
});
