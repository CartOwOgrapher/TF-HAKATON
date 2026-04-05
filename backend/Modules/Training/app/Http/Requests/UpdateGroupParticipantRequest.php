<?php

namespace Modules\Training\Http\Requests;

use Modules\Core\Abstracts\Http\Requests\BaseFormRequest;

class UpdateGroupParticipantRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'completion_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
