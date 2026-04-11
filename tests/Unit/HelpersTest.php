<?php

declare(strict_types=1);

use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;
use MoonlyDays\MNO\Exceptions\InvalidPhoneNumberException;
use MoonlyDays\MNO\Values\PhoneNumber;

it('phoneNumber() global helper returns a PhoneNumber', function (): void {
    $util = PhoneNumberUtil::getInstance();
    $example = $util->getExampleNumberForType('TZ', PhoneNumberType::MOBILE);
    $e164 = $util->format($example, PhoneNumberFormat::E164);

    $phone = phoneNumber($e164);

    expect($phone)->toBeInstanceOf(PhoneNumber::class)
        ->and($phone->e164())->toBe($e164);
});

it('phoneNumber() forwards the region argument', function (): void {
    $util = PhoneNumberUtil::getInstance();
    $example = $util->getExampleNumberForType('GB', PhoneNumberType::MOBILE);
    $national = $util->format($example, PhoneNumberFormat::NATIONAL);

    expect(phoneNumber($national, 'GB')->countryIso())->toBe('GB');
});

it('phoneNumber() throws on invalid input', function (): void {
    phoneNumber('not-a-number');
})->throws(InvalidPhoneNumberException::class);
