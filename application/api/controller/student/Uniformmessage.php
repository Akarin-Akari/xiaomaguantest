<?php

namespace app\api\controller\student;

use app\common\controller\Api;
use think\Cache;
/**
 * 首页接口
 */
class Uniformmessage extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    protected $APPID = 'wxbb51120daa7b619d';
    protected $AppSecret = '276949bc4ca519100ded7867e9f986ef';
    
    public function _initialize()
    {
        parent::_initialize();
        $this->common = new \app\api\controller\Common;
    }

    public function message_send($stu_id,$name,$course,$content,$openid)
    {
        // $name = '';
        // $phone = '';
        // $content = '';
        // $stu_id = '';
        // Cache::set('message_send_test',$stu_id,60*10);
        $appid=$this->APPID;
        $appsecret=$this->AppSecret;
        $tokenUrl= "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" .$appid ."&secret=".$appsecret;
        $getArr=array();
        $tokenArr=json_decode($this->common->send_post($tokenUrl,$getArr,"GET"));
        $access_token=$tokenArr->access_token;
        // $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/uniform_send?access_token='.$access_token;
        $url = 'https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token='.$access_token;
        $data = array(
            // 'access_token' =>$access_token,
            // 'touser' => 'oNOCU4o31qzdsJy2EMD6AdQ0x-ms', //发给谁
            // 'weapp_template_msg' => array(
            //     // 'appid' => 'wxf0153ab079e6c0dd',
            //     'template_id' => '82pkueHo4X5W6Uj9WVhPZHJIO0e0WCjifYdx2ZfMFlw',
            //     'page' => 'page/page/index',
            //     'form_id' =>'492',
            //     'emphasis_keyword'=>'消息通知',
            //     'data' => ['time3'=>'2023年2月16日 19:30','thing11'=>'/index/index','character_string15'=>'备注'],
            // )
            'access_token' =>$access_token,
            'template_id' => '9D-E7d93U9U_ZnvhZB8Apnzi81wbfCKRgAH_2Peh81M',
            'page'=>'pages/promoter_detail/promoter_detail?stu_id='.$stu_id,
            'touser' => $openid, //发给谁
            'data' => ['name3'=>['value'=>$name],'thing1'=>['value'=>$course],'thing2'=>['value'=>$content]],
            'miniprogram_state'=>'formal',
            'lang'=> 'zh_CN'
        );
        $data = json_encode($data);
        $result = $this->curl_post($url, $data);
        Cache::set('result_test',$result,60*10);
        $result = json_decode($result);
        if ($result->errcode == '0' && $result->errmsg == 'ok') {
            // echo $result;
            return true;
        } else {
            return false;
        }
    }

    
    function curl_post($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $response = curl_exec($ch);
        curl_close($ch);
        // echo $response;
        return $response;
    }

}