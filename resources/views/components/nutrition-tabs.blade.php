@php
    $tabs = [
        ['route' => 'nutrition.diary', 'match' => ['nutrition.diary'], 'label' => 'Дневник'],
        ['route' => 'nutrition.stats', 'match' => ['nutrition.stats', 'nutrition.weight.*'], 'label' => 'Статистика'],
        ['route' => 'nutrition.foods', 'match' => ['nutrition.foods'], 'label' => 'Продукты'],
        ['route' => 'nutrition.dishes.index', 'match' => ['nutrition.dishes.*'], 'label' => 'Блюда'],
        ['route' => 'nutrition.shopping.index', 'match' => ['nutrition.shopping.*'], 'label' => 'Покупки'],
        ['route' => 'nutrition.profile.edit', 'match' => ['nutrition.profile.*'], 'label' => 'Профиль'],
    ];
@endphp
<nav class="-mx-4 mb-4 flex gap-2 overflow-x-auto px-4 pb-1" aria-label="Разделы питания">
    @foreach ($tabs as $tab)
        @php($active = request()->routeIs(...$tab['match']))
        <a href="{{ route($tab['route']) }}"
           class="chip shrink-0 whitespace-nowrap {{ $active ? 'chip-active' : '' }}"
           @if ($active) aria-current="page" @endif>{{ $tab['label'] }}</a>
    @endforeach
</nav>
