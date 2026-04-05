<?php

namespace Modules\Gantt\Services;

/**
 * Сервис управления цветами элементов на диаграмме Ганта.
 *
 * Назначает и сохраняет цвет (`gantt_color`) для учебных групп.
 * По умолчанию цвет определяется детерминированно по `course_id` (mod PALETTE).
 * Пользователь может переопределить цвет вручную.
 */
class GanttColorService
{
    /**
     * Стандартная палитра цветов диаграммы Ганта (Tailwind 500).
     *
     * @var string[]
     */
    public const PALETTE = [
        '#3B82F6', // blue-500
        '#10B981', // emerald-500
        '#F59E0B', // amber-500
        '#EF4444', // red-500
        '#8B5CF6', // violet-500
        '#EC4899', // pink-500
        '#06B6D4', // cyan-500
        '#84CC16', // lime-500
        '#F97316', // orange-500
        '#6366F1', // indigo-500
    ];

    /**
     * Возвращает детерминированный цвет для курса.
     *
     * Цвет вычисляется как `PALETTE[courseId % count(PALETTE)]`,
     * что гарантирует одинаковый цвет для одного курса при каждом вызове.
     *
     * @param  int  $courseId  ID курса.
     * @return string          HEX-код цвета из палитры.
     */
    public function colorForCourse(int $courseId): string
    {
        return self::PALETTE[$courseId % count(self::PALETTE)];
    }

    /**
     * Назначает и сохраняет цвет для учебной группы.
     *
     * Если `$override` не передан — цвет вычисляется автоматически по `course_id`.
     * Значение сохраняется в поле `gantt_color` модели.
     *
     * @param  object       $group     Модель TrainingGroup.
     * @param  string|null  $override  HEX-код цвета из палитры или `null` для автоматического выбора.
     * @return string                  Итоговый HEX-код цвета.
     */
    public function assignColor($group, ?string $override = null): string
    {
        $color = $override ?? $this->colorForCourse($group->course_id);

        $group->gantt_color = $color;
        $group->save();

        return $color;
    }

    /**
     * Возвращает полную палитру цветов.
     *
     * @return string[]
     */
    public function getPalette(): array
    {
        return self::PALETTE;
    }
}