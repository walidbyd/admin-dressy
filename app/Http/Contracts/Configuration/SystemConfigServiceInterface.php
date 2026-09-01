<?php

namespace App\Http\Contracts\Configuration;

use App\Http\Contracts\Core\PsInterface;

interface SystemConfigServiceInterface extends PsInterface
{
    public function update($id, $systemConfigData, $adsTxtFile = null, $mobileSettingId = null, $mobileSettingData = null);

    public function get($id = null, $relation = null);
}
