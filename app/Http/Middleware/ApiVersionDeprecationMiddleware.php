<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ApiVersionDeprecationMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Detect version from URL (e.g. /api/v1/users)
        if (preg_match('/v\d+(_\d+)?/', $request->path(), $match)) {
            $versionKey = $match[0];
            $config = config("api_versions.versions.$versionKey");

            if (!$config) {
                return response()->json([
                    'message' => "Invalid or unsupported API version: $versionKey",
                ], 400);
            }

            // Handle deprecated versions
            if ($config['status'] === 'deprecated') {
                $sunsetAt = isset($config['sunset_at']) ? Carbon::parse($config['sunset_at']) : null;
                // Block access if sunset date is over
                if ($sunsetAt && now()->gte($sunsetAt)) {
                    return response()->json([
                        'message' => "API version $versionKey has been retired.",
                        'recommended_version' => config('api_versions.recommended_version'),
                        'docs_url' => $config['docs_url'],
                    ], 410); // 410 Gone
                }

                // Deprecated but still usable
                $response = $next($request);
                $response->headers->set('X-API-Version', $versionKey);
                $response->headers->set('X-API-Deprecated', 'true');
                $response->headers->set('X-API-Deprecation-Date', $config['deprecated_at']);
                $response->headers->set('X-API-Sunset', $config['sunset_at']);
                $response->headers->set('X-API-Docs', $config['docs_url']);
                $response->headers->set('Warning', '299 - "API version ' . $versionKey . ' is deprecated and will be removed on ' . $config['sunset_at'] . '"');
                return $response;
            }

            // Active version
            $response = $next($request);
            $response->headers->set('X-API-Version', $versionKey);
            return $response;
        }

        // If no version found in URL → continue normally or block
        return $next($request);
    }
}
