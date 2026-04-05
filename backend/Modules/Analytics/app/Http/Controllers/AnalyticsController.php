<?php

namespace Modules\Analytics\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Analytics\Services\AnalyticsService;

/**
 * @group Аналитика
 *
 * Аналитические отчёты по компаниям: сводка по всем компаниям и детализация по отдельной компании.
 */
class AnalyticsController extends Controller
{
    public function __construct(
        private readonly AnalyticsService $analyticsService
    ) {}

    /**
     * Сводная аналитика по всем компаниям
     *
     * Возвращает агрегированные показатели по каждой компании:
     * количество сотрудников, прошедших обучение, количество учебных групп и спецификаций,
     * общую стоимость обучения (с НДС 22% и без), средний прогресс выполнения.
     *
     * @authenticated
     *
     * @response 200 {
     *   "success": true,
     *   "message": "OK",
     *   "data": [
     *     {
     *       "id": 1,
     *       "code": "COMP01",
     *       "name": "ООО Ромашка",
     *       "total_employees": 25,
     *       "trained_employees": 18,
     *       "training_groups_count": 4,
     *       "specifications_count": 3,
     *       "total_cost": 81000.00,
     *       "total_cost_with_vat": 98820.00,
     *       "avg_progress": 74.50
     *     }
     *   ]
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function companies(): JsonResponse
    {
        $data = $this->analyticsService->companySummary();

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data'    => $data,
        ]);
    }

    /**
     * Детальная аналитика по компании
     *
     * Возвращает подробную информацию по конкретной компании:
     * список сотрудников с их прогрессом, список спецификаций
     * и распределение учебных групп по статусам.
     *
     * @authenticated
     *
     * @urlParam id integer required Идентификатор компании. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "OK",
     *   "data": {
     *     "company": {
     *       "id": 1,
     *       "code": "COMP01",
     *       "name": "ООО Ромашка"
     *     },
     *     "employees": [
     *       {
     *         "id": 1,
     *         "full_name": "Иванов Иван Иванович",
     *         "email": "ivanov@example.com",
     *         "groups_count": 2,
     *         "avg_progress": 85.00
     *       }
     *     ],
     *     "specifications": [
     *       {
     *         "id": 1,
     *         "number": "СПЦ-001",
     *         "date": "2026-01-15"
     *       }
     *     ],
     *     "status_distribution": {
     *       "active": 2,
     *       "completed": 1
     *     }
     *   }
     * }
     * @response 404 {"message": "No query results for model [Company] 99"}
     *
     * @param  int $id Идентификатор компании
     * @return \Illuminate\Http\JsonResponse
     */
    public function companyDetail(int $id): JsonResponse
    {
        $data = $this->analyticsService->companyDetail($id);

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data'    => $data,
        ]);
    }
}
