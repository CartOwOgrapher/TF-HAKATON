<?php

namespace Modules\Training\Http\Requests;

use Modules\Core\Abstracts\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rules\Enum;
use Modules\Training\Enums\TrainingStatus;

class UpdateTrainingGroupRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'course_id' => ['sometimes', 'exists:courses,id'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
            'status' => ['sometimes', new Enum(TrainingStatus::class)],
        ];
    }
}
