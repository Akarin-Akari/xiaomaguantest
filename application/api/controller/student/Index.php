<?php

namespace app\api\controller\student;

use app\common\controller\Api;
use TencentCloud\Cdn\V20180606\Models\Cache as ModelsCache;
use think\cache;
use think\Request;
use think\Db;

/**
 * 开机流程所需接口
 */
class Index extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    private $token = 'xiaomaguan';
    // yBMvbyEUFgz1XD0laW1N5bWqbNhWt9fiJnhH8F3FUsM
    public function _initialize()
    {

    }

    /**
     * 获取活动页面的图
     */
    public function getimage(){
        $image = '/uploads/20231009/b5d15fec79a74c3a68538f2e4764c0f2.png';
        $this->success('返回成功',$image);
    }


    
    public function studentcode()
    {
        //获取token
        $APPID = 'wx7580189fba858f26';
        $AppSecret = 'dfd9ba054745cfc5a5eb815112ecb375';
        $tokenUrl= "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" .$APPID ."&secret=".$AppSecret;
        $getArr=array();
        $tokenArr=json_decode($this->send_post($tokenUrl,$getArr,"GET"));
        $access_token=$tokenArr->access_token;
        //生成二维码
        $path="/pages/signup/signup";
        $width = 500;
        $data = [
            'access_token'=>$access_token,
            "path"=>$path,
        ];
        $post_data= json_encode($data,true);
        $url="https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token=".$access_token;
        $result= $this->api_notice_increment($url,$post_data);
        $time = time();
        $date = ''.date('Y-m-d',$time );
        $filepath = ROOT_PATH."public/uploads/code/huodong/".$date.'/';
        if(!file_exists($filepath)){
            mkdir($filepath,0777,true);
        }
        $imagepage = '/uploads/code/huodong/'.$time.'-'.'.jpg';
        file_put_contents(ROOT_PATH.'public'.$imagepage,$result);
        return $imagepage;
    }

    /**
     * 活动添加报名表
     */
    public function AddStudent() {
        $params = $this->request->post();
        if(empty($params['name'])|| empty($params['school_name'])|| empty($params['phone'])){
            $this->error('参数缺失');
        }
        $where['phone'] = $params['phone'];
        $pinlv = Cache::get('sign_up'.$params['phone']);
        if($pinlv){
            $this->error('不要重复提交信息,请稍后尝试');
        }
        $a = Db::name('signup')->where($where)->find();
        if($a){
            $this->error('当前用户已报名，无需重复提交');
        }

        // var_dump($a);exit;
        $params['createtime'] = time();
        $params['cooperation_id'] = 478;
        $res = Db::name('signup')->insert($params);
        Cache::set('sign_up'.$params['phone'],1,3);
        if($res){
            $this->success('返回成功');
        }else{
            $this->error('报名失败');
        }
    }

    public function gettest()
    {
        $res = Cache::get('params');
        var_dump($res['echostr']);
    }

    public function index(){
        $params = $this->request->get();
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
            Cache::set('test1',$tmpStr);
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

}
