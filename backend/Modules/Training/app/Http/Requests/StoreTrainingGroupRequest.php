<?php

namespace Modules\Training\Http\Requests;

use Modules\Core\Abstracts\Http\Requests\BaseFormRequest;

class StoreTrainingGroupRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'course_id' => ['required', 'exists:courses,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ];
    }
}
