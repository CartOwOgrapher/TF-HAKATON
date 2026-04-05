<?php

namespace Modules\Specification\Http\Requests;

use Modules\Core\Abstracts\Http\Requests\BaseFormRequest;

class StoreSpecificationRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'number' => ['required', 'string', 'max:50'],
            'date' => ['required', 'date'],
            'company_id' => ['required', 'exists:companies,id'],
        ];
    }
}
