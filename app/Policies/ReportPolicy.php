<?php

namespace App\Policies;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Report $report): bool
    {
        return $user->is($report->user);
    }

    public function uploadImage(User $user, Report $report): bool
    {
        return $this->update($user, $report)
            && $report->document_type->allowsImages();
    }

    public function viewImage(User $user, Report $report): bool
    {
        return $this->view($user, $report);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Report $report): bool
    {
        return $user->is($report->user)
            && in_array($report->status, [ReportStatus::Processing, ReportStatus::Active], true);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Report $report): bool
    {
        return $user->is($report->user)
            && ! in_array($report->status, [
                ReportStatus::Matched,
                ReportStatus::UnderReview,
                ReportStatus::Recovered,
            ], true);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Report $report): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Report $report): bool
    {
        return false;
    }
}
