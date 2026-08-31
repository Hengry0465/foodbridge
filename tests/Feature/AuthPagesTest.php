<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the login page', function () {
    $this->get(route('login'))
        ->assertSuccessful()
        ->assertSee('Welcome back');
});

it('shows the register page', function () {
    $this->get(route('register'))
        ->assertSuccessful()
        ->assertSee('Join FoodBridge');
});

it('registers a donor and redirects to donor dashboard', function () {
    $this->post('/register', [
        'name' => 'Test Donor',
        'username' => 'test_donor',
        'email' => 'donor@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => UserRole::Donor->value,
    ])->assertRedirect(route('donor.dashboard'));

    $this->assertAuthenticated();
    expect(auth()->user()?->username)->toBe('test_donor');
});

it('registers a recipient and redirects to recipient dashboard', function () {
    $this->post('/register', [
        'name' => 'Test Recipient',
        'username' => 'test_recipient',
        'email' => 'recipient@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => UserRole::Recipient->value,
    ])->assertRedirect(route('recipient.dashboard'));

    $this->assertAuthenticated();
});

it('logs in an active user with username', function () {
    $user = User::factory()->donor()->create([
        'username' => 'login_user',
        'password' => 'password123',
    ]);

    $this->post('/login', [
        'username' => 'login_user',
        'password' => 'password123',
    ])->assertRedirect(route('donor.dashboard'));

    $this->assertAuthenticatedAs($user);
});

it('rejects login for deactivated users', function () {
    User::factory()->donor()->inactive()->create([
        'username' => 'inactive_user',
        'password' => 'password123',
    ]);

    $this->post('/login', [
        'username' => 'inactive_user',
        'password' => 'password123',
    ])->assertSessionHasErrors('username');

    $this->assertGuest();
});

it('logs out an authenticated user', function () {
    $user = User::factory()->donor()->create();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

it('protects dashboards from guests', function () {
    $this->get(route('donor.dashboard'))->assertRedirect(route('login'));
});
