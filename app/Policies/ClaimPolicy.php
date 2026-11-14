<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Claim;
use App\Models\MatchCandidate;
use App\Models\User;

class ClaimPolicy
{
    public function claim(User $user, MatchCandidate $match): bool
    {
        return $match->lostReport->user_id === $user->id
            || $match->foundReport->user_id === $user->id;
    }

    public function withdraw(User $user, Claim $claim): bool
    {
        return $claim->claimant_id === $user->id;
    }

    public function view(User $user, Claim $claim): bool
    {
        return $claim->claimant_id === $user->id
            || $claim->reviewer_id === $user->id
            || $user->role === UserRole::Reviewer
            || $user->role === UserRole::Admin;
    }

    public function review(User $user, Claim $claim): bool
    {
        return $user->role === UserRole::Reviewer
            || $user->role === UserRole::Admin;
    }
}
