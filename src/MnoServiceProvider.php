<?php

declare(strict_types=1);

namespace MoonlyDays\MNO;

use Faker\Generator as FakerGenerator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use libphonenumber\PhoneNumberToCarrierMapper;
use libphonenumber\PhoneNumberUtil;
use MoonlyDays\MNO\Console\Commands\ShowCommand;
use MoonlyDays\MNO\Faker\MsisdnFaker;
use MoonlyDays\MNO\Rules\MsisdnRule;
use MoonlyDays\MNO\Values\Msisdn;
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
            $faker->addProvider(new MsisdnFaker($faker));
        });

        Rule::macro('phoneNumber', fn () => MsisdnRule::default());
        Request::macro('phoneNumber', function (string $key, mixed $default = null): mixed {
            if ($this->isNotFilled($key)) {
                return value($default);
            }

            return Msisdn::tryFrom($this->data($key)) ?: value($default);
        });

        Blueprint::macro('phoneNumber', fn (string $column): ColumnDefinition => $this->unsignedBigInteger($column));
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
