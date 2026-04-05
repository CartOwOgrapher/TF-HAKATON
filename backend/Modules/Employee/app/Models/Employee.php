<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Модель сотрудника.
 *
 * @property int         $id            Уникальный идентификатор
 * @property string      $employee_code Уникальный табельный номер (до 50 символов)
 * @property string      $last_name     Фамилия
 * @property string      $first_name    Имя
 * @property string|null $middle_name   Отчество
 * @property string      $full_name     Полное ФИО
 * @property string|null $email         Email-адрес
 * @property int         $company_id    Идентификатор компании
 * @property \Illuminate\Support\Carbon|null $created_at Дата создания
 * @property \Illuminate\Support\Carbon|null $updated_at Дата обновления
 * @property \Illuminate\Support\Carbon|null $deleted_at Дата мягкого удаления
 *
 * @property-read string $short_name Короткое имя в формате «Фамилия И.О.» (аксессор)
 *
 * @property-read \Modules\Company\Models\Company                                                          $company              Компания сотрудника
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Modules\Specification\Models\Specification> $createdSpecifications Спецификации, созданные сотрудником
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Modules\Training\Models\TrainingGroup>      $createdTrainingGroups Учебные группы, созданные сотрудником
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Modules\Training\Models\GroupParticipant>   $groupParticipants     Участия сотрудника в учебных группах
 */
class Employee extends Model
{
    use SoftDeletes;

    protected $table = 'employees';

    protected $fillable = [
        'employee_code',
        'last_name',
        'first_name',
        'middle_name',
        'full_name',
        'email',
        'company_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Получить компанию сотрудника.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\Modules\Company\Models\Company, \Modules\Employee\Models\Employee>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(
            \Modules\Company\Models\Company::class,
            'company_id'
        );
    }

    /**
     * Получить спецификации, созданные данным сотрудником.
     *
     * Связь по полю created_by в таблице specifications.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Modules\Specification\Models\Specification>
     */
    public function createdSpecifications(): HasMany
    {
        return $this->hasMany(
            \Modules\Specification\Models\Specification::class,
            'created_by'
        );
    }

    /**
     * Получить учебные группы, созданные данным сотрудником.
     *
     * Связь по полю created_by в таблице training_groups.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Modules\Training\Models\TrainingGroup>
     */
    public function createdTrainingGroups(): HasMany
    {
        return $this->hasMany(
            \Modules\Training\Models\TrainingGroup::class,
            'created_by'
        );
    }

     /**
     * Получить записи об участии сотрудника в учебных группах.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Modules\Training\Models\GroupParticipant>
     */
    public function groupParticipants(): HasMany
    {
        return $this->hasMany(
            \Modules\Training\Models\GroupParticipant::class,
            'employee_id'
        );
    }

    /**
     * Аксессор: короткое имя сотрудника в формате «Фамилия И.О.».
     *
     * Строит сокращённое ФИО из фамилии и инициалов.
     * Если отчество отсутствует — возвращает «Фамилия И.».
     *
     * @return string Сокращённое ФИО
     */
    public function getShortNameAttribute(): string
    {
        $firstInitial = $this->first_name
            ? mb_substr($this->first_name, 0, 1) . '.'
            : '';

        $middleInitial = $this->middle_name
            ? mb_substr($this->middle_name, 0, 1) . '.'
            : '';

        return trim("{$this->last_name} {$firstInitial}{$middleInitial}");
    }

    /**
     * Скоуп: фильтрация сотрудников по компании.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query     Билдер запроса
     * @param  int                                   $companyId Идентификатор компании
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}