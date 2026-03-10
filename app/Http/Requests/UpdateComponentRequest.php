<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'name' => 'required|unique:appfiy_component,name,'.$id,
            'component_type_id' => 'required',
            'scope' => 'required',
            'layout_type_id' => 'required',
            'style_group' => 'required',
            'plugin_slug' => 'required',
            'items' => 'nullable|json',
            'dev_data' => 'nullable|json',
            'pagination' => 'nullable|json',
            'filters' => 'nullable|json',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('messages.enterComponentName'),
            'name.unique' => __('messages.componentNameMustbeUnique'),
            'component_type_id.required' => __('messages.chooseComponentType'),
            'scope.required' => __('messages.ChooseScope'),
            'layout_type_id.required' => __('messages.chooseLayoutType'),
            'style_group.required' => __('messages.chooseStyleGroup'),
            'plugin_slug.required' => __('messages.choosePlugin'),
            'items.json' => __('messages.ItemJsonNotValid'),
            'dev_data.json' => __('messages.DevDataJsonNotValid'),
            'pagination.json' => __('messages.PaginationJsonNotValid'),
            'filters.json' => __('messages.FiltersJsonNotValid'),
        ];
    }
}
