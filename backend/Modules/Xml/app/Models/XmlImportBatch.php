<?php

namespace Modules\Xml\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Пакет импорта XML-данных
 *
 * Хранит метаданные о загруженном файле и результаты обработки.
 *
 * @property-read int $id
 * @property-read string $source_system Название системы-источника
 * @property-read string $file_name Имя исходного файла
 * @property-read \Carbon\Carbon $imported_at Дата/время импорта
 * @property-read int|null $processed_by ID пользователя, обработавшего пакет
 * @property-read array|null $raw_payload Исходные данные (JSON/array)
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Modules\Xml\Models\XmlImportLog> $logs Логи операций
 * @property-read \Modules\Core\Auth\Models\User|null $processor Пользователь, выполнивший обработку
 * @property-read int $success_count Количество успешных операций
 * @property-read int $error_count Количество ошибок
 * @property-read int $skipped_count Количество пропущенных записей
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class XmlImportBatch extends Model
{
    protected $table = 'xml_import_batches';

    public $timestamps = false;

    protected $fillable = [
        'source_system',
        'file_name',
        'imported_at',
        'processed_by',
        'raw_payload',
    ];

    protected $casts = [
        'imported_at' => 'datetime',
    ];

    /**
     * Логи операций в рамках пакета
     */
    public function logs(): HasMany
    {
        return $this->hasMany(XmlImportLog::class, 'batch_id');
    }

    /**
     * Пользователь, обработавший пакет
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\Auth\Models\User::class, 'processed_by');
    }

    /**
     * Количество успешных записей
     * @attribute
     * @example 142
     */
    public function getSuccessCountAttribute(): int
    {
        return $this->logs()->where('status', 'success')->count();
    }

    /**
     * Количество ошибок
     * @attribute
     * @example 3
     */
    public function getErrorCountAttribute(): int
    {
        return $this->logs()->where('status', 'error')->count();
    }

    /**
     * Количество пропущенных записей
     * @attribute
     * @example 0
     */
    public function getSkippedCountAttribute(): int
    {
        return $this->logs()->where('status', 'skipped')->count();
    }
}