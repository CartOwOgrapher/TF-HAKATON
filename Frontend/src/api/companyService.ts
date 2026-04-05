import apiClient from '@/api/axios';

export const companyService = {
  list: () =>
    apiClient.get('/companies/list'),

  create: (payload: object) =>
    apiClient.post('/companies/create', payload),

  update: (id: number, payload: object) =>
    apiClient.post(`/companies/${id}`, payload),

  delete: (id: number) =>
    apiClient.delete(`/companies/${id}/soft`),
};