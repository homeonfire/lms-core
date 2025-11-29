<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class YoomoneyP2PController extends Controller
{
    public function handle(Request $request)
    {
        // 1. Получаем секрет
        $secret = SystemSetting::where('key', 'yoomoney_p2p_secret')->value('payload');
        
        if (!$secret) {
            Log::error('YooMoney P2P: Secret not configured');
            return response('Error: Secret not found', 500);
        }

        // 2. Валидация подписи (SHA-1)
        // Формула: notification_type & operation_id & amount & currency & datetime & sender & codepro & notification_secret & label
        $string = join('&', [
            $request->input('notification_type'),
            $request->input('operation_id'),
            $request->input('amount'),
            $request->input('currency'),
            $request->input('datetime'),
            $request->input('sender'),
            $request->input('codepro'),
            $secret,
            $request->input('label'), // В label мы передаем ID заказа
        ]);

        $hash = sha1($string);

        if ($hash !== $request->input('sha1_hash')) {
            Log::warning('YooMoney P2P: Hash mismatch', ['request' => $request->all()]);
            return response('Invalid Hash', 200); // Возвращаем 200, чтобы Юмани не долбил нас повторами, но ничего не делаем
        }

        // 3. Ищем и обновляем заказ
        $orderId = $request->input('label');
        
        if (!$orderId) {
            return response('OK'); // Нет ID заказа -> игнорируем
        }

        $order = Order::find($orderId);

        if (!$order) {
            Log::error("YooMoney P2P: Order $orderId not found");
            return response('OK');
        }

        if ($order->status !== 'paid') {
            // Если сумма совпадает (или больше, с учетом комиссии)
            // Юмани шлет amount (сколько списали) и withdraw_amount (сколько придет).
            // Проверку суммы можно добавить здесь для строгости.

            $order->update([
                'status' => 'paid',
                'paid_at' => now(),
                'payment_id' => $request->input('operation_id'), // ID транзакции в Юмани
                'payment_method' => 'yoomoney_p2p',
            ]);
            
            Log::info("Order #{$order->id} paid via YooMoney P2P");
        }

        return response('OK');
    }
}