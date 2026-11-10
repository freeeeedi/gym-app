<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MealTemplateItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'meal_template_id', 'food_id', 'dish_id', 'name',
        'grams', 'servings', 'kcal', 'protein', 'fat', 'carbs',
    ];

    protected function casts(): array
    {
        return [
            'grams' => 'float',
            'servings' => 'float',
            'kcal' => 'float',
            'protein' => 'float',
            'fat' => 'float',
            'carbs' => 'float',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(MealTemplate::class, 'meal_template_id');
    }
}
