<?php

declare(strict_types=1);

namespace MoonlyDays\MNO\Support;

use Giggsey\Locale\Locale;

/**
 * Internal repository for reading libphonenumber's carrier prefix data.
 *
 * Centralizes access to the @internal libphonenumber\carrier\data\*
 * classes so the rest of the package does not depend on their layout.
 * If upstream changes the class naming or the ::DATA contract, only
 * this file needs to change.
 *
 * @internal
 */
class CarrierDataRepository
{
    /**
     * In-process cache keyed by "{language}:{callingCode}".
     *
     * @var array<string, array<int, string>|null>
     */
    protected static array $cache = [];

    /**
     * Load the prefix => carrier-name map for a given calling code and locale.
     *
     * Falls back to English if the requested locale has no data file.
     *
     * @return array<int, string>|null
     */
    public function load(int $countryCode, string $locale = 'en_US'): ?array
    {
        $language = Locale::getPrimaryLanguage($locale);
        $language = $language === '' ? 'en' : $language;

        $data = $this->loadForLanguage($countryCode, $language);

        if ($data === null && $language !== 'en') {
            $data = $this->loadForLanguage($countryCode, 'en');
        }

        return $data;
    }

    /**
     * Determine whether carrier data exists for the given calling code.
     */
    public function has(int $countryCode, string $locale = 'en_US'): bool
    {
        return $this->load($countryCode, $locale) !== null;
    }

    /**
     * @return array<int, string>|null
     */
    protected function loadForLanguage(int $countryCode, string $language): ?array
    {
        $key = $language.':'.$countryCode;

        if (\array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        $className = \sprintf(
            'libphonenumber\\carrier\\data\\%s\\%s_%d',
            $language,
            ucfirst($language),
            $countryCode,
        );

        if (! class_exists($className)) {
            return self::$cache[$key] = null;
        }

        /** @var array<int, string> $data */
        $data = $className::DATA;

        return self::$cache[$key] = $data;
    }
}
