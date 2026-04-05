<?php

namespace Modules\Course\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Core\Abstracts\Http\Requests\BaseFormRequest;

class CourseRequest extends BaseFormRequest
{
    public function rules(): array
    {
        $courseId = $this->route('id');
        return [
            'code' => ['required', 'string', 'max:10', 'min:10', 'unique:courses,code,' . $courseId],
            'title' => ['required', 'string'],
            'price' => ['required', 'decimal:2'],
            'description' => ['nullable', 'string'],
            'duration_days' => ['required', 'int'],
        ];

    }
}
