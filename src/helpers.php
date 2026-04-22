<?php

declare(strict_types=1);

use MoonlyDays\MNO\Values\Msisdn;

if (! function_exists('msisdn')) {
    function msisdn(string $msisdn, ?string $region = null): Msisdn
    {
        return Msisdn::from($msisdn, $region);
    }
}
