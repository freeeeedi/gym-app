<?php

namespace App\Models;

use App\Support\Macros;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Продукт каталога: КБЖУ на 100 г, признаки («содержит») и типичная порция.
 */
class Food extends Model
{
    protected $table = 'foods';

    protected $fillable = [
        'user_id', 'slug', 'name', 'category', 'kcal', 'protein', 'fat', 'carbs',
        'tags', 'serving_g', 'serving_name',
    ];

    protected function casts(): array
    {
        return [
            'kcal' => 'float',
            'protein' => 'float',
            'fat' => 'float',
            'carbs' => 'float',
            'tags' => 'array',
            'serving_g' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Общий каталог + собственные продукты пользователя. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(fn ($q) => $q->whereNull('foods.user_id')->orWhere('foods.user_id', $user->id));
    }

    /** КБЖУ на 100 г. */
    public function per100(): Macros
    {
        return Macros::from($this);
    }

    /** КБЖУ для заданной граммовки. */
    public function macrosFor(float $grams): Macros
    {
        return $this->per100()->times($grams / 100);
    }

    /** @return list<string> */
    public function tagList(): array
    {
        return $this->tags ?? [];
    }

    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tagList(), true);
    }

    public function categoryLabel(): string
    {
        return config("nutrition.food_categories.{$this->category}", $this->category);
    }
}
