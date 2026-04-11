<?php

declare(strict_types=1);

namespace MoonlyDays\MNO\Tests;

use MoonlyDays\MNO\Enums\NumberType;
use MoonlyDays\MNO\Facades\MNO;
use MoonlyDays\MNO\MnoServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            MnoServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'MNO' => MNO::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('mno.name', 'Test Operator');
        $app['config']->set('mno.country', 'TZ');
        $app['config']->set('mno.network_codes', []);
        $app['config']->set('mno.carrier_locale', 'en_US');
        $app['config']->set('mno.validation.min_length', null);
        $app['config']->set('mno.validation.max_length', null);
        $app['config']->set('mno.validation.number_types', [
            NumberType::Mobile,
            NumberType::General,
        ]);
    }
}
