<?php

namespace Modules\Company\Transformers;

use Modules\Core\Abstracts\Http\Resources\BaseResource;

class CompanyResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
        ];
    }
}