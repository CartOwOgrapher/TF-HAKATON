<?php

namespace Modules\Core\Auth\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\Core\Auth\Models\User;
use Modules\Core\Enums\Role;

/**
 * Сервис аутентификации.
 *
 * Отвечает за регистрацию, вход и выход пользователей.
 * Использует Laravel Sanctum для выдачи и отзыва токенов.
 */
class AuthService
{
    /**
     * Регистрирует нового пользователя.
     *
     * Создаёт пользователя с ролью `HR` по умолчанию
     * и выдаёт ему Sanctum-токен.
     *
     * @param  array  $data  Валидированные данные:
     *                       - `name` (string)
     *                       - `email` (string)
     *                       - `password` (string) — будет хеширован через cast модели
     * @return array         Массив с ключами:
     *                       - `user` (User) — созданная модель пользователя
     *                       - `token` (string) — plain-text Sanctum-токен
     */
    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => Role::HR, 
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return compact('user', 'token');
    }

    /**
     * Аутентифицирует пользователя по email и паролю.
     *
     * Инвалидирует все предыдущие токены пользователя перед выдачей нового.
     *
     * @param  array  $credentials  Валидированные данные:
     *                              - `email` (string)
     *                              - `password` (string)
     * @return array                Массив с ключами:
     *                              - `user` (User)
     *                              - `token` (string) — plain-text Sanctum-токен
     *
     * @throws ValidationException Если пользователь не найден или пароль неверный.
     */
    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Неверный email или пароль.'],
            ]);
        }

        $user->tokens()->delete();

        $token = $user->createToken('auth-token')->plainTextToken;

        return compact('user', 'token');
    }

    /**
     * Выполняет выход пользователя — удаляет текущий токен.
     *
     * @param  User  $user  Аутентифицированный пользователь.
     * @return void
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}