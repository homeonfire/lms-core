#!/bin/bash

# Останавливаем скрипт при любой ошибке
set -e

echo "🚀 Начало деплоя..."

# 1. Обновляем код из Git
echo "📥 Скачиваем обновления..."
git pull origin main

# 2. Собираем контейнеры (используем продакшн конфиг)
echo "🐳 Собираем Docker образы..."
docker compose -f docker-compose.prod.yml build

# 3. Запускаем в фоновом режиме
echo "🔥 Запускаем контейнеры..."
docker compose -f docker-compose.prod.yml up -d

# 4. Устанавливаем зависимости PHP
echo "📦 Устанавливаем PHP пакеты..."
docker compose -f docker-compose.prod.yml exec -T laravel.test composer install --no-dev --optimize-autoloader

# 5. Миграции БД
echo "🗄️ Запускаем миграции..."
docker compose -f docker-compose.prod.yml exec -T laravel.test php artisan migrate --force

# 6. Сборка фронтенда (Vite)
echo "🎨 Собираем фронтенд..."
docker compose -f docker-compose.prod.yml exec -T laravel.test npm install
docker compose -f docker-compose.prod.yml exec -T laravel.test npm run build

# 7. Кэширование и оптимизация
echo "🧹 Чистим и кэшируем..."
docker compose -f docker-compose.prod.yml exec -T laravel.test php artisan optimize:clear
docker compose -f docker-compose.prod.yml exec -T laravel.test php artisan config:cache
docker compose -f docker-compose.prod.yml exec -T laravel.test php artisan route:cache
docker compose -f docker-compose.prod.yml exec -T laravel.test php artisan view:cache
docker compose -f docker-compose.prod.yml exec -T laravel.test php artisan storage:link

# 8. Запуск очередей (перезапуск воркера)
echo "📨 Перезапускаем очереди..."
docker compose -f docker-compose.prod.yml exec -T laravel.test php artisan queue:restart

echo "✅ Деплой успешно завершен! Сайт должен быть доступен по https."