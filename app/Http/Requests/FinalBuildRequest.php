<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FinalBuildRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'site_url' => 'required|url',
            'license_key' => 'required',
            'is_push_notification' => 'boolean',
        ];

        return $rules;
    }

    public function messages()
    {
        return [
            'is_push_notification.boolean' => 'The push notification value must be true or false.',
        ];
    }
}
