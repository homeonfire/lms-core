<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue; // <--- ОБЯЗАТЕЛЬНО
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// Добавляем ShouldQueue - теперь это письмо пойдет в очередь!
class WelcomeStudent extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        //
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Добро пожаловать в LMS Core! 🚀')
            ->greeting('Привет, ' . $notifiable->name . '!')
            ->line('Спасибо за регистрацию на нашей платформе.')
            ->line('Теперь вы можете выбирать курсы и проходить обучение.')
            ->action('Перейти в каталог', url('/courses'))
            ->line('Успехов в учебе!');
    }
}