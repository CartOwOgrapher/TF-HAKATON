<?php

namespace Modules\Employee\Transformers;

use Modules\Core\Abstracts\Http\Resources\BaseResource;

class EmployeeResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'employee_code' => $this->employee_code,
            'last_name' => $this->last_name,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'full_name' => $this->full_name,
            'short_name' => $this->short_name,
            'email' => $this->email,
            'company_id' => $this->company_id,
        ];
    }
}