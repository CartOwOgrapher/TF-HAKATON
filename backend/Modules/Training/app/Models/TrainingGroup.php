<?php

namespace Modules\Training\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Contracts\CalculableContract;
use Modules\Training\Enums\TrainingStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Учебная группа (сессия обучения)
 *
 * Представляет конкретный запуск курса с датами, статусом и участниками.
 *
 * @property-read int $id
 * @property-read int $course_id ID курса
 * @property-read int|null $specification_id ID спецификации (опционально)
 * @property-read \Carbon\Carbon $start_date Дата начала
 * @property-read \Carbon\Carbon $end_date Дата окончания
 * @property-read TrainingStatus $status Статус группы
 * @property-read string|null $gantt_color Цвет для отображения в диаграмме Ганта (#RRGGBB)
 * @property-read \Modules\Course\Models\Course $course Курс
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Modules\Training\Models\GroupParticipant> $participants Участники группы
 * @property-read int $participants_count Количество участников
 * @property-read float $price_per_person Цена за одного участника (из курса)
 * @property-read float $group_cost Общая стоимость группы (цена × участники)
 * @property-read float $average_progress Средний прогресс группы в %
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @method static \Illuminate\Database\Eloquent\Builder|TrainingGroup withStatus(TrainingStatus $status) Фильтр по статусу
 * @method static \Illuminate\Database\Eloquent\Builder|TrainingGroup inPeriod(\DateTimeInterface|string $startDate, \DateTimeInterface|string $endDate) Фильтр по периоду (пересечение дат)
 * @method static \Illuminate\Database\Eloquent\Builder|TrainingGroup conflictsWith(TrainingGroup $group) Исключить группы с пересечением дат по тому же курсу
 */
class TrainingGroup extends Model implements CalculableContract
{
    protected $fillable = [
        'course_id',
        'specification_id',
        'start_date',
        'end_date',
        'status',
        'gantt_color',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => TrainingStatus::class,
        ];
    }

    /**
     * Курс, к которому относится группа
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(\Modules\Course\Models\Course::class);
    }

  //  public function specification(): BelongsTo
  //  {
  //      return $this->belongsTo(\Modules\Specification\Models\Specification::class);
  //  }

    /**
     * Участники группы
     */
    public function participants(): HasMany
    {
        return $this->hasMany(GroupParticipant::class);
    }

    /**
     * Количество участников (кэшируемый аттрибут)
     * @attribute
     * @example 25
     */
    public function getParticipantsCountAttribute(): int
    {
        return $this->participants()->count();
    }

    /**
     * Цена за одного участника (берётся из курса)
     * @attribute
     * @example 5000.00
     */
    public function getPricePerPersonAttribute(): float
    {
        return (float) $this->course->getLastPriceNumeric() ?? 0;
    }

    /**
     * Стоимость группы = цена × количество
     * @attribute
     * @example 125000.00
     */
    public function getGroupCostAttribute(): float
    {
        return $this->price_per_person * $this->participants_count;
    }

     /**
     * Средний прогресс участников в %
     * @attribute
     * @example 78.5
     */
    public function getAverageProgressAttribute(): float
    {
        $avg = $this->participants()->avg('completion_percent');

        return round($avg ?? 0, 2);
    }

    /**
     * Сбросить кэш участников для пересчёта
     * @internal
     */
    public function recalculateCost(): void
    {
        $this->unsetRelation('participants');
    }

    /**
     * Интерфейс CalculableContract: вернуть стоимость группы
     */
    public function getTotalCost(): float
    {
        return $this->group_cost;
    }

    public function scopeWithStatus($query, TrainingStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->where('start_date', '<=', $endDate)
                     ->where('end_date', '>=', $startDate);
    }

    public function scopeConflictsWith($query, self $group)
    {
        return $query->where('course_id', $group->course_id)
                     ->where('id', '!=', $group->id)
                     ->inPeriod($group->start_date, $group->end_date);
    }
}
