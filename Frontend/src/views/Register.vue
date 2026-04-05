<template>
  <div class="register-page">
    <!-- Фоновые декоративные элементы -->
    <div class="bg-glow bg-glow--top"></div>
    <div class="bg-glow bg-glow--bottom"></div>
    <div class="bg-grid"></div>

    <!-- Логотип -->
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

    <!-- Карточка регистрации -->
    <div class="register-card">
      <div class="card-header">
        <h1>Создать аккаунт</h1>
        <p>Зарегистрируйтесь для доступа к системе</p>
      </div>

      <!-- Серверная ошибка -->
      <Transition name="fade">
        <div v-if="serverError" class="server-error">
          {{ serverError }}
        </div>
      </Transition>

      <form class="register-form" @submit.prevent="handleRegister">
        <!-- Имя -->
        <div class="form-group">
          <label for="name">Имя</label>
          <div class="input-wrapper" :class="{ focused: nameFocused, error: errors.name }">
            <svg class="input-icon" width="18" height="18" viewBox="0 0 18 18" fill="none">
              <circle cx="9" cy="6" r="3.5" stroke="currentColor" stroke-width="1.4"/>
              <path d="M2.5 16c0-3 2.9-5.5 6.5-5.5s6.5 2.5 6.5 5.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
            </svg>
            <input
              id="name"
              v-model="form.name"
              type="text"
              placeholder="Иван Иванов"
              autocomplete="name"
              @focus="nameFocused = true"
              @blur="nameFocused = false"
            />
          </div>
          <Transition name="fade">
            <span v-if="errors.name" class="error-text">{{ errors.name }}</span>
          </Transition>
        </div>

        <!-- Email -->
        <div class="form-group">
          <label for="reg-email">Email</label>
          <div class="input-wrapper" :class="{ focused: emailFocused, error: errors.email }">
            <svg class="input-icon" width="18" height="18" viewBox="0 0 18 18" fill="none">
              <rect x="2" y="4" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.4"/>
              <path d="M2 6l7 4 7-4" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
            </svg>
            <input
              id="reg-email"
              v-model="form.email"
              type="email"
              placeholder="name@company.ru"
              autocomplete="email"
              @focus="emailFocused = true"
              @blur="emailFocused = false"
            />
          </div>
          <Transition name="fade">
            <span v-if="errors.email" class="error-text">{{ errors.email }}</span>
          </Transition>
        </div>

        <!-- Пароль -->
        <div class="form-group">
          <label for="reg-password">Пароль</label>
          <div class="input-wrapper" :class="{ focused: passwordFocused, error: errors.password }">
            <svg class="input-icon" width="18" height="18" viewBox="0 0 18 18" fill="none">
              <rect x="3" y="8" width="12" height="8" rx="2" stroke="currentColor" stroke-width="1.4"/>
              <path d="M6 8V5a3 3 0 016 0v3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
              <circle cx="9" cy="12" r="1" fill="currentColor"/>
            </svg>
            <input
              id="reg-password"
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              placeholder="Минимум 8 символов"
              autocomplete="new-password"
              @focus="passwordFocused = true"
              @blur="passwordFocused = false"
              @input="updateStrength"
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
          <!-- Индикатор надёжности пароля -->
          <div v-if="form.password" class="strength-bar">
            <div class="strength-track">
              <div
                class="strength-fill"
                :class="strengthClass"
                :style="{ width: strengthPercent + '%' }"
              ></div>
            </div>
            <span class="strength-label" :class="strengthClass">{{ strengthLabel }}</span>
          </div>
          <Transition name="fade">
            <span v-if="errors.password" class="error-text">{{ errors.password }}</span>
          </Transition>
        </div>

        <!-- Подтверждение пароля -->
        <div class="form-group">
          <label for="reg-password-confirm">Подтверждение пароля</label>
          <div class="input-wrapper" :class="{ focused: confirmFocused, error: errors.password_confirmation }">
            <svg class="input-icon" width="18" height="18" viewBox="0 0 18 18" fill="none">
              <rect x="3" y="8" width="12" height="8" rx="2" stroke="currentColor" stroke-width="1.4"/>
              <path d="M6 8V5a3 3 0 016 0v3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
              <path d="M7.5 12l1.5 1.5 2.5-3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <input
              id="reg-password-confirm"
              v-model="form.password_confirmation"
              :type="showPassword ? 'text' : 'password'"
              placeholder="Повторите пароль"
              autocomplete="new-password"
              @focus="confirmFocused = true"
              @blur="confirmFocused = false"
            />
            <!-- Галочка совпадения -->
            <Transition name="fade">
              <svg
                v-if="form.password_confirmation && form.password === form.password_confirmation"
                class="match-icon"
                width="18"
                height="18"
                viewBox="0 0 18 18"
                fill="none"
              >
                <circle cx="9" cy="9" r="7" fill="#22c55e" opacity="0.12"/>
                <path d="M6 9l2 2 4-4" stroke="#22c55e" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </Transition>
          </div>
          <Transition name="fade">
            <span v-if="errors.password_confirmation" class="error-text">{{ errors.password_confirmation }}</span>
          </Transition>
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
          <span v-else>Зарегистрироваться</span>
        </button>
      </form>

      <p class="login-hint">
        Уже есть аккаунт? <router-link to="/login" class="login-link">Войти</router-link>
      </p>
    </div>

    <!-- Футер -->
    <div class="register-footer">
      Global ERP — система корпоративного обучения
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { useAuthStore } from '@/stores/useAuthStore'

