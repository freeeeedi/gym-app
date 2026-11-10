<?php

use App\Livewire\MealDiary;
use App\Models\Dish;
use App\Models\Food;
use App\Models\MealEntry;
use App\Models\User;
use App\Services\Nutrition\NutritionCalculator;
use App\Services\Nutrition\NutritionDiary;
use App\Services\Nutrition\ShoppingListBuilder;
use App\Support\Macros;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    seedCatalog();
    Carbon::setTestNow('2026-11-11 12:00'); // среда
    $this->user = User::factory()->create();
    $this->diary = app(NutritionDiary::class);
    $this->today = Carbon::today();
});

function food(string $slug): Food
{
    return Food::where('slug', $slug)->firstOrFail();
}

/** Профиль из тестов расчёта: норма 2760 ккал, Б 128, Ж 72, У 400. */
function fillNutritionProfile(User $user, array $overrides = []): void
{
    $user->update(['nutrition_profile' => [
        'sex' => 'male', 'age' => 30, 'height_cm' => 180, 'activity' => 'moderate', 'goal' => 'maintain', 'restrictions' => [],
        ...$overrides,
    ]]);
    $user->weightEntries()->create(['measured_on' => Carbon::today(), 'weight_kg' => 80]);
}

it('считает КБЖУ продукта по граммовке и сумму за день по приёмам пищи', function () {
    // Куриная грудка: 113 ккал, Б 23,6, Ж 1,9, У 0,4 на 100 г; гречка отварная: 110 / 4,2 / 1,1 / 21,3.
    $chicken = $this->diary->addFood($this->user, $this->today, 'lunch', food('chicken-breast'), 200);
    $this->diary->addFood($this->user, $this->today, 'lunch', food('buckwheat-cooked'), 200);
    $this->diary->addCustom($this->user, $this->today, 'snack', 'Пирожное', new Macros(300, 4, 20, 25));

    // Не должны попасть в сумму: другой день и другой пользователь.
    $this->diary->addFood($this->user, $this->today->copy()->subDay(), 'lunch', food('chicken-breast'), 500);
    $this->diary->addFood(User::factory()->create(), $this->today, 'lunch', food('chicken-breast'), 500);

    expect($chicken->macros()->toArray())->toBe(['kcal' => 226.0, 'protein' => 47.2, 'fat' => 3.8, 'carbs' => 0.8]);

    $totals = $this->diary->dayTotals($this->user, $this->today);
    expect($totals->toArray())->toBe(['kcal' => 746.0, 'protein' => 59.6, 'fat' => 26.0, 'carbs' => 68.4]);

    $day = $this->diary->day($this->user, $this->today);
    expect($day['count'])->toBe(3)
        ->and($day['totals']->kcal)->toBe(746.0)
        ->and($day['meals']['lunch']['totals']->kcal)->toBe(446.0)
        ->and($day['meals']['lunch']['entries'])->toHaveCount(2)
        ->and($day['meals']['snack']['totals']->kcal)->toBe(300.0)
        ->and($day['meals']['breakfast']['entries'])->toHaveCount(0)
        ->and(array_keys($day['meals']))->toBe(['breakfast', 'lunch', 'dinner', 'snack']);
});

it('считает остаток до дневной нормы и перебор', function () {
    fillNutritionProfile($this->user);
    $targets = app(NutritionCalculator::class)->targets($this->user->nutritionProfile());
    $this->diary->addCustom($this->user, $this->today, 'lunch', 'Обед', new Macros(1000, 50, 30, 100));

    $remaining = $this->diary->remaining($targets->macros(), $this->diary->dayTotals($this->user, $this->today));
    expect($remaining->toArray())->toBe(['kcal' => 1760.0, 'protein' => 78.0, 'fat' => 42.0, 'carbs' => 300.0]);

    $this->diary->addCustom($this->user, $this->today, 'dinner', 'Застолье', new Macros(2500, 60, 150, 200));
    $over = $this->diary->remaining($targets->macros(), $this->diary->dayTotals($this->user, $this->today));
    expect($over->kcal)->toBe(-740.0)->and($over->fat)->toBe(-108.0);
});

