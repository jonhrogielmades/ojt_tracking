<?php

namespace App\Policies;

use App\Models\PartnerCompany;
use App\Models\User;

class PartnerCompanyPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasCapability('companies.manage');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PartnerCompany $partnerCompany): bool
    {
        return $user->hasCapability('companies.manage');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasCapability('companies.manage');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PartnerCompany $partnerCompany): bool
    {
        return $user->hasCapability('companies.manage');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PartnerCompany $partnerCompany): bool
    {
        return $user->hasCapability('companies.manage');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PartnerCompany $partnerCompany): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PartnerCompany $partnerCompany): bool
    {
        return false;
    }
}
