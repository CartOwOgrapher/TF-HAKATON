<?php

namespace Modules\Xml\Services\Importers;

use Illuminate\Support\Facades\DB;
use Modules\Xml\Models\XmlImportLog;

/**
 * Импортёр курсов обучения из XML.
 *
 * Реализует логику upsert: создаёт курс, если он отсутствует,
 * или обновляет поля при наличии изменений. Дополнительно синхронизирует
 * актуальную цену в таблице `course_price`.
 */
class CourseImporter
{
    /**
     * Импортирует один курс обучения.
     *
     * Алгоритм:
     * 1. Ищет курс по `code` в таблице `courses` (не удалённый).
     * 2. Если не найден — создаёт (`create`).
     * 3. Если найден и данные изменились — обновляет (`update`).
     * 4. Если ничего не изменилось — пропускает (`skip`).
     * 5. Если передана цена — синхронизирует актуальную запись в `course_price`.
     *
     * @param  array  $data     Данные курса из парсера:
     *                          - `code` (string) — уникальный код курса
     *                          - `title` (string) — название
     *                          - `description` (string|null) — описание
     *                          - `duration_days` (int) — длительность в днях
     *                          - `price` (string|null) — цена за человека (decimal-строка)
     *                          - `external_id` (string) — внешний ID в ERP (для лога)
     * @param  int    $batchId  ID батча (зарезервирован для расширения).
     * @return array            Массив с ключами:
     *                          - `operation_type` (string): `create` | `update` | `skip`
     *                          - `status` (string): `success` | `skipped`
     *                          - `message` (string): описание результата
     */
    public function import(array $data, int $batchId): array
    {
        $existing = DB::table('courses')
            ->where('code', $data['code'])
            ->whereNull('deleted_at')
            ->first();

        $operation = XmlImportLog::OP_SKIP;

        if (! $existing) {
            $courseId = DB::table('courses')->insertGetId([
                'code'          => $data['code'],
                'title'         => $data['title'],
                'description'   => $data['description'],
                'duration_days' => $data['duration_days'],
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            $operation = XmlImportLog::OP_CREATE;
        } else {
            $courseId = $existing->id;

            $courseChanged = $existing->title         !== $data['title']
                          || $existing->description   !== $data['description']
                          || (int) $existing->duration_days !== $data['duration_days'];

            if ($courseChanged) {
                DB::table('courses')
                    ->where('id', $courseId)
                    ->update([
                        'title'         => $data['title'],
                        'description'   => $data['description'],
                        'duration_days' => $data['duration_days'],
                        'updated_at'    => now(),
                    ]);

                $operation = XmlImportLog::OP_UPDATE;
            }
        }

        $priceUpdated = false;

        if ($data['price'] !== null) {
            $priceUpdated = $this->syncPrice($courseId, $data['price']);

            if ($priceUpdated && $operation === XmlImportLog::OP_SKIP) {
                $operation = XmlImportLog::OP_UPDATE;
            }
        }

        if ($operation === XmlImportLog::OP_SKIP) {
            return [
                'operation_type' => XmlImportLog::OP_SKIP,
                'status'         => XmlImportLog::STATUS_SKIPPED,
                'message'        => "Без изменений: {$data['title']} (код: {$data['code']})",
            ];
        }

        $label = $operation === XmlImportLog::OP_CREATE ? 'Создан' : 'Обновлён';

        return [
            'operation_type' => $operation,
            'status'         => XmlImportLog::STATUS_SUCCESS,
            'message'        => "{$label} курс: {$data['title']} (код: {$data['code']})"
                              . ($priceUpdated ? ', цена обновлена' : ''),
        ];
    }

    /**
     * Синхронизирует актуальную цену курса в таблице `course_price`.
     *
     * Если текущая активная цена совпадает с новой — ничего не делает.
     * Если отличается — закрывает текущую запись (устанавливает `valid_to` = вчера)
     * и создаёт новую с `valid_from` = сегодня.
     *
     * @param  int     $courseId  ID курса в таблице `courses`.
     * @param  string  $newPrice  Новая цена в виде строки с двумя десятичными знаками.
     * @return bool               `true` — цена обновлена, `false` — изменений нет.
     */
    private function syncPrice(int $courseId, string $newPrice): bool
    {
        $today     = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        $currentPrice = DB::table('course_price')
            ->where('course_id', $courseId)
            ->where('valid_from', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('valid_to')->orWhere('valid_to', '>=', $today);
            })
            ->orderByDesc('valid_from')
            ->first();

        if ($currentPrice && bccomp((string) $currentPrice->price, $newPrice, 2) === 0) {
            return false;
        }

        if ($currentPrice) {
            DB::table('course_price')
                ->where('id', $currentPrice->id)
                ->update(['valid_to' => $yesterday]);
        }

        DB::table('course_price')->insert([
            'course_id'  => $courseId,
            'price'      => $newPrice,
            'valid_from' => $today,
            'valid_to'   => null,
            'created_at' => now(),
        ]);

        return true;
    }
}