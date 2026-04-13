<?php

declare(strict_types=1);

use MoonlyDays\MNO\Exceptions\InvalidPhoneNumberException;
use MoonlyDays\MNO\Values\PhoneNumber;

describe('PhoneNumber::from', function (): void {
    it('parses a valid E.164 number without a region', function (): void {
        $e164 = mobileExampleFor('TZ');

        $phone = PhoneNumber::from($e164);

        expect($phone)->toBeInstanceOf(PhoneNumber::class)
            ->and($phone->e164())->toBe($e164)
            ->and($phone->countryIso())->toBe('TZ')
            ->and($phone->countryCode())->toBe(255);
    });

    it('parses a national-format number using a provided region', function (): void {
        $util = \libphonenumber\PhoneNumberUtil::getInstance();
        $example = $util->getExampleNumberForType('GB', \libphonenumber\PhoneNumberType::MOBILE);
        $national = $util->format($example, \libphonenumber\PhoneNumberFormat::NATIONAL);

        $phone = PhoneNumber::from($national, 'GB');

        expect($phone->countryIso())->toBe('GB')
            ->and($phone->countryCode())->toBe(44);
    });

    it('falls back to the configured MNO country when no region is passed', function (): void {
        config()->set('mno.country', 'GB');

        $util = \libphonenumber\PhoneNumberUtil::getInstance();
        $example = $util->getExampleNumberForType('GB', \libphonenumber\PhoneNumberType::MOBILE);
        $national = $util->format($example, \libphonenumber\PhoneNumberFormat::NATIONAL);

        $phone = PhoneNumber::from($national);

        expect($phone->countryIso())->toBe('GB');
    });

    it('throws InvalidPhoneNumberException for unparseable input', function (): void {
        PhoneNumber::from('not-a-number');
    })->throws(InvalidPhoneNumberException::class);

    it('throws InvalidPhoneNumberException for parseable-but-invalid numbers', function (): void {
        // A short string that parses but is not a valid number.
        PhoneNumber::from('+1234');
    })->throws(InvalidPhoneNumberException::class);

    it('includes the offending number in the exception message', function (): void {
        try {
            PhoneNumber::from('bogus');
        } catch (InvalidPhoneNumberException $e) {
            expect($e->getMessage())->toContain('bogus');

            return;
        }

        $this->fail('Expected InvalidPhoneNumberException was not thrown.');
    });
});

describe('PhoneNumber::tryFrom', function (): void {
    it('returns a PhoneNumber for valid input', function (): void {
        $phone = PhoneNumber::tryFrom(mobileExampleFor('TZ'));

        expect($phone)->toBeInstanceOf(PhoneNumber::class);
    });

    it('returns null for invalid input', function (): void {
        expect(PhoneNumber::tryFrom('not-a-number'))->toBeNull();
    });
});

describe('PhoneNumber formatting', function (): void {
    beforeEach(function (): void {
        $this->phone = PhoneNumber::from(mobileExampleFor('TZ'));
    });

    it('formats as E.164 with leading plus', function (): void {
        expect($this->phone->e164())->toStartWith('+255');
    });

    it('formats nationally without the country code prefix', function (): void {
        expect($this->phone->national())->not->toContain('+')
            ->and($this->phone->national())->not->toContain('255');
    });

    it('formats internationally with a leading plus', function (): void {
        expect($this->phone->international())->toStartWith('+255');
    });

    it('toString is the E.164 form', function (): void {
        expect((string) $this->phone)->toBe($this->phone->e164());
    });
});

describe('PhoneNumber decomposition', function (): void {
    it('exposes national number, network code and subscriber number', function (): void {
        $phone = PhoneNumber::from(mobileExampleFor('TZ'));

        $nationalNumber = $phone->nationalNumber();
        $networkCode = $phone->networkCode();
        $subscriber = $phone->subscriberNumber();

        expect($nationalNumber)->toBeString()
            ->and($nationalNumber)->not->toBe('')
            ->and($networkCode.$subscriber)->toBe($nationalNumber);
    });

    it('exposes the raw libphonenumber PhoneNumber instance', function (): void {
        $phone = PhoneNumber::from(mobileExampleFor('TZ'));

        expect($phone->toPhoneNumber())
            ->toBeInstanceOf(libphonenumber\PhoneNumber::class);
    });
});

describe('PhoneNumber::equals', function (): void {
    it('returns true for two instances with the same E.164 value', function (): void {
        $e164 = mobileExampleFor('TZ');

        expect(PhoneNumber::from($e164)->equals(PhoneNumber::from($e164)))->toBeTrue();
    });

    it('returns false for different numbers', function (): void {
        $a = PhoneNumber::from(mobileExampleFor('TZ'));
        $b = PhoneNumber::from(mobileExampleFor('GB'));

        expect($a->equals($b))->toBeFalse();
    });
});

describe('PhoneNumber macroable', function (): void {
    it('supports registering and calling macros', function (): void {
        PhoneNumber::macro('shout', fn (): string => mb_strtoupper($this->e164()));

        $phone = PhoneNumber::from(mobileExampleFor('TZ'));

        expect($phone->shout())->toBe(mb_strtoupper($phone->e164()));
    });
});

describe('PhoneNumber::timezones', function (): void {
    it('returns timezone identifiers for a valid number', function (): void {
        $phone = PhoneNumber::from(mobileExampleFor('TZ'));

        $timezones = $phone->timezones();

        expect($timezones)->toBeArray()
            ->and($timezones)->not->toBeEmpty()
            ->and($timezones)->each->toBeString();
    });

    it('does not include Etc/Unknown', function (): void {
        $phone = PhoneNumber::from(mobileExampleFor('TZ'));

        expect($phone->timezones())->not->toContain('Etc/Unknown');
    });

    it('returns Africa/Dar_es_Salaam for a Tanzanian number', function (): void {
        $phone = PhoneNumber::from(mobileExampleFor('TZ'));

        expect($phone->timezones())->toContain('Africa/Dar_es_Salaam');
    });

    it('returns Europe/London for a UK number', function (): void {
        $phone = PhoneNumber::from(mobileExampleFor('GB'));

        expect($phone->timezones())->toContain('Europe/London');
    });
});

describe('PhoneNumber::timezone', function (): void {
    it('returns the primary timezone as a string', function (): void {
        $phone = PhoneNumber::from(mobileExampleFor('TZ'));

        expect($phone->timezone())->toBeString()
            ->and($phone->timezone())->toBe($phone->timezones()[0]);
    });
});
