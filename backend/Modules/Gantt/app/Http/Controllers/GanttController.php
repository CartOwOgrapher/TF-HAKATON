<?php

namespace Modules\Gantt\Http\Controllers;

use Modules\Core\Abstracts\Http\Controllers\BaseController;
use Modules\Gantt\Http\Requests\GanttFilterRequest;
use Modules\Gantt\Http\Requests\GanttExportRequest;
use Modules\Gantt\Http\Requests\UpdateColorRequest;
use Modules\Gantt\Http\Requests\UpdateDatesRequest;
use Modules\Gantt\Http\Resources\GanttItemResource;
use Modules\Gantt\Services\GanttColorService;
use Modules\Gantt\Services\GanttExportService;
use Modules\Training\Models\TrainingGroup;
use Modules\Training\Enums\TrainingStatus;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @group Диаграмма Ганта
 *
 * Управление представлением учебных групп в формате диаграммы Ганта:
 * фильтрация по периоду и статусу, обновление дат и цвета, экспорт в CSV/JSON.
 */
class GanttController extends BaseController
{
    public function __construct(
        private readonly GanttColorService  $colorService,
        private readonly GanttExportService $exportService,
    ) {}

    /**
     * Список элементов Ганта
     *
     * Возвращает учебные группы в заданном периоде с цветами, прогрессом и флагами конфликтов.
     * Если у группы ещё не назначен цвет — он назначается автоматически на основе курса.
     * Конфликтом считается пересечение дат двух групп одного курса.
     *
     * @authenticated
     *
     * @queryParam from string required Начало периода (YYYY-MM-DD). Example: 2026-01-01
     * @queryParam to string required Конец периода (YYYY-MM-DD). Example: 2026-12-31
     * @queryParam status string Фильтр по статусу группы. Допустимые значения: `planned`, `in_progress`, `completed`, `cancelled`. Example: planned
     * @queryParam course_id integer Фильтр по ID курса. Example: 5
     *
     * @response 200 {
     *   "success": true,
     *   "message": "OK",
     *   "data": {
     *     "period": { "from": "2026-01-01", "to": "2026-12-31" },
     *     "total": 2,
     *     "palette": ["#3B82F6", "#10B981"],
     *     "items": [
     *       {
     *         "id": 1,
     *         "text": "Охрана труда",
     *         "course_id": 3,
     *         "course_code": "OT-001",
     *         "specification_id": 10,
     *         "start_date": "2026-02-01",
     *         "end_date": "2026-02-05",
     *         "duration": 5,
     *         "status": "planned",
     *         "status_label": "Запланирована",
     *         "progress": 0.0,
     *         "progress_percent": 0,
     *         "participant_count": 12,
     *         "price_per_person": "3500.00",
     *         "total_cost": 42000,
     *         "color": "#3B82F6",
     *         "conflict_ids": []
     *       }
     *     ]
     *   }
     * }
     *
     * @response 422 { "message": "Недопустимое значение статуса: unknown" }
     */
    public function index(GanttFilterRequest $request): JsonResponse
    {
        $from = $request->fromDate();
        $to   = $request->toDate();

        $groups = TrainingGroup::query()
            ->with(['course', 'participants'])
            ->inPeriod($from, $to)
            ->when(
                $request->filled('status'),
                function ($q) use ($request) {
                    $status = TrainingStatus::tryFrom($request->status);
                    if ($status === null) {
                        abort(422, "Недопустимое значение статуса: {$request->status}");
                    }
                    return $q->withStatus($status);
                }
            )
            ->when(
                $request->filled('course_id'),
                fn ($q) => $q->where('course_id', $request->course_id)
            )
            ->orderBy('start_date')
            ->get();

        $this->loadConflicts($groups);

        $groups->each(function ($group) {
            if (empty($group->gantt_color)) {
                $this->colorService->assignColor($group);
            }
        });

        return $this->success([
            'period'  => ['from' => $from, 'to' => $to],
            'total'   => $groups->count(),
            'palette' => GanttColorService::PALETTE,
            'items'   => GanttItemResource::collection($groups),
        ]);
    }

        /**
     * Обновить даты группы
     *
     * Изменяет даты начала и окончания учебной группы.
     * После обновления возвращает обновлённый элемент Ганта и список конфликтующих групп
     * (те, у кого совпадают даты начала в рамках того же курса).
     *
     * @authenticated
     *
     * @urlParam trainingGroup integer required ID учебной группы. Example: 1
     *
     * @bodyParam start_date string required Новая дата начала (YYYY-MM-DD). Example: 2026-03-01
     * @bodyParam end_date string required Новая дата окончания (YYYY-MM-DD). Example: 2026-03-10
     *
     * @response 200 {
     *   "success": true,
     *   "message": "OK",
     *   "data": {
     *     "item": { "id": 1, "start_date": "2026-03-01", "end_date": "2026-03-10" },
     *     "conflicts": [
     *       { "id": 7, "start_date": "2026-03-01", "end_date": "2026-03-08" }
     *     ]
     *   }
     * }
     *
     * @response 403 { "success": false, "message": "Недостаточно прав" }
     * @response 404 { "message": "No query results for model [TrainingGroup]." }
     * @response 422 { "message": "The end date must be a date after start date." }
     */
    public function updateDates(UpdateDatesRequest $request, TrainingGroup $trainingGroup): JsonResponse
    {
        $this->authorize('update', $trainingGroup);

        $trainingGroup->update([
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
        ]);

        $trainingGroup->load(['course', 'participants']);

        // Ищем конфликты с новыми датами
        $conflicts = TrainingGroup::query()
            ->conflictsWith($trainingGroup)
            ->get(['id', 'start_date', 'end_date']);

        $this->loadConflicts(collect([$trainingGroup]));

        return $this->success([
            'item'       => new GanttItemResource($trainingGroup),
            'conflicts'  => $conflicts->map(fn ($c) => [
                'id'         => $c->id,
                'start_date' => $c->start_date?->toDateString(),
                'end_date'   => $c->end_date?->toDateString(),
            ]),
        ]);
    }

