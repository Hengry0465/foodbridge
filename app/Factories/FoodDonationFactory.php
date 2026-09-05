<?php
namespace App\Factories;

use App\Factories\DonationTypes\CookedFoodDonation;
use App\Factories\DonationTypes\DonationTypeInterface;
use App\Factories\DonationTypes\FreshProduceDonation;
use App\Factories\DonationTypes\PackagedGoodsDonation;
use App\Models\Donation;
use Carbon\Carbon;
use InvalidArgumentException;

class FoodDonationFactory
{
    public static function create(array $data): Donation
    {
        $type = self::makeType($data['donation_type']);

        $providedExpiry = isset($data['expiry_date']) ? Carbon::parse($data['expiry_date']) : null;
        $data['expiry_date'] = $type->calculateExpiry($providedExpiry);

        return Donation::create($data);
    }

    public static function makeType(string $donationType): DonationTypeInterface
    {
        return match ($donationType) {
            'cooked_food' => new CookedFoodDonation(),
            'fresh_produce' => new FreshProduceDonation(),
            'packaged_goods' => new PackagedGoodsDonation(),
            default => throw new InvalidArgumentException("Unknown donation type: {$donationType}"),
        };
    }
}