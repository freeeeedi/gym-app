@php
    $muscleNames = config('training.muscles');
    $min = $settings->int('weekly_sets_min');
    $max = $settings->int('weekly_sets_max');
    $evaluated = $volume['evaluated_week'];
@endphp
<x-app-layout>
    <x-slot name="title">Прогресс</x-slot>
    <x-slot name="header">
        <h1 class="flex-1 font-semibold">Прогресс</h1>
    </x-slot>

    <div class="space-y-5">
        <section class="card space-y-3">
            <h2 class="h2">Подходы на группу мышц по неделям</h2>
            <p class="text-xs text-zinc-500">Рабочие подходы; вспомогательная нагрузка считается как половина. Цель: {{ $min }}–{{ $max }} в неделю.</p>
            @if ($volume['undertrained'])
                <div class="rounded-xl border border-amber-900 bg-amber-950/40 p-3 text-sm text-amber-200">
                    Недогружены на неделе с {{ $volume['weeks'][$evaluated]['label'] }}:
                    {{ collect($volume['undertrained'])->map(fn ($m) => $muscleNames[$m])->implode(', ') }}
                </div>
            @endif
            @if ($volume['overtrained'])
                <div class="rounded-xl border border-red-900 bg-red-950/40 p-3 text-sm text-red-200">
                    Выше {{ $max }} подходов: {{ collect($volume['overtrained'])->map(fn ($m) => $muscleNames[$m])->implode(', ') }}
                </div>
            @endif
            <div class="-mx-1 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs text-zinc-500">
                            <th class="px-1 py-1 text-left font-normal">Неделя с</th>
                            @foreach ($volume['weeks'] as $i => $week)
                                <th class="px-1 py-1 text-right font-normal {{ $i === $evaluated ? 'text-zinc-200' : '' }}">{{ $week['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($volume['sets'] as $muscle => $weeks)
                            <tr class="border-t border-zinc-800">
                                <td class="px-1 py-1.5 text-zinc-300">{{ $muscleNames[$muscle] }}</td>
                                @foreach ($weeks as $i => $value)
                                    <td class="px-1 py-1.5 text-right tabular-nums {{ $value == 0 ? 'text-zinc-600' : ($value < $min ? 'text-amber-300' : ($value > $max ? 'text-red-400' : 'text-lime-300')) }}">{{ format_kg($value) }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card">
            <h2 class="h2 mb-1">Личные рекорды</h2>
            <p class="mb-2 text-xs text-zinc-500">1ПМ — расчёт по формуле Эпли, ориентир, а не измерение.</p>
            @forelse ($records as $r)
                <a href="{{ route('exercises.show', $r['exercise']) }}" class="flex items-center gap-3 border-b border-zinc-800 py-2 last:border-0">
                    <x-exercise-icon :icon="$r['exercise']->icon" class="h-8 w-8 text-lime-400" />
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-medium">{{ $r['exercise']->name }}</div>
                        <div class="text-xs text-zinc-400">{{ format_kg($r['max_weight']) }} кг × {{ $r['max_weight_reps'] }} · 1ПМ ≈ {{ format_kg($r['best_e1rm']) }} кг</div>
                    </div>
                    <span class="text-zinc-500">›</span>
                </a>
            @empty
                <p class="muted">Рекорды появятся после первой завершённой тренировки.</p>
            @endforelse
        </section>

        <section class="card">
            <h2 class="h2 mb-2">История тренировок</h2>
            @forelse ($recent as $w)
                <a href="{{ route('workouts.summary', $w) }}" class="flex items-center gap-3 border-b border-zinc-800 py-2 last:border-0">
                    <span class="w-14 text-sm text-zinc-400">{{ $w->scheduled_on->format('d.m') }}</span>
                    <span class="min-w-0 flex-1 truncate text-sm">{{ $w->title }}</span>
                    <span class="text-xs text-zinc-500">{{ $w->workoutExercises->count() }} упр.</span>
                </a>
            @empty
                <p class="muted">Завершённых тренировок пока нет.</p>
            @endforelse
        </section>
    </div>
</x-app-layout>
