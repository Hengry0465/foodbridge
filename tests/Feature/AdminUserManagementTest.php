<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows admin to edit a user', function () {
    $admin = User::factory()->admin()->create();
    $donor = User::factory()->donor()->create(['email' => 'donor@example.com']);

    $this->actingAs($admin)
        ->get(route('admin.users.edit', $donor))
        ->assertSuccessful()
        ->assertSee('Edit user');
});

it('allows admin to update a user', function () {
    $admin = User::factory()->admin()->create();
    $donor = User::factory()->donor()->create([
        'name' => 'Donor One',
        'email' => 'donor@example.com',
    ]);

    $this->actingAs($admin)
        ->put(route('admin.users.update', $donor), [
            'name' => 'Updated Donor',
            'email' => 'updated@example.com',
            'role' => UserRole::Recipient->value,
            'is_active' => '1',
        ])
        ->assertRedirect(route('admin.dashboard', ['tab' => 'users']));

    $donor->refresh();
    expect($donor->name)->toBe('Updated Donor')
        ->and($donor->email)->toBe('updated@example.com')
        ->and($donor->role)->toBe(UserRole::Recipient);
});

it('allows admin to deactivate and activate users', function () {
    $admin = User::factory()->admin()->create();
    $donor = User::factory()->donor()->create();

    $this->actingAs($admin)
        ->post(route('admin.users.deactivate', $donor))
        ->assertRedirect();

    expect($donor->fresh()->is_active)->toBeFalse();

    $this->actingAs($admin)
        ->post(route('admin.users.activate', $donor))
        ->assertRedirect();

    expect($donor->fresh()->is_active)->toBeTrue();
});

it('prevents admin from deactivating themselves', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.users.deactivate', $admin))
        ->assertSessionHasErrors('user');

    expect($admin->fresh()->is_active)->toBeTrue();
});
