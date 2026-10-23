<x-app-layout>
    <x-slot name="title">Итоги</x-slot>
    <x-slot name="header">
        <a href="{{ route('dashboard') }}" class="btn-ghost btn-sm !px-2" aria-label="На главную">←</a>
        <div class="min-w-0 flex-1">
            <h1 class="truncate font-semibold">Итоги: {{ $workout->title }}</h1>
            <div class="text-sm text-zinc-400">{{ $workout->scheduled_on->translatedFormat('l, j F') }}</div>
        </div>
    </x-slot>

    <div class="space-y-4">
        <div class="grid grid-cols-2 gap-2">
            <div class="card">
                <div class="muted">Длительность</div>
                <div class="text-2xl font-bold">{{ $summary['duration'] ?? '—' }} <span class="text-base font-normal text-zinc-400">мин</span></div>
            </div>
            <div class="card">
                <div class="muted">Объём</div>
                <div class="text-2xl font-bold">{{ number_format($summary['volume'], 0, ',', ' ') }} <span class="text-base font-normal text-zinc-400">кг</span></div>
            </div>
            <div class="card">
                <div class="muted">Рабочих подходов</div>
                <div class="text-2xl font-bold">{{ $summary['sets'] }}</div>
            </div>
            <div class="card">
                <div class="muted">Упражнений</div>
                <div class="text-2xl font-bold">{{ $summary['exercises'] }}</div>
            </div>
        </div>

        <section class="card">
            <h2 class="h2 mb-2">🏆 Новые рекорды</h2>
            @forelse ($summary['records'] as $record)
                <div class="flex items-baseline justify-between border-b border-zinc-800 py-2 last:border-0">
                    <div>
                        <div class="font-medium">{{ $record['exercise']->name }}</div>
                        <div class="text-xs text-zinc-400">{{ $record['type'] }}, было {{ format_kg($record['previous']) }} кг</div>
                    </div>
                    <div class="text-lg font-bold text-lime-400">{{ format_kg($record['value']) }} кг</div>
                </div>
            @empty
                <p class="muted">В этот раз без рекордов. Стабильная работа — тоже прогресс.</p>
            @endforelse
        </section>

        <section class="card">
            <h2 class="h2 mb-2">Выполнено</h2>
            @foreach ($workout->workoutExercises as $we)
                <div class="border-b border-zinc-800 py-2 last:border-0">
                    <a href="{{ route('exercises.show', $we->exercise) }}" class="font-medium">{{ $we->exercise->name }}</a>
                    <div class="text-sm text-zinc-400">
                        {{ $we->sets->map(fn ($s) => format_kg($s->weight_kg).'×'.$s->reps)->implode(', ') }}
                    </div>
                </div>
            @endforeach
        </section>

        <div class="grid grid-cols-2 gap-2">
            <a href="{{ route('workouts.session', $workout) }}" class="btn-secondary">Подходы</a>
            <a href="{{ route('analytics') }}" class="btn-primary">Прогресс</a>
        </div>
    </div>
</x-app-layout>
