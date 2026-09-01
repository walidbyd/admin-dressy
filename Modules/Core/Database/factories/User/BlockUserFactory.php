<?php

namespace Modules\Core\Database\factories\User;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Entities\User\BlockUser;

class BlockUserFactory extends Factory
{
    protected $model = BlockUser::class;

    public function definition()
    {
        return [
            BlockUser::fromBlockUserId => User::factory(),
            BlockUser::toBlockUserId => User::factory(),
            BlockUser::addedUserId => function (array $attributes) {
                return $attributes[BlockUser::fromBlockUserId];
            },
            BlockUser::addedDate => now(),
        ];
    }
}
