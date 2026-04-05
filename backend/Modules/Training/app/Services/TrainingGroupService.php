<?php

namespace Modules\Training\Services;

use Modules\Core\Abstracts\Services\BaseService;
use Modules\Training\Models\TrainingGroup;
use Modules\Training\Enums\TrainingStatus;
use Illuminate\Database\Eloquent\Collection;

/**
 * Сервис управления учебными группами.
 *
 * Расширяет {@see BaseService} специфичной бизнес-логикой:
 * управление статусами через машину состояний, поиск конфликтов по датам.
 */
class TrainingGroupService extends BaseService
{
    /**
     * Возвращает FQCN модели учебной группы.
     *
     * @return string
     */
    protected function modelClass(): string
    {
        return TrainingGroup::class;
    }

    /**
     * Создаёт новую учебную группу.
     *
     * Если статус не передан — устанавливается `planned` по умолчанию.
     *
     * @param  array  $data  Валидированные данные:
     *                       - `course_id` (int)
     *                       - `start_date` (string)
     *                       - `end_date` (string)
     *                       - `status` (string, опционально)
     *                       - `specification_id` (int|null, опционально)
     * @return TrainingGroup  Созданная модель.
     */
    public function create(array $data): TrainingGroup
    {
        $data['status'] = $data['status'] ?? TrainingStatus::Planned->value;

        return TrainingGroup::create($data);
    }

    /**
     * Изменяет статус учебной группы.
     *
     * Проверяет допустимость перехода через метод {@see TrainingStatus::canTransitionTo()}.
     * При недопустимом переходе выбрасывает исключение.
     *
     * @param  TrainingGroup   $group      Модель группы.
     * @param  TrainingStatus  $newStatus  Целевой статус.
     * @return TrainingGroup              Обновлённая модель.
     *
     * @throws \DomainException Если переход из текущего статуса в целевой не разрешён.
     */
    public function changeStatus(TrainingGroup $group, TrainingStatus $newStatus): TrainingGroup
    {
        if (! $group->status->canTransitionTo($newStatus)) {
            throw new \DomainException(
                "Недопустимый переход статуса: {$group->status->label()} → {$newStatus->label()}"
            );
        }

        $group->update(['status' => $newStatus]);

        return $group->fresh();
    }

    /**
     * Находит учебные группы, конфликтующие по датам с указанной.
     *
     * Использует scope `conflictsWith` модели TrainingGroup.
     * Конфликт определяется как пересечение периодов [start_date, end_date]
     * у разных групп одного курса.
     *
     * @param  TrainingGroup  $group  Группа, для которой ищутся конфликты.
     * @return Collection           Коллекция конфликтующих групп.
     */
    public function findConflicts(TrainingGroup $group): Collection
    {
        return TrainingGroup::conflictsWith($group)->get();
    }

    /**
     * Удаляет учебную группу вместе со всеми её участниками.
     *
     * Сначала удаляет всех участников (что триггерит Observer и пересчёт стоимости),
     * затем удаляет саму группу.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $group  Модель группы для удаления.
     * @return bool                                         `true` при успешном удалении.
     */
    public function delete(\Illuminate\Database\Eloquent\Model $group): bool
    {
        $group->participants()->delete();

        return $group->delete();
    }
}
