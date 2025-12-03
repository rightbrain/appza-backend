<?php
namespace App\Services;

use App\Models\ClassType;
use App\Models\Component;
use App\Models\ComponentImportLog;
use App\Models\ComponentStyleGroup;
use App\Models\ComponentStyleGroupProperties;
use App\Models\ComponentType;
use App\Models\LayoutType;
use App\Models\Page;
use App\Models\Scope;
use App\Models\StyleGroup;
use App\Models\StyleGroupProperties;
use App\Models\StyleProperties;
use App\Models\SupportsPlugin;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ComponentImportService_bk
{
    protected $componentName;
    protected $componentSlug;
    protected $source;

    /**
     * Import component JSON payload
     * @param array $payload
     * @param bool $overwrite
     * @return array|null
     */
    public function import(array $payload, bool $overwrite = false): ?array
    {
        $this->componentName = $payload['component']['name'] ?? 'Unknown';
        $this->componentSlug = $payload['component']['slug'] ?? 'unknown';
        $this->source = $payload['meta']['source'] ?? 'unknown';

        // Create initial log entry
        $this->log('Import started', true, [
            'overwrite' => $overwrite,
            'file_size' => strlen(json_encode($payload))
        ]);

        DB::beginTransaction();
        try {
            $pluginSlug = $payload['plugin']['slug'];

            // Import reference data first
            $this->log('Importing reference data', true);
            $referenceData = $this->importReferenceData($payload);

            $payload['component']['layout_type_id'] = $referenceData['layout_type_id'] ?? null;
            $payload['component']['component_type_id'] = $referenceData['component_type_id'] ?? null;

            // Import component
            $this->log('Importing component', true);
            $component = $this->importComponent($payload['component'], $overwrite);

            // Import related data
            $this->log('Importing style groups', true, ['count' => count($payload['style_groups'])]);
            $this->importComponentStyleGroups($component->id, $payload['style_groups'], $payload['properties'], $pluginSlug);

            $this->log('Importing component properties', true, ['count' => count($payload['properties'])]);
            $this->importComponentProperties($component->id, $payload['properties']);

            $this->log('Importing component scopes', true, ['count' => count($payload['scopes'])]);
            $this->importComponentScopes($component, $payload['scopes']);

            DB::commit();

            $this->log('Import completed successfully', true);

            Log::info('Component imported successfully', [
                'component_id' => $component->id,
                'source' => $this->source,
                'overwrite' => $overwrite
            ]);

            return [
                'success' => true,
                'message' => 'Component imported successfully',
                'component_id' => $component->id
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            $this->log('Import failed: ' . $e->getMessage(), false, [
                'exception' => $e->getTraceAsString()
            ]);

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
     * Log import step
     */
    protected function log(string $message, bool $success, $data = null): void
    {
        $logData = [
            'component_name' => $this->componentName,
            'success' => $success,
            'message' => $message,
            'source' => $this->source,
        ];

        if ($data) {
            $logData['message'] .= ' - Data: ' . json_encode($data);
        }

        ComponentImportLog::create($logData);
    }

    /**
     * Import reference data (plugins, layout types, etc.)
     */
    protected function importReferenceData(array $payload): array
    {
        $layoutType = null;
        $componentType = null;

        // Import plugin if not exists
        if (!empty($payload['plugin'])) {
            $this->log('Importing plugin: ' . $payload['plugin']['slug'], true);
            SupportsPlugin::updateOrCreate(
                ['slug' => $payload['plugin']['slug']],
                $payload['plugin']
            );
        }

        // Import layout type if not exists
        if (!empty($payload['layout_type'])) {
            $this->log('Importing layout type: ' . $payload['layout_type']['slug'], true);
            $layoutType = LayoutType::updateOrCreate(
                ['slug' => $payload['layout_type']['slug']],
                $payload['layout_type']
            );
        }

        // Import component type if not exists
        if (!empty($payload['component_type'])) {
            $this->log('Importing component type: ' . $payload['component_type']['slug'], true);
            $componentType = ComponentType::updateOrCreate(
                ['slug' => $payload['component_type']['slug']],
                $payload['component_type']
            );
        }

        // Import scopes if not exists
        if (!empty($payload['scopes'])) {
            $this->log('Importing scopes', true, ['count' => count($payload['scopes'])]);
            foreach ($payload['scopes'] as $scope) {
                $pageId = null;

                // Check if scope has a page relationship
                if (!empty($scope['page_id']) && !empty($scope['page'])) {
                    // Check if page exists by slug
                    $page = Page::where('slug', $scope['page']['slug'])->first();

                    if (!$page) {
                        $this->log('Creating page: ' . $scope['page']['slug'], true);
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
            $this->log('Importing class types', true, ['count' => count($payload['class_types'])]);
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
     * @throws \Exception
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
     * @throws ConnectionException
     */
    protected function prepareComponentData(array $data): ?array
    {
        // Ensure JSON fields are properly encoded
        $jsonFields = ['scope', 'items', 'dev_data', 'filters', 'pagination'];
        foreach ($jsonFields as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = json_encode($data[$field]);
            }
        }

        // Handle image upload if component has an image
        if (!empty($data['image'])) {
            $fileName = $data['image'];
            $imageUrl = $fileName;

            $this->log("Downloading image: $imageUrl", true);

            // Download the image
            $response = Http::timeout(30)->get($imageUrl);

            if (!$response->successful()) {
                $this->log('Failed to download image', false, [
                    'url' => $imageUrl,
                    'status' => $response->status()
                ]);
                return null;
            }

            // Get raw image bytes
            $imageContent = $response->body();

            // Determine extension (fallback to jpg)
            $extension = pathinfo($fileName, PATHINFO_EXTENSION) ?: 'jpg';

            // Generate new filename to upload
            $newFileName = 'component-image/' . uniqid('img_') . '.' . $extension;

            // Upload to R2
            Storage::disk('r2')->put($newFileName, $imageContent);

            // Save new image path back into component payload
            if ($newFileName) {
                $this->log('Image uploaded to R2 successfully', true, ['url' => $newFileName]);
                $data['image'] = $newFileName;
            } else {
                $this->log('Failed to upload image to R2', false);
            }
        }

        return $data;
    }

    /**
     * Import component style groups
     */
    protected function importComponentStyleGroups(int $componentId, array $styleGroups, array $componentProperties, string $pluginSlug): void
    {
        foreach ($styleGroups as $index => $group) {
            // Validate required data
            if (empty($group['style_group']['slug']) || empty($group['style_group']['name'])) {
                $this->log("Skipping style group with missing required data at index $index", false, [
                    'group' => $group
                ]);
                continue;
            }

            // Find or create style group
            $styleGroup = StyleGroup::where('slug', $group['style_group']['slug'])->first();

            if (!$styleGroup) {
                $this->log("Creating new style group: " . $group['style_group']['slug'], true);
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
            $this->updateStyleGroupProperties($styleGroup, $group['style_group']['properties']);

            // Create or update component style group relationship
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
        if (count($properties) > 0) {
            foreach ($properties as $property) {
                // Skip if required fields are missing
                if (empty($property['name']) || empty($property['input_type'])) {
                    $this->log("Skipping property with missing required data", false, [
                        'style_group_id' => $styleGroup->id,
                        'property' => $property
                    ]);
                    continue;
                }

                // Find or create the style property
                $styleProperty = StyleProperties::updateOrCreate(
                    ['name' => $property['name'], 'input_type' => $property['input_type']],
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
    }

    /**
     * Import component properties
     */
    protected function importComponentProperties(int $componentId, array $properties): void
    {
        $processedCount = 0;
        $skippedCount = 0;

        foreach ($properties as $property) {
            $findStyleGroup = StyleGroup::where('slug', $property['style_group']['slug'])->first();
            if (!$findStyleGroup) {
                $this->log("Component style group not found: " . $property['style_group']['slug'], false);
                $skippedCount++;
                continue;
            }

            // Find style group by component_id and style_group_id
            $componentStyleGroup = ComponentStyleGroup::where('component_id', $componentId)
                ->where('style_group_id', $findStyleGroup->id)
                ->first();

            if (!$componentStyleGroup) {
                $this->log("Component style group not found for property: " . $property['name'], false);
                $skippedCount++;
                continue;
            }

            ComponentStyleGroupProperties::updateOrCreate(
                [
                    'component_id' => $componentId,
                    'style_group_id' => $findStyleGroup->id,
                    'name' => $property['name'],
                    'input_type' => $property['input_type'],
                ],
                [
                    'value' => $property['value'],
                    'default_value' => $property['default_value'],
                    'is_active' => $property['is_active'],
                ]
            );

            $processedCount++;
        }

        $this->log("Properties import summary", true, [
            'processed' => $processedCount,
            'skipped' => $skippedCount
        ]);
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