const authStore = useAuthStore()

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const errors = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const isLoading = ref(false)
const showPassword = ref(false)
const serverError = ref('')

const nameFocused = ref(false)
const emailFocused = ref(false)
const passwordFocused = ref(false)
const confirmFocused = ref(false)

// ============================================
// Индикатор надёжности пароля
// ============================================
const passwordStrength = ref(0)

function updateStrength() {
  let score = 0
  const p = form.password
  if (p.length >= 8) score++
  if (p.length >= 12) score++
  if (/[A-Z]/.test(p)) score++
  if (/[0-9]/.test(p)) score++
  if (/[^A-Za-z0-9]/.test(p)) score++
  passwordStrength.value = Math.min(score, 4)
}

const strengthPercent = computed(() => (passwordStrength.value / 4) * 100)
const strengthClass = computed(() => {
  const map = ['weak', 'weak', 'medium', 'strong', 'very-strong']
  return map[passwordStrength.value] || 'weak'
})
const strengthLabel = computed(() => {
  const labels = ['Слабый', 'Слабый', 'Средний', 'Надёжный', 'Отличный']
  return labels[passwordStrength.value] || ''
})

// ============================================
// Валидация
// ============================================
function validate() {
  let valid = true
  errors.name = ''
  errors.email = ''
  errors.password = ''
  errors.password_confirmation = ''
  serverError.value = ''

  if (!form.name.trim()) {
    errors.name = 'Введите имя'
    valid = false
  } else if (form.name.length > 255) {
    errors.name = 'Максимум 255 символов'
    valid = false
  }

  if (!form.email.trim()) {
    errors.email = 'Введите email'
    valid = false
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    errors.email = 'Некорректный формат email'
    valid = false
  }

  if (!form.password) {
    errors.password = 'Введите пароль'
    valid = false
  } else if (form.password.length < 8) {
    errors.password = 'Минимум 8 символов'
    valid = false
  }

  if (!form.password_confirmation) {
    errors.password_confirmation = 'Подтвердите пароль'
    valid = false
  } else if (form.password !== form.password_confirmation) {
    errors.password_confirmation = 'Пароли не совпадают'
    valid = false
  }

  return valid
}

// ============================================
// Отправка
// ============================================
async function handleRegister() {
  if (!validate()) return

  isLoading.value = true
  serverError.value = ''

  try {
    await authStore.register({ ...form })
    // Редирект на Dashboard происходит внутри authStore.register()
  } catch (err) {
    // Обработка серверных ошибок валидации (422)
    if (err.response?.status === 422 && err.response?.data?.errors) {
      const serverErrors = err.response.data.errors
      if (serverErrors.name) errors.name = serverErrors.name[0]
      if (serverErrors.email) errors.email = serverErrors.email[0]
      if (serverErrors.password) errors.password = serverErrors.password[0]
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
.register-page {
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
   Layout
   ============================================ */
.register-page {
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
  left: -100px;
  background: radial-gradient(circle, rgba(99, 102, 241, 0.2) 0%, transparent 70%);
}

.bg-glow--bottom {
  width: 500px;
  height: 500px;
  bottom: -200px;
  right: -100px;
  background: radial-gradient(circle, rgba(79, 110, 247, 0.18) 0%, transparent 70%);
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
  margin-bottom: 36px;
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
.register-card {
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
.register-form {
  display: flex;
  flex-direction: column;
  gap: 18px;
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

.match-icon {
  flex-shrink: 0;
}

/* ============================================
   Индикатор надёжности пароля
   ============================================ */
.strength-bar {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 2px;
}

.strength-track {
  flex: 1;
  height: 4px;
  background: #e2e8f0;
  border-radius: 2px;
  overflow: hidden;
}

.strength-fill {
  height: 100%;
  border-radius: 2px;
  transition: width 0.3s, background 0.3s;
}

.strength-fill.weak { background: #ef4444; }
.strength-fill.medium { background: #f59e0b; }
.strength-fill.strong { background: #22c55e; }
.strength-fill.very-strong { background: #10b981; }

.strength-label {
  font-size: 11px;
  font-weight: 600;
  white-space: nowrap;
}

.strength-label.weak { color: #ef4444; }
.strength-label.medium { color: #f59e0b; }
.strength-label.strong { color: #22c55e; }
.strength-label.very-strong { color: #10b981; }

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
  margin-top: 6px;
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

.spinner {
  animation: spin 0.7s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Ссылка на логин */
.login-hint {
  text-align: center;
  font-size: 13px;
  color: var(--text-muted);
  margin: 24px 0 0;
}

.login-link {
  color: var(--primary);
  text-decoration: none;
  font-weight: 600;
  transition: color 0.15s;
}

.login-link:hover {
  color: var(--primary-hover);
  text-decoration: underline;
}

/* ============================================
   Футер
   ============================================ */
.register-footer {
  margin-top: 36px;
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
  .register-page {
    padding: 32px 16px;
  }

  .register-card {
    padding: 32px 24px 28px;
    border-radius: 16px;
  }

  .card-header h1 {
    font-size: 22px;
  }
}
</style>
