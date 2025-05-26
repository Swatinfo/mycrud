<?php

namespace DryRun\Brands\Events;

use DryRun\Brands\Models\Brand;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BrandUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Brand $brand;
    public array $originalAttributes; // To store attributes before update for auditing

    /**
     * Create a new event instance.
     *
     * @param \DryRun\Brands\Models\Brand $brand
     * @param array $originalAttributes Attributes of the model before the update
     * @return void
     */
    public function __construct(Brand $brand, array $originalAttributes = [])
    {
        $this->brand = $brand;
        $this->originalAttributes = $originalAttributes;
    }
}
