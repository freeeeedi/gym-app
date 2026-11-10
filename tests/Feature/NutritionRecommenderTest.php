<?php

use App\Models\Dish;
use App\Models\Food;
use App\Services\Nutrition\NutritionCalculator;
use App\Services\Nutrition\NutritionRecommender;
use App\Support\NutritionProfile;

beforeEach(function () {
    seedCatalog();
    $this->recommender = app(NutritionRecommender::class);
    $this->foods = Food::orderBy('name')->get();
    $this->dishes = Dish::with('ingredients.food')->orderBy('name')->get();
});

function profileFor(string $goal, array $restrictions = []): NutritionProfile
{
    return new NutritionProfile(sex: 'male', age: 30, heightCm: 180.0, weightKg: 80.0, goal: $goal, restrictions: $restrictions);
}

function recommendedSlugs(array $result): array
{
    return collect($result['groups'])->flatten()->map(fn ($s) => $s->food->slug)->all();
}

it('каталог согласован со справочниками: категории, признаки, приёмы пищи', function () {
    $categories = array_keys(config('nutrition.food_categories'));
    $tags = array_keys(config('nutrition.food_tags'));
    $meals = array_keys(config('nutrition.meals'));

    expect($this->foods->count())->toBeGreaterThan(120)->and($this->dishes->count())->toBeGreaterThan(20);

    foreach ($this->foods as $food) {
        expect($categories)->toContain($food->category)
            ->and(array_diff($food->tagList(), $tags))->toBe([], "Неизвестный признак у {$food->slug}")
            ->and($food->kcal)->toBeGreaterThan(0)
            ->and($food->protein + $food->fat + $food->carbs)->toBeLessThanOrEqual(100.5, "БЖУ больше 100 г у {$food->slug}");
    }
    foreach ($this->dishes as $dish) {
        expect(array_diff($dish->meals, $meals))->toBe([])
            ->and($dish->ingredients->count())->toBeGreaterThan(1)
            ->and($dish->perServing()->kcal)->toBeBetween(150, 900, "Странная калорийность порции у {$dish->slug}");
    }
});

it('назначает продукту роль в рационе по правилам', function () {
    $role = fn (string $slug) => $this->recommender->foodRole($this->foods->firstWhere('slug', $slug));

    expect($role('chicken-breast'))->toBe('protein')
        ->and($role('cottage-cheese-0'))->toBe('protein')
        ->and($role('salmon'))->toBe('protein')
        ->and($role('buckwheat-dry'))->toBe('carbs')
        ->and($role('lentils-cooked'))->toBe('carbs')
        ->and($role('olive-oil'))->toBe('fat')
        ->and($role('walnuts'))->toBe('fat')
        ->and($role('broccoli'))->toBe('produce')
        ->and($role('banana'))->toBe('produce')
        // Не рекомендуются: «не на каждый день» и продукты без роли.
        ->and($role('sugar'))->toBeNull()
        ->and($role('sausages'))->toBeNull()
        ->and($role('butter'))->toBeNull()
        ->and($role('bread-white'))->toBeNull()
        ->and($role('hard-cheese'))->toBeNull();
});

it('не предлагает продукты, исключённые ограничениями, и продукты «не на каждый день»', function () {
    $result = $this->recommender->foods($this->foods, profileFor('maintain', ['vegan', 'no_nuts']));
    $bySlug = $this->foods->keyBy('slug');

    expect($result['excluded'])->toBeGreaterThan(40);
    foreach (recommendedSlugs($result) as $slug) {
        expect(array_intersect($bySlug[$slug]->tagList(), ['meat', 'fish', 'dairy', 'eggs', 'nuts', 'occasional']))
            ->toBe([], "$slug не должен попадать в рекомендации");
    }
    // Белок веганам — бобовые и соя.
    expect(array_map(fn ($s) => $s->food->slug, $result['groups']['protein']))->toContain('tofu');
});

it('без ограничений ничего не скрывает и заполняет все роли', function () {
    $result = $this->recommender->foods($this->foods, profileFor('maintain'));

    expect($result['excluded'])->toBe(0)
        ->and(array_keys($result['groups']))->toBe(['protein', 'carbs', 'fat', 'produce']);
    foreach ($result['groups'] as $role => $suggestions) {
        expect($suggestions)->not->toBeEmpty()
            ->and(count($suggestions))->toBeLessThanOrEqual(config('nutrition.recommendation.foods_per_role'));
        foreach ($suggestions as $suggestion) {
            expect($suggestion->role)->toBe($role)->and($suggestion->reasons)->not->toBeEmpty();
        }
    }
});

