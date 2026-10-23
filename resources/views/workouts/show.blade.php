<x-app-layout>
    <x-slot name="title">{{ $workout->title }}</x-slot>
    <x-slot name="header">
        <a href="{{ route('calendar', ['date' => $workout->scheduled_on->toDateString()]) }}" class="btn-ghost btn-sm !px-2" aria-label="К календарю">←</a>
        <div class="min-w-0 flex-1">
            <h1 class="truncate font-semibold">{{ $workout->title }}</h1>
            <div class="flex items-center gap-2 text-sm text-zinc-400">
                {{ $workout->scheduled_on->translatedFormat('D, j F') }}
                <x-status-badge :status="$workout->status" />
            </div>
        </div>
    </x-slot>

    <details class="card mb-4 !p-0" @if ($errors->hasAny(['scheduled_on', 'title', 'status'])) open @endif>
        <summary class="cursor-pointer list-none p-4 text-sm text-zinc-300">
            ⚙ Дата, шаблон, статус
            @if ($workout->template)
                <span class="text-zinc-500">· {{ $workout->template->name }}</span>
            @endif
        </summary>
        <div class="space-y-4 border-t border-zinc-800 p-4">
            <form method="POST" action="{{ route('workouts.update', $workout) }}" class="space-y-3">
                @csrf @method('PATCH')
                <div>
                    <label class="label" for="title">Название</label>
                    <input id="title" name="title" value="{{ old('title', $workout->title) }}" class="input min-h-[3rem]" required maxlength="120">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="label" for="scheduled_on">Дата (перенос)</label>
                        <input id="scheduled_on" type="date" name="scheduled_on" value="{{ old('scheduled_on', $workout->scheduled_on->toDateString()) }}" class="input min-h-[3rem]" required>
                    </div>
                    <div>
                        <label class="label" for="status">Статус</label>
                        <select id="status" name="status" class="input min-h-[3rem]">
                            @foreach (\App\Enums\WorkoutStatus::cases() as $status)
                                <option value="{{ $status->value }}" @selected($workout->status === $status)>{{ $status->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="label" for="day_template_id">Шаблон дня</label>
                    <select id="day_template_id" name="day_template_id" class="input min-h-[3rem]">
                        <option value="">Без шаблона</option>
                        @foreach ($templates as $template)
                            <option value="{{ $template->id }}" @selected($workout->day_template_id === $template->id)>{{ $template->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn-secondary w-full">Сохранить</button>
            </form>
            <form method="POST" action="{{ route('workouts.destroy', $workout) }}" onsubmit="return confirm('Удалить тренировку вместе с подходами?')">
                @csrf @method('DELETE')
                <button class="btn-danger w-full">Удалить тренировку</button>
            </form>
        </div>
    </details>

    <livewire:workout-planner :workout="$workout" />
</x-app-layout>
