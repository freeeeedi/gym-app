<?php

use App\Livewire\FoodCatalog;
use App\Models\Dish;
use App\Models\Food;
use App\Models\User;
use App\Services\Nutrition\NutritionDiary;
use App\Support\Macros;
use Database\Seeders\DemoSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    seedCatalog();
    Carbon::setTestNow('2026-11-11 12:00'); // среда
    $this->user = User::factory()->create();
});

function validNutritionProfile(array $overrides = []): array
{
    return [
        'sex' => 'male', 'age' => 30, 'height_cm' => '180', 'weight_kg' => '80',
        'activity' => 'moderate', 'goal' => 'maintain', 'restrictions' => [],
        ...$overrides,
    ];
}

it('открывает все страницы питания: пустой аккаунт и демо-пользователь с данными', function () {
    $this->seed(DemoSeeder::class);
    $demo = User::where('email', DemoSeeder::EMAIL)->first();
    $dish = Dish::where('slug', 'lentil-soup')->first();
    $demo->shoppingListItems()->create(['dish_id' => $dish->id, 'servings' => 4]);

    $urls = [
        route('dashboard'),
        route('more'),
        route('nutrition.diary'),
        route('nutrition.diary', ['date' => '2026-11-09']),
        route('nutrition.diary', ['date' => 'мусор']),
        route('nutrition.stats'),
        route('nutrition.stats', ['week' => '2026-11-02']),
        route('nutrition.stats', ['week' => 'мусор']),
        route('nutrition.foods'),
        route('nutrition.foods', ['tab' => 'all', 'search' => 'рис']),
        route('nutrition.foods', ['tab' => 'mine']),
        route('nutrition.dishes.index'),
        route('nutrition.dishes.index', ['meal' => 'breakfast']),
        route('nutrition.dishes.show', $dish),
        route('nutrition.shopping.index'),
        route('nutrition.profile.edit'),
    ];

    foreach ([$this->user, $demo] as $user) {
        foreach ($urls as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }
    }
});

it('гость не видит раздел питания', function () {
    $this->get(route('nutrition.diary'))->assertRedirect(route('login'));
    $this->get(route('nutrition.stats'))->assertRedirect(route('login'));
    $this->put(route('nutrition.profile.update'), validNutritionProfile())->assertRedirect(route('login'));
});

