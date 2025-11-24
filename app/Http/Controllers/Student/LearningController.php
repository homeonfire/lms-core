<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LearningController extends Controller
{
    public function show(Course $course, $lessonSlug = null)
    {
        // 1. Проверка доступа
        $isEnrolled = $course->orders()
            ->where('user_id', Auth::id())
            ->where('status', 'paid')
            ->exists();

        if (!$isEnrolled && $course->teacher_id !== Auth::id()) {
            return redirect()->route('courses.show', $course->slug)
                ->with('error', 'Сначала нужно записаться на курс.');
        }

        // 2. Загружаем меню (Syllabus)
        $syllabus = $course->modules()
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->with(['lessons', 'children.lessons'])
            ->get();

        // 3. Определяем текущий урок
        if ($lessonSlug) {
            $currentLesson = Lesson::where('slug', $lessonSlug)
                ->whereHas('module', fn($q) => $q->where('course_id', $course->id))
                // Грузим и блоки, и само задание
                ->with(['blocks', 'homework']) 
                ->firstOrFail();
        } else {
            // Логика поиска первого урока
            $currentLesson = null;
            foreach ($syllabus as $module) {
                if ($module->lessons->isNotEmpty()) {
                    $currentLesson = $module->lessons->first();
                    break;
                }
                foreach ($module->children as $child) {
                    if ($child->lessons->isNotEmpty()) {
                        $currentLesson = $child->lessons->first();
                        break 2;
                    }
                }
            }

            if (!$currentLesson) {
                return redirect()->route('my.learning')
                    ->with('error', 'В этом курсе пока нет уроков.');
            }
            
            return redirect()->route('learning.lesson', [$course->slug, $currentLesson->slug]);
        }

        // === ВОТ ЭТОЙ ЧАСТИ НЕ ХВАТАЛО ===
        
        // 4. Ищем ответ студента (если к уроку есть ДЗ)
        $submission = null;
        
        // Проверяем, загрузилась ли связь homework (она может быть null, если ДЗ к уроку нет)
        if ($currentLesson->homework) {
            $submission = $currentLesson->homework->submissions()
                ->where('student_id', Auth::id())
                ->first();
        }
        
        // ==================================

        // 5. Отправляем всё на фронтенд
        return Inertia::render('Learning/Show', [
            'course' => $course,
            'syllabus' => $syllabus,
            'lesson' => $currentLesson,
            // Явно передаем ДЗ и Ответ отдельными переменными для удобства
            'homework' => $currentLesson->homework, 
            'submission' => $submission,
        ]);
    }

    public function markAsComplete(Lesson $lesson)
    {
        $user = Auth::user();

        // 1. Отмечаем пройденным
        $user->lessons()->syncWithoutDetaching([
            $lesson->id => ['completed_at' => now()]
        ]);

        // 2. Ищем следующий урок
        // Нам нужно дерево курса, чтобы понять порядок
        $course = $lesson->module->course;
        
        $syllabus = $course->modules()
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->with(['lessons' => fn($q) => $q->orderBy('sort_order'), 'children.lessons' => fn($q) => $q->orderBy('sort_order')])
            ->get();

        // Превращаем дерево в плоский список (Flat List)
        $allLessons = collect();

        foreach ($syllabus as $module) {
            // Уроки главного модуля
            foreach ($module->lessons as $l) {
                $allLessons->push($l);
            }
            // Уроки подмодулей
            foreach ($module->children as $child) {
                foreach ($child->lessons as $l) {
                    $allLessons->push($l);
                }
            }
        }

        // Ищем индекс текущего урока
        $currentIndex = $allLessons->search(function($item) use ($lesson) {
            return $item->id === $lesson->id;
        });

        // Берем следующий
        $nextLesson = $allLessons->get($currentIndex + 1);

        // 3. Редирект
        if ($nextLesson) {
            return redirect()->route('learning.lesson', [$course->slug, $nextLesson->slug]);
        }

        // Если следующего нет — значит курс пройден!
        return redirect()->route('my.learning')
            ->with('success', 'Поздравляем! Вы завершили курс.');
    }
}   