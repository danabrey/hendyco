<?php

namespace App\Filament\Resources\Bookings\Schemas;

use App\Models\Dog;
use App\Models\Service;

/**
 * The price each client would be charged for a booking of a service with a given set of dogs,
 * before any hand edits.
 */
class SuggestedPrices
{
    /**
     * @param  array<int, int>  $dogCountsByClientId
     */
    public function __construct(private ?Service $service, private array $dogCountsByClientId) {}

    /**
     * @param  array<int, int|string>  $dogIds
     */
    public static function for(mixed $serviceId, array $dogIds): self
    {
        $service = $serviceId ? Service::find($serviceId) : null;
        $dogCountsByClientId = $dogIds ? Dog::whereIn('id', $dogIds)->pluck('client_id')->countBy()->all() : [];

        return new self($service, $dogCountsByClientId);
    }

    /**
     * The service's price for this client's dogs on the booking, or 0 when no service is chosen.
     */
    public function forClient(mixed $clientId): int
    {
        if (! $this->service) {
            return 0;
        }

        $dogCount = $clientId ? $this->dogCountsByClientId[$clientId] ?? 0 : 0;

        return $this->service->priceForDogCount($dogCount);
    }

    /**
     * The clients who own at least one of the dogs.
     *
     * @return list<int>
     */
    public function clientIds(): array
    {
        return array_keys($this->dogCountsByClientId);
    }
}
