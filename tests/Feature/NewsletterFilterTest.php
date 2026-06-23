<?php

use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\Newsletter;
use App\Jobs\SendNewsletterJob;
use App\Mail\PromotionalEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RolesSeeder']);
});

it('filters newsletter recipients based on include_courses', function () {
    Mail::fake();

    // Create teacher
    $teacher = User::create([
        'name' => 'Teacher',
        'email' => 'teacher-' . uniqid() . '@example.com',
        'password' => Hash::make('password'),
    ]);

    // Create courses
    $course1 = Course::create([
        'teacher_id' => $teacher->id,
        'title' => 'Course 1',
        'slug' => 'course-1-' . uniqid(),
        'description' => 'Test',
        'price' => 1000,
        'is_published' => true,
    ]);

    $course2 = Course::create([
        'teacher_id' => $teacher->id,
        'title' => 'Course 2',
        'slug' => 'course-2-' . uniqid(),
        'description' => 'Test',
        'price' => 2000,
        'is_published' => true,
    ]);

    // Create users who opted into marketing
    $userA = User::create([
        'name' => 'User A',
        'email' => 'usera-' . uniqid() . '@example.com',
        'password' => Hash::make('password'),
        'accepted_marketing_at' => now(),
    ]);

    $userB = User::create([
        'name' => 'User B',
        'email' => 'userb-' . uniqid() . '@example.com',
        'password' => Hash::make('password'),
        'accepted_marketing_at' => now(),
    ]);

    // Give User A access to Course 1
    Order::create([
        'user_id' => $userA->id,
        'course_id' => $course1->id,
        'amount' => 1000,
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    // Give User B access to Course 2
    Order::create([
        'user_id' => $userB->id,
        'course_id' => $course2->id,
        'amount' => 2000,
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    // Create newsletter targeting only Course 1
    $newsletter = Newsletter::create([
        'subject' => 'Test Newsletter',
        'content' => '<p>Hello!</p>',
        'recipients_filter' => [
            'include_courses' => [$course1->id]
        ],
        'status' => 'draft',
    ]);

    // Run job
    SendNewsletterJob::dispatch($newsletter);

    // Assert Mail was queued only to User A
    Mail::assertQueued(PromotionalEmail::class, function ($mail) use ($userA) {
        return $mail->hasTo($userA->email);
    });

    Mail::assertNotQueued(PromotionalEmail::class, function ($mail) use ($userB) {
        return $mail->hasTo($userB->email);
    });
});

it('filters newsletter recipients based on exclude_courses', function () {
    Mail::fake();

    $teacher = User::create([
        'name' => 'Teacher',
        'email' => 'teacher-' . uniqid() . '@example.com',
        'password' => Hash::make('password'),
    ]);

    $course1 = Course::create([
        'teacher_id' => $teacher->id,
        'title' => 'Course 1',
        'slug' => 'course-1-' . uniqid(),
        'description' => 'Test',
        'price' => 1000,
        'is_published' => true,
    ]);

    $userA = User::create([
        'name' => 'User A',
        'email' => 'usera-' . uniqid() . '@example.com',
        'password' => Hash::make('password'),
        'accepted_marketing_at' => now(),
    ]);

    $userB = User::create([
        'name' => 'User B',
        'email' => 'userb-' . uniqid() . '@example.com',
        'password' => Hash::make('password'),
        'accepted_marketing_at' => now(),
    ]);

    // Give User A access to Course 1
    Order::create([
        'user_id' => $userA->id,
        'course_id' => $course1->id,
        'amount' => 1000,
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    // Create newsletter excluding Course 1
    $newsletter = Newsletter::create([
        'subject' => 'Test Newsletter 2',
        'content' => '<p>Hello!</p>',
        'recipients_filter' => [
            'exclude_courses' => [$course1->id]
        ],
        'status' => 'draft',
    ]);

    SendNewsletterJob::dispatch($newsletter);

    // User A should NOT get it, User B SHOULD get it
    Mail::assertNotQueued(PromotionalEmail::class, function ($mail) use ($userA) {
        return $mail->hasTo($userA->email);
    });

    Mail::assertQueued(PromotionalEmail::class, function ($mail) use ($userB) {
        return $mail->hasTo($userB->email);
    });
});

it('filters newsletter recipients based on include_forms and exclude_forms', function () {
    Mail::fake();

    $form1 = Form::create([
        'title' => 'Form 1',
        'slug' => 'form-1-' . uniqid(),
        'is_active' => true,
        'schema' => [['type' => 'text', 'name' => 'name']],
    ]);

    $form2 = Form::create([
        'title' => 'Form 2',
        'slug' => 'form-2-' . uniqid(),
        'is_active' => true,
        'schema' => [['type' => 'text', 'name' => 'name']],
    ]);

    $userA = User::create([
        'name' => 'User A',
        'email' => 'usera-' . uniqid() . '@example.com',
        'password' => Hash::make('password'),
        'accepted_marketing_at' => now(),
    ]);

    $userB = User::create([
        'name' => 'User B',
        'email' => 'userb-' . uniqid() . '@example.com',
        'password' => Hash::make('password'),
        'accepted_marketing_at' => now(),
    ]);

    // User A filled Form 1
    FormSubmission::create([
        'form_id' => $form1->id,
        'user_id' => $userA->id,
        'data' => ['name' => 'User A'],
    ]);

    // User B filled Form 2
    FormSubmission::create([
        'form_id' => $form2->id,
        'user_id' => $userB->id,
        'data' => ['name' => 'User B'],
    ]);

    // 1. Test including Form 1
    $newsletter1 = Newsletter::create([
        'subject' => 'Test Newsletter 3',
        'content' => '<p>Hello!</p>',
        'recipients_filter' => [
            'include_forms' => [$form1->id]
        ],
        'status' => 'draft',
    ]);

    SendNewsletterJob::dispatch($newsletter1);

    Mail::assertQueued(PromotionalEmail::class, function ($mail) use ($userA) {
        return $mail->hasTo($userA->email);
    });

    Mail::assertNotQueued(PromotionalEmail::class, function ($mail) use ($userB) {
        return $mail->hasTo($userB->email);
    });

    // Reset fake
    Mail::fake();

    // 2. Test excluding Form 1
    $newsletter2 = Newsletter::create([
        'subject' => 'Test Newsletter 4',
        'content' => '<p>Hello!</p>',
        'recipients_filter' => [
            'exclude_forms' => [$form1->id]
        ],
        'status' => 'draft',
    ]);

    SendNewsletterJob::dispatch($newsletter2);

    Mail::assertNotQueued(PromotionalEmail::class, function ($mail) use ($userA) {
        return $mail->hasTo($userA->email);
    });

    Mail::assertQueued(PromotionalEmail::class, function ($mail) use ($userB) {
        return $mail->hasTo($userB->email);
    });
});

it('ignores marketing consent if ignore_marketing is set to true', function () {
    Mail::fake();

    $userA = User::create([
        'name' => 'User A (consented)',
        'email' => 'usera-consented-' . uniqid() . '@example.com',
        'password' => Hash::make('password'),
        'accepted_marketing_at' => now(),
    ]);

    $userB = User::create([
        'name' => 'User B (not consented)',
        'email' => 'userb-notconsented-' . uniqid() . '@example.com',
        'password' => Hash::make('password'),
        'accepted_marketing_at' => null, // No marketing consent
    ]);

    // Create newsletter with ignore_marketing => true
    $newsletter = Newsletter::create([
        'subject' => 'System Notification',
        'content' => '<p>Important update!</p>',
        'recipients_filter' => [
            'ignore_marketing' => true
        ],
        'status' => 'draft',
    ]);

    SendNewsletterJob::dispatch($newsletter);

    // Both users should get the email
    Mail::assertQueued(PromotionalEmail::class, function ($mail) use ($userA) {
        return $mail->hasTo($userA->email);
    });

    Mail::assertQueued(PromotionalEmail::class, function ($mail) use ($userB) {
        return $mail->hasTo($userB->email);
    });
});
