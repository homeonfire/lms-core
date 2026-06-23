<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewUserRegisteredWithPassword extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $rawPassword
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name');

        return (new MailMessage)
            ->subject('Ваши доступы к платформе ' . $appName . ' 🚀')
            ->greeting('Привет, ' . $notifiable->name . '!')
            ->line('Для вас создан аккаунт на обучающей платформе.')
            ->line('Вот ваши данные для входа в личный кабинет:')
            ->line('**Логин (Email):** ' . $notifiable->email)
            ->line('**Пароль:** ' . $this->rawPassword)
            ->action('Войти в кабинет', url('/login'))
            ->line('Пожалуйста, смените пароль в настройках профиля после первого входа.')
            ->line('Успехов в учебе!');
    }
}
