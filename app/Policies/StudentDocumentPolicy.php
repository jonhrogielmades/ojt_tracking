<?php

namespace App\Policies;

use App\Models\StudentDocument;
use App\Models\User;

class StudentDocumentPolicy
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
    public function view(User $user, StudentDocument $studentDocument): bool
    {
        return $this->canUseStudentFeatures($user) && $studentDocument->user_id === $user->id;
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
    public function update(User $user, StudentDocument $studentDocument): bool
    {
        return $this->canUseStudentFeatures($user)
            && $studentDocument->user_id === $user->id
            && $studentDocument->review_status !== 'approved';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, StudentDocument $studentDocument): bool
    {
        return $this->canUseStudentFeatures($user)
            && $studentDocument->user_id === $user->id
            && $studentDocument->review_status !== 'approved';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, StudentDocument $studentDocument): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, StudentDocument $studentDocument): bool
    {
        return false;
    }

    public function review(User $user, StudentDocument $studentDocument): bool
    {
        return $user->hasCapability('documents.manage');
    }

    private function canUseStudentFeatures(User $user): bool
    {
        return $user->isStudent() && $user->is_active;
    }
}
