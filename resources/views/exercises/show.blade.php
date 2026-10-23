@php($muscleNames = config('training.muscles'))
<x-app-layout>
    <x-slot name="title">{{ $exercise->name }}</x-slot>
    <x-slot name="header">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('exercises.index') }}" class="btn-ghost btn-sm !px-2" aria-label="Назад">←</a>
        <h1 class="min-w-0 flex-1 truncate font-semibold">{{ $exercise->name }}</h1>
    </x-slot>

    <div class="space-y-4">
        <section class="card flex gap-3">
            <x-exercise-icon :icon="$exercise->icon" class="h-14 w-14 rounded-2xl bg-zinc-800 p-2 text-lime-400" />
            <div class="min-w-0 space-y-1 text-sm">
                <div><span class="text-zinc-400">Основные:</span> {{ collect($exercise->primaryMuscles())->map(fn ($m) => $muscleNames[$m])->implode(', ') }}</div>
                @if ($exercise->secondaryMuscles())
                    <div><span class="text-zinc-400">Вспомогательные:</span> {{ collect($exercise->secondaryMuscles())->map(fn ($m) => $muscleNames[$m])->implode(', ') }}</div>
                @endif
                <div class="text-zinc-400">{{ $exercise->patternLabel() }} · {{ $exercise->equipmentLabel() }} · {{ $exercise->mechanicLabel() }} · {{ $exercise->rep_min }}–{{ $exercise->rep_max }} повт. · утомляемость {{ $exercise->fatigue }}/5</div>
                @if ($exercise->description)
                    <div class="text-zinc-300">{{ $exercise->description }}</div>
                @endif
            </div>
        </section>

        @if ($advice)
            <section class="card space-y-1 text-sm">
                <h2 class="h2">Что дальше</h2>
                <div class="text-zinc-400">Последний раз {{ $advice->lastDate->format('d.m') }}: {{ $advice->lastSetsLabel() }}</div>
                <div class="{{ $advice->action === 'increase' ? 'text-lime-300' : 'text-zinc-200' }}">{{ $advice->message }}</div>
                @if ($advice->stagnating)
                    <div class="text-amber-300">⚠ {{ $advice->stagnationMessage }}</div>
                @endif
            </section>
        @endif

        @if ($record)
            <section class="grid grid-cols-3 gap-2 text-center">
                <div class="card !p-3">
                    <div class="text-xs text-zinc-400">Макс. вес</div>
                    <div class="text-lg font-bold">{{ format_kg($record['max_weight']) }}</div>
                    <div class="text-xs text-zinc-500">× {{ $record['max_weight_reps'] }} · {{ $record['max_weight_date']->format('d.m') }}</div>
                </div>
                <div class="card !p-3">
                    <div class="text-xs text-zinc-400">1ПМ (расч.)</div>
                    <div class="text-lg font-bold">{{ format_kg($record['best_e1rm']) }}</div>
                    <div class="text-xs text-zinc-500">{{ $record['best_e1rm_date']->format('d.m') }}</div>
                </div>
                <div class="card !p-3">
                    <div class="text-xs text-zinc-400">Объём</div>
                    <div class="text-lg font-bold">{{ number_format($record['best_volume'], 0, ',', ' ') }}</div>
                    <div class="text-xs text-zinc-500">за тренировку</div>
                </div>
            </section>
        @endif

        @if ($sessions->count() >= 1)
            <section class="card space-y-4">
                <div>
                    <h2 class="h2">Рабочий вес, кг</h2>
                    <div class="relative h-48"><canvas id="chart-weight" aria-label="График рабочего веса"></canvas></div>
                </div>
                <div>
                    <h2 class="h2">Расчётный 1ПМ, кг</h2>
                    <p class="text-xs text-zinc-500">Формула Эпли: вес × (1 + повт./30). Ориентир, а не точный максимум: погрешность растёт после 10 повторений.</p>
                    <div class="relative h-48"><canvas id="chart-e1rm" aria-label="График расчётного максимума"></canvas></div>
                </div>
                <div>
                    <h2 class="h2">Объём за тренировку (вес × повт. × подходы)</h2>
                    <div class="relative h-48"><canvas id="chart-volume" aria-label="График объёма"></canvas></div>
                </div>
            </section>

            <section class="card">
                <h2 class="h2 mb-2">История</h2>
                @foreach ($sessions->reverse() as $s)
                    <div class="flex justify-between gap-2 border-b border-zinc-800 py-2 text-sm last:border-0">
                        <span class="text-zinc-400">{{ \Illuminate\Support\Carbon::parse($s['date'])->format('d.m.y') }}</span>
                        <span class="text-right">{{ $s['sets'] }}</span>
                    </div>
                @endforeach
            </section>

            @push('scripts')
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const sessions = @json($sessions);
                        const labels = sessions.map(s => s.date.slice(8, 10) + '.' + s.date.slice(5, 7));
                        const opts = {
                            responsive: true, maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                x: { ticks: { color: '#a1a1aa' }, grid: { color: '#27272a' } },
                                y: { ticks: { color: '#a1a1aa' }, grid: { color: '#27272a' } },
                            },
                        };
                        const line = (id, key, color) => new window.Chart(document.getElementById(id), {
                            type: 'line',
                            data: { labels, datasets: [{ data: sessions.map(s => s[key]), borderColor: color, backgroundColor: color, tension: .25, pointRadius: 3 }] },
                            options: opts,
                        });
                        line('chart-weight', 'top_weight', '#a3e635');
                        line('chart-e1rm', 'e1rm', '#facc15');
                        new window.Chart(document.getElementById('chart-volume'), {
                            type: 'bar',
                            data: { labels, datasets: [{ data: sessions.map(s => s.volume), backgroundColor: '#38bdf8' }] },
                            options: opts,
                        });
                    });
                </script>
            @endpush
        @else
            <div class="card text-center text-zinc-400">Упражнение ещё не выполнялось — графики появятся после первой тренировки.</div>
        @endif

        @can('delete', $exercise)
            <form method="POST" action="{{ route('exercises.destroy', $exercise) }}" onsubmit="return confirm('Удалить упражнение из каталога?')">
                @csrf @method('DELETE')
                <button class="btn-danger w-full">Удалить своё упражнение</button>
            </form>
        @endcan
    </div>
</x-app-layout>
