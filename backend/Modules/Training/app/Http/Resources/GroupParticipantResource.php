<?php

namespace Modules\Training\Http\Resources;

use Modules\Core\Abstracts\Http\Resources\BaseResource;

class GroupParticipantResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'training_group_id' => $this->training_group_id,
            'employee_id' => $this->employee_id,
            'employee' => $this->whenLoaded('employee', fn () => [
                'id' => $this->employee->id,
                'full_name' => $this->employee->full_name,
                'email' => $this->employee->email,
            ]),
            'completion_percent' => $this->completion_percent,
            'certificate_path'    => $this->certificate_path,
        ];
    }
}
