<?php

namespace App\Models;

use App\Support\OneRepMax;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutSet extends Model
{
    protected $fillable = ['workout_exercise_id', 'position', 'weight_kg', 'reps', 'is_warmup'];

    protected function casts(): array
    {
        return [
            'weight_kg' => 'float',
            'reps' => 'integer',
            'is_warmup' => 'boolean',
        ];
    }

    public function workoutExercise(): BelongsTo
    {
        return $this->belongsTo(WorkoutExercise::class);
    }

    public function volume(): float
    {
        return $this->weight_kg * $this->reps;
    }

    public function estimatedOneRepMax(): float
    {
        return OneRepMax::epley($this->weight_kg, $this->reps);
    }
}
