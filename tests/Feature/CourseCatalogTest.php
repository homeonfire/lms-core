<?php

use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

it('shows is_purchased as false for users without paid order', function () {
    $teacher = User::create([
        'name' => 'Teacher',
        'email' => 'teacher-cat-' . uniqid() . '@example.com',
        'password' => Hash::make('password'),
    ]);

    $course = Course::create([
        'teacher_id' => $teacher->id,
        'title' => 'Catalog Course 1',
        'slug' => 'cat-course-1-' . uniqid(),
        'description' => 'Test',
        'price' => 1000,
        'is_published' => true,
    ]);

    $student = User::create([
        'name' => 'Student',
        'email' => 'student-cat-' . uniqid() . '@example.com',
        'password' => Hash::make('password'),
    ]);

    $response = $this->actingAs($student)->get(route('courses.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Courses/Index')
        ->has('courses', fn (Assert $courses) => $courses
            ->where('0.id', $course->id)
            ->where('0.is_purchased', false)
            ->etc()
        )
    );
});

it('shows is_purchased as true for users with paid order', function () {
    $teacher = User::create([
        'name' => 'Teacher',
        'email' => 'teacher-cat-' . uniqid() . '@example.com',
        'password' => Hash::make('password'),
    ]);

    $course = Course::create([
        'teacher_id' => $teacher->id,
        'title' => 'Catalog Course 2',
        'slug' => 'cat-course-2-' . uniqid(),
        'description' => 'Test',
        'price' => 2000,
        'is_published' => true,
    ]);

    $student = User::create([
        'name' => 'Student',
        'email' => 'student-cat-' . uniqid() . '@example.com',
        'password' => Hash::make('password'),
    ]);

    // Create a paid order
    Order::create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'amount' => 2000,
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    $response = $this->actingAs($student)->get(route('courses.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Courses/Index')
        ->has('courses', fn (Assert $courses) => $courses
            ->where('0.id', $course->id)
            ->where('0.is_purchased', true)
            ->etc()
        )
    );
});
