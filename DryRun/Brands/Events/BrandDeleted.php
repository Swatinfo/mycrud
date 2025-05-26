<?php

namespace DryRun\Brands\Events;

use DryRun\Brands\Models\Brand;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BrandDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Brand $brand;
    public bool $isForceDelete;

    /**
     * Create a new event instance.
     *
     * @param \DryRun\Brands\Models\Brand $brand The model instance before it's (soft or force) deleted
     * @param bool $isForceDelete True if it was a force delete, false for soft delete
     * @return void
     */
    public function __construct(Brand $brand, bool $isForceDelete = false)
    {
        $this->brand = $brand;
        $this->isForceDelete = $isForceDelete;
    }
}
