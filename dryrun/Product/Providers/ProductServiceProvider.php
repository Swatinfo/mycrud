<?php

namespace DryRun\Product\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\File;
use DryRun\Product\Models\Product; // For Observer and Policy
// use DryRun\Product\Policies\ProductPolicy; // If policy is generated
// use DryRun\Product\Observers\ProductObserver; // If observer is generated

class ProductServiceProvider extends ServiceProvider
{
    protected string $moduleNamespace = 'DryRun\Product';
    protected string $modulePath;

    protected $policies = [
        // Model Policies
        // \DryRun\Product\Models\Product::class => \DryRun\Product\Policies\ProductPolicy::class,
    ];

    public function __construct($app)
    {
        parent::__construct($app);
        $this->modulePath = base_path('Modules/Product');
    }

    public function register()
    {
        // Service Bindings
        // $this->app->bind(\DryRun\Product\Contracts\ProductServiceInterface::class, \DryRun\Product\Services\ProductService::class);
    }

    public function boot()
    {
        $this->loadRoutes();
        $this->loadMigrations();
        $this->loadViews();
        $this->registerPolicies();
        $this->registerObservers(); // New method call
    }

    protected function loadRoutes(): void { /* ... (same as previous) ... */
        if (true && File::exists($this->modulePath . '/routes/web.php')) {
            Route::middleware('web')
                 ->namespace($this->moduleNamespace . '\\Http\\Controllers\\Web')
                 ->group($this->modulePath . '/routes/web.php');
        }

        if (true && File::exists($this->modulePath . '/routes/api.php')) {
            Route::prefix(config('app.api_prefix', 'api'))
                 ->middleware(config('app.api_middleware', ['api']))
                 ->namespace($this->moduleNamespace . '\\Http\\Controllers\\Api')
                 ->group($this->modulePath . '/routes/api.php');
        }
    }
    protected function loadMigrations(): void { /* ... (same as previous) ... */ }
    protected function loadViews(): void { /* ... (same as previous) ... */
        if (File::isDirectory($this->modulePath . '/views')) {
            $this->loadViewsFrom($this->modulePath . '/views', 'product');
        }
    }
    protected function registerPolicies(): void { /* ... (same as previous) ... */
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }

    /**
     * Register model observers for the module.
     */
    protected function registerObservers(): void
    {
        // Model Observers
        // Example: if class_exists(\DryRun\Product\Observers\ProductObserver::class)
        //     Product::observe(\DryRun\Product\Observers\ProductObserver::class);
    }
}
