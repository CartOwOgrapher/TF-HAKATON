import { defineStore } from 'pinia';
import { ref } from 'vue';
import { analyticsService } from '@/api/analyticsService.ts';

export interface CompanySummary {
    id: number;
    code: string;
    name: string;
    total_employees: number;
    trained_employees: number;
    training_groups_count: number;
    specifications_count: number;
    total_cost: number;
    total_cost_with_vat: number;
    avg_progress: number;
}

export interface EmployeeDetail {
    id: number;
    full_name: string;
    email: string;
    groups_count: number;
    avg_progress: number;
}

export interface CompanyDetail {
    company: { id: number; code: string; name: string };
    employees: EmployeeDetail[];
    specifications: { id: number; number: string; date: string }[];
    status_distribution: Record<string, number>;
}

export const useAnalyticsStore = defineStore('analytics', () => {
    const loading = ref(false);
    const error = ref<string | null>(null);

    async function fetchCompaniesSummary(): Promise<CompanySummary[]> {
        loading.value = true;
        error.value = null;
        try {
            const { data: response } = await analyticsService.companies();
            return response.data ?? [];
        } catch (e: any) {
            error.value = e.response?.data?.message || 'Ошибка загрузки аналитики';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function fetchCompanyDetail(id: number): Promise<CompanyDetail> {
        loading.value = true;
        error.value = null;
        try {
            const { data: response } = await analyticsService.companyDetail(id);
            return response.data;
        } catch (e: any) {
            error.value = e.response?.data?.message || 'Ошибка загрузки деталей';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    return { loading, error, fetchCompaniesSummary, fetchCompanyDetail };
});
