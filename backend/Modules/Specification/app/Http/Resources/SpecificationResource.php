<?php

namespace Modules\Specification\Http\Resources;

use Modules\Core\Abstracts\Http\Resources\BaseResource;
use Modules\Training\Http\Resources\TrainingGroupResource;

class SpecificationResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'date' => $this->date?->toDateString(),
            'company_id' => $this->company_id,
            'company' => $this->whenLoaded('company', fn () => [
                'id' => $this->company->id,
                'code' => $this->company->code,
                'name' => $this->company->name,
            ]),
            'groups_count' => $this->groups_count,
            'total_without_vat' => $this->total_without_vat,
            'vat_amount' => $this->vat_amount,
            'total_with_vat' => $this->total_with_vat,
            'training_groups' => TrainingGroupResource::collection(
                $this->whenLoaded('trainingGroups')
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
