<?php
namespace Modules\Course\Transformers;

use Modules\Core\Abstracts\Http\Resources\BaseResource;

class CourseCompactResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'title' => $this->title,
            'description' => $this->description,
            'duration_days' => $this->duration_days,
            'price' => $this->lastPrice?->price
        ];
    }
}