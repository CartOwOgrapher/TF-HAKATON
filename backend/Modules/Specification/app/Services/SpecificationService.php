<?php

namespace Modules\Specification\Services;

use Modules\Specification\Models\Specification;
use Modules\Training\Models\TrainingGroup;
use Illuminate\Database\Eloquent\Collection;

/**
 * Сервис управления спецификациями обучения.
 *
 * Реализует бизнес-логику создания, обновления, удаления спецификаций,
 * а также привязки и отвязки учебных групп.
 */
class SpecificationService
{
    /**
     * Создаёт новую спецификацию.
     *
     * @param  array  $data  Валидированные данные:
     *                       - `number` (string) — уникальный номер
     *                       - `date` (string) — дата в формате YYYY-MM-DD
     *                       - `company_id` (int) — ID компании
     * @return Specification  Созданная модель.
     */
    public function create(array $data): Specification
    {
        return Specification::create($data);
    }

    /**
     * Обновляет данные спецификации.
     *
     * @param  Specification  $specification  Модель для обновления.
     * @param  array          $data           Валидированные данные (те же поля, что и в create).
     * @return Specification                  Обновлённая и перезагруженная модель.
     */
    public function update(Specification $specification, array $data): Specification
    {
        $specification->update($data);
        return $specification->fresh();
    }

    /**
     * Удаляет спецификацию.
     *
     * Перед удалением сбрасывает `specification_id` у всех привязанных групп,
     * чтобы не нарушать ссылочную целостность.
     *
     * @param  Specification  $specification  Модель для удаления.
     * @return bool                           `true` при успешном удалении.
     */
    public function delete(Specification $specification): bool
    {
        $specification->trainingGroups()->update(['specification_id' => null]);

        return $specification->delete();
    }

    /**
     * Привязывает учебную группу к спецификации.
     *
     * Проверяет, что группа ещё не привязана к другой спецификации.
     * Если группа уже привязана к той же спецификации — операция идемпотентна.
     *
     * @param  Specification   $specification  Целевая спецификация.
     * @param  TrainingGroup   $group          Учебная группа для привязки.
     * @return void
     *
     * @throws \DomainException Если группа уже привязана к другой спецификации.
     */
    public function attachGroup(Specification $specification, TrainingGroup $group): void
    {
        if ($group->specification_id !== null && $group->specification_id !== $specification->id) {
            throw new \DomainException(
                "Группа #{$group->id} уже привязана к спецификации #{$group->specification_id}"
            );
        }

        $group->update(['specification_id' => $specification->id]);
    }

    /**
     * Открепляет учебную группу от спецификации.
     *
     * Устанавливает `specification_id = null` у группы.
     *
     * @param  TrainingGroup  $group  Учебная группа для открепления.
     * @return void
     */
    public function detachGroup(TrainingGroup $group): void
    {
        $group->update(['specification_id' => null]);
    }

    /**
     * Возвращает все спецификации компании с загруженными группами.
     *
     * @param  int  $companyId  ID компании.
     * @return Collection       Коллекция спецификаций с relation `trainingGroups`.
     */
    public function getByCompany(int $companyId): Collection
    {
        return Specification::forCompany($companyId)
            ->with('trainingGroups')
            ->get();
    }
}
