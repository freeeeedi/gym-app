<?php

namespace App\Livewire;

use App\Models\Food;
use App\Models\User;
use App\Services\Nutrition\NutritionRecommender;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Каталог продуктов: рекомендации под цель и ограничения, поиск по всему каталогу, свои продукты.
 */
class FoodCatalog extends Component
{
    private const TABS = ['recommended', 'all', 'mine'];

    private const FORM_DEFAULTS = [
        'name' => '', 'category' => 'other', 'kcal' => '', 'protein' => '', 'fat' => '', 'carbs' => '',
        'serving_g' => '', 'serving_name' => '',
    ];

    #[Url(except: 'recommended')]
    public string $tab = 'recommended';

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $category = '';

    /** Показывать только то, что разрешено ограничениями пользователя. */
    public bool $onlyAllowed = true;

    /** Форма своего продукта (КБЖУ на 100 г). */
    public array $form = self::FORM_DEFAULTS;

    public bool $showForm = false;

    protected $validationAttributes = [
        'form.name' => 'название',
        'form.category' => 'категория',
        'form.kcal' => 'калории',
        'form.protein' => 'белки',
        'form.fat' => 'жиры',
        'form.carbs' => 'углеводы',
        'form.serving_g' => 'вес порции',
        'form.serving_name' => 'название порции',
    ];

    public function setTab(string $tab): void
    {
        if (in_array($tab, self::TABS, true)) {
            $this->tab = $tab;
        }
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'category');
    }

    public function toggleFavorite(int $foodId): void
    {
        $food = Food::visibleTo($this->user())->findOrFail($foodId);
        $this->user()->favoriteFoods()->toggle([$food->id => ['created_at' => now()]]);
    }

    public function createFood(): void
    {
        foreach (['kcal', 'protein', 'fat', 'carbs', 'serving_g'] as $key) {
            $this->form[$key] = str_replace(',', '.', trim((string) $this->form[$key]));
        }

        $data = $this->validate([
            'form.name' => ['required', 'string', 'max:120'],
            'form.category' => ['required', Rule::in(array_keys(config('nutrition.food_categories')))],
            'form.kcal' => ['required', 'numeric', 'min:0', 'max:900'],
            'form.protein' => ['required', 'numeric', 'min:0', 'max:100'],
            'form.fat' => ['required', 'numeric', 'min:0', 'max:100'],
            'form.carbs' => ['required', 'numeric', 'min:0', 'max:100'],
            'form.serving_g' => ['nullable', 'numeric', 'min:1', 'max:2000'],
            'form.serving_name' => ['nullable', 'string', 'max:40', 'required_with:form.serving_g'],
        ])['form'];

        $this->user()->foods()->create([
            ...$data,
            'name' => trim($data['name']),
            'serving_g' => $data['serving_g'] === '' ? null : $data['serving_g'],
            'serving_name' => $data['serving_g'] === '' ? null : trim((string) $data['serving_name']),
            'tags' => [],
        ]);

        $this->form = self::FORM_DEFAULTS;
        $this->showForm = false;
        session()->flash('catalog', 'Продукт добавлен в «Мои продукты».');
    }

    /** Удаляется только свой продукт; записи дневника сохраняют название и КБЖУ. */
    public function deleteFood(int $foodId): void
    {
        $this->user()->foods()->findOrFail($foodId)->delete();
    }

    public function render(NutritionRecommender $recommender)
    {
        $user = $this->user();
        $profile = $user->nutritionProfile();
        $categories = config('nutrition.food_categories');

        $data = [
            'profile' => $profile,
            'categories' => $categories,
            'favoriteIds' => $user->favoriteFoods()->pluck('foods.id')->all(),
            'recommendation' => null,
            'foods' => collect(),
            'hidden' => 0,
        ];

        if ($this->tab === 'recommended') {
            $data['recommendation'] = $recommender->foods(Food::visibleTo($user)->orderBy('name')->get(), $profile);
        } elseif ($this->tab === 'mine') {
            $data['foods'] = $user->foods()->orderBy('name')->get();
        } else {
            $foods = Food::visibleTo($user)
                ->when(isset($categories[$this->category]), fn ($q) => $q->where('category', $this->category))
                ->orderBy('name')
                ->get();

            // Поиск по названию в PHP: регистронезависимо для кириллицы в любой СУБД.
            $term = trim($this->search);
            if ($term !== '') {
                $foods = $foods->filter(fn (Food $f) => mb_stripos($f->name, $term) !== false)->values();
            }
            if ($this->onlyAllowed && $profile->restrictions !== []) {
                $allowed = $foods->filter(fn (Food $f) => $profile->allows($f->tagList()))->values();
                $data['hidden'] = $foods->count() - $allowed->count();
                $foods = $allowed;
            }
            $data['foods'] = $foods;
        }

        return view('livewire.food-catalog', $data);
    }

    private function user(): User
    {
        return Auth::user();
    }
}
