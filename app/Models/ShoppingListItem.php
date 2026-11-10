<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Блюдо в списке покупок пользователя и число порций, на которое закупаться.
 */
class ShoppingListItem extends Model
{
    protected $fillable = ['user_id', 'dish_id', 'servings'];

    protected function casts(): array
    {
        return ['servings' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class);
    }
}
