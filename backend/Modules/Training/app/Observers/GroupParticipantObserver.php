<?php

namespace Modules\Training\Observers;

use Modules\Training\Models\GroupParticipant;

/**
 * Observer модели GroupParticipant.
 *
 * Автоматически пересчитывает стоимость учебной группы
 * при любом изменении состава участников (добавление, обновление, удаление).
 *
 * Регистрируется в {@see TrainingServiceProvider}.
 */
class GroupParticipantObserver
{
    /**
     * Реагирует на создание нового участника.
     *
     * Вызывает пересчёт стоимости группы, так как увеличилось число участников.
     *
     * @param  GroupParticipant  $participant  Созданная модель участника.
     * @return void
     */
    public function created(GroupParticipant $participant): void
    {
        $this->refreshGroup($participant);
    }

    /**
     * Реагирует на обновление участника.
     *
     * Вызывает пересчёт стоимости группы (например, при смене `employee_id`).
     *
     * @param  GroupParticipant  $participant  Обновлённая модель участника.
     * @return void
     */
    public function updated(GroupParticipant $participant): void
    {
        $this->refreshGroup($participant);
    }

    /**
     * Реагирует на удаление участника.
     *
     * Вызывает пересчёт стоимости группы, так как уменьшилось число участников.
     *
     * @param  GroupParticipant  $participant  Удалённая модель участника.
     * @return void
     */
    public function deleted(GroupParticipant $participant): void
    {
        $this->refreshGroup($participant);
    }

    /**
     * Инициирует пересчёт стоимости связанной учебной группы.
     *
     * Вызывает метод `recalculateCost()` на модели группы,
     * который делегирует расчёт в {@see CostCalculationService}.
     *
     * @param  GroupParticipant  $participant  Модель участника с загруженным relation `trainingGroup`.
     * @return void
     */
    protected function refreshGroup(GroupParticipant $participant): void
    {
        if ($group = $participant->trainingGroup) {
            $group->recalculateCost();
        }
    }
}