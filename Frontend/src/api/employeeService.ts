import apiClient from '@/api/axios';

export const employeeService = {
  list: () =>
    apiClient.get('/employees/list'),

  create: (payload: object) =>
    apiClient.post('/employees/create', payload),

  update: (id: number, payload: object) =>
    apiClient.post(`/employees/${id}`, payload),

  delete: (id: number) =>
    apiClient.delete(`/employees/${id}/soft`),

  getCompanies: () =>
    apiClient.get('/companies/list'),

  getTrainingGroups: () =>
    apiClient.get('/training-groups'),
};