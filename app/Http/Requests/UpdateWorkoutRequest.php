<?php

namespace App\Http\Requests;

use App\Enums\WorkoutStatus;
use App\Models\DayTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('workout'));
    }

    public function rules(): array
    {
        return [
            'scheduled_on' => ['sometimes', 'required', 'date'],
            'title' => ['sometimes', 'required', 'string', 'max:120'],
            'status' => ['sometimes', 'required', Rule::enum(WorkoutStatus::class)],
            'day_template_id' => ['sometimes', 'nullable', 'integer', Rule::exists(DayTemplate::class, 'id')->where(
                fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $this->user()->id)
            )],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
