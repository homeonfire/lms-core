# 🚦 LMS Core — Project Status & Runtime Context

> Проверка работоспособности проведена: **2026-06-23**
> Окружение: Docker (Laravel Sail), PostgreSQL 17, Redis, Mailpit
> Все сервисы запущены и работают штатно.

---

## 1. 🐳 Docker-инфраструктура

| Контейнер | Image | Порты | Статус |
|---|---|---|---|
| `lms-core-laravel.test-1` | `sail-8.4/app` | `80:80`, `5173:5173` | ✅ Up |
| `lms-core-pgsql-1` | `postgres:17-alpine` | `5432:5432` | ✅ Up, healthy |
| `lms-core-redis-1` | `redis:alpine` | `6379:6379` | ✅ Up, healthy |
| `lms-core-mailpit-1` | `axllent/mailpit:latest` | `1025:1025`, `8025:8025` | ✅ Up, healthy |

### Ключевые переменные окружения (`.env`)
```
APP_NAME=GC Hub
APP_URL=http://localhost
APP_ENV=local
APP_DEBUG=true

DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=lms_prod_db
DB_USERNAME=lms_prod_user

QUEUE_CONNECTION=database
CACHE_STORE=redis
SESSION_DRIVER=redis

REDIS_HOST=redis
REDIS_PORT=6379
```

---

## 2. 🌐 Доступные URL

| URL | Описание | Статус |
|---|---|---|
| `http://localhost/` | Публичная лендинговая страница | ✅ |
| `http://localhost/courses` | Каталог курсов (студент) | ✅ |
| `http://localhost/login` | Логин студентского портала | ✅ |
| `http://localhost/dashboard` | Личный кабинет студента | ✅ |
| `http://localhost/admin` | Filament Admin Panel | ✅ |
| `http://localhost:8025` | Mailpit (почтовый дашборд) | ✅ |

---

## 3. 👤 Учётные данные администратора

```
Email:    i@aifire.ru
Password: 354645451
Role:     Super Admin
```

> Пользователь создан через `RolesSeeder` (User ID = 1, роль `Super Admin`).

---

## 4. 🗂 Filament Admin Panel — Структура меню

Меню разделено на группы:

### Инфопанель
- **Инфопанель** — дашборд с виджетами

### Маркетинг
- **Аналитика Анкет** — статистика по форм-сабмитам
- **Анкеты и Формы** — конструктор форм (`forms`)
- **Рассылки** — email-рассылки (`newsletters`)

### Настройки системы
- **Импорт студентов** — массовый импорт пользователей
- **Настройки ЮKassa / P2P** — конфигурация платёжного шлюза
- **Платежи / Тильда** — логи вебхуков Tilda
- **Статичные страницы** — управление `pages` (Оферта, Политика)
- **Пользователи** — управление пользователями
- **Конструктор Главной** — настройки лендинга

### Управление контентом
- **Модули** — дерево `course_modules`
- **Курсы** — управление курсами
- **Домашние задания** — настройки `homeworks`
- **Уроки** — управление `lessons` и `content_blocks`

### Работа со студентами
- **Проверка ДЗ** — `homework_submissions` (проверка и выставление оценок)

### Продажи
- **Заказы** — управление `orders` (есть бейдж с кол-вом новых)

---

## 5. 📊 Данные на дашборде (актуальные из БД)

| Метрика | Значение |
|---|---|
| Общий доход | **6 990 000 ₽** |
| Оплаченных заказов | **3** |
| ДЗ на проверке | **0** (всё проверено) |

---

## 6. 📚 Контент в БД (Demo Data)

### Курсы
| Курс | Цена | Тарифы |
|---|---|---|
| PHP для профессионалов | от 1 500 000 ₽ | Базовый, VIP с наставником |
| UI/UX Дизайн с нуля | 990 000 ₽ | — |

### Заказы (3 оплаченных)
- Студент 1 → PHP для профессионалов (Базовый, 1 500 000 ₽)
- Студент 2 → PHP для профессионалов (VIP с наставником, 4 500 000 ₽)
- Студент 3 → UI/UX Дизайн с нуля (990 000 ₽)

---

## 7. 🎨 Студентский портал — UI

- **Layout:** боковое меню + контентная зона с фиолетово-синим градиентом
- **Навигация:** Каталог курсов / Моё обучение / Мои заказы / Профиль
- **Внизу сайдбара:** кнопка «Панель управления» (для Super Admin → /admin) и аватар пользователя

### Публичная главная
- Название: **GC Hub**
- Хедер: логотип + кнопки «Войти» / «Регистрация»
- Контент: сетка карточек курсов с ценами и кнопкой «Подробнее»

---

## 8. 🔧 Известные особенности окружения

- **Vite dev server** доступен на порту `5173` (для hot reload при разработке фронтенда)
- **Очереди** работают через `QUEUE_CONNECTION=database` — для запуска воркера: `php artisan queue:work`
- **Кэш и сессии** — Redis (`CACHE_STORE=redis`, `SESSION_DRIVER=redis`)
- **Почта** — в dev-окружении перехватывается Mailpit (`http://localhost:8025`)
- **SoftDeletes** активны у: `users`, `courses`, `lessons`
- **Роли Spatie** инициализированы: `Super Admin`, `Teacher`

---

## 9. 🚀 Полезные команды (внутри контейнера)

```bash
# Запустить очередь
docker exec lms-core-laravel.test-1 php artisan queue:work

# Миграции
docker exec lms-core-laravel.test-1 php artisan migrate

# Сиды
docker exec lms-core-laravel.test-1 php artisan db:seed

# Логи Laravel
docker exec lms-core-laravel.test-1 tail -f /var/www/html/storage/logs/laravel.log

# Artisan tinker
docker exec -it lms-core-laravel.test-1 php artisan tinker
```
