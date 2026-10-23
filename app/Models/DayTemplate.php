<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DayTemplate extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'slug', 'name', 'description', 'patterns'];

    protected function casts(): array
    {
        return ['patterns' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function muscles(): BelongsToMany
    {
        return $this->belongsToMany(Muscle::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $user->id));
    }

    /** @return list<string> */
    public function muscleSlugs(): array
    {
        return $this->muscles->sortBy('sort')->pluck('slug')->values()->all();
    }

    public function syncMuscles(array $slugs): void
    {
        $ids = Muscle::idsBySlug();
        $this->muscles()->sync(collect($slugs)->map(fn ($s) => $ids[$s])->all());
        $this->unsetRelation('muscles');
    }
}
