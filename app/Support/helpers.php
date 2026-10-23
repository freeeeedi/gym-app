<?php

if (! function_exists('format_kg')) {
    /** 62.5 → «62,5», 60.0 → «60». */
    function format_kg(?float $value): string
    {
        if ($value === null) {
            return '—';
        }

        return rtrim(rtrim(number_format($value, 2, ',', ' '), '0'), ',');
    }
}

if (! function_exists('format_minutes')) {
    function format_minutes(float $minutes): string
    {
        return (string) (int) round($minutes);
    }
}

if (! function_exists('format_number_input')) {
    /** Число для поля ввода: 62.5 → «62.5», 60.0 → «60». */
    function format_number_input(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
