import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { authService } from '@/api/authService';
import router from '@/router';

/**
 * Стор авторизации.
 *
 * Хранит токен в localStorage и реактивное состояние user.
 * При старте приложения вызывается init() — если токен есть,
 * подтягивает профиль через GET /auth/me.
 */
export const useAuthStore = defineStore('auth', () => {

  const user = ref<Record<string, any> | null>(null);
  const token = ref<string | null>(localStorage.getItem('token'));
  const loading = ref(false);
  const error = ref<string | null>(null);

  const isAuthenticated = computed(() => !!token.value);

async function login(email: string, password: string) {
    loading.value = true;
    error.value = null;

   try {
      const { data: response } = await authService.login({ email, password });

      token.value = response.data.token;
      localStorage.setItem('token', response.data.token);

      user.value = response.data.user;
  } catch (e: any) {
      const status = e.response?.status;
      const responseData = e.response?.data;

      if (!e.response) {
        // Сеть не ответила — сервер недоступен или CORS
        error.value = `Сервер недоступен: ${e.message}`;
      } else if (status === 422 && responseData?.errors) {
        // Валидация Laravel — собираем все ошибки полей
        error.value = Object.values(responseData.errors).flat().join('; ');
      } else if (status === 401) {
        error.value = responseData?.message || 'Неверный email или пароль';
      } else {
        // Любая другая ошибка — показываем статус + message
        error.value = `[${status}] ${responseData?.message || e.message}`;
      }

      console.error('[AuthStore login]', {
        status,
        message: responseData?.message,
        errors: responseData?.errors,
        raw: e,
      });

      throw e;
    } finally {
      loading.value = false;
    }
  }

  /**
   * Регистрация: создаёт аккаунт, сохраняет токен, редиректит.
   */
  async function register(payload: {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
  }) {
    loading.value = true;
    error.value = null;

    try {
      const { data: response } = await authService.register(payload);

      token.value = response.data.token;
      localStorage.setItem('token', response.data.token);

      user.value = response.data.user;

      await router.push('/');
    } catch (e: any) {
      // Если Laravel вернул validation errors
      if (e.response?.status === 422) {
        const errors = e.response.data.errors;
        error.value = Object.values(errors).flat().join(', ');
      } else {
        error.value =
          e.response?.data?.message || 'Ошибка регистрации';
      }
      throw e;
    } finally {
      loading.value = false;
    }
  }

  /**
   * Выход: удаляет токен, сбрасывает состояние.
   */
  async function logout() {
    try {
      await authService.logout();
    } catch {
      // Даже если бэк не ответил — всё равно чистим
    } finally {
      token.value = null;
      user.value = null;
      localStorage.removeItem('token');
      await router.push('/login');
    }
  }

  /**
   * Инициализация при старте приложения.
   * Если в localStorage есть токен — пробуем получить профиль.
   */
  async function init() {
    if (!token.value) return;

    try {
      const { data } = await authService.me();
      user.value = data.user ?? data;
    } catch {
      // Токен протух — чистим
      token.value = null;
      user.value = null;
      localStorage.removeItem('token');
    }
  }

  return {
    user,
    token,
    loading,
    error,
    isAuthenticated,
    login,
    register,
    logout,
    init,
  };
});
