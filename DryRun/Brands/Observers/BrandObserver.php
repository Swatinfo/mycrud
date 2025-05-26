<?php

namespace DryRun\Brands\Observers;

use DryRun\Brands\Models\Brand;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class BrandObserver
{
    /**
     * Handle the Brand "created" event.
     *
     * @param  \DryRun\Brands\Models\Brand  $brand
     * @return void
     */
    public function created(Brand $brand)
    {
        $this->logActivity('created', $brand);
    }

    /**
     * Handle the Brand "updated" event.
     *
     * @param  \DryRun\Brands\Models\Brand  $brand
     * @return void
     */
    public function updated(Brand $brand)
    {
        // Get original attributes to find changes
        // $original = $brand->getOriginal();
        // $changes = array_diff_assoc($brand->getAttributes(), $original);
        // unset($changes['updated_at']); // Don't log updated_at timestamp change itself
        // if (empty($changes)) return; // No actual change to log

        $this->logActivity('updated', $brand/*, $changes*/);
    }

    /**
     * Handle the Brand "deleted" event.
     *
     * @param  \DryRun\Brands\Models\Brand  $brand
     * @return void
     */
    public function deleted(Brand $brand)
    {
        // If soft delete, this is for soft delete. If not, it's permanent.
        $action = method_exists($brand, 'isForceDeleting') && $brand->isForceDeleting() ? 'force deleted' : 'deleted (soft)';
        $this->logActivity($action, $brand);
    }

    /**
     * Handle the Brand "restored" event.
     *
     * @param  \DryRun\Brands\Models\Brand  $brand
     * @return void
     */
    public function restored(Brand $brand)
    {
        $this->logActivity('restored', $brand);
    }

    /**
     * Handle the Brand "force deleted" event.
     *
     * @param  \DryRun\Brands\Models\Brand  $brand
     * @return void
     */
    public function forceDeleted(Brand $brand)
    {
        $this->logActivity('force deleted', $brand);
    }

    /**
     * Log activity to a central logging system or database.
     *
     * @param string $action
     * @param Brand $brand
     * @param array|null $changes
     * @return void
     */
    protected function logActivity(string $action, Brand $brand, ?array $changes = null)
    {
        $userId = Auth::id();
        $userName = Auth::check() ? Auth::user()->name : 'System'; // Or some other identifier for console commands

        $message = "User {$userName} (ID: {$userId}) {$action} Brand (ID: {$brand->id}).";

        // TODO: Implement more sophisticated logging, e.g., to a database table 'audit_logs'
        // Consider using a package like owen-it/laravel-auditing
        // Example:
        // AuditLog::create([
        //     'user_id' => $userId,
        //     'action' => $action,
        //     'auditable_id' => $brand->id,
        //     'auditable_type' => get_class($brand),
        //     'old_values' => $action === 'updated' ? json_encode($brand->getOriginal()) : null,
        //     'new_values' => $action !== 'deleted (soft)' && $action !== 'force deleted' ? json_encode($brand->getAttributes()) : null,
        //     'url' => request()->fullUrl(),
        //     'ip_address' => request()->ip(),
        //     'user_agent' => request()->userAgent(),
        // ]);

        Log::channel('audit')->info($message, [ // Assuming an 'audit' channel is configured in logging.php
            'user_id' => $userId,
            'user_name' => $userName,
            'model_id' => $brand->id,
            'model_type' => get_class($brand),
            'action' => $action,
            'changes' => $changes, // Only for 'updated' usually
            'ip_address' => request()->ip(),
            'url' => request()->fullUrl(),
        ]);
    }
}
