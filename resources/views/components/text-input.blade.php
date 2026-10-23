@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'input min-h-[3rem]']) }}>
