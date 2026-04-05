<?php

namespace Modules\Specification\Http\Controllers;

use Modules\Core\Abstracts\Http\Controllers\BaseController;
use Modules\Specification\Http\Requests\StoreSpecificationRequest;
use Modules\Specification\Http\Requests\UpdateSpecificationRequest;
use Modules\Specification\Http\Resources\SpecificationResource;
use Modules\Specification\Models\Specification;
use Modules\Specification\Services\SpecificationService;
use Modules\Training\Models\TrainingGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Спецификации
 *
 * CRUD-управление спецификациями обучения и привязка учебных групп.
 * Спецификация объединяет учебные группы одной компании в рамках одного договора.
 */
class SpecificationController extends BaseController
{
    public function __construct(
        private readonly SpecificationService $specificationService,
    ) {}

    /**
     * Список спецификаций
     *
     * Возвращает пагинированный список спецификаций с вложенными группами и участниками.
     * Поддерживает фильтрацию по компании.
     *
     * @authenticated
     *
     * @queryParam company_id integer Фильтр по ID компании. Example: 3
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
     *         "number": "СП-2026-001",
     *         "date": "2026-01-15",
     *         "company": { "id": 3, "name": "ООО Ромашка" },
     *         "training_groups": []
     *       }
     *     ],
     *     "per_page": 15,
     *     "total": 42
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $specifications = Specification::with(['company', 'trainingGroups.course', 'trainingGroups.participants'])
            ->when($request->company_id, fn ($q, $id) => $q->forCompany($id))
            ->paginate($request->per_page ?? 15);

        return $this->success(
            SpecificationResource::collection($specifications)->response()->getData()
        );
    }

    /**
     * Получить спецификацию
     *
     * Возвращает детальную информацию о спецификации, включая компанию,
     * все учебные группы с курсами и участниками.
     *
     * @authenticated
     *
     * @urlParam specification integer required ID спецификации. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "OK",
     *   "data": {
     *     "id": 1,
     *     "number": "СП-2026-001",
     *     "date": "2026-01-15",
     *     "company": { "id": 3, "name": "ООО Ромашка" },
     *     "training_groups": [
     *       { "id": 5, "course": { "title": "Охрана труда" }, "participants": [] }
     *     ]
     *   }
     * }
     *
     * @response 404 { "message": "No query results for model [Specification]." }
     */
    public function show(Specification $specification): JsonResponse
    {
        $specification->load(['company', 'trainingGroups.course', 'trainingGroups.participants']);

        return $this->success(new SpecificationResource($specification));
    }

    /**
     * Создать спецификацию
     *
     * Создаёт новую спецификацию обучения.
     * Требует роль `accounting` или `hr`.
     *
     * @authenticated
     *
     * @bodyParam number string required Номер спецификации (уникальный). Example: СП-2026-002
     * @bodyParam date string required Дата спецификации (YYYY-MM-DD). Example: 2026-04-01
     * @bodyParam company_id integer required ID компании. Example: 3
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Создано",
     *   "data": {
     *     "id": 2,
     *     "number": "СП-2026-002",
     *     "date": "2026-04-01",
     *     "company": { "id": 3, "name": "ООО Ромашка" },
     *     "training_groups": []
     *   }
     * }
     *
     * @response 403 { "success": false, "message": "Недостаточно прав" }
     * @response 422 { "message": "The number has already been taken." }
     */
    public function store(StoreSpecificationRequest $request): JsonResponse
    {
        $this->authorize('create', Specification::class);

        $specification = $this->specificationService->create($request->validated());

        return $this->created(
            new SpecificationResource($specification->load('company'))
        );
    }

