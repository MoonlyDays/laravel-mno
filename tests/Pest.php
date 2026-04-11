<?php

declare(strict_types=1);

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
