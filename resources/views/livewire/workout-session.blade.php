@php($muscleNames = config('training.muscles'))
<div class="space-y-3">
    @foreach ($workout->workoutExercises as $we)
        @php($isActive = $active && $active->id === $we->id)
        @php($done = $we->sets->where('is_warmup', false)->count())
        @php($adv = $advice[$we->id] ?? null)
        <section wire:key="session-{{ $we->id }}" class="card !p-0 {{ $isActive ? 'border-lime-400/60' : '' }}">
            <button type="button" wire:click="open({{ $we->id }})" class="flex w-full items-center gap-3 p-3 text-left" aria-expanded="{{ $isActive ? 'true' : 'false' }}">
                <x-exercise-icon :icon="$we->exercise->icon" class="h-10 w-10 rounded-xl bg-zinc-800 p-1.5 {{ $done >= $we->planned_sets ? 'text-lime-400' : 'text-zinc-300' }}" />
                <div class="min-w-0 flex-1">
                    <div class="font-medium leading-tight">{{ $we->exercise->name }}</div>
                    <div class="text-xs text-zinc-400">{{ $we->exercise->rep_min }}–{{ $we->exercise->rep_max }} повт.</div>
                </div>
                <span class="rounded-full px-2.5 py-1 text-sm font-semibold {{ $done >= $we->planned_sets ? 'bg-lime-400 text-zinc-950' : 'bg-zinc-800 text-zinc-300' }}">
                    {{ $done }}/{{ $we->planned_sets }}
                </span>
            </button>

            @if ($isActive)
                <div class="space-y-3 border-t border-zinc-800 p-3">
                    @if ($adv)
                        <div class="rounded-xl bg-zinc-950 p-3 text-sm">
                            <div class="text-zinc-400">Прошлый раз ({{ $adv->lastDate->format('d.m') }}): <span class="text-zinc-100">{{ $adv->lastSetsLabel() }}</span></div>
                            <div class="mt-1 {{ $adv->action === 'increase' ? 'text-lime-300' : 'text-zinc-300' }}">{{ $adv->message }}</div>
                            @if ($adv->stagnating)
                                <div class="mt-1 text-amber-300">⚠ {{ $adv->stagnationMessage }}</div>
                            @endif
                        </div>
                    @else
                        <div class="rounded-xl bg-zinc-950 p-3 text-sm text-zinc-400">Первое выполнение — подберите вес, с которым получится {{ $we->exercise->rep_min }}–{{ $we->exercise->rep_max }} повторений с запасом 1–2.</div>
                    @endif

                    @if ($we->sets->isNotEmpty())
                        <ol class="space-y-1.5">
                            @foreach ($we->sets as $set)
                                <li wire:key="set-{{ $set->id }}" class="flex items-center gap-2">
                                    <span class="w-6 text-center text-sm text-zinc-500">{{ $loop->iteration }}</span>
                                    <input type="number" inputmode="decimal" step="0.25" min="0" value="{{ format_number_input($set->weight_kg) }}"
                                           wire:change="updateSet({{ $set->id }}, 'weight_kg', $event.target.value)" @disabled(! $editable)
                                           class="input h-11 w-24 text-center" aria-label="Вес подхода {{ $loop->iteration }}">
                                    <span class="text-zinc-500">кг ×</span>
                                    <input type="number" inputmode="numeric" min="1" value="{{ $set->reps }}"
                                           wire:change="updateSet({{ $set->id }}, 'reps', $event.target.value)" @disabled(! $editable)
                                           class="input h-11 w-16 text-center" aria-label="Повторения подхода {{ $loop->iteration }}">
                                    @if ($editable)
                                        <button type="button" wire:click="deleteSet({{ $set->id }})" wire:confirm="Удалить подход?" class="btn-ghost btn-sm ml-auto !px-2 text-zinc-500" aria-label="Удалить подход {{ $loop->iteration }}">✕</button>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    @endif

                    @if ($editable)
                        <form wire:submit="addSet" class="space-y-2">
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="label" for="weight-{{ $we->id }}">Вес, кг</label>
                                    <div class="flex items-center gap-1">
                                        <button type="button" class="btn-secondary !w-11 !px-0" aria-label="Минус {{ format_kg($step) }} кг"
                                                @click="$wire.weight = Math.max(0, (parseFloat(String($wire.weight).replace(',', '.')) || 0) - {{ $step }}).toString()">−</button>
                                        <input id="weight-{{ $we->id }}" type="number" inputmode="decimal" step="0.25" min="0" wire:model="weight" class="input h-12 min-w-0 flex-1 text-center text-lg font-semibold">
                                        <button type="button" class="btn-secondary !w-11 !px-0" aria-label="Плюс {{ format_kg($step) }} кг"
                                                @click="$wire.weight = ((parseFloat(String($wire.weight).replace(',', '.')) || 0) + {{ $step }}).toString()">+</button>
                                    </div>
                                </div>
                                <div>
                                    <label class="label" for="reps-{{ $we->id }}">Повторения</label>
                                    <div class="flex items-center gap-1">
                                        <button type="button" class="btn-secondary !w-11 !px-0" aria-label="Меньше повторений"
                                                @click="$wire.reps = Math.max(1, (parseInt($wire.reps) || 1) - 1).toString()">−</button>
                                        <input id="reps-{{ $we->id }}" type="number" inputmode="numeric" min="1" wire:model="reps" class="input h-12 min-w-0 flex-1 text-center text-lg font-semibold">
                                        <button type="button" class="btn-secondary !w-11 !px-0" aria-label="Больше повторений"
                                                @click="$wire.reps = ((parseInt($wire.reps) || 0) + 1).toString()">+</button>
                                    </div>
                                </div>
                            </div>
                            <x-input-error :messages="$errors->get('weight')" />
                            <x-input-error :messages="$errors->get('reps')" />
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" wire:click="copyLast" class="btn-secondary" @disabled($we->sets->isEmpty())>⧉ Повтор</button>
                                <button type="submit" class="btn-primary" wire:loading.attr="disabled">✓ Записать</button>
                            </div>
                        </form>
                    @endif
                </div>
            @endif
        </section>
    @endforeach
</div>