it('считает сумму за неделю с понедельника по воскресенье и сравнивает с недельной нормой', function () {
    fillNutritionProfile($this->user);
    $targets = app(NutritionCalculator::class)->targets($this->user->nutritionProfile());

    $add = fn (string $date, float $kcal, float $p, float $f, float $c) => $this->diary
        ->addCustom($this->user, Carbon::parse($date), 'lunch', 'Еда', new Macros($kcal, $p, $f, $c));
    $add('2026-11-09', 1000, 50, 30, 100);  // понедельник
    $add('2026-11-11', 900, 45, 30, 90);    // среда, две записи
    $add('2026-11-11', 600, 30, 20, 60);
    $add('2026-11-15', 500, 25, 15, 50);    // воскресенье
    $add('2026-11-08', 999, 99, 99, 99);    // прошлая неделя
    $add('2026-11-16', 999, 99, 99, 99);    // следующая неделя

    $week = $this->diary->week($this->user, $this->today, $targets);

    expect($week['start']->toDateString())->toBe('2026-11-09')
        ->and($week['end']->toDateString())->toBe('2026-11-15')
        ->and($week['days'])->toHaveCount(7)
        ->and(array_map(fn ($d) => $d['totals']->kcal, $week['days']))->toBe([1000.0, 0.0, 1500.0, 0.0, 0.0, 0.0, 500.0])
        ->and(array_map(fn ($d) => $d['logged'], $week['days']))->toBe([true, false, true, false, false, false, true])
        ->and($week['total']->toArray())->toBe(['kcal' => 3000.0, 'protein' => 150.0, 'fat' => 95.0, 'carbs' => 300.0])
        ->and($week['logged_days'])->toBe(3)
        ->and($week['average']->kcal)->toBe(1000.0)
        ->and($week['average']->protein)->toBe(50.0)
        // Недельная норма — суточная × 7: 2760 × 7 ккал, 128 × 7 г белка.
        ->and($week['target']->kcal)->toBe(19320.0)
        ->and($week['target']->protein)->toBe(896.0)
        ->and($week['remaining']->kcal)->toBe(16320.0)
        ->and($week['remaining']->protein)->toBe(746.0);
});

it('неделя без профиля и без записей: нули и нет нормы', function () {
    $week = $this->diary->week($this->user, $this->today);

    expect($week['total']->isZero())->toBeTrue()
        ->and($week['logged_days'])->toBe(0)
        ->and($week['average']->isZero())->toBeTrue()
        ->and($week['target'])->toBeNull()
        ->and($week['remaining'])->toBeNull();
});

it('пересчитывает КБЖУ при изменении граммовки записи', function () {
    $entry = $this->diary->addFood($this->user, $this->today, 'lunch', food('chicken-breast'), 200);
    $this->diary->rescale($entry, 100);

    expect($entry->fresh()->grams)->toBe(100.0)
        ->and($entry->fresh()->macros()->toArray())->toBe(['kcal' => 113.0, 'protein' => 23.6, 'fat' => 1.9, 'carbs' => 0.4]);
});

it('считает КБЖУ порции блюда из ингредиентов и записывает блюдо порциями', function () {
    $a = Food::create(['name' => 'А', 'category' => 'other', 'kcal' => 100, 'protein' => 10, 'fat' => 5, 'carbs' => 2, 'tags' => ['gluten']]);
    $b = Food::create(['name' => 'Б', 'category' => 'other', 'kcal' => 300, 'protein' => 4, 'fat' => 20, 'carbs' => 30, 'tags' => ['dairy']]);
    $dish = Dish::create(['slug' => 'test-dish', 'name' => 'Тестовое блюдо', 'meals' => ['lunch'], 'servings' => 2]);
    $dish->ingredients()->create(['food_id' => $a->id, 'grams' => 300]);
    $dish->ingredients()->create(['food_id' => $b->id, 'grams' => 100]);
    $dish->load('ingredients.food');

    // Весь рецепт: 300 + 300 ккал, Б 30 + 4, Ж 15 + 20, У 6 + 30; порций — 2.
    expect($dish->totalMacros()->toArray())->toBe(['kcal' => 600.0, 'protein' => 34.0, 'fat' => 35.0, 'carbs' => 36.0])
        ->and($dish->perServing()->toArray())->toBe(['kcal' => 300.0, 'protein' => 17.0, 'fat' => 17.5, 'carbs' => 18.0])
        ->and($dish->servingGrams())->toBe(200.0)
        ->and($dish->tagList())->toBe(['gluten', 'dairy'])
        ->and($dish->dietLabels())->toBe(['вегетарианское', 'без лактозы']);

    $entry = $this->diary->addDish($this->user, $this->today, 'lunch', $dish, 1.5);
    expect($entry->kcal)->toBe(450.0)->and($entry->protein)->toBe(25.5)->and($entry->servings)->toBe(1.5)->and($entry->amountLabel())->toBe('1,5 порц.');

    // Список покупок: 3 порции блюда на 2 порции — множитель 1,5.
    $this->user->shoppingListItems()->create(['dish_id' => $dish->id, 'servings' => 3]);
    $groups = app(ShoppingListBuilder::class)->build($this->user->shoppingListItems()->with('dish.ingredients.food')->get());
    expect(array_keys($groups))->toBe(['other'])
        ->and(array_map(fn ($row) => [$row['food']->name, $row['grams']], $groups['other']))->toBe([['А', 450.0], ['Б', 150.0]]);
});

