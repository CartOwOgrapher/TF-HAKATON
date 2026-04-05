<?php

namespace Modules\Core\Abstracts\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseService
{
   
    abstract protected function modelClass(): string;

    public function getAll(): Collection
    {
        return $this->modelClass()::all();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->modelClass()::paginate($perPage);
    }

    public function findOrFail(int|string $id): Model
    {
        return $this->modelClass()::findOrFail($id);
    }

    public function create(array $data): Model
    {
        return $this->modelClass()::create($data);
    }

    public function update(Model $model, array $data): Model
    {
        $model->update($data);
        return $model->fresh();
    }

    public function delete(Model $model): bool
    {
        return $model->delete();
    }
}
