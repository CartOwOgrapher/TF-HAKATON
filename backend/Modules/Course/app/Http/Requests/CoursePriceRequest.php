<?php

namespace Modules\Course\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CoursePriceRequest extends FormRequest
{

    public function rules(): array
    {
        return [
            'price' => ['required', 'decimal:2'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
