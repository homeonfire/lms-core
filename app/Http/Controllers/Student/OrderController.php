<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Order;
use App\Models\Tariff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function enroll(Request $request, Course $course)
    {
        $user = Auth::user();
        
        // 1. Определяем цену и тариф
        $amount = $course->price;
        $tariffId = null;

        if ($request->filled('tariff_id')) {
            $tariff = Tariff::where('course_id', $course->id)
                ->where('id', $request->input('tariff_id'))
                ->firstOrFail();
            
            $amount = $tariff->price;
            $tariffId = $tariff->id;
        } 
        elseif ($course->tariffs()->exists()) {
            return redirect()->back()->with('error', 'Пожалуйста, выберите тариф.');
        }

        // 2. Проверяем дубликаты
        $existingOrder = Order::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->whereIn('status', ['new', 'paid','processing'])
            ->first();

        if ($existingOrder) {
            if ($existingOrder->status === 'paid') {
                // Если уже куплено - ведем к обучению
                return redirect()->route('my.learning');
            }
            
            // ИСПРАВЛЕНИЕ: Если заказ есть, но не оплачен — ведем на страницу "Уже заказано"
            return redirect()->route('courses.order_exists', $course->slug);
        }

        // 3. Создаем заказ
        $isFree = $amount === 0;

        Order::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'tariff_id' => $tariffId,
            'amount' => $amount,
            'status' => $isFree ? 'paid' : 'new',
            'paid_at' => $isFree ? now() : null,
            'history_log' => ['action' => 'created_by_student', 'ip' => request()->ip()],
            'utm_data' => $this->getUtmFromCookies(),
        ]);

        if ($isFree) {
            return redirect()->route('my.learning')->with('success', 'Вы успешно записались на курс!');
        }

        return redirect()->route('courses.thankyou', $course->slug);
    }
}