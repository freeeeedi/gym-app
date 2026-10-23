<?php

namespace App\Http\Requests;

use App\Models\DayTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'scheduled_on' => ['required', 'date'],
            'day_template_id' => ['nullable', 'integer', Rule::exists(DayTemplate::class, 'id')->where(
                fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $this->user()->id)
            )],
            'title' => ['nullable', 'string', 'max:120'],
        ];
    }
}
