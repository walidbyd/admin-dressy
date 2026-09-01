<?php

namespace Modules\Core\Database\factories\Item;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Entities\Item\Item;
use Modules\Core\Entities\Item\PaidItemHistory;

class PaidItemHistoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PaidItemHistory::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $paymentMethods = ['stripe', 'paystack', 'razor', 'In_App_Purchase', 'offline', 'paypal'];

        return [
            PaidItemHistory::itemId => Item::factory(),
            PaidItemHistory::startTimestamp => now()->unix(),
            PaidItemHistory::endTimestamp => now()->addMonth()->unix(),
            PaidItemHistory::paymentMethod => $this->faker->randomElement($paymentMethods),
            PaidItemHistory::amount => 100,
            PaidItemHistory::promotedDays => 30,
            PaidItemHistory::status => 1,
            PaidItemHistory::addedDate => now(),
            PaidItemHistory::addedUserId => User::factory(),
        ];
    }
}
