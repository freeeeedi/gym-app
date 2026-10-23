<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExerciseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $muscles = array_keys(config('training.muscles'));

        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'pattern' => ['required', Rule::in(array_keys(config('training.patterns')))],
            'equipment' => ['required', Rule::in(array_keys(config('training.equipment')))],
            'mechanic' => ['required', Rule::in(array_keys(config('training.mechanics')))],
            'fatigue' => ['required', 'integer', 'between:1,5'],
            'rep_min' => ['required', 'integer', 'between:1,50'],
            'rep_max' => ['required', 'integer', 'between:1,100', 'gte:rep_min'],
            'icon' => ['nullable', 'string', 'max:32', 'alpha_dash'],
            'primary' => ['required', 'array', 'min:1'],
            'primary.*' => ['string', Rule::in($muscles)],
            'secondary' => ['nullable', 'array'],
            'secondary.*' => ['string', Rule::in($muscles)],
        ];
    }
}
