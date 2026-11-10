<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNutritionProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Десятичная запятая с русской клавиатуры: «72,5» → «72.5».
        $this->merge([
            'weight_kg' => str_replace(',', '.', (string) $this->input('weight_kg')),
            'height_cm' => str_replace(',', '.', (string) $this->input('height_cm')),
        ]);
    }

    public function rules(): array
    {
        return [
            'sex' => ['required', Rule::in(array_keys(config('nutrition.sexes')))],
            'age' => ['required', 'integer', 'between:14,100'],
            'height_cm' => ['required', 'numeric', 'between:120,230'],
            'weight_kg' => ['required', 'numeric', 'between:30,300'],
            'activity' => ['required', Rule::in(array_keys(config('nutrition.activity_levels')))],
            'goal' => ['required', Rule::in(array_keys(config('nutrition.goals')))],
            'restrictions' => ['nullable', 'array'],
            'restrictions.*' => ['string', Rule::in(array_keys(config('nutrition.restrictions')))],
        ];
    }
}
