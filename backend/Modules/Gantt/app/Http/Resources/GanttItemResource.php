<?php

namespace Modules\Gantt\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GanttItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'text'              => $this->course?->title ?? "Группа #{$this->id}",
            'course_id'         => $this->course_id,
            'course_code'       => $this->course?->code,
            'specification_id'  => $this->specification_id,

            'start_date'        => $this->start_date?->toDateString(),
            'end_date'          => $this->end_date?->toDateString(),
            'duration'          => $this->start_date && $this->end_date
                                    ? $this->start_date->diffInDays($this->end_date) + 1
                                    : null,

            'status'            => $this->status?->value,
            'status_label'      => $this->status?->label(),

            'progress'          => round(($this->average_progress ?? 0) / 100, 4),
            'progress_percent'  => $this->average_progress ?? 0,

            'participant_count' => $this->participants_count,

            'price_per_person'  => $this->price_per_person,
            'total_cost'        => $this->group_cost,

            'color'             => $this->gantt_color,

            'conflict_ids'      => $this->whenLoaded('conflicts', fn () =>
                $this->conflicts->pluck('id')
            ),
        ];
    }
}