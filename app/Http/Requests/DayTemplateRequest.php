<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DayTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $template = $this->route('template');

        return $template ? $this->user()->can('update', $template) : true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:255'],
            'muscles' => ['required', 'array', 'min:1'],
            'muscles.*' => ['string', Rule::in(array_keys(config('training.muscles')))],
            'patterns' => ['nullable', 'array'],
            'patterns.*' => ['string', Rule::in(array_keys(config('training.patterns')))],
        ];
    }
}
