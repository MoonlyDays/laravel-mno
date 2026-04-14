<?php

declare(strict_types=1);

namespace MoonlyDays\MNO;

use Faker\Generator as FakerGenerator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use libphonenumber\PhoneNumberToCarrierMapper;
use libphonenumber\PhoneNumberUtil;
use MoonlyDays\MNO\Console\Commands\ShowCommand;
use MoonlyDays\MNO\Faker\PhoneNumberFaker;
use MoonlyDays\MNO\Rules\PhoneNumberRule;
use MoonlyDays\MNO\Values\PhoneNumber;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MnoServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $this->configureLibphonenumberPackage();

        $package
            ->name('mno')
            ->hasConfigFile()
            ->hasCommand(ShowCommand::class);

        $this->app->singleton(MnoService::class);
        $this->app->alias(MnoService::class, 'mno');

        $this->app->resolving(FakerGenerator::class, function (FakerGenerator $faker): void {
            $faker->addProvider(new PhoneNumberFaker($faker));
        });

        Rule::macro('phoneNumber', fn () => PhoneNumberRule::default());
        Request::macro('phoneNumber', function (string $key, mixed $default = null): mixed {
            if ($this->isNotFilled($key)) {
                return value($default);
            }

            return PhoneNumber::tryFrom($this->data($key)) ?: value($default);
        });
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
