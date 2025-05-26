<?php

namespace DryRun\Brands\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
// use Illuminate\Support\Carbon;

/**
 * @OA\Schema(
 * schema="BrandResource", title="Brand Resource", type="object",
 * @OA\Property(property="id", type="integer", example=1),
 * // TODO: Define properties based on your model
 * @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T12:00:00Z"),
 * @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-01T13:30:00Z"),
 * @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, example=null, description="Timestamp of soft deletion")
 * )
 */
class BrandResource extends JsonResource
{
    public function toArray($request): array
    {
        $data = [
            'id' => $this->whenNotNull($this->id),
            'name' => $this->whenNotNull($this->name),
            'created_at' => $this->whenNotNull($this->created_at ? $this->created_at->toIso8601String() : null),
            'updated_at' => $this->whenNotNull($this->updated_at ? $this->updated_at->toIso8601String() : null),
            'deleted_at' => $this->whenNotNull($this->deleted_at ? $this->deleted_at->toIso8601String() : null),
            'team_id' => $this->whenNotNull($this->team_id),
            // Example: 'user' => new UserResource($this->whenLoaded('user')),
            'deleted_at' => $this->whenNotNull($this->deleted_at?->toIso8601String()),
        ];

        // Remove null values if you prefer cleaner output, but this can hide fields that are intentionally null
        // return array_filter($data, fn ($value) => !is_null($value));
        return $data;
    }
}
