import apiClient from '@/api/axios';

export const dashboardService = {
    getCourses: () =>
        apiClient.get('/courses/list'),

    getGroups: () =>
        apiClient.get('/training-groups'), 

    getCompanies: () =>
        apiClient.get('/companies/list'), 

    getSpecs: () =>
        apiClient.get('/specifications'), 

};