<?php

namespace DryRun\Brands\Events;

use DryRun\Brands\Models\Brand;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BrandCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Brand $brand;

    /**
     * Create a new event instance.
     *
     * @param \DryRun\Brands\Models\Brand $brand
     * @return void
     */
    public function __construct(Brand $brand)
    {
        $this->brand = $brand;
    }
}
