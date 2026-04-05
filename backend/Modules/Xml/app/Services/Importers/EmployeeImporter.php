<?php

namespace Modules\Xml\Services\Importers;

use Illuminate\Support\Facades\DB;
use Modules\Xml\Models\XmlImportLog;

/**
 * Импортёр сотрудников (участников обучения) из XML.
 *
 * Реализует логику upsert для сотрудников и автоматически
 * создаёт или обновляет связанную компанию.
 */
class EmployeeImporter
{
    /**
     * Импортирует одного сотрудника.
     *
     * Алгоритм:
     * 1. Находит или создаёт компанию по `company_external_id`.
     * 2. Ищет сотрудника по `employee_code` (не удалённого).
     * 3. Если не найден — создаёт (`create`).
     * 4. Если найден и данные изменились — обновляет (`update`).
     * 5. Если ничего не изменилось — пропускает (`skip`).
     *
     * @param  array  $data     Данные сотрудника из парсера:
     *                          - `employee_code` (string) — табельный номер
     *                          - `last_name` (string) — фамилия
     *                          - `first_name` (string) — имя
     *                          - `middle_name` (string) — отчество
     *                          - `full_name` (string) — ФИО целиком
     *                          - `company_external_id` (string) — внешний код компании
     *                          - `company_name` (string) — название компании
     *                          - `external_id` (string) — внешний ID в ERP (для лога)
     * @param  int    $batchId  ID батча (зарезервирован для расширения).
     * @return array            Массив с ключами:
     *                          - `operation_type` (string): `create` | `update` | `skip`
     *                          - `status` (string): `success` | `skipped`
     *                          - `message` (string): описание результата
     */
    public function import(array $data, int $batchId): array
    {
        $company = DB::table('companies')
            ->where('code', $data['company_external_id'])
            ->first();

        if (! $company) {
            $companyId = DB::table('companies')->insertGetId([
                'code'       => $data['company_external_id'],
                'name'       => $data['company_name'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $companyId = $company->id;

            if ($company->name !== $data['company_name']) {
                DB::table('companies')
                    ->where('id', $companyId)
                    ->update(['name' => $data['company_name'], 'updated_at' => now()]);
            }
        }

        $existing = DB::table('employees')
            ->where('employee_code', $data['employee_code'])
            ->whereNull('deleted_at')
            ->first();

        if (! $existing) {
            DB::table('employees')->insert([
                'employee_code' => $data['employee_code'],
                'last_name'     => $data['last_name'],
                'first_name'    => $data['first_name'],
                'middle_name'   => $data['middle_name'],
                'full_name'     => $data['full_name'],
                'company_id'    => $companyId,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            return [
                'operation_type' => XmlImportLog::OP_CREATE,
                'status'         => XmlImportLog::STATUS_SUCCESS,
                'message'        => "Создан сотрудник: {$data['full_name']} (код: {$data['employee_code']})",
            ];
        }

        $changed = $existing->last_name   !== $data['last_name']
                || $existing->first_name  !== $data['first_name']
                || $existing->middle_name !== $data['middle_name']
                || $existing->full_name   !== $data['full_name']
                || $existing->company_id  !== $companyId;

        if (! $changed) {
            return [
                'operation_type' => XmlImportLog::OP_SKIP,
                'status'         => XmlImportLog::STATUS_SKIPPED,
                'message'        => "Без изменений: {$data['full_name']} (код: {$data['employee_code']})",
            ];
        }

        DB::table('employees')
            ->where('id', $existing->id)
            ->update([
                'last_name'   => $data['last_name'],
                'first_name'  => $data['first_name'],
                'middle_name' => $data['middle_name'],
                'full_name'   => $data['full_name'],
                'company_id'  => $companyId,
                'updated_at'  => now(),
            ]);

        return [
            'operation_type' => XmlImportLog::OP_UPDATE,
            'status'         => XmlImportLog::STATUS_SUCCESS,
            'message'        => "Обновлён сотрудник: {$data['full_name']} (код: {$data['employee_code']})",
        ];
    }
}