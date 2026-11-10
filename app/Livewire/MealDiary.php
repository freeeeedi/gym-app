<?php

namespace App\Livewire;

use App\Models\Dish;
use App\Models\Food;
use App\Models\MealEntry;
use App\Models\User;
use App\Services\Nutrition\NutritionCalculator;
use App\Services\Nutrition\NutritionDiary;
use App\Support\Macros;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Дневник питания на день: записи по приёмам пищи, сумма и остаток до нормы, вода.
 * Панель добавления: поиск по каталогу, недавние, избранные, блюда, шаблоны, свой продукт.
 */
class MealDiary extends Component
{
    private const TABS = ['search', 'recent', 'favorites', 'dishes', 'templates', 'custom'];

    private const CUSTOM_DEFAULTS = [
        'name' => '', 'kcal' => '', 'protein' => '', 'fat' => '', 'carbs' => '',
        'basis' => 'portion', 'grams' => '', 'save' => false,
    ];

    /** Дата дневника (Y-m-d); пустая строка — сегодня. */
    #[Url(except: '')]
    public string $date = '';

    /** Приём пищи, в который сейчас добавляем; null — панель закрыта. */
    public ?string $addingTo = null;

    public string $tab = 'search';

    public string $search = '';

    /** Выбранный продукт каталога и его граммовка. */
    public ?int $foodId = null;

    public string $grams = '';

    /** Свой продукт: КБЖУ за порцию (basis = portion) или на 100 г (basis = per100). */
    public array $custom = self::CUSTOM_DEFAULTS;

    /** Запись, у которой правится количество. */
    public ?int $editingId = null;

    public string $editAmount = '';

    /** Приём пищи, который сохраняется как шаблон. */
    public ?string $templateMeal = null;

    public string $templateName = '';

    /** Сообщение о последнем действии в панели добавления. */
    public string $notice = '';

    protected $validationAttributes = [
        'grams' => 'граммы',
        'editAmount' => 'количество',
        'templateName' => 'название шаблона',
        'custom.name' => 'название',
        'custom.kcal' => 'калории',
        'custom.protein' => 'белки',
        'custom.fat' => 'жиры',
        'custom.carbs' => 'углеводы',
        'custom.grams' => 'граммы',
    ];

    /** Ссылка из каталога продуктов: ?add=snack&food=12 сразу открывает ввод граммовки. */
    public function mount(): void
    {
        $meal = request()->query('add');
        if (is_string($meal) && isset(config('nutrition.meals')[$meal])) {
            $this->openAdd($meal);
            if ($food = Food::visibleTo($this->user())->find((int) request()->query('food'))) {
                $this->selectFood($food->id);
            }
        }
    }

    // --- Дата ---

    public function shiftDay(int $days): void
    {
        $this->setDay($this->day()->addDays($days <=> 0));
    }

    public function goToday(): void
    {
        $this->setDay(Carbon::today());
    }

    public function updatedDate(): void
    {
        $this->setDay($this->day());
    }

    // --- Панель добавления ---

    public function openAdd(string $meal): void
    {
        if (! isset(config('nutrition.meals')[$meal])) {
            return;
        }
        $this->reset('search', 'foodId', 'grams', 'custom', 'notice', 'editingId', 'templateMeal');
        $this->resetValidation();
        $this->addingTo = $meal;
        $this->tab = 'search';
    }

