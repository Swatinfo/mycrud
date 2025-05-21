<?php

namespace DryRun\Product\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
// use Illuminate\Support\Carbon;

/**
 * @OA\Schema(
 * schema="ProductResource", title="Product Resource", type="object",
 * @OA\Property(property="id", type="integer", example=1),
 * // TODO: Define properties based on your model
 * @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T12:00:00Z"),
 * @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-01T13:30:00Z"),
 * @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null, description="Timestamp of soft deletion")
 * )
 */
class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        $data = [
            'id' => $this->whenNotNull($this->id),
            'name' => $this->whenNotNull($this->name),
            'hsn_code' => $this->whenNotNull($this->hsn_code),
            'display_order' => $this->whenNotNull($this->display_order),
            'min_quantity' => $this->whenNotNull($this->min_quantity),
            'max_quantity' => $this->whenNotNull($this->max_quantity),
            'burning_loss' => $this->whenNotNull($this->burning_loss),
            'is_internal_product' => $this->whenNotNull($this->is_internal_product),
            'is_active' => $this->whenNotNull($this->is_active),
            'product_weight' => $this->whenNotNull($this->product_weight),
            'team_id' => $this->whenNotNull($this->team_id),
            'created_at' => $this->whenNotNull($this->created_at ? $this->created_at->toIso8601String() : null),
            'updated_at' => $this->whenNotNull($this->updated_at ? $this->updated_at->toIso8601String() : null),
            'deleted_at' => $this->whenNotNull($this->deleted_at ? $this->deleted_at->toIso8601String() : null),
            'min_stock' => $this->whenNotNull($this->min_stock),
            'max_stock' => $this->whenNotNull($this->max_stock),
            'is_store_room' => $this->whenNotNull($this->is_store_room),
            'unit_id' => $this->whenNotNull($this->unit_id),
            'rack_place' => $this->whenNotNull($this->rack_place),
            // Example: 'user' => new UserResource($this->whenLoaded('user')),
            'deleted_at' => $this->whenNotNull($this->deleted_at?->toIso8601String()),
        ];

        // Remove null values if you prefer cleaner output, but this can hide fields that are intentionally null
        // return array_filter($data, fn ($value) => !is_null($value));
        return $data;
    }
}
