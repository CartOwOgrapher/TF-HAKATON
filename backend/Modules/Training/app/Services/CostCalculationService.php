<?php

namespace Modules\Training\Services;

use Modules\Training\Models\TrainingGroup;

/**
 * Сервис расчёта стоимости учебной группы.
 *
 * Вычисляет итоговую стоимость группы как произведение
 * актуальной цены курса на количество участников.
 * Вызывается автоматически через {@see GroupParticipantObserver} при изменении состава группы.
 */
class CostCalculationService
{
    /**
     * Вычисляет стоимость учебной группы.
     *
     * Формула: `price_per_person × participants_count`.
     * Если у курса нет цены — стоимость равна 0.
     *
     * @param  TrainingGroup  $group  Модель группы (relations `course` и `participants` должны быть загружены
     *                                или будут запрошены при необходимости).
     * @return float                  Итоговая стоимость, округлённая до 2 знаков.
     */
    public function calculateGroupCost(TrainingGroup $group): float
    {
        $pricePerPerson = $group->course?->price ?? 0;
        $participantsCount = $group->participants()->count();

        return round($pricePerPerson * $participantsCount, 2);
    }

    /**
     * Пересчитывает стоимость группы с принудительным сбросом кешированных relations.
     *
     * Используется для гарантированно актуального результата, когда состав или цена
     * могли измениться после первоначальной загрузки модели.
     *
     * @param  TrainingGroup  $group  Модель группы.
     * @return float                  Актуальная итоговая стоимость.
     */
    public function recalculate(TrainingGroup $group): float
    {
        $group->unsetRelation('participants');
        $group->unsetRelation('course');

        return $this->calculateGroupCost($group);
    }
}
