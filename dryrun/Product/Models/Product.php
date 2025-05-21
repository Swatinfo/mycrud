<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // Will include SoftDeletes and other related models

class Product extends Model
{
    use HasFactory, SoftDeletes; // SoftDeletes added by default
    // use HasUuids;

    // protected $connection = 'your_connection_name';

    protected $table = 'products';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'deleted_at' => 'datetime',
        'name',
        'hsn_code',
        'display_order',
        'min_quantity',
        'max_quantity',
        'burning_loss',
        'is_internal_product',
        'is_active',
        'product_weight',
        'team_id',
        'min_stock',
        'max_stock',
        'is_store_room',
        'unit_id',
        'rack_place'
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
        'is_internal_product' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at']; // Includes 'deleted_at'

    /**
     * The event map for the model.
     * Allows for object-based events for model life-cycle.
     *
     * @var array
     */
    

    // Relationships
    
}
