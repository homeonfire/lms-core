<?php

use App\Models\Funnel;
use App\Models\FunnelStage;
use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RolesSeeder']);
});

it('automatically generates the 5 default stages on funnel creation', function () {
    $funnel = Funnel::create(['name' => 'Test Funnel', 'is_active' => true]);
    
    expect($funnel->stages)->toHaveCount(5);
    expect($funnel->stages[0]->name)->toBe('Новая заявка');
    expect($funnel->stages[3]->type)->toBe('won');
    expect($funnel->stages[4]->type)->toBe('lost');
});

it('automatically assigns a new order to the first stage of the active funnel', function () {
    $funnel = Funnel::create(['name' => 'Active Funnel', 'is_active' => true]);
    $firstStage = $funnel->stages()->first();

    $teacher = User::create([
        'name' => 'Teacher',
        'email' => 'teacher-' . uniqid() . '@example.com',
        'password' => Hash::make('password'),
    ]);

    $course = Course::create([
        'teacher_id' => $teacher->id,
        'title' => 'Test Course',
        'slug' => 'test-course-' . uniqid(),
        'price' => 1000,
        'is_published' => true,
    ]);

    $user = User::create([
        'name' => 'Student',
        'email' => 'student-' . uniqid() . '@example.com',
        'password' => Hash::make('password'),
    ]);

    $order = Order::create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'amount' => 1000,
        'status' => 'new',
    ]);

    expect($order->funnel_stage_id)->toBe($firstStage->id);
});

it('updates order status to paid when moved to a won stage', function () {
    $funnel = Funnel::create(['name' => 'Test Funnel', 'is_active' => true]);
    $wonStage = $funnel->stages()->where('type', 'won')->first();

    $teacher = User::create([
        'name' => 'Teacher',
        'email' => 'teacher-' . uniqid() . '@example.com',
        'password' => Hash::make('password'),
    ]);

    $course = Course::create([
        'teacher_id' => $teacher->id,
        'title' => 'Test Course',
        'slug' => 'test-course-' . uniqid(),
        'price' => 1000,
        'is_published' => true,
    ]);

    $user = User::create([
        'name' => 'Student',
        'email' => 'student-' . uniqid() . '@example.com',
        'password' => Hash::make('password'),
    ]);

    $order = Order::create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'amount' => 1000,
        'status' => 'new',
    ]);

    $order->update(['funnel_stage_id' => $wonStage->id]);

    expect($order->fresh()->status)->toBe('paid');
    expect($order->fresh()->paid_at)->not->toBeNull();
});

it('updates order status to cancelled when moved to a lost stage', function () {
    $funnel = Funnel::create(['name' => 'Test Funnel', 'is_active' => true]);
    $lostStage = $funnel->stages()->where('type', 'lost')->first();

    $teacher = User::create([
        'name' => 'Teacher',
        'email' => 'teacher-' . uniqid() . '@example.com',
        'password' => Hash::make('password'),
    ]);

    $course = Course::create([
        'teacher_id' => $teacher->id,
        'title' => 'Test Course',
        'slug' => 'test-course-' . uniqid(),
        'price' => 1000,
        'is_published' => true,
    ]);

    $user = User::create([
        'name' => 'Student',
        'email' => 'student-' . uniqid() . '@example.com',
        'password' => Hash::make('password'),
    ]);

    $order = Order::create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'amount' => 1000,
        'status' => 'new',
    ]);

    $order->update(['funnel_stage_id' => $lostStage->id]);

    expect($order->fresh()->status)->toBe('cancelled');
});

it('automatically moves the order to won stage when status is set to paid', function () {
    $funnel = Funnel::create(['name' => 'Test Funnel', 'is_active' => true]);
    $wonStage = $funnel->stages()->where('type', 'won')->first();

    $teacher = User::create([
        'name' => 'Teacher',
        'email' => 'teacher-' . uniqid() . '@example.com',
        'password' => Hash::make('password'),
    ]);

    $course = Course::create([
        'teacher_id' => $teacher->id,
        'title' => 'Test Course',
        'slug' => 'test-course-' . uniqid(),
        'price' => 1000,
        'is_published' => true,
    ]);

    $user = User::create([
        'name' => 'Student',
        'email' => 'student-' . uniqid() . '@example.com',
        'password' => Hash::make('password'),
    ]);

    $order = Order::create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'amount' => 1000,
        'status' => 'new',
    ]);

    $order->update(['status' => 'paid']);

    expect($order->fresh()->funnel_stage_id)->toBe($wonStage->id);
});