it('складывает в списке покупок одинаковые продукты разных блюд', function () {
    $soup = Dish::where('slug', 'lentil-soup')->firstOrFail();          // лук 100 г на 4 порции
    $buckwheat = Dish::where('slug', 'buckwheat-mushrooms')->firstOrFail(); // лук 100 г на 2 порции
    $this->user->shoppingListItems()->create(['dish_id' => $soup->id, 'servings' => 4]);
    $this->user->shoppingListItems()->create(['dish_id' => $buckwheat->id, 'servings' => 4]);

    $groups = app(ShoppingListBuilder::class)->build($this->user->shoppingListItems()->with('dish.ingredients.food')->get());
    $onion = collect($groups['vegetables'])->first(fn ($row) => $row['food']->slug === 'onion');

    expect($onion['grams'])->toBe(300.0); // 100 + 100 × 2
});

it('копирует предыдущий день и сохраняет приём пищи как шаблон', function () {
    $yesterday = $this->today->copy()->subDay();
    $this->diary->addFood($this->user, $yesterday, 'breakfast', food('oats'), 60);
    $this->diary->addFood($this->user, $yesterday, 'breakfast', food('banana'), 120);
    $this->diary->addCustom($this->user, $yesterday, 'snack', 'Эклер', new Macros(330, 5, 22, 28));

    expect($this->diary->copyDay($this->user, $yesterday, $this->today))->toBe(3)
        ->and($this->diary->dayTotals($this->user, $this->today)->toArray())
        ->toBe($this->diary->dayTotals($this->user, $yesterday)->toArray());

    $breakfast = $this->diary->day($this->user, $yesterday)['meals']['breakfast'];
    $template = $this->diary->saveTemplate($this->user, 'Мой завтрак', $breakfast['entries'])->load('items');
    expect($template->items)->toHaveCount(2)->and($template->macros()->kcal)->toBe($breakfast['totals']->kcal);

    $tomorrow = $this->today->copy()->addDay();
    $this->diary->applyTemplate($this->user, $template, $tomorrow, 'dinner');
    $day = $this->diary->day($this->user, $tomorrow);
    expect($day['meals']['dinner']['entries'])->toHaveCount(2)->and($day['totals']->kcal)->toBe($breakfast['totals']->kcal);
});

it('помнит недавние продукты и последнюю граммовку', function () {
    $this->diary->addFood($this->user, $this->today, 'lunch', food('rice-cooked'), 180);
    $this->diary->addFood($this->user, $this->today, 'lunch', food('egg'), 110);
    $this->diary->addFood($this->user, $this->today, 'dinner', food('rice-cooked'), 220);

    expect($this->diary->recentFoods($this->user)->pluck('slug')->all())->toBe(['rice-cooked', 'egg'])
        ->and($this->diary->suggestedGrams($this->user, food('rice-cooked')))->toBe(220.0)
        ->and($this->diary->suggestedGrams($this->user, food('banana')))->toBe(120.0)  // типичная порция
        ->and($this->diary->suggestedGrams($this->user, food('cod')))->toBe(100.0);    // по умолчанию
});

// --- Интерфейс дневника (Livewire) ---

it('дневник: поиск продукта, ввод граммовки и запись в приём пищи', function () {
    $buckwheat = food('buckwheat-cooked');

    Livewire::actingAs($this->user)->test(MealDiary::class)
        ->call('openAdd', 'lunch')
        ->assertSet('addingTo', 'lunch')
        ->set('search', 'ГРЕЧК')
        ->assertSee('Гречка отварная')
        ->assertDontSee('Рис отварной')
        ->call('selectFood', $buckwheat->id)
        ->assertSet('grams', '100')
        ->set('grams', '150')
        ->call('addFood')
        ->assertHasNoErrors()
        ->assertSet('foodId', null)
        ->assertSee('Добавлено: Гречка отварная, 150 г')
        ->call('closeAdd')
        ->assertSee('165');

    $entry = $this->user->mealEntries()->sole();
    expect($entry->meal)->toBe('lunch')
        ->and($entry->eaten_on->toDateString())->toBe('2026-11-11')
        ->and($entry->food_id)->toBe($buckwheat->id)
        ->and($entry->grams)->toBe(150.0)
        ->and($entry->kcal)->toBe(165.0);
});

