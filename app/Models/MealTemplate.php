<?php

namespace App\Models;

use App\Support\Macros;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Шаблон приёма пищи: сохранённый набор записей дневника.
 */
class MealTemplate extends Model
{
    protected $fillable = ['user_id', 'name'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(MealTemplateItem::class)->orderBy('id');
    }

    public function macros(): Macros
    {
        return Macros::sum($this->items->map(fn (MealTemplateItem $item) => Macros::from($item)));
    }
}
