<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\VersionCheckRequest;
use App\Models\Addon;
use App\Models\AddonVersion;
use App\Models\MobileSupportApp;
use App\Models\MobileVersionMapping;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MobileVersionCheckController extends Controller
{
    /**
     * Check version compatibility
     *
     * @return JsonResponse
     */
    public function check(Request $request)
    {
        try {
            $validated = $request->validate([
                'app_name' => 'required|string',
                'mobile_version' => 'required|regex:/^\d+\.\d+\.\d+$/',
                'plugin_version' => 'required|regex:/^\d+\.\d+\.\d+$/',
            ]);

            $appName = $validated['app_name'];
            $mobileVersion = $validated['mobile_version'];
            $pluginVersion = $validated['plugin_version'];

            /**
             * Exact mobile version mapping
             */
            $mapping = MobileVersionMapping::whereHas('mobile_app', function ($q) use ($appName) {
                $q->where('slug', $appName);
            })
                ->where('is_active', 1)
                ->where('mobile_version', $mobileVersion)
                ->first();

            /**
             * Mobile version not found
             */
            if (! $mapping) {
                return $this->buildResponse(
                    'force_update',
                    'Please update your app to continue.',
                    $this->latestMobileForPlugin($appName, $pluginVersion),
                    $pluginVersion
                );
            }

            /**
             * Force update flag
             */
            if ($mapping->force_update) {
                return $this->buildResponse(
                    'force_update',
                    $mapping->optional_message ?? 'Please update your app to continue.',
                    $mapping->mobile_version,
                    $mapping->latest_plugin_version
                );
            }

            /**
             * Plugin compatible?
             */
            $pluginCompatible =
                version_compare($pluginVersion, $mapping->minimum_plugin_version, '>=') &&
                version_compare($pluginVersion, $mapping->latest_plugin_version, '<=');

            if ($pluginCompatible) {

                // Is there a newer mobile version that also supports this plugin?
                $latestMobileForPlugin = $this->latestMobileForPlugin($appName, $pluginVersion);

                if ($latestMobileForPlugin &&
                    version_compare($latestMobileForPlugin, $mobileVersion, '>')
                ) {
                    return $this->buildResponse(
                        'optional_update',
                        'A newer version is available for better compatibility.',
                        $latestMobileForPlugin,
                        $pluginVersion
                    );
                }

                return $this->buildResponse(
                    'ok',
                    'Application is compatible.'
                );
            }

            /**
             * Plugin not supported by this mobile → FORCE UPDATE
             */
            $latestMobileForPlugin = $this->latestMobileForPlugin($appName, $pluginVersion);

            $message = $latestMobileForPlugin
                ? 'Please update your app to continue.'
                : 'This plugin version is no longer supported.';

            return $this->buildResponse(
                'force_update',
                $message,
                $latestMobileForPlugin,
                $pluginVersion
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->buildResponse(
                'error',
                'Validation failed.',
                null,
                null,
                422,
                $e->errors()
            );
        } catch (\Exception $e) {
            Log::error('App version check failed', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return $this->buildResponse(
                'error',
                'An internal server error occurred.',
                null,
                null,
                500
            );
        }
    }

    /**
     * Version check for mobile & plugin compatibility.
     */
    public function versionCheck(VersionCheckRequest $request): JsonResponse
    {
        try {
            $appName = $request->validated('app_name');
            $mobileVersion = $request->validated('mobile_version');
            $pluginVersion = $request->validated('plugin_version');
            $pluginSlug = $request->validated('plugin_slug');

            // 1. Find mobile app by slug
            $mobileApp = MobileSupportApp::where('slug', $appName)->first();

            if (! $mobileApp) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Mobile app not found.',
                ], 404);
            }

            // 2. Get the latest active mobile version mapping (highest mobile_version_code)
            $latestMapping = MobileVersionMapping::where('mobile_app_id', $mobileApp->id)
                ->where('is_active', 1)
                ->orderByDesc('mobile_version_code')
                ->first();

            $latestMobile = $latestMapping->mobile_version ?? null;
            $latestPlugin = $latestMapping->latest_plugin_version ?? null;

            // 3. Get all active mappings for this app (used for both compatibility checks)
            $activeMappings = MobileVersionMapping::where('mobile_app_id', $mobileApp->id)
                ->where('is_active', 1)
                ->get();

            // 4. compatible_mobile_version_vs_plugin:
            //    Mobile versions where the given plugin_version falls within [minimum, latest]
            $compatibleMobileVersions = $activeMappings
                ->filter(function ($mapping) use ($pluginVersion) {
                    return version_compare($pluginVersion, $mapping->minimum_plugin_version, '>=')
                        && version_compare($pluginVersion, $mapping->latest_plugin_version, '<=');
                })
                ->pluck('mobile_version')
                ->values()
                ->all();

            // 5. compatible_plugin_version_vs_mobile:
            //    Find the mapping that matches the given mobile_version,
            //    then return addon versions within [minimum, latest] range
            $compatiblePluginVersions = [];

            $matchedMapping = $activeMappings->firstWhere('mobile_version', $mobileVersion);

            if ($matchedMapping) {
                $addon = Addon::where('addon_slug', $pluginSlug)->first();

                if ($addon) {
                    $compatiblePluginVersions = AddonVersion::where('addon_id', $addon->id)
                        ->where('is_active', 1)
                        ->get()
                        ->filter(function ($addonVersion) use ($matchedMapping) {
                            return version_compare($addonVersion->version, $matchedMapping->minimum_plugin_version, '>=')
                                && version_compare($addonVersion->version, $matchedMapping->latest_plugin_version, '<=');
                        })
                        ->pluck('version')
                        ->values()
                        ->all();
                }
            }

            return response()->json([
                'status' => 200,
                'latest_mobile' => $latestMobile,
                'latest_plugin' => $latestPlugin,
                'compitable_mobile_version_vs_plugin' => $compatibleMobileVersions,
                'compitable_plugin_version_vs_mobile' => $compatiblePluginVersions,
            ]);

        } catch (\Exception $e) {
            Log::error('Version check failed', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'An internal server error occurred.',
            ], 500);
        }
    }

    private function buildResponse(
        string $status,
        string $message,
        ?string $latestMobile = null,
        ?string $latestPlugin = null,
        int $httpCode = 200,
        $errors = null
    ) {
        $response = [
            'status' => $status,
            'message' => $message,
        ];

        if ($latestMobile) {
            $response['latest_mobile'] = $latestMobile;
        }

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $httpCode);
    }

    private function latestMobileForPlugin(string $appName, string $pluginVersion): ?string
    {
        return MobileVersionMapping::whereHas('mobile_app', function ($q) use ($appName) {
            $q->where('slug', $appName);
        })
            ->where('is_active', 1)
            ->where('minimum_plugin_version', '<=', $pluginVersion)
            ->where('latest_plugin_version', '>=', $pluginVersion)
            ->orderByDesc('mobile_version_code')
            ->value('mobile_version');
    }
}
