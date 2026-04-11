<?php

declare(strict_types=1);

use MoonlyDays\MNO\PhoneNumber;

if (! function_exists('phoneNumber')) {
    function phoneNumber(string $phoneNumber, ?string $region = null): PhoneNumber
    {
        return PhoneNumber::from($phoneNumber, $region);
    }
}