it('ранжирует продукты по цели: на сушке выше низкокалорийные, на наборе — калорийные', function () {
    $score = fn (string $slug, string $goal) => $this->recommender
        ->suggestFood($this->foods->firstWhere('slug', $slug), $goal)->score;

    // Одна и та же крупа: сухая калорийнее варёной.
    expect($score('buckwheat-cooked', 'cut'))->toBeGreaterThan($score('buckwheat-dry', 'cut'))
        ->and($score('buckwheat-dry', 'gain'))->toBeGreaterThan($score('buckwheat-cooked', 'gain'))
        // Источники белка: на сушке обезжиренный творог выше лосося.
        ->and($score('cottage-cheese-0', 'cut'))->toBeGreaterThan($score('salmon', 'cut'));

    $cut = $this->recommender->suggestFood($this->foods->firstWhere('slug', 'chicken-breast'), 'cut');
    $nuts = $this->recommender->suggestFood($this->foods->firstWhere('slug', 'walnuts'), 'cut');
    $nutsGain = $this->recommender->suggestFood($this->foods->firstWhere('slug', 'walnuts'), 'gain');

    expect($cut->reasons)->toContain('Много белка: 23,6 г на 100 г', 'Низкая калорийность: 113 ккал на 100 г')
        ->and($nuts->reasons)->toContain('Калорийно — отмеряйте порцию')
        ->and($nutsGain->reasons)->toContain('Калорийный продукт: проще добрать норму');

    $carbsOnCut = array_map(fn ($s) => $s->food->slug, $this->recommender->foods($this->foods, profileFor('cut'))['groups']['carbs']);
    $carbsOnGain = array_map(fn ($s) => $s->food->slug, $this->recommender->foods($this->foods, profileFor('gain'))['groups']['carbs']);
    expect($carbsOnCut)->toContain('buckwheat-cooked')->not->toContain('buckwheat-dry')
        ->and($carbsOnGain)->toContain('buckwheat-dry');
});

it('блюда: исключает запрещённые ингредиенты и объясняет выбор под цель', function () {
    $slugs = fn (array $result) => array_map(fn ($s) => $s->dish->slug, $result['suggestions']);

    $all = $this->recommender->dishes($this->dishes, profileFor('maintain'));
    expect($all['excluded'])->toBe(0)->and($all['suggestions'])->toHaveCount($this->dishes->count());

    $vegan = $this->recommender->dishes($this->dishes, profileFor('maintain', ['vegan']));
    expect($slugs($vegan))->toContain('lentil-soup', 'tofu-vegetable-rice', 'buckwheat-mushrooms', 'hummus-vegetables')
        ->not->toContain('chicken-buckwheat-vegetables', 'vegetable-omelet', 'cottage-cheese-berries')
        ->and($vegan['excluded'])->toBe($this->dishes->count() - count($vegan['suggestions']));

    $glutenFree = $this->recommender->dishes($this->dishes, profileFor('maintain', ['gluten_free']));
    expect($slugs($glutenFree))->toContain('chicken-buckwheat-vegetables', 'baked-salmon-rice-broccoli')
        ->not->toContain('turkey-tomato-pasta', 'oatmeal-banana-nuts', 'tofu-vegetable-rice');

    $noLactose = $this->recommender->dishes($this->dishes, profileFor('maintain', ['lactose_free']));
    expect($slugs($noLactose))->not->toContain('cottage-cheese-berries', 'protein-banana-shake');
});

it('блюда: на сушке отмечает лёгкие порции, на наборе — сытные, везде — белковые', function () {
    $calculator = app(NutritionCalculator::class);
    $reasons = function (string $goal, string $slug) use ($calculator) {
        $profile = profileFor($goal);
        $result = $this->recommender->dishes($this->dishes, $profile, $calculator->targets($profile));

        return implode(' | ', collect($result['suggestions'])->first(fn ($s) => $s->dish->slug === $slug)->reasons);
    };

    expect($reasons('cut', 'baked-cod-vegetables'))->toContain('Лёгкая порция')->toContain('Много белка')
        ->and($reasons('gain', 'baked-cod-vegetables'))->not->toContain('Сытная порция')
        ->and($reasons('gain', 'homemade-gainer'))->toContain('Сытная порция')
        ->and($reasons('cut', 'homemade-gainer'))->not->toContain('Лёгкая порция');

    // Сортировка по убыванию очков.
    $profile = profileFor('cut');
    $scores = array_map(fn ($s) => $s->score, $this->recommender->dishes($this->dishes, $profile, $calculator->targets($profile))['suggestions']);
    $sorted = $scores;
    rsort($sorted);
    expect($scores)->toBe($sorted);
});
