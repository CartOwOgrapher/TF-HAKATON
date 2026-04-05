<?php

namespace Modules\Training\Http\Controllers;

use Modules\Core\Abstracts\Http\Controllers\BaseController;
use Modules\Training\Http\Requests\StoreTrainingGroupRequest;
use Modules\Training\Http\Requests\UpdateTrainingGroupRequest;
use Modules\Training\Http\Resources\TrainingGroupResource;
use Modules\Training\Models\TrainingGroup;
use Modules\Training\Services\TrainingGroupService;
use Modules\Training\Enums\TrainingStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Учебные группы
 *
 * CRUD-управление учебными группами, смена статусов и поиск конфликтов по датам.
 */
class TrainingGroupController extends BaseController
{
    public function __construct(
        private readonly TrainingGroupService $service,
    ) {}

    /**
     * Список учебных групп
     *
     * Возвращает пагинированный список учебных групп с курсами и участниками.
     * Поддерживает фильтрацию по статусу.
     *
     * @authenticated
     *
     * @queryParam status string Фильтр по статусу: `planned`, `in_progress`, `completed`, `cancelled`. Example: planned
     * @queryParam per_page integer Количество записей на странице. По умолчанию: 15. Example: 15
     *
     * @response 200 {
     *   "success": true,
     *   "message": "OK",
     *   "data": {
     *     "current_page": 1,
     *     "data": [
     *       {
     *         "id": 1,
     *         "course": { "id": 3, "title": "Охрана труда", "code": "OT-001" },
     *         "start_date": "2026-03-01",
     *         "end_date": "2026-03-05",
     *         "status": "planned",
     *         "status_label": "Запланирована",
     *         "participants_count": 10,
     *         "average_progress": 0
     *       }
     *     ],
     *     "per_page": 15,
     *     "total": 30
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $groups = TrainingGroup::with(['course', 'participants'])
            ->when($request->status, fn ($q, $s) => $q->withStatus(TrainingStatus::from($s)))
            ->paginate($request->per_page ?? 15);

        return $this->success(
            TrainingGroupResource::collection($groups)->response()->getData()
        );
    }

    /**
     * Получить учебную группу
     *
     * Возвращает детальную информацию о группе, включая курс и участников с данными сотрудников.
     *
     * @authenticated
     *
     * @urlParam training_group integer required ID учебной группы. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "OK",
     *   "data": {
     *     "id": 1,
     *     "course": { "id": 3, "title": "Охрана труда" },
     *     "start_date": "2026-03-01",
     *     "end_date": "2026-03-05",
     *     "status": "planned",
     *     "participants": [
     *       {
     *         "id": 10,
     *         "employee": { "id": 5, "full_name": "Иванов Иван Иванович" },
     *         "completion_percent": 0
     *       }
     *     ]
     *   }
     * }
     *
     * @response 404 { "message": "No query results for model [TrainingGroup]." }
     */
    public function show(TrainingGroup $trainingGroup): JsonResponse
    {
        $trainingGroup->load(['course', 'participants.employee']);

        return $this->success(new TrainingGroupResource($trainingGroup));
    }

    /**
     * Создать учебную группу
     *
     * Создаёт новую учебную группу. Статус по умолчанию — `planned`.
     * Требует политику `create` модели TrainingGroup.
     *
     * @authenticated
     *
     * @bodyParam course_id integer required ID курса. Example: 3
     * @bodyParam start_date string required Дата начала (YYYY-MM-DD). Example: 2026-05-01
     * @bodyParam end_date string required Дата окончания (YYYY-MM-DD). Example: 2026-05-10
     * @bodyParam status string Начальный статус. По умолчанию: `planned`. Example: planned
     * @bodyParam specification_id integer ID спецификации. Example: 1
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Создано",
     *   "data": {
     *     "id": 7,
     *     "course": { "id": 3, "title": "Охрана труда" },
     *     "start_date": "2026-05-01",
     *     "end_date": "2026-05-10",
     *     "status": "planned"
     *   }
     * }
     *
     * @response 403 { "success": false, "message": "Недостаточно прав" }
     * @response 422 { "message": "The start date must be a date before end date." }
     */
    public function store(StoreTrainingGroupRequest $request): JsonResponse
    {
        $this->authorize('create', TrainingGroup::class);

        $group = $this->service->create($request->validated());

        return $this->created(new TrainingGroupResource($group->load('course')));
    }

