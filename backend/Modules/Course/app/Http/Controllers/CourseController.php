<?php

namespace Modules\Course\Http\Controllers;

use Modules\Core\Abstracts\Http\Controllers\BaseController;
use Modules\Course\Services\CourseService;
use Modules\Course\Transformers\CourseResource;
use Modules\Course\Http\Requests\CourseRequest;
use Illuminate\Http\Request;

/**
 * @group Курсы
 *
 * Управление учебными курсами: создание, обновление, получение списка и удаление.
 */
class CourseController extends BaseController
{

    public function __construct(
        private readonly CourseService $couresService,
    ) {
    }

    /**
     * Список курсов
     *
     * Возвращает список всех курсов с актуальной ценой (lastPrice).
     *
     * @authenticated
     *
     * @response 200 {
     *   "courses": [
     *     {
     *       "id": 1,
     *       "code": "COURSE0001",
     *       "title": "Охрана труда",
     *       "description": "Базовый курс по охране труда",
     *       "duration_days": 3,
     *       "last_price": "4500.00"
     *     }
     *   ]
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function list()
    {
        $result = $this->couresService->list();

        return response()->json([
            'courses' => $result
        ], 200);
    }
    
    /**
     * Создать курс
     *
     * Создаёт новый курс и автоматически устанавливает начальную цену с текущей датой.
     *
     * @authenticated
     *
     * @bodyParam code         string  required Уникальный код курса (ровно 10 символов). Example: COURSE0001
     * @bodyParam title        string  required Название курса. Example: Охрана труда
     * @bodyParam price        numeric required Стоимость курса (два знака после запятой). Example: 4500.00
     * @bodyParam description  string  nullable Описание курса. Example: Базовый курс по охране труда
     * @bodyParam duration_days int    required Продолжительность курса в днях. Example: 3
     *
     * @response 201 {
     *   "message": "Курс создан",
     *   "data": {
     *     "course": {
     *       "id": 1,
     *       "code": "COURSE0001",
     *       "title": "Охрана труда",
     *       "description": "Базовый курс по охране труда",
     *       "duration_days": 3,
     *       "created_at": "2026-04-05T10:00:00.000000Z"
     *     }
     *   }
     * }
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "code": ["validation.unique"]
     *   }
     * }
     *
     * @param  \Modules\Course\Http\Requests\CourseRequest $request Валидированный запрос на создание курса
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CourseRequest $request)
    {
        $result = $this->couresService->create($request->validated());

        return $this->created(
            [
                'course' => new CourseResource($result)
            ],
            'Курс создан'
        );
    }

    /**
     * Мягкое удаление курса
     *
     * Помечает курс как удалённый (soft delete). Запись сохраняется в базе данных
     * и может быть восстановлена.
     *
     * @authenticated
     *
     * @urlParam id integer required Идентификатор курса. Example: 1
     *
     * @response 200 {
     *   "message": "Курс удалён (soft)",
     *   "data": {
     *     "course": {
     *       "id": 1,
     *       "code": "COURSE0001",
     *       "deleted_at": "2026-04-05T12:00:00.000000Z"
     *     }
     *   }
     * }
     * @response 404 {"message": "No query results for model [Course] 99"}
     *
     * @param  int $courseId Идентификатор курса
     * @return \Illuminate\Http\JsonResponse
     */
    public function soft(int $courseId)
    {
        $result = $this->couresService->soft($courseId);

        return $this->success(
            [
                'course' => $result
            ],
            'Курс удалён (soft)'
        );
    }

    /**
     * Жёсткое удаление курса
     *
     * Безвозвратно удаляет курс из базы данных, включая ранее мягко удалённые записи.
     *
     * @authenticated
     *
     * @urlParam id integer required Идентификатор курса. Example: 1
     *
     * @response 200 {
     *   "message": "Курс удалён",
     *   "data": {
     *     "course": {
     *       "id": 1,
     *       "code": "COURSE0001"
     *     }
     *   }
     * }
     * @response 404 {"message": "No query results for model [Course] 99"}
     *
     * @param  int $courseId Идентификатор курса
     * @return \Illuminate\Http\JsonResponse
     */
    public function hard(int $courseId)
    {
        $result = $this->couresService->hard($courseId);

        return $this->success(
            [
                'course' => $result
            ],
            'Курс удалён'
        );
    }

    public function store(Request $request)
    {
    }

    public function show($courseId)
    {
        return view('course::show');
    }

    public function edit($courseId)
    {
        return view('course::edit');
    }

    /**
     * Обновить курс
     *
     * Обновляет данные курса. Если переданная цена отличается от текущей,
     * автоматически создаётся новая запись цены с текущей датой,
     * а предыдущая цена закрывается (valid_to = вчера).
     *
     * @authenticated
     *
     * @urlParam id integer required Идентификатор курса. Example: 1
     *
     * @bodyParam code         string  required Уникальный код курса (ровно 10 символов). Example: COURSE0001
     * @bodyParam title        string  required Название курса. Example: Охрана труда (расширенный)
     * @bodyParam price        numeric required Новая стоимость курса. Example: 5000.00
     * @bodyParam description  string  nullable Описание курса. Example: Расширенный курс
     * @bodyParam duration_days int    required Продолжительность в днях. Example: 5
     *
     * @response 200 {
     *   "message": "Курс обновлён",
     *   "data": {
     *     "updated course": {
     *       "id": 1,
     *       "code": "COURSE0001",
     *       "title": "Охрана труда (расширенный)",
     *       "duration_days": 5
     *     }
     *   }
     * }
     * @response 404 {"message": "No query results for model [Course] 99"}
     *
     * @param  \Modules\Course\Http\Requests\CourseRequest $request Валидированный запрос на обновление курса
     * @param  int $courseId Идентификатор курса
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(CourseRequest $request, int $courseId)
    {
        $result = $this->couresService->update($request->validated(), $courseId);
        
        return $this->success([
            'updated course' => $result],
            'Курс обновлён'
        );
    }

    public function destroy($courseId)
    {
    }
}
