<?php

namespace App\Enums;

enum WorkoutStatus: string
{
    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    public function label(): string
    {
        return config('training.statuses.'.$this->value);
    }
}
