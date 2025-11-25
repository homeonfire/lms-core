#!/bin/bash
set -e

echo "🚀 Starting Deployment..."

# 1. Скачиваем свежий код
git pull origin main

# 2. Собираем и запускаем контейнеры
echo "🐳 Building Containers..."
docker compose -f docker-compose.prod.yml up -d --build

# 3. Устанавливаем PHP зависимости
echo "📦 Installing PHP Dependencies..."
docker compose -f docker-compose.prod.yml exec -T laravel.test composer install --no-dev --optimize-autoloader

# 4. Накатываем миграции БД
echo "🗄️ Migrating Database..."
docker compose -f docker-compose.prod.yml exec -T laravel.test php artisan migrate --force

# 5. ГАРАНТИЯ РОЛЕЙ (Создаем роли, если база чистая)
echo "👮‍♂️ Checking Roles..."
docker compose -f docker-compose.prod.yml exec -T laravel.test php artisan tinker --execute="
\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Super Admin']);
\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Teacher']);
\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Student']);
\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Manager']);
\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Curator']);
"

# 6. Сборка фронтенда (с фиксом версий)
echo "🎨 Building Frontend..."
docker compose -f docker-compose.prod.yml exec -T laravel.test npm install --legacy-peer-deps
docker compose -f docker-compose.prod.yml exec -T laravel.test npm run build

# 7. Права доступа и Кэш
echo "🧹 Fixing Permissions & Cache..."
docker compose -f docker-compose.prod.yml exec -T -u root laravel.test chmod -R 777 storage bootstrap/cache
docker compose -f docker-compose.prod.yml exec -T laravel.test php artisan optimize:clear
docker compose -f docker-compose.prod.yml exec -T laravel.test php artisan config:cache
docker compose -f docker-compose.prod.yml exec -T laravel.test php artisan route:cache
docker compose -f docker-compose.prod.yml exec -T laravel.test php artisan view:cache
docker compose -f docker-compose.prod.yml exec -T laravel.test php artisan storage:link

# 8. Перезапуск очередей (чтобы подхватить новый код)
echo "🔄 Restarting Queues..."
docker compose -f docker-compose.prod.yml exec -T laravel.test php artisan queue:restart
docker compose -f docker-compose.prod.yml exec -T queue php artisan queue:restart

echo "✅ DEPLOY SUCCESSFUL! Site is ready."