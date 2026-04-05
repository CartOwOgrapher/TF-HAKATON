<?php

namespace Modules\Xml\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Xml\Http\Requests\XmlImportRequest;
use Modules\Xml\Models\XmlImportBatch;
use Modules\Xml\Services\XmlImportService;

/**
 * @group XML Import
 *
 * Управление импортом XML-файлов из внешней системы Global ERP.
 * Поддерживаются типы: участники (Edu_Participant), курсы (Edu_Course), спецификации (Edu_Specification).
 */
class XmlImportController extends Controller
{
    public function __construct(
        private readonly XmlImportService $importService,
    ) {}

        /**
     * Импортировать XML-файл
     *
     * Загружает и обрабатывает XML-файл из внешней системы Global ERP.
     * Тип данных определяется автоматически по корневому тегу XML.
     * Результат каждой операции (создание / обновление / пропуск) фиксируется в лог батча.
     *
     * @authenticated
     *
     * @bodyParam file file required XML-файл для импорта. Поддерживаемые корневые теги: `Edu_Participant`, `Participants`, `Edu_Course`, `Courses`, `Edu_Specification`, `Specifications`.
     *
     * @response 200 {
     *   "message": "Импорт завершён. Обработано файлов: 1",
     *   "batches": [
     *     {
     *       "id": 42,
     *       "source_system": "Global ERP",
     *       "file_name": "employees_2026.xml",
     *       "imported_at": "2026-04-05T11:00:00.000000Z",
     *       "success_count": 15,
     *       "error_count": 1,
     *       "skipped_count": 3,
     *       "logs": [
     *         {
     *           "id": 1,
     *           "entity_name": "Employee",
     *           "entity_external_id": "EMP-001",
     *           "operation_type": "create",
     *           "status": "success",
     *           "message": "Создан сотрудник: Иванов Иван Иванович (код: EMP-001)",
     *           "created_at": "2026-04-05T11:00:00.000000Z"
     *         }
     *       ]
     *     }
     *   ]
     * }
     *
     * @response 422 {
     *   "message": "The file field is required.",
     *   "errors": { "file": ["The file field is required."] }
     * }
     */
    public function import(XmlImportRequest $request): JsonResponse
    {
        $processedBy = $request->user()?->id;
        $results     = [];

        $file = $request->file('file');
            $batch     = $this->importService->importFile($file, $processedBy);
            $results[] = $this->formatBatch($batch, withLogs: true);

        return response()->json([
            'message' => 'Импорт завершён. Обработано файлов: ' . count($results),
            'batches' => $results,
        ]);
    }

        /**
     * Список батчей импорта
     *
     * Возвращает пагинированный список всех батчей импорта, отсортированных по убыванию даты.
     *
     * @authenticated
     *
     * @queryParam per_page int Количество записей на странице. По умолчанию: 20. Example: 20
     *
     * @response 200 {
     *   "current_page": 1,
     *   "data": [
     *     {
     *       "id": 42,
     *       "source_system": "Global ERP",
     *       "file_name": "employees_2026.xml",
     *       "imported_at": "2026-04-05T11:00:00.000000Z",
     *       "success_count": 15,
     *       "error_count": 1,
     *       "skipped_count": 3
     *     }
     *   ],
     *   "per_page": 20,
     *   "total": 100
     * }
     */
    public function batches(Request $request): JsonResponse
    {
        $batches = XmlImportBatch::query()
            ->orderByDesc('imported_at')
            ->paginate($request->integer('per_page', 20));

        $batches->getCollection()->transform(fn($b) => $this->formatBatch($b));

        return response()->json($batches);
    }

        /**
     * Получить батч импорта
     *
     * Возвращает подробную информацию о конкретном батче, включая все записи лога.
     *
     * @authenticated
     *
     * @urlParam batch integer required ID батча импорта. Example: 42
     *
     * @response 200 {
     *   "batch": {
     *     "id": 42,
     *     "source_system": "Global ERP",
     *     "file_name": "employees_2026.xml",
     *     "imported_at": "2026-04-05T11:00:00.000000Z",
     *     "success_count": 15,
     *     "error_count": 1,
     *     "skipped_count": 3,
     *     "logs": []
     *   }
     * }
     *
     * @response 404 { "message": "No query results for model [XmlImportBatch]." }
     */
    public function batchShow(XmlImportBatch $batch): JsonResponse
    {
        return response()->json([
            'batch' => $this->formatBatch($batch, withLogs: true),
        ]);
    }

    /**
     * Логи батча импорта
     *
     * Возвращает пагинированный список записей лога для заданного батча.
     * Поддерживает фильтрацию по статусу и типу сущности.
     *
     * @authenticated
     *
     * @urlParam batch integer required ID батча импорта. Example: 42
     * @queryParam status string Фильтр по статусу: `success`, `error`, `skipped`. Example: error
     * @queryParam entity string Фильтр по имени сущности: `Employee`, `Course`, `Specification`. Example: Employee
     * @queryParam per_page int Количество записей на странице. По умолчанию: 50. Example: 50
     *
     * @response 200 {
     *   "current_page": 1,
     *   "data": [
     *     {
     *       "id": 1,
     *       "batch_id": 42,
     *       "entity_name": "Employee",
     *       "entity_external_id": "EMP-001",
     *       "operation_type": "create",
     *       "status": "error",
     *       "message": "Ошибка БД: ...",
     *       "created_at": "2026-04-05T11:00:00.000000Z"
     *     }
     *   ],
     *   "per_page": 50,
     *   "total": 1
     * }
     *
     * @response 404 { "message": "No query results for model [XmlImportBatch]." }
     */
    public function batchLogs(XmlImportBatch $batch, Request $request): JsonResponse
    {
        $logs = $batch->logs()
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('entity'), fn($q) => $q->where('entity_name', $request->entity))
            ->orderBy('id')
            ->paginate($request->integer('per_page', 50));

        return response()->json($logs);
    }

        /**
     * Форматирует батч в массив для ответа API.
     *
     * @param  XmlImportBatch  $batch    Модель батча.
     * @param  bool            $withLogs Включить записи лога в ответ.
     * @return array
     */
    private function formatBatch(XmlImportBatch $batch, bool $withLogs = false): array
    {
        $data = [
            'id'            => $batch->id,
            'source_system' => $batch->source_system,
            'file_name'     => $batch->file_name,
            'imported_at'   => $batch->imported_at,
            'success_count' => $batch->success_count,
            'error_count'   => $batch->error_count,
            'skipped_count' => $batch->skipped_count,
        ];

        if ($withLogs) {
            $data['logs'] = $batch->logs->map(fn($log) => [
                'id'                 => $log->id,
                'entity_name'        => $log->entity_name,
                'entity_external_id' => $log->entity_external_id,
                'operation_type'     => $log->operation_type,
                'status'             => $log->status,
                'message'            => $log->message,
                'created_at'         => $log->created_at,
            ]);
        }

        return $data;
    }
}