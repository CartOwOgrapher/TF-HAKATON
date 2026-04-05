<?php

namespace Modules\Course\Http\Controllers;

use Modules\Core\Abstracts\Http\Controllers\BaseController;
use Modules\Course\Services\CoursePriceService;
use Illuminate\Http\Request;
use Modules\Course\Http\Requests\CoursePriceRequest;
use Modules\Course\Transformers\CoursePriceResource;

/**
 * @group Цены курсов
 *
 * Управление историей цен курсов: получение списка и установка новой цены.
 */
class CoursePriceController extends BaseController
{

    public function __construct(
        private readonly CoursePriceService $coursePriceService,
    ) {
    }

    /**
     * Список цен курса
     *
     * Возвращает историю цен указанного курса, отсортированную по дате начала действия (сначала новые).
     *
     * @authenticated
     *
     * @urlParam id integer required Идентификатор курса. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "course_price": [
     *       {
     *         "id": 2,
     *         "course_id": 1,
     *         "price": "5000.00",
     *         "valid_from": "2026-04-01",
     *         "valid_to": null
     *       },
     *       {
     *         "id": 1,
     *         "course_id": 1,
     *         "price": "4500.00",
     *         "valid_from": "2026-01-01",
     *         "valid_to": "2026-03-31"
     *       }
     *     ]
     *   }
     * }
     *
     * @param  int $courseId Идентификатор курса
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(int $courseId) 
    {   
        $result = $this->coursePriceService->list($courseId);
        return $this->success(
            [
                'course_price' => $result
            ]
        );
    }

    public function index()
    {
        return view('course::index');
    }

    /**
     * Установить новую цену курса
     *
     * Создаёт новую запись цены для указанного курса с текущей датой начала действия.
     * Предыдущая активная цена (valid_to = null) автоматически закрывается (valid_to = вчера).
     *
     * @authenticated
     *
     * @urlParam id integer required Идентификатор курса. Example: 1
     *
     * @bodyParam price numeric required Новая стоимость курса (два знака после запятой). Example: 5000.00
     *
     * @response 201 {
     *   "message": "Установлена новая цена курса",
     *   "data": {
     *     "course_price": {
     *       "id": 3,
     *       "course_id": 1,
     *       "price": "5000.00",
     *       "valid_from": "2026-04-05",
     *       "valid_to": null
     *     }
     *   }
     * }
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "price": ["validation.required"]
     *   }
     * }
     *
     * @param  \Modules\Course\Http\Requests\CoursePriceRequest $request Валидированный запрос на установку цены
     * @param  int $courseId Идентификатор курса
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(CoursePriceRequest $request, int $courseId)
    {
        $result = $this->coursePriceService->create($request->validated(), $courseId);

        return $this->created(
            [
                'course_price' => new CoursePriceResource($result)
            ],
            'Установлена новая цена курса'
        );
    }

    public function store(Request $request) {}

    public function show($id)
    {
        return view('course::show');
    }

    public function edit($id)
    {
        return view('course::edit');
    }

    public function destroy($id) {}
}
