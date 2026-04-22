<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;
use MoonlyDays\MNO\Casts\MsisdnCast;
use MoonlyDays\MNO\Exceptions\InvalidMsisdnException;
use MoonlyDays\MNO\Values\Msisdn;

function e164ToInt(string $e164): int
{
    return (int) Str::chopStart($e164, PhoneNumberUtil::PLUS_SIGN);
}

describe('PhoneNumberCast::get', function (): void {
    it('returns null when the raw value is null', function (): void {
        $cast = new MsisdnCast();
        $model = new class extends Model {};

        expect($cast->get($model, 'phone', null, []))->toBeNull();
    });

    it('hydrates a PhoneNumber from a stored E.164 string', function (): void {
        $cast = new MsisdnCast();
        $model = new class extends Model {};
        $e164 = mobileExampleFor('TZ');

        $phone = $cast->get($model, 'phone', $e164, []);

        expect($phone)->toBeInstanceOf(Msisdn::class)
            ->and($phone->e164())->toBe($e164);
    });

    it('hydrates a PhoneNumber from a stored integer', function (): void {
        config()->set('mno.country', 'TZ');
        $cast = new MsisdnCast();
        $model = new class extends Model {};
        $e164 = mobileExampleFor('TZ');
        $int = e164ToInt($e164);

        $phone = $cast->get($model, 'phone', $int, []);

        expect($phone)->toBeInstanceOf(Msisdn::class)
            ->and($phone->e164())->toBe($e164);
    });

    it('throws on invalid stored data', function (): void {
        $cast = new MsisdnCast();
        $model = new class extends Model {};

        $cast->get($model, 'phone', 'garbage', []);
    })->throws(InvalidMsisdnException::class);
});

describe('PhoneNumberCast::set', function (): void {
    it('returns null when the incoming value is null', function (): void {
        $cast = new MsisdnCast();
        $model = new class extends Model {};

        expect($cast->set($model, 'phone', null, []))->toBeNull();
    });

    it('accepts a PhoneNumber instance and stores its E.164 form (without plus)', function (): void {
        $cast = new MsisdnCast();
        $model = new class extends Model {};
        $phone = Msisdn::from(mobileExampleFor('TZ'));

        expect($cast->set($model, 'phone', $phone, []))->toBe($phone->toInteger());
    });

    it('accepts a raw string and normalises it to E.164 (without plus)', function (): void {
        $cast = new MsisdnCast();
        $model = new class extends Model {};

        $util = PhoneNumberUtil::getInstance();
        $example = $util->getExampleNumberForType('TZ', PhoneNumberType::MOBILE);
        $national = $util->format($example, PhoneNumberFormat::NATIONAL);
        $expected = $util->format($example, PhoneNumberFormat::E164);

        config()->set('mno.country', 'TZ');

        expect($cast->set($model, 'phone', $national, []))->toBe(e164ToInt($expected));
    });

    it('accepts an integer and stores it as an integer', function (): void {
        config()->set('mno.country', 'TZ');
        $cast = new MsisdnCast();
        $model = new class extends Model {};
        $e164 = mobileExampleFor('TZ');
        $int = e164ToInt($e164);

        expect($cast->set($model, 'phone', $int, []))->toBe($int);
    });

    it('throws on invalid string input', function (): void {
        $cast = new MsisdnCast();
        $model = new class extends Model {};

        $cast->set($model, 'phone', 'not-a-number', []);
    })->throws(InvalidMsisdnException::class);
});

describe('PhoneNumberCast on Eloquent models', function (): void {
    it('round-trips a value through an Eloquent attribute cast', function (): void {
        $model = new class extends Model
        {
            protected $guarded = [];

            protected $casts = [
                'phone' => MsisdnCast::class,
            ];
        };

        $e164 = mobileExampleFor('TZ');
        $model->phone = $e164;

        expect($model->getAttributes()['phone'])->toBe(e164ToInt($e164))
            ->and($model->phone)->toBeInstanceOf(Msisdn::class)
            ->and($model->phone->e164())->toBe($e164);
    });

    it('rehydrates a PhoneNumber from an integer attribute loaded from storage', function (): void {
        config()->set('mno.country', 'TZ');

        $model = new class extends Model
        {
            protected $guarded = [];

            protected $casts = [
                'phone' => MsisdnCast::class,
            ];
        };

        $e164 = mobileExampleFor('TZ');
        $model->setRawAttributes(['phone' => e164ToInt($e164)], true);

        expect($model->phone)->toBeInstanceOf(Msisdn::class)
            ->and($model->phone->e164())->toBe($e164);
    });
});

describe('PhoneNumber as a Castable', function (): void {
    it('resolves to PhoneNumberCast via castUsing()', function (): void {
        expect(Msisdn::castUsing([]))->toBe(MsisdnCast::class);
    });

    it('can be used directly as the cast class on an Eloquent model', function (): void {
        $model = new class extends Model
        {
            protected $guarded = [];

            protected $casts = [
                'phone' => Msisdn::class,
            ];
        };

        $e164 = mobileExampleFor('TZ');
        $model->phone = $e164;

        expect($model->getAttributes()['phone'])->toBe(e164ToInt($e164))
            ->and($model->phone)->toBeInstanceOf(Msisdn::class)
            ->and($model->phone->e164())->toBe($e164);
    });
});
