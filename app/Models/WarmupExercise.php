<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class WarmupExercise extends Model
{
    protected $fillable = ['slug', 'name', 'type', 'minutes', 'reps', 'joints', 'description', 'icon'];

    protected function casts(): array
    {
        return ['joints' => 'array', 'minutes' => 'integer'];
    }

    public function muscles(): BelongsToMany
    {
        return $this->belongsToMany(Muscle::class);
    }

    /** @return list<string> */
    public function muscleSlugs(): array
    {
        return $this->muscles->pluck('slug')->all();
    }
}
