<?php

declare(strict_types=1);

use libphonenumber\NumberParseException;
use MoonlyDays\MNO\Exceptions\InvalidPhoneNumberException;
use MoonlyDays\MNO\Exceptions\PhoneNumberLengthException;

describe('InvalidPhoneNumberException', function (): void {
    it('mentions the offending number in its message', function (): void {
        $exception = InvalidPhoneNumberException::forNumber('+123');

        expect($exception->getMessage())->toContain('+123');
    });

    it('carries a previous exception when provided', function (): void {
        $previous = new NumberParseException(1, 'boom');

        $exception = InvalidPhoneNumberException::forNumber('bad', $previous);

        expect($exception->getPrevious())->toBe($previous);
    });

    it('extends InvalidArgumentException', function (): void {
        expect(InvalidPhoneNumberException::forNumber('x'))
            ->toBeInstanceOf(InvalidArgumentException::class);
    });
});

describe('PhoneNumberLengthException', function (): void {
    it('builds a missing-country message', function (): void {
        expect(PhoneNumberLengthException::missingCountry()->getMessage())
            ->toContain('no country is configured');
    });

    it('builds a missing-metadata message referencing the country', function (): void {
        expect(PhoneNumberLengthException::missingMetadata('ZZ')->getMessage())
            ->toContain('ZZ');
    });

    it('builds an ambiguous-lengths message listing possibilities', function (): void {
        $message = PhoneNumberLengthException::ambiguous('TZ', [7, 9])->getMessage();

        expect($message)->toContain('TZ')
            ->and($message)->toContain('7')
            ->and($message)->toContain('9');
    });

    it('extends RuntimeException', function (): void {
        expect(PhoneNumberLengthException::missingCountry())
            ->toBeInstanceOf(RuntimeException::class);
    });
});
