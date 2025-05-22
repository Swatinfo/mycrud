<?php

namespace DryRun\Products\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
 use App\Models\Team; // TODO: Create `Team` model or ensure it's discoverable.
 use UnitMaster; // TODO: Create `UnitMaster` model or ensure it's discoverable.
 use BlockedStock; // TODO: Create `BlockedStock` model or ensure it's discoverable.
 use BrandProduct; // TODO: Create `BrandProduct` model or ensure it's discoverable.
 use Brand; // TODO: Create `Brand` model or ensure it's discoverable.
 use CategoryProduct; // TODO: Create `CategoryProduct` model or ensure it's discoverable.
 use Category; // TODO: Create `Category` model or ensure it's discoverable.
 use CompoundingStockTransaction; // TODO: Create `CompoundingStockTransaction` model or ensure it's discoverable.
 use DuplicateOrderTransaction; // TODO: Create `DuplicateOrderTransaction` model or ensure it's discoverable.
 use DuplicateOrderTransactionImport; // TODO: Create `DuplicateOrderTransactionImport` model or ensure it's discoverable.
 use MonthlyProductStock; // TODO: Create `MonthlyProductStock` model or ensure it's discoverable.
 use OrderCart; // TODO: Create `OrderCart` model or ensure it's discoverable.
 use OrderTransaction; // TODO: Create `OrderTransaction` model or ensure it's discoverable.
 use OrderTransactionImport; // TODO: Create `OrderTransactionImport` model or ensure it's discoverable.
 use ProductBalance; // TODO: Create `ProductBalance` model or ensure it's discoverable.
 use ProductCompleteDatum; // TODO: Create `ProductCompleteDatum` model or ensure it's discoverable.
 use ProductPackaging; // TODO: Create `ProductPackaging` model or ensure it's discoverable.
 use ProductProduct; // TODO: Create `ProductProduct` model or ensure it's discoverable.
 use ProductProductionBom; // TODO: Create `ProductProductionBom` model or ensure it's discoverable.
 use ProductPurchaseTransaction; // TODO: Create `ProductPurchaseTransaction` model or ensure it's discoverable.
 use ProductRawmaterial; // TODO: Create `ProductRawmaterial` model or ensure it's discoverable.
 use Rawmaterial; // TODO: Create `Rawmaterial` model or ensure it's discoverable.
 use ProductSemifinished; // TODO: Create `ProductSemifinished` model or ensure it's discoverable.
 use Semifinished; // TODO: Create `Semifinished` model or ensure it's discoverable.
 use ProductSizeDatum; // TODO: Create `ProductSizeDatum` model or ensure it's discoverable.
 use ProductSizeMaster; // TODO: Create `ProductSizeMaster` model or ensure it's discoverable.
 use ProductStock; // TODO: Create `ProductStock` model or ensure it's discoverable.
 use ProductStockReport; // TODO: Create `ProductStockReport` model or ensure it's discoverable.
 use ProductStockReportAllDetail; // TODO: Create `ProductStockReportAllDetail` model or ensure it's discoverable.
 use ProductStockReportView; // TODO: Create `ProductStockReportView` model or ensure it's discoverable.
 use ProductStockTransaction; // TODO: Create `ProductStockTransaction` model or ensure it's discoverable.
 use ProductTopCategory; // TODO: Create `ProductTopCategory` model or ensure it's discoverable.
 use ProductWarehouse; // TODO: Create `ProductWarehouse` model or ensure it's discoverable.
 use Warehouse; // TODO: Create `Warehouse` model or ensure it's discoverable.
 use ProductWarehouseStockTransaction; // TODO: Create `ProductWarehouseStockTransaction` model or ensure it's discoverable.
 use ProductWarehouseUpdate; // TODO: Create `ProductWarehouseUpdate` model or ensure it's discoverable.
 use ProductsInformation; // TODO: Create `ProductsInformation` model or ensure it's discoverable.
 use SalesProductWiseMonthly; // TODO: Create `SalesProductWiseMonthly` model or ensure it's discoverable.
 use StockExtraProductionItem; // TODO: Create `StockExtraProductionItem` model or ensure it's discoverable.
 use StoreStocksItem; // TODO: Create `StoreStocksItem` model or ensure it's discoverable.
 use WarehouseStock; // TODO: Create `WarehouseStock` model or ensure it's discoverable.
 use WarehouseStockTransaction; // TODO: Create `WarehouseStockTransaction` model or ensure it's discoverable. // Will include SoftDeletes and other related models

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
    protected $dates = [
        'deleted_at'
    ]; // Includes 'deleted_at'

    /**
     * The event map for the model.
     * Allows for object-based events for model life-cycle.
     *
     * @var array
     */
    

    // Relationships
    public function team()
    {
        return $this->belongsTo(App\Models\Team::class  , 'team_id');
    }

    public function unit()
    {
        return $this->belongsTo(UnitMaster::class  , 'unit_id');
    }

    public function blockedStock()
    {
        return $this->hasOne(BlockedStock::class  , 'product_id');
    }

    public function brandProduct()
    {
        return $this->hasOne(BrandProduct::class  , 'product_id');
    }

    public function brandBrandProduct()
    {
        return $this->belongsToMany(Brand::class  , 'brand_product', 'product_id', 'brand_id');
    }

    public function categoryProduct()
    {
        return $this->hasOne(CategoryProduct::class  , 'product_id');
    }

    public function categoryCategoryProduct()
    {
        return $this->belongsToMany(Category::class  , 'category_product', 'product_id', 'category_id');
    }

    public function compoundingStockTransaction()
    {
        return $this->hasOne(CompoundingStockTransaction::class  , 'product_id');
    }

    public function duplicateOrderTransaction()
    {
        return $this->hasOne(DuplicateOrderTransaction::class  , 'product_id');
    }

    public function duplicateOrderTransactionImport()
    {
        return $this->hasOne(DuplicateOrderTransactionImport::class  , 'product_id');
    }

    public function monthlyProductStock()
    {
        return $this->hasOne(MonthlyProductStock::class  , 'product_id');
    }

    public function orderCart()
    {
        return $this->hasOne(OrderCart::class  , 'product_id');
    }

    public function orderTransaction()
    {
        return $this->hasOne(OrderTransaction::class  , 'product_id');
    }

    public function orderTransactionImport()
    {
        return $this->hasOne(OrderTransactionImport::class  , 'product_id');
    }

    public function productBalance()
    {
        return $this->hasOne(ProductBalance::class  , 'product_id');
    }

    public function productCompleteData()
    {
        return $this->hasMany(ProductCompleteDatum::class  , 'product_id');
    }

    public function productPackaging()
    {
        return $this->hasOne(ProductPackaging::class  , 'product_id');
    }

    public function productProduct()
    {
        return $this->hasOne(ProductProduct::class  , 'product_id');
    }

    public function productproductProduct()
    {
        return $this->belongsToMany(Product::class, 'product_product', 'product_id', 'product_id');
    }

    public function productProductionBom()
    {
        return $this->hasOne(ProductProductionBom::class  , 'product_id');
    }

    public function productPurchaseTransactions()
    {
        return $this->hasMany(ProductPurchaseTransaction::class  , 'product_id');
    }

    public function productRawmaterial()
    {
        return $this->hasOne(ProductRawmaterial::class  , 'product_id');
    }

    public function rawmaterialProductRawmaterial()
    {
        return $this->belongsToMany(Rawmaterial::class  , 'product_rawmaterial', 'product_id', 'rawmaterial_id');
    }

    public function productSemifinished()
    {
        return $this->hasOne(ProductSemifinished::class  , 'product_id');
    }

    public function semifinishedProductSemifinished()
    {
        return $this->belongsToMany(Semifinished::class  , 'product_semifinished', 'product_id', 'semifinished_id');
    }

    public function productSizeData()
    {
        return $this->hasMany(ProductSizeDatum::class  , 'product_id');
    }

    public function productSizeMaster()
    {
        return $this->hasOne(ProductSizeMaster::class  , 'product_id');
    }

    public function productStock()
    {
        return $this->hasOne(ProductStock::class  , 'product_id');
    }

    public function productStockReport()
    {
        return $this->hasOne(ProductStockReport::class  , 'product_id');
    }

    public function productStockReportAllDetails()
    {
        return $this->hasMany(ProductStockReportAllDetail::class  , 'product_id');
    }

    public function productStockReportView()
    {
        return $this->hasOne(ProductStockReportView::class  , 'product_id');
    }

    public function productStockTransaction()
    {
        return $this->hasOne(ProductStockTransaction::class  , 'product_id');
    }

    public function productTopCategory()
    {
        return $this->hasOne(ProductTopCategory::class  , 'product_id');
    }

    public function productWarehouse()
    {
        return $this->hasOne(ProductWarehouse::class  , 'product_id');
    }

    public function warehouseProductWarehouse()
    {
        return $this->belongsToMany(Warehouse::class  , 'product_warehouse', 'product_id', 'warehouse_id');
    }

    public function productWarehouseStockTransaction()
    {
        return $this->hasOne(ProductWarehouseStockTransaction::class  , 'product_id');
    }

    public function warehouseProductWarehouseStockTransaction()
    {
        return $this->belongsToMany(Warehouse::class  , 'product_warehouse_stock_transaction', 'product_id', 'warehouse_id');
    }

    public function productWarehouseUpdates()
    {
        return $this->hasMany(ProductWarehouseUpdate::class  , 'product_id');
    }

    public function warehouseProductWarehouseUpdate()
    {
        return $this->belongsToMany(Warehouse::class  , 'product_warehouse_updates', 'product_id', 'warehouse_id');
    }

    public function productsInformation()
    {
        return $this->hasOne(ProductsInformation::class  , 'product_id');
    }

    public function salesProductWiseMonthly()
    {
        return $this->hasOne(SalesProductWiseMonthly::class  , 'product_id');
    }

    public function stockExtraProductionItems()
    {
        return $this->hasMany(StockExtraProductionItem::class  , 'product_id');
    }

    public function storeStocksItems()
    {
        return $this->hasMany(StoreStocksItem::class  , 'product_id');
    }

    public function warehouseStock()
    {
        return $this->hasOne(WarehouseStock::class  , 'product_id');
    }

    public function warehouseStockTransaction()
    {
        return $this->hasOne(WarehouseStockTransaction::class  , 'product_id');
    }
}
