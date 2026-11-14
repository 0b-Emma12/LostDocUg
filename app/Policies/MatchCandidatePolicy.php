<?php

namespace App\Policies;

use App\Models\MatchCandidate;
use App\Models\User;

class MatchCandidatePolicy
{
    public function view(User $user, MatchCandidate $match): bool
    {
        return $match->lostReport->user_id === $user->id
            || $match->foundReport->user_id === $user->id
            || $user->role->value === 'reviewer'
            || $user->role->value === 'admin';
    }

    public function claim(User $user, MatchCandidate $match): bool
    {
        return $match->lostReport->user_id === $user->id
            || $match->foundReport->user_id === $user->id;
    }
}
