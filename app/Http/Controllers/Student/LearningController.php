<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\TestResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LearningController extends Controller
{
    public function show(Course $course, $lessonSlug = null)
    {
        $user = Auth::user();

        // 1. Проверка заказа
        $order = $course->orders()
            ->where('user_id', $user->id)
            ->where('status', 'paid')
            ->first();

        // ПРИВИЛЕГИИ (Админ или Автор)
        $hasPrivilegedAccess = ($course->teacher_id === $user->id) || $user->hasRole('Super Admin');

        if (!$order && !$hasPrivilegedAccess) {
            return redirect()->route('courses.show', $course->slug)
                ->with('error', 'Сначала нужно записаться на курс.');
        }

        // 2. Загружаем полную структуру
        $rawSyllabus = $course->modules()
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->with([
                'tariffs', 
                'lessons.tariffs', 
                'lessons.module.tariffs',
                'children.tariffs', 
                'children.lessons.tariffs', 
                'children.lessons.module.tariffs'
            ])
            ->get();

        // 3. ФИЛЬТРАЦИЯ: Убираем всё недоступное
        $tariffId = $order?->tariff_id;

        // Функция проверки прав (Helper)
        $checkAccess = function($entity) use ($tariffId, $hasPrivilegedAccess) {
            if ($hasPrivilegedAccess) return true; // Админ видит всё
            if ($entity->tariffs->isEmpty()) return true; // Нет ограничений
            if (!$tariffId) return false; // Есть ограничения, но нет тарифа
            return $entity->tariffs->contains('id', $tariffId);
        };

        // Фильтруем дерево
        $syllabus = $rawSyllabus->filter(function ($module) use ($checkAccess) {
            // Если сам модуль закрыт - убираем его целиком
            if (!$checkAccess($module)) return false;

            // Фильтруем уроки внутри модуля
            $module->setRelation('lessons', $module->lessons->filter(function ($lesson) use ($checkAccess) {
                return $checkAccess($lesson);
            })->values());

            // Фильтруем подмодули
            $module->setRelation('children', $module->children->filter(function ($child) use ($checkAccess) {
                if (!$checkAccess($child)) return false;

                // Фильтруем уроки внутри подмодуля
                $child->setRelation('lessons', $child->lessons->filter(function ($lesson) use ($checkAccess) {
                    return $checkAccess($lesson);
                })->values());

                // Оставляем подмодуль, даже если в нем нет уроков (но сам он доступен)
                return true;
            })->values());

            return true;
        })->values(); // Сбрасываем ключи массива для JSON

        // 4. Собираем плоский список (теперь только из ДОСТУПНЫХ уроков)
        $allLessons = collect();
        foreach ($syllabus as $module) {
            foreach ($module->lessons as $l) $allLessons->push($l);
            foreach ($module->children as $child) {
                foreach ($child->lessons as $l) $allLessons->push($l);
            }
        }

        // 5. Определяем текущий урок
        if ($lessonSlug) {
            // Пытаемся найти урок в БД
            // Важно: мы не можем просто взять firstOrFail, так как урок может существовать, но быть недоступным.
            // Нам нужно проверить, есть ли он в нашем отфильтрованном списке $allLessons.
            
            $currentLesson = $allLessons->first(fn($l) => $l->slug === $lessonSlug);

            if (!$currentLesson) {
                // Если урок существует в БД, но был отфильтрован - значит нет доступа
                // Либо просто неверный URL
                return redirect()->route('my.learning')->with('error', 'Урок недоступен или не существует.');
            }

            // Важно: $currentLesson из коллекции не имеет подгруженных связей блоков и домашки.
            // Нам нужно догрузить их.
            $currentLesson->load(['blocks.testResults' => fn($q) => $q->where('user_id', $user->id)]);
            $currentLesson->load(['homework']);

        } else {
            // Ищем ПЕРВЫЙ ДОСТУПНЫЙ
            $currentLesson = $allLessons->first();

            if (!$currentLesson) {
                return redirect()->route('my.learning')->with('error', 'В этом курсе пока нет уроков, доступных для вашего тарифа.');
            }
            
            return redirect()->route('learning.lesson', [$course->slug, $currentLesson->slug]);
        }

        // 6. Навигация
        $currentIndex = $allLessons->search(fn($i) => $i->id === $currentLesson->id);
        $prevLesson = ($currentIndex > 0) ? $allLessons->get($currentIndex - 1) : null;

        // 7. Логика "Можно ли завершить?"
        $canComplete = true;

        if (!$hasPrivilegedAccess) {
            // Тесты
            foreach ($currentLesson->blocks as $block) {
                if ($block->type === 'quiz') {
                    $passed = $block->testResults->where('is_passed', true)->isNotEmpty();
                    if (!$passed) {
                        $canComplete = false;
                        break;
                    }
                }
            }
            // ДЗ
            $submission = null;
            if ($currentLesson->homework) {
                $submission = $currentLesson->homework->submissions()
                    ->where('student_id', Auth::id())
                    ->first();

                if ($currentLesson->is_stop_lesson && $currentLesson->homework->is_required) {
                    if (!$submission || $submission->status !== 'approved') {
                        $canComplete = false;
                    }
                }
            }
        } else {
             // Для админа подгрузим submission для отображения
             $submission = null;
             if ($currentLesson->homework) {
                $submission = $currentLesson->homework->submissions()->where('student_id', Auth::id())->first();
             }
        }

        return Inertia::render('Learning/Show', [
            'course' => $course,
            'syllabus' => $syllabus, // Отфильтрованное дерево
            'lesson' => $currentLesson,
            'homework' => $currentLesson->homework, 
            'submission' => $submission,
            'prevLessonUrl' => $prevLesson ? route('learning.lesson', [$course->slug, $prevLesson->slug]) : null,
            'canComplete' => $canComplete,
        ]);
    }

    // AJAX проверка теста
    public function checkTest(Request $request, \App\Models\ContentBlock $block)
    {
        if ($block->type !== 'quiz') abort(404);
        $userAnswers = $request->input('answers', []);
        $correctCount = 0;
        $totalQuestions = count($block->content['questions'] ?? []);

        if ($totalQuestions === 0) return back();

        foreach ($block->content['questions'] as $qIndex => $question) {
            if (isset($userAnswers[$qIndex])) {
                $ansIndex = $userAnswers[$qIndex];
                if (isset($question['answers'][$ansIndex]) && ($question['answers'][$ansIndex]['is_correct'] ?? false)) {
                    $correctCount++;
                }
            }
        }

        $score = round(($correctCount / $totalQuestions) * 100);
        $minScore = $block->content['min_score'] ?? 70;
        $isPassed = $score >= $minScore;

        TestResult::updateOrCreate(
            ['user_id' => auth()->id(), 'content_block_id' => $block->id],
            ['score_percent' => $score, 'is_passed' => $isPassed, 'user_answers' => $userAnswers]
        );

        return back()->with($isPassed ? 'success' : 'error', $isPassed ? "Тест сдан! ($score%)" : "Не сдан ($score% из $minScore%)");
    }

    // Завершение урока
    public function markAsComplete(Lesson $lesson)
    {
        $user = Auth::user();
        $user->lessons()->syncWithoutDetaching([$lesson->id => ['completed_at' => now()]]);

        // Повторяем логику фильтрации, чтобы найти следующий ДОСТУПНЫЙ урок
        $course = $lesson->module->course;
        $order = $course->orders()->where('user_id', $user->id)->where('status', 'paid')->first();
        $hasPrivilegedAccess = ($course->teacher_id === $user->id) || $user->hasRole('Super Admin');
        $tariffId = $order?->tariff_id;

        $rawSyllabus = $course->modules()->whereNull('parent_id')->orderBy('sort_order')
            ->with(['tariffs', 'lessons.tariffs', 'lessons.module.tariffs', 'children.tariffs', 'children.lessons.tariffs'])
            ->get();

        $checkAccess = function($entity) use ($tariffId, $hasPrivilegedAccess) {
            if ($hasPrivilegedAccess) return true;
            if ($entity->tariffs->isEmpty()) return true;
            if (!$tariffId) return false;
            return $entity->tariffs->contains('id', $tariffId);
        };

        $allLessons = collect();
        
        foreach ($rawSyllabus as $module) {
            if (!$checkAccess($module)) continue;

            foreach ($module->lessons as $l) {
                if ($checkAccess($l)) $allLessons->push($l);
            }
            foreach ($module->children as $child) {
                if (!$checkAccess($child)) continue;
                foreach ($child->lessons as $l) {
                    if ($checkAccess($l)) $allLessons->push($l);
                }
            }
        }

        $currentIndex = $allLessons->search(fn($i) => $i->id === $lesson->id);
        $nextLesson = $allLessons->get($currentIndex + 1);

        if ($nextLesson) {
            return redirect()->route('learning.lesson', [$course->slug, $nextLesson->slug]);
        }

        return redirect()->route('my.learning')->with('success', 'Курс завершен!');
    }
}