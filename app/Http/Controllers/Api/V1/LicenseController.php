<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\BuildDomain;
use App\Models\Lead;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

class LicenseController extends Controller
{
    use ValidatesRequests;
    protected $authorization;
    protected $domain;
    protected $pluginName;
    protected $email;

    public function __construct(Request $request){
        $data = Lead::checkAuthorization($request);
        $this->authorization = ($data && $data['auth_type'])?$data['auth_type']:false;
        $this->domain = ($data && $data['domain'])?$data['domain']:'';
        $this->email = ($data && $data['email'])?$data['email']:'';
        $this->pluginName = ($data && $data['plugin_name'])?$data['plugin_name']:'';
    }

    public function check(Request $request)
    {
        if (!$this->authorization){
            $response = new JsonResponse([
                'status'=>Response::HTTP_UNAUTHORIZED,
                'url' => $request->getUri(),
                'method' => $request->getMethod(),
                'message'=>'Unauthorized1',
            ],Response::HTTP_UNAUTHORIZED);
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }


        if ($request->get('license_key')!='' && $request->get('site_url')!=''){
            $fluentApiUrl = config('app.fluent_api_url');

            $params = [
                'fluent_cart_action' => 'check_license',
                'license' => $request->get('license_key'),
                'url' => $request->get('site_url')
            ];

            $response = Http::withHeaders([])->get($fluentApiUrl,$params);

            $body = $response->getBody()->getContents();
            $data = json_decode($body,true);
            if (!$data['success']){
                $response = new JsonResponse([
                    'status'=>Response::HTTP_NOT_FOUND,
                    'url' => $request->getUri(),
                    'method' => $request->getMethod(),
                    'message'=>$data['data']['error'],
                ],Response::HTTP_OK);
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            }

            $response = new JsonResponse([
                'status'=>Response::HTTP_OK,
                'url' => $request->getUri(),
                'method' => $request->getMethod(),
                'message'=>'Your License key is valid.',
                'data'=>$data['data'],
            ],Response::HTTP_OK);
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $response = new JsonResponse([
            'status'=>Response::HTTP_NOT_FOUND,
            'url' => $request->getUri(),
            'method' => $request->getMethod(),
            'message'=>'Parameter missing',
        ],Response::HTTP_OK);
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function activate(Request $request)
    {
        if (!$this->authorization){
            $response = new JsonResponse([
                'status'=>Response::HTTP_UNAUTHORIZED,
                'url' => $request->getUri(),
                'method' => $request->getMethod(),
                'message'=>'Unauthorized',
            ],Response::HTTP_UNAUTHORIZED);
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'site_url' => 'required',
            'license_key' => 'required'
        ],[
            'site_url.required' => __('customers::messages.domainRequired'),
            'license_key.required' => __('customers::messages.licenseRequired')
        ]);

        if ($validator->fails()) {
            $response = new JsonResponse([
                'status'=>Response::HTTP_UNAUTHORIZED,
                'url' => $request->getUri(),
                'method' => $request->getMethod(),
                'message'=> $validator->errors(),
            ],Response::HTTP_UNAUTHORIZED);
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $data = $request->only('site_url','license_key','email');
        $input = [
            'url' => $data['site_url'],
            'license' => $data['license_key'],
            'fluent_cart_action' => 'activate_license'
        ];

        $fluentApiUrl = config('app.fluent_api_url');
        $response = Http::withHeaders([])->get($fluentApiUrl,$input);

        $body = $response->getBody()->getContents();
        $res = json_decode($body,true);

        if (isset($res['code']) && $res['code'] == 'rest_no_route'){
            $response = new JsonResponse([
                'status'=>Response::HTTP_NOT_FOUND,
                'url' => $request->getUri(),
                'method' => $request->getMethod(),
                'message'=>$res['data']['error'],
            ],Response::HTTP_NOT_FOUND);
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        if (!$res['success']){
            $response = new JsonResponse([
                'status'=>Response::HTTP_NOT_FOUND,
                'url' => $request->getUri(),
                'method' => $request->getMethod(),
                'message'=>$res['data']['error'],
            ],Response::HTTP_NOT_FOUND);
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        if ($res['success']){
            $getBuildDomain = BuildDomain::where('site_url',$data['site_url'])->where('license_key',$data['license_key'])->first();
            if (!$getBuildDomain){
                $packageName = $this->getSubdomainAndDomain($data['site_url']);
                $domainInput = [
                    'site_url' => $data['site_url'],
                    'package_name' => 'com.'.$packageName.'.live',
                    'email' => $data['email'] ?? $this->email,
                    'plugin_name' => $this->pluginName,
                    'license_key' => $data['license_key'],
                ];
                BuildDomain::create($domainInput);
            }
        }

        $response = new JsonResponse([
            'status'=>Response::HTTP_OK,
            'url' => $request->getUri(),
            'method' => $request->getMethod(),
            'message'=>'Your License key has been activated successfully.',
            'data'=>$res['data'],
        ],Response::HTTP_OK);
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    function getSubdomainAndDomain($url) {
        $parsedUrl = parse_url($url);
        if (isset($parsedUrl['host'])) {
            $host = $parsedUrl['host'];

            // Remove top-level domains (e.g., .com, .co, .net)
            $hostParts = explode('.', $host);
            array_pop($hostParts); // Remove the last part (top-level domain)

            // Rejoin the remaining parts and remove any non-alphabetic characters
            $cleaned = preg_replace('/[^a-zA-Z]/', '', implode('', $hostParts));
            return strtolower($cleaned); // Return as lowercase only letters
        }
        return null;
    }

}
