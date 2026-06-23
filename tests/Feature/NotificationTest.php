<?php

use App\Models\Form;
use App\Models\User;
use App\Models\Course;
use App\Models\SystemSetting;
use App\Notifications\NewUserRegisteredWithPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('sends NewUserRegisteredWithPassword notification when guest submits a form', function () {
    // Seed roles
    $this->artisan('db:seed', ['--class' => 'RolesSeeder']);

    Notification::fake();

    $form = Form::create([
        'title' => 'Form for new user test',
        'slug' => 'test-reg-form-' . uniqid(),
        'is_active' => true,
        'schema' => [
            [
                'type' => 'text',
                'name' => 'name',
                'label' => 'Ваше имя',
                'required' => true
            ],
            [
                'type' => 'email',
                'name' => 'email',
                'label' => 'Ваш Email',
                'required' => true
            ]
        ],
        'settings' => [
            'success_message' => 'Done'
        ]
    ]);

    $email = 'notified-student-' . uniqid() . '@example.com';

    $response = $this->post(route('public.form.submit', $form->id), [
        'name' => 'John Doe',
        'email' => $email,
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertStatus(302); // Should redirect back

    // Verify user was created
    $user = User::where('email', $email)->first();
    expect($user)->not->toBeNull();

    // Assert notification was sent
    Notification::assertSentTo(
        $user,
        NewUserRegisteredWithPassword::class
    );
});

it('sends NewUserRegisteredWithPassword notification when Tilda webhook creates a user', function () {
    // Seed roles
    $this->artisan('db:seed', ['--class' => 'RolesSeeder']);

    Notification::fake();

    // Set up settings key
    SystemSetting::updateOrCreate(
        ['key' => 'tilda_secret'],
        ['payload' => 'supersecret', 'group' => 'tilda']
    );

    // Create course
    $teacher = User::create([
        'name' => 'Teacher',
        'email' => 'teacher-notify-' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
    ]);

    $course = Course::create([
        'teacher_id' => $teacher->id,
        'title' => 'Tilda Notify Course',
        'slug' => 'tilda-notify-' . uniqid(),
        'description' => 'Test',
        'price' => 5000,
        'is_published' => true,
    ]);

    $email = 'tilda-student-' . uniqid() . '@example.com';

    $response = $this->postJson('/api/webhooks/tilda', [
        'secret' => 'supersecret',
        'email' => $email,
        'name' => 'Webhook Student',
        'course_id' => $course->id,
        'payment' => [
            'orderid' => 'trans_' . uniqid(),
            'amount' => 5000
        ]
    ]);

    $response->assertStatus(200);

    // Verify user was created
    $user = User::where('email', $email)->first();
    expect($user)->not->toBeNull();

    // Assert notification was sent
    Notification::assertSentTo(
        $user,
        NewUserRegisteredWithPassword::class
    );
});
