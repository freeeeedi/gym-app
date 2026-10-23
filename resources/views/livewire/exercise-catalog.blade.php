<div class="space-y-3">
    <div class="space-y-2">
        <input type="search" wire:model.live.debounce.250ms="search" placeholder="Поиск упражнения" class="input min-h-[3rem]" aria-label="Поиск упражнения">
        <div class="grid grid-cols-3 gap-2">
            <select wire:model.live="muscle" class="input text-sm" aria-label="Мышца">
                <option value="">Мышца</option>
                @foreach ($muscleOptions as $slug => $name)
                    <option value="{{ $slug }}">{{ $name }}</option>
                @endforeach
            </select>
            <select wire:model.live="pattern" class="input text-sm" aria-label="Паттерн">
                <option value="">Паттерн</option>
                @foreach ($patternOptions as $slug => $name)
                    <option value="{{ $slug }}">{{ $name }}</option>
                @endforeach
            </select>
            <select wire:model.live="equipment" class="input text-sm" aria-label="Оборудование">
                <option value="">Снаряд</option>
                @foreach ($equipmentOptions as $slug => $name)
                    <option value="{{ $slug }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-center justify-between">
            <span class="muted">Найдено: {{ $exercises->count() }}</span>
            @if ($search || $muscle || $pattern || $equipment)
                <button type="button" wire:click="resetFilters" class="text-sm text-lime-400">Сбросить фильтры</button>
            @endif
        </div>
    </div>

    <ul class="space-y-2">
        @forelse ($exercises as $exercise)
            <li wire:key="cat-{{ $exercise->id }}" class="card flex items-center gap-3 !p-3">
                <x-exercise-icon :icon="$exercise->icon" class="h-10 w-10 rounded-xl bg-zinc-800 p-1.5 text-lime-400" />
                <a href="{{ route('exercises.show', $exercise) }}" class="min-w-0 flex-1">
                    <div class="font-medium leading-tight">{{ $exercise->name }}</div>
                    <div class="mt-0.5 truncate text-xs text-zinc-400">
                        {{ collect($exercise->primaryMuscles())->map(fn ($m) => $muscleOptions[$m])->implode(', ') }}
                        · {{ $exercise->equipmentLabel() }} · {{ $exercise->mechanicLabel() }}
                        @if ($exercise->user_id) · <span class="text-lime-400">своё</span> @endif
                    </div>
                </a>
                @if ($picker)
                    @if (in_array($exercise->id, $selectedIds))
                        <span class="flex h-11 w-11 items-center justify-center text-lime-400" title="Уже в тренировке">✓</span>
                    @else
                        <button type="button" wire:click="pick({{ $exercise->id }})" class="btn-secondary btn-sm !h-11 !w-11 !px-0 text-xl" aria-label="Добавить {{ $exercise->name }}">+</button>
                    @endif
                @endif
            </li>
        @empty
            <li class="card text-center text-zinc-400">Ничего не найдено.</li>
        @endforelse
    </ul>
</div>
