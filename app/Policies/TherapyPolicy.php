<?php

namespace App\Policies;

use App\Models\Therapy;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TherapyPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; 
    }
    

    public function view(User $user, Therapy $therapy): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Therapy $therapy): bool
    {
        return $user->is_admin === true; 
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Therapy $therapy): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Therapy $therapy): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Therapy $therapy): bool
    {
        return false;
    }
}
