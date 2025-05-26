<?php

namespace DryRun\Brands\Policies;

use App\Models\User; // Assuming your global User model
use DryRun\Brands\Models\Brand;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class BrandPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     *
     * @param  \App\Models\User  $user
     * @param  string  $ability
     * @return void|bool
     */
    // public function before(User $user, $ability)
    // {
    //     // Example: Give admins all permissions
    //     // if ($user->hasRole('Super Admin') || $user->hasDirectPermissionTo('bypass policies')) {
    //     //     return true;
    //     // }
    //     // return null; // Defer to other methods
    // }

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User|null  $user  // Nullable for guest access if needed
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(?User $user)
    {
        // TODO: Implement logic based on roles/permissions
        // Example: return $user && ($user->hasPermissionTo('view any brands') || $user->hasRole('Editor'));
        return true; // Default: allow if authenticated, or always if $user is nullable and guest access is intended
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User|null  $user
     * @param  \DryRun\Brands\Models\Brand  $brand
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(?User $user, Brand $brand)
    {
        // TODO: Implement logic
        // Example: return $user && ($user->hasPermissionTo('view brands') || $user->id === $brand->user_id);
        return true;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        // TODO: Implement logic
        // Example: return $user->hasPermissionTo('create brands');
        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \DryRun\Brands\Models\Brand  $brand
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Brand $brand)
    {
        // TODO: Implement logic
        // Example: return $user->hasPermissionTo('update brands') || $user->id === $brand->user_id;
        return true;
    }

    /**
     * Determine whether the user can delete the model (soft delete).
     *
     * @param  \App\Models\User  $user
     * @param  \DryRun\Brands\Models\Brand  $brand
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Brand $brand)
    {
        // TODO: Implement logic
        // Example: return $user->hasPermissionTo('delete brands') || $user->id === $brand->user_id;
        return true;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \DryRun\Brands\Models\Brand  $brand
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Brand $brand)
    {
        // TODO: Implement logic
        // Example: return $user->hasPermissionTo('restore brands');
        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \DryRun\Brands\Models\Brand  $brand
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Brand $brand)
    {
        // TODO: Implement logic
        // Example: return $user->hasPermissionTo('force delete brands') && $user->hasRole('Administrator');
        return true;
    }
}
