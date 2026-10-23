<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Muscle extends Model
{
    public $timestamps = false;

    protected $fillable = ['slug', 'name', 'sort'];

    /** Карта slug => id. */
    public static function idsBySlug(): Collection
    {
        return static::query()->pluck('id', 'slug');
    }

    public static function label(string $slug): string
    {
        return config("training.muscles.$slug", $slug);
    }
}
