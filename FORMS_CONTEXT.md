# 📋 LMS Core — Система Анкет и Форм (Forms)

> Раздел «Анкеты и Формы» в Filament-панели.
> Проверена и задокументирована: 2026-06-23
> Тестовая анкета создана: slug `test-anketa`, ID=2

---

## 1. Архитектура модуля

```
[Admin: Filament FormResource]
         ↓ создаёт
[forms] (DB) ← schema (JSONB) — конструктор полей
         ↓ ссылается
[form_submissions] (DB) ← data (JSONB) — ответы студентов

[Public Route /f/{slug}]
    GET  → FormController@show   → Inertia: Pages/Public/FormView.vue
    POST → FormController@submit → динамическая валидация → сохраняет FormSubmission
```

---

## 2. Модели

### `App\Models\Form`
**Файл:** `app/Models/Form.php`

```php
// Касты
'schema'    => 'array'   // JSONB — конфигурация полей
'settings'  => 'array'   // JSONB — UI-настройки
'is_active' => 'boolean'

// Связи
hasMany(FormSubmission::class)
```

### `App\Models\FormSubmission`
**Файл:** `app/Models/FormSubmission.php`

```php
// Касты
'data'     => 'array'  // JSONB — ответы пользователя
'utm_data' => 'array'  // JSONB — UTM-метки

// Связи
belongsTo(Form::class)
belongsTo(User::class) // nullable — для гостей
```

---

## 3. Структура JSONB-полей

### `forms.schema` — массив объектов-полей
```json
[
  {
    "type":     "text",       // text | textarea | email | phone | select
    "label":    "Ваше имя",   // Отображаемое название
    "name":     "full_name",  // Ключ в form_submissions.data (alphaDash)
    "required": true,
    "options":  []            // только для type=select, массив строк
  }
]
```

### `forms.settings` — UI-настройки
```json
{
  "submit_text":     "Отправить",
  "success_message": "Спасибо! Анкета принята."
}
```

### `form_submissions.data` — ответы
```json
{
  "full_name": "Иван Тестовый",
  "email": "user@example.com"
}
```

### `form_submissions.utm_data` — маркетинг
```json
{
  "utm_source":   "google",
  "utm_medium":   "cpc",
  "utm_campaign": "php-course",
  "referer":      "https://google.com"
}
```

---

## 4. Filament Resource

**Файл:** `app/Filament/Resources/FormResource.php`
- Группа навигации: **Маркетинг**
- Навигационный лейбл: **Анкеты и Формы**
- Модель: `App\Models\Form as FormModel` (алиас, т.к. `Form` — зарезервирован Filament)

### Конструктор формы (UI-схема)
Двухколоночный layout (2/3 + 1/3):

**Левая колонка — Конструктор:**
- `Repeater::make('schema')` — добавление полей
  - `Select::make('type')` — тип: text / textarea / email / phone / select
  - `Toggle::make('required')` — обязательность (default: true)
  - `TextInput::make('label')` — Вопрос/Заголовок поля
  - `TextInput::make('name')` — ID поля (alphaDash, латиница)
  - `TagsInput::make('options')` — варианты (только для `type=select`, скрыт иначе)
  - Repeater поддерживает: `.cloneable()` (дублировать), `.collapsible()` (свернуть)

**Правая колонка — Настройки:**
- `TextInput::make('title')` → автогенерирует `slug` через `afterStateUpdated`
- `TextInput::make('slug')` — уникальный, prefix `/f/`
- `Toggle::make('is_active')` — активность анкеты

**Тексты интерфейса:**
- `settings.submit_text` — текст кнопки
- `settings.success_message` — сообщение успеха

### Таблица списка форм (колонки)
| Колонка | Описание |
|---|---|
| `title` | Название, bold, searchable |
| `slug` | Ссылка с prefix `/f/`, copyable |
| `submissions_count` | Кол-во заявок (badge), считается через `counts('submissions')` |
| `is_active` | Быстрый переключатель (ToggleColumn) |
| `created_at` | Дата создания |

### Действия в строке таблицы
- **Изменить** → `/admin/forms/{id}/edit`
- **Открыть** → `/f/{slug}` в новой вкладке (route: `public.form.show`)

### RelationManager: Ответы и Заявки
**Файл:** `app/Filament/Resources/FormResource/RelationManagers/SubmissionsRelationManager.php`
- Отображается внизу страницы редактирования формы
- Колонки: ID, Email пользователя, `data.email`, `data.phone`, Дата
- Действие **Просмотр** (ViewAction) открывает Infolist-модал:
  - Секция «Информация о заявке»: Дата создания, Аккаунт (user.name)
  - Секция «Ответы пользователя»: `KeyValueEntry::make('data')` — Поле → Ответ
  - Секция «Маркетинг»: `KeyValueEntry::make('utm_data')` — свёрнута по умолчанию

---

## 5. Роутинг (Public)

**Файл:** `routes/web.php`
```php
Route::get('/f/{slug}',  [FormController::class, 'show'])  ->name('public.form.show');
Route::post('/f/{form}', [FormController::class, 'submit'])->name('public.form.submit');
```

---

## 6. Контроллер

