import apiClient from '@/api/axios';

export const specificationService = {
  /** GET /specifications */
  read: () =>
    apiClient.get('/specifications'),

  /** GET /specifications?company_id=:id */
  readByCompany: (companyId: number) =>
    apiClient.get('/specifications', { params: { company_id: companyId } }),

  /** GET /specifications/:id  — returns spec with groups & computed costs */
  show: (id: number) =>
    apiClient.get(`/specifications/${id}`),

  /** POST /specifications  body: { number, date, company_id } */
  create: (payload: { number: string; date: string; company_id: number }) =>
    apiClient.post('/specifications', payload),

  /** PUT /specifications/:id */
  update: (id: number, payload: object) =>
    apiClient.put(`/specifications/${id}`, payload),

  /** DELETE /specifications/:id */
  delete: (id: number) =>
    apiClient.delete(`/specifications/${id}`),

  /** POST /specifications/:specId/groups/:groupId — attach group */
  attachGroup: (specId: number, groupId: number) =>
    apiClient.post(`/specifications/${specId}/groups/${groupId}`),

  /** DELETE /specifications/:specId/groups/:groupId — detach group */
  detachGroup: (specId: number, groupId: number) =>
    apiClient.delete(`/specifications/${specId}/groups/${groupId}`),
};