<x-app-layout>
    <x-slot name="title">Продукты</x-slot>
    <x-slot name="header">
        <h1 class="flex-1 font-semibold">Питание</h1>
    </x-slot>

    <x-nutrition-tabs />

    <livewire:food-catalog />
</x-app-layout>