it('дневник: граммовка обязательна и должна быть числом', function () {
    Livewire::actingAs($this->user)->test(MealDiary::class)
        ->call('openAdd', 'lunch')
        ->call('selectFood', food('egg')->id)
        ->set('grams', 'много')
        ->call('addFood')
        ->assertHasErrors('grams');

    expect($this->user->mealEntries()->count())->toBe(0);
});

it('дневник: свой продукт вручную — КБЖУ за порцию, запятая как разделитель', function () {
    Livewire::actingAs($this->user)->test(MealDiary::class)
        ->call('openAdd', 'snack')
        ->call('setTab', 'custom')
        ->set('custom.name', 'Пирожное из кофейни')
        ->set('custom.kcal', '320')
        ->set('custom.protein', '4,5')
        ->set('custom.fat', '18')
        ->set('custom.carbs', '35,5')
        ->call('addCustom')
        ->assertHasNoErrors()
        ->assertSet('custom.name', '');

    $entry = $this->user->mealEntries()->sole();
    expect($entry->name)->toBe('Пирожное из кофейни')
        ->and($entry->meal)->toBe('snack')
        ->and($entry->food_id)->toBeNull()
        ->and($entry->grams)->toBeNull()
        ->and($entry->macros()->toArray())->toBe(['kcal' => 320.0, 'protein' => 4.5, 'fat' => 18.0, 'carbs' => 35.5]);
});

it('дневник: калории своего продукта считаются из БЖУ, если не введены', function () {
    Livewire::actingAs($this->user)->test(MealDiary::class)
        ->call('openAdd', 'snack')
        ->call('setTab', 'custom')
        ->set('custom.name', 'Батончик')
        ->set('custom.protein', '10')
        ->set('custom.fat', '10')
        ->set('custom.carbs', '10')
        ->call('addCustom')
        ->assertHasNoErrors();

    expect($this->user->mealEntries()->sole()->kcal)->toBe(170.0); // 10×4 + 10×9 + 10×4
});

it('дневник: свой продукт на 100 г пересчитывается по весу и сохраняется в мои продукты', function () {
    Livewire::actingAs($this->user)->test(MealDiary::class)
        ->call('openAdd', 'dinner')
        ->call('setTab', 'custom')
        ->set('custom.name', 'Сыр из лавки')
        ->set('custom.basis', 'per100')
        ->set('custom.kcal', '350')
        ->set('custom.protein', '25')
        ->set('custom.fat', '27')
        ->set('custom.carbs', '1')
        ->set('custom.grams', '40')
        ->set('custom.save', true)
        ->call('addCustom')
        ->assertHasNoErrors();

    $food = $this->user->foods()->sole();
    $entry = $this->user->mealEntries()->sole();
    expect($food->name)->toBe('Сыр из лавки')->and($food->kcal)->toBe(350.0)
        ->and($entry->food_id)->toBe($food->id)
        ->and($entry->grams)->toBe(40.0)
        ->and($entry->kcal)->toBe(140.0)
        ->and($entry->protein)->toBe(10.0);
});

it('дневник: свой продукт требует название, КБЖУ и вес для режима «на 100 г»', function () {
    Livewire::actingAs($this->user)->test(MealDiary::class)
        ->call('openAdd', 'snack')
        ->call('setTab', 'custom')
        ->call('addCustom')
        ->assertHasErrors(['custom.name', 'custom.kcal'])
        ->set('custom.name', 'Что-то')
        ->set('custom.kcal', '100')
        ->set('custom.basis', 'per100')
        ->call('addCustom')
        ->assertHasErrors('custom.grams');

    expect($this->user->mealEntries()->count())->toBe(0);
});

