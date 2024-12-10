<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ApkBuildRequest extends FormRequest
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
            'app_name' => 'required|string',
            'app_logo' => 'required|string',
            'app_splash_screen_image' => 'required|string',
            'site_url' => 'required|url',
            'license_key' => 'required',
            'email' => 'required|email',
        ];

        $iosFields = ['ios_issuer_id', 'ios_key_id', 'ios_p8_file_content', 'ios_team_id'];

        // Check if any iOS specific field is filled
        $iosFieldFilled = false;
        foreach($iosFields as $field){
            if ($this->filled($field)) {
                $iosFieldFilled = true;
            }
        }

        foreach($iosFields as $field) {
            $rules[$field] = $iosFieldFilled ? 'required' : 'nullable';
        }

        return $rules;
    }
}