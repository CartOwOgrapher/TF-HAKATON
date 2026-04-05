<template>
  <div class="login-page">
    <!-- Фоновые декоративные элементы -->
    <div class="bg-glow bg-glow--top"></div>
    <div class="bg-glow bg-glow--bottom"></div>
    <div class="bg-grid"></div>

    <!-- Логотип сверху -->
    <div class="brand">
      <div class="brand-icon">
        <svg width="28" height="28" viewBox="0 0 40 40" fill="none">
          <circle cx="20" cy="20" r="18" stroke="currentColor" stroke-width="2.2"/>
          <path d="M14 20 L18 24 L26 16" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      <div class="brand-text">
        <span class="brand-title">Обучение</span>
        <span class="brand-sub">УПРАВЛЕНИЕ</span>
      </div>
    </div>

    <!-- Карточка логина по центру экрана -->
    <div class="login-card">
      <div class="card-header">
        <h1>Добро пожаловать</h1>
        <p>Войдите в систему управления обучением</p>
      </div>

      <!-- Серверная ошибка -->
      <Transition name="fade">
        <div v-if="serverError" class="server-error">
          {{ serverError }}
        </div>
      </Transition>

      <form class="login-form" @submit.prevent="handleLogin">
        <div class="form-group">
          <label for="email">Email</label>
          <div class="input-wrapper" :class="{ focused: emailFocused, error: emailError }">
            <svg class="input-icon" width="18" height="18" viewBox="0 0 18 18" fill="none">
              <rect x="2" y="4" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.4"/>
              <path d="M2 6l7 4 7-4" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
            </svg>
            <input
              id="email"
              v-model="email"
              type="email"
              placeholder="name@company.ru"
              autocomplete="email"
              @focus="emailFocused = true"
              @blur="emailFocused = false"
            />
          </div>
          <Transition name="fade">
            <span v-if="emailError" class="error-text">{{ emailError }}</span>
          </Transition>
        </div>

        <div class="form-group">
          <label for="password">Пароль</label>
          <div class="input-wrapper" :class="{ focused: passwordFocused, error: passwordError }">
            <svg class="input-icon" width="18" height="18" viewBox="0 0 18 18" fill="none">
              <rect x="3" y="8" width="12" height="8" rx="2" stroke="currentColor" stroke-width="1.4"/>
              <path d="M6 8V5a3 3 0 016 0v3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
              <circle cx="9" cy="12" r="1" fill="currentColor"/>
            </svg>
            <input
              id="password"
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              placeholder="••••••••"
              autocomplete="current-password"
              @focus="passwordFocused = true"
              @blur="passwordFocused = false"
            />
            <button
              type="button"
              class="toggle-password"
              @click="showPassword = !showPassword"
              tabindex="-1"
            >
              <svg v-if="!showPassword" width="18" height="18" viewBox="0 0 18 18" fill="none">
                <path d="M1.5 9s3-5.5 7.5-5.5S16.5 9 16.5 9s-3 5.5-7.5 5.5S1.5 9 1.5 9z" stroke="currentColor" stroke-width="1.4"/>
                <circle cx="9" cy="9" r="2.5" stroke="currentColor" stroke-width="1.4"/>
              </svg>
              <svg v-else width="18" height="18" viewBox="0 0 18 18" fill="none">
                <path d="M1.5 9s3-5.5 7.5-5.5S16.5 9 16.5 9s-3 5.5-7.5 5.5S1.5 9 1.5 9z" stroke="currentColor" stroke-width="1.4"/>
                <circle cx="9" cy="9" r="2.5" stroke="currentColor" stroke-width="1.4"/>
                <path d="M3 3l12 12" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
              </svg>
            </button>
          </div>
          <Transition name="fade">
            <span v-if="passwordError" class="error-text">{{ passwordError }}</span>
          </Transition>
        </div>

        <div class="form-options">
          <label class="checkbox-wrapper">
            <input v-model="rememberMe" type="checkbox" />
            <span class="checkmark"></span>
            <span class="checkbox-label">Запомнить меня</span>
          </label>
          <a href="#" class="forgot-link">Забыли пароль?</a>
        </div>

        <button
          type="submit"
          class="submit-btn"
          :class="{ loading: isLoading }"
          :disabled="isLoading"
        >
          <svg
            v-if="isLoading"
            class="spinner"
            width="20"
            height="20"
            viewBox="0 0 20 20"
            fill="none"
          >
            <circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="2" opacity="0.3"/>
            <path d="M10 2a8 8 0 018 8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
          <span v-else>Войти</span>
        </button>
      </form>

      <p class="signup-hint">
        Нет аккаунта? <router-link to="/register" class="signup-link">Зарегистрироваться</router-link>
      </p>
    </div>

    <!-- Футер -->
    <div class="login-footer">
      Global ERP — система корпоративного обучения
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthStore } from '@/stores/useAuthStore'

