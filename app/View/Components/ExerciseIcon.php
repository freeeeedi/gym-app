<?php

namespace App\View\Components;

use Illuminate\View\Component;

/**
 * Иконка упражнения из resources/svg/exercise-icons/{key}.svg.
 * Неизвестный или пустой ключ → иконка по умолчанию (гантель).
 */
class ExerciseIcon extends Component
{
    public const FALLBACK = 'default';

    public string $svg;

    public function __construct(public ?string $icon = null)
    {
        $this->svg = self::load($icon);
    }

    public static function load(?string $key): string
    {
        static $cache = [];
        $key = $key && preg_match('/^[a-z0-9_-]+$/', $key) ? $key : self::FALLBACK;

        return $cache[$key] ??= (function () use ($key) {
            $path = resource_path("svg/exercise-icons/{$key}.svg");

            return is_file($path)
                ? file_get_contents($path)
                : file_get_contents(resource_path('svg/exercise-icons/'.self::FALLBACK.'.svg'));
        })();
    }

    /** @return list<string> */
    public static function available(): array
    {
        return collect(glob(resource_path('svg/exercise-icons/*.svg')))
            ->map(fn ($p) => basename($p, '.svg'))
            ->sort()->values()->all();
    }

    public function render(): string
    {
        return <<<'BLADE'
            <span {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center justify-center [&>svg]:h-full [&>svg]:w-full']) }} aria-hidden="true">{!! $svg !!}</span>
            BLADE;
    }
}
