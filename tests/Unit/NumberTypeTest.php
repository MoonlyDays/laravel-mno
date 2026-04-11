<?php

declare(strict_types=1);

use libphonenumber\PhoneMetadata;
use libphonenumber\PhoneNumberDesc;
use libphonenumber\PhoneNumberUtil;
use MoonlyDays\MNO\Enums\NumberType;

it('has a case for every libphonenumber description descriptor', function (NumberType $type): void {
    $metadata = PhoneNumberUtil::getInstance()->getMetadataForRegion('GB');

    expect($metadata)->toBeInstanceOf(PhoneMetadata::class);

    $desc = $type->descriptionFrom($metadata);

    // GB metadata populates most descriptors; a returned null is acceptable
    // for types a given region doesn't define.
    expect($desc === null || $desc instanceof PhoneNumberDesc)->toBeTrue();
})->with(NumberType::cases());

it('resolves mobile description from metadata', function (): void {
    $metadata = PhoneNumberUtil::getInstance()->getMetadataForRegion('TZ');

    $desc = NumberType::Mobile->descriptionFrom($metadata);

    expect($desc)->toBeInstanceOf(PhoneNumberDesc::class);
});

it('uses the backed string value as the case name', function (): void {
    expect(NumberType::Mobile->value)->toBe('mobile')
        ->and(NumberType::FixedLine->value)->toBe('fixed_line')
        ->and(NumberType::TollFree->value)->toBe('toll_free');
});
