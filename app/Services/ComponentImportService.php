<?php
namespace App\Services;

use App\Models\Component;
use App\Models\ComponentStyleGroup;
use App\Models\ComponentStyleGroupProperties;
use App\Models\StyleGroup;
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
            // Import reference data first
            $this->importReferenceData($payload);

            // Import component
            $component = $this->importComponent($payload['component'], $overwrite);

            // Import related data
            $this->importComponentStyleGroups($component->id, $payload['style_groups']);
            $this->importComponentProperties($component->id, $payload['properties']);
            $this->importComponentScopes($component, $payload['scopes']);

            DB::commit();

            Log::info('Component imported successfully', [
                'component_id' => $component->id,
                'source' => $payload['meta']['source'] ?? 'unknown',
                'overwrite' => $overwrite
            ]);

            return [
                'success' => true,
                'message' => 'Component imported successfully',
                'component_id' => $component->id
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
    protected function importReferenceData(array $payload): void
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
            LayoutType::updateOrCreate(
                ['id' => $payload['layout_type']['id']],
                $payload['layout_type']
            );
        }

        // Import component type if not exists
        if (!empty($payload['component_type'])) {
            ComponentType::updateOrCreate(
                ['id' => $payload['component_type']['id']],
                $payload['component_type']
            );
        }

        // Import scopes if not exists
        if (!empty($payload['scopes'])) {
            foreach ($payload['scopes'] as $scope) {
                Scope::updateOrCreate(
                    ['slug' => $scope['slug']],
                    $scope
                );
            }
        }

        // Import class types if not exists
        if (!empty($payload['class_types'])) {
            foreach ($payload['class_types'] as $classType) {
                ClassType::updateOrCreate(
                    ['slug' => $classType['slug']],
                    $classType
                );
            }
        }
    }

    /**
     * Import component
     */
    protected function importComponent(array $componentData, bool $overwrite): Component
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
    protected function importComponentStyleGroups(int $componentId, array $styleGroups): void
    {
        foreach ($styleGroups as $group) {
            // Find style group by slug
            $styleGroup = StyleGroup::where('slug', $group['style_group']['slug'])->first();

            if (!$styleGroup) {
                Log::warning("Style group not found: {$group['style_group']['slug']}");
                continue;
            }

            ComponentStyleGroup::updateOrCreate(
                [
                    'component_id' => $componentId,
                    'style_group_id' => $styleGroup->id,
                ],
                [
                    'is_checked' => $group['is_checked'] ?? false,
                ]
            );
        }
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

            if (!$componentStyleGroup) {
                Log::warning("Component style group not found for property: {$property['name']}");
                continue;
            }

            ComponentStyleGroupProperties::updateOrCreate(
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
            );
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
