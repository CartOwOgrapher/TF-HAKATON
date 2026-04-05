<?php

namespace Modules\Analytics\Services;

use Illuminate\Support\Facades\DB;
use Modules\Company\Models\Company;

/**
 * Сервис аналитики.
 *
 * Формирует агрегированные отчёты по компаниям на основе данных
 * об обучении сотрудников, учебных группах и спецификациях.
 */
class AnalyticsService
{
    /**
     * Сформировать сводный отчёт по всем компаниям.
     *
     * Для каждой компании вычисляет:
     * - общее количество сотрудников и количество прошедших обучение (distinct по group_participants)
     * - количество уникальных учебных групп с участием сотрудников компании
     * - количество спецификаций компании
     * - общую стоимость обучения по актуальным ценам курсов и количеству участников
     * - стоимость с НДС 22%
     * - средний процент выполнения по всем участникам
     *
     * @return array<int, array{
     *     id: int,
     *     code: string,
     *     name: string,
     *     total_employees: int,
     *     trained_employees: int,
     *     training_groups_count: int,
     *     specifications_count: int,
     *     total_cost: float,
     *     total_cost_with_vat: float,
     *     avg_progress: float
     * }>
     */
    public function companySummary(): array
    {
        $companies = Company::withCount([
            'employees',
            'specifications',
        ])->get();

        $result = [];

        foreach ($companies as $company) {
            $trainedEmployees = DB::table('group_participants')
                ->join('employees', 'group_participants.employee_id', '=', 'employees.id')
                ->where('employees.company_id', $company->id)
                ->distinct('group_participants.employee_id')
                ->count('group_participants.employee_id');

            $trainingGroupsCount = DB::table('group_participants')
                ->join('employees', 'group_participants.employee_id', '=', 'employees.id')
                ->where('employees.company_id', $company->id)
                ->distinct('group_participants.training_group_id')
                ->count('group_participants.training_group_id');

            $avgProgress = DB::table('group_participants')
                ->join('employees', 'group_participants.employee_id', '=', 'employees.id')
                ->where('employees.company_id', $company->id)
                ->avg('group_participants.completion_percent') ?? 0;

            $totalCost = $this->calculateCompanyTotalCost($company->id);

            $result[] = [
                'id'                    => $company->id,
                'code'                  => $company->code,
                'name'                  => $company->name,
                'total_employees'       => $company->employees_count,
                'trained_employees'     => $trainedEmployees,
                'training_groups_count' => $trainingGroupsCount,
                'specifications_count'  => $company->specifications_count,
                'total_cost'            => round($totalCost, 2),
                'total_cost_with_vat'   => round($totalCost * 1.22, 2),
                'avg_progress'          => round($avgProgress, 2),
            ];
        }

        return $result;
    }
    
    /**
     * Сформировать детальный отчёт по одной компании.
     *
     * Возвращает:
     * - базовые данные компании
     * - список активных сотрудников с количеством групп и средним прогрессом
     *   (через LEFT JOIN с group_participants, training_groups, courses)
     * - список спецификаций компании
     * - распределение учебных групп по статусам (количество групп на каждый статус)
     *
     * @param  int $companyId Идентификатор компании
     * @return array{
     *     company: array{id: int, code: string, name: string},
     *     employees: \Illuminate\Support\Collection<int, array{id: int, full_name: string, email: string|null, groups_count: int, avg_progress: float}>,
     *     specifications: \Illuminate\Support\Collection<int, object{id: int, number: string, date: string}>,
     *     status_distribution: \Illuminate\Support\Collection<string, int>
     * }
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Если компания не найдена
     */
    public function companyDetail(int $companyId): array
    {
        $company = Company::findOrFail($companyId);

        $employees = DB::table('employees')
            ->leftJoin('group_participants', 'employees.id', '=', 'group_participants.employee_id')
            ->leftJoin('training_groups', 'group_participants.training_group_id', '=', 'training_groups.id')
            ->leftJoin('courses', 'training_groups.course_id', '=', 'courses.id')
            ->where('employees.company_id', $companyId)
            ->whereNull('employees.deleted_at')
            ->select(
                'employees.id',
                'employees.full_name',
                'employees.email',
                DB::raw('COUNT(DISTINCT group_participants.training_group_id) as groups_count'),
                DB::raw('COALESCE(AVG(group_participants.completion_percent), 0) as avg_progress')
            )
            ->groupBy('employees.id', 'employees.full_name', 'employees.email')
            ->get()
            ->map(fn ($e) => [
                'id'            => $e->id,
                'full_name'     => $e->full_name,
                'email'         => $e->email,
                'groups_count'  => (int) $e->groups_count,
                'avg_progress'  => round((float) $e->avg_progress, 2),
            ]);

        $specifications = DB::table('specifications')
            ->where('company_id', $companyId)
            ->select('id', 'number', 'date')
            ->get();

        $statusDistribution = DB::table('group_participants')
            ->join('employees', 'group_participants.employee_id', '=', 'employees.id')
            ->join('training_groups', 'group_participants.training_group_id', '=', 'training_groups.id')
            ->where('employees.company_id', $companyId)
            ->select('training_groups.status', DB::raw('COUNT(DISTINCT training_groups.id) as count'))
            ->groupBy('training_groups.status')
            ->pluck('count', 'status');

        return [
            'company'              => [
                'id'   => $company->id,
                'code' => $company->code,
                'name' => $company->name,
            ],
            'employees'            => $employees,
            'specifications'       => $specifications,
            'status_distribution'  => $statusDistribution,
        ];
    }

    /**
     * Рассчитать общую стоимость обучения для компании.
     *
     * Вычисляет сумму произведений актуальной цены курса на количество участников
     * во всех учебных группах, связанных со спецификациями данной компании.
     *
     * Актуальная цена определяется как цена с максимальной датой valid_from,
     * не превышающей текущую дату (подзапрос к таблице course_price).
     *
     * @param  int $companyId Идентификатор компании
     * @return float Общая стоимость обучения (без НДС)
     */
   private function calculateCompanyTotalCost(int $companyId): float
{
    return (float) DB::table('specifications')
        ->join('training_groups', 'training_groups.specification_id', '=', 'specifications.id')
        ->join('courses', 'training_groups.course_id', '=', 'courses.id')
        ->leftJoin(
            DB::raw('(
                SELECT training_group_id, COUNT(*) as cnt
                FROM group_participants
                GROUP BY training_group_id
            ) as pc'),
            'pc.training_group_id', '=', 'training_groups.id'
        )
        ->leftJoin(
            DB::raw('(
                SELECT cp1.course_id, cp1.price
                FROM course_price cp1
                INNER JOIN (
                    SELECT course_id, MAX(valid_from) as max_valid
                    FROM course_price
                    WHERE valid_from <= CURDATE()
                    GROUP BY course_id
                ) cp2 ON cp1.course_id = cp2.course_id AND cp1.valid_from = cp2.max_valid
            ) as latest_price'),
            'latest_price.course_id', '=', 'courses.id'
        )
        ->where('specifications.company_id', $companyId)
        ->selectRaw('COALESCE(SUM(COALESCE(latest_price.price, 0) * COALESCE(pc.cnt, 0)), 0) as total')
        ->value('total');
}
}
