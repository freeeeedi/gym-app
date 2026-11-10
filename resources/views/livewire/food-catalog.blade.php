@php
    $tabs = ['recommended' => 'Подходят мне', 'all' => 'Все продукты', 'mine' => 'Мои продукты'];
    $roles = config('nutrition.recommendation.roles');
@endphp
<div class="space-y-3">
    <div class="grid grid-cols-3 gap-2" role="tablist">
        @foreach ($tabs as $key => $label)
            <button type="button" wire:click="setTab('{{ $key }}')" role="tab" aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                    class="chip min-h-[2.75rem] justify-center px-2 text-center !text-xs {{ $tab === $key ? 'chip-active' : '' }}">{{ $label }}</button>
        @endforeach
    </div>

    @if (session('catalog'))
        <div class="rounded-xl border border-lime-900 bg-lime-950/60 px-4 py-3 text-sm text-lime-200" role="status">{{ session('catalog') }}</div>
    @endif

    @if ($tab === 'recommended')
        <div class="card space-y-1 text-sm">
            <div>
                Цель: <span class="font-semibold text-lime-300">{{ $profile->goalLabel() }}</span>
                @if ($profile->restrictions)
                    · {{ implode(', ', array_map('mb_strtolower', $profile->restrictionLabels())) }}
                @endif
            </div>
            <p class="text-xs text-zinc-400">
                Продукты подобраны по правилам: источник белка, сложные углеводы, полезные жиры, овощи и фрукты. Под каждым — почему он предложен.
                @if ($recommendation['excluded'] > 0)
                    Скрыто из-за ограничений: {{ $recommendation['excluded'] }}.
                @endif
            </p>
            @unless ($profile->isComplete())
                <a href="{{ route('nutrition.profile.edit') }}" class="block text-xs text-amber-300">Цель и ограничения задаются в профиле питания ›</a>
            @endunless
        </div>

        @foreach ($recommendation['groups'] as $role => $suggestions)
            @continue($suggestions === [])
            <section aria-label="{{ $roles[$role] }}">
                <h2 class="h2 mb-2">{{ $roles[$role] }}</h2>
                <ul class="space-y-2">
                    @foreach ($suggestions as $suggestion)
                        @php($food = $suggestion->food)
                        <li wire:key="rec-{{ $role }}-{{ $food->id }}" class="card flex items-center gap-2 !p-3">
                            <div class="min-w-0 flex-1">
                                <div class="font-medium leading-tight">{{ $food->name }}</div>
                                <div class="mt-0.5 text-xs text-zinc-400">{{ format_kg($food->kcal) }} ккал · Б {{ format_kg($food->protein) }} · Ж {{ format_kg($food->fat) }} · У {{ format_kg($food->carbs) }} <span class="whitespace-nowrap text-zinc-600">на 100 г</span></div>
                                @if ($suggestion->reasons)
                                    <div class="mt-1 text-xs text-lime-300/90">{{ implode(' · ', $suggestion->reasons) }}</div>
                                @endif
                            </div>
                            <button type="button" wire:click="toggleFavorite({{ $food->id }})" class="btn-ghost btn-sm !px-2 text-xl {{ in_array($food->id, $favoriteIds) ? 'text-amber-300' : 'text-zinc-600' }}" aria-label="{{ in_array($food->id, $favoriteIds) ? 'Убрать из избранного' : 'В избранное' }}: {{ $food->name }}" aria-pressed="{{ in_array($food->id, $favoriteIds) ? 'true' : 'false' }}">{{ in_array($food->id, $favoriteIds) ? '★' : '☆' }}</button>
                            <a href="{{ route('nutrition.diary', ['add' => 'snack', 'food' => $food->id]) }}" class="btn-secondary btn-sm !h-11 !w-11 !px-0 text-xl" aria-label="Записать в дневник: {{ $food->name }}">+</a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    @else
        @if ($tab === 'all')
            <div class="space-y-2">
                <input type="search" wire:model.live.debounce.250ms="search" placeholder="Поиск продукта" class="input min-h-[3rem]" aria-label="Поиск продукта">
                <select wire:model.live="category" class="input min-h-[3rem] text-sm" aria-label="Категория">
                    <option value="">Все категории</option>
                    @foreach ($categories as $slug => $name)
                        <option value="{{ $slug }}">{{ $name }}</option>
                    @endforeach
                </select>
                @if ($profile->restrictions)
                    <label class="flex items-center gap-3">
                        <input type="checkbox" wire:model.live="onlyAllowed" class="h-5 w-5 rounded border-zinc-600 bg-zinc-950 text-lime-400 focus:ring-lime-400">
                        <span class="text-sm">Только подходящие моим ограничениям</span>
                    </label>
                @endif
                <div class="flex items-center justify-between">
                    <span class="muted">Найдено: {{ $foods->count() }}@if ($hidden > 0), скрыто: {{ $hidden }}@endif</span>
                    @if ($search || $category)
                        <button type="button" wire:click="resetFilters" class="text-sm text-lime-400">Сбросить фильтры</button>
                    @endif
                </div>
            </div>
        @else
            @if ($showForm)
                <form wire:submit="createFood" class="card space-y-3">
                    <h2 class="h2">Новый продукт</h2>
                    <div>
                        <label class="label" for="food-name">Название</label>
                        <input id="food-name" type="text" wire:model="form.name" maxlength="120" class="input min-h-[3rem]">
                        <x-input-error :messages="$errors->get('form.name')" />
                    </div>
                    <div>
                        <label class="label" for="food-category">Категория</label>
                        <select id="food-category" wire:model="form.category" class="input min-h-[3rem]">
                            @foreach ($categories as $slug => $name)
                                <option value="{{ $slug }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <fieldset>
                        <legend class="label">КБЖУ на 100 г</legend>
                        <div class="grid grid-cols-4 gap-2">
                            @foreach ([['kcal', 'Ккал'], ['protein', 'Белки'], ['fat', 'Жиры'], ['carbs', 'Углев.']] as [$key, $label])
                                <div>
                                    <label class="mb-1 block text-xs text-zinc-400" for="food-{{ $key }}">{{ $label }}</label>
                                    <input id="food-{{ $key }}" type="text" inputmode="decimal" wire:model="form.{{ $key }}" class="input min-h-[3rem] px-2 text-center" autocomplete="off">
                                </div>
                            @endforeach
                        </div>
                        @foreach (['form.kcal', 'form.protein', 'form.fat', 'form.carbs'] as $field)
                            <x-input-error :messages="$errors->get($field)" />
                        @endforeach
                    </fieldset>
                    <fieldset>
                        <legend class="label">Типичная порция (необязательно)</legend>
                        <div class="grid grid-cols-3 gap-2">
                            <input type="text" inputmode="decimal" wire:model="form.serving_g" placeholder="граммы" class="input min-h-[3rem]" aria-label="Вес порции, г" autocomplete="off">
                            <input type="text" wire:model="form.serving_name" placeholder="например, 1 шт." maxlength="40" class="input col-span-2 min-h-[3rem]" aria-label="Название порции">
                        </div>
                        <x-input-error :messages="$errors->get('form.serving_g')" />
                        <x-input-error :messages="$errors->get('form.serving_name')" />
                    </fieldset>
                    <div class="flex gap-2">
                        <button type="button" wire:click="$set('showForm', false)" class="btn-secondary flex-1">Отмена</button>
                        <button class="btn-primary flex-[2]">Сохранить</button>
                    </div>
                </form>
            @else
                <button type="button" wire:click="$set('showForm', true)" class="btn-secondary w-full">+ Добавить свой продукт</button>
            @endif
        @endif

        <ul class="space-y-2">
            @forelse ($foods as $food)
                <li wire:key="food-{{ $food->id }}" class="card flex items-center gap-2 !p-3">
                    <div class="min-w-0 flex-1">
                        <div class="font-medium leading-tight">{{ $food->name }}@if ($food->user_id && $tab === 'all') <span class="text-xs text-lime-400">свой</span>@endif</div>
                        <div class="mt-0.5 text-xs text-zinc-400">{{ format_kg($food->kcal) }} ккал · Б {{ format_kg($food->protein) }} · Ж {{ format_kg($food->fat) }} · У {{ format_kg($food->carbs) }} <span class="whitespace-nowrap text-zinc-600">на 100 г</span></div>
                        <div class="mt-0.5 text-xs text-zinc-500">
                            {{ $food->categoryLabel() }}@if ($food->serving_g) · {{ $food->serving_name }} = {{ format_kg($food->serving_g) }} г@endif
                        </div>
                    </div>
                    @if ($tab === 'mine')
                        <button type="button" wire:click="deleteFood({{ $food->id }})" wire:confirm="Удалить продукт «{{ $food->name }}»? Записи в дневнике сохранятся." class="btn-ghost btn-sm !px-2 text-zinc-500" aria-label="Удалить {{ $food->name }}">✕</button>
                    @else
                        <button type="button" wire:click="toggleFavorite({{ $food->id }})" class="btn-ghost btn-sm !px-2 text-xl {{ in_array($food->id, $favoriteIds) ? 'text-amber-300' : 'text-zinc-600' }}" aria-label="{{ in_array($food->id, $favoriteIds) ? 'Убрать из избранного' : 'В избранное' }}: {{ $food->name }}" aria-pressed="{{ in_array($food->id, $favoriteIds) ? 'true' : 'false' }}">{{ in_array($food->id, $favoriteIds) ? '★' : '☆' }}</button>
                    @endif
                    <a href="{{ route('nutrition.diary', ['add' => 'snack', 'food' => $food->id]) }}" class="btn-secondary btn-sm !h-11 !w-11 !px-0 text-xl" aria-label="Записать в дневник: {{ $food->name }}">+</a>
                </li>
            @empty
                <li class="card text-center text-sm text-zinc-400">
                    @if ($tab === 'mine')
                        Своих продуктов пока нет. Добавьте то, что часто едите, а в каталоге не нашли.
                    @else
                        Ничего не найдено.
                    @endif
                </li>
            @endforelse
        </ul>
    @endif
</div>
