<?php

namespace Modules\Specification\Http\Requests;

use Modules\Core\Abstracts\Http\Requests\BaseFormRequest;

class UpdateSpecificationRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'number' => ['sometimes', 'string', 'max:50'],
            'date' => ['sometimes', 'date'],
            'company_id' => ['sometimes', 'exists:companies,id'],
        ];
    }
}
