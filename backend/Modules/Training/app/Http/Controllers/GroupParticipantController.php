<?php

namespace Modules\Training\Http\Controllers;

use Modules\Core\Abstracts\Http\Controllers\BaseController;
use Modules\Training\Http\Requests\StoreGroupParticipantRequest;
use Modules\Training\Http\Requests\UpdateGroupParticipantRequest;
use Modules\Training\Http\Resources\GroupParticipantResource;
use Modules\Training\Models\TrainingGroup;
use Modules\Training\Models\GroupParticipant;
use Modules\Training\Services\ProgressService;
use Illuminate\Http\JsonResponse;

/**
 * @group Участники учебной группы
 *
 * Управление составом участников учебной группы и их прогрессом.
 */
class GroupParticipantController extends BaseController
{
    public function __construct(
        private readonly ProgressService $progressService,
    ) {}

    /**
     * Список участников группы
     *
     * Возвращает всех участников указанной учебной группы с данными сотрудников.
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
     *       "id": 10,
     *       "employee": {
     *         "id": 5,
     *         "full_name": "Иванов Иван Иванович",
     *         "employee_code": "EMP-001"
     *       },
     *       "completion_percent": 75,
     *       "certificate_path": null
     *     }
     *   ]
     * }
     *
     * @response 404 { "message": "No query results for model [TrainingGroup]." }
     */
    public function index(TrainingGroup $trainingGroup): JsonResponse
    {
        $participants = $trainingGroup->participants()->with('employee')->get();

        return $this->success(GroupParticipantResource::collection($participants));
    }

    /**
     * Добавить участника в группу
     *
     * Добавляет сотрудника в учебную группу с начальным прогрессом 0%.
     * После добавления автоматически пересчитывается стоимость группы (через Observer).
     * Требует политику `update` учебной группы.
     *
     * @authenticated
     *
     * @urlParam training_group integer required ID учебной группы. Example: 1
     *
     * @bodyParam employee_id integer required ID сотрудника. Example: 5
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Создано",
     *   "data": {
     *     "id": 11,
     *     "employee": { "id": 5, "full_name": "Петров Пётр Петрович" },
     *     "completion_percent": 0,
     *     "certificate_path": null
     *   }
     * }
     *
     * @response 403 { "success": false, "message": "Недостаточно прав" }
     * @response 404 { "message": "No query results for model [TrainingGroup]." }
     * @response 422 { "message": "The employee id field is required." }
     */
    public function store(StoreGroupParticipantRequest $request, TrainingGroup $trainingGroup): JsonResponse
    {
        $this->authorize('update', $trainingGroup);

        $participant = $trainingGroup->participants()->create([
            'employee_id' => $request->employee_id,
            'completion_percent' => 0,
        ]);

        return $this->created(
            new GroupParticipantResource($participant->load('employee'))
        );
    }

    /**
     * Обновить прогресс участника
     *
     * Устанавливает процент прохождения обучения для участника.
     * Значение автоматически ограничивается диапазоном [0, 100].
     * После обновления пересчитывается средний прогресс группы.
     * Требует политику `updateProgress` учебной группы.
     *
     * @authenticated
     *
     * @urlParam training_group integer required ID учебной группы. Example: 1
     * @urlParam participant integer required ID записи участника группы. Example: 10
     *
     * @bodyParam completion_percent number required Процент прохождения (0–100). Example: 75
     *
     * @response 200 {
     *   "success": true,
     *   "message": "OK",
     *   "data": {
     *     "id": 10,
     *     "employee": { "id": 5, "full_name": "Иванов Иван Иванович" },
     *     "completion_percent": 75,
     *     "certificate_path": null
     *   }
     * }
     *
     * @response 403 { "success": false, "message": "Недостаточно прав" }
     * @response 404 { "message": "No query results for model [GroupParticipant]." }
     * @response 422 { "message": "The completion percent must be between 0 and 100." }
     */
    public function update(
        UpdateGroupParticipantRequest $request,
        TrainingGroup $trainingGroup,
        GroupParticipant $participant,
    ): JsonResponse {
        $this->authorize('updateProgress', $trainingGroup);

        $participant = $this->progressService->updateParticipantProgress(
            $participant,
            $request->completion_percent
        );

        return $this->success(new GroupParticipantResource($participant));
    }

    /**
     * Удалить участника из группы
     *
     * Исключает участника из учебной группы.
     * После удаления автоматически пересчитывается стоимость группы (через Observer).
     * Требует политику `update` учебной группы.
     *
     * @authenticated
     *
     * @urlParam training_group integer required ID учебной группы. Example: 1
     * @urlParam participant integer required ID записи участника группы. Example: 10
     *
     * @response 204
     * @response 403 { "success": false, "message": "Недостаточно прав" }
     * @response 404 { "message": "No query results for model [GroupParticipant]." }
     */
    public function destroy(TrainingGroup $trainingGroup, GroupParticipant $participant): JsonResponse
    {
        $this->authorize('update', $trainingGroup);

        $participant->delete();

        return $this->noContent();
    }
}
