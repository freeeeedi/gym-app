@php
    $items = [
        ['route' => 'dashboard', 'match' => ['dashboard', 'workouts.*'], 'label' => 'Сегодня', 'icon' => 'M3 12l9-8 9 8M5 10v10h5v-6h4v6h5V10'],
        ['route' => 'calendar', 'match' => ['calendar'], 'label' => 'Календарь', 'icon' => 'M7 3v3M17 3v3M4 8h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z'],
        ['route' => 'exercises.index', 'match' => ['exercises.*'], 'label' => 'Каталог', 'icon' => 'M6 9v6M18 9v6M3 11v2M21 11v2M6 12h12'],
        ['route' => 'analytics', 'match' => ['analytics*'], 'label' => 'Прогресс', 'icon' => 'M4 19h16M7 15l4-4 3 3 5-6'],
        ['route' => 'more', 'match' => ['more', 'templates.*', 'settings.*', 'profile.*'], 'label' => 'Ещё', 'icon' => 'M5 12h.01M12 12h.01M19 12h.01'],
    ];
@endphp
<nav class="fixed inset-x-0 bottom-0 z-30 border-t border-zinc-800 bg-zinc-950/95 pb-[env(safe-area-inset-bottom)] backdrop-blur">
    <div class="mx-auto grid max-w-xl grid-cols-5">
        @foreach ($items as $item)
            @php($active = request()->routeIs(...$item['match']))
            <a href="{{ route($item['route']) }}"
               class="flex flex-col items-center gap-1 py-2.5 text-xs {{ $active ? 'text-lime-400' : 'text-zinc-400' }}"
               @if ($active) aria-current="page" @endif>
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="{{ $item['icon'] }}" />
                </svg>
                {{ $item['label'] }}
            </a>
        @endforeach
    </div>
</nav>
