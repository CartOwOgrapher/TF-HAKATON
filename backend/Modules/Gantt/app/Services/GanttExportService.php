<?php

namespace Modules\Gantt\Services;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Сервис экспорта данных диаграммы Ганта.
 *
 * Формирует потоковые ответы (StreamedResponse) для скачивания данных
 * о учебных группах в форматах CSV и JSON.
 */
class GanttExportService
{
    /**
     * Экспортирует учебные группы в формате CSV.
     *
     * Файл содержит BOM (UTF-8) для корректного отображения кириллицы в Excel.
     * Разделитель полей — точка с запятой (`;`).
     *
     * Колонки CSV:
     * - ID, Курс, Дата начала, Дата окончания, Длительность (дней),
     *   Статус, Участников, Средний прогресс (%), Цена за человека (руб.), Стоимость группы (руб.)
     *
     * @param  Collection  $groups  Коллекция моделей TrainingGroup с загруженными relations `course` и `participants`.
     * @param  string      $from    Начало периода (YYYY-MM-DD) — используется в имени файла.
     * @param  string      $to      Конец периода (YYYY-MM-DD) — используется в имени файла.
     * @return StreamedResponse     Ответ с заголовком `Content-Disposition: attachment`.
     */
    public function exportCsv(Collection $groups, string $from, string $to): StreamedResponse
    {
        $filename = "gantt_{$from}_{$to}.csv";

        return response()->streamDownload(function () use ($groups) {
            $handle = fopen('php://output', 'w');

            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'ID',
                'Курс',
                'Дата начала',
                'Дата окончания',
                'Длительность (дней)',
                'Статус',
                'Участников',
                'Средний прогресс (%)',
                'Цена за человека (руб.)',
                'Стоимость группы (руб.)',
            ], ';');

            foreach ($groups as $group) {
                fputcsv($handle, [
                    $group->id,
                    $group->course?->title ?? '',
                    $group->start_date?->toDateString(),
                    $group->end_date?->toDateString(),
                    $group->start_date && $group->end_date
                        ? $group->start_date->diffInDays($group->end_date) + 1
                        : '',
                    $group->status?->label() ?? $group->status?->value,
                    $group->participants_count,
                    $group->average_progress,
                    $group->price_per_person,
                    $group->group_cost,
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Экспортирует учебные группы в формате JSON.
     *
     * Структура файла:
     * ```json
     * {
     *   "exported_at": "2026-04-05T11:00:00+00:00",
     *   "period": { "from": "2026-01-01", "to": "2026-12-31" },
     *   "total": 5,
     *   "items": [ { "id": 1, "course": "...", ... } ]
     * }
     * ```
     *
     * @param  Collection  $groups  Коллекция моделей TrainingGroup с загруженными relations `course` и `participants`.
     * @param  string      $from    Начало периода (YYYY-MM-DD).
     * @param  string      $to      Конец периода (YYYY-MM-DD).
     * @return StreamedResponse     Ответ с заголовком `Content-Disposition: attachment`.
     */
    public function exportJson(Collection $groups, string $from, string $to): StreamedResponse
    {
        $filename = "gantt_{$from}_{$to}.json";

        $data = [
            'exported_at' => now()->toIso8601String(),
            'period'      => ['from' => $from, 'to' => $to],
            'total'       => $groups->count(),
            'items'       => $groups->map(fn ($group) => [
                'id'               => $group->id,
                'course'           => $group->course?->title,
                'course_code'      => $group->course?->code,
                'start_date'       => $group->start_date?->toDateString(),
                'end_date'         => $group->end_date?->toDateString(),
                'duration_days'    => $group->start_date && $group->end_date
                    ? $group->start_date->diffInDays($group->end_date) + 1
                    : null,
                'status'           => $group->status?->value,
                'status_label'     => $group->status?->label(),
                'participant_count'=> $group->participants_count,
                'progress_percent' => $group->average_progress,
                'price_per_person' => $group->price_per_person,
                'total_cost'       => $group->group_cost,
                'color'            => $group->gantt_color,
            ])->values(),
        ];

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }, $filename, [
            'Content-Type'        => 'application/json; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}