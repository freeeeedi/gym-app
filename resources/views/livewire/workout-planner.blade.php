@php
    $muscleNames = config('training.muscles');
    $over = $totalMinutes > $budget;
    $percent = min(100, $budget > 0 ? round($totalMinutes / $budget * 100) : 0);
@endphp
<div class="space-y-5">
    {{-- Прогноз времени --}}
    <section class="card space-y-2" aria-label="Прогноз длительности">
        <div class="flex items-baseline justify-between">
            <span class="muted">Прогноз с разминкой</span>
            <span class="text-lg font-semibold {{ $over ? 'text-red-400' : 'text-zinc-50' }}">
                {{ format_minutes($totalMinutes) }} <span class="text-sm font-normal text-zinc-400">/ {{ $budget }} мин</span>
            </span>
        </div>
        <div class="h-2 overflow-hidden rounded-full bg-zinc-800">
            <div class="h-full rounded-full {{ $over ? 'bg-red-500' : 'bg-lime-400' }}" style="width: {{ $percent }}%"></div>
        </div>
        @if ($recommendation)
            <div class="flex flex-wrap gap-1.5 pt-1">
                @foreach ($recommendation->dayMuscles as $m)
                    @php($n = $recommendation->sessionExercises[$m] ?? 0)
                    <span class="chip !py-0.5 text-xs {{ $n > 0 ? 'chip-active' : '' }}">{{ $muscleNames[$m] }}{{ $n ? ' · '.$n : '' }}</span>
                @endforeach
            </div>
        @endif
    </section>

    {{-- Выбранные упражнения --}}
    <section aria-label="Упражнения тренировки">
        <h2 class="h2 mb-2">Упражнения <span class="text-zinc-500">{{ $workout->workoutExercises->count() }}</span></h2>
        @if ($workout->workoutExercises->isEmpty())
            <div class="card text-center text-zinc-400">
                Пока пусто. Добавьте упражнения из подсказок ниже — они подобраны под тип дня.
            </div>
        @else
            <ol class="space-y-2">
                @foreach ($workout->workoutExercises as $we)
                    <li wire:key="we-{{ $we->id }}" class="card !p-3">
                        <div class="flex items-start gap-3">
                            <x-exercise-icon :icon="$we->exercise->icon" class="mt-0.5 h-10 w-10 rounded-xl bg-zinc-800 p-1.5 text-lime-400" />
                            <div class="min-w-0 flex-1">
                                <div class="font-medium leading-tight">{{ $we->exercise->name }}</div>
                                <div class="mt-0.5 text-xs text-zinc-400">
                                    {{ $we->exercise->patternLabel() }} · {{ $we->exercise->mechanicLabel() }}
                                    · ~{{ format_minutes($estimator->exerciseMinutes($we->exercise, $we->planned_sets)) }} мин
                                </div>
                            </div>
                            @if ($editable && $we->sets->isEmpty())
                                <button type="button" wire:click="remove({{ $we->id }})" class="btn-ghost btn-sm !px-2 text-zinc-500" aria-label="Убрать {{ $we->exercise->name }}">✕</button>
                            @endif
                        </div>
                        @if ($editable)
                            <div class="mt-2 flex items-center justify-between gap-2">
                                <div class="flex items-center gap-1" role="group" aria-label="Подходы">
                                    <button type="button" wire:click="setSets({{ $we->id }}, {{ $we->planned_sets - 1 }})" class="btn-secondary btn-sm !w-10 !px-0" aria-label="Меньше подходов">−</button>
                                    <span class="w-20 text-center text-sm">{{ $we->planned_sets }} подх.</span>
                                    <button type="button" wire:click="setSets({{ $we->id }}, {{ $we->planned_sets + 1 }})" class="btn-secondary btn-sm !w-10 !px-0" aria-label="Больше подходов">+</button>
                                </div>
                                <div class="flex gap-1">
                                    <button type="button" wire:click="move({{ $we->id }}, -1)" class="btn-secondary btn-sm !w-10 !px-0" @disabled($loop->first) aria-label="Выше">↑</button>
                                    <button type="button" wire:click="move({{ $we->id }}, 1)" class="btn-secondary btn-sm !w-10 !px-0" @disabled($loop->last) aria-label="Ниже">↓</button>
                                </div>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ol>
        @endif
    </section>

    {{-- Основное действие --}}
    @if ($workout->status->value === 'planned')
        <form method="POST" action="{{ route('workouts.start', $workout) }}">
            @csrf
            <button class="btn-primary w-full text-lg" @disabled($workout->workoutExercises->isEmpty())>▶ Начать тренировку</button>
        </form>
    @elseif ($workout->status->value === 'in_progress')
        <a href="{{ route('workouts.session', $workout) }}" class="btn-primary w-full text-lg">Продолжить тренировку</a>
    @else
        <a href="{{ route('workouts.summary', $workout) }}" class="btn-secondary w-full">Итоги тренировки</a>
    @endif

    {{-- Разминка --}}
    @if ($warmup)
        <section class="card space-y-3" aria-label="Разминка" x-data="{ open: {{ $workout->status->value === 'planned' ? 'false' : 'true' }} }">
            <button type="button" class="flex w-full items-center justify-between" @click="open = !open" :aria-expanded="open">
                <h2 class="h2">Разминка · ~{{ $warmup->minutes() }} мин</h2>
                <span class="text-zinc-400" x-text="open ? '▲' : '▼'"></span>
            </button>
            <div class="muted">Акцент: {{ collect($warmup->focusMuscles)->map(fn ($m) => $muscleNames[$m])->implode(', ') }}</div>
            <div x-show="open" x-cloak class="space-y-3">
                @if ($warmup->general)
                    <div>
                        <div class="text-xs uppercase tracking-wide text-zinc-500">1. Общая</div>
                        <div class="mt-1 flex items-center gap-2">
                            <x-exercise-icon :icon="$warmup->general->icon" class="h-6 w-6 text-lime-400" />
                            <span>{{ $warmup->general->name }} — {{ $warmup->general->minutes }} мин</span>
                        </div>
                        <div class="muted">{{ $warmup->general->description }}</div>
                    </div>
                @endif
                @if ($warmup->mobility)
                    <div>
                        <div class="text-xs uppercase tracking-wide text-zinc-500">2. Суставы и активация</div>
                        <ul class="mt-1 space-y-1">
                            @foreach ($warmup->mobility as $item)
                                <li class="flex gap-2"><span class="text-lime-400">•</span><span>{{ $item->name }} <span class="text-zinc-400">— {{ $item->reps }}</span></span></li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if ($warmup->rampExercise)
                    <div>
                        <div class="text-xs uppercase tracking-wide text-zinc-500">3. Разминочные подходы: {{ $warmup->rampExercise->name }}</div>
                        @if ($warmup->workingWeight)
                            <div class="muted">Рабочий вес по истории: {{ format_kg($warmup->workingWeight) }} кг</div>
                        @else
                            <div class="muted">Рабочий вес пока неизвестен — начните с лёгкого веса и прибавляйте.</div>
                        @endif
                        <div class="mt-1 grid grid-cols-3 gap-2">
                            @foreach ($warmup->rampSets as $set)
                                <div class="rounded-xl bg-zinc-800 p-2 text-center">
                                    <div class="font-semibold">{{ $set['weight'] !== null ? format_kg($set['weight']).' кг' : $set['percent'].'%' }}</div>
                                    <div class="text-xs text-zinc-400">× {{ $set['reps'] }}{{ $set['weight'] !== null ? ' · '.$set['percent'].'%' : '' }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- Подсказки и каталог --}}
    @if ($editable)
        <section aria-label="Добавить упражнения">
            <div class="mb-3 grid grid-cols-2 gap-1 rounded-xl bg-zinc-900 p-1" role="tablist">
                <button type="button" role="tab" wire:click="$set('tab', 'suggestions')" aria-selected="{{ $tab === 'suggestions' ? 'true' : 'false' }}"
                        class="rounded-lg py-2.5 text-sm font-semibold {{ $tab === 'suggestions' ? 'bg-zinc-700 text-zinc-50' : 'text-zinc-400' }}">Подсказки</button>
                <button type="button" role="tab" wire:click="$set('tab', 'catalog')" aria-selected="{{ $tab === 'catalog' ? 'true' : 'false' }}"
                        class="rounded-lg py-2.5 text-sm font-semibold {{ $tab === 'catalog' ? 'bg-zinc-700 text-zinc-50' : 'text-zinc-400' }}">Каталог</button>
            </div>

            @if ($tab === 'catalog')
                <livewire:exercise-catalog :picker="true" :selected-ids="$workout->workoutExercises->pluck('exercise_id')->all()" :key="'picker-'.$workout->workoutExercises->pluck('exercise_id')->implode('-')" />
            @else
                @if ($recommendation->missingPatterns)
                    <p class="mb-3 text-sm text-amber-300">
                        Не закрыто: {{ collect($recommendation->missingPatterns)->map(fn ($p) => mb_strtolower(config('training.patterns.'.$p)))->implode(', ') }}
                    </p>
                @elseif ($workout->template)
                    <p class="mb-3 text-sm text-lime-300">Все обязательные паттерны дня закрыты. Ниже — чем можно дополнить.</p>
                @endif

                <div class="space-y-4">
                    @forelse ($recommendation->groups as $group)
                        <div wire:key="group-{{ $group->pattern }}" x-data="{ more: false }">
                            <div class="mb-2 flex items-center gap-2">
                                <h3 class="font-semibold">{{ $group->label }}</h3>
                                @if ($group->required && ! $group->covered)
                                    <span class="rounded-full bg-amber-400/15 px-2 py-0.5 text-xs text-amber-300">нужен</span>
                                @elseif ($group->covered)
                                    <span class="rounded-full bg-zinc-800 px-2 py-0.5 text-xs text-zinc-400">уже есть</span>
                                @endif
                            </div>
                            <ul class="space-y-2">
                                @foreach ($group->suggestions as $s)
                                    <li wire:key="sug-{{ $s->exercise->id }}" class="card !p-3" @if ($loop->index >= $perPattern) x-show="more" x-cloak @endif>
                                        <div class="flex items-start gap-3">
                                            <x-exercise-icon :icon="$s->exercise->icon" class="mt-0.5 h-10 w-10 rounded-xl bg-zinc-800 p-1.5 text-lime-400" />
                                            <div class="min-w-0 flex-1">
                                                <div class="font-medium leading-tight">{{ $s->exercise->name }}</div>
                                                <div class="mt-0.5 text-xs text-zinc-400">
                                                    {{ $s->exercise->equipmentLabel() }} · {{ $s->exercise->mechanicLabel() }} · +{{ format_minutes($s->minutes) }} мин → {{ format_minutes($s->projectedMinutes) }} мин
                                                </div>
                                            </div>
                                            <button type="button" wire:click="add({{ $s->exercise->id }})" wire:loading.attr="disabled"
                                                    class="btn-primary btn-sm !h-11 !w-11 !px-0 text-xl" aria-label="Добавить {{ $s->exercise->name }}">+</button>
                                        </div>
                                        <details class="mt-2 text-sm">
                                            <summary class="cursor-pointer list-none text-zinc-300">
                                                <span class="text-lime-400">Почему:</span> {{ $s->reasons[0] ?? '' }}
                                                @if (count($s->reasons) > 1)<span class="text-zinc-500"> · ещё {{ count($s->reasons) - 1 }}</span>@endif
                                            </summary>
                                            <ul class="mt-1 space-y-0.5 pl-4 text-zinc-400">
                                                @foreach (array_slice($s->reasons, 1) as $reason)
                                                    <li class="list-disc">{{ $reason }}</li>
                                                @endforeach
                                            </ul>
                                        </details>
                                    </li>
                                @endforeach
                            </ul>
                            @if (count($group->suggestions) > $perPattern)
                                <button type="button" class="mt-2 text-sm text-lime-400" @click="more = !more"
                                        x-text="more ? 'Свернуть' : 'Ещё варианты ({{ count($group->suggestions) - $perPattern }})'"></button>
                            @endif
                        </div>
                    @empty
                        <div class="card text-center text-zinc-400">
                            Подсказок нет: время или объём на сегодня исчерпаны. Можно добавить упражнение из каталога вручную.
                        </div>
                    @endforelse
                </div>

                @if ($recommendation->excluded)
                    <details class="mt-4 rounded-xl border border-zinc-800 p-3 text-sm">
                        <summary class="cursor-pointer text-zinc-300">Почему не предложены другие ({{ count($recommendation->excluded) }})</summary>
                        <ul class="mt-2 space-y-1.5 text-zinc-400">
                            @foreach ($recommendation->excluded as $row)
                                <li><span class="text-zinc-200">{{ $row['exercise']->name }}</span> — {{ $row['reason'] }}</li>
                            @endforeach
                        </ul>
                    </details>
                @endif
            @endif
        </section>
    @endif
</div>
