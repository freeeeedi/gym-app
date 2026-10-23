<?php

namespace App\Policies;

use App\Models\Exercise;
use App\Models\User;

class ExercisePolicy
{
    /** Общий каталог виден всем, свои упражнения — только владельцу. */
    public function view(User $user, Exercise $exercise): bool
    {
        return $exercise->user_id === null || $exercise->user_id === $user->id;
    }

    public function delete(User $user, Exercise $exercise): bool
    {
        return $exercise->user_id === $user->id;
    }
}
