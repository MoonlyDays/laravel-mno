<?php

declare(strict_types=1);

namespace MoonlyDays\MNO\Faker;

use Faker\Provider\Base;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use MoonlyDays\MNO\Facades\MNO;
use MoonlyDays\MNO\Values\PhoneNumber;
use RuntimeException;

class PhoneNumberFaker extends Base
{
    /**
     * Generate a random PhoneNumber value object for the configured MNO.
     */
    public function phoneNumberObject(): PhoneNumber
    {
        return PhoneNumber::from($this->e164PhoneNumber());
    }

    /**
     * Generate a random phone number string (E.164) for the configured MNO.
     *
     * Overrides Faker's built-in phoneNumber() to emit a number the MNO accepts.
     */
    public function phoneNumber(): string
    {
        return $this->e164PhoneNumber();
    }

    /**
     * Generate a random phone number in E.164 format (e.g., "+255712345678").
     */
    public function e164PhoneNumber(): string
    {
        $util = PhoneNumberUtil::getInstance();
        $countryCode = MNO::countryCode();
        $region = MNO::countryIsoCode();
        $networkCodes = Arr::shuffle(MNO::networkCodes());
        $maxLength = MNO::maxLength();

        foreach ($networkCodes as $networkCode) {
            $subscriberLength = $maxLength - Str::length((string) $networkCode);

            for ($attempt = 0; $attempt < 5; $attempt++) {
                $subscriber = '';
                for ($i = 0; $i < $subscriberLength; $i++) {
                    $subscriber .= $this->generator->numberBetween(0, 9);
                }

                $number = '+'.$countryCode.$networkCode.$subscriber;

                try {
                    if ($util->isValidNumber($util->parse($number, $region))) {
                        return $number;
                    }
                } catch (NumberParseException) {
                    break;
                }
            }
        }

        $example = $util->getExampleNumber($region);
        if ($example === null) {
            throw new RuntimeException(
                "Unable to generate a phone number: no example number for region [{$region}]."
            );
        }

        return $util->format($example, PhoneNumberFormat::E164);
    }

    /**
     * Generate a random phone number in national format (e.g., "0712 345 678").
     */
    public function nationalPhoneNumber(): string
    {
        return $this->phoneNumberObject()->national();
    }

    /**
     * Generate a random phone number in international format (e.g., "+255 712 345 678").
     */
    public function internationalPhoneNumber(): string
    {
        return $this->phoneNumberObject()->international();
    }
}
