<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Filament\Resources\CourseResource;
use App\Filament\Resources\OrderResource;
use App\Filament\Resources\HomeworkSubmissionResource;
use App\Filament\Resources\NewsletterResource;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\FormResource;
use App\Filament\Pages\FormAnalyticsList;
use App\Filament\Pages\TildaIntegration;
use App\Filament\Pages\ImportUsers;
use App\Filament\Pages\PaymentSettings;

class QuickLinksWidget extends Widget
{
    protected static string $view = 'filament.widgets.quick-links-widget';

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
    {
        return [
            'links' => $this->getLinks(),
        ];
    }

    public function getLinks(): array
    {
        $user = auth()->user();
        $links = [];

        // 1. Курсы
        if ($user->hasAnyRole(['Super Admin', 'Manager', 'Teacher', 'Curator'])) {
            $links[] = [
                'title' => 'Курсы',
                'url' => CourseResource::getUrl('index'),
                'icon' => 'heroicon-o-academic-cap',
                'description' => 'Управление программами обучения, модулями, уроками и тарифами',
            ];
        }

        // 2. Заказы
        if ($user->hasAnyRole(['Super Admin', 'Manager', 'Teacher'])) {
            $links[] = [
                'title' => 'Заказы',
                'url' => OrderResource::getUrl('index'),
                'icon' => 'heroicon-o-currency-dollar',
                'description' => 'Просмотр оплат, финансовая аналитика, выдача доступов',
            ];
        }

        // 3. Проверка ДЗ
        if ($user->hasAnyRole(['Super Admin', 'Manager', 'Teacher', 'Curator'])) {
            $links[] = [
                'title' => 'Проверка ДЗ',
                'url' => HomeworkSubmissionResource::getUrl('index'),
                'icon' => 'heroicon-o-clipboard-document-check',
                'description' => 'Проверка домашних заданий студентов и обратная связь',
            ];
        }

        // 4. Рассылки
        if ($user->hasAnyRole(['Super Admin', 'Manager'])) {
            $links[] = [
                'title' => 'Рассылки',
                'url' => NewsletterResource::getUrl('index'),
                'icon' => 'heroicon-o-envelope',
                'description' => 'Отправка email-сообщений студентам по гибким фильтрам',
            ];
        }

        // 5. Импорт студентов
        if ($user->hasAnyRole(['Super Admin', 'Manager'])) {
            $links[] = [
                'title' => 'Импорт студентов',
                'url' => ImportUsers::getUrl(),
                'icon' => 'heroicon-o-user-plus',
                'description' => 'Пакетный импорт студентов из Excel/CSV файлов',
            ];
        }

        // 6. Аналитика анкет
        if ($user->hasAnyRole(['Super Admin', 'Manager', 'Teacher'])) {
            $links[] = [
                'title' => 'Аналитика анкет',
                'url' => FormAnalyticsList::getUrl(),
                'icon' => 'heroicon-o-chart-bar',
                'description' => 'Статистика ответов на формы и анкеты обратной связи',
            ];
        }

        // 7. Настройки оплат
        if ($user->hasRole('Super Admin')) {
            $links[] = [
                'title' => 'Настройки оплат',
                'url' => PaymentSettings::getUrl(),
                'icon' => 'heroicon-o-credit-card',
                'description' => 'Подключение платежных шлюзов (ЮMoney, ЮKassa)',
            ];
        }

        // 8. Tilda
        if ($user->hasRole('Super Admin')) {
            $links[] = [
                'title' => 'Интеграция Tilda',
                'url' => TildaIntegration::getUrl(),
                'icon' => 'heroicon-o-cog-6-tooth',
                'description' => 'Настройка вебхуков для интеграции с формами Tilda',
            ];
        }

        return $links;
    }
}
