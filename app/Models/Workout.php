<?php

namespace App\Models;

use App\Enums\WorkoutStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workout extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'day_template_id', 'title', 'scheduled_on', 'status',
        'started_at', 'finished_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_on' => 'date',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'status' => WorkoutStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(DayTemplate::class, 'day_template_id');
    }

    public function workoutExercises(): HasMany
    {
        return $this->hasMany(WorkoutExercise::class)->orderBy('position')->orderBy('id');
    }

    public function isCompleted(): bool
    {
        return $this->status === WorkoutStatus::Completed;
    }

    public function isInProgress(): bool
    {
        return $this->status === WorkoutStatus::InProgress;
    }

    public function durationMinutes(): ?int
    {
        if (! $this->started_at || ! $this->finished_at) {
            return null;
        }

        return (int) round($this->started_at->diffInSeconds($this->finished_at) / 60);
    }
}
