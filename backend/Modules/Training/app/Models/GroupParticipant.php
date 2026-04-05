<?php

namespace Modules\Training\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Запись участника в учебной группе
 *
 * Связывает сотрудника с группой, хранит прогресс и путь к сертификату.
 *
 * @property-read int $id
 * @property-read int $training_group_id ID учебной группы
 * @property-read int $employee_id ID сотрудника
 * @property-read float $completion_percent Процент завершения (0.00–100.00)
 * @property-read string|null $certificate_path Относительный путь к файлу сертификата в диске 'public'
 * @property-read \Modules\Training\Models\TrainingGroup $trainingGroup Учебная группа
 * @property-read \Modules\Employee\Models\Employee $employee Сотрудник
 * @property-read bool $has_certificate Флаг: есть ли валидный сертификат
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class GroupParticipant extends Model
{
    protected $fillable = [
        'training_group_id',
        'employee_id',
        'completion_percent',
        'certificate_path',      // <-- новое поле
    ];

    protected function casts(): array
    {
        return [
            'completion_percent' => 'float',
        ];
    }

    /**
     * Учебная группа участника
     */
    public function trainingGroup(): BelongsTo
    {
        return $this->belongsTo(TrainingGroup::class);
    }

    /**
     * Сотрудник-участник
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(\Modules\Employee\Models\Employee::class);
    }

    /**
     * Проверка наличия сертификата на диске
     * @attribute
     * @example true
     */
    public function hasCertificate(): bool
    {
        return $this->certificate_path !== null
            && Storage::disk('public')->exists($this->certificate_path);
    }

    /**
     * Удалить сертификат и очистить поле
     * @internal
     */
    public function deleteCertificate(): void
    {
        if ($this->certificate_path) {
            Storage::disk('public')->delete($this->certificate_path);
            $this->update(['certificate_path' => null]);
        }
    }
}