    /**
     * Обновить учебную группу
     *
     * Обновляет данные учебной группы.
     * Требует политику `update` модели TrainingGroup.
     *
     * @authenticated
     *
     * @urlParam training_group integer required ID учебной группы. Example: 1
     *
     * @bodyParam course_id integer ID курса. Example: 3
     * @bodyParam start_date string Дата начала (YYYY-MM-DD). Example: 2026-05-05
     * @bodyParam end_date string Дата окончания (YYYY-MM-DD). Example: 2026-05-15
     * @bodyParam specification_id integer ID спецификации или null. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "OK",
     *   "data": { "id": 1, "start_date": "2026-05-05", "end_date": "2026-05-15" }
     * }
     *
     * @response 403 { "success": false, "message": "Недостаточно прав" }
     * @response 404 { "message": "No query results for model [TrainingGroup]." }
     */
    public function update(UpdateTrainingGroupRequest $request, TrainingGroup $trainingGroup): JsonResponse
    {
        $this->authorize('update', $trainingGroup);

        $group = $this->service->update($trainingGroup, $request->validated());

        return $this->success(new TrainingGroupResource($group->load('course')));
    }

    /**
     * Удалить учебную группу
     *
     * Удаляет учебную группу вместе со всеми её участниками.
     * Требует политику `delete` модели TrainingGroup.
     *
     * @authenticated
     *
     * @urlParam training_group integer required ID учебной группы. Example: 1
     *
     * @response 204
     * @response 403 { "success": false, "message": "Недостаточно прав" }
     * @response 404 { "message": "No query results for model [TrainingGroup]." }
     */
    public function destroy(TrainingGroup $trainingGroup): JsonResponse
    {
        $this->authorize('delete', $trainingGroup);

        $this->service->delete($trainingGroup);

        return $this->noContent();
    }

    /**
     * Сменить статус учебной группы
     *
     * Изменяет статус группы согласно допустимым переходам машины состояний.
     * Недопустимые переходы возвращают ошибку `422`.
     *
     * Допустимые переходы:
     * - `planned` → `in_progress`, `cancelled`
     * - `in_progress` → `completed`, `cancelled`
     * - `completed`, `cancelled` — финальные статусы, переходов нет
     *
     * Требует политику `changeStatus` модели TrainingGroup.
     *
     * @authenticated
     *
     * @urlParam training_group integer required ID учебной группы. Example: 1
     *
     * @bodyParam status string required Новый статус. Допустимые значения: `planned`, `in_progress`, `completed`, `cancelled`. Example: in_progress
     *
     * @response 200 {
     *   "success": true,
     *   "message": "OK",
     *   "data": { "id": 1, "status": "in_progress", "status_label": "В процессе" }
     * }
     *
     * @response 403 { "success": false, "message": "Недостаточно прав" }
     * @response 422 { "message": "Недопустимый переход статуса: Запланирована → Завершена" }
     */
    public function changeStatus(Request $request, TrainingGroup $trainingGroup): JsonResponse
    {
        $this->authorize('changeStatus', $trainingGroup);

        $request->validate(['status' => ['required', 'string']]);

        $group = $this->service->changeStatus(
            $trainingGroup,
            TrainingStatus::from($request->status)
        );

        return $this->success(new TrainingGroupResource($group));
    }

    /**
     * Конфликты учебной группы
     *
     * Возвращает список учебных групп, чьи даты пересекаются с датами указанной группы
     * в рамках одного курса. Используется для диагностики перед назначением дат.
     *
     * @authenticated
     *
     * @urlParam training_group integer required ID учебной группы. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "OK",
     *   "data": [
     *     {
     *       "id": 4,
     *       "course": { "title": "Охрана труда" },
     *       "start_date": "2026-03-01",
     *       "end_date": "2026-03-05",
     *       "status": "planned"
     *     }
     *   ]
     * }
     *
     * @response 404 { "message": "No query results for model [TrainingGroup]." }
     */
    public function conflicts(TrainingGroup $trainingGroup): JsonResponse
    {
        $conflicts = $this->service->findConflicts($trainingGroup);

        return $this->success(TrainingGroupResource::collection($conflicts));
    }
}
