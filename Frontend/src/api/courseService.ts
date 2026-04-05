import apiClient from '@/api/axios';

export const courseService = {
  read: () =>
    apiClient.get('/courses/list'),

  create: (payload: object) =>
    apiClient.post('/courses/create', payload),

  update: (id: number, payload: object) =>
    apiClient.post(`/courses/${id}`, payload),

  delete: (id: number) =>
    apiClient.delete(`/courses/${id}/soft`),

  getPrices: (courseId: number) =>
    apiClient.get(`/course_price/${courseId}/list`),

  createPrice: (courseId: number, payload: object) =>
    apiClient.post(`/course_price/${courseId}/create`, payload),
};