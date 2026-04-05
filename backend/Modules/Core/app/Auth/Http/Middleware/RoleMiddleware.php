<?php

namespace Modules\Core\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Core\Enums\Role;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Не авторизован',
            ], 401);
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        $allowedRoles = array_map(
            fn (string $role) => Role::from($role),
            $roles
        );

        if (! $user->hasRole(...$allowedRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'Недостаточно прав',
            ], 403);
        }

        return $next($request);
    }
}
