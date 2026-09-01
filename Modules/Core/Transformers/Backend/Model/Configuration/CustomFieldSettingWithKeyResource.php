<?php

namespace Modules\Core\Transformers\Backend\Model\Configuration;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomFieldSettingWithKeyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => (int) checkAndGetValue($this, 'id'),
            'setting_env' => (string) checkAndGetValue($this, 'setting_env'),
            'setting' => (array) $this->getSetting(),
            'ref_selection' => (array) $this->getRefSelection(),
        ];
    }

    private function getSetting()
    {
        if (empty($this->setting)) {
            return '';
        }
        return json_decode($this->setting, true);
    }

    private function getRefSelection()
    {
        if (empty($this->ref_selection)) {
            return '';
        }
        return json_decode($this->ref_selection, true);
    }
}
