import apiClient from '@/api/axios';

export const analyticsService = {
    /**
     * Сводная аналитика по всем компаниям
     * GET /api/analytics/companies
     */
    companies() {
        return apiClient.get('/analytics/companies');
    },

    /**
     * Детальная аналитика по конкретной компании
     * GET /api/analytics/companies/:id
     */
    companyDetail(id: number) {
        return apiClient.get(`/analytics/companies/${id}`);
    },
};
