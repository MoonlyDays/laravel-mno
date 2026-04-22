<?php

declare(strict_types=1);

namespace MoonlyDays\MNO\Faker;

use Faker\Provider\Base;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumber as BasePhoneNumber;
use libphonenumber\PhoneNumberUtil;
use MoonlyDays\MNO\Facades\MNO;
use MoonlyDays\MNO\Values\Msisdn;
use RuntimeException;

class MsisdnFaker extends Base
{
    private const MAX_ATTEMPTS_PER_NETWORK_CODE = 5;

    /**
     * @throws NumberParseException
     * @throws RuntimeException
     */
    public function msisdn(): Msisdn
    {
        return new Msisdn($this->generateMsisdn());
    }

    /**
     * @throws NumberParseException
     * @throws RuntimeException
     */
    protected function generateMsisdn(): BasePhoneNumber
    {
        $util = app(PhoneNumberUtil::class);
        $countryCode = MNO::countryCode();
        $region = MNO::countryIsoCode();
        $networkCodes = Arr::shuffle(MNO::networkCodes());
        $minLength = MNO::minLength();
        $maxLength = MNO::maxLength();

        foreach ($networkCodes as $networkCode) {
            $networkCodeLength = Str::length((string) $networkCode);

            if ($networkCodeLength >= $maxLength) {
                continue;
            }

            $nsnLength = $this->generator->numberBetween(
                max($minLength, $networkCodeLength + 1),
                $maxLength,
            );

            $subscriberLength = $nsnLength - $networkCodeLength;
            for ($attempt = 0; $attempt < self::MAX_ATTEMPTS_PER_NETWORK_CODE; $attempt++) {
                $subscriber = '';
                for ($i = 0; $i < $subscriberLength; $i++) {
                    $subscriber .= $this->generator->numberBetween(0, 9);
                }

                $parsed = $util->parse('+'.$countryCode.$networkCode.$subscriber, $region);

                if ($util->isValidNumber($parsed)) {
                    return $parsed;
                }
            }
        }

        throw new RuntimeException(
            "Unable to generate a valid phone number for region [{$region}] within the configured MNO constraints."
        );
    }
}
