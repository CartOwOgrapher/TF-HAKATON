import apiClient from '@/api/axios';

export const xmlService = {
  // Загрузка файла и запуск импорта
  import: (payload: FormData) =>
    apiClient.post('/xml/import', payload, {
      headers: { 'Content-Type': 'multipart/form-data' },
    }),

  // Список батчей импорта
  listBatches: () =>
    apiClient.get('/xml/batches'),

  // Детали конкретного батча
  getBatch: (id: number) =>
    apiClient.get(`/xml/batches/${id}`),

  // Логи батча
  getBatchLogs: (id: number) =>
    apiClient.get(`/xml/batches/${id}/logs`),
};