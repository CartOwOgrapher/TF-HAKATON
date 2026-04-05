<?php

namespace Modules\Gantt\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDatesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date', 'date_format:Y-m-d'],
            'end_date'   => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.required'        => 'Дата начала обязательна.',
            'start_date.date_format'     => 'Дата начала должна быть в формате YYYY-MM-DD.',
            'end_date.required'          => 'Дата окончания обязательна.',
            'end_date.date_format'       => 'Дата окончания должна быть в формате YYYY-MM-DD.',
            'end_date.after_or_equal'    => 'Дата окончания не может быть раньше даты начала.',
        ];
    }
}