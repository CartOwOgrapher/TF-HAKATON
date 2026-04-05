<?php

namespace Modules\Course\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Abstracts\Models\BaseModel;
use Illuminate\Database\Eloquent\Model; 
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Модель учебного курса.
 *
 * @property int         $id            Уникальный идентификатор
 * @property string      $code          Уникальный код курса (ровно 10 символов)
 * @property string      $title         Название курса
 * @property string|null $description   Описание курса
 * @property int         $duration_days Продолжительность курса в днях
 * @property \Illuminate\Support\Carbon|null $created_at Дата создания
 * @property \Illuminate\Support\Carbon|null $updated_at Дата обновления
 * @property \Illuminate\Support\Carbon|null $deleted_at Дата мягкого удаления
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Modules\Course\Models\CoursePrice> $price       Все записи цен курса
 * @property-read \Modules\Course\Models\CoursePrice|null                                           $lastPrice   Последняя (актуальная) запись цены
 */
class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'courses';
    protected $fillable = [
        'code',
        'title',
        'description',
        'duration_days',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Получить все записи цен курса.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Modules\Course\Models\CoursePrice>
     */
    public function price(): HasMany
    {
        return $this->hasMany(CoursePrice::class, 'course_id');
    }
    
    /**
     * Получить числовое значение последней цены курса.
     *
     * Возвращает значение поля price из самой последней по дате записи цен.
     * Используется для сравнения при обновлении курса.
     *
     * @return string|null Значение цены или null, если цены отсутствуют
     */
    public function getLastPriceNumeric() : ?string
    {
        return $this->price()->latest()->first()['price'];
    }
    
    /**
     * Получить последнюю (актуальную) цену курса.
     *
     * Отношение HasOne с выборкой последней записи по дате (latestOfMany).
     * Используется для eager loading при получении списка курсов.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<\Modules\Course\Models\CoursePrice>
     */
    public function lastPrice(): HasOne
    {
        return $this->hasOne(CoursePrice::class, 'course_id')->latestOfMany();
    }

    
}
