<x-app-layout>
    <x-slot name="title">Блюда</x-slot>
    <x-slot name="header">
        <h1 class="flex-1 font-semibold">Питание</h1>
    </x-slot>

    <x-nutrition-tabs />

    <div class="space-y-3">
        <div class="card space-y-1 text-sm">
            <div>
                Цель: <span class="font-semibold text-lime-300">{{ $profile->goalLabel() }}</span>
                @if ($profile->restrictions)
                    · {{ implode(', ', array_map('mb_strtolower', $profile->restrictionLabels())) }}
                @endif
            </div>
            <p class="text-xs text-zinc-400">
                Блюда из продуктов каталога, от самых подходящих вашей цели. КБЖУ указано на одну порцию.
                @if ($result['excluded'] > 0)
                    Скрыто из-за ограничений: {{ $result['excluded'] }}.
                @endif
            </p>
        </div>

        <div class="-mx-4 flex gap-2 overflow-x-auto px-4" aria-label="Приём пищи">
            <a href="{{ route('nutrition.dishes.index') }}" class="chip shrink-0 {{ $meal === null ? 'chip-active' : '' }}">Все</a>
            @foreach ($meals as $key => $label)
                <a href="{{ route('nutrition.dishes.index', ['meal' => $key]) }}" class="chip shrink-0 whitespace-nowrap {{ $meal === $key ? 'chip-active' : '' }}">{{ $label }}</a>
            @endforeach
        </div>

        <ul class="space-y-2">
            @forelse ($result['suggestions'] as $suggestion)
                @php($dish = $suggestion->dish)
                @php($m = $suggestion->perServing)
                <li class="card !p-0">
                    <a href="{{ route('nutrition.dishes.show', $dish) }}" class="block p-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="font-medium leading-tight">{{ $dish->name }}</div>
                                <div class="mt-0.5 text-xs text-zinc-500">
                                    {{ implode(', ', array_map('mb_strtolower', $dish->mealLabels())) }}@if ($dish->cook_minutes) · {{ $dish->cook_minutes }} мин@endif
                                    @if (in_array($dish->id, $inShoppingList)) · <span class="text-lime-400">в списке покупок</span>@endif
                                </div>
                            </div>
                            <div class="shrink-0 text-right">
                                <div class="font-semibold tabular-nums">{{ format_int($m->kcal) }}</div>
                                <div class="text-xs text-zinc-500">ккал</div>
                            </div>
                        </div>
                        <div class="mt-1 text-xs text-zinc-400">Б {{ format_int($m->protein) }} · Ж {{ format_int($m->fat) }} · У {{ format_int($m->carbs) }} г на порцию</div>
                        @if ($suggestion->reasons)
                            <div class="mt-1 text-xs text-lime-300/90">{{ implode(' · ', $suggestion->reasons) }}</div>
                        @endif
                    </a>
                </li>
            @empty
                <li class="card text-center text-sm text-zinc-400">Нет блюд, подходящих под выбранный приём пищи и ваши ограничения.</li>
            @endforelse
        </ul>
    </div>
</x-app-layout>
