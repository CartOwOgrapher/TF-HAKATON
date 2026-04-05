import axios from 'axios';
import { API_URL } from '@/config/api';

/**
 * Axios-инстанс для работы с API бэкенда (Laravel Sanctum).
 *
 * - baseURL указывает на /api, поэтому в запросах пишем только путь:
 *   apiClient.get('/courses') → GET http://localhost:8080/api/courses
 *
 * - Request interceptor: подставляет Bearer-токен из localStorage
 * - Response interceptor: при 401 сбрасывает токен и редиректит на /login
 */
const apiClient = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// ── Request interceptor ─────────────────────────────────────────────
apiClient.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error),
);

// ── Response interceptor ────────────────────────────────────────────
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  },
);

export default apiClient;
