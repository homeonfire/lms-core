<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Student\CourseController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Главная страница (пока просто редирект на вход)
Route::get('/', function () {
    return redirect()->route('login');
});

// Группа маршрутов для авторизованных студентов
Route::middleware(['auth', 'verified'])->group(function () {

    // Плеер уроков
// URL будет вида: /learning/php-course/lesson-1
Route::get('/learning/{course:slug}/{lessonSlug?}', [\App\Http\Controllers\Student\LearningController::class, 'show'])
    ->name('learning.lesson');

    // Каталог курсов
    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/{course:slug}', [CourseController::class, 'show'])->name('courses.show');
    Route::post('/homework/{homework}/submit', [\App\Http\Controllers\Student\HomeworkController::class, 'submit'])
    ->name('homework.submit');

    // Профиль (стандартный Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Личный кабинет (вместо dashboard редиректа можно сразу сюда)
Route::get('/my-learning', [CourseController::class, 'myCourses'])->name('my.learning');

Route::post('/learning/{lesson}/complete', [\App\Http\Controllers\Student\LearningController::class, 'markAsComplete'])
    ->name('learning.complete');

// Логика записи
Route::post('/courses/{course:slug}/enroll', [\App\Http\Controllers\Student\OrderController::class, 'enroll'])->name('courses.enroll');

// Dashboard теперь пусть ведет на My Learning
Route::get('/dashboard', function () {
    return redirect()->route('my.learning');
})->name('dashboard');
});

require __DIR__.'/auth.php';