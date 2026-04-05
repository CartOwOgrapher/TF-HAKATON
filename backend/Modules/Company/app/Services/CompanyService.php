<?php

namespace Modules\Company\Services;

use Modules\Company\Models\Company;

/**
 * Сервис для работы с компаниями.
 *
 * Содержит бизнес-логику создания, обновления и удаления компаний.
 */
class CompanyService
{
     /**
     * Получить список всех компаний.
     *
     * Возвращает все записи компаний из базы данных без пагинации и фильтрации.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \Modules\Company\Models\Company>
     */
    public function list()
    {
        return Company::all();
    }

    /**
     * Создать новую компанию.
     *
     * Принимает валидированные данные и сохраняет новую запись компании в базе данных.
     *
     * @param  array{code: string, name: string} $data Валидированные данные компании
     * @return \Modules\Company\Models\Company Созданная модель компании
     */
    public function create(array $data): Company
    {
        return Company::create([
            'code' => $data['code'],
            'name' => $data['name'],
        ]);
    }

    /**
     * Обновить существующую компанию.
     *
     * Находит компанию по идентификатору и обновляет её код и название.
     * Выбрасывает исключение, если запись не найдена.
     *
     * @param  array{code: string, name: string} $data Валидированные данные для обновления
     * @param  int $id Идентификатор компании
     * @return \Modules\Company\Models\Company Обновлённая модель компании
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Если компания с указанным ID не найдена
     */
    public function update(array $data, int $id): Company
    {
        $company = Company::findOrFail($id);
        $company->code = $data['code'];
        $company->name = $data['name'];
        $company->save();

        return $company;
    }

    /**
     * Мягко удалить компанию (soft delete).
     *
     * Устанавливает значение поля `deleted_at`, скрывая запись из стандартных запросов.
     * Запись остаётся в базе данных и может быть восстановлена.
     *
     * @param  int $id Идентификатор компании
     * @return \Modules\Company\Models\Company Модель компании с установленным `deleted_at`
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Если компания с указанным ID не найдена
     */
    public function soft(int $id): Company
    {
        $company = Company::findOrFail($id);
        $company->delete();
        return $company;
    }

    /**
     * Безвозвратно удалить компанию (hard delete).
     *
     * Полностью удаляет запись компании из базы данных, включая ранее мягко удалённые записи.
     * Операция необратима.
     *
     * @param  int $id Идентификатор компании
     * @return \Modules\Company\Models\Company Модель компании до её удаления
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Если компания с указанным ID не найдена (в т.ч. среди удалённых)
     */
    public function hard(int $id): Company
    {
        $company = Company::withTrashed()->findOrFail($id);
        $deleted = $company;
        $company->forceDelete();
        return $deleted;
    }
}