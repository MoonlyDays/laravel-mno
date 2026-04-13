<?php

declare(strict_types=1);

namespace MoonlyDays\MNO\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
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
        $this->components->twoColumnDetail('Name', $country->name());
        $this->components->twoColumnDetail('Carrier Count', count($country->carriers()));
        $this->components->twoColumnDetail('Country Code', '+'.$country->countryCode());
        $this->components->twoColumnDetail('Mobile Network Portability', $country->isMobileNumberPortable() ? '<fg=green>true</>' : '<fg=red>false</>');
        $this->components->twoColumnDetail('Timezones', $this->summarizeList($country->timezones()));
        $this->components->twoColumnDetail('Example Number', $country->exampleNumber());
        $this->components->twoColumnDetail('Min Length', $country->minPhoneNumberLength(MNO::numberTypes()));
        $this->components->twoColumnDetail('Max Length', $country->maxPhoneNumberLength(MNO::numberTypes()));

        $this->components->info('Carriers:');

        foreach ($country->carriers() as $carrier) {
            $this->components->twoColumnDetail(
                $carrier->name(),
                $this->summarizeList($carrier->networkCodes()),
            );
        }

        $this->newLine();

        return self::SUCCESS;
    }

    protected function showCarrier(Carrier $carrier): int
    {
        $country = $carrier->country();
        $minLength = $country->minPhoneNumberLength(MNO::numberTypes());
        $maxLength = $country->maxPhoneNumberLength(MNO::numberTypes());

        $this->components->info("Carrier: {$carrier->name()}");

        $this->components->twoColumnDetail('Name', $carrier->name());
        $this->components->twoColumnDetail('Country ISO Code', $country->isoCode());
        $this->components->twoColumnDetail('Country Name', $country->name());
        $this->components->twoColumnDetail('Network Codes', $carrier->networkCodeCount());
        $this->components->twoColumnDetail('Min Length', $minLength);
        $this->components->twoColumnDetail('Max Length', $maxLength);

        $this->components->info('Network Codes:');

        foreach ($carrier->networkCodes() as $networkCode) {
            // Render each allocation as "+<cc> <ndc> XXX…", where the trailing
            // X placeholders pad the NDC out to the country's max national
            // length so the operator can see a full example shape at a glance.
            $prefix = '  +'.$country->countryCode().' '.$networkCode;
            $remaining = max(0, $maxLength - Str::length($networkCode));
            $placeholder = $remaining > 0 ? ' <fg=gray>'.Str::repeat('X', $remaining).'</>' : '';

            $this->line('<info>'.$prefix.'</info>'.$placeholder);
        }

        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Summarize a list of items, showing the first two and collapsing the rest.
     *
     * E.g. ["A", "B", "C", "D"] → "A, B, and 2 others"
     */
    protected function summarizeList(array $items, int $limit = 4): string
    {
        if ($items === []) {
            return '<fg=gray>none</>';
        }

        $remaining = count($items) - $limit;
        if ($remaining <= 0) {
            return Arr::join($items, ', ', ' and ');
        }

        $visible = array_slice($items, 0, $limit);

        return implode(', ', $visible).' and <fg=gray>'.$remaining.' more</>';
    }
}
