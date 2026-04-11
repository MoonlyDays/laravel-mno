<?php

declare(strict_types=1);

namespace MoonlyDays\MNO;

use Illuminate\Validation\Rule;
use libphonenumber\PhoneNumberToCarrierMapper;
use libphonenumber\PhoneNumberUtil;
use MoonlyDays\MNO\Rules\PhoneNumberRule;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MnoServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $this->configureLibphonenumberPackage();

        $package
            ->name('mno')
            ->hasConfigFile();

        $this->app->singleton(MnoService::class);
        $this->app->alias(MnoService::class, 'mno');

        Rule::macro('phoneNumber', fn () => PhoneNumberRule::default());
    }

    protected function configureLibphonenumberPackage(): void
    {
        $this->app->bind(PhoneNumberUtil::class, fn (): PhoneNumberUtil => PhoneNumberUtil::getInstance());
        $this->app->bind(
            PhoneNumberToCarrierMapper::class,
            fn (): PhoneNumberToCarrierMapper => PhoneNumberToCarrierMapper::getInstance(),
        );
    }
}
