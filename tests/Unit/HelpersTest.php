<?php

declare(strict_types=1);

use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;
use MoonlyDays\MNO\Exceptions\InvalidMsisdnException;
use MoonlyDays\MNO\Values\Msisdn;

it('msisdn() global helper returns a Msisdn', function (): void {
    $util = PhoneNumberUtil::getInstance();
    $example = $util->getExampleNumberForType('TZ', PhoneNumberType::MOBILE);
    $e164 = $util->format($example, PhoneNumberFormat::E164);

    $phone = msisdn($e164);

    expect($phone)->toBeInstanceOf(Msisdn::class)
        ->and($phone->e164())->toBe($e164);
});

it('msisdn() forwards the region argument', function (): void {
    $util = PhoneNumberUtil::getInstance();
    $example = $util->getExampleNumberForType('GB', PhoneNumberType::MOBILE);
    $national = $util->format($example, PhoneNumberFormat::NATIONAL);

    expect(msisdn($national, 'GB')->countryIso())->toBe('GB');
});

it('msisdn() throws on invalid input', function (): void {
    msisdn('not-a-number');
})->throws(InvalidMsisdnException::class);
