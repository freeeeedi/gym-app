@php($categories = config('nutrition.food_categories'))
<x-app-layout>
    <x-slot name="title">Список покупок</x-slot>
    <x-slot name="header">
        <h1 class="flex-1 font-semibold">Питание</h1>
    </x-slot>

    <x-nutrition-tabs />

    <div class="space-y-5">
        @if ($items->isEmpty())
            <div class="card space-y-3 text-center">
                <p class="muted">Список пуст. Выберите блюда, которые хотите приготовить, — соберём общий список продуктов с граммовками.</p>
                <a href="{{ route('nutrition.dishes.index') }}" class="btn-primary w-full">Выбрать блюда</a>
            </div>
        @else
            <section class="card !p-0" aria-label="Выбранные блюда">
                <h2 class="h2 p-4 pb-2">Блюда</h2>
                <ul>
                    @foreach ($items as $item)
                        <li class="flex items-center gap-2 border-t border-zinc-800 px-4 py-2">
                            <a href="{{ route('nutrition.dishes.show', $item->dish) }}" class="min-w-0 flex-1 text-sm leading-tight">{{ $item->dish->name }}</a>
                            <div class="flex items-center gap-1" role="group" aria-label="Порции: {{ $item->dish->name }}">
                                <form method="POST" action="{{ route('nutrition.shopping.update', $item->id) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="servings" value="{{ $item->servings - 1 }}">
                                    <button class="btn-secondary btn-sm !w-10 !px-0" @disabled($item->servings <= 1) aria-label="Меньше порций">−</button>
                                </form>
                                <span class="w-16 text-center text-sm tabular-nums">{{ $item->servings }} порц.</span>
                                <form method="POST" action="{{ route('nutrition.shopping.update', $item->id) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="servings" value="{{ $item->servings + 1 }}">
                                    <button class="btn-secondary btn-sm !w-10 !px-0" @disabled($item->servings >= 30) aria-label="Больше порций">+</button>
                                </form>
                            </div>
                            <form method="POST" action="{{ route('nutrition.shopping.destroy', $item->id) }}">
                                @csrf @method('DELETE')
                                <button class="btn-ghost btn-sm !px-2 text-zinc-500" aria-label="Убрать {{ $item->dish->name }}">✕</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
                <div class="border-t border-zinc-800 p-3">
                    <a href="{{ route('nutrition.dishes.index') }}" class="btn-secondary btn-sm w-full">+ Добавить блюдо</a>
                </div>
            </section>

            {{-- Отметки «куплено» хранятся в браузере: это черновик похода в магазин, а не данные учёта. --}}
            <section class="space-y-3" aria-label="Продукты"
                     x-data="{
                         bought: JSON.parse(localStorage.getItem('shopping-bought') || '[]'),
                         toggle(id) {
                             this.bought = this.bought.includes(id) ? this.bought.filter(i => i !== id) : [...this.bought, id];
                             localStorage.setItem('shopping-bought', JSON.stringify(this.bought));
                         },
                         reset() { this.bought = []; localStorage.removeItem('shopping-bought'); },
                     }">
                <div class="flex items-baseline justify-between">
                    <h2 class="h2">Что купить</h2>
                    <button type="button" x-show="bought.length" x-cloak @click="reset()" class="text-sm text-lime-400">Снять отметки</button>
                </div>
                @foreach ($groups as $category => $rows)
                    <div class="card !p-0">
                        <h3 class="px-4 pt-3 text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ $categories[$category] }}</h3>
                        <ul>
                            @foreach ($rows as $row)
                                <li class="border-b border-zinc-800 last:border-0">
                                    <label class="flex min-h-[3rem] cursor-pointer items-center gap-3 px-4 py-2">
                                        <input type="checkbox" class="h-5 w-5 rounded border-zinc-600 bg-zinc-950 text-lime-400 focus:ring-lime-400"
                                               :checked="bought.includes({{ $row['food']->id }})" @change="toggle({{ $row['food']->id }})">
                                        <span class="min-w-0 flex-1 text-sm" :class="bought.includes({{ $row['food']->id }}) && 'text-zinc-500 line-through'">{{ $row['food']->name }}</span>
                                        <span class="shrink-0 text-sm tabular-nums text-zinc-400">{{ format_int($row['grams']) }} г</span>
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
                <p class="text-xs text-zinc-500">Граммовки — сырой вес на выбранное число порций, округлены вверх. Соль и специи в список не входят.</p>
            </section>

            <form method="POST" action="{{ route('nutrition.shopping.clear') }}" onsubmit="return confirm('Очистить список покупок?')">
                @csrf @method('DELETE')
                <button class="btn-ghost w-full text-sm">Очистить список</button>
            </form>
        @endif
    </div>
</x-app-layout>
