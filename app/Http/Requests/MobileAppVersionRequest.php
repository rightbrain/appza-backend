<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MobileAppVersionRequest extends FormRequest
{
    /**
     * Allow request
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        $rules = [
            'mobile_app_id' => [
                'required',
                'integer',
                'exists:appza_support_mobile_apps,id',
            ],

            // STRICT semantic versioning: X.Y.Z
            'mobile_version' => [
                'required',
                'regex:/^\d+\.\d+\.\d+$/',
            ],

            'minimum_plugin_version' => [
                'required',
                'regex:/^\d+\.\d+\.\d+$/',
            ],

            'latest_plugin_version' => [
                'required',
                'regex:/^\d+\.\d+\.\d+$/',
            ],

            'optional_message' => [
                'nullable',
                'string',
            ],

            'force_update' => [
                'nullable',
                'boolean',
            ],
        ];

        // CREATE
        if ($this->isMethod('POST')) {
            $rules = array_merge($rules, $this->storeRules());
        }

        // UPDATE
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules = array_merge($rules, $this->updateRules());
        }

        return $rules;
    }

    /**
     * Rules for creating a new version rule
     */
    protected function storeRules(): array
    {
        return [
            'mobile_version' => [
                'required',
                'regex:/^\d+\.\d+\.\d+$/',
                Rule::unique('appza_mobile_version_mapping')->where(function ($query) {
                    return $query->where('mobile_app_id', $this->mobile_app_id);
                }),
            ],
        ];
    }

    /**
     * Rules for updating an existing version rule
     */
    protected function updateRules(): array
    {
        return [
            'mobile_version' => [
                'required',
                'regex:/^\d+\.\d+\.\d+$/',
                Rule::unique('appza_mobile_version_mapping')
                    ->where(function ($query) {
                        return $query->where('mobile_app_id', $this->mobile_app_id);
                    })
                    ->ignore($this->route('id'), 'id'),
            ],
        ];
    }

    /**
     * Custom validation messages
     */
    public function messages(): array
    {
        return [
            'mobile_app_id.required' => 'Mobile app is required.',
            'mobile_app_id.exists' => 'Selected mobile app does not exist.',

            'mobile_version.required' => 'Mobile version is required.',
            'mobile_version.regex' =>
                'Mobile version must follow semantic versioning (e.g. 1.2.3).',
            'mobile_version.unique' =>
                'This mobile version already exists for the selected app.',

            'minimum_plugin_version.required' =>
                'Minimum plugin version is required.',
            'minimum_plugin_version.regex' =>
                'Minimum plugin version must be in format X.Y.Z.',

            'latest_plugin_version.required' =>
                'Latest plugin version is required.',
            'latest_plugin_version.regex' =>
                'Latest plugin version must be in format X.Y.Z.',

            'force_update.boolean' =>
                'Force update must be true or false.',
        ];
    }

    /**
     * Normalize input before validation
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'force_update' => $this->boolean('force_update'),
        ]);
    }
}
