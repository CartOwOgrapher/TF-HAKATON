<?php

namespace Modules\Training\Http\Resources;

use Modules\Core\Abstracts\Http\Resources\BaseResource;

class TrainingGroupResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'course' => $this->whenLoaded('course', fn () => [
                'id' => $this->course->id,
                'name' => $this->course->title,
                'price' => $this->course->getLastPriceNumeric(),
            ]),
            'specification_id' => $this->specification_id,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),

            'participants_count' => $this->participants_count,
            'price_per_person' => $this->price_per_person,
            'group_cost' => $this->group_cost,
            'average_progress' => $this->average_progress,

            'participants' => GroupParticipantResource::collection(
                $this->whenLoaded('participants')
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
