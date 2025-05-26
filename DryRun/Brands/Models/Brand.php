<?php

namespace DryRun\Brands\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
 use App\Models\Team; // TODO: Create `Team` model or ensure it's discoverable.
 use BrandProduct; // TODO: Create `BrandProduct` model or ensure it's discoverable.
 use Product; // TODO: Create `Product` model or ensure it's discoverable.
use DryRun\Brands\Events\BrandCreated;
use DryRun\Brands\Events\BrandUpdated;
use DryRun\Brands\Events\BrandDeleted; // Will include SoftDeletes and other related models

class Brand extends Model
{
    use HasFactory, SoftDeletes; // SoftDeletes added by default
    // use HasUuids;

    // protected $connection = 'your_connection_name';

    protected $table = 'brands';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'team_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        // 'password',
        // 'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'deleted_at'
    ]; // Includes 'deleted_at'

    /**
     * The event map for the model.
     * Allows for object-based events for model life-cycle.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'created' => BrandCreated::class,
        'updated' => BrandUpdated::class,
        'deleted' => BrandDeleted::class
    ];

    // Relationships
    public function team()
    {
        return $this->belongsTo(App\Models\Team::class  , 'team_id');
    }

    public function brandProduct()
    {
        return $this->hasOne(BrandProduct::class  , 'brand_id');
    }

    public function productBrandProduct()
    {
        return $this->belongsToMany(Product::class  , 'brand_product', 'brand_id', 'product_id');
    }
}
