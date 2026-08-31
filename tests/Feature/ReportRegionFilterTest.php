<?php

use App\Admin\DTOs\ReportFilterDto;
use App\Admin\Reports\ReportFactory;
use App\Enums\FoodRegion;
use App\Enums\ReportType;
use App\Models\Donation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('filters donations by region using the decorator pattern', function () {
    $donor = User::factory()->donor()->create(['region' => FoodRegion::KualaLumpur]);

    Donation::factory()->create([
        'user_id' => $donor->id,
        'region' => FoodRegion::KualaLumpur,
    ]);
    Donation::factory()->create([
        'user_id' => $donor->id,
        'region' => FoodRegion::Penang,
    ]);

    $report = app(ReportFactory::class)->make(new ReportFilterDto(
        type: ReportType::Donations,
        region: FoodRegion::KualaLumpur->value,
    ));

    expect($report->paginate(25)->total())->toBe(1);
});

it('filters donations by region on admin dashboard', function () {
    $admin = User::factory()->admin()->create();
    $donor = User::factory()->donor()->create(['region' => FoodRegion::Selangor]);

    Donation::factory()->create([
        'user_id' => $donor->id,
        'region' => FoodRegion::Selangor,
    ]);
    Donation::factory()->create([
        'user_id' => $donor->id,
        'region' => FoodRegion::Johor,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard', ['tab' => 'donations', 'region' => 'selangor']))
        ->assertSuccessful()
        ->assertSee('1 records in database')
        ->assertSee('Selangor');
});
