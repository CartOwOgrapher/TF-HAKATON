<?php

namespace Modules\Core\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Трейт для привязки модели к компании и глобального скоупа
 * 
 * Добавляет отношение к компании и скоуп для фильтрации по company_id.
 * Используйте в моделях, которые принадлежат конкретной компании (мульти-тенанси).
 * 
 * @package Modules\Core\Traits
 * 
 * @property-read int|null $company_id ID компании
 * @property-read \Modules\Company\Models\Company|null $company Компания, которой принадлежит запись
 * 
 * @method BelongsTo company() Отношение к модели Company
 * @method static \Illuminate\Database\Eloquent\Builder|static forCompany(int $companyId) Скоуп для фильтрации по компании
 */
trait HasCompany
{
    /**
     * Отношение к компании
     * 
     * @return BelongsTo<\Modules\Company\Models\Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(\Modules\Company\Models\Company::class);
    }

    /**
     * Локальный скоуп для фильтрации записей по компании
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $companyId ID компании
     * @return \Illuminate\Database\Eloquent\Builder
     * 
     * @usage Model::forCompany(1)->get()
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
