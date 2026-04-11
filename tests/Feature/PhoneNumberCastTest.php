<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;
use MoonlyDays\MNO\Casts\PhoneNumberCast;
use MoonlyDays\MNO\Exceptions\InvalidPhoneNumberException;
use MoonlyDays\MNO\Values\PhoneNumber;

function castSample(string $region = 'TZ'): string
{
    $util = PhoneNumberUtil::getInstance();
    $example = $util->getExampleNumberForType($region, PhoneNumberType::MOBILE);

    return $util->format($example, PhoneNumberFormat::E164);
}

describe('PhoneNumberCast::get', function (): void {
    it('returns null when the raw value is null', function (): void {
        $cast = new PhoneNumberCast();
        $model = new class extends Model {};

        expect($cast->get($model, 'phone', null, []))->toBeNull();
    });

    it('hydrates a PhoneNumber from a stored E.164 string', function (): void {
        $cast = new PhoneNumberCast();
        $model = new class extends Model {};
        $e164 = castSample();

        $phone = $cast->get($model, 'phone', $e164, []);

        expect($phone)->toBeInstanceOf(PhoneNumber::class)
            ->and($phone->e164())->toBe($e164);
    });

    it('throws on invalid stored data', function (): void {
        $cast = new PhoneNumberCast();
        $model = new class extends Model {};

        $cast->get($model, 'phone', 'garbage', []);
    })->throws(InvalidPhoneNumberException::class);
});

describe('PhoneNumberCast::set', function (): void {
    it('returns null when the incoming value is null', function (): void {
        $cast = new PhoneNumberCast();
        $model = new class extends Model {};

        expect($cast->set($model, 'phone', null, []))->toBeNull();
    });

    it('accepts a PhoneNumber instance and stores its E.164 form', function (): void {
        $cast = new PhoneNumberCast();
        $model = new class extends Model {};
        $phone = PhoneNumber::from(castSample());

        expect($cast->set($model, 'phone', $phone, []))->toBe($phone->e164());
    });

    it('accepts a raw string and normalises it to E.164', function (): void {
        $cast = new PhoneNumberCast();
        $model = new class extends Model {};

        $util = PhoneNumberUtil::getInstance();
        $example = $util->getExampleNumberForType('TZ', PhoneNumberType::MOBILE);
        $national = $util->format($example, PhoneNumberFormat::NATIONAL);
        $expected = $util->format($example, PhoneNumberFormat::E164);

        config()->set('mno.country', 'TZ');

        expect($cast->set($model, 'phone', $national, []))->toBe($expected);
    });

    it('throws on invalid string input', function (): void {
        $cast = new PhoneNumberCast();
        $model = new class extends Model {};

        $cast->set($model, 'phone', 'not-a-number', []);
    })->throws(InvalidPhoneNumberException::class);
});

describe('PhoneNumberCast on Eloquent models', function (): void {
    it('round-trips a value through an Eloquent attribute cast', function (): void {
        $model = new class extends Model
        {
            protected $guarded = [];

            protected $casts = [
                'phone' => PhoneNumberCast::class,
            ];
        };

        $e164 = castSample();
        $model->phone = $e164;

        expect($model->getAttributes()['phone'])->toBe($e164)
            ->and($model->phone)->toBeInstanceOf(PhoneNumber::class)
            ->and($model->phone->e164())->toBe($e164);
    });
});
