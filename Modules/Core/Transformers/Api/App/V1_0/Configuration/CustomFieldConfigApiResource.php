<?php

namespace Modules\Core\Transformers\Api\App\V1_0\Configuration;

use App\Enums\CustomField\TimeFormat;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\Entities\Configuration\Color;
use Modules\Core\Entities\Configuration\Setting;

class CustomFieldConfigApiResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $setting = null;

        if (!empty($this->{Setting::setting})) {
            $setting = json_decode($this->{Setting::setting});
        }
        return [
            'time_format' => $setting ? checkAndGetValue($setting?->time_format, 'id', TimeFormat::HOUR_12) : TimeFormat::HOUR_12,
        ];
    }
}
