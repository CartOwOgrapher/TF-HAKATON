import apiClient from '@/api/axios';

export const trainingGroupService = {
  list: () =>
    apiClient.get('/training-groups'),

  create: (payload: object) =>
    apiClient.post('/training-groups', payload),

  update: (id: number, payload: object) =>
    apiClient.put(`/training-groups/${id}`, payload),

  delete: (id: number) =>
    apiClient.delete(`/training-groups/${id}`),

  show(id: number) {
    return apiClient.get(`/training-groups/${id}`);
  },

  // Участники группы
  getParticipants: (groupId: number) =>
    apiClient.get(`/training-groups/${groupId}/participants`),

  addParticipant: (groupId: number, payload: { employee_id: number; completion_percent?: number }) =>
    apiClient.post(`/training-groups/${groupId}/participants`, payload),

  updateParticipant: (groupId: number, participantId: number, payload: { completion_percent: number }) =>
    apiClient.patch(`/training-groups/${groupId}/participants/${participantId}`, payload),

  removeParticipant: (groupId: number, participantId: number) =>
    apiClient.delete(`/training-groups/${groupId}/participants/${participantId}`),

  // Статус группы
  changeStatus: (id: number, status: string) =>
    apiClient.patch(`/training-groups/${id}/status`, { status }),

  // ── Сертификаты ───────────────────────────────────────────────────
  //
  // Загрузка, скачивание и удаление PDF-сертификата участника группы.
  // Используется apiClient — токен и baseURL подставляются автоматически.

  /** POST — загрузка сертификата (multipart/form-data) */
  uploadCertificate: (groupId: number, participantId: number, file: File) => {
    const formData = new FormData()
    formData.append('certificate', file)

    return apiClient.post(
      `/training-groups/${groupId}/participants/${participantId}/certificate`,
      formData,
      { headers: { 'Content-Type': 'multipart/form-data' } },
    )
  },

  /** GET — скачивание сертификата (возвращает Blob) */
  downloadCertificate: (groupId: number, participantId: number) =>
    apiClient.get(
      `/training-groups/${groupId}/participants/${participantId}/certificate`,
      { responseType: 'blob' },
    ),

  /** DELETE — удаление сертификата */
  deleteCertificate: (groupId: number, participantId: number) =>
    apiClient.delete(
      `/training-groups/${groupId}/participants/${participantId}/certificate`,
    ),
};