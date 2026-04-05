<?php

namespace Modules\Xml\Services\Importers;

use Illuminate\Support\Facades\DB;
use Modules\Xml\Models\XmlImportLog;

/**
 * Импортёр спецификаций обучения из XML.
 *
 * Реализует логику upsert для спецификаций. При каждом импорте
 * полностью пересоздаёт учебные группы и участников спецификации
 * (delete + insert), чтобы гарантировать синхронизацию с ERP.
 */
class SpecificationImporter
{
    /**
     * Импортирует одну спецификацию обучения.
     *
     * Алгоритм:
     * 1. Проверяет наличие `company_code` — без него спецификация не может быть привязана.
     * 2. Находит или создаёт компанию.
     * 3. Находит или создаёт запись спецификации по `number`; при изменении даты или компании — обновляет.
     * 4. Удаляет все существующие группы и участников этой спецификации.
     * 5. Создаёт группы заново со всеми участниками.
     *
     * @param  array  $data     Данные спецификации из парсера:
     *                          - `number` (string) — уникальный номер спецификации
     *                          - `date` (string|null) — дата в формате `YYYY-MM-DD`
     *                          - `company_code` (string) — внешний код компании
     *                          - `company_name` (string) — название компании
     *                          - `groups` (array) — массив учебных групп (см. {@see createGroup()})
     * @param  int    $batchId  ID батча (зарезервирован для расширения).
     * @return array            Массив с ключами:
     *                          - `operation_type` (string): `create` | `update` | `skip`
     *                          - `status` (string): `success` | `error`
     *                          - `message` (string): описание результата
     */
    public function import(array $data, int $batchId): array
    {
        if (empty($data['company_code'])) {
            return [
                'operation_type' => XmlImportLog::OP_SKIP,
                'status'         => XmlImportLog::STATUS_ERROR,
                'message'        => "Спецификация №{$data['number']}: отсутствует <idOrganization>.",
            ];
        }

        $companyId = $this->resolveCompany($data['company_code'], $data['company_name']);

        $existing = DB::table('specifications')
            ->where('number', $data['number'])
            ->first();

        $operation = XmlImportLog::OP_SKIP;

        if (! $existing) {
            $specificationId = DB::table('specifications')->insertGetId([
                'number'     => $data['number'],
                'date'       => $data['date'],
                'company_id' => $companyId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $operation = XmlImportLog::OP_CREATE;
        } else {
            $specificationId = $existing->id;

            $changed = $existing->date       !== $data['date']
                    || $existing->company_id !== $companyId;

            if ($changed) {
                DB::table('specifications')
                    ->where('id', $specificationId)
                    ->update([
                        'date'       => $data['date'],
                        'company_id' => $companyId,
                        'updated_at' => now(),
                    ]);
            }

            $operation = XmlImportLog::OP_UPDATE;
        }

        $oldGroupIds = DB::table('training_groups')
            ->where('specification_id', $specificationId)
            ->pluck('id');

        if ($oldGroupIds->isNotEmpty()) {
            DB::table('group_participants')
                ->whereIn('training_group_id', $oldGroupIds)
                ->delete();

            DB::table('training_groups')
                ->whereIn('id', $oldGroupIds)
                ->delete();
        }

        foreach ($data['groups'] as $groupData) {
            $this->createGroup($groupData, $specificationId);
        }

        $label = $operation === XmlImportLog::OP_CREATE ? 'Создана' : 'Обновлена';

        return [
            'operation_type' => $operation,
            'status'         => XmlImportLog::STATUS_SUCCESS,
            'message'        => "{$label} спецификация №{$data['number']} "
                              . '(групп: ' . count($data['groups']) . ')',
        ];
    }

    /**
     * Создаёт учебную группу и добавляет в неё участников.
     *
     * Разрешает курс через {@see resolveCourse()}, создаёт запись группы,
     * затем пакетно вставляет всех участников в `group_participants`.
     *
     * @param  array  $groupData        Данные группы из парсера:
     *                                  - `course` (array) — данные курса
     *                                  - `start_date` (string|null)
     *                                  - `end_date` (string|null)
     *                                  - `status` (string)
     *                                  - `participants` (array) — массив данных участников
     * @param  int    $specificationId  ID спецификации, к которой привязывается группа.
     * @return void
     */
    private function createGroup(array $groupData, int $specificationId): void
    {
        $courseId = $this->resolveCourse($groupData['course']);

        $groupId = DB::table('training_groups')->insertGetId([
            'specification_id'   => $specificationId,
            'course_id'          => $courseId,
            'start_date'         => $groupData['start_date'],
            'end_date'           => $groupData['end_date'],
            'status'             => $groupData['status'],
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        if (empty($groupData['participants'])) {
            return;
        }

        $now  = now();
        $rows = [];

        foreach ($groupData['participants'] as $participantData) {
            $rows[] = [
                'training_group_id'  => $groupId,
                'employee_id'        => $this->resolveEmployee($participantData),
                'completion_percent' => 0,
                'created_at'         => $now,
                'updated_at'         => $now,
            ];
        }

        DB::table('group_participants')->insert($rows);
    }

    /**
     * Находит или создаёт компанию по коду; при изменении названия — обновляет.
     *
     * @param  string  $code  Внешний код компании из ERP.
     * @param  string  $name  Название компании.
     * @return int            ID компании в таблице `companies`.
     */
    private function resolveCompany(string $code, string $name): int
    {
        $company = DB::table('companies')
            ->where('code', $code)
            ->first();

        if (! $company) {
            return DB::table('companies')->insertGetId([
                'code'       => $code,
                'name'       => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($company->name !== $name) {
            DB::table('companies')
                ->where('id', $company->id)
                ->update(['name' => $name, 'updated_at' => now()]);
        }

        return $company->id;
    }

    /**
     * Находит или создаёт курс по коду; при изменении полей — обновляет.
     * Дополнительно синхронизирует цену через {@see syncPrice()}.
     *
     * @param  array  $data  Данные курса:
     *                       - `code` (string), `title` (string), `description` (string|null),
     *                         `duration_days` (int), `price` (string|null)
     * @return int            ID курса в таблице `courses`.
     */
    private function resolveCourse(array $data): int
    {
        $existing = DB::table('courses')
            ->where('code', $data['code'])
            ->whereNull('deleted_at')
            ->first();

        if (! $existing) {
            $courseId = DB::table('courses')->insertGetId([
                'code'          => $data['code'],
                'title'         => $data['title'],
                'description'   => $data['description'],
                'duration_days' => $data['duration_days'],
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        } else {
            $courseId = $existing->id;

            $changed = $existing->title         !== $data['title']
                    || $existing->description   !== $data['description']
                    || (int) $existing->duration_days !== $data['duration_days'];

            if ($changed) {
                DB::table('courses')
                    ->where('id', $courseId)
                    ->update([
                        'title'         => $data['title'],
                        'description'   => $data['description'],
                        'duration_days' => $data['duration_days'],
                        'updated_at'    => now(),
                    ]);
            }
        }

        if ($data['price'] !== null) {
            $this->syncPrice($courseId, $data['price']);
        }

        return $courseId;
    }

    /**
     * Синхронизирует актуальную цену курса в таблице `course_price`.
     *
     * Если текущая активная цена совпадает с новой — пропускает.
     * Иначе закрывает текущую запись (valid_to = вчера) и создаёт новую.
     *
     * @param  int     $courseId  ID курса.
     * @param  string  $newPrice  Новая цена (decimal-строка).
     * @return void
     */
    private function syncPrice(int $courseId, string $newPrice): void
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
            return;
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
    }

    /**
     * Находит или создаёт сотрудника по табельному номеру; при изменении данных — обновляет.
     *
     * Также вызывает {@see resolveCompany()} для привязки к компании.
     *
     * @param  array  $data  Данные участника:
     *                       - `employee_code`, `last_name`, `first_name`, `middle_name`,
     *                         `full_name`, `company_code`, `company_name`
     * @return int            ID сотрудника в таблице `employees`.
     */
    private function resolveEmployee(array $data): int
    {
        $companyId = $this->resolveCompany($data['company_code'], $data['company_name']);

        $employee = DB::table('employees')
            ->where('employee_code', $data['employee_code'])
            ->whereNull('deleted_at')
            ->first();

        if (! $employee) {
            return DB::table('employees')->insertGetId([
                'employee_code' => $data['employee_code'],
                'last_name'     => $data['last_name'],
                'first_name'    => $data['first_name'],
                'middle_name'   => $data['middle_name'],
                'full_name'     => $data['full_name'],
                'company_id'    => $companyId,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }

        $changed = $employee->last_name   !== $data['last_name']
                || $employee->first_name  !== $data['first_name']
                || $employee->middle_name !== $data['middle_name']
                || $employee->full_name   !== $data['full_name']
                || $employee->company_id  !== $companyId;

        if ($changed) {
            DB::table('employees')
                ->where('id', $employee->id)
                ->update([
                    'last_name'   => $data['last_name'],
                    'first_name'  => $data['first_name'],
                    'middle_name' => $data['middle_name'],
                    'full_name'   => $data['full_name'],
                    'company_id'  => $companyId,
                    'updated_at'  => now(),
                ]);
        }

        return $employee->id;
    }
}