<?php

declare(strict_types=1);

namespace MoonlyDays\MNO\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use MoonlyDays\MNO\MnoService;

/**
 * @property MnoService $resource
 */
class PhoneNumberFormatResource extends JsonResource
{
    public function __construct(MnoService $resource)
    {
        parent::__construct($resource);
    }

    public static function make(mixed ...$parameters): self
    {
        return resolve(self::class, $parameters);
    }

    public function toAttributes(Request $request): array
    {
        return [
            'countryCode' => $this->resource->countryCode(),
            'country' => $this->resource->countryIsoCode(),
            'minLength' => $this->resource->minLength(),
            'maxLength' => $this->resource->maxLength(),
            'networkCodes' => $this->resource->networkCodes(),
        ];
    }
}
