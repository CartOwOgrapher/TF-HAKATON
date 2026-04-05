<?php

namespace Modules\Course\Services;

use Modules\Course\Models\CoursePrice;
use Modules\Course\Models\Course;
use Modules\Course\Transformers\CoursePriceResource;

/**
 * Сервис для управления историей цен курсов.
 *
 * Обеспечивает получение списка цен и создание новой цены
 * с автоматическим закрытием предыдущей активной цены.
 */
class CoursePriceService
{
    /**
     * Получить историю цен курса.
     *
     * Возвращает все записи цен для указанного курса,
     * отсортированные по дате начала действия в убывающем порядке (сначала новые).
     *
     * @param  int|string $courseId Идентификатор курса
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection<\Modules\Course\Transformers\CoursePriceResource>
     */
    public function list($courseId)
    {
        $coursePrice = CoursePrice::where('course_id', $courseId)
        ->orderBy('valid_from', 'desc')
        ->get();

        return CoursePriceResource::collection($coursePrice);
    }

    /**
     * Создать новую запись цены курса.
     *
     * Перед созданием находит текущую активную цену (valid_to = null)
     * и закрывает её, установив valid_to = вчера.
     * Новая цена создаётся с valid_from = текущая дата и valid_to = null.
     *
     * @param  array{price: string} $data    Валидированные данные с новой ценой
     * @param  int                  $courseId Идентификатор курса
     * @return \Modules\Course\Models\CoursePrice Созданная запись цены
     */
    public function create(array $data, int $courseId)
    {
        $previousPrice = CoursePrice::where('course_id', $courseId)
            ->whereNull('valid_to')
            ->latest('valid_from')
            ->first();
        
        if ($previousPrice) {
            $previousPrice->update([
                'valid_to' => now()->subDay()->toDateString(), 
            ]);
        }
        $coursePrice = CoursePrice::create([
            'course_id'  => $courseId,
            'price'      => $data['price'],
            'valid_from' => now()->toDateString(),
            'valid_to'   => null, 
        ]);

        return $coursePrice;
    }
}
