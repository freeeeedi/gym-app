<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Exercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'slug', 'name', 'description', 'pattern', 'equipment',
        'mechanic', 'fatigue', 'icon', 'rep_min', 'rep_max',
    ];

    protected function casts(): array
    {
        return [
            'fatigue' => 'integer',
            'rep_min' => 'integer',
            'rep_max' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function muscles(): BelongsToMany
    {
        return $this->belongsToMany(Muscle::class)->withPivot('role');
    }

    /** Общий каталог + собственные упражнения пользователя. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $user->id));
    }

    /** @return list<string> */
    public function primaryMuscles(): array
    {
        return $this->musclesByRole('primary');
    }

    /** @return list<string> */
    public function secondaryMuscles(): array
    {
        return $this->musclesByRole('secondary');
    }

    private function musclesByRole(string $role): array
    {
        return $this->muscles
            ->filter(fn (Muscle $m) => $m->pivot->role === $role)
            ->sortBy('sort')
            ->pluck('slug')
            ->values()
            ->all();
    }

    public function isCompound(): bool
    {
        return $this->mechanic === 'compound';
    }

    public function patternLabel(): string
    {
        return config("training.patterns.{$this->pattern}", $this->pattern);
    }

    public function equipmentLabel(): string
    {
        return config("training.equipment.{$this->equipment}", $this->equipment);
    }

    public function mechanicLabel(): string
    {
        return config("training.mechanics.{$this->mechanic}", $this->mechanic);
    }

    /** Синхронизирует мышцы по slug-ам. */
    public function syncMuscles(array $primary, array $secondary = []): void
    {
        $ids = Muscle::idsBySlug();
        $sync = [];
        foreach ($secondary as $slug) {
            $sync[$ids[$slug]] = ['role' => 'secondary'];
        }
        foreach ($primary as $slug) {
            $sync[$ids[$slug]] = ['role' => 'primary'];
        }
        $this->muscles()->sync($sync);
        $this->unsetRelation('muscles');
    }
}
