<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DishIngredient extends Model
{
    public $timestamps = false;

    protected $fillable = ['dish_id', 'food_id', 'grams', 'position'];

    protected function casts(): array
    {
        return ['grams' => 'float', 'position' => 'integer'];
    }

    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class);
    }

    public function food(): BelongsTo
    {
        return $this->belongsTo(Food::class);
    }
}
