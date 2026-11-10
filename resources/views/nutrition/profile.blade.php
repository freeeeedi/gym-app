@php
    $selected = old('restrictions', $profile->restrictions);
@endphp
<x-app-layout>
    <x-slot name="title">Профиль питания</x-slot>
    <x-slot name="header">
        <h1 class="flex-1 font-semibold">Питание</h1>
    </x-slot>

    <x-nutrition-tabs />

    <div class="space-y-5">
        @if ($targets)
            <section class="card space-y-3" aria-label="Суточная норма">
                <div class="flex items-baseline justify-between">
                    <h2 class="h2">Ваша норма на день</h2>
                    <span class="text-xs text-zinc-500">{{ $profile->goalLabel() }}</span>
                </div>
                <div class="grid grid-cols-4 gap-2 text-center">
                    <div class="rounded-xl bg-zinc-800 p-2">
                        <div class="text-lg font-bold text-lime-300">{{ format_int($targets->kcal) }}</div>
                        <div class="text-xs text-zinc-400">ккал</div>
                    </div>
                    <div class="rounded-xl bg-zinc-800 p-2">
                        <div class="text-lg font-bold">{{ $targets->protein }}</div>
                        <div class="text-xs text-zinc-400">белки, г</div>
                    </div>
                    <div class="rounded-xl bg-zinc-800 p-2">
                        <div class="text-lg font-bold">{{ $targets->fat }}</div>
                        <div class="text-xs text-zinc-400">жиры, г</div>
                    </div>
                    <div class="rounded-xl bg-zinc-800 p-2">
                        <div class="text-lg font-bold">{{ $targets->carbs }}</div>
                        <div class="text-xs text-zinc-400">углев., г</div>
                    </div>
                </div>
                <dl class="space-y-1 text-sm">
                    <div class="flex justify-between gap-2">
                        <dt class="text-zinc-400">ИМТ</dt>
                        <dd><span class="font-semibold">{{ format_kg($bmi) }}</span> — {{ mb_strtolower($bmiCategory['label']) }}</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-zinc-400">Вес при нормальном ИМТ</dt>
                        <dd>{{ format_kg($normalWeight[0]) }}–{{ format_kg($normalWeight[1]) }} кг</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-zinc-400">Базовый обмен</dt>
                        <dd>{{ format_int($targets->bmr) }} ккал</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-zinc-400">Суточный расход</dt>
                        <dd>{{ format_int($targets->tdee) }} ккал</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-zinc-400">Вода</dt>
                        <dd>{{ format_int($targets->waterMl) }} мл</dd>
                    </div>
                </dl>
                @foreach ($targets->notes as $note)
                    <p class="text-xs text-zinc-400">{{ $note }}</p>
                @endforeach
                <p class="text-xs text-zinc-500">Расчёт по формуле Миффлина — Сан Жеора с коэффициентом активности. Это ориентир, а не назначение врача: если вес две-три недели идёт не туда, меняйте цель или активность.</p>
            </section>
        @else
            <div class="rounded-xl border border-amber-900 bg-amber-950/40 p-3 text-sm text-amber-200">
                Заполните анкету — рассчитаем ИМТ, суточную норму калорий, белков, жиров и углеводов и подберём продукты.
            </div>
        @endif

        <form method="POST" action="{{ route('nutrition.profile.update') }}" class="space-y-5">
            @csrf @method('PUT')

            <section class="card space-y-3">
                <h2 class="h2">О себе</h2>
                <fieldset>
                    <legend class="label">Пол</legend>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach (config('nutrition.sexes') as $key => $label)
                            <label class="chip min-h-[3rem] cursor-pointer justify-center has-[:checked]:chip-active">
                                <input type="radio" name="sex" value="{{ $key }}" class="sr-only" @checked(old('sex', $profile->sex) === $key) required>{{ $label }}
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('sex')" />
                </fieldset>
                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="label" for="age">Возраст, лет</label>
                        <input id="age" type="number" inputmode="numeric" name="age" min="14" max="100" value="{{ old('age', $profile->age) }}" class="input min-h-[3rem]" required>
                    </div>
                    <div>
                        <label class="label" for="height_cm">Рост, см</label>
                        <input id="height_cm" type="text" inputmode="decimal" name="height_cm" value="{{ old('height_cm', $profile->heightCm ? format_number_input($profile->heightCm) : '') }}" class="input min-h-[3rem]" required>
                    </div>
                    <div>
                        <label class="label" for="weight_kg">Вес, кг</label>
                        <input id="weight_kg" type="text" inputmode="decimal" name="weight_kg" value="{{ old('weight_kg', $profile->weightKg ? format_number_input($profile->weightKg) : '') }}" class="input min-h-[3rem]" required>
                    </div>
                </div>
                <x-input-error :messages="$errors->get('age')" />
                <x-input-error :messages="$errors->get('height_cm')" />
                <x-input-error :messages="$errors->get('weight_kg')" />
                <p class="text-xs text-zinc-500">Вес сохраняется в журнал веса — его динамика видна на вкладке «Статистика».</p>
            </section>

            <section class="card space-y-3">
                <h2 class="h2">Активность</h2>
                <div class="space-y-2">
                    @foreach (config('nutrition.activity_levels') as $key => $level)
                        <label class="flex min-h-[3rem] cursor-pointer items-center gap-3 rounded-xl border border-zinc-700 px-3 py-2 has-[:checked]:border-lime-400 has-[:checked]:bg-lime-400/10">
                            <input type="radio" name="activity" value="{{ $key }}" class="h-5 w-5 border-zinc-600 bg-zinc-950 text-lime-400 focus:ring-lime-400" @checked(old('activity', $profile->activity) === $key) required>
                            <span>
                                <span class="block text-sm font-medium">{{ $level['label'] }}</span>
                                <span class="block text-xs text-zinc-400">{{ $level['hint'] }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('activity')" />
            </section>

            <section class="card space-y-3">
                <h2 class="h2">Цель</h2>
                <div class="grid grid-cols-3 gap-2">
                    @foreach (config('nutrition.goals') as $key => $goal)
                        <label class="chip min-h-[3rem] cursor-pointer justify-center text-center has-[:checked]:chip-active">
                            <input type="radio" name="goal" value="{{ $key }}" class="sr-only" @checked(old('goal', $profile->goal) === $key) required>{{ $goal['label'] }}
                        </label>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('goal')" />
                <p class="text-xs text-zinc-500">Набор массы: +10% к расходу калорий. Сушка: −15% и больше белка, чтобы сохранить мышцы.</p>
            </section>

            <section class="card space-y-3">
                <h2 class="h2">Ограничения и аллергии</h2>
                <div class="flex flex-wrap gap-2">
                    @foreach (config('nutrition.restrictions') as $key => $restriction)
                        <label class="chip cursor-pointer has-[:checked]:chip-active">
                            <input type="checkbox" name="restrictions[]" value="{{ $key }}" class="sr-only" @checked(in_array($key, $selected))>{{ $restriction['label'] }}
                        </label>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('restrictions')" />
                <p class="text-xs text-zinc-500">Продукты и блюда с отмеченными компонентами не попадут в рекомендации. В дневник записать можно что угодно.</p>
            </section>

            <button class="btn-primary w-full">Сохранить и рассчитать</button>
        </form>
    </div>
</x-app-layout>
