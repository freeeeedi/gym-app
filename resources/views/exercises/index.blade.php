<x-app-layout>
    <x-slot name="title">Каталог</x-slot>
    <x-slot name="header">
        <h1 class="flex-1 font-semibold">Каталог упражнений</h1>
        <a href="{{ route('exercises.create') }}" class="btn-secondary btn-sm">+ Своё</a>
    </x-slot>

    <livewire:exercise-catalog />
</x-app-layout>
