<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VersionCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'app_name' => ['required', 'string'],
            'mobile_version' => ['required', 'regex:/^\d+\.\d+\.\d+$/'],
            'plugin_version' => ['required', 'regex:/^\d+\.\d+\.\d+$/'],
            'plugin_slug' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'app_name.required' => 'App name is required.',
            'mobile_version.required' => 'Mobile version is required.',
            'mobile_version.regex' => 'Mobile version must follow semantic versioning (e.g. 1.0.151).',
            'plugin_version.required' => 'Plugin version is required.',
            'plugin_version.regex' => 'Plugin version must follow semantic versioning (e.g. 1.2.201).',
            'plugin_slug.required' => 'Plugin slug is required.',
        ];
    }
}
