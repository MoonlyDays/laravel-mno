<?php

declare(strict_types=1);

use libphonenumber\PhoneNumber as BasePhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;
use MoonlyDays\MNO\Exceptions\InvalidMsisdnException;
use MoonlyDays\MNO\Values\Msisdn;

describe('PhoneNumber::from', function (): void {
    it('parses a valid E.164 number without a region', function (): void {
        $e164 = mobileExampleFor('TZ');

        $phone = Msisdn::from($e164);

        expect($phone)->toBeInstanceOf(Msisdn::class)
            ->and($phone->e164())->toBe($e164)
            ->and($phone->countryIso())->toBe('TZ')
            ->and($phone->countryCode())->toBe(255);
    });

    it('parses a national-format number using a provided region', function (): void {
        $util = PhoneNumberUtil::getInstance();
        $example = $util->getExampleNumberForType('GB', PhoneNumberType::MOBILE);
        $national = $util->format($example, PhoneNumberFormat::NATIONAL);

        $phone = Msisdn::from($national, 'GB');

        expect($phone->countryIso())->toBe('GB')
            ->and($phone->countryCode())->toBe(44);
    });

    it('falls back to the configured MNO country when no region is passed', function (): void {
        config()->set('mno.country', 'GB');

        $util = PhoneNumberUtil::getInstance();
        $example = $util->getExampleNumberForType('GB', PhoneNumberType::MOBILE);
        $national = $util->format($example, PhoneNumberFormat::NATIONAL);

        $phone = Msisdn::from($national);

        expect($phone->countryIso())->toBe('GB');
    });

    it('throws InvalidPhoneNumberException for unparseable input', function (): void {
        Msisdn::from('not-a-number');
    })->throws(InvalidMsisdnException::class);

    it('throws InvalidPhoneNumberException for parseable-but-invalid numbers', function (): void {
        // A short string that parses but is not a valid number.
        Msisdn::from('+1234');
    })->throws(InvalidMsisdnException::class);

    it('includes the offending number in the exception message', function (): void {
        try {
            Msisdn::from('bogus');
        } catch (InvalidMsisdnException $e) {
            expect($e->getMessage())->toContain('bogus');

            return;
        }

        $this->fail('Expected InvalidPhoneNumberException was not thrown.');
    });

    it('accepts an integer and parses it against the given region', function (): void {
        $e164 = mobileExampleFor('TZ');
        $int = (int) ltrim($e164, '+');

        $phone = Msisdn::from($int, 'TZ');

        expect($phone)->toBeInstanceOf(Msisdn::class)
            ->and($phone->e164())->toBe($e164);
    });

    it('accepts an integer using the configured MNO country as the default region', function (): void {
        config()->set('mno.country', 'TZ');
        $e164 = mobileExampleFor('TZ');
        $int = (int) ltrim($e164, '+');

        $phone = Msisdn::from($int);

        expect($phone->e164())->toBe($e164);
    });

    it('throws InvalidPhoneNumberException when given an unparseable integer', function (): void {
        Msisdn::from(1234, 'TZ');
    })->throws(InvalidMsisdnException::class);
});

describe('PhoneNumber::tryFrom', function (): void {
    it('returns a PhoneNumber for valid input', function (): void {
        $phone = Msisdn::tryFrom(mobileExampleFor('TZ'));

        expect($phone)->toBeInstanceOf(Msisdn::class);
    });

    it('returns null for invalid input', function (): void {
        expect(Msisdn::tryFrom('not-a-number'))->toBeNull();
    });

    it('accepts an integer and parses it against the given region', function (): void {
        $e164 = mobileExampleFor('TZ');
        $int = (int) ltrim($e164, '+');

        $phone = Msisdn::tryFrom($int, 'TZ');

        expect($phone)->toBeInstanceOf(Msisdn::class)
            ->and($phone->e164())->toBe($e164);
    });

    it('returns null for an unparseable integer', function (): void {
        expect(Msisdn::tryFrom(1234, 'TZ'))->toBeNull();
    });
});

