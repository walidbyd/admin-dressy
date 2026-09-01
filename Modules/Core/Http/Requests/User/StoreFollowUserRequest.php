<?php

namespace Modules\Core\Http\Requests\User;

use App\Exceptions\PsApiException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class StoreFollowUserRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'user_id' => 'required|exists:users,id',
            'followed_user_id' => 'required|exists:users,id',
        ];
    }

    public function attributes()
    {
        return [
            'user_id' => 'User',
            'followed_user_id' => 'Followed User',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new PsApiException(
            implode("\n", Arr::flatten($validator->getMessageBag()->getMessages()))
        );

    }
}
