<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTrainingSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Чекбоксы: отсутствие в запросе = выключено.
        $this->merge([
            'rotation_fallback' => $this->boolean('rotation_fallback'),
            'include_bodyweight' => $this->boolean('include_bodyweight'),
            'count_secondary_as_half' => $this->boolean('count_secondary_as_half'),
        ]);
    }

    public function rules(): array
    {
        return [
            'time_budget_minutes' => ['required', 'integer', 'between:20,180'],
            'rotation_window' => ['required', 'integer', 'between:0,10'],
            'rotation_fallback' => ['boolean'],
            'weekly_sets_min' => ['required', 'integer', 'between:0,40'],
            'weekly_sets_max' => ['required', 'integer', 'between:1,60', 'gte:weekly_sets_min'],
            'exercises_per_muscle_min' => ['required', 'integer', 'between:1,6'],
            'exercises_per_muscle_max' => ['required', 'integer', 'between:1,8', 'gte:exercises_per_muscle_min'],
            'sets_compound' => ['required', 'integer', 'between:1,8'],
            'sets_isolation' => ['required', 'integer', 'between:1,8'],
            'rest_compound_seconds' => ['required', 'integer', 'between:30,600'],
            'rest_isolation_seconds' => ['required', 'integer', 'between:15,600'],
            'include_bodyweight' => ['boolean'],
            'count_secondary_as_half' => ['boolean'],
            'stagnation_sessions' => ['required', 'integer', 'between:2,10'],
        ];
    }
}
