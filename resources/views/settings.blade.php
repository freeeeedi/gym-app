@php
    $fields = [
        'Время и подходы' => [
            ['time_budget_minutes', 'Бюджет времени на тренировку, мин', 'Подсказки не выводят прогноз за этот предел (разминка учитывается).'],
            ['sets_compound', 'Подходов в базовых упражнениях', null],
            ['sets_isolation', 'Подходов в изолирующих', null],
            ['rest_compound_seconds', 'Отдых в базовых, сек', 'Используется в таймере отдыха и прогнозе времени.'],
            ['rest_isolation_seconds', 'Отдых в изолирующих, сек', null],
        ],
        'Объём' => [
            ['weekly_sets_min', 'Минимум рабочих подходов на группу в неделю', 'Ниже — группа считается недогруженной.'],
            ['weekly_sets_max', 'Максимум подходов на группу в неделю', 'Выше — упражнения на группу не предлагаются.'],
            ['exercises_per_muscle_min', 'Упражнений на мышцу за тренировку: минимум', null],
            ['exercises_per_muscle_max', 'Упражнений на мышцу за тренировку: максимум', null],
        ],
        'Ротация и прогрессия' => [
            ['rotation_window', 'Окно ротации: последних тренировок на мышцу', 'Упражнения из этих тренировок не предлагаются. 0 — ротация выключена.'],
            ['stagnation_sessions', 'Стагнация: тренировок подряд без роста', 'После этого предлагается разгрузка.'],
        ],
    ];
    $toggles = [
        ['rotation_fallback', 'Если все варианты паттерна недавно делались — всё равно предложить самый давний'],
        ['include_bodyweight', 'Предлагать упражнения с собственным весом'],
        ['count_secondary_as_half', 'Считать вспомогательную нагрузку как половину подхода'],
    ];
@endphp
<x-app-layout>
    <x-slot name="title">Настройки</x-slot>
    <x-slot name="header">
        <a href="{{ route('more') }}" class="btn-ghost btn-sm !px-2" aria-label="Назад">←</a>
        <h1 class="flex-1 font-semibold">Настройки тренировок</h1>
    </x-slot>

    <form method="POST" action="{{ route('settings.update') }}" class="space-y-5">
        @csrf @method('PUT')
        @foreach ($fields as $section => $items)
            <section class="card space-y-3">
                <h2 class="h2">{{ $section }}</h2>
                @foreach ($items as [$key, $label, $hint])
                    <div>
                        <label class="label" for="{{ $key }}">{{ $label }}</label>
                        <input id="{{ $key }}" type="number" inputmode="numeric" name="{{ $key }}" value="{{ old($key, $settings->get($key)) }}" class="input min-h-[3rem]" required>
                        @if ($hint)<p class="mt-1 text-xs text-zinc-500">{{ $hint }}</p>@endif
                        <x-input-error :messages="$errors->get($key)" />
                    </div>
                @endforeach
            </section>
        @endforeach
        <section class="card space-y-3">
            <h2 class="h2">Спорные правила</h2>
            @foreach ($toggles as [$key, $label])
                <label class="flex items-start gap-3">
                    <input type="checkbox" name="{{ $key }}" value="1" class="mt-1 h-5 w-5 rounded border-zinc-600 bg-zinc-950 text-lime-400 focus:ring-lime-400" @checked(old($key, $settings->get($key)))>
                    <span class="text-sm">{{ $label }}</span>
                </label>
            @endforeach
        </section>
        <button class="btn-primary w-full">Сохранить</button>
    </form>
    <form method="POST" action="{{ route('settings.reset') }}" class="mt-3">
        @csrf @method('DELETE')
        <button class="btn-ghost w-full text-sm">Сбросить к значениям по умолчанию</button>
    </form>
</x-app-layout>