const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const rememberMe = ref(false)
const showPassword = ref(false)
const isLoading = ref(false)

const emailFocused = ref(false)
const passwordFocused = ref(false)

const emailError = ref('')
const passwordError = ref('')
const serverError = ref('')

function validate() {
  let valid = true
  emailError.value = ''
  passwordError.value = ''
  serverError.value = ''

  if (!email.value.trim()) {
    emailError.value = 'Введите email'
    valid = false
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
    emailError.value = 'Некорректный формат email'
    valid = false
  }

  if (!password.value) {
    passwordError.value = 'Введите пароль'
    valid = false
  } else if (password.value.length < 4) {
    passwordError.value = 'Минимум 4 символа'
    valid = false
  }

  return valid
}

import { useRouter } from 'vue-router'

const router = useRouter()

async function handleLogin() {
  if (!validate()) return

  isLoading.value = true
  serverError.value = ''

  try {
  await authStore.login(email.value, password.value)
  
  console.log('TOKEN:', authStore.token)
  console.log('IS_AUTH:', authStore.isAuthenticated)
  console.log('PUSHING TO /')
  
  const result = await router.push('/')
  
  console.log('PUSH RESULT:', result)
} catch (err) {
    if (err.response?.status === 401 || err.response?.status === 422) {
      passwordError.value = 'Неверный email или пароль'
    } else {
      serverError.value = authStore.error || 'Ошибка соединения с сервером'
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
/* ============================================
   Переменные
   ============================================ */
.login-page {
  --bg: #0b1120;
  --bg-card: #ffffff;
  --primary: #4f6ef7;
  --primary-hover: #3b5de7;
  --primary-glow: rgba(79, 110, 247, 0.35);
  --primary-light: rgba(79, 110, 247, 0.1);
  --text-primary: #1e293b;
  --text-secondary: #64748b;
  --text-muted: #94a3b8;
  --text-on-dark: rgba(148, 163, 184, 0.6);
  --border: #e2e8f0;
  --border-focus: #4f6ef7;
  --error: #ef4444;
  --error-bg: rgba(239, 68, 68, 0.06);
  --radius: 12px;
}

/* ============================================
   Полноэкранный layout — форма по центру
   ============================================ */
.login-page {
  min-height: 100vh;
  min-height: 100dvh;
  background: var(--bg);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
  padding: 40px 24px;
}

/* ============================================
   Декоративный фон
   ============================================ */
.bg-glow {
  position: absolute;
  border-radius: 50%;
  pointer-events: none;
  filter: blur(100px);
}

.bg-glow--top {
  width: 600px;
  height: 600px;
  top: -250px;
  right: -100px;
  background: radial-gradient(circle, rgba(79, 110, 247, 0.2) 0%, transparent 70%);
}

.bg-glow--bottom {
  width: 500px;
  height: 500px;
  bottom: -200px;
  left: -100px;
  background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, transparent 70%);
}

.bg-grid {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(255, 255, 255, 0.02) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
  background-size: 60px 60px;
  pointer-events: none;
}

/* ============================================
   Брендинг
   ============================================ */
.brand {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-bottom: 40px;
  position: relative;
  z-index: 1;
}

.brand-icon {
  width: 44px;
  height: 44px;
  background: rgba(79, 110, 247, 0.15);
  border: 1px solid rgba(79, 110, 247, 0.25);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--primary);
}

.brand-text {
  display: flex;
  flex-direction: column;
}

.brand-title {
  font-size: 20px;
  font-weight: 700;
  color: #ffffff;
  letter-spacing: -0.02em;
  line-height: 1.2;
}

.brand-sub {
  font-size: 10px;
  font-weight: 600;
  color: var(--primary);
  letter-spacing: 0.15em;
  text-transform: uppercase;
}

/* ============================================
   Карточка
   ============================================ */
.login-card {
  width: 100%;
  max-width: 420px;
  background: var(--bg-card);
  border-radius: 20px;
  padding: 44px 40px 36px;
  position: relative;
  z-index: 1;
  box-shadow:
    0 4px 24px rgba(0, 0, 0, 0.25),
    0 0 80px rgba(79, 110, 247, 0.08);
  animation: cardIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
}

@keyframes cardIn {
  from {
    opacity: 0;
    transform: translateY(16px) scale(0.98);
  }
}

/* Header */
.card-header {
  margin-bottom: 32px;
  text-align: center;
}

.card-header h1 {
  font-size: 26px;
  font-weight: 800;
  color: var(--text-primary);
  margin: 0 0 8px;
  letter-spacing: -0.03em;
}

.card-header p {
  font-size: 14px;
  color: var(--text-secondary);
  margin: 0;
  line-height: 1.5;
}

/* ============================================
   Серверная ошибка
   ============================================ */
.server-error {
  background: var(--error-bg);
  border: 1px solid rgba(239, 68, 68, 0.15);
  color: var(--error);
  font-size: 13px;
  padding: 10px 14px;
  border-radius: var(--radius);
  margin-bottom: 20px;
  text-align: center;
}

/* ============================================
   Форма
   ============================================ */
.login-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group label {
  font-size: 13px;
  font-weight: 600;
  color: var(--text-primary);
}

/* Input */
.input-wrapper {
  display: flex;
  align-items: center;
  gap: 10px;
  border: 1.5px solid var(--border);
  border-radius: var(--radius);
  padding: 0 14px;
  height: 46px;
  background: #f8fafc;
  transition: all 0.2s;
}

.input-wrapper.focused {
  border-color: var(--border-focus);
  box-shadow: 0 0 0 3px var(--primary-light);
  background: #fff;
}

.input-wrapper.error {
  border-color: var(--error);
  box-shadow: 0 0 0 3px var(--error-bg);
}

.input-icon {
  flex-shrink: 0;
  color: var(--text-muted);
  transition: color 0.2s;
}

.input-wrapper.focused .input-icon {
  color: var(--primary);
}

.input-wrapper input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 14px;
  color: var(--text-primary);
  background: transparent;
  font-family: inherit;
}

