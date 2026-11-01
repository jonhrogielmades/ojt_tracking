<?php

namespace App\Policies;

use App\Models\DtrEntry;
use App\Models\User;

class DtrEntryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->canUseStudentFeatures($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, DtrEntry $dtrEntry): bool
    {
        return $this->canUseStudentFeatures($user) && $dtrEntry->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->canUseStudentFeatures($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, DtrEntry $dtrEntry): bool
    {
        return $this->canUseStudentFeatures($user) && $dtrEntry->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DtrEntry $dtrEntry): bool
    {
        return $this->canUseStudentFeatures($user) && $dtrEntry->user_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, DtrEntry $dtrEntry): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, DtrEntry $dtrEntry): bool
    {
        return false;
    }

    private function canUseStudentFeatures(User $user): bool
    {
        return $user->isStudent() && $user->is_active;
    }
}
