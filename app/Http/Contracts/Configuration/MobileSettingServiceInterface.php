<?php

namespace App\Http\Contracts\Configuration;

use App\Http\Contracts\Core\PsInterface;

interface MobileSettingServiceInterface extends PsInterface
{
    public function save($mobileSettingData, $mobileColors = []);

    public function update($id, $mobileSettingData, $mobileColors = []);

    public function get($id = null, $relation = null);
}