    /**
     * Обновить спецификацию
     *
     * Обновляет данные существующей спецификации.
     * Требует роль `accounting` или `hr`.
     *
     * @authenticated
     *
     * @urlParam specification integer required ID спецификации. Example: 1
     *
     * @bodyParam number string Номер спецификации. Example: СП-2026-001-rev
     * @bodyParam date string Дата (YYYY-MM-DD). Example: 2026-04-05
     * @bodyParam company_id integer ID компании. Example: 3
     *
     * @response 200 {
     *   "success": true,
     *   "message": "OK",
     *   "data": { "id": 1, "number": "СП-2026-001-rev", "date": "2026-04-05" }
     * }
     *
     * @response 403 { "success": false, "message": "Недостаточно прав" }
     * @response 404 { "message": "No query results for model [Specification]." }
     */
    public function update(UpdateSpecificationRequest $request, Specification $specification): JsonResponse
    {
        $this->authorize('update', $specification);

        $specification = $this->specificationService->update($specification, $request->validated());

        return $this->success(
            new SpecificationResource($specification->load('company'))
        );
    }

    /**
     * Удалить спецификацию
     *
     * Удаляет спецификацию. Все привязанные учебные группы открепляются
     * (поле `specification_id` сбрасывается в `null`).
     * Требует роль `accounting`.
     *
     * @authenticated
     *
     * @urlParam specification integer required ID спецификации. Example: 1
     *
     * @response 204
     * @response 403 { "success": false, "message": "Недостаточно прав" }
     * @response 404 { "message": "No query results for model [Specification]." }
     */
    public function destroy(Specification $specification): JsonResponse
    {
        $this->authorize('delete', $specification);

        $this->specificationService->delete($specification);

        return $this->noContent();
    }

    /**
     * Привязать учебную группу к спецификации
     *
     * Устанавливает связь между спецификацией и учебной группой.
     * Группа не может быть привязана к двум спецификациям одновременно —
     * при попытке будет возвращена ошибка `422`.
     * Требует роль `accounting` или `hr`.
     *
     * @authenticated
     *
     * @urlParam specification integer required ID спецификации. Example: 1
     * @urlParam training_group integer required ID учебной группы. Example: 5
     *
     * @response 200 {
     *   "success": true,
     *   "message": "OK",
     *   "data": {
     *     "id": 1,
     *     "number": "СП-2026-001",
     *     "training_groups": [ { "id": 5 } ]
     *   }
     * }
     *
     * @response 403 { "success": false, "message": "Недостаточно прав" }
     * @response 404 { "message": "No query results for model [Specification]." }
     * @response 422 { "message": "Группа #5 уже привязана к спецификации #3" }
     */
    public function attachGroup(Specification $specification, TrainingGroup $trainingGroup): JsonResponse
    {
        $this->authorize('attachGroup', $specification);

        $this->specificationService->attachGroup($specification, $trainingGroup);

        return $this->success(
            new SpecificationResource($specification->load(['company', 'trainingGroups.course', 'trainingGroups.participants']))
        );
    }

    /**
     * Открепить учебную группу от спецификации
     *
     * Сбрасывает `specification_id` у учебной группы (обнуляет связь).
     * Требует роль `accounting` или `hr`.
     *
     * @authenticated
     *
     * @urlParam specification integer required ID спецификации. Example: 1
     * @urlParam training_group integer required ID учебной группы. Example: 5
     *
     * @response 200 {
     *   "success": true,
     *   "message": "OK",
     *   "data": {
     *     "id": 1,
     *     "number": "СП-2026-001",
     *     "training_groups": []
     *   }
     * }
     *
     * @response 403 { "success": false, "message": "Недостаточно прав" }
     * @response 404 { "message": "No query results for model [Specification]." }
     */
    public function detachGroup(Specification $specification, TrainingGroup $trainingGroup): JsonResponse
    {
        $this->authorize('detachGroup', $specification);

        $this->specificationService->detachGroup($trainingGroup);

        return $this->success(
            new SpecificationResource($specification->fresh()->load(['company', 'trainingGroups.course', 'trainingGroups.participants']))
        );
    }
}
