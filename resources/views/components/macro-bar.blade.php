@props([
    'label',
    'value',
    'target' => null,
    'unit' => 'г',
    'color' => 'bg-lime-400',
    // Подсвечивать ли перебор красным (для калорий — да, для белка — нет).
    'warnOver' => false,
])
@php
    $percent = $target > 0 ? min(100, round($value / $target * 100)) : 0;
    $over = $warnOver && $target > 0 && $value > $target * 1.05;
@endphp
<div {{ $attributes }}>
    <div class="flex items-baseline justify-between gap-2 text-sm">
        <span class="text-zinc-300">{{ $label }}</span>
        <span class="tabular-nums">
            <span class="font-semibold {{ $over ? 'text-red-400' : 'text-zinc-50' }}">{{ format_int($value) }}</span>
            @if ($target)
                <span class="text-zinc-500">/ {{ format_int($target) }} {{ $unit }}</span>
            @else
                <span class="text-zinc-500">{{ $unit }}</span>
            @endif
        </span>
    </div>
    <div class="mt-1 h-2 overflow-hidden rounded-full bg-zinc-800" role="progressbar" aria-label="{{ $label }}" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">
        <div class="h-full rounded-full {{ $over ? 'bg-red-500' : $color }}" style="width: {{ $percent }}%"></div>
    </div>
</div>
