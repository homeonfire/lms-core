<?php

namespace App\Jobs;

use App\Mail\PromotionalEmail;
use App\Models\Newsletter;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendNewsletterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // Даем джобу час на выполнение (если база огромная)

    public function __construct(
        public Newsletter $newsletter
    ) {}

    public function handle(): void
    {
        // 1. Меняем статус на "В процессе"
        $this->newsletter->update(['status' => 'processing']);

        // 2. Собираем пользователей по фильтрам
        $filters = $this->newsletter->recipients_filter ?? [];
        
        $query = User::query()
            ->whereNotNull('email'); 

        // ВАЖНО: Шлем только тем, кто согласился на маркетинг, если не включен обход
        if (empty($filters['ignore_marketing']) || !$filters['ignore_marketing']) {
            $query->whereNotNull('accepted_marketing_at');
        } 

        // === ВКЛЮЧЕНИЯ (INCLUDES) ===

        // 1. Курсы и тарифы
        if (!empty($filters['include_courses']) || !empty($filters['include_tariffs'])) {
            $query->where(function ($q) use ($filters) {
                if (!empty($filters['include_courses'])) {
                    $q->whereHas('orders', function ($oq) use ($filters) {
                        $oq->whereIn('course_id', $filters['include_courses'])
                           ->where('status', 'paid');
                    });
                }
                if (!empty($filters['include_tariffs'])) {
                    if (!empty($filters['include_courses'])) {
                        $q->orWhereHas('orders', function ($oq) use ($filters) {
                            $oq->whereIn('tariff_id', $filters['include_tariffs'])
                               ->where('status', 'paid');
                        });
                    } else {
                        $q->whereHas('orders', function ($oq) use ($filters) {
                            $oq->whereIn('tariff_id', $filters['include_tariffs'])
                               ->where('status', 'paid');
                        });
                    }
                }
            });
        }

        // 2. Роли
        if (!empty($filters['include_roles'])) {
            $query->role($filters['include_roles']);
        }

        // 3. Анкеты
        if (!empty($filters['include_forms'])) {
            $query->whereHas('formSubmissions', function ($q) use ($filters) {
                $q->whereIn('form_id', $filters['include_forms']);
            });
        }

        // === ИСКЛЮЧЕНИЯ (EXCLUDES) ===

        // 1. Исключить курсы
        if (!empty($filters['exclude_courses'])) {
            $query->whereDoesntHave('orders', function ($q) use ($filters) {
                $q->whereIn('course_id', $filters['exclude_courses'])
                  ->where('status', 'paid');
            });
        }

        // 2. Исключить тарифы
        if (!empty($filters['exclude_tariffs'])) {
            $query->whereDoesntHave('orders', function ($q) use ($filters) {
                $q->whereIn('tariff_id', $filters['exclude_tariffs'])
                  ->where('status', 'paid');
            });
        }

        // 3. Исключить роли
        if (!empty($filters['exclude_roles'])) {
            $query->whereDoesntHave('roles', function ($q) use ($filters) {
                $q->whereIn('name', $filters['exclude_roles']);
            });
        }

        // 4. Исключить анкеты
        if (!empty($filters['exclude_forms'])) {
            $query->whereDoesntHave('formSubmissions', function ($q) use ($filters) {
                $q->whereIn('form_id', $filters['exclude_forms']);
            });
        }

        // 3. Отправляем письма (chunks для экономии памяти)
        // Мы используем chunk, чтобы не грузить 10000 юзеров в память сразу
        $query->chunk(100, function ($users) {
            foreach ($users as $user) {
                // Отправляем каждое письмо в очередь
                Mail::to($user->email)->queue(new PromotionalEmail($this->newsletter));
            }
        });

        // 4. Финиш
        $this->newsletter->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }
}