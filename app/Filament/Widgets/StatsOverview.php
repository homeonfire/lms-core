<?php

namespace App\Filament\Widgets;

use App\Models\HomeworkSubmission;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 2;

    // Обновлять данные каждые 15 секунд (живой дашборд)
    protected static ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        $user = auth()->user();
        $isTeacher = $user->hasRole('Teacher');
        $isAdmin = $user->hasRole('Super Admin');

        // 1. ЗАПРОС ДЛЯ ЗАКАЗОВ (ДОХОД)
        $ordersQuery = Order::query()->where('status', 'paid');
        
        if ($isTeacher) {
            $ordersQuery->whereHas('course', fn($q) => $q->where('teacher_id', $user->id));
        }

        $income = $ordersQuery->sum('amount'); // Сумма в копейках
        $ordersCount = $ordersQuery->count();


        // 2. ЗАПРОС ДЛЯ ДЗ НА ПРОВЕРКЕ
        $homeworksQuery = HomeworkSubmission::query()->where('status', 'pending');

        if ($isTeacher) {
            // Цепочка: Сдача -> ДЗ -> Урок -> Модуль -> Курс -> Учитель
            $homeworksQuery->whereHas('homework.lesson.module.course', fn($q) => $q->where('teacher_id', $user->id));
        }

        $pendingHomeworks = $homeworksQuery->count();


        // 3. ДОПОЛНИТЕЛЬНЫЕ МЕТРИКИ (Студенты и Курсы)
        if ($isTeacher) {
            $studentsCount = \App\Models\User::whereHas('orders', fn($q) => $q->where('status', 'paid')->whereHas('course', fn($c) => $c->where('teacher_id', $user->id)))->count();
            $studentsLabel = 'Ваши студенты';
            $studentsDesc = 'С доступом к вашим курсам';
            
            $coursesCount = \App\Models\Course::where('teacher_id', $user->id)->count();
            $coursesLabel = 'Ваши курсы';
            $coursesDesc = 'Создано вами';
        } else {
            $studentsCount = \App\Models\User::role('Student')->count();
            $studentsLabel = 'Всего студентов';
            $studentsDesc = 'Зарегистрировано на платформе';

            $coursesCount = \App\Models\Course::count();
            $coursesLabel = 'Всего курсов';
            $coursesDesc = 'Активные и черновики';
        }


        return [
            Stat::make('Общий доход', number_format($income / 100, 0, '.', ' ') . ' ₽')
                ->description($isTeacher ? 'С ваших курсов' : 'По всей платформе')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Оплаченных заказов', $ordersCount)
                ->description('Успешные продажи')
                ->color('primary'),

            Stat::make($studentsLabel, $studentsCount)
                ->description($studentsDesc)
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make($coursesLabel, $coursesCount)
                ->description($coursesDesc)
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('info'),

            Stat::make('ДЗ на проверке', $pendingHomeworks)
                ->description($pendingHomeworks > 0 ? 'Требуют внимания' : 'Всё проверено')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color($pendingHomeworks > 0 ? 'danger' : 'success'),
        ];
    }
}