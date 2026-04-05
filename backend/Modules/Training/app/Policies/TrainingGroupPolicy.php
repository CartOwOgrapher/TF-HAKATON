<?php

namespace Modules\Training\Policies;

use Modules\Core\Abstracts\Policies\BasePolicy;
use Modules\Core\Auth\Models\User;
use Modules\Core\Enums\Role;
use Modules\Training\Models\TrainingGroup;

class TrainingGroupPolicy extends BasePolicy
{
    protected array $permissions = [
        'viewAny'        => [],
        'view'           => [],
        'create'         => [Role::HR, Role::TrainingCenter],
        'update'         => [Role::HR, Role::TrainingCenter],
        'delete'         => [Role::HR],
        'updateProgress' => [Role::HR, Role::TrainingCenter],
        'changeStatus'   => [Role::HR, Role::TrainingCenter],
    ];

    public function viewAny(User $user): bool
    {
        return $this->checkRole($user, 'viewAny');
    }

    public function view(User $user, TrainingGroup $group): bool
    {
        return $this->checkRole($user, 'view');
    }

    public function create(User $user): bool
    {
        return $this->checkRole($user, 'create');
    }

    public function update(User $user, TrainingGroup $group): bool
    {
        return $this->checkRole($user, 'update');
    }

    public function delete(User $user, TrainingGroup $group): bool
    {
        if ($group->specification_id !== null) {
            return false;
        }

        return $this->checkRole($user, 'delete');
    }

    public function updateProgress(User $user, TrainingGroup $group): bool
    {
        return $this->checkRole($user, 'updateProgress');
    }

    public function changeStatus(User $user, TrainingGroup $group): bool
    {
        return $this->checkRole($user, 'changeStatus');
    }
}