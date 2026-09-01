<?php

namespace Modules\Payment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOfflinePaymentSettingRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => 'required|unique:psx_core_keys,name',
            'description' => 'required',
            'icon' => 'nullable|sometimes|image',
            'status' => 'nullable',
            'added_user_id' => 'nullable',
        ];
    }

    public function attributes()
    {
        return [
            'icon' => 'Icon',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
}
