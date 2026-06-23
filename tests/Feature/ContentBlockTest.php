<?php

use App\Models\ContentBlock;
use App\Models\Lesson;
use App\Models\CourseModule;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('can save content block with null or missing content', function () {
    // Create teacher
    $teacher = User::create([
        'name' => 'Test Teacher',
        'email' => 'teacher-test-' . uniqid() . '@example.com',
        'password' => Hash::make('password'),
    ]);

    // Create course
    $course = Course::create([
        'teacher_id' => $teacher->id,
        'title' => 'Test Course',
        'slug' => 'test-course-' . uniqid(),
        'description' => 'Test Description',
        'price' => 0,
        'is_published' => true,
    ]);

    // Create module
    $module = CourseModule::create([
        'course_id' => $course->id,
        'title' => 'Test Module',
    ]);

    // Create lesson
    $lesson = Lesson::create([
        'module_id' => $module->id,
        'title' => 'Test Lesson',
        'slug' => 'test-lesson-' . uniqid(),
    ]);

    // Create content block with type separator and no content
    $block = new ContentBlock();
    $block->lesson_id = $lesson->id;
    $block->type = 'separator';
    $block->save();

    expect($block->fresh()->content)->toBeArray();
});
