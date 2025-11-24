<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function enroll(Course $course)
    {
        $user = Auth::user();

        // 1. Проверяем, нет ли уже активного заказа или доступа
        $existingOrder = Order::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->whereIn('status', ['new', 'paid'])
            ->first();

        if ($existingOrder) {
            if ($existingOrder->status === 'paid') {
                // Если уже куплено - ведем сразу в плеер уроков (пока на страницу "Мое обучение")
                return redirect()->route('my.learning');
            }
            // Если заказ висит, но не оплачен
            return redirect()->back()->with('message', 'Заказ уже создан. Ожидайте связи с менеджером.');
        }

        // 2. Создаем заказ
        $isFree = $course->price === 0;

        Order::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'amount' => $course->price,
            'status' => $isFree ? 'paid' : 'new', // Бесплатный сразу оплачен
            'paid_at' => $isFree ? now() : null,
            'history_log' => ['action' => 'created_by_student', 'ip' => request()->ip()]
        ]);

        // 3. Редирект
        if ($isFree) {
            return redirect()->route('my.learning')->with('success', 'Вы успешно записались на курс!');
        }

        return redirect()->back()->with('success', 'Заявка отправлена! Менеджер свяжется с вами для оплаты.');
    }
}