<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('allows authenticated users to edit their profile', function () {
    $user = User::factory()->donor()->create();

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertSuccessful()
        ->assertSee('Edit profile');
});

it('updates profile name and email', function () {
    $user = User::factory()->donor()->create([
        'name' => 'Old Name',
        'email' => 'old@example.com',
    ]);

    $this->actingAs($user)
        ->put(route('profile.update'), [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ])
        ->assertRedirect(route('donor.dashboard'));

    $user->refresh();
    expect($user->name)->toBe('New Name')
        ->and($user->email)->toBe('new@example.com');
});

it('updates profile password when provided', function () {
    $user = User::factory()->donor()->create(['password' => 'oldpassword']);

    $this->actingAs($user)
        ->put(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])
        ->assertRedirect(route('donor.dashboard'));

    expect(Hash::check('newpassword123', $user->fresh()->password))->toBeTrue();
});

it('requires authentication for profile routes', function () {
    $this->get(route('profile.edit'))->assertRedirect(route('login'));
});