it('дневник: правка количества, удаление и защита чужих записей', function () {
    $entry = $this->diary->addFood($this->user, $this->today, 'lunch', food('chicken-breast'), 200);
    $foreign = $this->diary->addFood(User::factory()->create(), $this->today, 'lunch', food('chicken-breast'), 200);

    $component = Livewire::actingAs($this->user)->test(MealDiary::class)
        ->call('startEdit', $entry->id)
        ->assertSet('editAmount', '200')
        ->set('editAmount', '150,5')
        ->call('saveEdit')
        ->assertHasNoErrors()
        ->assertSet('editingId', null);
    expect($entry->fresh()->grams)->toBe(150.5);

    expect(fn () => $component->call('deleteEntry', $foreign->id))->toThrow(ModelNotFoundException::class);
    expect(fn () => $component->call('startEdit', $foreign->id))->toThrow(ModelNotFoundException::class);
    expect(MealEntry::find($foreign->id))->not->toBeNull();

    $component->call('deleteEntry', $entry->id);
    expect(MealEntry::find($entry->id))->toBeNull();
});

it('дневник: блюдо, шаблон, копирование вчерашнего дня, вода, избранное и смена даты', function () {
    $dish = Dish::where('slug', 'oatmeal-banana-nuts')->firstOrFail();
    $this->diary->addFood($this->user, $this->today->copy()->subDay(), 'breakfast', food('egg'), 110);

    $component = Livewire::actingAs($this->user)->test(MealDiary::class)
        ->assertSee('Скопировать предыдущий день')
        ->call('copyYesterday');
    expect($this->user->mealEntries()->where('eaten_on', '2026-11-11')->count())->toBe(1);

    $component->call('openAdd', 'breakfast')->call('setTab', 'dishes')->assertSee($dish->name)->call('addDish', $dish->id);
    $dishEntry = $this->user->mealEntries()->where('dish_id', $dish->id)->sole();
    expect($dishEntry->servings)->toBe(1.0)->and($dishEntry->kcal)->toBe($dish->load('ingredients.food')->perServing()->kcal);

    // Шаблон из сегодняшнего завтрака (яйцо + овсянка) и его применение к ужину.
    $component->call('closeAdd')->call('startTemplate', 'breakfast')->set('templateName', 'Обычный завтрак')->call('saveTemplate');
    $template = $this->user->mealTemplates()->with('items')->sole();
    expect($template->name)->toBe('Обычный завтрак')->and($template->items)->toHaveCount(2);

    $component->call('openAdd', 'dinner')->call('setTab', 'templates')->assertSee('Обычный завтрак')->call('applyTemplate', $template->id);
    expect($this->user->mealEntries()->where('eaten_on', '2026-11-11')->where('meal', 'dinner')->count())->toBe(2);

    // Вода: только разрешённые порции, не уходит в минус.
    $component->call('addWater', 250)->call('addWater', 500)->call('addWater', 777)->call('addWater', -200);
    expect($this->user->waterLogs()->sole()->ml)->toBe(550);
    $component->call('addWater', -500)->call('addWater', -500);
    expect($this->user->waterLogs()->sole()->ml)->toBe(0);

    // Избранное.
    $egg = food('egg');
    $component->call('toggleFavorite', $egg->id)->call('setTab', 'favorites')->assertSee('Яйцо куриное');
    expect($this->user->favoriteFoods()->pluck('foods.id')->all())->toBe([$egg->id]);
    $component->call('toggleFavorite', $egg->id);
    expect($this->user->favoriteFoods()->count())->toBe(0);

    // Смена даты: вчера и обратно.
    $component->call('closeAdd')->call('shiftDay', -1)->assertSet('date', '2026-11-10')->assertSee('Вчера')
        ->call('goToday')->assertSet('date', '');
});

it('дневник: ссылка из каталога сразу открывает ввод граммовки', function () {
    $egg = food('egg');

    Livewire::actingAs($this->user)->withQueryParams(['add' => 'snack', 'food' => $egg->id])->test(MealDiary::class)
        ->assertSet('addingTo', 'snack')
        ->assertSet('foodId', $egg->id)
        ->assertSet('grams', '55');
});

it('дневник показывает остаток до нормы, а без профиля — приглашение его заполнить', function () {
    Livewire::actingAs($this->user)->test(MealDiary::class)->assertSee('Заполните профиль питания');

    fillNutritionProfile($this->user);
    $this->diary->addCustom($this->user, $this->today, 'lunch', 'Обед', new Macros(1000, 50, 30, 100));

    Livewire::actingAs($this->user)->test(MealDiary::class)
        ->assertDontSee('Заполните профиль питания')
        ->assertSee('Осталось')
        ->assertSee('1 760')
        ->assertSee('2 760');
});
