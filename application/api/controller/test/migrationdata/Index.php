<?php

namespace app\api\controller\test\migrationdata;

use think\Request;

use app\common\controller\Api;


/**
 * 首页接口
 */
class Index extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        
    }
    
    protected function index(Request $request)
    {
        $signature = $request->param("signature");
        $timestamp = $request->param("timestamp");
        $nonce     = $request->param("nonce");
        // $redis->set('echostr',$echostr);
        //读取数据
        //2).处理数据
        //2.1)排序 $timestamp，$nonce，$token
        $arrayName = array($timestamp ,$nonce ,$this->token );
        sort($arrayName);
        //2.2).加密  sha1
        $temp = implode($arrayName);
        $temp = sha1($temp);
        //3).比对参数
        //客户系统判断是否来自微信
        if ($temp == $signature) {
            //告诉微信校验成功
            return $request->param('echostr');
        } else {
            return false;
        }
    }
}