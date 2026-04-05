<?php

namespace Modules\Employee\Http\Controllers;

use Modules\Core\Abstracts\Http\Controllers\BaseController;
use Modules\Employee\Services\EmployeeService;
use Modules\Employee\Transformers\EmployeeResource;
use Modules\Employee\Http\Requests\EmployeeRequest;

/**
 * @group Сотрудники
 *
 * Управление сотрудниками компаний: создание, обновление, получение списка и удаление.
 */
class EmployeeController extends BaseController
{
    public function __construct(
        private readonly EmployeeService $employeeService,
    ) {
    }

    /**
     * Список сотрудников
     *
     * Возвращает список всех сотрудников с информацией о связанной компании.
     *
     * @authenticated
     *
     * @response 200 {
     *   "employees": [
     *     {
     *       "id": 1,
     *       "employee_code": "EMP001",
     *       "last_name": "Иванов",
     *       "first_name": "Иван",
     *       "middle_name": "Иванович",
     *       "full_name": "Иванов Иван Иванович",
     *       "email": "ivanov@example.com",
     *       "company_id": 1,
     *       "company": {
     *         "id": 1,
     *         "name": "ООО Ромашка"
     *       }
     *     }
     *   ]
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function list()
    {
        $result = $this->employeeService->list();

        return response()->json([
            'employees' => $result
        ], 200);
    }

    /**
     * Создать сотрудника
     *
     * Создаёт нового сотрудника и привязывает его к компании.
     *
     * @authenticated
     *
     * @bodyParam employee_code string  required Уникальный табельный номер (до 50 символов). Example: EMP001
     * @bodyParam last_name     string  required Фамилия (до 100 символов). Example: Иванов
     * @bodyParam first_name    string  required Имя (до 100 символов). Example: Иван
     * @bodyParam middle_name   string  nullable Отчество (до 100 символов). Example: Иванович
     * @bodyParam full_name     string  required Полное ФИО (до 255 символов). Example: Иванов Иван Иванович
     * @bodyParam email         string  nullable Уникальный email сотрудника. Example: ivanov@example.com
     * @bodyParam company_id    integer required Идентификатор компании (должна существовать). Example: 1
     *
     * @response 201 {
     *   "message": "Сотрудник создан",
     *   "data": {
     *     "employee": {
     *       "id": 1,
     *       "employee_code": "EMP001",
     *       "full_name": "Иванов Иван Иванович",
     *       "email": "ivanov@example.com",
     *       "company_id": 1
     *     }
     *   }
     * }
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "employee_code": ["Сотрудник с таким табельным номером уже существует."]
     *   }
     * }
     *
     * @param  \Modules\Employee\Http\Requests\EmployeeRequest $request Валидированный запрос на создание сотрудника
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(EmployeeRequest $request)
    {
        $result = $this->employeeService->create($request->validated());

        return $this->created(
            [
                'employee' => new EmployeeResource($result)
            ],
            'Сотрудник создан'
        );
    }

    /**
     * Обновить сотрудника
     *
     * Обновляет данные существующего сотрудника по его идентификатору.
     *
     * @authenticated
     *
     * @urlParam id integer required Идентификатор сотрудника. Example: 1
     *
     * @bodyParam employee_code string  required Уникальный табельный номер (до 50 символов). Example: EMP002
     * @bodyParam last_name     string  required Фамилия (до 100 символов). Example: Петров
     * @bodyParam first_name    string  required Имя (до 100 символов). Example: Пётр
     * @bodyParam middle_name   string  nullable Отчество (до 100 символов). Example: Петрович
     * @bodyParam full_name     string  required Полное ФИО (до 255 символов). Example: Петров Пётр Петрович
     * @bodyParam email         string  nullable Уникальный email сотрудника. Example: petrov@example.com
     * @bodyParam company_id    integer required Идентификатор компании. Example: 1
     *
     * @response 200 {
     *   "message": "Сотрудник обновлён",
     *   "data": {
     *     "employee": {
     *       "id": 1,
     *       "employee_code": "EMP002",
     *       "full_name": "Петров Пётр Петрович"
     *     }
     *   }
     * }
     * @response 404 {"message": "No query results for model [Employee] 99"}
     *
     * @param  \Modules\Employee\Http\Requests\EmployeeRequest $request Валидированный запрос на обновление
     * @param  int $id Идентификатор сотрудника
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(EmployeeRequest $request, int $id)
    {
        $result = $this->employeeService->update(
            $request->validated(),
            $id
        );

        return $this->success(
            [
                'employee' => new EmployeeResource($result)
            ],
            'Сотрудник обновлён'
        );
    }

    /**
     * Мягкое удаление сотрудника
     *
     * Помечает сотрудника как удалённого (soft delete). Запись остаётся в базе данных
     * и может быть восстановлена.
     *
     * @authenticated
     *
     * @urlParam id integer required Идентификатор сотрудника. Example: 1
     *
     * @response 200 {
     *   "message": "Сотрудник удалён (soft)",
     *   "data": {
     *     "employee": {
     *       "id": 1,
     *       "full_name": "Иванов Иван Иванович",
     *       "deleted_at": "2026-04-05T12:00:00.000000Z"
     *     }
     *   }
     * }
     * @response 404 {"message": "No query results for model [Employee] 99"}
     *
     * @param  int $id Идентификатор сотрудника
     * @return \Illuminate\Http\JsonResponse
     */
    public function soft(int $id)
    {
        $result = $this->employeeService->soft($id);

        return $this->success(
            [
                'employee' => $result
            ],
            'Сотрудник удалён (soft)'
        );
    }

    /**
     * Жёсткое удаление сотрудника
     *
     * Безвозвратно удаляет сотрудника из базы данных, включая ранее мягко удалённые записи.
     *
     * @authenticated
     *
     * @urlParam id integer required Идентификатор сотрудника. Example: 1
     *
     * @response 200 {
     *   "message": "Сотрудник удалён",
     *   "data": {
     *     "employee": {
     *       "id": 1,
     *       "full_name": "Иванов Иван Иванович"
     *     }
     *   }
     * }
     * @response 404 {"message": "No query results for model [Employee] 99"}
     *
     * @param  int $id Идентификатор сотрудника
     * @return \Illuminate\Http\JsonResponse
     */
    public function hard(int $id)
    {
        $result = $this->employeeService->hard($id);

        return $this->success(
            [
                'employee' => $result
            ],
            'Сотрудник удалён'
        );
    }
}