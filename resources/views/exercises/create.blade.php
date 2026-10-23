@php($muscles = config('training.muscles'))
<x-app-layout>
    <x-slot name="title">Новое упражнение</x-slot>
    <x-slot name="header">
        <a href="{{ route('exercises.index') }}" class="btn-ghost btn-sm !px-2" aria-label="Назад">←</a>
        <h1 class="flex-1 font-semibold">Новое упражнение</h1>
    </x-slot>

    <form method="POST" action="{{ route('exercises.store') }}" class="space-y-4">
        @csrf
        <div>
            <label class="label" for="name">Название</label>
            <input id="name" name="name" value="{{ old('name') }}" class="input min-h-[3rem]" required maxlength="120">
        </div>
        <div class="grid grid-cols-2 gap-2">
            <div>
                <label class="label" for="pattern">Паттерн</label>
                <select id="pattern" name="pattern" class="input min-h-[3rem]" required>
                    @foreach (config('training.patterns') as $slug => $name)
                        <option value="{{ $slug }}" @selected(old('pattern') === $slug)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label" for="equipment">Оборудование</label>
                <select id="equipment" name="equipment" class="input min-h-[3rem]" required>
                    @foreach (config('training.equipment') as $slug => $name)
                        <option value="{{ $slug }}" @selected(old('equipment') === $slug)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label" for="mechanic">Тип</label>
                <select id="mechanic" name="mechanic" class="input min-h-[3rem]" required>
                    @foreach (config('training.mechanics') as $slug => $name)
                        <option value="{{ $slug }}" @selected(old('mechanic') === $slug)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label" for="fatigue">Утомляемость (1–5)</label>
                <input id="fatigue" type="number" name="fatigue" min="1" max="5" value="{{ old('fatigue', 2) }}" class="input min-h-[3rem]" required>
            </div>
            <div>
                <label class="label" for="rep_min">Повторений от</label>
                <input id="rep_min" type="number" name="rep_min" min="1" max="50" value="{{ old('rep_min', 8) }}" class="input min-h-[3rem]" required>
            </div>
            <div>
                <label class="label" for="rep_max">до</label>
                <input id="rep_max" type="number" name="rep_max" min="1" max="100" value="{{ old('rep_max', 12) }}" class="input min-h-[3rem]" required>
            </div>
        </div>

        <fieldset>
            <legend class="label">Основные мышцы</legend>
            <div class="flex flex-wrap gap-2">
                @foreach ($muscles as $slug => $name)
                    <label class="chip has-[:checked]:chip-active cursor-pointer">
                        <input type="checkbox" name="primary[]" value="{{ $slug }}" class="sr-only" @checked(in_array($slug, old('primary', [])))>{{ $name }}
                    </label>
                @endforeach
            </div>
        </fieldset>
        <fieldset>
            <legend class="label">Вспомогательные мышцы</legend>
            <div class="flex flex-wrap gap-2">
                @foreach ($muscles as $slug => $name)
                    <label class="chip has-[:checked]:chip-active cursor-pointer">
                        <input type="checkbox" name="secondary[]" value="{{ $slug }}" class="sr-only" @checked(in_array($slug, old('secondary', [])))>{{ $name }}
                    </label>
                @endforeach
            </div>
        </fieldset>

        <fieldset>
            <legend class="label">Иконка</legend>
            <div class="grid grid-cols-7 gap-2">
                @foreach ($icons as $icon)
                    <label class="flex aspect-square cursor-pointer items-center justify-center rounded-xl border border-zinc-800 bg-zinc-900 p-1.5 text-zinc-300 has-[:checked]:border-lime-400 has-[:checked]:text-lime-400" title="{{ $icon }}">
                        <input type="radio" name="icon" value="{{ $icon }}" class="sr-only" @checked(old('icon', 'default') === $icon)>
                        <x-exercise-icon :icon="$icon" class="h-full w-full" />
                    </label>
                @endforeach
            </div>
        </fieldset>

        <div>
            <label class="label" for="description">Подсказка по технике</label>
            <textarea id="description" name="description" rows="2" class="input">{{ old('description') }}</textarea>
        </div>

        <button class="btn-primary w-full">Сохранить</button>
    </form>
</x-app-layout>
