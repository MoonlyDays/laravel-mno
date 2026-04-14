<?php

declare(strict_types=1);

namespace MoonlyDays\MNO\Faker;

use Faker\Provider\Base;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use MoonlyDays\MNO\Facades\MNO;
use MoonlyDays\MNO\Values\PhoneNumber;
use RuntimeException;

class PhoneNumberFaker extends Base
{
    /**
     * @throws NumberParseException
     */
    public function phoneNumberObject(): PhoneNumber
    {
        return PhoneNumber::from($this->e164PhoneNumber());
    }

    /**
     * @throws NumberParseException
     */
    public function phoneNumber(): string
    {
        return $this->e164PhoneNumber();
    }

    /**
     * @throws NumberParseException
     */
    public function e164PhoneNumber(): string
    {
        $util = PhoneNumberUtil::getInstance();
        $countryCode = MNO::countryCode();
        $region = MNO::countryIsoCode();
        $networkCodes = Arr::shuffle(MNO::networkCodes());
        $minLength = MNO::minLength();
        $maxLength = MNO::maxLength();

        foreach ($networkCodes as $networkCode) {
            $nsnLength = $this->generator->numberBetween($minLength, $maxLength);
            $subscriberLength = $nsnLength - Str::length((string) $networkCode);

            for ($attempt = 0; $attempt < 5; $attempt++) {
                $subscriber = '';
                for ($i = 0; $i < $subscriberLength; $i++) {
                    $subscriber .= $this->generator->numberBetween(0, 9);
                }

                $number = '+'.$countryCode.$networkCode.$subscriber;

                if ($util->isValidNumber($util->parse($number, $region))) {
                    return $number;
                }
            }
        }

        throw new RuntimeException(
            "Unable to generate a valid phone number for region [{$region}] within the configured MNO constraints."
        );
    }

    /**
     * @throws NumberParseException
     */
    public function nationalPhoneNumber(): string
    {
        return $this->phoneNumberObject()->national();
    }

    /**
     * @throws NumberParseException
     */
    public function internationalPhoneNumber(): string
    {
        return $this->phoneNumberObject()->international();
    }
}
