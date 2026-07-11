<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Meal;

class MealPolicy
{
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'chef']);
    }

    public function update(User $user, Meal $meal): bool
    {
        return in_array($user->role, ['admin', 'chef']);
    }

    public function delete(User $user, Meal $meal): bool
    {
        return in_array($user->role, ['admin', 'chef']);
    }
}
