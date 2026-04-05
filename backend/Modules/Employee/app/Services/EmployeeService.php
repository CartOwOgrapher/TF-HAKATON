<?php

namespace Modules\Employee\Services;

use Modules\Employee\Models\Employee;

/**
 * Сервис для управления сотрудниками.
 *
 * Содержит бизнес-логику создания, обновления и удаления сотрудников компаний.
 */
class EmployeeService
{
    /**
     * Получить список всех сотрудников с данными компании.
     *
     * Загружает всех сотрудников вместе с отношением company (eager loading).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \Modules\Employee\Models\Employee>
     */
    public function list()
    {
        return Employee::with('company')->get();
    }

    /**
     * Создать нового сотрудника.
     *
     * Принимает валидированные данные и создаёт запись сотрудника через массовое присвоение.
     *
     * @param  array{
     *     employee_code: string,
     *     last_name: string,
     *     first_name: string,
     *     middle_name: string|null,
     *     full_name: string,
     *     email: string|null,
     *     company_id: int
     * } $data Валидированные данные сотрудника
     * @return \Modules\Employee\Models\Employee Созданная модель сотрудника
     */
    public function create(array $data): Employee
    {
        return Employee::create($data);
    }

    /**
     * Обновить данные существующего сотрудника.
     *
     * Находит сотрудника по идентификатору и обновляет поля через массовое присвоение.
     *
     * @param  array{
     *     employee_code: string,
     *     last_name: string,
     *     first_name: string,
     *     middle_name: string|null,
     *     full_name: string,
     *     email: string|null,
     *     company_id: int
     * } $data Валидированные данные для обновления
     * @param  int $id Идентификатор сотрудника
     * @return \Modules\Employee\Models\Employee Обновлённая модель сотрудника
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Если сотрудник не найден
     */
    public function update(array $data, int $id): Employee
    {
        $employee = Employee::findOrFail($id);

        $employee->update($data);

        return $employee;
    }

    /**
     * Мягко удалить сотрудника (soft delete).
     *
     * Устанавливает deleted_at, скрывая запись из стандартных запросов.
     * Запись можно восстановить.
     *
     * @param  int $id Идентификатор сотрудника
     * @return \Modules\Employee\Models\Employee Модель сотрудника с установленным deleted_at
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Если сотрудник не найден
     */
    public function soft(int $id): Employee
    {
        $employee = Employee::findOrFail($id);

        $employee->delete();

        return $employee;
    }

    /**
     * Безвозвратно удалить сотрудника (hard delete).
     *
     * Полностью удаляет запись из базы данных, включая ранее мягко удалённые.
     * Перед удалением создаёт клон модели для возврата данных. Операция необратима.
     *
     * @param  int $id Идентификатор сотрудника
     * @return \Modules\Employee\Models\Employee Копия модели сотрудника до удаления
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Если сотрудник не найден (в т.ч. среди удалённых)
     */
    public function hard(int $id): Employee
    {
        $employee = Employee::withTrashed()->findOrFail($id);

        $deleted = clone $employee;

        $employee->forceDelete();

        return $deleted;
    }
}