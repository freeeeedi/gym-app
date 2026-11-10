@php
    $meals = config('nutrition.meals');
    $eaten = $day['totals'];
    $over = $targets && $remaining->kcal < 0;
    $dayLabel = $isToday ? 'Сегодня' : ($current->isYesterday() ? 'Вчера' : ($current->isTomorrow() ? 'Завтра' : $current->translatedFormat('l')));
    $tabs = ['search' => 'Поиск', 'recent' => 'Недавние', 'favorites' => 'Избранное', 'dishes' => 'Блюда', 'templates' => 'Шаблоны', 'custom' => 'Свой продукт'];
@endphp
<div class="space-y-4">
    {{-- Дата --}}
    <div class="flex items-center gap-2">
        <button type="button" wire:click="shiftDay(-1)" class="btn-secondary btn-sm !w-11 !px-0 text-lg" aria-label="Предыдущий день">‹</button>
        <label class="relative min-w-0 flex-1 cursor-pointer text-center">
            <span class="block font-semibold capitalize">{{ $dayLabel }}</span>
            <span class="block text-xs text-zinc-400">{{ $current->translatedFormat('j F Y') }}</span>
            <input type="date" wire:model.live="date" value="{{ $current->toDateString() }}" class="absolute inset-0 h-full w-full cursor-pointer opacity-0" aria-label="Выбрать дату">
        </label>
        <button type="button" wire:click="shiftDay(1)" class="btn-secondary btn-sm !w-11 !px-0 text-lg" aria-label="Следующий день">›</button>
    </div>
    @unless ($isToday)
        <button type="button" wire:click="goToday" class="block w-full text-center text-sm text-lime-400">Вернуться к сегодняшнему дню</button>
    @endunless

    @if (session('diary'))
        <div class="rounded-xl border border-lime-900 bg-lime-950/60 px-4 py-3 text-sm text-lime-200" role="status">{{ session('diary') }}</div>
    @endif

    {{-- Сводка дня --}}
    <section class="card space-y-3" aria-label="Итог дня">
        @if ($targets)
            <div class="flex items-end justify-between gap-3">
                <div>
                    <div class="muted">{{ $over ? 'Перебор' : 'Осталось' }}</div>
                    <div class="text-3xl font-bold tabular-nums {{ $over ? 'text-red-400' : 'text-lime-300' }}">
                        {{ format_int(abs($remaining->kcal)) }} <span class="text-base font-normal text-zinc-400">ккал</span>
                    </div>
                </div>
                <div class="text-right text-sm text-zinc-400">
                    <div>съедено <span class="font-semibold text-zinc-100">{{ format_int($eaten->kcal) }}</span></div>
                    <div>норма {{ format_int($targets->kcal) }}</div>
                </div>
            </div>
            <div class="h-3 overflow-hidden rounded-full bg-zinc-800" role="progressbar" aria-label="Калории" aria-valuenow="{{ min(100, round($eaten->kcal / $targets->kcal * 100)) }}" aria-valuemin="0" aria-valuemax="100">
                <div class="h-full rounded-full {{ $over ? 'bg-red-500' : 'bg-lime-400' }}" style="width: {{ min(100, $eaten->kcal / $targets->kcal * 100) }}%"></div>
            </div>
            <div class="grid grid-cols-3 gap-3">
                @foreach ([['Белки', 'protein', 'bg-sky-400'], ['Жиры', 'fat', 'bg-amber-400'], ['Углеводы', 'carbs', 'bg-violet-400']] as [$label, $key, $color])
                    <div>
                        <x-macro-bar :label="$label" :value="$eaten->{$key}" :target="$targets->{$key}" :color="$color" />
                        <div class="mt-1 text-xs text-zinc-500">
                            @if ($remaining->{$key} >= 0) ещё {{ format_int($remaining->{$key}) }} г @else +{{ format_int(abs($remaining->{$key})) }} г сверх @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="flex items-end justify-between gap-3">
                <div>
                    <div class="muted">Съедено</div>
                    <div class="text-3xl font-bold tabular-nums">{{ format_int($eaten->kcal) }} <span class="text-base font-normal text-zinc-400">ккал</span></div>
                </div>
                <div class="text-right text-sm text-zinc-400">Б {{ format_int($eaten->protein) }} · Ж {{ format_int($eaten->fat) }} · У {{ format_int($eaten->carbs) }}</div>
            </div>
            <a href="{{ route('nutrition.profile.edit') }}" class="block rounded-xl border border-amber-900 bg-amber-950/40 p-3 text-sm text-amber-200">
                Заполните профиль питания — рассчитаем вашу норму КБЖУ и покажем остаток на день ›
            </a>
        @endif
    </section>

    {{-- Приёмы пищи --}}
    @foreach ($day['meals'] as $meal => $block)
        <section class="card !p-0" aria-label="{{ $block['label'] }}" wire:key="meal-{{ $meal }}">
            <div class="flex items-center gap-3 p-3 pl-4">
                <div class="min-w-0 flex-1">
                    <h2 class="font-semibold">{{ $block['label'] }}</h2>
                    @if ($block['entries']->isNotEmpty())
                        <div class="text-xs text-zinc-400">
                            {{ format_int($block['totals']->kcal) }} ккал · Б {{ format_int($block['totals']->protein) }} · Ж {{ format_int($block['totals']->fat) }} · У {{ format_int($block['totals']->carbs) }}
                        </div>
                    @endif
                </div>
                <button type="button" wire:click="openAdd('{{ $meal }}')" class="btn-secondary btn-sm" aria-label="Добавить в «{{ $block['label'] }}»">+ Добавить</button>
            </div>

            @if ($block['entries']->isNotEmpty())
                <ul class="border-t border-zinc-800">
                    @foreach ($block['entries'] as $entry)
                        <li wire:key="entry-{{ $entry->id }}" class="border-b border-zinc-800 px-4 py-2 last:border-0">
                            @if ($editingId === $entry->id)
                                <form wire:submit="saveEdit" class="space-y-2">
                                    <div class="text-sm font-medium">{{ $entry->name }}</div>
                                    <div class="flex items-center gap-2">
                                        <input type="text" inputmode="decimal" wire:model="editAmount" class="input min-h-[2.75rem] w-28 text-center" aria-label="{{ $entry->grams !== null ? 'Граммы' : 'Порции' }}">
                                        <span class="text-sm text-zinc-400">{{ $entry->grams !== null ? 'г' : 'порц.' }}</span>
                                        <button class="btn-primary btn-sm ml-auto">Сохранить</button>
                                        <button type="button" wire:click="cancelEdit" class="btn-ghost btn-sm">Отмена</button>
                                    </div>
                                    <x-input-error :messages="$errors->get('editAmount')" />
                                    <button type="button" wire:click="deleteEntry({{ $entry->id }})" class="text-sm text-red-300">Удалить запись</button>
                                </form>
                            @else
                                <div class="flex items-center gap-2">
                                    <button type="button" wire:click="startEdit({{ $entry->id }})" class="min-w-0 flex-1 text-left" @disabled($entry->grams === null && $entry->servings === null) aria-label="Изменить количество: {{ $entry->name }}">
                                        <span class="block truncate text-sm">{{ $entry->name }}</span>
                                        <span class="block text-xs text-zinc-400">
                                            @if ($entry->amountLabel()){{ $entry->amountLabel() }} · @endif
                                            Б {{ format_kg($entry->protein) }} · Ж {{ format_kg($entry->fat) }} · У {{ format_kg($entry->carbs) }}
                                        </span>
                                    </button>
                                    <span class="shrink-0 text-sm tabular-nums text-zinc-200">{{ format_int($entry->kcal) }} <span class="text-xs text-zinc-500">ккал</span></span>
                                    <button type="button" wire:click="deleteEntry({{ $entry->id }})" class="btn-ghost btn-sm !min-h-[2.25rem] !px-2 text-zinc-500" aria-label="Удалить {{ $entry->name }}">✕</button>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
                <div class="border-t border-zinc-800 px-4 py-2">
                    @if ($templateMeal === $meal)
                        <form wire:submit="saveTemplate" class="space-y-2">
                            <label class="label" for="template-name">Название шаблона</label>
                            <div class="flex gap-2">
                                <input id="template-name" type="text" wire:model="templateName" maxlength="80" class="input min-h-[2.75rem] flex-1">
                                <button class="btn-primary btn-sm">Сохранить</button>
                                <button type="button" wire:click="cancelTemplate" class="btn-ghost btn-sm">Отмена</button>
                            </div>
                            <x-input-error :messages="$errors->get('templateName')" />
                        </form>
                    @else
                        <button type="button" wire:click="startTemplate('{{ $meal }}')" class="text-sm text-zinc-400">Сохранить приём как шаблон</button>
                    @endif
                </div>
            @endif
        </section>
    @endforeach

    @if ($previousCount > 0)
        <button type="button" wire:click="copyYesterday" class="btn-secondary w-full !text-sm">
            Скопировать предыдущий день ({{ $previousCount }} зап.)
        </button>
    @endif

    {{-- Вода --}}
    <section class="card space-y-3" aria-label="Вода">
        <x-macro-bar label="Вода" :value="$waterMl" :target="$waterTarget" unit="мл" color="bg-sky-400" />
        <div class="flex flex-wrap gap-2">
            @foreach (config('nutrition.water.portions') as $ml)
                <button type="button" wire:click="addWater({{ $ml }})" class="btn-secondary btn-sm flex-1">+{{ $ml }} мл</button>
            @endforeach
            <button type="button" wire:click="addWater(-{{ config('nutrition.water.portions')[0] }})" class="btn-ghost btn-sm" @disabled($waterMl === 0) aria-label="Убрать {{ config('nutrition.water.portions')[0] }} мл">−{{ config('nutrition.water.portions')[0] }}</button>
        </div>
    </section>

    {{-- Панель добавления --}}
    @if ($addingTo)
        <div class="fixed inset-0 z-40 overflow-y-auto bg-zinc-950" role="dialog" aria-modal="true" aria-label="Добавить в дневник">
            <div class="mx-auto max-w-xl px-4 pb-10">
                <div class="sticky top-0 z-10 -mx-4 space-y-3 border-b border-zinc-900 bg-zinc-950/95 px-4 py-3 backdrop-blur">
                    <div class="flex items-center gap-3">
                        <button type="button" wire:click="closeAdd" class="btn-ghost btn-sm !px-2" aria-label="Закрыть">←</button>
                        <h2 class="min-w-0 flex-1 truncate font-semibold">{{ $meals[$addingTo] }} · {{ $isToday ? 'сегодня' : $current->format('d.m') }}</h2>
                        <button type="button" wire:click="closeAdd" class="btn-primary btn-sm">Готово</button>
                    </div>
                    <div class="-mx-4 scroll-row flex gap-2 overflow-x-auto px-4" role="tablist">
                        @foreach ($tabs as $key => $label)
                            <button type="button" wire:click="setTab('{{ $key }}')" role="tab" aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                                    class="chip shrink-0 whitespace-nowrap {{ $tab === $key ? 'chip-active' : '' }}">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>

                <div class="space-y-3 pt-3">
                    @if ($notice)
                        <div class="rounded-xl border border-lime-900 bg-lime-950/60 px-4 py-2 text-sm text-lime-200" role="status">✓ {{ $notice }}</div>
                    @endif

                    @if ($food)
                        {{-- Граммовка выбранного продукта --}}
                        <form wire:submit="addFood" class="card space-y-3">
                            <div class="flex items-start gap-2">
                                <div class="min-w-0 flex-1">
                                    <div class="font-semibold leading-tight">{{ $food->name }}</div>
                                    <div class="mt-0.5 text-xs text-zinc-400">
                                        На 100 г: {{ format_kg($food->kcal) }} ккал · Б {{ format_kg($food->protein) }} · Ж {{ format_kg($food->fat) }} · У {{ format_kg($food->carbs) }}
                                    </div>
                                </div>
                                <button type="button" wire:click="toggleFavorite({{ $food->id }})" class="btn-ghost btn-sm !px-2 text-xl {{ in_array($food->id, $favoriteIds) ? 'text-amber-300' : 'text-zinc-500' }}" aria-label="{{ in_array($food->id, $favoriteIds) ? 'Убрать из избранного' : 'В избранное' }}" aria-pressed="{{ in_array($food->id, $favoriteIds) ? 'true' : 'false' }}">{{ in_array($food->id, $favoriteIds) ? '★' : '☆' }}</button>
                            </div>
                            <div>
                                <label class="label" for="grams">Сколько, г</label>
                                <input id="grams" type="text" inputmode="decimal" wire:model.live.debounce.300ms="grams" class="input min-h-[3.5rem] text-center text-2xl font-semibold" autocomplete="off">
                                <x-input-error :messages="$errors->get('grams')" />
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @if ($food->serving_g)
                                    @foreach ([1, 2, 3] as $n)
                                        <button type="button" wire:click="$set('grams', '{{ format_number_input($food->serving_g * $n) }}')" class="chip">
                                            {{ $n > 1 ? $n.' × ' : '' }}{{ $food->serving_name }} · {{ format_kg($food->serving_g * $n) }} г
                                        </button>
                                    @endforeach
                                @else
                                    @foreach ([50, 100, 150, 200] as $g)
                                        <button type="button" wire:click="$set('grams', '{{ $g }}')" class="chip">{{ $g }} г</button>
                                    @endforeach
                                @endif
                            </div>
                            @if ($foodMacros)
                                <div class="grid grid-cols-4 gap-2 text-center">
                                    @foreach ([['ккал', $foodMacros->kcal], ['белки', $foodMacros->protein], ['жиры', $foodMacros->fat], ['углев.', $foodMacros->carbs]] as [$label, $value])
                                        <div class="rounded-xl bg-zinc-800 p-2">
                                            <div class="font-semibold tabular-nums">{{ format_kg($value) }}</div>
                                            <div class="text-xs text-zinc-400">{{ $label }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            <div class="flex gap-2">
                                <button type="button" wire:click="cancelFood" class="btn-secondary flex-1">Назад</button>
                                <button class="btn-primary flex-[2]">Добавить</button>
                            </div>
                        </form>
                    @elseif ($tab === 'custom')
                        {{-- Свой продукт вручную --}}
                        <form wire:submit="addCustom" class="card space-y-3">
                            <p class="text-xs text-zinc-400">Для того, чего нет в каталоге: пирожное из кофейни, блюдо в кафе, продукт с этикеткой.</p>
                            <div>
                                <label class="label" for="custom-name">Название</label>
                                <input id="custom-name" type="text" wire:model="custom.name" maxlength="120" class="input min-h-[3rem]" placeholder="Например, эклер">
                                <x-input-error :messages="$errors->get('custom.name')" />
                            </div>
                            <div class="grid grid-cols-2 gap-2" role="radiogroup" aria-label="КБЖУ указаны">
                                <label class="chip min-h-[2.75rem] cursor-pointer justify-center has-[:checked]:chip-active">
                                    <input type="radio" wire:model.live="custom.basis" value="portion" class="sr-only">за всю порцию
                                </label>
                                <label class="chip min-h-[2.75rem] cursor-pointer justify-center has-[:checked]:chip-active">
                                    <input type="radio" wire:model.live="custom.basis" value="per100" class="sr-only">на 100 г
                                </label>
                            </div>
                            <div class="grid grid-cols-4 gap-2">
                                @foreach ([['kcal', 'Ккал'], ['protein', 'Белки'], ['fat', 'Жиры'], ['carbs', 'Углев.']] as [$key, $label])
                                    <div>
                                        <label class="label" for="custom-{{ $key }}">{{ $label }}</label>
                                        <input id="custom-{{ $key }}" type="text" inputmode="decimal" wire:model.blur="custom.{{ $key }}" class="input min-h-[3rem] px-2 text-center" autocomplete="off">
                                    </div>
                                @endforeach
                            </div>
                            @foreach (['custom.kcal', 'custom.protein', 'custom.fat', 'custom.carbs'] as $field)
                                <x-input-error :messages="$errors->get($field)" />
                            @endforeach
                            <p class="text-xs text-zinc-500">Калории можно не вводить — посчитаем из белков, жиров и углеводов.</p>
                            <div>
                                <label class="label" for="custom-grams">Вес порции, г{{ $custom['basis'] === 'per100' ? '' : ' (необязательно)' }}</label>
                                <input id="custom-grams" type="text" inputmode="decimal" wire:model.blur="custom.grams" class="input min-h-[3rem]" autocomplete="off">
                                <x-input-error :messages="$errors->get('custom.grams')" />
                            </div>
                            @if ($custom['basis'] === 'per100')
                                <label class="flex items-start gap-3">
                                    <input type="checkbox" wire:model="custom.save" class="mt-0.5 h-5 w-5 rounded border-zinc-600 bg-zinc-950 text-lime-400 focus:ring-lime-400">
                                    <span class="text-sm">Сохранить в «Мои продукты», чтобы потом находить поиском</span>
                                </label>
                            @endif
                            @if ($customPreview && ! $customPreview->isZero() && ! is_numeric(str_replace(',', '.', (string) $custom['kcal'])))
                                <div class="text-sm text-zinc-400">Расчётная калорийность: <span class="font-semibold text-zinc-100">{{ format_int($customPreview->kcal) }} ккал</span>{{ $custom['basis'] === 'per100' ? ' на 100 г' : '' }}</div>
                            @endif
                            <button class="btn-primary w-full">Добавить</button>
                        </form>
                    @elseif ($tab === 'dishes')
                        <ul class="space-y-2">
                            @forelse ($dishes as $dish)
                                @php($m = $dish->perServing())
                                <li wire:key="dish-{{ $dish->id }}" class="card flex items-center gap-3 !p-3">
                                    <a href="{{ route('nutrition.dishes.show', $dish) }}" class="min-w-0 flex-1">
                                        <div class="font-medium leading-tight">{{ $dish->name }}</div>
                                        <div class="mt-0.5 text-xs text-zinc-400">Порция: {{ format_int($m->kcal) }} ккал · Б {{ format_int($m->protein) }} · Ж {{ format_int($m->fat) }} · У {{ format_int($m->carbs) }}</div>
                                    </a>
                                    <button type="button" wire:click="addDish({{ $dish->id }})" class="btn-secondary btn-sm !h-11 !w-11 !px-0 text-xl" aria-label="Добавить порцию: {{ $dish->name }}">+</button>
                                </li>
                            @empty
                                <li class="card text-center text-zinc-400">Нет блюд, подходящих под ваши ограничения.</li>
                            @endforelse
                        </ul>
                    @elseif ($tab === 'templates')
                        <ul class="space-y-2">
                            @forelse ($templates as $template)
                                @php($m = $template->macros())
                                <li wire:key="tpl-{{ $template->id }}" class="card space-y-2 !p-3">
                                    <div class="flex items-center gap-3">
                                        <div class="min-w-0 flex-1">
                                            <div class="font-medium leading-tight">{{ $template->name }}</div>
                                            <div class="mt-0.5 text-xs text-zinc-400">{{ format_int($m->kcal) }} ккал · Б {{ format_int($m->protein) }} · Ж {{ format_int($m->fat) }} · У {{ format_int($m->carbs) }}</div>
                                        </div>
                                        <button type="button" wire:click="applyTemplate({{ $template->id }})" class="btn-secondary btn-sm">Добавить</button>
                                    </div>
                                    <div class="text-xs text-zinc-500">{{ $template->items->pluck('name')->implode(', ') }}</div>
                                    <button type="button" wire:click="deleteTemplate({{ $template->id }})" wire:confirm="Удалить шаблон «{{ $template->name }}»?" class="text-xs text-red-300">Удалить шаблон</button>
                                </li>
                            @empty
                                <li class="card text-center text-sm text-zinc-400">Шаблонов пока нет. Запишите приём пищи и нажмите под ним «Сохранить приём как шаблон» — например, обычный завтрак.</li>
                            @endforelse
                        </ul>
                    @else
                        {{-- Поиск, недавние, избранное --}}
                        @if ($tab === 'search')
                            <input type="search" wire:model.live.debounce.250ms="search" placeholder="Поиск продукта" class="input min-h-[3rem]" aria-label="Поиск продукта" autocomplete="off">
                        @endif
                        <ul class="space-y-2">
                            @forelse ($foods as $item)
                                <li wire:key="food-{{ $item->id }}" class="card flex items-center gap-2 !p-0">
                                    <button type="button" wire:click="selectFood({{ $item->id }})" class="min-w-0 flex-1 p-3 text-left">
                                        <span class="block font-medium leading-tight">{{ $item->name }}@if ($item->user_id) <span class="text-xs text-lime-400">свой</span>@endif</span>
                                        <span class="mt-0.5 block text-xs text-zinc-400">{{ format_kg($item->kcal) }} ккал · Б {{ format_kg($item->protein) }} · Ж {{ format_kg($item->fat) }} · У {{ format_kg($item->carbs) }} <span class="whitespace-nowrap text-zinc-600">на 100 г</span></span>
                                    </button>
                                    <button type="button" wire:click="toggleFavorite({{ $item->id }})" class="btn-ghost btn-sm mr-1 !px-2 text-xl {{ in_array($item->id, $favoriteIds) ? 'text-amber-300' : 'text-zinc-600' }}" aria-label="{{ in_array($item->id, $favoriteIds) ? 'Убрать из избранного' : 'В избранное' }}: {{ $item->name }}" aria-pressed="{{ in_array($item->id, $favoriteIds) ? 'true' : 'false' }}">{{ in_array($item->id, $favoriteIds) ? '★' : '☆' }}</button>
                                </li>
                            @empty
                                <li class="card text-center text-sm text-zinc-400">
                                    @if ($tab === 'search' && trim($search) === '')
                                        Начните вводить название: «курица», «гречка», «творог». Нет в каталоге — вкладка «Свой продукт».
                                    @elseif ($tab === 'search')
                                        Ничего не найдено. Внесите продукт вручную на вкладке «Свой продукт».
                                    @elseif ($tab === 'recent')
                                        Здесь появятся продукты, которые вы недавно записывали.
                                    @else
                                        Отмечайте продукты звёздочкой — они соберутся здесь.
                                    @endif
                                </li>
                            @endforelse
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
