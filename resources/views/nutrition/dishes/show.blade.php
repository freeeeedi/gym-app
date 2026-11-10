<x-app-layout>
    <x-slot name="title">{{ $dish->name }}</x-slot>
    <x-slot name="header">
        <a href="{{ route('nutrition.dishes.index') }}" class="btn-ghost btn-sm !px-2" aria-label="К списку блюд">←</a>
        <h1 class="min-w-0 flex-1 truncate font-semibold">{{ $dish->name }}</h1>
    </x-slot>

    <div class="space-y-4">
        @unless ($allowed)
            <div class="rounded-xl border border-amber-900 bg-amber-950/40 p-3 text-sm text-amber-200">
                В составе есть продукты, которые исключены вашими ограничениями.
            </div>
        @endunless

        <section class="card space-y-3">
            <div class="text-sm text-zinc-400">
                {{ implode(', ', $dish->mealLabels()) }}@if ($dish->cook_minutes) · {{ $dish->cook_minutes }} мин@endif
                · порций в рецепте: {{ $dish->servings }}
            </div>
            <div class="grid grid-cols-4 gap-2 text-center">
                @foreach ([['ккал', $macros->kcal], ['белки, г', $macros->protein], ['жиры, г', $macros->fat], ['углев., г', $macros->carbs]] as [$label, $value])
                    <div class="rounded-xl bg-zinc-800 p-2">
                        <div class="text-lg font-bold tabular-nums">{{ format_int($value) }}</div>
                        <div class="text-xs text-zinc-400">{{ $label }}</div>
                    </div>
                @endforeach
            </div>
            <p class="text-xs text-zinc-500">
                На одну порцию (≈ {{ format_int($dish->servingGrams()) }} г сырых продуктов).
                @if ($targets)
                    Это {{ round($macros->kcal / $targets->kcal * 100) }}% вашей дневной нормы калорий и {{ round($macros->protein / max(1, $targets->protein) * 100) }}% нормы белка.
                @endif
            </p>
            @if ($dish->dietLabels())
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($dish->dietLabels() as $label)
                        <span class="chip !py-0.5 text-xs">{{ $label }}</span>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="card">
            <h2 class="h2 mb-2">Ингредиенты <span class="text-sm font-normal text-zinc-500">на {{ $dish->servings }} порц.</span></h2>
            <ul>
                @foreach ($dish->ingredients as $ingredient)
                    <li class="flex justify-between gap-2 border-b border-zinc-800 py-2 text-sm last:border-0">
                        <span>{{ $ingredient->food->name }}</span>
                        <span class="shrink-0 tabular-nums text-zinc-400">{{ format_kg($ingredient->grams) }} г</span>
                    </li>
                @endforeach
            </ul>
        </section>

        @if ($dish->recipe)
            <section class="card">
                <h2 class="h2 mb-2">Как готовить</h2>
                <p class="text-sm leading-relaxed text-zinc-300">{{ $dish->recipe }}</p>
            </section>
        @endif

        <section class="card space-y-3">
            <h2 class="h2">Записать в дневник</h2>
            <form method="POST" action="{{ route('nutrition.dishes.log', $dish) }}" class="space-y-3">
                @csrf
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="label" for="meal">Приём пищи</label>
                        <select id="meal" name="meal" class="input min-h-[3rem]">
                            @foreach (config('nutrition.meals') as $key => $label)
                                <option value="{{ $key }}" @selected(old('meal', $defaultMeal) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label" for="servings">Порций</label>
                        <input id="servings" type="text" inputmode="decimal" name="servings" value="{{ old('servings', 1) }}" class="input min-h-[3rem]" required>
                    </div>
                </div>
                <button class="btn-primary w-full">Записать на сегодня</button>
            </form>
        </section>

        <section class="card space-y-3">
            <h2 class="h2">Список покупок</h2>
            @if ($shoppingItem)
                <p class="text-sm text-zinc-300">Блюдо уже в списке: порций — {{ $shoppingItem->servings }}.</p>
                <a href="{{ route('nutrition.shopping.index') }}" class="btn-secondary w-full">Открыть список покупок</a>
            @else
                <form method="POST" action="{{ route('nutrition.shopping.store') }}" class="flex items-end gap-2">
                    @csrf
                    <input type="hidden" name="dish_id" value="{{ $dish->id }}">
                    <div class="w-24">
                        <label class="label" for="shopping-servings">Порций</label>
                        <input id="shopping-servings" type="number" inputmode="numeric" name="servings" min="1" max="30" value="{{ $dish->servings }}" class="input min-h-[3rem]" required>
                    </div>
                    <button class="btn-secondary flex-1">Добавить продукты в список</button>
                </form>
            @endif
        </section>
    </div>
</x-app-layout>
