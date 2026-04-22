<?php

declare(strict_types=1);

use libphonenumber\NumberParseException;
use MoonlyDays\MNO\Exceptions\InvalidMsisdnException;
use MoonlyDays\MNO\Exceptions\PhoneNumberLengthException;

describe('InvalidPhoneNumberException', function (): void {
    it('mentions the offending number in its message', function (): void {
        $exception = InvalidMsisdnException::forNumber('+123');

        expect($exception->getMessage())->toContain('+123');
    });

    it('carries a previous exception when provided', function (): void {
        $previous = new NumberParseException(1, 'boom');

        $exception = InvalidMsisdnException::forNumber('bad', $previous);

        expect($exception->getPrevious())->toBe($previous);
    });

    it('extends InvalidArgumentException', function (): void {
        expect(InvalidMsisdnException::forNumber('x'))
            ->toBeInstanceOf(InvalidArgumentException::class);
    });
});

describe('PhoneNumberLengthException', function (): void {
    it('builds a missing-metadata message referencing the country', function (): void {
        expect(PhoneNumberLengthException::missingMetadata('ZZ')->getMessage())
            ->toContain('ZZ');
    });

    it('builds an undefined-lengths message referencing the country', function (): void {
        expect(PhoneNumberLengthException::undefined('TZ')->getMessage())
            ->toContain('TZ');
    });

    it('extends RuntimeException', function (): void {
        expect(PhoneNumberLengthException::missingMetadata('TZ'))
            ->toBeInstanceOf(RuntimeException::class);
    });
});
