<?php

namespace Modules\Company\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Core\Abstracts\Http\Controllers\BaseController;
use Modules\Company\Services\CompanyService;
use Modules\Company\Transformers\CompanyResource;
use Modules\Company\Http\Requests\CompanyRequest;

/**
 * @group Компании
 *
 * Управление компаниями: создание, обновление, получение списка и удаление.
 */
class CompanyController extends BaseController
{
    public function __construct(private readonly CompanyService $companyService) {}

    /**
     * Список компаний
     *
     * Возвращает список всех компаний без пагинации.
     *
     * @authenticated
     *
     * @response 200 {
     *   "companies": [
     *     {
     *       "id": 1,
     *       "code": "COMP01",
     *       "name": "ООО Ромашка",
     *       "created_at": "2026-04-01T10:00:00.000000Z",
     *       "updated_at": "2026-04-01T10:00:00.000000Z"
     *     }
     *   ]
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function list()
    {
        $companies = $this->companyService->list();
        return response()->json(['companies' => $companies], 200);
    }

    /**
     * Создать компанию
     *
     * Создаёт новую компанию с уникальным кодом и названием.
     *
     * @authenticated
     *
     * @bodyParam code string required Уникальный код компании (до 10 символов). Example: COMP01
     * @bodyParam name string required Название компании (до 255 символов). Example: ООО Ромашка
     *
     * @response 201 {
     *   "message": "Компания создана",
     *   "data": {
     *     "company": {
     *       "id": 1,
     *       "code": "COMP01",
     *       "name": "ООО Ромашка",
     *       "created_at": "2026-04-01T10:00:00.000000Z",
     *       "updated_at": "2026-04-01T10:00:00.000000Z"
     *     }
     *   }
     * }
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "code": ["Компания с таким кодом уже существует."]
     *   }
     * }
     *
     * @param  \Modules\Company\Http\Requests\CompanyRequest $request Валидированный запрос на создание компании
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CompanyRequest $request)
{
    $result = $this->companyService->create($request->validated());

    return $this->created(
        [
            'company' => new CompanyResource($result)
        ],
        'Компания создана'
    );
}

    /**
     * Обновить компанию
     *
     * Обновляет код и название существующей компании по её идентификатору.
     *
     * @authenticated
     *
     * @urlParam id integer required Идентификатор компании. Example: 1
     *
     * @bodyParam code string required Уникальный код компании (до 10 символов). Example: COMP02
     * @bodyParam name string required Название компании (до 255 символов). Example: АО Лютик
     *
     * @response 200 {
     *   "message": "Компания обновлена",
     *   "data": {
     *     "company": {
     *       "id": 1,
     *       "code": "COMP02",
     *       "name": "АО Лютик",
     *       "created_at": "2026-04-01T10:00:00.000000Z",
     *       "updated_at": "2026-04-05T12:00:00.000000Z"
     *     }
     *   }
     * }
     * @response 404 {"message": "No query results for model [Company] 99"}
     *
     * @param  \Modules\Company\Http\Requests\CompanyRequest $request Валидированный запрос на обновление компании
     * @param  int $id Идентификатор компании
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(CompanyRequest $request, int $id)
{
    $result = $this->companyService->update(
        $request->validated(),
        $id
    );

    return $this->success(
        [
            'company' => new CompanyResource($result)
        ],
        'Компания обновлена'
    );
}

    /**
     * Мягкое удаление компании
     *
     * Помечает компанию как удалённую (soft delete), не удаляя запись из базы данных.
     * Запись можно восстановить.
     *
     * @authenticated
     *
     * @urlParam id integer required Идентификатор компании. Example: 1
     *
     * @response 200 {
     *   "message": "Компания удалена (soft)",
     *   "data": {
     *     "company": {
     *       "id": 1,
     *       "code": "COMP01",
     *       "name": "ООО Ромашка",
     *       "deleted_at": "2026-04-05T12:00:00.000000Z"
     *     }
     *   }
     * }
     * @response 404 {"message": "No query results for model [Company] 99"}
     *
     * @param  int $id Идентификатор компании
     * @return \Illuminate\Http\JsonResponse
     */
    public function soft(int $id)
    {
        $company = $this->companyService->soft($id);
        return $this->success(['company' => $company], 'Компания удалена (soft)');
    }

    /**
     * Жёсткое удаление компании
     *
     * Безвозвратно удаляет компанию из базы данных, включая ранее мягко удалённые записи.
     *
     * @authenticated
     *
     * @urlParam id integer required Идентификатор компании. Example: 1
     *
     * @response 200 {
     *   "message": "Компания удалена полностью",
     *   "data": {
     *     "company": {
     *       "id": 1,
     *       "code": "COMP01",
     *       "name": "ООО Ромашка"
     *     }
     *   }
     * }
     * @response 404 {"message": "No query results for model [Company] 99"}
     *
     * @param  int $id Идентификатор компании
     * @return \Illuminate\Http\JsonResponse
     */
    public function hard(int $id)
    {
        $company = $this->companyService->hard($id);
        return $this->success(['company' => $company], 'Компания удалена полностью');
    }
}