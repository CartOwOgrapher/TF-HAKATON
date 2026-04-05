Readme · MD
Copy

# Global ERP — Система учёта корпоративного обучения
 
> Веб-приложение для планирования, учёта и визуализации корпоративного обучения с интеграцией в Global ERP.
 
Разработано в рамках хакатона **«Визуализация обучения в Global ERP: от данных к решениям»**.
 
---
 
## Оглавление
 
1. [О проекте](#о-проекте)
2. [Технологический стек](#технологический-стек)
3. [Архитектура](#архитектура)
4. [Модули системы](#модули-системы)
5. [Функциональные возможности](#функциональные-возможности)
6. [Бизнес-логика расчётов](#бизнес-логика-расчётов)
7. [API-эндпоинты](#api-эндпоинты)
8. [Установка и запуск](#установка-и-запуск)
 
---
 
## О проекте
 
Система предназначена для автоматизации процессов корпоративного обучения и решает следующие задачи:
 
- **Планирование обучения** — создание курсов, формирование учебных групп, назначение сотрудников.
- **Расчёт стоимости** — автоматический расчёт стоимости обучения по группам и спецификациям с учётом НДС (22%).
- **Мониторинг прогресса** — отслеживание процента завершения обучения по каждому участнику и группе в целом.
- **Визуализация графика** — интерактивная диаграмма Ганта с drag-and-drop, переключением масштабов и обнаружением конфликтов.
- **Управление документами** — формирование спецификаций (юридических документов), загрузка и хранение сертификатов участников.
- **Аналитика** — аналитический модуль с агрегированной статистикой по обучению в разрезе компаний (количество сотрудников, прогресс, стоимость, распределение по статусам).
- **XML-интеграция** — асинхронный импорт данных из Global ERP через XML-файлы с автоопределением типа данных, upsert-стратегией и детальным логированием результатов.
 
**Ключевые пользователи:** HR-специалисты, сотрудники центра обучения, сотрудники отдела бухгалтерии.
 
---
 
## Технологический стек
 
### Backend
 
| Технология | Версия | Назначение |
|---|---|---|
| PHP | 8.2+ | Язык серверной части |
| Laravel | 11.x | Основной фреймворк |
| Laravel Modules (nwidart) | — | Модульная структура приложения |
| Laravel Sanctum | — | Аутентификация через токены (SPA) |
| MySQL | 8.0+ | Реляционная СУБД |
| Docker + Docker Compose | — | Контейнеризация (PHP-FPM + Nginx + MySQL) |
 
### Frontend
 
| Технология | Версия | Назначение |
|---|---|---|
| Vue.js | 3.x | UI-фреймворк (Composition API, `<script setup>`) |
| Vite | — | Сборщик |
| Pinia | — | State management |
| Tailwind CSS | — | Стилизация |
| shadcn/ui (Vue) | — | Компонентная библиотека |
| DHTMLX Gantt | GPL | Диаграмма Ганта |
| Axios | — | HTTP-клиент |
 
### Обоснование выбора стека
 
- **Laravel** — зрелый фреймворк с встроенной поддержкой миграций, Eloquent ORM, валидации, авторизации (Policies), очередей. Идеален для быстрого создания MVP.
- **nwidart/laravel-modules** — пакет для организации модульного монолита. Каждый модуль — автономная единица со своими моделями, контроллерами, сервисами, миграциями и провайдерами. Позволяет масштабировать и декомпозировать приложение в будущем.
- **Vue 3 + Composition API** — реактивный UI с компонентным подходом. `<script setup>` минимизирует бойлерплейт.
- **MySQL** — надёжная СУБД с полной поддержкой внешних ключей и транзакций, что критично для финансовых расчётов.
- **DHTMLX Gantt** — зрелая Gantt-библиотека с нативной поддержкой drag-and-drop, масштабирования и кастомизации, в отличие от более простых альтернатив (frappe-gantt).
- **shadcn/ui** — профессиональный UI-кит, обеспечивающий консистентный бизнес-ориентированный интерфейс.
- **Docker** — унифицированное окружение для разработки и деплоя. Контейнеры PHP-FPM, Nginx и MySQL обеспечивают воспроизводимость среды.
 
---
 
## Архитектура
 
### Принятые решения
 
Выбрана архитектура **модульного монолита** на базе пакета `nwidart/laravel-modules` — каждый функциональный домен изолирован в собственный модуль с отдельными моделями, контроллерами, сервисами и миграциями, но все модули работают в рамках единого процесса и единой базы данных.
 
Преимущества для MVP:
- нет накладных расходов на сетевое взаимодействие между сервисами;
- единая транзакция при операциях, затрагивающих несколько модулей;
- модульность сохраняет путь к декомпозиции в будущем.
 
### Общая схема
 
```
┌─────────────────────────────────────────────────────┐
│                   Frontend (SPA)                    │
│            Vue 3 + Pinia + shadcn/ui                │
└────────────────────────┬────────────────────────────┘
                         │ REST API (JSON)
                         │ Auth: Bearer Token (Sanctum)
┌────────────────────────▼────────────────────────────┐
│     Nginx (docker/nginx)  ──►  PHP-FPM (docker/php) │
├─────────────────────────────────────────────────────┤
│                  Backend (Laravel)                   │
│                                                     │
│  ┌─────────────────────────────────────────────────┐│
│  │  Core Module (Auth, Abstracts, Contracts,       ││
│  │              Traits, Enums, Policies)            ││
│  └─────────────────────────────────────────────────┘│
│  ┌──────────┐ ┌──────────┐ ┌───────────────────┐   │
│  │ Company  │ │ Employee │ │      Course       │   │
│  │  Module  │ │  Module  │ │      Module       │   │
│  └──────────┘ └──────────┘ └───────────────────┘   │
│  ┌──────────┐ ┌──────────┐ ┌───────────────────┐   │
│  │ Training │ │  Specif. │ │   Gantt Module    │   │
│  │  Module  │ │  Module  │ │                   │   │
│  └──────────┘ └──────────┘ └───────────────────┘   │
│  ┌──────────┐ ┌────────────────────────────────┐   │
│  │Analytics │ │     Xml Module (import,        │   │
│  │  Module  │ │     parsers, importers, logs)  │   │
│  └──────────┘ └────────────────────────────────┘   │
└────────────────────────┬────────────────────────────┘
                         │
                ┌────────▼────────┐
                │     MySQL       │
                └─────────────────┘
```
 
### Слои внутри каждого модуля
 
```
Modules/{ModuleName}/
├── app/
│   ├── Models/              — Eloquent-модели, relations, scopes, accessors
│   ├── Http/
│   │   ├── Controllers/     — тонкие контроллеры, делегируют логику сервисам
│   │   ├── Requests/        — Form Request валидация
│   │   └── Resources/       — JSON-ресурсы (или Transformers — API-ответы)
│   ├── Services/            — бизнес-логика (расчёты, CRUD, проверки)
│   ├── Enums/               — перечисления (статусы, роли)
│   ├── Observers/           — реакция на события моделей
│   ├── Policies/            — авторизация действий по ролям
│   └── Providers/           — регистрация сервисов и маршрутов
├── config/                  — конфигурация модуля
├── database/
│   ├── migrations/          — миграции таблиц
│   └── seeders/             — сидеры тестовых данных
├── routes/
│   └── api.php              — маршруты модуля
├── module.json              — метаданные модуля
└── composer.json            — зависимости модуля
```
 
---
 
## Модули системы
 
### Core — Ядро приложения (Auth, абстракции, контракты)
 
Модуль `Core` является фундаментом системы и содержит:
 
**Аутентификация (`Auth/`)** — Laravel Sanctum (токены для SPA). Эндпоинты: register, login, logout, me. Контроллер `AuthController`, сервис `AuthService`, middleware `RoleMiddleware`. При регистрации пользователю автоматически присваивается роль HR. При повторном входе старые токены отзываются.
 
**Абстракции (`Abstracts/`)** — базовые классы, от которых наследуются все модули: `BaseController` (включает трейты `ApiResponse` + `AuthorizesRequests`), `BaseFormRequest`, `BaseResource`, `BasePolicy` (с универсальной проверкой ролей и автопропуском для Admin), `BaseService` (CRUD-шаблон поверх Eloquent).
 
**Контракты (`Contracts/`)** — интерфейсы для бизнес-логики: `CalculableContract` (методы `recalculateCost()` и `getTotalCost()` — реализуют `TrainingGroup` и `Specification`), `HasCompanyScope` (для объектов с привязкой к компании).
 
**Traits (`Traits/`)** — переиспользуемые поведения: `ApiResponse` (стандартизация JSON-ответов: `success`, `created`, `error`, `notFound`, `noContent`), `CalculatesVAT` (расчёт НДС 22%), `HasCompany` (связь `company()` и scope `forCompany()`).
 
**Enums (`Enums/`)** — `Role` с методом `label()` для человекочитаемых названий.
 
Роли:
 
| Роль | Код | Права |
|---|---|---|
| Администратор | `admin` | Полный доступ ко всем модулям |
| HR-специалист | `hr` | Управление сотрудниками и группами |
| Центр обучения | `training` | Курсы, прогресс, диаграмма Ганта |
| Бухгалтерия | `accounting` | Спецификации, расчёт стоимости (только чтение) |
 
### Company — Компании
 
Справочник компаний-заказчиков обучения. Каждая компания имеет уникальный код (`code`, до 10 символов) и полное наименование. Связи: `employees()`, `specifications()`. Поддерживается soft delete и hard delete.
 
Состав: `CompanyController`, `CompanyRequest`, `CompanyService`, `CompanyResource` (в `Transformers/`).
 
### Employee — Участники обучения
 
Реестр сотрудников / физических лиц, участвующих в обучении. Привязка к компании. Хранит раздельные поля ФИО (`last_name`, `first_name`, `middle_name`) и объединённое `full_name`. Уникальный табельный номер `employee_code` — используется для идентификации при XML-импорте. Accessor `getShortNameAttribute` формирует инициалы. Поддерживается soft delete и hard delete.
 
Состав: `EmployeeController`, `EmployeeRequest`, `EmployeeService`, `EmployeeResource` (в `Transformers/`).
 
### Course — Курсы обучения
 
Каталог образовательных программ с уникальным кодом (`code`), названием (`title`), описанием, длительностью (в днях). Поддерживается soft delete и hard delete.
 
Реализована **времязависимость цены** через отдельную модель `CoursePrice` (таблица `course_price`) — система хранит историю изменений цен (прайс-лист) с полями `valid_from` / `valid_to`. При изменении цены курса предыдущая запись автоматически закрывается (`valid_to` = вчера), и создаётся новая запись (`valid_from` = сегодня).
 
Состав: `CourseController`, `CoursePriceController`, `CourseService`, `CoursePriceService`, модели `Course` и `CoursePrice`, ресурсы `CourseResource`, `CourseCompactResource`, `CoursePriceResource`.
 
### Training — Учебные группы
 
Основной модуль автоматизации. Учебная группа объединяет курс и участников.
 
**TrainingGroup** — ссылка на курс, даты начала/окончания, статус обучения, привязка к спецификации, цвет для Ганта (`gantt_color`), ссылка на создателя (`created_by` → `employees`). Вычисляемые поля через Eloquent accessors: `participants_count`, `price_per_person`, `group_cost`, `average_progress`. Scopes: `withStatus`, `inPeriod`, `conflictsWith`.
 
**GroupParticipant** — связь сотрудника с группой: `completion_percent`, `certificate_path`, `enrolled_at`, `completed_at`. Уникальный составной индекс `[training_group_id, employee_id]`.
 
**Статусная модель (state machine):**
 
```
Планируется ──► В процессе ──► Завершено
     │                │
     └────────────────┴──► Отменено
```
 
Переходы валидируются через `TrainingStatus::canTransitionTo()`. Отдельный endpoint `PATCH /status`.
 
**Observer** (`GroupParticipantObserver`) — при CRUD-операциях над участником сбрасывает кеш связей группы для пересчёта стоимости и прогресса.
 
**Policy** (`TrainingGroupPolicy`) — удаление запрещено, если группа привязана к спецификации. Создание/обновление: HR + TrainingCenter. Удаление: только HR.
 
**Сертификаты** (`CertificateController`) — загрузка PDF через API, хранение в `storage/app/public/certificates/{group_id}/{participant_id}.pdf`, скачивание через проксируемый endpoint.
 
**Конфликты** — endpoint `GET /conflicts` + scope `conflictsWith`.
 
Сервисы: `TrainingGroupService`, `CostCalculationService`, `ProgressService`.
 
### Specification — Спецификации
 
Юридический документ, объединяющий несколько учебных групп и агрегирующий стоимость. Привязка групп через отдельные endpoint (attach/detach). Одна группа может быть привязана только к одной спецификации (проверка с `DomainException`). При удалении спецификации группы открепляются.
 
Вычисляемые поля через accessors: `total_without_vat`, `vat_amount` (22% через trait `CalculatesVAT`), `total_with_vat`, `groups_count`.
 
Реализует контракты `CalculableContract` и `HasCompanyScope`.
 
Состав: `SpecificationController`, `StoreSpecificationRequest`, `UpdateSpecificationRequest`, `SpecificationResource`, `SpecificationService`, `SpecificationPolicy` (создание/обновление: Accounting + HR; удаление: только Accounting).
 
### Gantt — Диаграмма Ганта
 
Визуализация расписания обучения на интерактивной временной шкале.
 
Возможности: отображение групп с прогресс-баром; масштабы неделя/месяц/квартал; drag-and-drop дат; подсветка конфликтов; тултипы; маркер «сегодня»; экспорт CSV/JSON (BOM для Excel); навигация; кеширование данных; детерминированная палитра цветов по `course_id` с ручным переопределением.
 
Бэкенд: `GanttController`, `GanttColorService`, `GanttExportService`, 4 Request-класса, `GanttItemResource`.
 
### Analytics — Аналитика по компаниям
 
Два уровня детализации:
 
**Сводная** (`GET /api/analytics/companies`) — по каждой компании: всего сотрудников, обученных сотрудников, учебных групп, спецификаций, стоимость (без НДС и с НДС 22%), средний прогресс. Расчёт стоимости через SQL с учётом актуальной цены (`MAX(valid_from) WHERE valid_from <= CURDATE()`).
 
**Детализация** (`GET /api/analytics/companies/{id}`) — список сотрудников с прогрессом, спецификации, распределение по статусам.
 
Состав: `AnalyticsController`, `AnalyticsService`.
 
### Xml — XML-интеграция с Global ERP
 
Полноценный модуль импорта. Автоопределение типа по корневому тегу XML:
- `Edu_Participant` / `Participants` — сотрудники (upsert по `employee_code`, автосоздание компании);
- `Edu_Course` / `Courses` — курсы (upsert по `sCode`, синхронизация цены с историчностью);
- `Edu_Specification` / `Specifications` — комплексный: спецификация + группы + курсы + участники.
 
Каждая запись в отдельной транзакции — ошибка одной не откатывает весь пакет.
 
**Логирование**: `XmlImportBatch` (хранит raw XML, счётчики success/error/skipped) и `XmlImportLog` (детальный лог по каждой записи).
 
Состав: 3 парсера, 3 импортёра, `XmlImportService`, `XmlImportController`, `XmlImportRequest`.
 
---
 
## Функциональные возможности
 
### Реализованные (обязательные)
 
- [x] CRUD для всех справочников (компании, сотрудники, курсы) с поддержкой soft/hard delete
- [x] Создание и управление учебными группами
- [x] Привязка участников к группам с отслеживанием прогресса
- [x] Автоматический расчёт стоимости обучения (через Eloquent accessors)
- [x] Формирование спецификаций с расчётом НДС (22%)
- [x] Статусная модель учебных групп (state machine с валидацией переходов)
- [x] Аутентификация и ролевая авторизация (4 роли, Policies, RoleMiddleware)
- [x] Диаграмма Ганта с отображением групп на временной шкале
 
### Реализованные (дополнительные, за бонусные баллы)
 
- [x] **Интерактивная диаграмма Ганта** — drag-and-drop изменение дат, переключение масштабов (неделя/месяц/квартал), экспорт в CSV и JSON
- [x] **Обнаружение конфликтов расписания** — подсветка групп по одному курсу с пересекающимися датами (scope `conflictsWith` + отдельный endpoint)
- [x] **Хранение сертификатов** — загрузка, хранение и скачивание PDF-сертификатов для участников
- [x] **Прогресс-бар внутри диаграммы Ганта** — визуальный индикатор среднего прогресса по группе
- [x] **Времязависимость цены курса** — прайс-лист (`course_price`) с историей изменений (`valid_from` / `valid_to`), автозакрытие старой цены при создании новой
- [x] **XML-интеграция с Global ERP** — импорт сотрудников, курсов и спецификаций из XML-файлов с автоопределением типа, upsert-стратегией и детальным логированием
- [x] **Аналитический модуль** — сводная и детальная аналитика по компаниям (обученные сотрудники, стоимость с НДС, распределение по статусам)
- [x] **Docker-контейнеризация** — Docker Compose для унифицированного развёртывания
- [x] **Soft delete** — мягкое удаление с возможностью полного удаления для компаний, сотрудников, курсов и учебных групп
 
### UX/UI
 
- Бизнес-ориентированный дизайн на базе shadcn/ui
- Не более 2–3 кликов до карточки группы
- Интуитивная навигация по объектам
- Toast-уведомления для обратной связи (успех, ошибки валидации)
- Тёмная тема
 
---
 
## Бизнес-логика расчётов
 
### Расчёт стоимости группы
 
**Сервис:** `CostCalculationService`
 
```
Стоимость группы = Цена курса за человека × Количество участников
```
 
- Цена берётся из последней записи прайс-листа связанного курса (через `Course::getLastPriceNumeric()` → `CoursePrice::latest()`).
- Количество участников = число записей в `group_participants` для данной группы.
- Пересчёт происходит автоматически при изменении состава участников: `GroupParticipantObserver` вызывает `recalculateCost()`, который сбрасывает кеш связей, чтобы accessors `group_cost` и `average_progress` пересчитались при следующем обращении.
- Стоимость вычисляется на лету через Eloquent accessor (`getGroupCostAttribute`), не хранится в БД — гарантия актуальности.
 
### Расчёт прогресса группы
 
**Сервис:** `ProgressService`
 
```
Средний прогресс = AVG(completion_percent) по всем участникам группы
```
 
- Каждый участник группы имеет поле `completion_percent` (0–100%, тип `decimal(5,2)`).
- Средний прогресс — среднее арифметическое через SQL `AVG()`.
- Значение округляется до 2 знаков после запятой.
- `ProgressService` предоставляет: обновление прогресса участника (с ограничением 0–100), массовое обновление прогресса всей группы.
 
### Расчёт стоимости спецификации
 
**Модель:** `Specification` (trait `CalculatesVAT`)
 
```
Сумма без НДС = Σ(group_cost) по всем привязанным группам
НДС (22%)     = Сумма без НДС × 0.22
Итого с НДС   = Сумма без НДС + НДС
```
 
- Спецификация агрегирует стоимости всех привязанных учебных групп через accessor `getTotalWithoutVatAttribute`.
- Расчёт НДС вынесен в trait `CalculatesVAT` (модуль Core): методы `calculateVAT()` и `calculateTotalWithVAT()`.
 
### Обнаружение конфликтов расписания
 
```
Конфликт: group_A.course_id == group_B.course_id
          AND group_A.start_date <= group_B.end_date
          AND group_A.end_date >= group_B.start_date
          AND group_A.id != group_B.id
```
 
Реализован через scope `scopeConflictsWith`. Конфликтные группы помечаются в API-ответе Ганта массивом `conflict_ids` и подсвечиваются на диаграмме. Доступен отдельный endpoint `GET /api/training-groups/{id}/conflicts`.
 
---
 
## API-эндпоинты
 
Все эндпоинты (кроме auth) требуют заголовок `Authorization: Bearer {token}`.
 
### Auth (`/api/auth`)
 
| Метод | Путь | Описание |
|---|---|---|
| POST | `/api/auth/register` | Регистрация (роль HR по умолчанию) |
| POST | `/api/auth/login` | Вход (старые токены отзываются) |
| POST | `/api/auth/logout` | Выход (отзыв текущего токена) |
| GET | `/api/auth/me` | Текущий пользователь |
 
### Companies (`/api/companies`)
 
| Метод | Путь | Описание |
|---|---|---|
| GET | `/api/companies/list` | Список всех компаний |
| POST | `/api/companies/create` | Создать компанию |
| POST | `/api/companies/{id}` | Обновить компанию |
| DELETE | `/api/companies/{id}/soft` | Мягкое удаление |
| DELETE | `/api/companies/{id}/hard` | Полное удаление |
 
### Employees (`/api/employees`)
 
| Метод | Путь | Описание |
|---|---|---|
| GET | `/api/employees/list` | Список сотрудников (с компанией) |
| POST | `/api/employees/create` | Создать сотрудника |
| POST | `/api/employees/{id}` | Обновить сотрудника |
| DELETE | `/api/employees/{id}/soft` | Мягкое удаление |
| DELETE | `/api/employees/{id}/hard` | Полное удаление |
 
### Courses (`/api/courses`)
 
| Метод | Путь | Описание |
|---|---|---|
| GET | `/api/courses/list` | Список курсов (с последней ценой) |
| POST | `/api/courses/create` | Создать курс (автосоздание записи цены) |
| POST | `/api/courses/{id}` | Обновить курс (при изменении цены — новая запись в прайс-лист) |
| DELETE | `/api/courses/{id}/soft` | Мягкое удаление |
| DELETE | `/api/courses/{id}/hard` | Полное удаление |
 
### Course Prices — прайс-лист (`/api/course_price`)
 
| Метод | Путь | Описание |
|---|---|---|
| GET | `/api/course_price/{courseId}/list` | История цен курса |
| POST | `/api/course_price/{courseId}/create` | Добавить новую цену (автозакрытие предыдущей) |
 
### Training Groups (`/api/training-groups`)
 
| Метод | Путь | Описание |
|---|---|---|
| GET | `/api/training-groups` | Список групп (пагинация, фильтр по статусу) |
| POST | `/api/training-groups` | Создать группу (статус `planned` по умолчанию) |
| GET | `/api/training-groups/{id}` | Получить группу с курсом и участниками |
| PUT | `/api/training-groups/{id}` | Обновить группу |
| DELETE | `/api/training-groups/{id}` | Удалить группу (каскадное удаление участников) |
| PATCH | `/api/training-groups/{id}/status` | Сменить статус (с валидацией переходов) |
| GET | `/api/training-groups/{id}/conflicts` | Проверить конфликты расписания |
 
### Group Participants (вложенный ресурс)
 
| Метод | Путь | Описание |
|---|---|---|
| GET | `/api/training-groups/{id}/participants` | Список участников группы |
| POST | `/api/training-groups/{id}/participants` | Добавить участника |
| PATCH | `/api/training-groups/{id}/participants/{pid}` | Обновить прогресс участника |
| DELETE | `/api/training-groups/{id}/participants/{pid}` | Удалить участника |
 
### Certificates
 
| Метод | Путь | Описание |
|---|---|---|
| POST | `/api/training-groups/{id}/participants/{pid}/certificate` | Загрузить PDF-сертификат |
| GET | `/api/training-groups/{id}/participants/{pid}/certificate` | Скачать сертификат |
| DELETE | `/api/training-groups/{id}/participants/{pid}/certificate` | Удалить сертификат |
 
### Specifications (`/api/specifications`)
 
| Метод | Путь | Описание |
|---|---|---|
| GET | `/api/specifications` | Список спецификаций (пагинация, фильтр по `company_id`) |
| POST | `/api/specifications` | Создать спецификацию |
| GET | `/api/specifications/{id}` | Получить с группами и расчётами |
| PUT | `/api/specifications/{id}` | Обновить спецификацию |
| DELETE | `/api/specifications/{id}` | Удалить (группы открепляются) |
| POST | `/api/specifications/{id}/groups/{groupId}` | Привязать группу |
| DELETE | `/api/specifications/{id}/groups/{groupId}` | Отвязать группу |
 
### Gantt (`/api/gantt`)
 
| Метод | Путь | Описание |
|---|---|---|
| GET | `/api/gantt?from=&to=&status=&course_id=` | Данные для диаграммы (с конфликтами и палитрой) |
| PATCH | `/api/gantt/{id}/dates` | Изменить даты группы (drag-and-drop) |
| PATCH | `/api/gantt/{id}/color` | Изменить цвет группы |
| GET | `/api/gantt/export?from=&to=&format=csv` | Экспорт (CSV или JSON) |
 
### Analytics (`/api/analytics`)
 
| Метод | Путь | Описание |
|---|---|---|
| GET | `/api/analytics/companies` | Сводная аналитика по всем компаниям |
| GET | `/api/analytics/companies/{id}` | Детализация по компании |
 
### XML Import (`/api/xml`)
 
| Метод | Путь | Описание |
|---|---|---|
| POST | `/api/xml/import` | Загрузка и импорт XML-файла (автоопределение типа) |
| GET | `/api/xml/batches` | История импортов (пагинация) |
| GET | `/api/xml/batches/{id}` | Детали импорта (с логами) |
| GET | `/api/xml/batches/{id}/logs` | Логи конкретного импорта |
 
---
 
## Установка и запуск
 
### Docker (при помощи Makefile)
 
```bash
# Клонирование репозитория
git clone <repo-url>
cd TF-Hakaton
 
cd backend
cp .env.example .env
 
# Настройка БД в .env
# DB_CONNECTION=mysql
# DB_HOST=mysql
# DB_PORT=3306
# DB_DATABASE=erp_training
# DB_USERNAME=erp_user
# DB_PASSWORD=secret
 
# Запуск контейнеров
make up
 
make composer CMD="install"
 
make artisan CMD="migrate"
make artisan CMD="db:seed"
make artisan CMD="storage:link"
 
make composer CMD="dump-autoload"
```
 
### Frontend
 
```bash
# Установка зависимостей
npm install
 
# Запуск dev-сервера
npm run dev
```
 
---
