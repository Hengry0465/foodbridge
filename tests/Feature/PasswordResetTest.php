<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

it('shows forgot password page', function () {
    $this->get(route('password.request'))
        ->assertSuccessful()
        ->assertSee('Forgot password');
});

it('sends a password reset link', function () {
    Notification::fake();

    $user = User::factory()->donor()->create(['email' => 'reset@example.com']);

    $this->post(route('password.email'), ['email' => 'reset@example.com'])
        ->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPassword::class);
});

it('resets password with valid token', function () {
    $user = User::factory()->donor()->create(['email' => 'reset@example.com']);
    $token = Password::createToken($user);

    $this->post(route('password.update'), [
        'email' => 'reset@example.com',
        'token' => $token,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ])->assertRedirect(route('login'));

    expect(Hash::check('newpassword123', $user->fresh()->password))->toBeTrue();
});
