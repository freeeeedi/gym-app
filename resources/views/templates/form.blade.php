<x-app-layout>
    <x-slot name="title">{{ $template->exists ? 'Шаблон' : 'Новый шаблон' }}</x-slot>
    <x-slot name="header">
        <a href="{{ route('templates.index') }}" class="btn-ghost btn-sm !px-2" aria-label="Назад">←</a>
        <h1 class="flex-1 font-semibold">{{ $template->exists ? 'Изменить шаблон' : 'Новый шаблон' }}</h1>
    </x-slot>

    <form method="POST" action="{{ $template->exists ? route('templates.update', $template) : route('templates.store') }}" class="space-y-4">
        @csrf
        @if ($template->exists) @method('PUT') @endif
        <div>
            <label class="label" for="name">Название</label>
            <input id="name" name="name" value="{{ old('name', $template->name) }}" class="input min-h-[3rem]" required maxlength="80">
        </div>
        <div>
            <label class="label" for="description">Описание</label>
            <input id="description" name="description" value="{{ old('description', $template->description) }}" class="input min-h-[3rem]" maxlength="255">
        </div>
        <fieldset>
            <legend class="label">Целевые мышцы</legend>
            <div class="flex flex-wrap gap-2">
                @foreach (config('training.muscles') as $slug => $name)
                    <label class="chip has-[:checked]:chip-active cursor-pointer">
                        <input type="checkbox" name="muscles[]" value="{{ $slug }}" class="sr-only" @checked(in_array($slug, old('muscles', $selectedMuscles)))>{{ $name }}
                    </label>
                @endforeach
            </div>
        </fieldset>
        <fieldset>
            <legend class="label">Обязательные паттерны движения</legend>
            <div class="flex flex-wrap gap-2">
                @foreach (config('training.patterns') as $slug => $name)
                    <label class="chip has-[:checked]:chip-active cursor-pointer">
                        <input type="checkbox" name="patterns[]" value="{{ $slug }}" class="sr-only" @checked(in_array($slug, old('patterns', $template->patterns ?? [])))>{{ $name }}
                    </label>
                @endforeach
            </div>
        </fieldset>
        <button class="btn-primary w-full">Сохранить</button>
    </form>
</x-app-layout>
