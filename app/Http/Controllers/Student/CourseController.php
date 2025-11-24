<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Inertia\Inertia;

class CourseController extends Controller
{
    public function index()
    {
        $courses = Course::where('is_published', true)
            ->with('teacher:id,name,avatar_url')
            ->latest()
            ->get();

        return Inertia::render('Courses/Index', [
            'courses' => $courses,
        ]);
    }

    public function show($slug)
    {
        // 1. Ищем курс по Slug
        $course = Course::where('slug', $slug)
            ->where('is_published', true)
            ->with('teacher')
            ->firstOrFail();

        // 2. Формируем программу (Syllabus)
        // Берем только КОРНЕВЫЕ модули (у которых нет родителя)
        // И жадно подгружаем их дочерние модули и уроки
        $syllabus = $course->modules()
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->with([
                'lessons' => fn($q) => $q->orderBy('sort_order'), // Уроки корневого модуля
                'children' => fn($q) => $q->orderBy('sort_order') // Подмодули
                    ->with(['lessons' => fn($q2) => $q2->orderBy('sort_order')]) // Уроки подмодулей
            ])
            ->get();

        return Inertia::render('Courses/Show', [
            'course' => $course,
            'syllabus' => $syllabus,
        ]);
    }

    // Добавляем новый метод:
    public function myCourses()
    {
        $user = auth()->user();

        // 1. Берем курсы
        $myCourses = Course::whereHas('orders', function ($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->where('status', 'paid');
        })
        ->with('teacher:id,name,avatar_url')
        ->get();

        // 2. Считаем прогресс для каждого курса
        // (map изменяет коллекцию, добавляя новые поля)
        $myCourses->transform(function ($course) use ($user) {
            
            // Всего уроков в курсе (ищем через модули)
            $totalLessons = \App\Models\Lesson::whereHas('module', function ($q) use ($course) {
                $q->where('course_id', $course->id);
            })->count();

            // Пройдено уроков (ищем в таблице связи, где completed_at не null)
            // И обязательно проверяем, что урок относится именно к этому курсу
            $completedLessons = $user->lessons()
                ->whereNotNull('completed_at')
                ->whereHas('module', function ($q) use ($course) {
                    $q->where('course_id', $course->id);
                })
                ->count();

            // Вычисляем процент (защита от деления на ноль)
            $course->progress = $totalLessons > 0 
                ? round(($completedLessons / $totalLessons) * 100) 
                : 0;

            return $course;
        });

        return Inertia::render('MyLearning', [
            'courses' => $myCourses
        ]);
    }
}