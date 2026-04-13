<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;
use MoonlyDays\MNO\Rules\PhoneNumberRule;

function validate(array $data, array $rules): Illuminate\Contracts\Validation\Validator
{
    return Validator::make($data, $rules);
}

describe('PhoneNumberRule basic validation', function (): void {
    it('passes for a valid phone number', function (): void {
        $validator = validate(
            ['phone' => mobileExampleFor('TZ')],
            ['phone' => new PhoneNumberRule()],
        );

        expect($validator->passes())->toBeTrue();
    });

    it('fails for a non-string value', function (): void {
        $validator = validate(
            ['phone' => ['array']],
            ['phone' => new PhoneNumberRule()],
        );

        expect($validator->fails())->toBeTrue();
    });

    it('fails for an unparseable value', function (): void {
        $validator = validate(
            ['phone' => 'not-a-number'],
            ['phone' => new PhoneNumberRule()],
        );

        expect($validator->fails())->toBeTrue();
    });
});

describe('PhoneNumberRule country constraint', function (): void {
    it('passes when the number matches one of the allowed countries', function (): void {
        $rule = (new PhoneNumberRule())->country('TZ', 'KE');

        expect(validate(['phone' => mobileExampleFor('TZ')], ['phone' => $rule])->passes())->toBeTrue();
    });

    it('fails when the number is from a different country', function (): void {
        $rule = (new PhoneNumberRule())->country('TZ');

        expect(validate(['phone' => mobileExampleFor('GB')], ['phone' => $rule])->fails())->toBeTrue();
    });

    it('accepts countries passed as an array', function (): void {
        $rule = (new PhoneNumberRule())->country(['TZ', 'KE']);

        expect(validate(['phone' => mobileExampleFor('TZ')], ['phone' => $rule])->passes())->toBeTrue();
    });
});

describe('PhoneNumberRule length constraints', function (): void {
    it('fails when the national number is shorter than min length', function (): void {
        $rule = (new PhoneNumberRule())->minLength(20);

        expect(validate(['phone' => mobileExampleFor('TZ')], ['phone' => $rule])->fails())->toBeTrue();
    });

    it('fails when the national number is longer than max length', function (): void {
        $rule = (new PhoneNumberRule())->maxLength(2);

        expect(validate(['phone' => mobileExampleFor('TZ')], ['phone' => $rule])->fails())->toBeTrue();
    });

    it('passes when the national number is within bounds', function (): void {
        $rule = (new PhoneNumberRule())->minLength(1)->maxLength(30);

        expect(validate(['phone' => mobileExampleFor('TZ')], ['phone' => $rule])->passes())->toBeTrue();
    });
});

describe('PhoneNumberRule network code constraint', function (): void {
    it('passes when the number starts with an allowed network code', function (): void {
        $util = PhoneNumberUtil::getInstance();
        $example = $util->getExampleNumberForType('TZ', PhoneNumberType::MOBILE);
        $national = (string) $example->getNationalNumber();
        $prefix = mb_substr($national, 0, 2);

        $rule = (new PhoneNumberRule())->networkCodes($prefix);

        expect(validate(['phone' => mobileExampleFor('TZ')], ['phone' => $rule])->passes())->toBeTrue();
    });

    it('fails when the number does not start with an allowed network code', function (): void {
        $rule = (new PhoneNumberRule())->networkCodes('99');

        expect(validate(['phone' => mobileExampleFor('TZ')], ['phone' => $rule])->fails())->toBeTrue();
    });

    it('accepts network codes as an array', function (): void {
        $util = PhoneNumberUtil::getInstance();
        $example = $util->getExampleNumberForType('TZ', PhoneNumberType::MOBILE);
        $national = (string) $example->getNationalNumber();
        $prefix = mb_substr($national, 0, 2);

        $rule = (new PhoneNumberRule())->networkCodes([$prefix, '00']);

        expect(validate(['phone' => mobileExampleFor('TZ')], ['phone' => $rule])->passes())->toBeTrue();
    });
});

describe('PhoneNumberRule::default', function (): void {
    afterEach(fn () => PhoneNumberRule::defaults(null));

    it('produces a rule configured from the MNO service', function (): void {
        config()->set('mno.country', 'TZ');

        $util = PhoneNumberUtil::getInstance();
        $example = $util->getExampleNumberForType('TZ', PhoneNumberType::MOBILE);
        $nationalPrefix = mb_substr((string) $example->getNationalNumber(), 0, 2);
        config()->set('mno.network_codes', [$nationalPrefix]);

        $rule = PhoneNumberRule::default();

        expect($rule)->toBeInstanceOf(PhoneNumberRule::class);
        expect(validate(['phone' => mobileExampleFor('TZ')], ['phone' => $rule])->passes())->toBeTrue();
    });

    it('rejects numbers from other countries when default is TZ', function (): void {
        config()->set('mno.country', 'TZ');

        $rule = PhoneNumberRule::default();

        expect(validate(['phone' => mobileExampleFor('GB')], ['phone' => $rule])->fails())->toBeTrue();
    });

    it('respects a custom default resolver', function (): void {
        PhoneNumberRule::defaults(fn () => (new PhoneNumberRule())->country('GB'));

        $rule = PhoneNumberRule::default();

        expect(validate(['phone' => mobileExampleFor('GB')], ['phone' => $rule])->passes())->toBeTrue()
            ->and(validate(['phone' => mobileExampleFor('TZ')], ['phone' => $rule])->fails())->toBeTrue();
    });
});

describe('Rule::phoneNumber macro', function (): void {
    it('is registered and returns a PhoneNumberRule', function (): void {
        config()->set('mno.country', 'TZ');

        expect(Rule::phoneNumber())->toBeInstanceOf(PhoneNumberRule::class);
    });
});
