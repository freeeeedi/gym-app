@php
    $muscleNames = config('training.muscles');
    $current = count($volume['weeks']) - 1;
    $min = $settings->int('weekly_sets_min');
    $max = $settings->int('weekly_sets_max');
@endphp
<x-app-layout>
    <x-slot name="title">Сегодня</x-slot>

    <div class="space-y-5">
        <div>
            <div class="muted capitalize">{{ $today->translatedFormat('l, j F') }}</div>
            <h1 class="h1">Привет, {{ auth()->user()->name }}!</h1>
        </div>

        @if ($active)
            <a href="{{ route('workouts.session', $active) }}" class="card flex items-center gap-3 !border-amber-400/50">
                <span class="text-2xl">⏱</span>
                <div class="flex-1">
                    <div class="font-semibold">{{ $active->title }} — идёт</div>
                    <div class="muted">Нажмите, чтобы продолжить</div>
                </div>
                <span class="text-amber-300">›</span>
            </a>
        @endif

        <section class="space-y-2">
            <h2 class="h2">Тренировка на сегодня</h2>
            @forelse ($todayWorkouts as $w)
                <a href="{{ route($w->isInProgress() ? 'workouts.session' : ($w->isCompleted() ? 'workouts.summary' : 'workouts.show'), $w) }}" class="card flex items-center gap-3">
                    <div class="min-w-0 flex-1">
                        <div class="truncate font-semibold">{{ $w->title }}</div>
                        <div class="muted">{{ $w->workout_exercises_count }} упр.</div>
                    </div>
                    <x-status-badge :status="$w->status" />
                </a>
            @empty
                <div class="card space-y-3">
                    <p class="muted">На сегодня ничего не запланировано. Выберите тип дня — упражнения подскажем.</p>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach ($templates as $template)
                            <form method="POST" action="{{ route('workouts.store') }}">
                                @csrf
                                <input type="hidden" name="scheduled_on" value="{{ $today->toDateString() }}">
                                <input type="hidden" name="day_template_id" value="{{ $template->id }}">
                                <button class="btn-secondary w-full !text-sm">{{ $template->name }}</button>
                            </form>
                        @endforeach
                    </div>
                </div>
            @endforelse
        </section>

        @if ($upcoming->isNotEmpty())
            <section class="space-y-2">
                <h2 class="h2">Ближайшие</h2>
                @foreach ($upcoming as $w)
                    <a href="{{ route('workouts.show', $w) }}" class="card flex items-center gap-3 !p-3">
                        <div class="w-14 text-center">
                            <div class="text-xs uppercase text-zinc-500">{{ $w->scheduled_on->translatedFormat('D') }}</div>
                            <div class="font-bold">{{ $w->scheduled_on->format('d.m') }}</div>
                        </div>
                        <div class="min-w-0 flex-1 truncate">{{ $w->title }}</div>
                        <span class="text-zinc-500">›</span>
                    </a>
                @endforeach
            </section>
        @endif

        <section class="card space-y-3">
            <div class="flex items-baseline justify-between">
                <h2 class="h2">Объём этой недели</h2>
                <span class="text-xs text-zinc-500">цель {{ $min }}–{{ $max }} подх.</span>
            </div>
            @foreach ($volume['sets'] as $muscle => $weeks)
                @php($value = $weeks[$current])
                <div class="flex items-center gap-2 text-sm">
                    <span class="w-28 shrink-0 truncate text-zinc-300">{{ $muscleNames[$muscle] }}</span>
                    <div class="h-2 flex-1 overflow-hidden rounded-full bg-zinc-800">
                        <div class="h-full rounded-full {{ $value > $max ? 'bg-red-500' : ($value >= $min ? 'bg-lime-400' : 'bg-amber-400') }}" style="width: {{ min(100, $max ? $value / $max * 100 : 0) }}%"></div>
                    </div>
                    <span class="w-8 text-right tabular-nums text-zinc-400">{{ format_kg($value) }}</span>
                </div>
            @endforeach
            <a href="{{ route('analytics') }}" class="block text-sm text-lime-400">Подробнее о прогрессе ›</a>
        </section>

        @if ($lastCompleted)
            <a href="{{ route('workouts.summary', $lastCompleted) }}" class="block text-center text-sm text-zinc-400">
                Последняя тренировка: {{ $lastCompleted->title }}, {{ $lastCompleted->scheduled_on->translatedFormat('j F') }} ›
            </a>
        @endif
    </div>
</x-app-layout>
