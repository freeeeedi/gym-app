<?php

namespace App\Models;

use App\Support\Macros;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Блюдо из продуктов каталога. КБЖУ не хранится, а считается из ингредиентов.
 * Для расчётов связь ingredients.food должна быть загружена.
 */
class Dish extends Model
{
    protected $fillable = ['slug', 'name', 'meals', 'servings', 'cook_minutes', 'recipe'];

    protected function casts(): array
    {
        return [
            'meals' => 'array',
            'servings' => 'integer',
            'cook_minutes' => 'integer',
        ];
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(DishIngredient::class)->orderBy('position')->orderBy('id');
    }

    /** КБЖУ всего рецепта. */
    public function totalMacros(): Macros
    {
        return Macros::sum($this->ingredients->map(fn (DishIngredient $i) => $i->food->macrosFor($i->grams)));
    }

    /** КБЖУ одной порции. */
    public function perServing(): Macros
    {
        return $this->totalMacros()->times(1 / max(1, $this->servings))->rounded();
    }

    /** Вес порции по сырым ингредиентам, г (без учёта уварки и воды). */
    public function servingGrams(): float
    {
        return round($this->ingredients->sum('grams') / max(1, $this->servings));
    }

    /**
     * Признаки всех ингредиентов («содержит»).
     *
     * @return list<string>
     */
    public function tagList(): array
    {
        return $this->ingredients
            ->flatMap(fn (DishIngredient $i) => $i->food->tagList())
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Пометки для карточки: «веганское», «без глютена» и т. п.
     *
     * @return list<string>
     */
    public function dietLabels(): array
    {
        $tags = $this->tagList();
        $has = fn (string ...$any) => array_intersect($any, $tags) !== [];
        $labels = [];

        if (! $has('meat', 'fish', 'dairy', 'eggs')) {
            $labels[] = 'веганское';
        } elseif (! $has('meat', 'fish')) {
            $labels[] = 'вегетарианское';
        }
        if (! $has('gluten')) {
            $labels[] = 'без глютена';
        }
        if (! $has('lactose')) {
            $labels[] = 'без лактозы';
        }

        return $labels;
    }

    /** @return list<string> названия приёмов пищи */
    public function mealLabels(): array
    {
        return array_map(fn (string $meal) => config("nutrition.meals.$meal", $meal), $this->meals ?? []);
    }
}
