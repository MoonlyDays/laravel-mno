<?php

declare(strict_types=1);

namespace MoonlyDays\MNO\Facades;

use Illuminate\Support\Facades\Facade;
use MoonlyDays\MNO\Enums\NumberType;
use MoonlyDays\MNO\MnoService;
use MoonlyDays\MNO\PhoneNumber;

/**
 * @method static string country()
 * @method static int countryCode()
 * @method static string name()
 * @method static array<string> networkCodes()
 * @method static string carrierLocale()
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
