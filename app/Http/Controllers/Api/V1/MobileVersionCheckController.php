<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MobileVersionMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MobileVersionCheckController extends Controller
{
    /**
     * Check version compatibility
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function check(Request $request)
    {
        try {
            // Validate request - plugin_version is nullable
            $validated = $request->validate([
                'app_name' => 'required|string',
                'mobile_version' => 'required|regex:/^\d+\.\d+\.\d+$/',
                'plugin_version' => 'nullable|regex:/^\d+\.\d+\.\d+$/'
            ]);

            // Cache version mapping for 1 hour
            $cacheKey = 'version_mapping_' . $validated['app_name'];
            $versionMapping = Cache::remember($cacheKey, 3600, function () use ($validated) {
                return MobileVersionMapping::where('app_name', $validated['app_name'])
                    ->where('is_active', 1)
                    ->first();
            });

            if (!$versionMapping) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No version mapping found for this app.',
                ], 404);
            }

            // Extract versions
            $mobileVersion = $validated['mobile_version'];
            $pluginVersion = $validated['plugin_version'] ?? null;
            $minimumMobile = $versionMapping->mobile_version;
            $minimumPlugin = $versionMapping->minimum_plugin_version;
            $latestPlugin = $versionMapping->latest_plugin_version;
            $forceUpdate = $versionMapping->force_update;
            $customMessage = $versionMapping->optional_message;

            // Check if force update is enabled
            if ($forceUpdate) {
                return $this->forceUpdateResponse($minimumMobile, $latestPlugin, $customMessage);
            }

            // Check mobile version compatibility
            if (version_compare($mobileVersion, $minimumMobile, '<')) {
                return $this->forceUpdateResponse($minimumMobile, $latestPlugin, $customMessage);
            }

            // Check plugin version only if provided
            if ($pluginVersion) {
                // Check if plugin version is below minimum
                if (version_compare($pluginVersion, $minimumPlugin, '<')) {
                    return $this->forceUpdateResponse($minimumMobile, $latestPlugin, $customMessage);
                }

                // Check if plugin version is below latest
                if (version_compare($pluginVersion, $latestPlugin, '<')) {
                    return response()->json([
                        'status' => 'optional_update',
                        'message' => $customMessage ?? 'A new plugin version is available.',
                        'latest_plugin' => $latestPlugin,
                    ]);
                }
            }

            // All checks passed
            return response()->json([
                'status' => 'ok',
                'message' => 'Application is compatible.',
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Version check error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while checking version compatibility.',
            ], 500);
        }
    }

    /**
     * Return a force update response
     */
    private function forceUpdateResponse($latestMobile, $latestPlugin, $customMessage)
    {
        return response()->json([
            'status' => 'force_update',
            'message' => $customMessage ?? 'Please update your app to continue.',
            'latest_mobile' => $latestMobile,
            'latest_plugin' => $latestPlugin,
        ]);
    }}
