<?php

namespace App\Http\Controllers\Api;

use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class VersionsController extends Controller
{
    protected $authorization;
    protected $domain;
    protected $pluginName;
    protected $email;

    public function __construct(Request $request)
    {
        $data = Lead::checkAuthorization($request);
        $this->authorization = ($data && $data['auth_type']) ? $data['auth_type'] : false;
        $this->domain = $data['domain'] ?? '';
        $this->email = $data['email'] ?? '';
        $this->pluginName = $data['plugin_name'] ?? '';
    }

    protected function jsonResponse(int $statusCode, string $message, array $additionalData = []): JsonResponse
    {
        return response()->json(array_merge([
            'status' => $statusCode,
            'message' => $message,
        ], $additionalData), $statusCode);
    }

    public function index()
    {
        $versions = config('api_versions.versions');
        $recommended = config('api_versions.recommended_version');

        return response()->json([
            'available_versions' => $versions,
            'recommended_version' => $recommended,
//            'current_version' => $this->getCurrentVersion(),
        ]);
    }

    protected function getCurrentVersion()
    {
        // Optionally detect version from request URL
        $uri = request()->path();
        if (preg_match('/v\d+(_\d+)?/', $uri, $match)) {
            return $match[0];
        }
        return null;
    }



}
