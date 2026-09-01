<?php

namespace Modules\Core\Database\factories\Configuration;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Entities\Configuration\AdPostType;

class AdPostTypeFactory extends Factory
{
    protected $model = AdPostType::class;

    public function definition()
    {
        $adPostTypes = ['paid_item_first', 'bumps_ups_between', 'google_ads_between', 'bumps_and_google_ads_between', 'normal_ads_only'];

        return [
            AdPostType::key => $this->faker->randomElement($adPostTypes),
            AdPostType::value => 'This is Ad Post Type Title',
            AdPostType::addedUserId => User::factory(),
        ];
    }
}
