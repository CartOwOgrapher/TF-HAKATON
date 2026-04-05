<?php

namespace Modules\Core\Auth\Http\Controllers;

use Modules\Core\Abstracts\Http\Controllers\BaseController;
use Modules\Core\Auth\Http\Requests\LoginRequest;
use Modules\Core\Auth\Http\Requests\RegisterRequest;
use Modules\Core\Auth\Http\Resources\UserResource;
use Modules\Core\Auth\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Аутентификация
 *
 * Регистрация, вход, выход и получение профиля текущего пользователя.
 * Используется Sanctum token-based аутентификация.
 */
class AuthController extends BaseController
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    /**
     * Регистрация нового пользователя
     *
     * Создаёт нового пользователя с ролью `HR` и возвращает Sanctum-токен.
     *
     * @unauthenticated
     *
     * @bodyParam name string required Имя пользователя. Example: Иван Иванов
     * @bodyParam email string required Email-адрес. Example: ivan@example.com
     * @bodyParam password string required Пароль (минимум 8 символов). Example: secret123
     * @bodyParam password_confirmation string required Подтверждение пароля. Example: secret123
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Регистрация прошла успешно",
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "Иван Иванов",
     *       "email": "ivan@example.com",
     *       "role": "hr"
     *     },
     *     "token": "1|abc123..."
     *   }
     * }
     *
     * @response 422 {
     *   "message": "The email has already been taken.",
     *   "errors": { "email": ["The email has already been taken."] }
     * }
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return $this->created([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'Регистрация прошла успешно');
    }

    /**
     * Вход в систему
     *
     * Проверяет учётные данные, инвалидирует все предыдущие токены пользователя
     * и выдаёт новый Sanctum-токен.
     *
     * @unauthenticated
     *
     * @bodyParam email string required Email-адрес. Example: ivan@example.com
     * @bodyParam password string required Пароль. Example: secret123
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Вход выполнен",
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "Иван Иванов",
     *       "email": "ivan@example.com",
     *       "role": "hr"
     *     },
     *     "token": "2|xyz789..."
     *   }
     * }
     *
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": { "email": ["Неверный email или пароль."] }
     * }
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return $this->success([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'Вход выполнен');
    }

    /**
     * Выход из системы
     *
     * Инвалидирует текущий токен пользователя.
     *
     * @authenticated
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Выход выполнен",
     *   "data": null
     * }
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->success(message: 'Выход выполнен');
    }

    /**
     * Текущий пользователь
     *
     * Возвращает профиль аутентифицированного пользователя.
     *
     * @authenticated
     *
     * @response 200 {
     *   "success": true,
     *   "message": "OK",
     *   "data": {
     *     "id": 1,
     *     "name": "Иван Иванов",
     *     "email": "ivan@example.com",
     *     "role": "hr"
     *   }
     * }
     *
     * @response 401 { "message": "Unauthenticated." }
     */
    public function me(Request $request): JsonResponse
    {
        return $this->success(
            new UserResource($request->user())
        );
    }
}
