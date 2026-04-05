<?php

namespace Modules\Core\Auth\Http\Resources;

use Modules\Core\Abstracts\Http\Resources\BaseResource;

class UserResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role->value,
            'role_label' => $this->role->label(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
