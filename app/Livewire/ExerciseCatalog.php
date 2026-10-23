<?php

namespace App\Livewire;

use App\Models\Exercise;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Каталог упражнений с поиском и фильтрами.
 * В режиме выбора ($picker = true) кнопка «+» отправляет событие exercise-picked.
 */
class ExerciseCatalog extends Component
{
    public bool $picker = false;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $muscle = '';

    #[Url(except: '')]
    public string $pattern = '';

    #[Url(except: '')]
    public string $equipment = '';

    /** @var list<int> уже выбранные в тренировке (для режима выбора) */
    public array $selectedIds = [];

    public function pick(int $exerciseId): void
    {
        $this->dispatch('exercise-picked', exerciseId: $exerciseId);
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'muscle', 'pattern', 'equipment');
    }

    public function render()
    {
        $muscleOptions = config('training.muscles');
        $patternOptions = config('training.patterns');
        $equipmentOptions = config('training.equipment');

        $exercises = Exercise::query()
            ->visibleTo(Auth::user())
            ->with('muscles')
            ->when(isset($patternOptions[$this->pattern]), fn ($q) => $q->where('pattern', $this->pattern))
            ->when(isset($equipmentOptions[$this->equipment]), fn ($q) => $q->where('equipment', $this->equipment))
            ->when(isset($muscleOptions[$this->muscle]), fn ($q) => $q->whereHas('muscles', fn ($m) => $m->where('slug', $this->muscle)))
            ->orderBy('name')
            ->get();

        // Поиск по названию в PHP: регистронезависимо для кириллицы в любой СУБД.
        $term = trim($this->search);
        if ($term !== '') {
            $exercises = $exercises->filter(fn (Exercise $e) => mb_stripos($e->name, $term) !== false)->values();
        }

        return view('livewire.exercise-catalog', compact('exercises', 'muscleOptions', 'patternOptions', 'equipmentOptions'));
    }
}
