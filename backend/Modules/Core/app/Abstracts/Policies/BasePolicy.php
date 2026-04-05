<?php

namespace Modules\Core\Abstracts\Policies;

use Modules\Core\Auth\Models\User;
use Modules\Core\Enums\Role;

abstract class BasePolicy
{
    protected array $permissions = [];

    protected function checkRole(User $user, string $ability): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $allowed = $this->permissions[$ability] ?? [];

        if (empty($allowed)) {
            return true;
        }

        return $user->hasRole(...$allowed);
    }
}