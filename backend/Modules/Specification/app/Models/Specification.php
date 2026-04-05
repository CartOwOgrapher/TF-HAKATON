<?php

namespace Modules\Specification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Contracts\CalculableContract;
use Modules\Core\Contracts\HasCompanyScope;
use Modules\Core\Traits\HasCompany;
use Modules\Core\Traits\CalculatesVAT;

/**
 * Спецификация (счёт/документ) на обучение
 *
 * Содержит группы обучения, рассчитывает суммы с НДС и без.
 * Используется для финансового учёта и отчётности.
 *
 * @property-read int $id
 * @property-read string $number Номер спецификации
 * @property-read \Carbon\Carbon $date Дата спецификации
 * @property-read int $company_id ID компании
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Modules\Training\Models\TrainingGroup> $trainingGroups Группы обучения в спецификации
 * @property-read float $total_without_vat Сумма без НДС (агрегировано по группам)
 * @property-read float $vat_amount Сумма НДС
 * @property-read float $total_with_vat Итоговая сумма с НДС
 * @property-read int $groups_count Количество групп в спецификации
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @method static \Illuminate\Database\Eloquent\Builder|Specification whereCompanyId(int $companyId)
 */
class Specification extends Model implements CalculableContract, HasCompanyScope
{
    use HasCompany, CalculatesVAT;

    protected $fillable = [
        'number',
        'date',
        'company_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    /**
     * Группы обучения, входящие в спецификацию
     */
    public function trainingGroups(): HasMany
    {
        return $this->hasMany(\Modules\Training\Models\TrainingGroup::class);
    }

    /**
     * Сумма всех групп без НДС
     * @attribute
     * @example 125000.00
     */
    public function getTotalWithoutVatAttribute(): float
    {
        return $this->trainingGroups->sum(function ($group) {
            return $group->group_cost;
        });
    }

    /**
     * Рассчитанная сумма НДС
     * @attribute
     * @example 25000.00
     */
    public function getVatAmountAttribute(): float
    {
        return $this->calculateVAT($this->total_without_vat);
    }

    /**
     * Итоговая сумма с НДС
     * @attribute
     * @example 150000.00
     */
    public function getTotalWithVatAttribute(): float
    {
        return $this->calculateTotalWithVAT($this->total_without_vat);
    }

    /**
     * Количество групп в спецификации
     * @attribute
     * @example 5
     */
    public function getGroupsCountAttribute(): int
    {
        return $this->trainingGroups()->count();
    }

    /**
     * Сбросить кэшированные отношения для пересчёта стоимости
     * @internal
     */
    public function recalculateCost(): void
    {
        $this->unsetRelation('trainingGroups');
    }

    /**
     * Интерфейс CalculableContract: вернуть итоговую стоимость
     */
    public function getTotalCost(): float
    {
        return $this->total_with_vat;
    }
}
