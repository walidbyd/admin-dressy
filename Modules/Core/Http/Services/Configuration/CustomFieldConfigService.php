<?php

namespace Modules\Core\Http\Services\Configuration;

use App\Config\Cache\CustomFieldConfigCache;
use App\Http\Contracts\Configuration\CustomFieldConfigServiceInterface;
use App\Http\Contracts\Configuration\SettingServiceInterface;
use App\Http\Services\PsService;
use Exception;
use Modules\Core\Constants\Constants;
use Modules\Core\Entities\Configuration\Setting;
use Modules\Core\Http\Facades\PsCache;

class CustomFieldConfigService extends PsService implements CustomFieldConfigServiceInterface
{
    public function __construct(protected SettingServiceInterface $settingService) {}

    public function get()
    {
        try {
            return PsCache::remember(
                [CustomFieldConfigCache::BASE],
                CustomFieldConfigCache::GET_EXPIRY,
                [],
                fn() => $this->settingService->get(env: Constants::CUSTOM_FIELD_CONFIG)
            );
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function update($customFieldConfigData)
    {
        try {
            $customFieldConfig = $this->get();

            $settingData = $this->prepareSettingData($customFieldConfigData);

            $this->settingService->update($customFieldConfig->{Setting::id}, $settingData);

            PsCache::clear(CustomFieldConfigCache::BASE);
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function prepareSettingData($customFieldConfigData)
    {
        $setting = [
            'time_format' => [
                'id' => $customFieldConfigData['time_format']
            ]
        ];

        $settingJson = json_encode($setting);

        return [
            'setting' => $settingJson
        ];
    }
}
