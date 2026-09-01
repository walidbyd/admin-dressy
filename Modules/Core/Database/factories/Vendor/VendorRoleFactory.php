<?php

namespace Modules\Core\Database\factories\Vendor;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Entities\Vendor\VendorRole;

class VendorRoleFactory extends Factory
{
    protected $model = VendorRole::class;

    public function definition()
    {
        $roles = ['Owner', 'Manager', 'Employer'];

        return [
            VendorRole::name => $this->faker->randomElement($roles),
            VendorRole::description => 'description',
            VendorRole::addedUserId => User::factory(),
            VendorRole::status => 1,
        ];
    }
}
