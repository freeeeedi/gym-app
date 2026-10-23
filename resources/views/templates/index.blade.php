@php($muscleNames = config('training.muscles'))
<x-app-layout>
    <x-slot name="title">Шаблоны дней</x-slot>
    <x-slot name="header">
        <a href="{{ route('more') }}" class="btn-ghost btn-sm !px-2" aria-label="Назад">←</a>
        <h1 class="flex-1 font-semibold">Шаблоны дней</h1>
        <a href="{{ route('templates.create') }}" class="btn-secondary btn-sm">+ Новый</a>
    </x-slot>

    <p class="muted mb-3">Шаблон задаёт целевые мышцы и обязательные паттерны — по ним строятся подсказки.</p>
    <div class="space-y-2">
        @foreach ($templates as $template)
            <div class="card space-y-2">
                <div class="flex items-start gap-2">
                    <div class="min-w-0 flex-1">
                        <div class="font-semibold">{{ $template->name }}</div>
                        @if ($template->description)
                            <div class="muted">{{ $template->description }}</div>
                        @endif
                    </div>
                    @if ($template->user_id)
                        <span class="chip !py-0.5 text-xs">мой</span>
                    @endif
                </div>
                <div class="text-sm"><span class="text-zinc-400">Мышцы:</span> {{ $template->muscles->sortBy('sort')->map(fn ($m) => $muscleNames[$m->slug])->implode(', ') }}</div>
                <div class="text-sm"><span class="text-zinc-400">Обязательно:</span> {{ collect($template->patterns)->map(fn ($p) => mb_strtolower(config('training.patterns.'.$p)))->implode(', ') ?: '—' }}</div>
                <div class="flex gap-2 pt-1">
                    @can('update', $template)
                        <a href="{{ route('templates.edit', $template) }}" class="btn-secondary btn-sm flex-1">Изменить</a>
                        <form method="POST" action="{{ route('templates.destroy', $template) }}" onsubmit="return confirm('Удалить шаблон?')">
                            @csrf @method('DELETE')
                            <button class="btn-danger btn-sm">Удалить</button>
                        </form>
                    @else
                        <a href="{{ route('templates.create', ['from' => $template->id]) }}" class="btn-secondary btn-sm flex-1">Скопировать и изменить</a>
                    @endcan
                </div>
            </div>
        @endforeach
    </div>
</x-app-layout>
