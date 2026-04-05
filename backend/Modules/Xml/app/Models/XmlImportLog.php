<?php

namespace Modules\Xml\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Лог отдельной операции импорта XML
 *
 * Фиксирует результат обработки одной сущности из пакета.
 *
 * @property-read int $id
 * @property-read int $batch_id ID пакета импорта
 * @property-read string $entity_name Тип сущности (напр. "Employee", "Course")
 * @property-read string $entity_external_id Внешний ID сущности из системы-источника
 * @property-read string $operation_type Тип операции: "create" | "update" | "skip"
 * @property-read string $status Статус: "success" | "error" | "skipped"
 * @property-read string|null $message Текст ошибки или комментарий
 * @property-read \Modules\Xml\Models\XmlImportBatch $batch Пакет, к которому относится лог
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class XmlImportLog extends Model
{
    protected $table = 'xml_import_logs';

    const UPDATED_AT = null;

    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR   = 'error';
    const STATUS_SKIPPED = 'skipped';

    const OP_CREATE = 'create';
    const OP_UPDATE = 'update';
    const OP_SKIP   = 'skip';

    protected $fillable = [
        'batch_id',
        'entity_name',
        'entity_external_id',
        'operation_type',
        'status',
        'message',
    ];

    /**
     * Пакет импорта, к которому относится лог
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(XmlImportBatch::class, 'batch_id');
    }
}