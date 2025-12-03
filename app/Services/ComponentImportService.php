<?php
namespace App\Services;

use App\Models\Component;
use App\Models\ComponentStyleGroup;
use App\Models\ComponentStyleGroupProperties;
use App\Models\Page;
use App\Models\StyleGroup;
use App\Models\StyleGroupProperties;
use App\Models\StyleProperties;
use App\Models\SupportsPlugin;
use App\Models\LayoutType;
use App\Models\Scope;
use App\Models\ComponentType;
use App\Models\ClassType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ComponentImportService
{
    /**
     * Import component JSON payload
     * @param array $payload
     * @param bool $overwrite
     * @return array
     */
    public function import(array $payload, bool $overwrite = false): array
    {
        DB::beginTransaction();
        try {
            $pluginSlug = $payload['plugin']['slug'];
//            dump($payload['plugin']['slug']);
            // Import reference data first
            $referenceData = $this->importReferenceData($payload);
//            dump($payload);
            $payload['component']['layout_type_id'] = $referenceData['layout_type_id'] ?? null;
            $payload['component']['component_type_id'] = $referenceData['component_type_id'] ?? null;

            // Import component
            $component = $this->importComponent($payload['component'], $overwrite);

            // Import related data
            $this->importComponentStyleGroups($component->id, $payload['style_groups'], $payload['properties'], $pluginSlug);
//            $this->importComponentProperties($component->id, $payload['properties']);
//            $this->importComponentScopes($component, $payload['scopes']);

            DB::commit();

            Log::info('Component imported successfully', [
//                'component_id' => $component->id,
                'source' => $payload['meta']['source'] ?? 'unknown',
                'overwrite' => $overwrite
            ]);

            return [
                'success' => true,
                'message' => 'Component imported successfully',
//                'component_id' => $component->id
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Component import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Import reference data (plugins, layout types, etc.)
     */

    protected function importReferenceData(array $payload): array
    {
        // Import plugin if not exists
        if (!empty($payload['plugin'])) {
            SupportsPlugin::updateOrCreate(
                ['slug' => $payload['plugin']['slug']],
                $payload['plugin']
            );
        }

        // Import layout type if not exists
        if (!empty($payload['layout_type'])) {
            $layoutType = LayoutType::updateOrCreate(
                ['slug' => $payload['layout_type']['slug']],
                $payload['layout_type']
            );
        }

        // Import component type if not exists
        if (!empty($payload['component_type'])) {
            $componentType = ComponentType::updateOrCreate(
                ['slug' => $payload['component_type']['slug']],
                $payload['component_type']
            );
        }

        // Import scopes if not exists
        if (!empty($payload['scopes'])) {
            foreach ($payload['scopes'] as $scope) {
                $pageId = null;

                // Check if scope has a page relationship
                if (!empty($scope['page_id']) && !empty($scope['page'])) {
                    // Check if page exists by slug
                    $page = Page::where('slug', $scope['page']['slug'])->first();

                    if (!$page) {
                        // Create page if it doesn't exist
                        $page = Page::create([
                            'name' => $scope['page']['name'],
                            'slug' => $scope['page']['slug'],
                            'plugin_slug' => $scope['page']['plugin_slug'] ?? $payload['plugin']['slug'],
                            'background_color' => $scope['page']['background_color'] ?? null,
                            'border_color' => $scope['page']['border_color'] ?? null,
                            'border_radius' => $scope['page']['border_radius'] ?? null,
                            'component_limit' => $scope['page']['component_limit'] ?? null,
                            'persistent_footer_buttons' => $scope['page']['persistent_footer_buttons'] ?? null,
                        ]);
                    }

                    $pageId = $page->id;
                }

                // Create or update scope with page_id
                Scope::updateOrCreate(
                    ['slug' => $scope['slug'], 'plugin_slug' => $payload['plugin']['slug']],
                    [
                        'name' => $scope['name'],
                        'is_global' => $scope['is_global'],
                        'page_id' => $pageId,
                    ]
                );
            }
        }

        // Import class types if not exists
        if (!empty($payload['class_types'])) {
            $pluginSlug = $payload['plugin']['slug'] ?? null;

            foreach ($payload['class_types'] as $classType) {
                // Check if class type exists
                $existingClassType = ClassType::where('slug', $classType['slug'])->first();

                if ($existingClassType) {
                    // If exists, ensure plugin is in the JSON array
                    $plugins = $existingClassType->plugin ?? [];
                    if (!in_array($pluginSlug, $plugins)) {
                        $plugins[] = $pluginSlug;
                        $existingClassType->plugin = $plugins;
                        $existingClassType->save();
                    }
                } else {
                    // If new, create with plugin in JSON array
                    ClassType::create([
                        'name' => $classType['name'],
                        'slug' => $classType['slug'],
                        'plugin' => [$pluginSlug], // Create as JSON array
                        'is_active' => $classType['is_active'] ?? 1,
                        'created_at' => now()
                    ]);
                }
            }
        }
        return [
          'layout_type_id' => $layoutType?->id ?? null,
          'component_type_id' => $componentType?->id ?? null
        ];
    }

    /**
     * Import component
     */
    protected function importComponent(array $componentData, bool $overwrite)
    {
        // Check if component exists
        $existing = Component::where('slug', $componentData['slug'])->first();

        if ($existing && !$overwrite) {
            throw new \Exception('Component already exists. Enable overwrite to replace.');
        }

        // Prepare data for import
        $data = $this->prepareComponentData($componentData);

        return Component::updateOrCreate(
            ['slug' => $data['slug']],
            $data
        );
    }

    /**
     * Prepare component data for import
     */
    protected function prepareComponentData(array $data): array
    {
        // Ensure JSON fields are properly encoded
        $jsonFields = ['scope', 'items', 'dev_data', 'filters', 'pagination'];
        foreach ($jsonFields as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = json_encode($data[$field]);
            }
        }

        return $data;
    }

    /**
     * Import component style groups
     */

    protected function importComponentStyleGroups(int $componentId, array $styleGroups, array $componentProperties, string $pluginSlug): void
    {
//        dump($styleGroups);
        foreach ($styleGroups as $group) {
//            dump($group['style_group']['properties']);

            /*$filtered = array_filter($properties, function ($item) use ($group) {
                return $item['style_group_id'] == $group['style_group_id'];
            });

            $filteredProperties = array_values($filtered);*/
//            dump($filteredProperties);
            // Validate required data
            if (empty($group['style_group']['slug']) || empty($group['style_group']['name'])) {
                Log::warning("Skipping style group with missing required data", ['group' => $group]);
                continue;
            }

            // Find or create style group
            $styleGroup = StyleGroup::where('slug', $group['style_group']['slug'])->first();

            if (!$styleGroup) {
                // Create new style group
                $styleGroup = StyleGroup::create([
                    'name' => $group['style_group']['name'],
                    'slug' => $group['style_group']['slug'],
                    'plugin_slug' => [$pluginSlug],
                    'is_active' => $group['style_group']['is_active'] ?? 1,
                ]);
            } else {
                // Update existing style group's plugin array if needed
                $this->updateStyleGroupPlugins($styleGroup, $pluginSlug);
            }

            // Update existing style group's properties
//            $this->updateStyleGroupProperties($styleGroup, $filteredProperties);
            $this->updateStyleGroupProperties($styleGroup, $group['style_group']['properties']);

            // Create or update component style group relationship
            $componentStyleGroup = ComponentStyleGroup::updateOrCreate(
                [
                    'component_id' => $componentId,
                    'style_group_id' => $styleGroup->id,
                ],
                [
                    'is_checked' => $group['is_checked'] ?? false,
                ]
            );
//            importComponentProperties
            dump($componentStyleGroup);
        }
    }

    /**
     * Update the plugin_slug array for a style group
     */
    protected function updateStyleGroupPlugins(StyleGroup $styleGroup, string $pluginSlug): void
    {
        $currentPlugins = $styleGroup->plugin_slug ?? [];

        // Ensure it's an array
        if (!is_array($currentPlugins)) {
            $currentPlugins = json_decode($currentPlugins, true) ?? [];
        }

        // Add plugin if not exists
        if (!in_array($pluginSlug, $currentPlugins)) {
            $currentPlugins[] = $pluginSlug;
            $styleGroup->update(['plugin_slug' => $currentPlugins]);
        }
    }

    protected function updateStyleGroupProperties(StyleGroup $styleGroup, array $properties): void
    {
        if (count($properties) >0 ) {
            foreach ($properties as $property) {
                // Skip if required fields are missing
                if (empty($property['name']) || empty($property['input_type'])) {
                    Log::warning("Skipping property with missing required data", ['property' => $property]);
                    continue;
                }

                // Find or create the style property
                $styleProperty = StyleProperties::updateOrCreate(
                    ['name' => $property['name'],'input_type' => $property['input_type']],
                    [
                        'value' => $property['value'] ?? null,
                        'default_value' => $property['default_value'] ?? null,
                        'is_active' => $property['is_active'] ?? true,
                        'updated_at' => now()
                    ]
                );

                // Create or update the relationship between style group and style property
                StyleGroupProperties::updateOrCreate(
                    [
                        'style_group_id' => $styleGroup->id,
                        'style_property_id' => $styleProperty->id,
                    ],
                    []
                );
            }
        }
        /*// Get current property IDs for this style group to avoid duplicates
        $currentPropertyIds = StyleGroupProperty::where('style_group_id', $styleGroup->id)
            ->pluck('style_property_id')
            ->toArray();

        foreach ($filteredProperties as $property) {
            // Skip if required fields are missing
            if (empty($property['name']) || empty($property['input_type'])) {
                Log::warning("Skipping property with missing required data", ['property' => $property]);
                continue;
            }

            // Find or create the style property
            $styleProperty = StyleProperty::updateOrCreate(
                ['name' => $property['name']],
                [
                    'input_type' => $property['input_type'],
                    'value' => $property['value'] ?? null,
                    'default_value' => $property['default_value'] ?? null,
                    'is_active' => $property['is_active'] ?? true,
                ]
            );

            // Create or update the relationship between style group and style property
            StyleGroupProperty::updateOrCreate(
                [
                    'style_group_id' => $styleGroup->id,
                    'style_property_id' => $styleProperty->id,
                ],
                []
            );

            // Remove from current IDs list to track which ones we've processed
            if (($key = array_search($styleProperty->id, $currentPropertyIds)) !== false) {
                unset($currentPropertyIds[$key]);
            }
        }

        // Remove any properties that were in the style group but not in the import
        if (!empty($currentPropertyIds)) {
            StyleGroupProperty::where('style_group_id', $styleGroup->id)
                ->whereIn('style_property_id', $currentPropertyIds)
                ->delete();
        }*/
    }




    /**
     * Import component properties
     */
    protected function importComponentProperties(int $componentId, array $properties): void
    {
        foreach ($properties as $property) {
            // Find style group by component_id and style_group_id
            $componentStyleGroup = ComponentStyleGroup::where('component_id', $componentId)
                ->where('style_group_id', $property['style_group_id'])
                ->first();
            dump($componentStyleGroup,$property);


            if (!$componentStyleGroup) {
                Log::warning("Component style group not found for property: {$property['name']}");
                continue;
            }

            /*array:11 [▼ // app/Services/ComponentImportService.php:374
  "id" => 73274
  "component_id" => 367
  "style_group_id" => 1
  "name" => "padding_x"
  "input_type" => "number"
  "value" => "40000"
  "default_value" => null
  "is_active" => 1
  "deleted_at" => null
  "created_at" => "2025-12-01T10:34:55.000000Z"
  "updated_at" => "2025-12-03T04:50:28.000000Z"
]*/

            /*ComponentStyleGroupProperties::updateOrCreate(
                [
                    'component_id' => $componentId,
                    'style_group_id' => $property['style_group_id'],
                    'name' => $property['name'],
                ],
                [
                    'input_type' => $property['input_type'],
                    'value' => $property['value'],
                    'default_value' => $property['default_value'],
                ]
            );*/
        }
    }

    /**
     * Import component scopes
     */
    protected function importComponentScopes(Component $component, array $scopes): void
    {
        $scopeSlugs = array_column($scopes, 'slug');
        $component->scope = json_encode($scopeSlugs);
        $component->save();
    }
}
