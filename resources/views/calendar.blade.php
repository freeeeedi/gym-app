@php
    $selectedKey = $selected->toDateString();
    $dayWorkouts = $workouts->get($selectedKey, collect());
    $dot = fn ($w) => match ($w->status->value) { 'completed' => 'bg-lime-400', 'in_progress' => 'bg-amber-400', default => 'bg-zinc-400' };
@endphp
<x-app-layout>
    <x-slot name="title">Календарь</x-slot>
    <x-slot name="header">
        <a href="{{ route('calendar', ['view' => $view, 'date' => $prev->toDateString()]) }}" class="btn-ghost btn-sm !px-3" aria-label="Назад">‹</a>
        <h1 class="flex-1 text-center font-semibold capitalize">{{ $title }}</h1>
        <a href="{{ route('calendar', ['view' => $view, 'date' => $next->toDateString()]) }}" class="btn-ghost btn-sm !px-3" aria-label="Вперёд">›</a>
    </x-slot>

    <div class="mb-3 flex items-center gap-2">
        <div class="grid flex-1 grid-cols-2 gap-1 rounded-xl bg-zinc-900 p-1">
            <a href="{{ route('calendar', ['view' => 'month', 'date' => $selectedKey]) }}" class="rounded-lg py-2 text-center text-sm font-semibold {{ $view === 'month' ? 'bg-zinc-700' : 'text-zinc-400' }}">Месяц</a>
            <a href="{{ route('calendar', ['view' => 'week', 'date' => $selectedKey]) }}" class="rounded-lg py-2 text-center text-sm font-semibold {{ $view === 'week' ? 'bg-zinc-700' : 'text-zinc-400' }}">Неделя</a>
        </div>
        <a href="{{ route('calendar', ['view' => $view]) }}" class="btn-secondary btn-sm">Сегодня</a>
    </div>

    @if ($view === 'month')
        <div class="grid grid-cols-7 gap-1 text-center">
            @foreach (['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'] as $wd)
                <div class="pb-1 text-xs text-zinc-500">{{ $wd }}</div>
            @endforeach
            @foreach ($days as $day)
                @php($key = $day->toDateString())
                @php($items = $workouts->get($key, collect()))
                <a href="{{ route('calendar', ['view' => 'month', 'date' => $key]) }}"
                   class="flex aspect-square flex-col items-center justify-center rounded-xl text-sm
                          {{ $key === $selectedKey ? 'bg-lime-400 font-bold text-zinc-950' : ($day->isToday() ? 'border border-lime-400/60' : 'bg-zinc-900') }}
                          {{ $day->month !== $selected->month ? 'opacity-40' : '' }}"
                   aria-label="{{ $day->translatedFormat('j F') }}{{ $items->isNotEmpty() ? ', тренировок: '.$items->count() : '' }}">
                    {{ $day->day }}
                    <span class="mt-0.5 flex h-1.5 gap-0.5">
                        @foreach ($items->take(3) as $w)
                            <span class="h-1.5 w-1.5 rounded-full {{ $key === $selectedKey ? 'bg-zinc-950' : $dot($w) }}"></span>
                        @endforeach
                    </span>
                </a>
            @endforeach
        </div>
    @else
        <div class="space-y-2">
            @foreach ($days as $day)
                @php($key = $day->toDateString())
                <a href="{{ route('calendar', ['view' => 'week', 'date' => $key]) }}"
                   class="card flex items-center gap-3 !p-3 {{ $key === $selectedKey ? '!border-lime-400' : '' }}">
                    <div class="w-12 text-center">
                        <div class="text-xs uppercase text-zinc-500">{{ $day->translatedFormat('D') }}</div>
                        <div class="text-xl font-bold {{ $day->isToday() ? 'text-lime-400' : '' }}">{{ $day->day }}</div>
                    </div>
                    <div class="min-w-0 flex-1 space-y-1">
                        @forelse ($workouts->get($key, collect()) as $w)
                            <div class="flex items-center gap-2 text-sm">
                                <span class="h-2 w-2 shrink-0 rounded-full {{ $dot($w) }}"></span>
                                <span class="truncate">{{ $w->title }}</span>
                            </div>
                        @empty
                            <span class="text-sm text-zinc-600">Отдых</span>
                        @endforelse
                    </div>
                </a>
            @endforeach
        </div>
    @endif

    <section class="mt-5 space-y-3">
        <h2 class="h2 capitalize">{{ $selected->translatedFormat('l, j F') }}</h2>

        @foreach ($dayWorkouts as $w)
            <a href="{{ route('workouts.show', $w) }}" class="card flex items-center gap-3 !p-3">
                <span class="h-2.5 w-2.5 rounded-full {{ $dot($w) }}"></span>
                <div class="min-w-0 flex-1">
                    <div class="truncate font-medium">{{ $w->title }}</div>
                    <div class="text-xs text-zinc-400">{{ $w->workout_exercises_count }} упр.</div>
                </div>
                <x-status-badge :status="$w->status" />
            </a>
        @endforeach

        <form method="POST" action="{{ route('workouts.store') }}" class="card space-y-3">
            @csrf
            <div class="font-medium">Новая тренировка</div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="label" for="new-date">Дата</label>
                    <input id="new-date" type="date" name="scheduled_on" value="{{ $selectedKey }}" class="input min-h-[3rem]" required>
                </div>
                <div>
                    <label class="label" for="new-template">Тип дня</label>
                    <select id="new-template" name="day_template_id" class="input min-h-[3rem]">
                        @foreach ($templates as $template)
                            <option value="{{ $template->id }}">{{ $template->name }}</option>
                        @endforeach
                        <option value="">Без шаблона</option>
                    </select>
                </div>
            </div>
            <button class="btn-primary w-full">+ Создать</button>
        </form>
    </section>
</x-app-layout>
