<?php

namespace Modules\Gantt\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Gantt\Services\GanttColorService;

class UpdateColorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $palette = implode(',', GanttColorService::PALETTE);

        return [
            'color' => [
                'required',
                'string',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'color.required' => 'Цвет обязателен.',
            'color.regex'    => 'Цвет должен быть в формате HEX (#RRGGBB).',
        ];
    }
}