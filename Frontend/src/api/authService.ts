import apiClient from '@/api/axios';


export const authService = {
  /**
   * POST /auth/login
   * @param {{ email: string, password: string }} credentials
   * @returns {{ token: string, user: object }}
   */
  login(credentials: { email: string; password: string }) {
    return apiClient.post('/auth/login', credentials);
  },

  /**
   * POST /auth/register
   * @param {{ name: string, email: string, password: string, password_confirmation: string }} data
   */
  register(data: {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
  }) {
    return apiClient.post('/auth/register', data);
  },

  /**
   * POST /auth/logout
   * Требует Bearer-токен.
   */
  logout() {
    return apiClient.post('/auth/logout');
  },

  /**
   * GET /auth/me
   * Возвращает текущего пользователя по токену.
   */
  me() {
    return apiClient.get('/auth/me');
  },
};
