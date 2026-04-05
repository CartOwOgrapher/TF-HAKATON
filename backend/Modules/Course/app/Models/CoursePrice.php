<?php

namespace Modules\Course\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Course\Models\Course;

/**
 * Модель записи цены курса.
 *
 * Хранит историю изменений стоимости курса с датами начала и окончания действия.
 * Активная цена имеет valid_to = null.
 *
 * @property int         $id         Уникальный идентификатор
 * @property int         $course_id  Идентификатор курса
 * @property string      $price      Стоимость курса
 * @property string      $valid_from Дата начала действия цены (YYYY-MM-DD)
 * @property string|null $valid_to   Дата окончания действия цены (null = цена актуальна)
 * @property \Illuminate\Support\Carbon|null $created_at Дата создания
 * @property \Illuminate\Support\Carbon|null $updated_at Дата обновления
 *
 * @property-read \Modules\Course\Models\Course $course Курс, к которому относится запись цены
 */
class CoursePrice extends Model
{
    use HasFactory;

    protected $table = 'course_price';

    protected $fillable = [
        'course_id',
        'price',
        'valid_from',
        'valid_to',
    ];

    /**
     * Получить курс, к которому относится данная запись цены.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\Modules\Course\Models\Course, \Modules\Course\Models\CoursePrice>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

}