        /**
     * Экспорт данных Ганта
     *
     * Выгружает учебные группы за указанный период в формате CSV или JSON.
     * Формат определяется параметром `format` (по умолчанию — CSV).
     * CSV содержит BOM для корректного открытия в Excel.
     *
     * @authenticated
     *
     * @queryParam from string required Начало периода (YYYY-MM-DD). Example: 2026-01-01
     * @queryParam to string required Конец периода (YYYY-MM-DD). Example: 2026-12-31
     * @queryParam format string Формат файла: `csv` или `json`. По умолчанию: `csv`. Example: csv
     * @queryParam status string Фильтр по статусу. Example: completed
     * @queryParam course_id integer Фильтр по ID курса. Example: 5
     *
     * @response 200 scenario="CSV" {
     *   "Content-Type": "text/csv; charset=UTF-8",
     *   "Content-Disposition": "attachment; filename=\"gantt_2026-01-01_2026-12-31.csv\""
     * }
     *
     * @response 200 scenario="JSON" {
     *   "Content-Type": "application/json; charset=UTF-8",
     *   "Content-Disposition": "attachment; filename=\"gantt_2026-01-01_2026-12-31.json\""
     * }
     */
    public function export(GanttExportRequest $request): StreamedResponse
    {
        $from = $request->fromDate();
        $to   = $request->toDate();

        $groups = TrainingGroup::query()
            ->with(['course', 'participants'])
            ->inPeriod($from, $to)
            ->when(
                $request->filled('status'),
                function ($q) use ($request) {
                    $status = TrainingStatus::tryFrom($request->status);
                    if ($status === null) {
                        abort(422, "Недопустимое значение статуса: {$request->status}");
                    }
                    return $q->withStatus($status);
                }
            )
            ->when(
                $request->filled('course_id'),
                fn ($q) => $q->where('course_id', $request->course_id)
            )
            ->orderBy('start_date')
            ->get();

        return match ($request->exportFormat()) {
            'json'  => $this->exportService->exportJson($groups, $from, $to),
            default => $this->exportService->exportCsv($groups, $from, $to),
        };
    }

    /**
     * Обновить цвет группы на Ганте
     *
     * Устанавливает цвет отображения учебной группы на диаграмме Ганта.
     * Цвет должен быть из стандартной палитры (см. `GanttColorService::PALETTE`).
     * Если цвет не передан — назначается автоматически по ID курса.
     *
     * @authenticated
     *
     * @urlParam trainingGroup integer required ID учебной группы. Example: 1
     *
     * @bodyParam color string required HEX-код цвета из палитры. Example: #3B82F6
     *
     * @response 200 {
     *   "success": true,
     *   "message": "OK",
     *   "data": {
     *     "id": 1,
     *     "color": "#3B82F6"
     *   }
     * }
     *
     * @response 403 { "success": false, "message": "Недостаточно прав" }
     * @response 404 { "message": "No query results for model [TrainingGroup]." }
     * @response 422 { "message": "The color must be a valid hex color." }
     */
    public function updateColor(UpdateColorRequest $request, TrainingGroup $trainingGroup): JsonResponse
    {
        $this->authorize('update', $trainingGroup);

        $color = $this->colorService->assignColor($trainingGroup, $request->color);

        return $this->success([
            'id'    => $trainingGroup->id,
            'color' => $color,
        ]);
    }

    /**
     * Вычисляет конфликты для каждой группы в коллекции.
     *
     * Конфликтом считается совпадение `start_date` у двух разных групп одного курса.
     * Результат сохраняется как relation `conflicts` на каждой модели.
     *
     * @param  \Illuminate\Support\Collection  $groups  Коллекция моделей TrainingGroup.
     * @return void
     */
    private function loadConflicts($groups): void
    {
        $byCourse = $groups->groupBy('course_id');

        foreach ($groups as $group) {
            $siblings  = $byCourse->get($group->course_id, collect());
            $conflicts = $siblings->filter(
                fn ($other) => $other->id !== $group->id
                    && (string) $other->start_date === (string) $group->start_date
            );
            $group->setRelation('conflicts', $conflicts);
        }
    }
}