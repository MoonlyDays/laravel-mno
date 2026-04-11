<?php

declare(strict_types=1);

use MoonlyDays\MNO\Values\PhoneNumber;

if (! function_exists('phoneNumber')) {
    function phoneNumber(string $phoneNumber, ?string $region = null): PhoneNumber
    {
        return PhoneNumber::from($phoneNumber, $region);
    }
}
