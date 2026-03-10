<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreComponentRequest;
use App\Http\Requests\UpdateComponentRequest;
use App\Models\ClassType;
use App\Models\Component;
use App\Models\ComponentStyleGroup;
use App\Models\ComponentStyleGroupProperties;
use App\Models\ComponentType;
use App\Models\GlobalConfigComponent;
use App\Models\LayoutType;
use App\Models\Scope;
use App\Models\StyleGroup;
use App\Models\StyleGroupProperties;
use App\Models\SupportsPlugin;
use App\Models\ThemeComponent;
use App\Traits\HandlesFileUploads;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class ComponentController extends Controller
{
    use HandlesFileUploads;

    public function index(Request $request): Renderable
    {
        $search = $request->input('search');

        $components = Component::query()
            ->join('appza_supports_plugin', 'appza_supports_plugin.slug', '=', 'appfiy_component.plugin_slug')
            ->select('appfiy_component.*', 'appza_supports_plugin.name as plugin_name')
            ->when($search, function ($query, $search) {
                $query->where('appfiy_component.name', 'like', '%'.$search.'%')
                    ->orWhere('appfiy_component.slug', 'like', '%'.$search.'%')
                    ->orWhere('appfiy_component.label', 'like', '%'.$search.'%')
                    ->orWhere('appza_supports_plugin.slug', 'like', '%'.$search.'%')
                    ->orWhere('appza_supports_plugin.name', 'like', '%'.$search.'%')
                    ->orWhere('appfiy_component.scope', 'like', '%'.$search.'%');
            })
            ->orderByDesc('appfiy_component.id')
            ->paginate(20);

        $components->appends(['search' => $search]);

        return view('component.index', [
            'components' => $components,
            'search' => $search,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Renderable
    {
        $pluginDropdown = SupportsPlugin::getPluginDropdown();

        return view('component.add', compact('pluginDropdown'));
    }

    public function store(StoreComponentRequest $request): RedirectResponse
    {
        $plugin = $request->validated('plugin_slug');
        $input = [
            'parent_id' => null,
            'layout_type_id' => null,
            'name' => null,
            'slug' => null,
            'plugin_slug' => $plugin,
        ];

        $component = Component::create($input);

        if (! $component) {
            return redirect()->back()->with('error', __('messages.componentCreationFailed'));
        }

        $styleGroups = StyleGroup::where('is_active', 1)->whereJsonContains('plugin_slug', $plugin)->get();

        $this->addComponentStylesWithProperties($styleGroups, $component);

        return redirect()->route('component_edit', $component->id);
    }

    private function addComponentStylesWithProperties(Collection $styleGroups, Component $component): void
    {
        $existingStyleGroupIds = ComponentStyleGroup::where('component_id', $component->id)
            ->whereIn('style_group_id', $styleGroups->pluck('id'))
            ->pluck('style_group_id')
            ->toArray();

        $styleGroups->each(function ($group) use ($component, $existingStyleGroupIds) {

            if (in_array($group->id, $existingStyleGroupIds)) {
                return;
            }

            ComponentStyleGroup::create([
                'component_id' => $component->id,
                'style_group_id' => $group->id,
                'style_group_label' => trim(
                    str_ireplace('decoration', '', $group->name)
                ),
            ]);

            $properties = StyleGroupProperties::where('appfiy_style_group_properties.style_group_id', $group->id)
                ->join(
                    'appfiy_style_properties',
                    'appfiy_style_properties.id',
                    '=',
                    'appfiy_style_group_properties.style_property_id'
                )
                ->where('appfiy_style_properties.is_active', 1)
                ->select([
                    'appfiy_style_properties.name',
                    'appfiy_style_properties.input_type',
                    'appfiy_style_properties.value',
                    'appfiy_style_properties.default_value',
                ])
                ->get();

            $insertData = $properties->map(function ($property) use ($component, $group) {
                return [
                    'component_id' => $component->id,
                    'style_group_id' => $group->id,
                    'name' => $property->name,
                    'input_type' => $property->input_type,
                    'value' => $property->value,
                    'default_value' => $property->default_value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            if (! empty($insertData)) {
                ComponentStyleGroupProperties::insert($insertData);
            }
        });
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id): Renderable
    {
        $data = Component::findOrFail($id);
        $layoutTypes = LayoutType::where('is_active', 1)->pluck('name', 'id');

        $styleGroups = StyleGroup::where('is_active', 1)->whereJsonContains('plugin_slug', $data->plugin_slug)->get();
        $this->addComponentStylesWithProperties($styleGroups, $data);

        $properties = $this->getComponentStyleGroupProperties($id, $styleGroups);
        $componentStyleIdArray = $this->getCheckedStyleGroupIds($properties);

        $scopes = $this->formatScopes(Scope::select(['id', 'name', 'slug', 'is_global'])->whereIn('plugin_slug', [$data->plugin_slug, 'global'])->get());
        $componentType = ComponentType::where('is_active', 1)->get();
        $pluginDropdown = SupportsPlugin::getPluginDropdown();

        $alreadyUse = $this->checkIfComponentIsAlreadyInUse($id);

        $classTypesDropdown = ClassType::getDropdown($data->plugin_slug);

        return view('component.edit', [
            'data' => $data,
            'layoutTypes' => $layoutTypes,
            'scopeArrayData' => $scopes,
            'styleGroups' => $styleGroups,
            'componentStyleIdArray' => $componentStyleIdArray,
            'componentStyleGroup' => $properties,
            'componentType' => $componentType,
            'pluginDropdown' => $pluginDropdown,
            'classTypesDropdown' => $classTypesDropdown,
            'alreadyUse' => $alreadyUse,
        ]);
    }

    /**
     * Fetch and process component style group properties.
     */
    protected function getComponentStyleGroupProperties(int $componentId, Collection $styleGroups): array
    {
        $styleGroupIds = $styleGroups->pluck('id')->toArray();

        $componentStyleGroups = ComponentStyleGroup::with('styleGroup:id,name,slug')
            ->where('component_id', $componentId)
            ->whereIn('style_group_id', $styleGroupIds)
            ->get();

        $validProperties = StyleGroupProperties::whereIn('style_group_id', $styleGroupIds)
            ->join('appfiy_style_properties', 'appfiy_style_properties.id', '=', 'appfiy_style_group_properties.style_property_id')
            ->where('appfiy_style_properties.is_active', 1)
            ->select(
                'appfiy_style_group_properties.style_group_id',
                'appfiy_style_properties.name',
                'appfiy_style_properties.input_type',
                'appfiy_style_properties.value',
                'appfiy_style_properties.default_value'
            )
            ->get()
            ->groupBy('style_group_id');

        $existingProperties = ComponentStyleGroupProperties::where('component_id', $componentId)
            ->whereIn('style_group_id', $styleGroupIds)
            ->get()
            ->groupBy('style_group_id');

        $properties = [];

        foreach ($componentStyleGroups as $group) {
            $groupId = $group->style_group_id;

            $valid = $validProperties[$groupId] ?? collect();

            $existingGroupItems = $existingProperties[$groupId] ?? collect();

            $existing = $existingGroupItems->keyBy('name');

            $validNames = $valid->pluck('name')->unique()->toArray();
            $existingNames = $existingGroupItems->pluck('name')->unique()->toArray();

            $namesToAdd = array_diff($validNames, $existingNames);
            $namesToDelete = array_diff($existingNames, $validNames);

            if (! empty($namesToDelete)) {
                ComponentStyleGroupProperties::where('component_id', $componentId)
                    ->where('style_group_id', $groupId)
                    ->whereIn('name', $namesToDelete)
                    ->delete();
            }

            foreach ($namesToAdd as $name) {
                $prop = $valid->firstWhere('name', $name);
                if ($prop) {
                    ComponentStyleGroupProperties::create([
                        'component_id' => $componentId,
                        'style_group_id' => $groupId,
                        'name' => $prop->name,
                        'input_type' => $prop->input_type,
                        'value' => $prop->value,
                        'default_value' => $prop->default_value,
                    ]);

                    $existing->put($name, (object) [
                        'component_id' => $componentId,
                        'style_group_id' => $groupId,
                        'name' => $prop->name,
                        'input_type' => $prop->input_type,
                        'value' => $prop->value,
                        'default_value' => $prop->default_value,
                    ]);
                }
            }

            $groupArray = $group->toArray();
            $groupArray['properties'] = array_map(function ($item) {
                return (array) $item;
            }, $existing->values()->toArray());

            $groupArray['style_group_id'] = $groupId;

            $properties[] = $groupArray;
        }

        return $properties;
    }

    /**
     * Retrieve checked style group IDs from component properties.
     */
    protected function getCheckedStyleGroupIds(array $properties): array
    {
        return collect($properties)
            ->filter(fn ($group) => ! empty($group['is_checked']))
            ->pluck('style_group_id')
            ->toArray();
    }

    /**
     * Format scopes into grouped associative array.
     */
    protected function formatScopes(Collection $scopes): array
    {
        $scopeArray = [];
        foreach ($scopes as $val) {
            $key = $val['is_global'] == 0 ? 'page-scope' : 'global-scope';
            $scopeArray[$key][] = $val->toArray();
        }

        return $scopeArray;
    }

    /**
     * Check if the component is already in use globally or on a theme page.
     */
    protected function checkIfComponentIsAlreadyInUse(int $componentId): bool
    {
        return GlobalConfigComponent::where('component_id', $componentId)->exists()
            || ThemeComponent::where('component_id', $componentId)->exists();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateComponentRequest $request, int $id): RedirectResponse
    {
        $input = $request->only([
            'name', 'label', 'component_type_id', 'scope', 'layout_type_id',
            'style_group', 'plugin_slug', 'icon_code', 'event', 'class_type',
            'app_icon', 'web_icon', 'product_type', 'selected_design',
            'details_page', 'transparent', 'is_active', 'is_upcoming',
            'is_multiple', 'items', 'dev_data', 'pagination', 'filters',
            'show_no_data_view',
        ]);

        $input['scope'] = json_encode($input['scope']);
        $component = Component::findOrFail($id);

        $input['image'] = config('app.is_image_update')
            ? $this->handleFileUpload($request, $component, 'image', 'component-image')
            : $component->image;

        $input['image_url'] = config('app.is_image_update')
            ? $this->handleFileUpload($request, $component, 'image_url', 'component-image')
            : $component->image_url;

        DB::beginTransaction();
        try {
            $component->update($input);

            if (isset($input['style_group'])) {
                ComponentStyleGroup::where('component_id', $id)
                    ->update(['is_checked' => false]);

                foreach ($input['style_group'] as $styleGroupId) {
                    ComponentStyleGroup::where('component_id', $id)
                        ->where('style_group_id', $styleGroupId)
                        ->update(['is_checked' => true]);
                }
            }

            DB::commit();
            Session::flash('message', __('messages.updateMessage'));

            return redirect()->route('component_list');
        } catch (\Exception $e) {
            DB::rollback();
            Session::flash('danger', $e->getMessage());

            return redirect()->back()->withInput();
        }
    }

    public function componentPropertiesInlineUpdate(Request $request): JsonResponse
    {
        $componentPropertiesId = $request->input('component_properties_id');
        $value = $request->input('value');

        $property = ComponentStyleGroupProperties::findOrFail($componentPropertiesId);

        $property->update(['value' => $value]);

        return response()->json(['status' => 'ok'], 200);
    }

    public function componentStyleGroupInlineUpdate(Request $request): JsonResponse
    {
        $id = $request->input('id');
        $value = $request->input('value');

        $styleGroup = ComponentStyleGroup::findOrFail($id);

        $styleGroup->update(['style_group_label' => $value]);

        return response()->json(['status' => 'ok'], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): RedirectResponse
    {
        if (GlobalConfigComponent::where('component_id', $id)->exists()) {
            $usedConfigs = GlobalConfigComponent::where('component_id', $id)
                ->join('appfiy_global_config', 'appfiy_global_config.id', '=', 'appfiy_global_config_component.global_config_id')
                ->pluck('appfiy_global_config.name')->unique()->implode(', ');

            Session::flash('validate', __('messages.alreadyUseGlobalConfig').' Please check config [ '.$usedConfigs.' ]');

            return redirect()->route('component_list');
        }

        if (ThemeComponent::where('component_id', $id)->exists()) {
            $usedThemes = ThemeComponent::where('component_id', $id)
                ->join('appfiy_theme', 'appfiy_theme.id', '=', 'appfiy_theme_component.theme_id')
                ->pluck('appfiy_theme.name')->unique()->implode(', ');

            Session::flash('validate', __('messages.alreadyUseTheme').' Please check theme [ '.$usedThemes.' ]');

            return redirect()->route('component_list');
        }

        Component::findOrFail($id)->delete();

        Session::flash('delete', __('messages.deleteMessage'));

        return redirect()->route('component_list');
    }

    public function updatePluginSlug(Request $request): JsonResponse
    {
        $component = Component::findOrFail($request->input('id'));
        $component->update(['plugin_slug' => $request->input('value')]);

        return response()->json(['status' => 'ok'], 200);
    }
}
