<?php

namespace app\api\controller\coach;

use app\common\controller\Api;
use think\cache;

/**
 * 开机流程所需接口
 */
class Kaoqin extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    public function _initialize()
    {
        
        
    }

    public function index()
    {
        $params = $this->request->post();
        if(empty($params['latitude'])|| empty($params['longitude']) || empty($params['longitude']) || empty($params['longitude']) ){
            $this->error('参数缺失');
        }
    }


    
    public function getlocation()
    {
        $params = $this->request->post();
        if(empty($params['latitude'])|| empty($params['longitude'])){
            $this->error('参数缺失');
        }
        $url = "https://apis.map.qq.com/ws/geocoder/v1/?location=";
        $key = "NGZBZ-ZBAKU-5LZVV-BN45H-FWV2E-DQBRK";
        $url = $url.$params['latitude'].','.$params['longitude'].'&key='.$key;
        $res = $this->curl($url);
        $res = json_decode($res,true);
        if($res['status'] == 0){
            $this->success('res',$res['result']);
        }else{
            $this->error('返回失败',$res['message']);
        }
    }

    
    public function curl($url){
        $info=curl_init();
        curl_setopt($info,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($info,CURLOPT_HEADER,0);
        curl_setopt($info,CURLOPT_NOBODY,0);
        curl_setopt($info,CURLOPT_TIMEOUT, 3);
        curl_setopt($info,CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($info,CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($info,CURLOPT_URL,$url);
        $output= curl_exec($info);
        curl_close($info);
        return $output;
    }
}
