<?php

declare(strict_types=1);

use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;
use MoonlyDays\MNO\Rules\PhoneNumberRule;
use MoonlyDays\MNO\Tests\TestCase;
use MoonlyDays\MNO\Values\PhoneNumber;

uses(TestCase::class)->in('Unit', 'Feature');

// Reset PhoneNumberRule/PhoneNumber static state between tests so one
// test's macro or default resolver cannot leak into the next.
uses()->afterEach(function (): void {
    PhoneNumberRule::defaults(null);
    PhoneNumber::flushMacros();
})->in('Unit', 'Feature');

/**
 * Helper: grab a known-valid mobile number (E.164) for a region from libphonenumber.
 */
function mobileExampleFor(string $region): string
{
    $util = PhoneNumberUtil::getInstance();
    $example = $util->getExampleNumberForType($region, PhoneNumberType::MOBILE);

    expect($example)->not->toBeNull();

    return $util->format($example, PhoneNumberFormat::E164);
}
