<?php

namespace Modules\Core\Database\factories\Vendor;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Entities\Vendor\Vendor;

class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    public function definition()
    {
        $user = User::factory()->create();

        return [
            Vendor::name => $this->faker->name,
            Vendor::phone => $this->faker->phoneNumber,
            Vendor::email => $this->faker->email(),
            Vendor::address => $this->faker->address,
            Vendor::description => $this->faker->text(200),
            Vendor::website => $this->faker->url,
            Vendor::facebook => $this->faker->url,
            Vendor::instagram => $this->faker->url,
            Vendor::status => 1,
            Vendor::ownerUserId => $user->{User::id},
            Vendor::isUnlimited => 1,
            Vendor::addedUserId => $user->{User::id},
        ];
    }
}
