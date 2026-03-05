<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ThemeResource;
use App\Models\ComponentStyleGroup;
use App\Models\Currency;
use App\Models\Lead;
use App\Models\SupportsPlugin;
use App\Models\Theme;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use stdClass;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ThemeController extends Controller
{
    protected $authorization;

    protected $domain;

    protected $pluginName;

    public function __construct(Request $request)
    {
        $data = Lead::checkAuthorization($request);
        $this->authorization = $data['auth_type'] ?? false;
        $this->domain = $data['domain'] ?? '';
        $this->pluginName = $data['plugin_name'] ?? '';
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $isHashAuthorization = config('app.is_hash_authorization');
        // Check if the user is authorized
        if ($isHashAuthorization && ! $this->authorization) {
            return response()->json([
                'status' => Response::HTTP_UNAUTHORIZED,
                'url' => $request->getUri(),
                'method' => $request->getMethod(),
                'message' => 'Unauthorized',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $plugin_slug = $request->query('plugin_slug');

        if (! $plugin_slug || ! is_array($plugin_slug)) {
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'url' => $request->getUri(),
                'method' => $request->getMethod(),
                'message' => 'Plugin slug must be an array and cannot be empty',
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            // Fetch active themes with their photo gallery
            $themes = Theme::active()
                ->whereNull('deleted_at')
                ->whereIn('plugin_slug', (array) $plugin_slug)
                ->with('photoGallery')
                ->orderBy('sort_order')
                ->get();

            // Check if themes exist
            if ($themes->isEmpty()) {
                return response()->json([
                    'status' => Response::HTTP_OK,
                    'url' => $request->getUri(),
                    'method' => $request->getMethod(),
                    'message' => 'Data Not Found',
                    'data' => [],
                ], Response::HTTP_OK);
            }

            // Use the ThemeResource to transform the data
            return ThemeResource::collection($themes)
                ->additional([
                    'status' => Response::HTTP_OK,
                    'url' => $request->getUri(),
                    'method' => $request->getMethod(),
                    'message' => 'Data Found',
                ])
                ->response()
                ->setEncodingOptions(JSON_UNESCAPED_SLASHES);

        } catch (Exception $ex) {
            // Handle server error
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $ex->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getTheme(Request $request)
    {
        $isHashAuthorization = config('app.is_hash_authorization');
        if ($isHashAuthorization && ! $this->authorization) {
            $response = new JsonResponse([
                'status' => Response::HTTP_UNAUTHORIZED,
                'url' => $request->getUri(),
                'method' => $request->getMethod(),
                'message' => 'Unauthorized',
            ], Response::HTTP_UNAUTHORIZED);
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }
        try {
            $requiredParams = [
                'slug' => 'Theme slug is required',
                'plugin_slug' => 'Plugin slug is required',
            ];

            foreach ($requiredParams as $param => $message) {
                if (! $request->query($param)) {
                    return response()->json([
                        'status' => Response::HTTP_BAD_REQUEST,
                        'url' => $request->getUri(),
                        'method' => $request->getMethod(),
                        'message' => $message,
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            $themeSlug = $request->query('slug');
            $pluginSlug = $request->query('plugin_slug');

            $theme = Theme::where('slug', $themeSlug)->where('plugin_slug', $pluginSlug)->first();

            if (! $theme) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'url' => $request->getUri(),
                    'method' => $request->getMethod(),
                    'message' => 'Theme not found',
                ], Response::HTTP_NOT_FOUND);
            }

            $themeID = $theme->id;

            // Fetch theme data with relationships and necessary conditions
            $data = Theme::select([
                'appfiy_theme.id',
                'appfiy_theme.name',
                'appfiy_theme.slug',
                'appfiy_theme.background_color',
                'appfiy_theme.font_family',
                'appfiy_theme.text_color',
                'appfiy_theme.font_size',
                'appfiy_theme.transparent',
                'appfiy_theme.dashboard_page',
                'appfiy_theme.login_page',
                'appfiy_theme.login_modal',
                'appfiy_theme.image',
                'appfiy_theme.default_page',
            ])
                ->with([
                    'globalConfig' => function ($query) use ($pluginSlug) {
                        $query->select([
                            'appfiy_theme_config.theme_id',
                            'appfiy_theme_config.mode',
                            'appfiy_theme_config.global_config_id',
                        ])
                            ->join('appfiy_global_config', 'appfiy_global_config.id', '=', 'appfiy_theme_config.global_config_id')
                            ->where('appfiy_global_config.plugin_slug', $pluginSlug)->where('appfiy_theme_config.is_active', 1);
                    },
                    'page' => function ($query) {
                        $query->select([
                            'appfiy_theme_page.id',
                            'appfiy_theme_page.theme_id',
                            'appfiy_theme_page.page_id',
                            'appfiy_theme_page.persistent_footer_buttons',
                            'appfiy_theme_page.background_color',
                            'appfiy_theme_page.border_color',
                            'appfiy_theme_page.border_radius',
                            'appfiy_theme_page.screen_status',
                            'appfiy_theme_page.static_screen_image',
                            'appfiy_theme_page.static_screen_message',
                            'appfiy_page.name',
                            'appfiy_page.component_limit',
                            'appfiy_page.slug',
                        ])->join('appfiy_page', 'appfiy_page.id', '=', 'appfiy_theme_page.page_id')
                            ->orderby('appfiy_theme_page.sort_order');
                    },
                ])
                ->where('appfiy_theme.id', $themeID)
                ->where('appfiy_theme.is_active', 1)
                ->first();

            // Check if the theme data is available
            if (! $data) {
                return response()->json(['error' => 'Theme not found or inactive'], 404);
            }

            // Construct theme data array with defaults and conditionals
            $themeData = [
                'theme_name' => $data->name ?? 'Default Theme Name',
                'theme_slug' => $data->slug,
                'plugin_slug' => $pluginSlug,
                'default_active_page_slug' => $data->default_page,
                'background_color' => $data->background_color ?? '#FFFFFF',
                'font_family' => $data->font_family ?? 'Arial',
                'text_color' => $data->text_color ?? '#000000',
                'font_size' => $data->font_size ?? 14,
                'is_transparent_background' => $data->transparent === 'True',
                'image_url' => $data->image ? rtrim(config('app.image_public_path'), '/').'/'.$data->image : null,
                'dashboard_page' => $data->dashboard_page ?? null,
                'login_page' => $data->login_page ?? null,
                'login_modal' => $data->login_modal ?? null,
                'is_show_scanner' => config('app.is_show_scanner', false),
                'theme_status' => 'active',
            ];

            // === Pre-fetch all data to avoid N+1 queries ===

            // Cache plugin prefix (called repeatedly with same slug)
            $pluginPrefix = SupportsPlugin::getPluginPrefix($pluginSlug);

            // 1. Collect global config IDs that have a mode
            $activeGlobalConfigs = collect($data->globalConfig)->filter(fn ($c) => ! empty($c->mode));
            $globalConfigIds = $activeGlobalConfigs->pluck('global_config_id')->unique()->values();

            // 2. Batch fetch global config rows and currencies
            $globalConfigRowMap = collect();
            $currencyMap = collect();

            if ($globalConfigIds->isNotEmpty()) {
                $globalConfigRowMap = DB::table('appfiy_global_config')->select([
                    'text_properties_color', 'id', 'mode', 'name', 'slug', 'selected_color',
                    'unselected_color', 'background_color', 'layout', 'icon_theme_size',
                    'icon_theme_color', 'shadow', 'icon', 'automatically_imply_leading',
                    'center_title', 'flexible_space', 'bottom', 'shape_type',
                    'shape_border_radius', 'toolbar_opacity', 'actions_icon_theme_color',
                    'is_transparent_background', 'actions_icon_theme_size', 'title_spacing',
                    'image', 'icon_properties_size', 'icon_properties_color',
                    'icon_properties_shape_radius', 'icon_properties_background_color',
                    'margin_x', 'margin_y', 'padding_x', 'padding_y',
                    'image_properties_height', 'image_properties_width',
                    'image_properties_shape_radius', 'image_properties_padding_x',
                    'image_properties_padding_y', 'image_properties_margin_x',
                    'image_properties_margin_y', 'icon_properties_padding_x',
                    'icon_properties_padding_y', 'icon_properties_margin_x',
                    'icon_properties_margin_y', 'float', 'currency_id',
                ])->whereIn('id', $globalConfigIds)->get()->keyBy('id');

                $currencyIds = $globalConfigRowMap->pluck('currency_id')->unique()->filter();
                if ($currencyIds->isNotEmpty()) {
                    $currencyMap = Currency::whereIn('id', $currencyIds)->get()->keyBy('id');
                }
            }

            // 3. Batch fetch all global config components
            $globalComponentsByConfig = collect();
            $gcComponentIds = collect();

            if ($globalConfigIds->isNotEmpty()) {
                $globalComponentsRaw = DB::table('appfiy_global_config_component')
                    ->join('appfiy_component', 'appfiy_component.id', '=', 'appfiy_global_config_component.component_id')
                    ->join('appfiy_layout_type', 'appfiy_layout_type.id', '=', 'appfiy_component.layout_type_id')
                    ->join('appfiy_component_type', 'appfiy_component_type.id', '=', 'appfiy_component.component_type_id')
                    ->select([
                        'appfiy_global_config_component.global_config_id',
                        'appfiy_global_config_component.component_id',
                        'appfiy_global_config_component.component_position',
                        'appfiy_component.id',
                        'appfiy_component.name',
                        'appfiy_component.slug',
                        'appfiy_component.label',
                        'appfiy_component.layout_type_id',
                        'appfiy_layout_type.slug as layout_type',
                        'appfiy_component.icon_code',
                        'appfiy_component.event',
                        'appfiy_component.scope',
                        'appfiy_component.is_active',
                        'appfiy_component.class_type',
                        'appfiy_component.product_type',
                        'appfiy_component.web_icon',
                        'appfiy_component.image',
                        'appfiy_component.is_multiple',
                        'appfiy_component.selected_design',
                        'appfiy_component.details_page',
                        'appfiy_component.transparent',
                        'appfiy_component.plugin_slug',
                        'appfiy_component_type.name as group_name',
                    ])
                    ->whereIn('appfiy_global_config_component.global_config_id', $globalConfigIds)
                    ->where('appfiy_component.plugin_slug', $pluginSlug)
                    ->whereNull('appfiy_component.deleted_at')
                    ->get();

                $globalComponentsByConfig = $globalComponentsRaw->groupBy('global_config_id');
                $gcComponentIds = $globalComponentsRaw->pluck('id')->unique();
            }

            // 4. Batch fetch all page components
            $pageIds = collect($data->page)->pluck('id');
            $pageComponentsByPage = collect();
            $pgComponentIds = collect();

            if ($pageIds->isNotEmpty()) {
                $pageComponentsRaw = DB::table('appfiy_theme_component')
                    ->select([
                        'appfiy_theme_component.theme_page_id',
                        'appfiy_theme_component.id as theme_component_id',
                        'appfiy_component.id',
                        'appfiy_component.name',
                        'appfiy_component.web_icon',
                        'appfiy_component.slug',
                        'appfiy_component.label',
                        'appfiy_component.layout_type_id',
                        'appfiy_layout_type.slug as layout_type',
                        'appfiy_component.icon_code',
                        'appfiy_component.event',
                        'appfiy_component.scope',
                        'appfiy_component.class_type',
                        'appfiy_component.is_active',
                        'appfiy_component.product_type',
                        'appfiy_component.selected_design',
                        'appfiy_component.details_page',
                        'appfiy_component.transparent',
                        'appfiy_theme_component.display_name',
                        'appfiy_theme_component.clone_component',
                        'appfiy_theme_component.selected_id',
                        'appfiy_theme_component.sort_ordering',
                        'appfiy_component.image',
                        'image_url',
                        'appfiy_component.is_multiple',
                        'appfiy_component.plugin_slug',
                        'appfiy_component_type.name as group_name',
                        'appfiy_component.items',
                        'appfiy_component.dev_data',
                        'appfiy_component.filters',
                        'appfiy_component.pagination',
                        'appfiy_component.show_no_data_view',
                    ])
                    ->join('appfiy_component', 'appfiy_component.id', '=', 'appfiy_theme_component.component_id')
                    ->join('appfiy_component_type', 'appfiy_component_type.id', '=', 'appfiy_component.component_type_id')
                    ->join('appfiy_layout_type', 'appfiy_layout_type.id', '=', 'appfiy_component.layout_type_id')
                    ->where('appfiy_theme_component.theme_id', $themeID)
                    ->whereIn('appfiy_component.plugin_slug', [$pluginSlug, 'wordpress'])
                    ->whereIn('appfiy_theme_component.theme_page_id', $pageIds)
                    ->where('appfiy_theme_component.selected_id', 1)
                    ->orderBy('sort_ordering')
                    ->get();

                $pageComponentsByPage = $pageComponentsRaw->groupBy('theme_page_id');
                $pgComponentIds = $pageComponentsRaw->pluck('id')->unique();
            }

            // 5. Batch fetch all style data for all component IDs (both sections)
            $allComponentIds = $gcComponentIds->merge($pgComponentIds)->unique();

            $styleGroupsByComponent = collect();
            $styleGroupNameMap = collect();
            $styleGroupLabelMap = collect();
            $gcStylePropsMap = collect();
            $pgStylePropsMap = collect();

            if ($allComponentIds->isNotEmpty()) {
                // Checked style groups for all components
                $allCheckedStyleGroups = DB::table('appfiy_component_style_group')
                    ->where('is_checked', 1)
                    ->whereIn('component_id', $allComponentIds)
                    ->get();

                $styleGroupsByComponent = $allCheckedStyleGroups->groupBy('component_id');
                $allCheckedStyleGroupIds = $allCheckedStyleGroups->pluck('style_group_id')->unique();

                if ($allCheckedStyleGroupIds->isNotEmpty()) {
                    // Style group names/slugs
                    $styleGroupNameMap = DB::table('appfiy_style_group')
                        ->whereIn('id', $allCheckedStyleGroupIds)
                        ->get()
                        ->keyBy('id');

                    // Style group labels
                    $styleGroupLabelMap = ComponentStyleGroup::whereIn('component_id', $allComponentIds)
                        ->whereIn('style_group_id', $allCheckedStyleGroupIds)
                        ->where('is_checked', 1)
                        ->get()
                        ->keyBy(fn ($item) => $item->component_id.'_'.$item->style_group_id);
                }

                // Global config component style properties (no is_active/deleted_at filter)
                if ($gcComponentIds->isNotEmpty() && $allCheckedStyleGroupIds->isNotEmpty()) {
                    $gcProps = DB::table('appfiy_component_style_group_properties')
                        ->select(['name', 'input_type', 'value', 'default_value', 'component_id', 'style_group_id'])
                        ->whereIn('component_id', $gcComponentIds)
                        ->whereIn('style_group_id', $allCheckedStyleGroupIds)
                        ->get();
                    $gcStylePropsMap = $gcProps->groupBy(fn ($item) => $item->component_id.'_'.$item->style_group_id);
                }

                // Page component style properties (with is_active=1, deleted_at IS NULL, joined with style_group for slug)
                if ($pgComponentIds->isNotEmpty() && $allCheckedStyleGroupIds->isNotEmpty()) {
                    $pgProps = DB::table('appfiy_component_style_group_properties')
                        ->join('appfiy_style_group', 'appfiy_style_group.id', '=', 'appfiy_component_style_group_properties.style_group_id')
                        ->select([
                            'appfiy_component_style_group_properties.id',
                            'appfiy_component_style_group_properties.name',
                            'appfiy_component_style_group_properties.input_type',
                            'appfiy_component_style_group_properties.value',
                            'appfiy_style_group.id as style_group_id',
                            'appfiy_style_group.slug',
                            'appfiy_component_style_group_properties.component_id',
                        ])
                        ->whereIn('appfiy_component_style_group_properties.component_id', $pgComponentIds)
                        ->whereIn('appfiy_component_style_group_properties.style_group_id', $allCheckedStyleGroupIds)
                        ->where('appfiy_component_style_group_properties.is_active', 1)
                        ->whereNull('appfiy_component_style_group_properties.deleted_at')
                        ->get();
                    $pgStylePropsMap = $pgProps->groupBy('component_id');
                }
            }

            // === Process global config using pre-fetched data ===

            $globalConfigData = [];

            if (count($data['globalConfig']) > 0) {

                foreach ($data['globalConfig'] as $config) {
                    if (! empty($config['mode'] ?? null)) {

                        $configData = $globalConfigRowMap->get($config['global_config_id']);

                        $getCurrency = $currencyMap->get($configData->currency_id);

                        $dataArray = [];
                        if (isset($configData) && ! empty($configData)) {
                            $finalCon = [
                                'mode' => $configData->mode,
                                'name' => $configData->name,
                                'slug' => $configData->slug,
                                'image_url' => $configData->image ? config('app.image_public_path').$configData->image : null,
                                'is_active' => 'yes',
                                'properties' => [
                                    'selected_color' => $configData->selected_color,
                                    'unselected_color' => $configData->unselected_color,
                                    'background_color' => $configData->background_color,
                                    'layout' => $configData->layout,
                                    'icon_theme_size' => $configData->icon_theme_size,
                                    'icon_theme_color' => $configData->icon_theme_color,
                                    'shadow' => $configData->shadow,
                                    'icon' => $configData->icon,
                                    'automatically_imply_leading' => $configData->automatically_imply_leading,
                                    'center_title' => $configData->center_title,
                                    'flexible_space' => $configData->flexible_space,
                                    'bottom' => $configData->bottom,
                                    'shape_type' => $configData->shape_type,
                                    'toolbar_opacity' => $configData->toolbar_opacity,
                                    'actions_icon_theme_color' => $configData->actions_icon_theme_color,
                                    'actions_icon_theme_size' => $configData->actions_icon_theme_size,
                                    'title_spacing' => $configData->title_spacing,
                                    'general_properties' => [
                                        'margin_x' => $configData->margin_x,
                                        'margin_y' => $configData->margin_y,
                                        'padding_x' => $configData->padding_x,
                                        'padding_y' => $configData->padding_y,
                                        'shadow' => $configData->shadow,
                                        'border_color' => $configData->background_color,
                                        'border_width' => '0',
                                        'shape_radius' => $configData->shape_border_radius,
                                        'background_color' => $configData->background_color,
                                        'float' => $configData->float == 1,
                                        'country_name' => $getCurrency->country,
                                        'currency_name' => $getCurrency->currency,
                                        'currency_code' => $getCurrency->code,
                                        'currency_symbol' => $getCurrency->symbol,
                                    ],
                                    'text_properties' => [
                                        'color' => $configData->text_properties_color,
                                        'font_style' => 'normal',
                                        'font_weight' => '700',
                                        'text' => null,
                                        'text_decoration' => 'none',
                                    ],
                                    'icon_properties' => [
                                        'size' => number_format($configData->icon_properties_size, 1),
                                        'color' => $configData->icon_properties_color,
                                        'weight' => '2.0',
                                        'shape_radius' => $configData->icon_properties_shape_radius,
                                        'background_color' => $configData->icon_properties_background_color,
                                        'is_transparent_background' => $configData->is_transparent_background == 1,
                                        'selected_color' => $configData->selected_color,
                                        'unselected_color' => $configData->unselected_color,
                                        'padding_x' => number_format($configData->icon_properties_padding_x, 1),
                                        'padding_y' => number_format($configData->icon_properties_padding_y, 1),
                                        'margin_x' => number_format($configData->icon_properties_margin_x, 1),
                                        'margin_y' => number_format($configData->icon_properties_margin_y, 1),
                                    ],
                                    'image_properties' => [
                                        'height' => number_format($configData->image_properties_height, 1),
                                        'width' => number_format($configData->image_properties_width, 1),
                                        'shape_radius' => number_format($configData->image_properties_shape_radius, 1),
                                        'padding_x' => number_format($configData->image_properties_padding_x, 1),
                                        'padding_y' => number_format($configData->image_properties_padding_y, 1),
                                        'margin_x' => number_format($configData->image_properties_margin_x, 1),
                                        'margin_y' => number_format($configData->image_properties_margin_y, 1),
                                    ],
                                ],
                            ];

                            // Reuse the properties for customization and styles
                            $finalCon['customize_properties'] = $finalCon['properties'];
                            $finalCon['styles'] = $finalCon['properties'];
                            $finalCon['customize_styles'] = $finalCon['properties'];

                            // Get pre-fetched components for this config
                            $getComponents = $globalComponentsByConfig->get($configData->id, collect());

                            // Sort based on mode
                            if ($configData->mode === 'navbar') {
                                $getComponents = $getComponents->sortBy('component_position')->values();
                            }
                            if ($configData->mode === 'drawer') {
                                $getComponents = $getComponents->sortBy(fn ($c) => (int) $c->component_position)->values();
                            }

                            $componentWithStyle = [];
                            foreach ($getComponents as $component) {
                                $component = (array) $component;

                                // Build styles from pre-fetched data
                                $componentStyleGroups = $styleGroupsByComponent->get($component['id'], collect());
                                $finalNewStyle = [];

                                foreach ($componentStyleGroups as $group) {
                                    $groupName = $styleGroupNameMap->get($group->style_group_id);

                                    $propsKey = $component['id'].'_'.$group->style_group_id;
                                    $getComponentsStyle = $gcStylePropsMap->get($propsKey, collect());

                                    $labelKey = $component['id'].'_'.$group->style_group_id;
                                    $labelRecord = $styleGroupLabelMap->get($labelKey);

                                    $newStyle = [];
                                    foreach ($getComponentsStyle as $sty) {
                                        $newStyle['group_label'] = $labelRecord->style_group_label ?? null;
                                        $newStyle[$sty->name] = $sty->value;
                                    }

                                    $finalNewStyle[$groupName->slug] = $newStyle;
                                }

                                // Define common properties in an array
                                $commonProperties = [
                                    'label' => $component['label'],
                                    'group_name' => $component['group_name'],
                                    'layout_type' => $component['layout_type'],
                                    'icon_code' => $component['icon_code'],
                                    'event' => $component['event'],
                                    'scope' => json_decode($component['scope']),
                                    'class_type' => $component['product_type'] ? $pluginPrefix.$component['product_type'] : null,
                                    'web_icon' => $component['web_icon'],
                                    'is_multiple' => $component['is_multiple'],
                                    'selected_design' => $component['selected_design'],
                                    'detailsPage' => $component['details_page'],
                                    'is_transparent_background' => $component['transparent'] === 'True',
                                ];

                                // Assign the same properties to both 'properties' and 'customize_properties'
                                $componentArrange = [
                                    'component_position' => $component['component_position'],
                                    'name' => $component['name'],
                                    'slug' => $component['slug'],
                                    'support_extension' => $component['plugin_slug'],
                                    'corresponding_page_slug' => $component['slug'],
                                    'image_url' => config('app.image_public_path').$component['image'],
                                    'is_active' => 'true',
                                    'properties' => $commonProperties,
                                    'customize_properties' => $commonProperties, // Reuse common properties
                                    'styles' => $finalNewStyle ?: new stdClass,
                                    'customize_styles' => $finalNewStyle ?: new stdClass,
                                ];

                                // Special handling for 'Category' or 'CategoryProduct' product type
                                if ($component['product_type'] === 'Category') {
                                    $componentArrange['properties']['category_slugs'] = [];
                                    $componentArrange['customize_properties']['category_slugs'] = [];
                                    $componentArrange['properties']['all_category'] = true;
                                    $componentArrange['customize_properties']['all_category'] = true;
                                } elseif ($component['product_type'] === 'CategoryProduct') {
                                    $componentArrange['properties']['categories'] = [];
                                    $componentArrange['customize_properties']['categories'] = [];
                                }

                                $componentWithStyle[] = $componentArrange;
                            }

                            $finalCon['components'] = $componentWithStyle;
                            $dataArray[] = $finalCon;
                            $globalConfigData[] = $finalCon;
                        }
                    }
                    $themeData['global_config'] = $globalConfigData;
                }
            }

            // === Process pages using pre-fetched data ===

            $pages = [];
            if (count($data['page']) > 0) {
                foreach ($data['page'] as $page) {
                    $getPagesComponents = $pageComponentsByPage->get($page['id'], collect())->toArray();

                    // Fetch the components and build them
                    $final = [];
                    if (count($getPagesComponents) > 0) {
                        foreach ($getPagesComponents as $pagesComponent) {
                            $pagesComponent = (array) $pagesComponent;

                            // Build styles from pre-fetched data
                            $componentCheckedGroups = $styleGroupsByComponent->get($pagesComponent['id'], collect());
                            $checkedGroupIds = $componentCheckedGroups->pluck('style_group_id')->unique();

                            $componentStyleRows = $pgStylePropsMap->get($pagesComponent['id'], collect())
                                ->whereIn('style_group_id', $checkedGroupIds);

                            $newStyle = [];
                            foreach ($componentStyleRows as $sty) {
                                $sty = (array) $sty;
                                $labelKey = $pagesComponent['id'].'_'.$sty['style_group_id'];
                                $labelRecord = $styleGroupLabelMap->get($labelKey);
                                $newStyle[$sty['slug']]['group_label'] = $labelRecord->style_group_label ?? null;
                                $newStyle[$sty['slug']][$sty['name']] = $sty['value'];
                            }

                            // Build component structure
                            $componentGeneral = $this->buildPageComponentStructure($pagesComponent, $newStyle, $pluginSlug, $pluginPrefix);

                            if ($pagesComponent['layout_type'] == 'ListViewVertical') {
                                /* IF EMPTY OBJECT NOT REPLACE TO ARRAY & SAME OBJECT SEND API FROM DB BY REQ BY SAIFUL START */
                                foreach (['items', 'dev_data', 'filters', 'pagination'] as $key) {
                                    if (empty($pagesComponent[$key])) {
                                        continue;
                                    }

                                    $decoded = $pagesComponent[$key];

                                    if (is_string($decoded)) {
                                        $first = json_decode($decoded, false); // 🧠 decode as object

                                        if (is_string($first)) {
                                            $second = json_decode($first, false);
                                            $decoded = json_last_error() === JSON_ERROR_NONE ? $second : $first;
                                        } elseif (is_object($first) || is_array($first)) {
                                            $decoded = $first;
                                        } else {
                                            $decoded = null;
                                        }
                                    }

                                    if ($key === 'dev_data' && is_object($decoded)) {
                                        foreach ((array) $decoded as $devKey => $devValue) {
                                            $componentGeneral['properties'][$devKey] = $devValue;
                                            $componentGeneral['customize_properties'][$devKey] = $devValue;
                                        }
                                    } else {
                                        $componentGeneral['properties'][$key] = $decoded;
                                        $componentGeneral['customize_properties'][$key] = $decoded;
                                    }
                                }
                                /* IF EMPTY OBJECT NOT REPLACE TO ARRAY & SAME OBJECT SEND API FROM DB BY REQ BY SAIFUL END */

                                /* IF EMPTY OBJECT REPLACE TO NULL BY REQ BY SOHEL VI START */
                                /*$replaceEmptyObjectsWithNull = function (mixed $data) use (&$replaceEmptyObjectsWithNull): mixed {
                                    if ($data instanceof stdClass) {
                                        $vars = get_object_vars($data);

                                        if (empty($vars)) {
                                            return null;
                                        }

                                        foreach ($vars as $key => $value) {
                                            $data->$key = $replaceEmptyObjectsWithNull($value);
                                        }

                                        return $data;
                                    }

                                    if (is_array($data)) {
                                        foreach ($data as $key => $value) {
                                            $data[$key] = $replaceEmptyObjectsWithNull($value);
                                        }

                                        return $data;
                                    }

                                    return $data;
                                };
                                // Your loop starts here
                                foreach (['items', 'dev_data', 'filters', 'pagination'] as $key) {
                                    if (empty($pagesComponent[$key])) {
                                        continue;
                                    }

                                    $decoded = $pagesComponent[$key];

                                    if (is_string($decoded)) {
                                        $first = json_decode($decoded, false); // decode as object

                                        if (is_string($first)) {
                                            $second = json_decode($first, false);
                                            $decoded = json_last_error() === JSON_ERROR_NONE ? $second : $first;
                                        } elseif (is_object($first) || is_array($first)) {
                                            $decoded = $first;
                                        } else {
                                            $decoded = null;
                                        }
                                    }

                                    // 🔁 Apply the transformer
                                    $decoded = $replaceEmptyObjectsWithNull($decoded);

                                    if ($key === 'dev_data' && is_object($decoded)) {
                                        foreach ((array) $decoded as $devKey => $devValue) {
                                            $componentGeneral['properties'][$devKey] = $devValue;
                                            $componentGeneral['customize_properties'][$devKey] = $devValue;
                                        }
                                    } else {
                                        $componentGeneral['properties'][$key] = $decoded;
                                        $componentGeneral['customize_properties'][$key] = $decoded;
                                    }
                                }*/
                                /* IF EMPTY OBJECT REPLACE TO NULL BY REQ BY SOHEL VI END */

                            }

                            $final[] = $componentGeneral;
                        }
                    }

                    $pageProperties = [
                        'screen_status' => $page->screen_status,
                        'static_screen_image' => $page->static_screen_image ? config('app.image_public_path').$page->static_screen_image : null,
                        'static_screen_message' => $page->static_screen_message,
                    ];

                    if ($page->screen_status === 'dynamic') {
                        $pageProperties['page_decoration'] = [
                            'background_color' => $page->background_color,
                            'border_color' => $page->border_color,
                            'border_radius' => $page->border_radius,
                        ];
                    }

                    $pages[] = [
                        'name' => $page->name,
                        'slug' => $page->slug,
                        'component_limit' => $page->component_limit > 0 ? $page->component_limit : null,
                        'persistent_footer_buttons' => isset($page->persistent_footer_buttons) ? (string) $page->persistent_footer_buttons : null,
                        'properties' => $pageProperties,
                        'customize_properties' => $pageProperties,
                        'components' => $final,
                    ];
                }
            }
            $themeData['pages'] = $pages;

            if (isset($data) && ! empty($data)) {
                $response = new JsonResponse([
                    'status' => Response::HTTP_OK,
                    'url' => $request->getUri(),
                    'method' => $request->getMethod(),
                    'message' => 'Data Found',
                    'data' => $themeData,
                ], Response::HTTP_OK);
                $response->setEncodingOptions(JSON_UNESCAPED_SLASHES);
                $response->headers->set('Content-Type', 'application/json');

                return $response;
            }
            $response = new JsonResponse([
                'status' => Response::HTTP_NOT_FOUND,
                'url' => $request->getUri(),
                'method' => $request->getMethod(),
                'message' => 'Data Not Found',
            ], Response::HTTP_NOT_FOUND);
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        } catch (Exception $ex) {
            return \response([
                'message' => $ex->getMessage(),
            ]);
        }
    }

    /**
     * Build component general properties for page components.
     */
    private function buildPageComponentGeneralProperties(array $pagesComponent, string $pluginSlug, string $pluginPrefix): array
    {
        $componentPluginSlug = $pagesComponent['plugin_slug'];
        $classType = $pluginPrefix.$pagesComponent['product_type'];

        $componentProperties = [
            'label' => $pagesComponent['label'],
            'group_name' => $pagesComponent['group_name'],
            'layout_type' => $pagesComponent['layout_type'],
            'icon_code' => $pagesComponent['icon_code'],
            'event' => $pagesComponent['event'],
            'scope' => json_decode($pagesComponent['scope']),
            'class_type' => $pagesComponent['product_type'] ? $classType : null,
            'web_icon' => $pagesComponent['web_icon'],
            'is_multiple' => $pagesComponent['is_multiple'],
            'selected_design' => $pagesComponent['selected_design'],
            'detailsPage' => $pagesComponent['details_page'],
            'is_transparent_background' => $pagesComponent['transparent'] == 'True',
        ];

        // Additional product type checks
        if ($pagesComponent['product_type'] == 'Category') {
            $componentProperties['category_slugs'] = [];
            $componentProperties['all_category'] = true;
        }
        if ($pagesComponent['product_type'] == 'CategoryProduct') {
            $componentProperties['categories'] = [];
        }

        // Handle special case for BannerSliderHorizontal layout
        if ($pagesComponent['layout_type'] === 'BannerSliderHorizontal') {
            $staticProterties = [
                [
                    'name' => 'Banner 1',
                    'image' => $pagesComponent['image'] ? config('app.image_public_path').$pagesComponent['image'] : null,
                    'category' => null,
                    'button_text' => 'Sale2',
                    'button_color' => '#ffffff',
                ],
                [
                    'name' => 'Banner 2',
                    'image' => $pagesComponent['image'] ? config('app.image_public_path').$pagesComponent['image'] : null,
                    'category' => null,
                    'button_text' => 'Sale2',
                    'button_color' => '#ffffff',
                ],
            ];
            $componentProperties['items'] = $staticProterties;
        }

        if ($pagesComponent['layout_type'] == 'ListViewVertical' || $pagesComponent['layout_type'] == 'ListViewHorizontal' || $pagesComponent['layout_type'] == 'ListViewGrid') {
            $componentProperties['show_no_data_view'] = $pagesComponent['show_no_data_view'] == 1 ? true : false;
        }

        return $componentProperties;
    }

    /**
     * Build component structure for page components.
     */
    private function buildPageComponentStructure(array $pagesComponent, array $newStyle, string $pluginSlug, string $pluginPrefix): array
    {
        $componentProperties = $this->buildPageComponentGeneralProperties($pagesComponent, $pluginSlug, $pluginPrefix);

        return [
            'name' => $pagesComponent['name'],
            'slug' => $pagesComponent['slug'],
            'support_extension' => $pagesComponent['plugin_slug'],
            'component_image' => $pagesComponent['image'] ? config('app.image_public_path').$pagesComponent['image'] : null,
            'image_url' => $pagesComponent['image_url'] ? config('app.image_public_path').$pagesComponent['image_url'] : null,
            'is_active' => $pagesComponent['is_active'] == 1,
            'properties' => $componentProperties,
            'customize_properties' => $componentProperties,
            'styles' => $newStyle,
            'customize_styles' => $newStyle,
        ];
    }
}
