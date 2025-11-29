<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use YooKassa\Client;

class PaymentController extends Controller
{
    // 1. Страница выбора метода оплаты
    public function checkout(Order $order)
    {
        // Безопасность: Платить может только владелец заказа
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        // Если уже оплачено — отправляем учиться
        if ($order->status === 'paid') {
            return redirect()->route('my.learning');
        }

        return Inertia::render('Payment/Checkout', [
            'order' => $order->load('course', 'tariff'),
            'methods' => [
                // Проверяем в настройках, включена ли ЮКасса
                'yookassa' => (bool) SystemSetting::where('key', 'yookassa_enabled')->value('payload'),
            ]
        ]);
    }

    // 2. Инициализация платежа (редирект на ЮKassa)
    public function pay(Request $request, Order $order)
    {
        if ($order->user_id !== auth()->id()) abort(403);

        $method = $request->input('method');

        if ($method === 'yookassa') {
            return $this->payWithYookassa($order);
        }

        return back()->with('error', 'Выберите метод оплаты.');
    }

    private function payWithYookassa(Order $order)
    {
        $shopId = SystemSetting::where('key', 'yookassa_shop_id')->value('payload');
        $secretKey = SystemSetting::where('key', 'yookassa_secret_key')->value('payload');

        if (!$shopId || !$secretKey) {
            return back()->with('error', 'Ошибка конфигурации оплаты. Обратитесь к администратору.');
        }

        $client = new Client();
        $client->setAuth($shopId, $secretKey);

        try {
            // Создаем платеж в ЮКассе
            $payment = $client->createPayment(
                [
                    'amount' => [
                        'value' => $order->amount, // У нас сумма в рублях
                        'currency' => 'RUB',
                    ],
                    'confirmation' => [
                        'type' => 'redirect',
                        'return_url' => route('courses.thankyou', $order->course->slug), // Куда вернуть после оплаты
                    ],
                    'capture' => true,
                    'description' => 'Заказ #' . $order->id . ': ' . $order->course->title,
                    'metadata' => [
                        'order_id' => $order->id, // Важно для вебхука
                    ],
                ],
                uniqid('', true) // Ключ идемпотентности
            );

            // Сохраняем ID платежа в заказ
            $order->update([
                'payment_id' => $payment->getId(),
                'payment_method' => 'yookassa'
            ]);

            // Редиректим пользователя на страницу банка
            // Inertia::location делает жесткий редирект (уход с нашего сайта)
            return Inertia::location($payment->getConfirmation()->getConfirmationUrl());

        } catch (\Exception $e) {
            return back()->with('error', 'Ошибка платежной системы: ' . $e->getMessage());
        }
    }
}