it('сохраняет профиль питания, записывает вес и показывает норму', function () {
    $this->actingAs($this->user)
        ->put(route('nutrition.profile.update'), validNutritionProfile(['weight_kg' => '80,0', 'restrictions' => ['no_nuts']]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $profile = $this->user->fresh()->nutritionProfile();
    expect($profile->isComplete())->toBeTrue()
        ->and($profile->weightKg)->toBe(80.0)
        ->and($profile->heightCm)->toBe(180.0)
        ->and($profile->restrictions)->toBe(['no_nuts'])
        ->and($this->user->weightEntries()->sole()->measured_on->toDateString())->toBe('2026-11-11');

    $this->actingAs($this->user)->get(route('nutrition.profile.edit'))
        ->assertOk()
        ->assertSee('Ваша норма на день')
        ->assertSee('2 760')     // ккал
        ->assertSee('24,7')      // ИМТ
        ->assertSee('норма');

    // Тот же вес повторно — новая запись не создаётся; другой вес в тот же день — запись обновляется.
    $this->actingAs($this->user)->put(route('nutrition.profile.update'), validNutritionProfile());
    $this->actingAs($this->user)->put(route('nutrition.profile.update'), validNutritionProfile(['weight_kg' => '79.5', 'goal' => 'cut']));
    expect($this->user->weightEntries()->count())->toBe(1)
        ->and($this->user->fresh()->currentWeight())->toBe(79.5)
        ->and($this->user->fresh()->nutritionProfile()->goal)->toBe('cut');
});

it('валидирует профиль питания', function () {
    $this->actingAs($this->user)
        ->put(route('nutrition.profile.update'), validNutritionProfile([
            'sex' => 'x', 'age' => 5, 'height_cm' => '50', 'weight_kg' => 'много', 'activity' => 'лень', 'goal' => 'чудо', 'restrictions' => ['магия'],
        ]))
        ->assertSessionHasErrors(['sex', 'age', 'height_cm', 'weight_kg', 'activity', 'goal', 'restrictions.0']);

    expect($this->user->fresh()->nutrition_profile)->toBeNull()
        ->and($this->user->weightEntries()->count())->toBe(0);
});

it('статистика показывает съеденное за неделю в сравнении с нормой и остаток', function () {
    $this->actingAs($this->user)->put(route('nutrition.profile.update'), validNutritionProfile());
    $diary = app(NutritionDiary::class);
    $diary->addCustom($this->user, Carbon::parse('2026-11-09'), 'lunch', 'Обед', new Macros(2000, 100, 60, 250));
    $diary->addCustom($this->user, Carbon::parse('2026-11-11'), 'lunch', 'Обед', new Macros(1320, 60, 40, 180));

    $this->actingAs($this->user)->get(route('nutrition.stats'))
        ->assertOk()
        ->assertSee('Эта неделя')
        ->assertSee('дней с записями: 2 из 7')
        ->assertSee('3 320')    // съедено за неделю
        ->assertSee('19 320')   // недельная норма 2760 × 7
        ->assertSee('До недельной нормы осталось 16 000 ккал')
        ->assertSee('1 660')    // в среднем за день с записями
        ->assertSee('chart-kcal');

    $this->actingAs($this->user)->get(route('nutrition.stats', ['week' => '2026-11-02']))
        ->assertOk()
        ->assertDontSee('Эта неделя')
        ->assertSee('дней с записями: 0 из 7');
});

it('журнал веса: запись, замена за тот же день, удаление, чужие записи недоступны', function () {
    $this->actingAs($this->user)->post(route('nutrition.weight.store'), ['measured_on' => '2026-11-10', 'weight_kg' => '81,4'])->assertRedirect();
    $this->actingAs($this->user)->post(route('nutrition.weight.store'), ['measured_on' => '2026-11-10', 'weight_kg' => '81.0'])->assertRedirect();
    $this->actingAs($this->user)->post(route('nutrition.weight.store'), ['measured_on' => '2026-11-11', 'weight_kg' => '80.6'])->assertRedirect();

    expect($this->user->weightEntries()->orderBy('measured_on')->pluck('weight_kg')->all())->toEqual([81.0, 80.6])
        ->and($this->user->currentWeight())->toBe(80.6);

    $this->actingAs($this->user)->post(route('nutrition.weight.store'), ['measured_on' => '2026-11-12', 'weight_kg' => '80'])
        ->assertSessionHasErrors('measured_on'); // будущее
    $this->actingAs($this->user)->post(route('nutrition.weight.store'), ['measured_on' => '2026-11-11', 'weight_kg' => '5'])
        ->assertSessionHasErrors('weight_kg');

    $this->actingAs($this->user)->get(route('nutrition.stats'))->assertOk()->assertSee('chart-body-weight')->assertSee('80,6 кг');

    $entry = $this->user->weightEntries()->orderBy('measured_on')->first();
    $this->actingAs(User::factory()->create())->delete(route('nutrition.weight.destroy', $entry->id))->assertNotFound();
    $this->actingAs($this->user)->delete(route('nutrition.weight.destroy', $entry->id))->assertRedirect();
    expect($this->user->weightEntries()->count())->toBe(1);
});

it('блюда: список учитывает ограничения, карточка показывает КБЖУ порции, блюдо пишется в дневник', function () {
    $this->actingAs($this->user)->put(route('nutrition.profile.update'), validNutritionProfile(['restrictions' => ['vegan']]));

    $this->actingAs($this->user)->get(route('nutrition.dishes.index'))
        ->assertOk()
        ->assertSee('Суп из чечевицы')
        ->assertDontSee('Куриная грудка с гречкой и овощами')
        ->assertSee('Скрыто из-за ограничений');

    $this->actingAs($this->user)->get(route('nutrition.dishes.index', ['meal' => 'snack']))
        ->assertOk()
        ->assertSee('Хумус с овощными палочками')
        ->assertDontSee('Суп из чечевицы');

    $dish = Dish::with('ingredients.food')->where('slug', 'chicken-buckwheat-vegetables')->first();
    $this->actingAs($this->user)->get(route('nutrition.dishes.show', $dish))
        ->assertOk()
        ->assertSee('исключены вашими ограничениями')
        ->assertSee('Гречка (сухая)')
        ->assertSee(format_int($dish->perServing()->kcal));

    $this->actingAs($this->user)->post(route('nutrition.dishes.log', $dish), ['meal' => 'dinner', 'servings' => '1,5'])
        ->assertRedirect(route('nutrition.diary'));
    $entry = $this->user->mealEntries()->sole();
    expect($entry->meal)->toBe('dinner')
        ->and($entry->dish_id)->toBe($dish->id)
        ->and($entry->servings)->toBe(1.5)
        ->and($entry->kcal)->toBe($dish->perServing()->times(1.5)->rounded()->kcal)
        ->and($entry->eaten_on->toDateString())->toBe('2026-11-11');

    $this->actingAs($this->user)->post(route('nutrition.dishes.log', $dish), ['meal' => 'полдник', 'servings' => '0'])
        ->assertSessionHasErrors(['meal', 'servings']);
});

it('список покупок: добавление блюд, порции, общий список, чужие позиции недоступны', function () {
    $soup = Dish::where('slug', 'lentil-soup')->first();
    $buckwheat = Dish::where('slug', 'buckwheat-mushrooms')->first();

    $this->actingAs($this->user)->get(route('nutrition.shopping.index'))->assertOk()->assertSee('Список пуст');

    $this->actingAs($this->user)->post(route('nutrition.shopping.store'), ['dish_id' => $soup->id])->assertRedirect();
    $this->actingAs($this->user)->post(route('nutrition.shopping.store'), ['dish_id' => $buckwheat->id, 'servings' => 4])->assertRedirect();
    // Повторное добавление не дублирует блюдо.
    $this->actingAs($this->user)->post(route('nutrition.shopping.store'), ['dish_id' => $soup->id, 'servings' => 4])->assertRedirect();

    expect($this->user->shoppingListItems()->count())->toBe(2);

    $this->actingAs($this->user)->get(route('nutrition.shopping.index'))
        ->assertOk()
        ->assertSee('Чечевица (сухая)')
        ->assertSee('Лук репчатый')
        ->assertSee('300 г')   // лук: 100 + 100 × 2
        ->assertSee('Овощи и зелень');

    $item = $this->user->shoppingListItems()->where('dish_id', $soup->id)->first();
    $other = User::factory()->create();
    $this->actingAs($other)->patch(route('nutrition.shopping.update', $item->id), ['servings' => 2])->assertNotFound();
    $this->actingAs($other)->delete(route('nutrition.shopping.destroy', $item->id))->assertNotFound();

    $this->actingAs($this->user)->patch(route('nutrition.shopping.update', $item->id), ['servings' => 8])->assertRedirect();
    expect($item->fresh()->servings)->toBe(8);
    $this->actingAs($this->user)->patch(route('nutrition.shopping.update', $item->id), ['servings' => 0])->assertSessionHasErrors('servings');

    $this->actingAs($this->user)->delete(route('nutrition.shopping.destroy', $item->id))->assertRedirect();
    expect($this->user->shoppingListItems()->count())->toBe(1);
    $this->actingAs($this->user)->delete(route('nutrition.shopping.clear'))->assertRedirect();
    expect($this->user->shoppingListItems()->count())->toBe(0);
});

it('каталог продуктов: рекомендации, поиск с учётом ограничений, свои продукты', function () {
    $this->actingAs($this->user)->put(route('nutrition.profile.update'), validNutritionProfile(['goal' => 'cut', 'restrictions' => ['vegetarian']]));

    Livewire::actingAs($this->user)->test(FoodCatalog::class)
        ->assertSee('Сушка')
        ->assertSee('Источники белка')
        ->assertSee('Творог обезжиренный')
        ->assertDontSee('Куриная грудка')
        ->call('setTab', 'all')
        ->set('search', 'кури')
        ->assertDontSee('Куриная грудка отварная')   // скрыта ограничением
        ->assertSee('Яйцо куриное')
        ->set('onlyAllowed', false)
        ->assertSee('Куриная грудка отварная')
        ->set('search', '')
        ->set('category', 'nuts')
        ->assertSee('Миндаль')
        ->assertDontSee('Яйцо куриное');

    $component = Livewire::actingAs($this->user)->test(FoodCatalog::class)
        ->call('setTab', 'mine')
        ->assertSee('Своих продуктов пока нет')
        ->set('showForm', true)
        ->call('createFood')
        ->assertHasErrors(['form.name', 'form.kcal', 'form.protein', 'form.fat', 'form.carbs'])
        ->set('form.name', 'Гранола домашняя')
        ->set('form.category', 'grains')
        ->set('form.kcal', '420')
        ->set('form.protein', '10,5')
        ->set('form.fat', '15')
        ->set('form.carbs', '60')
        ->set('form.serving_g', '40')
        ->call('createFood')
        ->assertHasErrors('form.serving_name')
        ->set('form.serving_name', 'горсть')
        ->call('createFood')
        ->assertHasNoErrors()
        ->assertSee('Гранола домашняя');

    $food = $this->user->foods()->sole();
    expect($food->protein)->toBe(10.5)->and($food->serving_g)->toBe(40.0)->and($food->category)->toBe('grains');

    // Свой продукт виден только владельцу и удаляется только им.
    $other = User::factory()->create();
    Livewire::actingAs($other)->test(FoodCatalog::class)->call('setTab', 'all')->set('search', 'гранола')->assertDontSee('Гранола домашняя');
    expect(fn () => Livewire::actingAs($other)->test(FoodCatalog::class)->call('deleteFood', $food->id))->toThrow(ModelNotFoundException::class);
    expect(fn () => Livewire::actingAs($other)->test(FoodCatalog::class)->call('toggleFavorite', $food->id))->toThrow(ModelNotFoundException::class);

    // Удаление продукта не трогает записи дневника: в них остаётся снимок названия и КБЖУ.
    $entry = app(NutritionDiary::class)->addFood($this->user, Carbon::today(), 'breakfast', $food, 50);
    Livewire::actingAs($this->user)->test(FoodCatalog::class)->call('deleteFood', $food->id);
    expect(Food::find($food->id))->toBeNull()
        ->and($entry->fresh()->food_id)->toBeNull()
        ->and($entry->fresh()->name)->toBe('Гранола домашняя')
        ->and($entry->fresh()->kcal)->toBe(210.0);
});

it('главная показывает питание за сегодня', function () {
    $this->actingAs($this->user)->get(route('dashboard'))->assertOk()->assertSee('Питание сегодня')->assertSee('Записывайте еду');

    $this->actingAs($this->user)->put(route('nutrition.profile.update'), validNutritionProfile());
    app(NutritionDiary::class)->addCustom($this->user, Carbon::today(), 'lunch', 'Обед', new Macros(1234, 60, 40, 150));

    $this->actingAs($this->user)->get(route('dashboard'))->assertOk()->assertSee('1 234')->assertSee('2 760');
});
