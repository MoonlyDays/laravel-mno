<?php

declare(strict_types=1);

namespace MoonlyDays\MNO\Enums;

use libphonenumber\PhoneMetadata;
use libphonenumber\PhoneNumberDesc;

enum NumberType: string
{
    case Mobile = 'mobile';
    case FixedLine = 'fixed_line';
    case General = 'general';
    case TollFree = 'toll_free';
    case PremiumRate = 'premium_rate';
    case SharedCost = 'shared_cost';
    case Voip = 'voip';
    case PersonalNumber = 'personal_number';
    case Pager = 'pager';
    case Uan = 'uan';
    case Voicemail = 'voicemail';

    /**
     * Resolve the PhoneNumberDesc from metadata for this type.
     */
    public function descriptionFrom(PhoneMetadata $metadata): ?PhoneNumberDesc
    {
        return match ($this) {
            self::Mobile => $metadata->getMobile(),
            self::FixedLine => $metadata->getFixedLine(),
            self::General => $metadata->getGeneralDesc(),
            self::TollFree => $metadata->getTollFree(),
            self::PremiumRate => $metadata->getPremiumRate(),
            self::SharedCost => $metadata->getSharedCost(),
            self::Voip => $metadata->getVoip(),
            self::PersonalNumber => $metadata->getPersonalNumber(),
            self::Pager => $metadata->getPager(),
            self::Uan => $metadata->getUan(),
            self::Voicemail => $metadata->getVoicemail(),
        };
    }
}
