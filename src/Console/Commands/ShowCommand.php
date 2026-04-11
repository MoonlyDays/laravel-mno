<?php

declare(strict_types=1);

namespace MoonlyDays\MNO\Console\Commands;

use Illuminate\Console\Command;
use MoonlyDays\MNO\Facades\MNO;
use MoonlyDays\MNO\Values\Carrier;
use MoonlyDays\MNO\Values\Country;

class ShowCommand extends Command
{
    protected $signature = 'mno:show
        {country? : ISO 3166-1 alpha-2 country code (e.g. TZ)}
        {carrier? : Carrier name to inspect within the given country}';

    protected $description = 'Show information about the configured MNO, or inspect carriers in a country.';

    public function handle(): int
    {
        $countryIsoCode = $this->argument('country');
        $carrierName = $this->argument('carrier');

        return match (true) {
            is_null($countryIsoCode) => $this->showCarrier(MNO::carrier()),
            is_null($carrierName) => $this->showCountry(MNO::country($countryIsoCode)),
            default => $this->showCarrier(MNO::carrier($countryIsoCode, $carrierName)),
        };
    }

    protected function showCountry(Country $country): int
    {
        $this->components->info("Country: {$country->isoCode()}");

        $this->components->twoColumnDetail('ISO Code', $country->isoCode());
        $this->components->twoColumnDetail('Carrier Count', count($country->carriers()));
        $this->components->twoColumnDetail('Country Code', '+'.$country->countryCode());
        $this->components->twoColumnDetail('Mobile Network Portability', $country->isMobileNumberPortable() ? 'true' : 'false');
        $this->components->twoColumnDetail('Example Number', $country->exampleNumber());

        $this->components->info('Carriers:');

        foreach ($country->carriers() as $carrier) {
            $this->components->twoColumnDetail($carrier->name(), $carrier->networkCodeCount());
        }

        $this->newLine();

        return self::SUCCESS;
    }

    protected function showCarrier(Carrier $carrier): int
    {
        $this->components->info("Carrier: {$carrier->name()}");

        $this->components->twoColumnDetail('Name', $carrier->name());
        $this->components->twoColumnDetail('Country ISO Code', $carrier->country()->isoCode());
        $this->components->twoColumnDetail('Network Codes', $carrier->networkCodeCount());

        $this->components->info('Network Codes:');

        foreach ($carrier->networkCodes() as $networkCode) {
            $this->info('  +'.$carrier->country()->countryCode().' '.$networkCode);
        }

        $this->newLine();

        return self::SUCCESS;
    }
}
