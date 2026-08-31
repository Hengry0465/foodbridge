<?php

use App\Enums\DonationStatus;
use App\Models\Donation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects guests away from admin dashboard', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
});

it('forbids non-admin users from admin dashboard', function () {
    $donor = User::factory()->donor()->create();

    $this->actingAs($donor)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

it('shows admin dashboard with platform stats', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertSee('Platform Statistics')
        ->assertSee('Admin Dashboard');
});

it('shows donations matches and pickups tabs', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.dashboard', ['tab' => 'donations']))
        ->assertSuccessful()
        ->assertSee('Donations');

    $this->actingAs($admin)
        ->get(route('admin.dashboard', ['tab' => 'matches']))
        ->assertSuccessful()
        ->assertSee('Matches');

    $this->actingAs($admin)
        ->get(route('admin.dashboard', ['tab' => 'pickups']))
        ->assertSuccessful()
        ->assertSee('Pickups');
});

it('filters users on admin dashboard', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->donor()->create(['name' => 'Alpha Donor', 'username' => 'alpha_donor']);
    User::factory()->recipient()->create(['name' => 'Beta Recipient', 'username' => 'beta_recipient']);

    $this->actingAs($admin)
        ->get(route('admin.dashboard', ['tab' => 'users', 'role' => 'donor', 'search' => 'Alpha']))
        ->assertSuccessful()
        ->assertSee('Alpha Donor')
        ->assertDontSee('Beta Recipient');
});

it('filters donations by status on admin dashboard', function () {
    $admin = User::factory()->admin()->create();
    $donor = User::factory()->donor()->create();

    Donation::factory()->create([
        'user_id' => $donor->id,
        'status' => DonationStatus::Available,
    ]);
    Donation::factory()->create([
        'user_id' => $donor->id,
        'status' => DonationStatus::Completed,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.dashboard', ['tab' => 'donations', 'status' => 'available']));

    $response->assertSuccessful()
        ->assertSee('1 records in database')
        ->assertSee('>available<', false);
});

it('deactivates a user from admin dashboard', function () {
    $admin = User::factory()->admin()->create();
    $donor = User::factory()->donor()->create();

    $this->actingAs($admin)
        ->post(route('admin.users.deactivate', $donor))
        ->assertRedirect();

    expect($donor->fresh()->is_active)->toBeFalse();
});
