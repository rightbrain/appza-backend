<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\BuildResponseRequest;
use App\Http\Requests\FinalBuildRequest;
use App\Jobs\ProcessBuild;
use App\Models\ApkBuildHistory;
use App\Models\BuildDomain;
use App\Models\BuildOrder;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use App\Services\IosBuildValidationService;

class ApkBuildHistoryController extends Controller
{
    protected $authorization;
    protected $domain;
    protected $pluginName;
    protected $customerName;
    protected $iosBuildValidationService;

    public function __construct(Request $request,IosBuildValidationService $iosBuildValidationService){
        $data = Lead::checkAuthorization($request);
        $this->authorization = ($data && $data['auth_type'])?$data['auth_type']:false;
        $this->domain = ($data && $data['domain'])?$data['domain']:'';
        $this->pluginName = ($data && $data['plugin_name'])?$data['plugin_name']:'';
        $this->customerName = ($data && $data['customer_name'])?$data['customer_name']:'';
        $this->iosBuildValidationService = $iosBuildValidationService;
    }

    public function apkBuild(FinalBuildRequest $request){

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

        if ($this->pluginName == 'lazy_task'){
            $response = new JsonResponse([
                'status'=>Response::HTTP_OK,
                'message'=> 'Build process off for lazy task',
            ],Response::HTTP_OK);
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $input = $request->validated();

        $findSiteUrl = BuildDomain::where('site_url',$input["site_url"])->where('license_key',$input['license_key'])->where('package_name',$input['package_name'])->first();
        if ($findSiteUrl){
            $apkBuildExists = BuildOrder::where('status','processing')->where('issuer_id',$findSiteUrl->ios_issuer_id)->exists();

            if ($apkBuildExists){
                $response = new JsonResponse([
                    'status' => Response::HTTP_OK,
                    'url' => $request->getUri(),
                    'method' => $request->getMethod(),
                    'message' => 'An app building process is already going on. Please try again later.'
                ], Response::HTTP_OK);
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            }

            $buildHistory = ApkBuildHistory::create([
                'version_id' => $findSiteUrl->version_id,
                'build_domain_id' => $findSiteUrl->id,
                'fluent_id' => $findSiteUrl->fluent_id,
                'app_name' => $findSiteUrl->app_name,
                'app_logo' => $findSiteUrl->app_logo,
                'app_splash_screen_image' => $findSiteUrl->app_splash_screen_image,
                'ios_issuer_id' => $findSiteUrl->ios_issuer_id,
                'ios_key_id' => $findSiteUrl->ios_key_id,
                'ios_team_id' => $findSiteUrl->team_id,
                'ios_p8_file_content' => $findSiteUrl->ios_p8_file_content,
            ]);

            $buildHistory->update([
                'build_version'=>$buildHistory->id
            ]);

            /* START APK BUILD REQUEST JOBS FOR ANIS VI */
            $job = $this->buildRequestProcessForJob($buildHistory,$findSiteUrl);
            /* END APK BUILD REQUEST JOBS FOR ANIS VI */


            $response = new JsonResponse([
                'status' => Response::HTTP_OK,
                'url' => $request->getUri(),
                'method' => $request->getMethod(),
                'message' => 'Your App building process has been started successfully.',
                'data' => $buildHistory
            ], Response::HTTP_OK);
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }else{
            $response = new JsonResponse([
                'status' => Response::HTTP_NOT_FOUND,
                'url' => $request->getUri(),
                'method' => $request->getMethod(),
                'message' => 'Domain or license key wrong',
            ], Response::HTTP_OK);
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
    }

    private function buildRequestProcessForJob($buildHistory,$findSiteUrl)
    {
        $data['package_name'] = $findSiteUrl->package_name;
        $data['app_name'] = $buildHistory->app_name;
        $data['domain'] = $findSiteUrl->site_url;
        $data['base_suffix'] = '/wp-json/appza/api/v1/home-page';
        $data['base_url'] = rtrim($findSiteUrl->site_url, '/').'/wp-json/appza/api/v1/home-page';
        $data['build_number'] = $buildHistory->build_version;
        $data['icon_url'] = url('').'/upload/build-apk/logo/'.$buildHistory->app_logo;

        //for android
        $data['build_target'] = 'android';
        $data['jks_url'] = url('').'/android/upload-keystore.jks';
        $data['key_properties_url'] = url('').'/android/key.properties';

        $details = [
            'customer_name'=>$this->customerName,
            'subject'=>'Your App Build Request is in Progress',
            'app_name'=>$buildHistory->app_name,
            'mail_template'=>'build_request'
        ];

        Mail::to($findSiteUrl->confirm_email)->send(new \App\Mail\BuildRequestMail($details));

        $order = BuildOrder::create($data);
        $order = $order->fresh();
        dispatch(new ProcessBuild($order->id));

        //for ios
        if ($findSiteUrl->is_ios){
            $data['build_target'] = 'ios';

            $data['jks_url'] = null;
            $data['key_properties_url'] = null;

            $data['issuer_id'] = $buildHistory->ios_issuer_id;
            $data['key_id'] = $buildHistory->ios_key_id;
            $data['api_key_url'] = url('').'/upload/build-apk/p8file/'.$buildHistory->ios_p8_file_content;
            $data['team_id'] = $buildHistory->ios_team_id;
            $data['app_identifier'] =$findSiteUrl->package_name;

            $order = BuildOrder::create($data);
            $order = $order->fresh();
            dispatch(new ProcessBuild($order->id));
        }
    }

    public function apkBuildResponse(BuildResponseRequest $request,$id)
    {
        $input = $request->validated();
        $orderItem = BuildOrder::find($id);
        if (!$orderItem){
            $response = new JsonResponse([
                'status'=>Response::HTTP_NOT_FOUND,
                'message'=> 'Build order not found',
            ],Response::HTTP_NOT_FOUND);
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $orderItem->update($input);
        $getUserInfo = BuildDomain::where('package_name',$orderItem->package_name)->first();

        if ($input['status']==='failed'){
            $details = [
                'customer_name'=>$getUserInfo->app_name,
                'subject'=>'Update on Your App Build: Action Required',
                'app_name'=>$getUserInfo->app_name,
                'mail_template'=>'build_failed'
            ];
            Mail::to($getUserInfo->confirm_email)->send(new \App\Mail\BuildRequestMail($details));
        }elseif ($input['status']==='completed'){
            $details = [
                'customer_name'=>$getUserInfo->app_name,
                'subject'=>'Your App Build Is Complete!',
                'app_name'=>$getUserInfo->app_name,
                'apk_url'=>$orderItem->apk_url,
                'mail_template'=>'build_complete'
            ];
            Mail::to($getUserInfo->confirm_email)->send(new \App\Mail\BuildRequestMail($details));
        }

        $response = new JsonResponse([
            'status'=>Response::HTTP_OK,
            'message'=> 'success',
        ],Response::HTTP_OK);
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}