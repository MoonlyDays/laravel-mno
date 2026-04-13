<?php

declare(strict_types=1);

namespace MoonlyDays\MNO\Faker;

use Faker\Provider\Base;
use Illuminate\Support\Str;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use MoonlyDays\MNO\Facades\MNO;
use MoonlyDays\MNO\Values\PhoneNumber;

class PhoneNumberFaker extends Base
{
    /**
     * Generate a random PhoneNumber value object for the configured MNO.
     */
    public function phoneNumber(): PhoneNumber
    {
        return PhoneNumber::from($this->e164PhoneNumber());
    }

    /**
     * Generate a random phone number in E.164 format (e.g., "+255712345678").
     */
    public function e164PhoneNumber(): string
    {
        $util = PhoneNumberUtil::getInstance();
        $countryCode = MNO::countryCode();
        $region = MNO::countryIsoCode();
        $networkCodes = MNO::networkCodes();
        $maxLength = MNO::maxLength();

        // Shuffle network codes to avoid bias when some codes produce invalid numbers.
        shuffle($networkCodes);

        foreach ($networkCodes as $networkCode) {
            $subscriberLength = $maxLength - Str::length((string) $networkCode);

            $subscriber = '';
            for ($i = 0; $i < $subscriberLength; $i++) {
                $subscriber .= $this->generator->numberBetween(0, 9);
            }

            $number = '+'.$countryCode.$networkCode.$subscriber;

            try {
                $parsed = $util->parse($number, $region);
                if ($util->isValidNumber($parsed)) {
                    return $number;
                }
            } catch (NumberParseException) {
                continue;
            }
        }

        // Fallback: use libphonenumber's example number for the region.
        $example = $util->getExampleNumber($region);

        return $util->format($example, PhoneNumberFormat::E164);
    }

    /**
     * Generate a random phone number in national format (e.g., "0712 345 678").
     */
    public function nationalPhoneNumber(): string
    {
        return $this->phoneNumber()->national();
    }

    /**
     * Generate a random phone number in international format (e.g., "+255 712 345 678").
     */
    public function internationalPhoneNumber(): string
    {
        return $this->phoneNumber()->international();
    }
}
