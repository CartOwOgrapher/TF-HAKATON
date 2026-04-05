<?php

namespace Modules\Specification\Policies;

use Modules\Core\Abstracts\Policies\BasePolicy;
use Modules\Core\Auth\Models\User;
use Modules\Core\Enums\Role;
use Modules\Specification\Models\Specification;

class SpecificationPolicy extends BasePolicy
{
    protected array $permissions = [
        'viewAny'     => [],
        'view'        => [],
        'create'      => [Role::Accounting, Role::HR],
        'update'      => [Role::Accounting, Role::HR],
        'delete'      => [Role::Accounting],
        'attachGroup' => [Role::Accounting, Role::HR],
        'detachGroup' => [Role::Accounting, Role::HR],
    ];

    public function viewAny(User $user): bool
    {
        return $this->checkRole($user, 'viewAny');
    }

    public function view(User $user, Specification $specification): bool
    {
        return $this->checkRole($user, 'view');
    }

    public function create(User $user): bool
    {
        return $this->checkRole($user, 'create');
    }

    public function update(User $user, Specification $specification): bool
    {
        return $this->checkRole($user, 'update');
    }

    public function delete(User $user, Specification $specification): bool
    {
        return $this->checkRole($user, 'delete');
    }

    public function attachGroup(User $user, Specification $specification): bool
    {
        return $this->checkRole($user, 'attachGroup');
    }

    public function detachGroup(User $user, Specification $specification): bool
    {
        return $this->checkRole($user, 'detachGroup');
    }
}
