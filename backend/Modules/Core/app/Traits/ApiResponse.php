<?php

namespace Modules\Core\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Трейт для стандартизации JSON-ответов API
 * 
 * Все методы возвращают ответы в едином формате:
 * - Успешные: { success: true, message: "...", data: {...} }
 * - Ошибки: { success: false, message: "...", errors: {...} }
 * 
 * @package Modules\Core\Traits
 * @mixin \Illuminate\Routing\Controller
 * 
 * @method JsonResponse success(mixed $data = null, string $message = 'OK', int $code = 200) Возвращает успешный ответ
 * @method JsonResponse created(mixed $data = null, string $message = 'Создано') Возвращает ответ 201 Created
 * @method JsonResponse error(string $message = 'Ошибка', int $code = 400, mixed $errors = null) Возвращает ответ с ошибкой
 * @method JsonResponse notFound(string $message = 'Не найдено') Возвращает ответ 404
 * @method JsonResponse noContent() Возвращает ответ 204 без тела
 */
trait ApiResponse
{
    /**
     * Стандартизированный успешный ответ
     * 
     * @param mixed $data Данные для ответа (массив, объект, скаляр)
     * @param string $message Сообщение для клиента
     * @param int $code HTTP-статус код
     * @return JsonResponse
     * 
     * @example {
     *   "success": true,
     *   "message": "OK",
     *   "data": { "id": 1, "name": "Example" }
     * }
     */
    protected function success(mixed $data = null, string $message = 'OK', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Ответ 201 Created для новых ресурсов
     * 
     * @param mixed $data Данные созданного ресурса
     * @param string $message Сообщение (по умолчанию "Создано")
     * @return JsonResponse
     * 
     * @example {
     *   "success": true,
     *   "message": "Создано",
     *   "data": { "id": 42, "created_at": "2024-01-15T10:30:00Z" }
     * }
     */
    protected function created(mixed $data = null, string $message = 'Создано'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    /**
     * Стандартизированный ответ с ошибкой
     * 
     * @param string $message Текст ошибки
     * @param int $code HTTP-статус код (по умолчанию 400)
     * @param mixed $errors Детали ошибок (массив, строка, объект)
     * @return JsonResponse
     * 
     * @example {
     *   "success": false,
     *   "message": "Ошибка валидации",
     *   "errors": { "email": ["Некорректный формат"] }
     * }
     */
    protected function error(string $message = 'Ошибка', int $code = 400, mixed $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

    /**
     * Ответ 404 для несуществующих ресурсов
     * 
     * @param string $message Кастомное сообщение (по умолчанию "Не найдено")
     * @return JsonResponse
     * 
     * @example {
     *   "success": false,
     *   "message": "Пользователь не найден",
     *   "errors": null
     * }
     */
    protected function notFound(string $message = 'Не найдено'): JsonResponse
    {
        return $this->error($message, 404);
    }

    /**
     * Ответ 204 No Content (пустое тело)
     * 
     * Используется для успешных DELETE-запросов или действий без возвращаемых данных.
     * 
     * @return JsonResponse
     */
    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
