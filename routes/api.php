<?php

use App\Http\Controllers\Api\V1\ApkBuildHistoryController;
use App\Http\Controllers\Api\V1\ApkBuildResourceController;
use App\Http\Controllers\Api\V1\FreeTrialController;
use App\Http\Controllers\Api\V1\GlobalConfigController;
use App\Http\Controllers\Api\V1\LeadController;
use App\Http\Controllers\Api\V1\LicenseController;
use App\Http\Controllers\Api\V1\PageComponentController;
use App\Http\Controllers\Api\V1\PluginController;
use App\Http\Controllers\Api\V1\ThemeController;
use App\Http\Controllers\Api\VersionsController;
use App\Http\Middleware\LogActivity;
use App\Http\Middleware\LogRequestResponse;
use Illuminate\Support\Facades\Route;

Route::get('/appza/versions', [VersionsController::class, 'index']);
require __DIR__ . '/api_v0.php';
require __DIR__ . '/api_v1.php';
require __DIR__ . '/api_v2.php';


Route::fallback(function () {
    $path = request()->path();

    if (preg_match('/v\d+(_\d+)?/', $path, $match)) {
        $version = $match[0];
        $config = config("api_versions.versions.$version");

        if ($config && isset($config['status']) && $config['status'] === 'deprecated') {
            return response()->json([
                'message' => "API version $version has been fully removed.",
                'recommended_version' => config('api_versions.recommended_version'),
                'docs_url' => $config['docs_url'],
            ], 410); // Gone
        }
    }

    return response()->json([
        'message' => 'Endpoint not found or version retired.',
        'recommended_version' => config('api_versions.recommended_version'),
    ], 404);
});


