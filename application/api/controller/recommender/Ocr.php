<?php

namespace app\api\controller\recommender;

vendor('autoload.php');
use TencentCloud\Common\Credential;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Ocr\V20181119\OcrClient;
use TencentCloud\Ocr\V20181119\Models\IDCardOCRRequest;

/**
 * 开机流程所需接口
 */
class Ocr 
{
    public function ocr($ImageUrl){
        try {
            $cred = new Credential("AKIDYCmDcktWXNGG2Xsxta3WjhW7g5D5wTnr", "ve8LVPDl2IICqEakDZhFWtzqTebIitLW");
            $httpProfile = new HttpProfile();
            $httpProfile->setEndpoint("ocr.tencentcloudapi.com");
            
            $clientProfile = new ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);
            $client = new OcrClient($cred, "ap-guangzhou", $clientProfile);
        
            $req = new IDCardOCRRequest();
            
            $params = array(
                "ImageUrl" => $ImageUrl
            );
            $req->fromJsonString(json_encode($params));
            $resp = $client->IDCardOCR($req);
            // $info = $resp->toJsonString();
            $info = json_decode( json_encode( $resp),true);
            $info['code'] = 1;
            return $info;
        }
        catch(TencentCloudSDKException $e){
            $info['code'] = ((array)$e)["\0*\0code"];
            $info['message'] =  ((array)$e)["\0*\0message"];
            return $info;
        }
    }

}