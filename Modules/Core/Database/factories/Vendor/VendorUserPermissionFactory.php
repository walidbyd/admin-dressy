<?php

namespace Modules\Core\Database\factories\Vendor;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Entities\Vendor\VendorUserPermission;

class VendorUserPermissionFactory extends Factory
{
    protected $model = VendorUserPermission::class;

    public function definition()
    {
        $user = User::factory();

        return [
            VendorUserPermission::userId => $user,
            VendorUserPermission::vendorAndRole => json_encode(['1' => '1']),
            VendorUserPermission::addedUserId => function (array $attributes) {
                return $attributes[VendorUserPermission::userId];
            },
        ];
    }
}
