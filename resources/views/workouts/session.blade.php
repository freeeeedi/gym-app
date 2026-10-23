<x-app-layout>
    <x-slot name="title">{{ $workout->title }}</x-slot>
    <x-slot name="header">
        <a href="{{ route('workouts.show', $workout) }}" class="btn-ghost btn-sm !px-2" aria-label="К плану">←</a>
        <div class="min-w-0 flex-1">
            <div class="truncate font-semibold">{{ $workout->title }}</div>
            @if ($workout->started_at && ! $workout->isCompleted())
                <div class="text-sm text-lime-400" x-data="workoutClock({{ $workout->started_at->timestamp }})">
                    ⏱ <span x-text="label" aria-label="Время тренировки"></span>
                </div>
            @elseif ($workout->isCompleted())
                <div class="text-sm text-zinc-400">Завершена · {{ $workout->durationMinutes() }} мин</div>
            @endif
        </div>
        @unless ($workout->isCompleted())
            <form method="POST" action="{{ route('workouts.finish', $workout) }}" onsubmit="return confirm('Завершить тренировку? Упражнения без подходов будут убраны из истории.')">
                @csrf
                <button class="btn-primary btn-sm">Завершить</button>
            </form>
        @endunless
    </x-slot>

    <livewire:workout-session :workout="$workout" />

    <div class="mt-4 flex gap-2">
        <a href="{{ route('workouts.show', $workout) }}" class="btn-secondary flex-1">Изменить план</a>
    </div>

    {{-- Таймер отдыха --}}
    <div x-data="restTimer('rest-{{ $workout->id }}')" @set-logged.window="start($event.detail.seconds)"
         x-show="active" x-cloak x-transition
         class="fixed inset-x-0 bottom-[calc(4.25rem+env(safe-area-inset-bottom))] z-40 px-4" role="timer" aria-live="polite">
        <div class="mx-auto max-w-xl overflow-hidden rounded-2xl border border-lime-400/40 bg-zinc-900 shadow-2xl">
            <div class="h-1 bg-lime-400 transition-all" :style="`width: ${progress}%`"></div>
            <div class="flex items-center gap-3 p-3">
                <div>
                    <div class="text-xs text-zinc-400">Отдых</div>
                    <div class="text-2xl font-bold tabular-nums" x-text="label"></div>
                </div>
                <button type="button" class="btn-secondary btn-sm ml-auto" @click="add(30)">+30 с</button>
                <button type="button" class="btn-ghost btn-sm" @click="skip()">Пропустить</button>
            </div>
        </div>
    </div>
</x-app-layout>
