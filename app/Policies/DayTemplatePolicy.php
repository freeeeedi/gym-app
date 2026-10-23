<?php

namespace App\Policies;

use App\Models\DayTemplate;
use App\Models\User;

class DayTemplatePolicy
{
    public function view(User $user, DayTemplate $template): bool
    {
        return $template->user_id === null || $template->user_id === $user->id;
    }

    /** Стандартные шаблоны не редактируются — их можно скопировать. */
    public function update(User $user, DayTemplate $template): bool
    {
        return $template->user_id === $user->id;
    }

    public function delete(User $user, DayTemplate $template): bool
    {
        return $template->user_id === $user->id;
    }
}
