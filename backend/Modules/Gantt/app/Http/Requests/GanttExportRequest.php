<?php

namespace Modules\Gantt\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GanttExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from'      => ['nullable', 'date', 'date_format:Y-m-d'],
            'to'        => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:from'],
            'format'    => ['nullable', 'string', 'in:csv,json'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'status'    => ['nullable', 'string'],
        ];
    }

    public function fromDate(): string
    {
        return $this->input('from', now()->startOfYear()->toDateString());
    }

    public function toDate(): string
    {
        return $this->input('to', now()->endOfYear()->toDateString());
    }

    public function exportFormat(): string
    {
        return $this->input('format', 'csv');
    }
}