**Файл:** `app/Http/Controllers/Public/FormController.php`
**Trait:** `App\Traits\HasUtmCollection`

### `show($slug)`
1. Находит форму по `slug` + `is_active = true` (иначе 404)
2. Рендерит Inertia: `Pages/Public/FormView` с пропсом `form`

### `submit(Request $request, Form $form)` — логика
1. **Динамическая валидация** — строит `$rules` из `$form->schema`:
   - `required` / `nullable` в зависимости от флага поля
   - Для `type = email` добавляет правило `email`
   - Кастомные сообщения: `"Поле «{label}» обязательно."`

2. **Автоматическое создание/привязка пользователя** (если в схеме есть `type=email`):
   - Ищет поле с `type=email` в схеме → берёт значение из данных
   - Если пользователь **не авторизован** и email указан:
     - Ищет User по email
     - Если не найден → создаёт нового (случайный пароль, роль `Student`)
     - Отправляет `WelcomeStudent` уведомление
     - Сразу логинит через `Auth::login($user)`
   - Если пользователь уже был, но нет телефона → обновляет `users.phone`

3. **Сохраняет `FormSubmission`**:
   ```php
   FormSubmission::create([
       'form_id'  => $form->id,
       'user_id'  => $user?->id,    // nullable для гостей
       'data'     => $validated,
       'utm_data' => $this->getUtmFromCookies(),
   ]);
   ```

4. Возвращает `back()->with('success', $settings['success_message'])`

### Трейт `HasUtmCollection`
Собирает UTM из кук запроса:
- Ключи: `utm_source`, `utm_medium`, `utm_campaign`, `utm_term`, `utm_content`, `gclid`, `yclid`, `fbclid`
- Добавляет `referer` если нет `utm_source`

---

## 7. Vue-компонент (Frontend)

**Файл:** `resources/js/Pages/Public/FormView.vue`
**Layout:** `PublicLayout.vue`

### Логика (`<script setup>`)
```js
// Пропс от Inertia
const props = defineProps({ form: Object });

// Определяет текущего пользователя
const user = computed(() => usePage().props.auth?.user);

// Предзаполняет formData из схемы
// - поля email/phone подставляет из user если авторизован
props.form.schema.forEach(field => { /* ... */ });

const formState = useForm(formData);

// POST на /f/{form.id}
const submit = () => formState.post(route('public.form.submit', props.form.id), {
    preserveScroll: true,
    onSuccess: () => formState.reset(),
});
```

### Шаблон — рендер полей по типу
| Тип поля | HTML-элемент | Особенность |
|---|---|---|
| `text`, `email`, `phone` | `<input>` | email: disabled если пользователь авторизован |
| `textarea` | `<textarea rows="3">` | |
| `select` | `<select>` | Варианты из `field.options[]` |

### Flash-сообщение
- После успешной отправки показывает `$page.props.flash?.success`
- Форма скрывается (`v-else` на `<form>`)

---

## 8. Пример тестовой анкеты (созданной)

| Параметр | Значение |
|---|---|
| ID | 2 |
| Название | Тестовая анкета |
| Slug | `test-anketa` |
| URL | `http://localhost/f/test-anketa` |
| Поля | `full_name` (text, required) |
| Кнопка | «Отправить» |
| Сообщение | «Спасибо! Анкета принята.» |
| Заявок | 1 (от пользователя «Иван», `full_name: Иван Тестовый`) |

---

## 9. Ключевые особенности и нюансы

> [!IMPORTANT]
> `Form as FormModel` — модель нужно импортировать с алиасом в Filament, потому что `Form` — зарезервированное имя класса Filament Forms.

> [!WARNING]
> Валидация в `FormController::submit` использует `Validator::make($request->all(), ...)` — это допустимо здесь, т.к. `$rules` строятся динамически из `$form->schema`. Нельзя вынести в FormRequest (схема не известна заранее).

> [!TIP]
> Автосоздание пользователя при заполнении формы с полем `type=email` — это CRM-механика: каждый лид автоматически становится пользователем системы с ролью `Student`.

> [!NOTE]
> Для добавления нового типа поля нужно:
> 1. Добавить опцию в `Select::make('type')` в `FormResource.php`
> 2. Добавить рендер в `FormView.vue` (`v-else-if="field.type === 'newtype'"`)
> 3. Добавить правило валидации в `FormController::submit` если требуется

---

## 10. Файловая карта модуля

```
app/
├── Filament/Resources/
│   ├── FormResource.php                        ← Filament CRUD (конструктор + таблица)
│   └── FormResource/RelationManagers/
│       └── SubmissionsRelationManager.php      ← Таблица ответов внутри формы
├── Http/Controllers/Public/
│   └── FormController.php                      ← show() + submit()
├── Models/
│   ├── Form.php                                ← Eloquent модель
│   └── FormSubmission.php                      ← Eloquent модель ответов
└── Traits/
    └── HasUtmCollection.php                    ← Сбор UTM из кук

resources/js/Pages/Public/
└── FormView.vue                                ← SPA-страница формы (Inertia)

routes/web.php                                  ← GET /f/{slug}, POST /f/{form}

database/migrations/
└── 2025_11_26_125838_create_forms_table.php    ← forms + form_submissions
```