describe('PhoneNumber formatting', function (): void {
    beforeEach(function (): void {
        $this->phone = Msisdn::from(mobileExampleFor('TZ'));
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

describe('PhoneNumber::toInteger', function (): void {
    it('returns an int', function (): void {
        $phone = Msisdn::from(mobileExampleFor('TZ'));

        expect($phone->toInteger())->toBeInt();
    });

    it('returns the E.164 digits without the leading plus', function (): void {
        $e164 = mobileExampleFor('TZ');
        $phone = Msisdn::from($e164);

        expect((string) $phone->toInteger())->toBe(ltrim($e164, '+'));
    });

    it('produces equal ints for equal numbers', function (): void {
        $e164 = mobileExampleFor('TZ');

        expect(Msisdn::from($e164)->toInteger())
            ->toBe(Msisdn::from($e164)->toInteger());
    });

    it('produces different ints for different numbers', function (): void {
        $a = Msisdn::from(mobileExampleFor('TZ'));
        $b = Msisdn::from(mobileExampleFor('GB'));

        expect($a->toInteger())->not->toBe($b->toInteger());
    });

    it('preserves the country calling code in the leading digits', function (): void {
        $phone = Msisdn::from(mobileExampleFor('TZ'));

        expect((string) $phone->toInteger())->toStartWith((string) $phone->countryCode());
    });

    it('fits within PHP_INT_MAX on 64-bit platforms', function (): void {
        // E.164 allows up to 15 digits (max ~10^15), well under PHP_INT_MAX
        // on 64-bit systems (~9.2 * 10^18). Guard against silent overflow.
        if (PHP_INT_SIZE < 8) {
            $this->markTestSkipped('Requires 64-bit PHP.');
        }

        foreach (['TZ', 'GB', 'US', 'DE', 'JP'] as $region) {
            $phone = Msisdn::from(mobileExampleFor($region));

            expect($phone->toInteger())->toBeLessThan(PHP_INT_MAX);
        }
    });
});

describe('PhoneNumber decomposition', function (): void {
    it('exposes national number, network code and subscriber number', function (): void {
        $phone = Msisdn::from(mobileExampleFor('TZ'));

        $nationalNumber = $phone->nationalNumber();
        $networkCode = $phone->networkCode();
        $subscriber = $phone->subscriberNumber();

        expect($nationalNumber)->toBeString()
            ->and($nationalNumber)->not->toBe('')
            ->and($networkCode.$subscriber)->toBe($nationalNumber);
    });

    it('exposes the raw libphonenumber PhoneNumber instance', function (): void {
        $phone = Msisdn::from(mobileExampleFor('TZ'));

        expect($phone->toPhoneNumber())
            ->toBeInstanceOf(BasePhoneNumber::class);
    });
});

describe('PhoneNumber::equals', function (): void {
    it('returns true for two instances with the same E.164 value', function (): void {
        $e164 = mobileExampleFor('TZ');

        expect(Msisdn::from($e164)->equals(Msisdn::from($e164)))->toBeTrue();
    });

    it('returns false for different numbers', function (): void {
        $a = Msisdn::from(mobileExampleFor('TZ'));
        $b = Msisdn::from(mobileExampleFor('GB'));

        expect($a->equals($b))->toBeFalse();
    });
});

describe('PhoneNumber macroable', function (): void {
    it('supports registering and calling macros', function (): void {
        Msisdn::macro('shout', fn (): string => mb_strtoupper($this->e164()));

        $phone = Msisdn::from(mobileExampleFor('TZ'));

        expect($phone->shout())->toBe(mb_strtoupper($phone->e164()));
    });
});

describe('PhoneNumber::timezones', function (): void {
    it('returns timezone identifiers for a valid number', function (): void {
        $phone = Msisdn::from(mobileExampleFor('TZ'));

        $timezones = $phone->timezones();

        expect($timezones)->toBeArray()
            ->and($timezones)->not->toBeEmpty()
            ->and($timezones)->each->toBeString();
    });

    it('does not include Etc/Unknown', function (): void {
        $phone = Msisdn::from(mobileExampleFor('TZ'));

        expect($phone->timezones())->not->toContain('Etc/Unknown');
    });

    it('returns Africa/Dar_es_Salaam for a Tanzanian number', function (): void {
        $phone = Msisdn::from(mobileExampleFor('TZ'));

        expect($phone->timezones())->toContain('Africa/Dar_es_Salaam');
    });

    it('returns Europe/London for a UK number', function (): void {
        $phone = Msisdn::from(mobileExampleFor('GB'));

        expect($phone->timezones())->toContain('Europe/London');
    });
});

describe('PhoneNumber::timezone', function (): void {
    it('returns the primary timezone as a string', function (): void {
        $phone = Msisdn::from(mobileExampleFor('TZ'));

        expect($phone->timezone())->toBeString()
            ->and($phone->timezone())->toBe($phone->timezones()[0]);
    });
});
