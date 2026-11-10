<?php

namespace App\Models;

use App\Support\Macros;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Запись дневника питания. КБЖУ — снимок за всю съеденную порцию.
 */
class MealEntry extends Model
{
    protected $fillable = [
        'user_id', 'eaten_on', 'meal', 'food_id', 'dish_id', 'name',
        'grams', 'servings', 'kcal', 'protein', 'fat', 'carbs',
    ];

    protected function casts(): array
    {
        return [
            'eaten_on' => 'date',
            'grams' => 'float',
            'servings' => 'float',
            'kcal' => 'float',
            'protein' => 'float',
            'fat' => 'float',
            'carbs' => 'float',
        ];
    }

    /** Храним чистую дату (Y-m-d) в любой СУБД — важно для сравнений в SQLite. */
    protected function eatenOn(): Attribute
    {
        return Attribute::set(fn ($value) => Carbon::parse($value)->toDateString());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function food(): BelongsTo
    {
        return $this->belongsTo(Food::class);
    }

    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class);
    }

    public function macros(): Macros
    {
        return Macros::from($this);
    }

    /** «150 г», «1,5 порц.» или пусто для ручного ввода за порцию. */
    public function amountLabel(): string
    {
        if ($this->grams !== null) {
            return format_kg($this->grams).' г';
        }
        if ($this->servings !== null) {
            return format_kg($this->servings).' порц.';
        }

        return '';
    }
}
