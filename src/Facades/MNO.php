<?php

declare(strict_types=1);

namespace MoonlyDays\MNO\Facades;

use Illuminate\Support\Facades\Facade;
use MoonlyDays\MNO\Enums\NumberType;
use MoonlyDays\MNO\MnoService;
use MoonlyDays\MNO\Values\Carrier;
use MoonlyDays\MNO\Values\Country;
use MoonlyDays\MNO\Values\PhoneNumber;

/**
 * @method static string countryIsoCode()
 * @method static Country country(?string $code = null)
 * @method static int countryCode()
 * @method static string carrierName()
 * @method static array<string> networkCodes()
 * @method static Carrier carrier(?string $country = null, ?string $name = null)
 * @method static int minLength()
 * @method static int maxLength()
 * @method static PhoneNumber|null exampleNumber()
 * @method static array<NumberType> numberTypes()
 *
 * @see MnoService
 */
class MNO extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return MnoService::class;
    }
}
