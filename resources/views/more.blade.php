<x-app-layout>
    <x-slot name="title">Ещё</x-slot>
    <x-slot name="header">
        <h1 class="flex-1 font-semibold">Ещё</h1>
    </x-slot>

    <nav class="space-y-2">
        <a href="{{ route('templates.index') }}" class="card flex items-center justify-between">Шаблоны тренировочных дней <span class="text-zinc-500">›</span></a>
        <a href="{{ route('settings.edit') }}" class="card flex items-center justify-between">Настройки тренировок <span class="text-zinc-500">›</span></a>
        <a href="{{ route('exercises.create') }}" class="card flex items-center justify-between">Добавить своё упражнение <span class="text-zinc-500">›</span></a>
        <a href="{{ route('profile.edit') }}" class="card flex items-center justify-between">Профиль и пароль <span class="text-zinc-500">›</span></a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="card w-full text-left text-red-300">Выйти</button>
        </form>
    </nav>
</x-app-layout>
