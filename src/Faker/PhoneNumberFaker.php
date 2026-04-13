<?php

declare(strict_types=1);

namespace MoonlyDays\MNO\Faker;

use Faker\Provider\Base;
use Illuminate\Support\Arr;
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
        $countryCode = MNO::countryCode();
        $networkCode = Arr::random(MNO::networkCodes());
        $subscriberLength = MNO::maxLength() - strlen($networkCode);

        $subscriber = '';
        for ($i = 0; $i < $subscriberLength; $i++) {
            $subscriber .= $this->generator->numberBetween(0, 9);
        }

        return '+' . $countryCode . $networkCode . $subscriber;
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
