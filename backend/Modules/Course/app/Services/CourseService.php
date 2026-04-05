<?php

namespace Modules\Course\Services;

use Modules\Course\Models\Course;
use Modules\Course\Models\CoursePrice;
use Modules\Course\Transformers\CourseResource;
use Modules\Course\Transformers\CourseCompactResource;

use Modules\Course\Services\CoursePriceService;

/**
 * Сервис для управления учебными курсами.
 *
 * Содержит бизнес-логику создания, обновления, удаления курсов,
 * а также автоматическое управление историей цен при обновлении.
 */
class CourseService

{
    public function __construct(
        private readonly CoursePriceService $coursePriceService,
    ) {
    }

    /**
     * Получить список всех курсов с актуальной ценой.
     *
     * Загружает все курсы вместе с отношением lastPrice (последняя активная цена)
     * и возвращает коллекцию в виде компактного ресурса.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection<\Modules\Course\Transformers\CourseCompactResource>
     */
    public function list()
    {
        $courses = Course::with('lastPrice')->get();
        return CourseCompactResource::collection($courses);
    }

    /**
     * Обновить данные курса.
     *
     * Обновляет основные поля курса. Если новая цена отличается от текущей,
     * делегирует создание новой записи цены в CoursePriceService
     * (предыдущая цена при этом закрывается автоматически).
     *
     * @param  array{code: string, title: string, price: string, description: string|null, duration_days: int} $data Валидированные данные
     * @param  int $courseId Идентификатор курса
     * @return \Modules\Course\Models\Course Обновлённая модель курса
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Если курс с указанным ID не найден
     */
    public function update(array $data, int $courseId) 
    {

        $course = Course::findOrFail($courseId);

        $course->code = $data['code'];
        $course->title = $data['title'];

        $currentCoursePrice = $course->getLastPriceNumeric();

        if($currentCoursePrice != $data['price'])
        {
            $this->coursePriceService->create($data, $courseId);
        }
      
        $course->description = $data['description'];
        $course->duration_days = $data['duration_days'];

        $course->save();
        
        return $course;
    }

    /**
     * Создать новый курс с начальной ценой.
     *
     * Создаёт запись курса и автоматически добавляет первую запись цены
     * с valid_from = текущая дата и valid_to = null.
     *
     * @param  array{code: string, title: string, price: string, description: string|null, duration_days: int} $data Валидированные данные
     * @return \Modules\Course\Models\Course Созданная модель курса
     */
    public function create(array $data)
    {

        $course = Course::create([
            'code' => $data['code'],
            'title' => $data['title'],
            'description' => $data['description'],
            'duration_days' => $data['duration_days'],
        ]);

        $coursePrice = CoursePrice::create([
            'course_id' => $course->id,
            'price' => $data['price'],
            'valid_from' => now()->toDateString(),
            'valid_to' => null,
        ]);

        return $course;
    }

    /**
     * Мягко удалить курс (soft delete).
     *
     * Устанавливает deleted_at, скрывая запись из стандартных запросов.
     * Запись можно восстановить.
     *
     * @param  int $id Идентификатор курса
     * @return \Modules\Course\Models\Course Модель курса с установленным deleted_at
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Если курс не найден
     */
    public function soft(int $id)
    {
        $course = Course::findOrFail($id);

        $course->delete();

        return $course;
    }

    /**
     * Безвозвратно удалить курс (hard delete).
     *
     * Полностью удаляет запись курса из базы данных, включая ранее мягко удалённые.
     * Операция необратима.
     *
     * @param  int $id Идентификатор курса
     * @return \Modules\Course\Models\Course Модель курса до удаления
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Если курс не найден (в т.ч. среди удалённых)
     */
    public function hard(int $id) 
    {
        $course = Course::withTrashed()->findOrFail($id);
        
        $deleted = $course;

        $course->forceDelete();

        return $deleted;
    }
}