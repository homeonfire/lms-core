# 🗄 LMS Core — Database Schema Reference

> Документ сгенерирован на основе всех миграций проекта (25 файлов).
> Актуален на: 2025-11-28 (последняя миграция: `add_payment_id_to_orders_table`).
> **PostgreSQL** — основная СУБД. Активно используется тип `JSONB`.

---

## Содержание

1. [Пользователи и авторизация](#1-пользователи-и-авторизация)
2. [Курсы и структура контента](#2-курсы-и-структура-контента)
3. [Домашние задания и прогресс](#3-домашние-задания-и-прогресс)
4. [Заказы и коммерция](#4-заказы-и-коммерция)
5. [Рассылки и формы](#5-рассылки-и-формы)
6. [Системные таблицы](#6-системные-таблицы)
7. [Spatie Permission (RBAC)](#7-spatie-permission-rbac)
8. [ERD-схема связей](#8-erd-схема-связей)

---

## 1. Пользователи и авторизация

### `users`
Основная таблица пользователей (студенты, преподаватели, кураторы, менеджеры, администраторы).

| Колонка | Тип | Nullable | Описание |
|---|---|---|---|
| `id` | bigint PK | — | |
| `name` | string | — | |
| `email` | string UNIQUE | — | |
| `email_verified_at` | timestamp | ✓ | |
| `password` | string | — | |
| `remember_token` | string | ✓ | |
| `avatar_url` | string | ✓ | URL аватара |
| `phone` | string | ✓ | Телефон |
| `last_seen_at` | timestamp | ✓ | Последний визит |
| `is_active` | boolean | — | default: `true` |
| `utm_data` | **jsonb** | ✓ | UTM-метки при регистрации |
| `accepted_offer_at` | timestamp | ✓ | Дата принятия оферты |
| `accepted_policy_at` | timestamp | ✓ | Дата принятия политики |
| `accepted_marketing_at` | timestamp | ✓ | Дата согласия на рекламу |
| `deleted_at` | timestamp | ✓ | SoftDeletes |
| `created_at` / `updated_at` | timestamps | — | |

### `password_reset_tokens`
| Колонка | Тип | Описание |
|---|---|---|
| `email` | string PK | |
| `token` | string | |
| `created_at` | timestamp | |

### `sessions`
| Колонка | Тип | Описание |
|---|---|---|
| `id` | string PK | |
| `user_id` | bigint | indexed, nullable |
| `ip_address` | string(45) | |
| `user_agent` | text | |
| `payload` | longText | |
| `last_activity` | integer | indexed |

### `personal_access_tokens`
Sanctum-токены для API.

| Колонка | Тип | Описание |
|---|---|---|
| `id` | bigint PK | |
| `tokenable_type` | string | Polymorphic |
| `tokenable_id` | bigint | Polymorphic |
| `name` | text | Название токена |
| `token` | string(64) UNIQUE | Хэш токена |
| `abilities` | text | JSON-список abilities |
| `last_used_at` | timestamp | |
| `expires_at` | timestamp | indexed |

---

## 2. Курсы и структура контента

### `courses`
| Колонка | Тип | Nullable | Описание |
|---|---|---|---|
| `id` | bigint PK | — | |
| `teacher_id` | bigint FK → users | — | Автор/преподаватель |
| `title` | string | — | |
| `slug` | string UNIQUE | — | ЧПУ-ссылка |
| `description` | text | ✓ | |
| `thumbnail_url` | string | ✓ | Обложка курса |
| `price` | unsignedInt | — | Цена в копейках, default: `0` |
| `starts_at` | dateTime | ✓ | Дата начала потока |
| `ends_at` | dateTime | ✓ | Дата окончания потока |
| `is_published` | boolean | — | default: `false` |
| `telegram_channel_link` | string | ✓ | Ссылка на Telegram-канал |
| `telegram_chat_link` | string | ✓ | Ссылка на Telegram-чат |
| `deleted_at` | timestamp | ✓ | SoftDeletes |
| `created_at` / `updated_at` | timestamps | — | |

### `course_modules`
Рекурсивное дерево модулей (Модуль → Подмодуль → Глава → ...). Бесконечная вложенность через `parent_id`.

| Колонка | Тип | Nullable | Описание |
|---|---|---|---|
| `id` | bigint PK | — | |
| `course_id` | bigint FK → courses | — | CASCADE delete |
| `parent_id` | bigint FK → course_modules | ✓ | Родительский модуль (nullable = корень) |
| `title` | string | — | |
| `description` | text | ✓ | |
| `sort_order` | integer | — | default: `0` |
| `created_at` / `updated_at` | timestamps | — | |

> ⚠️ При выборке всегда использовать eager loading: `with('children.lessons')` или рекурсивный CTE для глубокого дерева.

### `lessons`
Конечный узел дерева. Прикреплен к модулю.

| Колонка | Тип | Nullable | Описание |
|---|---|---|---|
| `id` | bigint PK | — | |
| `module_id` | bigint FK → course_modules | — | CASCADE delete |
| `title` | string | — | |
| `slug` | string | — | |
| `is_stop_lesson` | boolean | — | default: `false` — блокирует переход без сдачи ДЗ |
| `is_published` | boolean | — | default: `true` |
| `available_at` | dateTime | ✓ | Дата открытия (расписание) |
| `duration_minutes` | integer | — | default: `0` |
| `sort_order` | integer | — | default: `0` |
| `deleted_at` | timestamp | ✓ | SoftDeletes |
| `created_at` / `updated_at` | timestamps | — | |

### `content_blocks`
Блоки контента внутри урока. Паттерн «лонгрид».

| Колонка | Тип | Nullable | Описание |
|---|---|---|---|
| `id` | bigint PK | — | |
| `lesson_id` | bigint FK → lessons | — | CASCADE delete |
| `type` | string (indexed) | — | `text`, `video_youtube`, `video_vk`, `video_rutube`, `image`, `file`, `quiz` |
| `content` | **jsonb** | — | Структура зависит от `type` (см. ниже) |
| `sort_order` | integer | — | default: `0` |
| `is_visible` | boolean | — | default: `true` |
| `created_at` / `updated_at` | timestamps | — | |

**Структура `content` по типам:**
```json
// text
{ "html": "<p>...</p>" }

// video_youtube / video_vk / video_rutube
{ "video_id": "dQw4w9WgXcQ", "title": "..." }

// image
{ "url": "https://...", "alt": "..." }

// file
{ "url": "https://...", "name": "Материал.pdf", "size": 204800 }

// quiz
{
  "question": "Текст вопроса",
  "options": ["A", "B", "C", "D"],
  "correct_index": 2,
  "explanation": "..."
}
```

### `course_curator` (pivot)
Связь Куратор ↔ Курс (Many-to-Many).

| Колонка | Тип | Описание |
|---|---|---|
| `id` | bigint PK | |
| `course_id` | bigint FK → courses | CASCADE |
| `user_id` | bigint FK → users | CASCADE — ID куратора |
| `created_at` / `updated_at` | timestamps | |
> UNIQUE(`course_id`, `user_id`) — защита от дублей.

---

## 3. Домашние задания и прогресс

### `homeworks`
Настройки домашнего задания для урока (один урок — одно ДЗ).

| Колонка | Тип | Nullable | Описание |
|---|---|---|---|
| `id` | bigint PK | — | |
| `lesson_id` | bigint FK → lessons | — | CASCADE delete |
| `description` | text | — | Текст задания |
| `submission_fields` | **jsonb** | ✓ | Схема полей для ответа (собирается в Filament Repeater) |
| `is_required` | boolean | — | default: `true` |
| `created_at` / `updated_at` | timestamps | — | |

**Пример `submission_fields`:**
```json
[
  { "type": "text", "label": "Ссылка на работу", "required": true },
  { "type": "file", "label": "Загрузите файл", "required": false }
]
```

### `homework_submissions`
Ответы студентов на ДЗ.

| Колонка | Тип | Nullable | Описание |
|---|---|---|---|
| `id` | bigint PK | — | |
| `homework_id` | bigint FK → homeworks | — | CASCADE delete |
| `student_id` | bigint FK → users | — | CASCADE |
| `curator_id` | bigint FK → users | ✓ | Кто проверил |
| `content` | **jsonb** | — | Ответы студента на поля формы |
| `status` | string | — | `pending` / `approved` / `rejected` / `revision`, default: `pending` |
| `curator_comment` | text | ✓ | Комментарий куратора |
| `grade_percent` | decimal(5,2) | ✓ | Оценка 0.00–100.00 |
| `reviewed_at` | timestamp | ✓ | Когда проверено |
| `created_at` / `updated_at` | timestamps | — | |

### `lesson_user` (pivot — прогресс)
Отслеживание прохождения уроков студентом.

| Колонка | Тип | Nullable | Описание |
|---|---|---|---|
| `id` | bigint PK | — | |
| `user_id` | bigint FK → users | — | CASCADE |
| `lesson_id` | bigint FK → lessons | — | CASCADE |
| `unlocked_at` | timestamp | ✓ | Когда открыт доступ к уроку |
| `completed_at` | timestamp | ✓ | Когда урок помечен пройденным |
> UNIQUE(`user_id`, `lesson_id`)

### `test_results`
Результаты прохождения тестов (блок типа `quiz`).

| Колонка | Тип | Nullable | Описание |
|---|---|---|---|
| `id` | bigint PK | — | |
| `user_id` | bigint FK → users | — | CASCADE |
| `content_block_id` | bigint FK → content_blocks | — | CASCADE (тест — это блок контента) |
| `score_percent` | unsignedInt | — | 0–100 |
| `is_passed` | boolean | — | Сдан или нет |
| `user_answers` | **jsonb** | ✓ | История ответов |
| `created_at` / `updated_at` | timestamps | — | |

---

## 4. Заказы и коммерция

### `tariffs`
Тарифы внутри курса (Базовый, VIP и т.д.).

| Колонка | Тип | Nullable | Описание |
|---|---|---|---|
| `id` | bigint PK | — | |
| `course_id` | bigint FK → courses | — | CASCADE |
| `name` | string | — | Название тарифа |
| `price` | unsignedInt | — | Цена в копейках, default: `0` |
| `telegram_channel_link` | string | ✓ | |
| `telegram_chat_link` | string | ✓ | |
| `created_at` / `updated_at` | timestamps | — | |

### `module_tariff` (pivot)
Модули, входящие в тариф.

| Колонка | Тип | Описание |
|---|---|---|
| `id` | bigint PK | |
| `course_module_id` | bigint FK → course_modules | CASCADE |
| `tariff_id` | bigint FK → tariffs | CASCADE |

### `lesson_tariff` (pivot)
Уроки, входящие в тариф (альтернативная гранулярность).

| Колонка | Тип | Описание |
|---|---|---|
| `id` | bigint PK | |
| `lesson_id` | bigint FK → lessons | CASCADE |
| `tariff_id` | bigint FK → tariffs | CASCADE |

### `orders`
Заказы — основной механизм управления доступом.

| Колонка | Тип | Nullable | Описание |
|---|---|---|---|
| `id` | bigint PK | — | |
| `user_id` | bigint FK → users | — | Покупатель, CASCADE |
| `course_id` | bigint FK → courses | — | CASCADE |
| `tariff_id` | bigint FK → tariffs | ✓ | nullOnDelete |
| `manager_id` | bigint FK → users | ✓ | Ответственный менеджер |
| `status` | string | — | `new` / `paid` / `cancelled`, default: `new` |
| `amount` | unsignedInt | — | Сумма в копейках |
| `payment_id` | string (indexed) | ✓ | ID платежа во внешней системе |
| `payment_method` | string | — | `manual` / `tilda` / `yookassa`, default: `manual` |
| `history_log` | **jsonb** | ✓ | История изменений заказа |
| `utm_data` | **jsonb** | ✓ | UTM-метки заказа |
| `paid_at` | timestamp | ✓ | |
| `created_at` / `updated_at` | timestamps | — | |

> **Правило доступа:** студент имеет доступ к урокам только при наличии `Order` со статусом `paid` на соответствующий курс/тариф.
> **Бесплатный курс:** `price = 0` → заказ автоматически переходит в `paid`.

### `order_notes`
Заметки менеджеров по заказу (внутренние, не видны студенту).

| Колонка | Тип | Nullable | Описание |
|---|---|---|---|
| `id` | bigint PK | — | |
| `order_id` | bigint FK → orders | — | CASCADE |
| `user_id` | bigint FK → users | — | Кто написал |
| `content` | text | — | Текст заметки |
| `is_private` | boolean | — | default: `true` |
| `created_at` / `updated_at` | timestamps | — | |

---

## 5. Рассылки и формы

### `newsletters`
Email-рассылки.

| Колонка | Тип | Nullable | Описание |
|---|---|---|---|
| `id` | bigint PK | — | |
| `subject` | string | — | Тема письма |
| `content` | longText | — | HTML-тело |
| `recipients_filter` | **jsonb** | ✓ | Фильтр получателей: `{"course_id": [1,2], "tariff_id": [5]}` |
| `scheduled_at` | dateTime | ✓ | Запланированное время отправки |
| `sent_at` | dateTime | ✓ | Фактическое время отправки |
| `status` | string | — | `draft` / `scheduled` / `processing` / `sent`, default: `draft` |
| `created_at` / `updated_at` | timestamps | — | |

### `forms`
Конструктор форм (лиды, опросы, квизы).

| Колонка | Тип | Nullable | Описание |
|---|---|---|---|
| `id` | bigint PK | — | |
| `title` | string | — | |
| `slug` | string UNIQUE | — | |
| `schema` | **jsonb** | — | Структура полей формы |
| `settings` | **jsonb** | ✓ | Текст кнопки, сообщение успеха |
| `is_active` | boolean | — | default: `true` |
| `created_at` / `updated_at` | timestamps | — | |

### `form_submissions`
Ответы на формы.

| Колонка | Тип | Nullable | Описание |
|---|---|---|---|
| `id` | bigint PK | — | |
| `form_id` | bigint FK → forms | — | CASCADE |
| `user_id` | bigint FK → users | ✓ | nullOnDelete (анонимы) |
| `data` | **jsonb** | — | Ответы пользователя |
| `utm_data` | **jsonb** | ✓ | UTM-метки сабмита |
| `created_at` / `updated_at` | timestamps | — | |

### `pages`
Статические страницы (Оферта, Политика конфиденциальности).

| Колонка | Тип | Nullable | Описание |
|---|---|---|---|
| `id` | bigint PK | — | |
| `title` | string | — | Заголовок |
| `slug` | string UNIQUE | — | URL-путь |
| `content` | longText | ✓ | HTML-содержимое |
| `is_published` | boolean | — | default: `true` |
| `created_at` / `updated_at` | timestamps | — | |

---

## 6. Системные таблицы

### `system_settings`
Key-value хранилище настроек системы.

| Колонка | Тип | Nullable | Описание |
|---|---|---|---|
| `id` | bigint PK | — | |
| `group` | string (indexed) | — | `smtp`, `general`, `ui` |
| `key` | string UNIQUE | — | `mail_host`, `logo_url` |
| `payload` | **jsonb** | ✓ | Значение (строка, bool, JSON) |
| `is_locked` | boolean | — | default: `false` — защита от случайного изменения |
| `created_at` / `updated_at` | timestamps | — | |

### `cache` / `cache_locks`
Стандартные Laravel-таблицы для кэша.

### `jobs` / `job_batches` / `failed_jobs`
Стандартные Laravel-таблицы для очередей.

---

## 7. Spatie Permission (RBAC)

### Роли в проекте (из `RolesSeeder`)
| Роль | Описание |
|---|---|
| `Super Admin` | Полный доступ к системе, управление Filament-панелью |
| `Teacher` | Преподаватель (видит свои курсы) |
| *(curator)* | Куратор назначается через `course_curator` pivot-таблицу |

### Таблицы Spatie
| Таблица | Описание |
|---|---|
| `permissions` | Список прав (`name`, `guard_name`) |
| `roles` | Список ролей (`name`, `guard_name`) |
| `model_has_permissions` | Прямые права у моделей (polymorphic) |
| `model_has_roles` | Роли у моделей (polymorphic) |
| `role_has_permissions` | Права, назначенные ролям |

---

## 8. ERD-схема связей

```
users
 ├── has many → courses (teacher_id)
 ├── has many → orders (user_id / manager_id)
 ├── has many → homework_submissions (student_id / curator_id)
 ├── has many → order_notes (user_id)
 ├── has many → test_results (user_id)
 ├── belongs to many → lessons (lesson_user — прогресс)
 └── belongs to many → courses (course_curator — кураторство)

courses
 ├── has many → course_modules
 ├── has many → tariffs
 ├── has many → orders
 └── belongs to many → users (course_curator)

course_modules (рекурсивно)
 ├── parent_id → course_modules (self-reference)
 ├── has many → course_modules (children)
 ├── has many → lessons
 └── belongs to many → tariffs (module_tariff)

lessons
 ├── has one → homework
 ├── has many → content_blocks
 ├── belongs to many → users (lesson_user — прогресс)
 └── belongs to many → tariffs (lesson_tariff)

content_blocks
 └── has many → test_results

homeworks
 └── has many → homework_submissions

orders
 ├── belongs to → tariff
 └── has many → order_notes

tariffs
 ├── belongs to many → course_modules (module_tariff)
 └── belongs to many → lessons (lesson_tariff)

forms
 └── has many → form_submissions
```

---

## Итоговая таблица: все таблицы проекта

| Таблица | Кол-во JSONB полей | SoftDeletes | Назначение |
|---|---|---|---|
| `users` | 1 (`utm_data`) | ✓ | Все пользователи системы |
| `courses` | — | ✓ | Каталог курсов |
| `course_modules` | — | — | Дерево модулей курса |
| `lessons` | — | ✓ | Уроки |
| `content_blocks` | 1 (`content`) | — | Блоки контента урока |
| `homeworks` | 1 (`submission_fields`) | — | Настройки ДЗ |
| `homework_submissions` | 1 (`content`) | — | Ответы студентов |
| `lesson_user` | — | — | Прогресс студента |
| `test_results` | 1 (`user_answers`) | — | Результаты тестов |
| `tariffs` | — | — | Тарифы курса |
| `orders` | 2 (`history_log`, `utm_data`) | — | Заказы (доступ) |
| `order_notes` | — | — | Заметки по заказу |
| `course_curator` | — | — | Связь куратор-курс |
| `module_tariff` | — | — | Связь модуль-тариф |
| `lesson_tariff` | — | — | Связь урок-тариф |
| `newsletters` | 1 (`recipients_filter`) | — | Email-рассылки |
| `forms` | 2 (`schema`, `settings`) | — | Конструктор форм |
| `form_submissions` | 2 (`data`, `utm_data`) | — | Ответы на формы |
| `pages` | — | — | Статические страницы |
| `system_settings` | 1 (`payload`) | — | Настройки системы |
| `personal_access_tokens` | — | — | Sanctum-токены |
| `sessions` | — | — | Сессии |
| `password_reset_tokens` | — | — | Сброс пароля |
| `cache` / `cache_locks` | — | — | Кэш Laravel |
| `jobs` / `job_batches` / `failed_jobs` | — | — | Очереди Laravel |
| `permissions` / `roles` / `model_has_*` / `role_has_*` | — | — | Spatie RBAC |
