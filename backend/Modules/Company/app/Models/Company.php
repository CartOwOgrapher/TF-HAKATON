<?php

namespace Modules\Company\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Модель компании.
 *
 * @property int    $id          Уникальный идентификатор
 * @property string $code        Уникальный код компании (до 10 символов)
 * @property string $name        Название компании (до 255 символов)
 * @property \Illuminate\Support\Carbon|null $created_at  Дата создания
 * @property \Illuminate\Support\Carbon|null $updated_at  Дата последнего обновления
 * @property \Illuminate\Support\Carbon|null $deleted_at  Дата мягкого удаления
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Modules\Employee\Models\Employee>         $employees       Сотрудники компании
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Modules\Specification\Models\Specification> $specifications  Спецификации компании
 */
class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
    ];
    
    /**
     * Получить сотрудников компании.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Modules\Employee\Models\Employee>
     */
    public function employees(): HasMany
    {
        return $this->hasMany(\Modules\Employee\Models\Employee::class);
    }

    /**
     * Получить спецификации компании.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Modules\Specification\Models\Specification>
     */
    public function specifications(): HasMany
    {
        return $this->hasMany(\Modules\Specification\Models\Specification::class);
    }
}