    public function closeAdd(): void
    {
        $this->reset('addingTo', 'search', 'foodId', 'grams', 'custom', 'notice');
        $this->resetValidation();
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, self::TABS, true)) {
            $this->tab = $tab;
            $this->reset('foodId', 'grams');
            $this->resetValidation();
        }
    }

    public function selectFood(int $foodId): void
    {
        $food = Food::visibleTo($this->user())->findOrFail($foodId);
        $this->foodId = $food->id;
        $this->grams = format_number_input(app(NutritionDiary::class)->suggestedGrams($this->user(), $food));
        $this->resetValidation();
    }

    public function cancelFood(): void
    {
        $this->reset('foodId', 'grams');
        $this->resetValidation();
    }

    public function addFood(NutritionDiary $diary): void
    {
        if (! $this->addingTo || ! $this->foodId) {
            return;
        }
        $grams = $this->number($this->grams);
        validator(['grams' => $grams], ['grams' => ['required', 'numeric', 'min:1', 'max:5000']], [], $this->validationAttributes)->validate();

        $food = Food::visibleTo($this->user())->findOrFail($this->foodId);
        $diary->addFood($this->user(), $this->day(), $this->addingTo, $food, (float) $grams);

        $this->notice = 'Добавлено: '.$food->name.', '.format_kg((float) $grams).' г';
        $this->reset('foodId', 'grams', 'search');
    }

    /** Блюдо добавляется одной порцией; число порций правится в записи. */
    public function addDish(int $dishId, NutritionDiary $diary): void
    {
        if (! $this->addingTo) {
            return;
        }
        $dish = Dish::with('ingredients.food')->findOrFail($dishId);
        $diary->addDish($this->user(), $this->day(), $this->addingTo, $dish);

        $this->notice = 'Добавлено: '.$dish->name.', 1 порция';
    }

    /** Свой продукт, которого нет в каталоге: название и КБЖУ вручную. */
    public function addCustom(NutritionDiary $diary): void
    {
        if (! $this->addingTo) {
            return;
        }
        foreach (['kcal', 'protein', 'fat', 'carbs', 'grams'] as $key) {
            $this->custom[$key] = $this->number((string) $this->custom[$key]);
        }
        $per100 = $this->custom['basis'] === 'per100';

        $this->validate([
            'custom.name' => ['required', 'string', 'max:120'],
            'custom.basis' => [Rule::in(['portion', 'per100'])],
            'custom.kcal' => ['nullable', 'numeric', 'min:0', 'max:9000', 'required_without_all:custom.protein,custom.fat,custom.carbs'],
            'custom.protein' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'custom.fat' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'custom.carbs' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'custom.grams' => [$per100 ? 'required' : 'nullable', 'numeric', 'min:1', 'max:5000'],
        ], ['custom.kcal.required_without_all' => 'Укажите калории или хотя бы один из макронутриентов.']);

        $macros = $this->customMacros();
        $name = trim($this->custom['name']);
        $grams = $this->custom['grams'] === '' ? null : (float) $this->custom['grams'];

        if ($per100) {
            if ($this->custom['save']) {
                // Сохраняем в «Мои продукты» и записываем как обычный продукт каталога.
                $food = $this->user()->foods()->create([
                    'name' => $name, 'category' => 'other', 'tags' => [], ...$macros->rounded()->toArray(),
                ]);
                $diary->addFood($this->user(), $this->day(), $this->addingTo, $food, $grams);
            } else {
                $diary->addCustom($this->user(), $this->day(), $this->addingTo, $name, $macros->times($grams / 100), $grams);
            }
        } else {
            $diary->addCustom($this->user(), $this->day(), $this->addingTo, $name, $macros, $grams);
        }

        $this->notice = 'Добавлено: '.$name;
        $this->custom = self::CUSTOM_DEFAULTS;
    }

    public function applyTemplate(int $templateId, NutritionDiary $diary): void
    {
        if (! $this->addingTo) {
            return;
        }
        $template = $this->user()->mealTemplates()->with('items')->findOrFail($templateId);
        $count = $diary->applyTemplate($this->user(), $template, $this->day(), $this->addingTo);

        $this->notice = "Добавлен шаблон «{$template->name}»: позиций — {$count}";
    }

    public function deleteTemplate(int $templateId): void
    {
        $this->user()->mealTemplates()->findOrFail($templateId)->delete();
    }

    public function toggleFavorite(int $foodId): void
    {
        $food = Food::visibleTo($this->user())->findOrFail($foodId);
        $this->user()->favoriteFoods()->toggle([$food->id => ['created_at' => now()]]);
    }

    // --- Записи дня ---

    public function startEdit(int $entryId): void
    {
        $entry = $this->entry($entryId);
        $amount = $entry->grams ?? $entry->servings;
        if ($amount === null) {
            return;
        }
        $this->editingId = $entry->id;
        $this->editAmount = format_number_input($amount);
        $this->resetValidation();
    }

    public function cancelEdit(): void
    {
        $this->reset('editingId', 'editAmount');
        $this->resetValidation();
    }

    public function saveEdit(NutritionDiary $diary): void
    {
        if (! $this->editingId) {
            return;
        }
        $entry = $this->entry($this->editingId);
        $amount = $this->number($this->editAmount);
        $max = $entry->grams !== null ? 5000 : 20;
        validator(['editAmount' => $amount], ['editAmount' => ['required', 'numeric', 'gt:0', "max:$max"]], [], $this->validationAttributes)->validate();

        $diary->rescale($entry, (float) $amount);
        $this->cancelEdit();
    }

    public function deleteEntry(int $entryId): void
    {
        $this->entry($entryId)->delete();
        $this->cancelEdit();
    }

    public function copyYesterday(NutritionDiary $diary): void
    {
        $count = $diary->copyDay($this->user(), $this->day()->subDay(), $this->day());
        session()->flash('diary', $count > 0 ? "Скопировано записей: {$count}." : 'За предыдущий день записей нет.');
    }

    public function startTemplate(string $meal): void
    {
        if (isset(config('nutrition.meals')[$meal])) {
            $this->templateMeal = $meal;
            $this->templateName = config("nutrition.meals.$meal").' '.$this->day()->format('d.m');
            $this->resetValidation();
        }
    }

    public function cancelTemplate(): void
    {
        $this->reset('templateMeal', 'templateName');
        $this->resetValidation();
    }

    public function saveTemplate(NutritionDiary $diary): void
    {
        if (! $this->templateMeal) {
            return;
        }
        $this->validate(['templateName' => ['required', 'string', 'max:80']]);

        $entries = $this->user()->mealEntries()
            ->where('eaten_on', $this->day()->toDateString())
            ->where('meal', $this->templateMeal)
            ->orderBy('id')
            ->get();
        if ($entries->isNotEmpty()) {
            $diary->saveTemplate($this->user(), trim($this->templateName), $entries);
            session()->flash('diary', 'Шаблон сохранён — он появится на вкладке «Шаблоны» при добавлении.');
        }
        $this->cancelTemplate();
    }

    // --- Вода ---

    public function addWater(int $ml): void
    {
        if (! in_array(abs($ml), config('nutrition.water.portions'), true)) {
            return;
        }
        $log = $this->user()->waterLogs()->firstOrNew(['logged_on' => $this->day()->toDateString()]);
        $log->ml = max(0, (int) $log->ml + $ml);
        $log->save();
    }

    public function render(NutritionDiary $diary, NutritionCalculator $calculator)
    {
        $user = $this->user();
        $date = $this->day();
        $profile = $user->nutritionProfile();
        $targets = $calculator->targets($profile);
        $day = $diary->day($user, $date);

        $data = [
            'day' => $day,
            'current' => $date,
            'isToday' => $date->isToday(),
            'targets' => $targets,
            'remaining' => $targets ? $diary->remaining($targets->macros(), $day['totals']) : null,
            'waterMl' => (int) $user->waterLogs()->where('logged_on', $date->toDateString())->value('ml'),
            'waterTarget' => $targets?->waterMl ?? $calculator->waterMl($profile->weightKg),
            'previousCount' => $day['count'] === 0
                ? $user->mealEntries()->where('eaten_on', $date->copy()->subDay()->toDateString())->count()
                : 0,
        ];

        if ($this->addingTo) {
            $data += $this->panelData($diary, $user, $profile);
        }

        return view('livewire.meal-diary', $data);
    }

    /** Данные панели добавления — только для открытой вкладки. */
    private function panelData(NutritionDiary $diary, User $user, $profile): array
    {
        $food = $this->foodId ? Food::visibleTo($user)->find($this->foodId) : null;
        $grams = (float) $this->number($this->grams);

        $data = [
            'food' => $food,
            'foodMacros' => $food && is_numeric($this->number($this->grams)) ? $food->macrosFor($grams)->rounded() : null,
            'favoriteIds' => $user->favoriteFoods()->pluck('foods.id')->all(),
            'foods' => collect(),
            'dishes' => collect(),
            'templates' => collect(),
            'customPreview' => $this->tab === 'custom' ? $this->customMacros() : null,
        ];

        if ($food) {
            return $data;
        }

        switch ($this->tab) {
            case 'search':
                $term = trim($this->search);
                if ($term !== '') {
                    // Поиск по названию в PHP: регистронезависимо для кириллицы в любой СУБД.
                    $data['foods'] = Food::visibleTo($user)->orderBy('name')->get()
                        ->filter(fn (Food $f) => mb_stripos($f->name, $term) !== false)
                        ->take(40)->values();
                }
                break;
            case 'recent':
                $data['foods'] = $diary->recentFoods($user);
                break;
            case 'favorites':
                $data['foods'] = $user->favoriteFoods()->visibleTo($user)->orderBy('name')->get();
                break;
            case 'dishes':
                // Сначала блюда для этого приёма пищи; запрещённые ограничениями не показываются.
                $data['dishes'] = Dish::with('ingredients.food')->orderBy('name')->get()
                    ->filter(fn (Dish $dish) => $profile->allows($dish->tagList()))
                    ->sortByDesc(fn (Dish $dish) => in_array($this->addingTo, $dish->meals ?? [], true))
                    ->values();
                break;
            case 'templates':
                $data['templates'] = $user->mealTemplates()->with('items')->latest('id')->get();
                break;
        }

        return $data;
    }

    /** КБЖУ из формы своего продукта; калории без ввода считаются из макронутриентов. */
    private function customMacros(): Macros
    {
        $value = fn (string $key) => is_numeric($n = $this->number((string) $this->custom[$key])) ? (float) $n : 0.0;
        $per = config('nutrition.kcal_per_gram');
        $kcal = $this->number((string) $this->custom['kcal']);

        return new Macros(
            is_numeric($kcal) ? (float) $kcal : $value('protein') * $per['protein'] + $value('fat') * $per['fat'] + $value('carbs') * $per['carbs'],
            $value('protein'),
            $value('fat'),
            $value('carbs'),
        );
    }

    private function user(): User
    {
        return Auth::user();
    }

    private function entry(int $entryId): MealEntry
    {
        return $this->user()->mealEntries()->findOrFail($entryId);
    }

    private function day(): Carbon
    {
        try {
            return $this->date === '' ? Carbon::today() : Carbon::createFromFormat('Y-m-d', $this->date)->startOfDay();
        } catch (\Throwable) {
            return Carbon::today();
        }
    }

    private function setDay(Carbon $day): void
    {
        $this->date = $day->isToday() ? '' : $day->toDateString();
        $this->reset('editingId', 'editAmount', 'templateMeal', 'templateName');
    }

    /** Десятичная запятая с русской клавиатуры: «62,5» → «62.5». */
    private function number(string $value): string
    {
        return str_replace(',', '.', trim($value));
    }
}