.input-wrapper input::placeholder {
  color: var(--text-muted);
}

.toggle-password {
  background: none;
  border: none;
  cursor: pointer;
  padding: 2px;
  color: var(--text-muted);
  display: flex;
  align-items: center;
  transition: color 0.15s;
}

.toggle-password:hover {
  color: var(--text-secondary);
}

/* Error text */
.error-text {
  font-size: 12px;
  color: var(--error);
  padding-left: 2px;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s, transform 0.2s;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

/* Опции */
.form-options {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

/* Checkbox */
.checkbox-wrapper {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  position: relative;
  user-select: none;
}

.checkbox-wrapper input {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.checkmark {
  width: 18px;
  height: 18px;
  border: 1.5px solid var(--border);
  border-radius: 5px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s;
  flex-shrink: 0;
}

.checkbox-wrapper input:checked + .checkmark {
  background: var(--primary);
  border-color: var(--primary);
}

.checkbox-wrapper input:checked + .checkmark::after {
  content: '';
  width: 5px;
  height: 9px;
  border: solid white;
  border-width: 0 2px 2px 0;
  transform: rotate(45deg) translate(-1px, -1px);
}

.checkbox-label {
  font-size: 13px;
  color: var(--text-secondary);
}

.forgot-link {
  font-size: 13px;
  color: var(--primary);
  text-decoration: none;
  font-weight: 500;
  transition: color 0.15s;
}

.forgot-link:hover {
  color: var(--primary-hover);
  text-decoration: underline;
}

/* Кнопка */
.submit-btn {
  height: 46px;
  border: none;
  border-radius: var(--radius);
  background: var(--primary);
  color: white;
  font-size: 15px;
  font-weight: 600;
  font-family: inherit;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  margin-top: 4px;
  box-shadow: 0 4px 16px var(--primary-glow);
}

.submit-btn:hover:not(:disabled) {
  background: var(--primary-hover);
  box-shadow: 0 6px 24px var(--primary-glow);
  transform: translateY(-1px);
}

.submit-btn:active:not(:disabled) {
  transform: translateY(0) scale(0.985);
}

.submit-btn:disabled {
  opacity: 0.85;
  cursor: not-allowed;
}

/* Spinner */
.spinner {
  animation: spin 0.7s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Ссылка на регистрацию */
.signup-hint {
  text-align: center;
  font-size: 13px;
  color: var(--text-muted);
  margin: 24px 0 0;
}

.signup-link {
  color: var(--primary);
  text-decoration: none;
  font-weight: 600;
  transition: color 0.15s;
}

.signup-link:hover {
  color: var(--primary-hover);
  text-decoration: underline;
}

/* ============================================
   Футер
   ============================================ */
.login-footer {
  margin-top: 40px;
  font-size: 12px;
  color: var(--text-on-dark);
  position: relative;
  z-index: 1;
  letter-spacing: 0.02em;
}

/* ============================================
   Responsive
   ============================================ */
@media (max-width: 500px) {
  .login-page {
    padding: 32px 16px;
  }

  .login-card {
    padding: 32px 24px 28px;
    border-radius: 16px;
  }

  .card-header h1 {
    font-size: 22px;
  }
}
</style>
