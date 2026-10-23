@props(['status'])
@php
    $classes = match ($status->value) {
        'completed' => 'bg-lime-400/15 text-lime-300',
        'in_progress' => 'bg-amber-400/15 text-amber-300',
        default => 'bg-zinc-800 text-zinc-300',
    };
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium $classes"]) }}>{{ $status->label() }}</span>
