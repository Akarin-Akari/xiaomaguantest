<?php

namespace app\api\controller\coach;

use app\common\controller\Api;
use think\cache;

/**
 * 开机流程所需接口
 */
class Index extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    private $token = 'vipdriver';
    private $kaoqin = null;
    private $coachai = null;
    private $coachsc = null;
    private $kaoqinconfig = null;

    // yBMvbyEUFgz1XD0laW1N5bWqbNhWt9fiJnhH8F3FUsM
    public function _initialize()
    {
        
        $this->coachai = new \app\admin\model\Coach;
        $this->coachsc = new \app\admin\model\coach\Sc;


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



    public function gettest()
    {
        $res = Cache::get('params');
        var_dump($res['echostr']);
    }

    public function index(){
        $params = $this->request->get();
        Cache::set('test1',$params);

        $timestamp = $params['timestamp'];
        $nonce = $params['nonce'];
        $signature = $params['signature'];
        $echostr = $params['echostr'];

        $token = $this->token;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if ($tmpStr == $signature ) {
            Cache::set('test1',123123);
            echo $echostr;
            exit;
        } else {
            return false;
        }
    }

    public function FunctionName()
    {
        $APPID = 'wx7580189fba858f26';
        $AppSecret = 'dfd9ba054745cfc5a5eb815112ecb375';
        $machine_code = '10022';
        $tokenUrl= "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" .$APPID ."&secret=".$AppSecret;
        $getArr=array();
        $tokenArr=json_decode($this->send_post($tokenUrl,$getArr,"GET"));
        $access_token=$tokenArr->access_token;
        //生成二维码
        $path="/pages/qrcode/qrcode?machine_code=".$machine_code;
        $width = 500;
        $data = [
            'access_token'=>$access_token,
            "scene"=>$path,
        ];
        $post_data= json_encode($data,true);
        $url="https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=".$access_token;
        $result= $this->api_notice_increment($url,$post_data);
        $time = time();
        $date = date('Y-m-d',$time );
        $filepath = ROOT_PATH."public/uploads/code/student/".$date.'/';
        if(!file_exists($filepath)){
            mkdir($filepath,0777,true);
        }
        $imagepage = '/uploads/code/student/'.$date.'/'.$time.'-'.$machine_code.'.jpg';
        file_put_contents(ROOT_PATH.'public'.$imagepage,$result);
        return $imagepage;
    }

    function send_post($url, $post_data,$method='POST') {
        $postdata = http_build_query($post_data);
        $options = array(
            'http' => array(
                'method' => $method, //or GET
                'header' => 'Content-type:application/x-www-form-urlencoded',
                'content' => $postdata,
                'timeout' => 15 * 60 // 超时时间（单位:s）
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        return $result;
    }
    
    function api_notice_increment($url,$data)
    {
        $curl = curl_init();
        $a = strlen($data);
        $header = array("Content-Type: application/json; charset=utf-8","Content-Length: $a");
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl,CURLOPT_POST,1);
        curl_setopt($curl,CURLOPT_POSTFIELDS,$data);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    
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
