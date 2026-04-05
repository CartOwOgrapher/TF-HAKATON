<?php

namespace Modules\Training\Services;

use Modules\Training\Models\TrainingGroup;
use Modules\Training\Models\GroupParticipant;

/**
 * Сервис управления прогрессом обучения.
 *
 * Предоставляет методы для чтения и обновления прогресса
 * как отдельных участников, так и всей группы целиком.
 */
class ProgressService
{
   
    /**
     * Возвращает средний прогресс обучения по группе.
     *
     * Вычисляется как среднее арифметическое `completion_percent`
     * всех участников группы. Если участников нет — возвращает 0.
     *
     * @param  TrainingGroup  $group  Учебная группа.
     * @return float                  Средний процент прохождения (0–100), округлённый до 2 знаков.
     */
    public function getGroupProgress(TrainingGroup $group): float
    {
        $avg = $group->participants()->avg('completion_percent');

        return round($avg ?? 0, 2);
    }

    /**
     * Обновляет прогресс одного участника группы.
     *
     * Значение автоматически ограничивается диапазоном [0, 100].
     * Возвращает перезагруженную модель с актуальными данными.
     *
     * @param  GroupParticipant  $participant  Модель участника.
     * @param  float             $percent      Новый процент прохождения (0–100).
     * @return GroupParticipant               Обновлённый участник.
     */
    public function updateParticipantProgress(GroupParticipant $participant, float $percent): GroupParticipant
    {
        $percent = max(0, min(100, $percent));

        $participant->update(['completion_percent' => $percent]);

        return $participant->fresh();
    }

    /**
     * Устанавливает одинаковый прогресс для всех участников группы.
     *
     * Применяется, например, при массовом завершении или сбросе обучения.
     * Значение автоматически ограничивается диапазоном [0, 100].
     *
     * @param  TrainingGroup  $group    Учебная группа.
     * @param  float          $percent  Процент прохождения для всех участников (0–100).
     * @return void
     */
    public function setGroupProgress(TrainingGroup $group, float $percent): void
    {
        $percent = max(0, min(100, $percent));

        $group->participants()->update(['completion_percent' => $percent]);
    }
}