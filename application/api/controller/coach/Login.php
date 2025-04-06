<?php

namespace app\api\controller\coach;

use app\common\controller\Api;
use app\api\controller\wx\wxBizDataCrypt;

/**
 * 登录接口
 */
class Login extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    // protected $APPID = 'wx65ab2477b850e783';
    // protected $AppSecret = '269770e1921892000a49135a60e0c55a';
    protected $APPID = 'wxd402d8011559d15f';
    protected $AppSecret = 'dd982731500f6873c8d1c64f6c9528f1';
    protected $coach = null;
    protected $coachsc = null;
    protected $common = null;



    public function _initialize()
    {
        parent ::_initialize();
        $this->coach = new \app\admin\model\Coach;
        $this->coachsc = new \app\admin\model\Coachsc;
        $this->common = new \app\api\controller\Common;
    }

    /**
     * 第二次授权登录获取手机号
     */
    public function phone_login()
    {
        //开发者使用登陆凭证 code 获取 session_key 和 openid
        $APPID = $this->APPID;
        $AppSecret = $this->AppSecret;
        $params = $this->request->post();
        if(empty($params['code'])|| empty($params['encryptedData']) || empty($params['iv']) || empty($params['phone'])){
            $this->error('参数缺失');
        }
        $code = $params['code'];

        $url = "https://api.weixin.qq.com/sns/jscode2session?appid=" . $APPID . "&secret=" . $AppSecret . "&js_code=" . $code . "&grant_type=authorization_code";
        $arr = $this->common->curl($url);  // 一个使用curl实现的get方法请求
        
        $arr = json_decode($arr, true);
        if(empty($arr)||empty($arr['openid'])||empty($arr['session_key'])){
            $this->error('请求微信接口失败,appid或私钥不匹配！');
        }
        $openid = $arr['openid'];
        $phone = $params['phone'];

        $coach_AI = $this->coach->with(['space'])->where(['coach.phone'=>$phone])->find();
        $coach_SC = $this->coachsc->with(['space'])->where(['coachsc.phone'=>$phone])->find();

        $type = 1;
        $arr = $this->getdata($coach_AI,$coach_SC,$openid,$phone,$type);

        

        if (!$arr){
            $this->error('当前数据异常');
        } 
        if($arr){
            $this->success('返回成功', $arr);
        }else{
            $this->error('您无权进入小程序，请联系管理员');
        }
    }

    /*
    *第一次登录
    * @param $code string
    * @param $rawData string
    * @param $signatrue string
    * @param $encryptedData string
    * @param $iv string
    * @return $code 成功码
    * @return $session3rd 第三方3rd_session
    * @return $data 用户数据
    */
    public function openid_login()
    {
        //开发者使用登陆凭证 code 获取 session_key 和 openid
        $APPID = $this->APPID;//自己配置
        $AppSecret = $this->AppSecret;//自己配置
        $params = $this->request->post();
        
        if(empty($params['code'])){
            $this->error('参数缺失');
        }
        $code =  $params['code'];
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid=" . $APPID . "&secret=" . $AppSecret . "&js_code=" . $code . "&grant_type=authorization_code";
        $arr = $this->common->curl($url); // 一个使用curl实现的get方法请求
        $arr = json_decode($arr,true);
        $openid = trim($arr['openid']);
        $coach_AI = $this->coach->with(['space'])->where(['coach.openid'=>$openid])->find();
        $coach_SC = $this->coachsc->with(['space'])->where(['coachsc.openid'=>$openid])->find();
        $type = 2;
        $data['coach'] = $this->getdata($coach_AI,$coach_SC,$openid,'',$type);

        if(!$data['coach']){
            $this->success('登录成功',"");
        }
        $this->success('登陆成功',$data);
    }



    public function phone_logintest()
    {
        //开发者使用登陆凭证 code 获取 session_key 和 openid
        $APPID = $this->APPID;
        $AppSecret = $this->AppSecret;
        $params = $this->request->post();
        if(empty($params['code'])|| empty($params['encryptedData']) || empty($params['iv']) || empty($params['phone'])){
            $this->error('参数缺失');
        }
        $code = $params['code'];

        $url = "https://api.weixin.qq.com/sns/jscode2session?appid=" . $APPID . "&secret=" . $AppSecret . "&js_code=" . $code . "&grant_type=authorization_code";
        $arr = $this->common->curl($url);  // 一个使用curl实现的get方法请求
        
        $arr = json_decode($arr, true);
        if(empty($arr)||empty($arr['openid'])||empty($arr['session_key'])){
            $this->error('请求微信接口失败,appid或私钥不匹配！');
        }
        $openid = $arr['openid'];
        $phone = $params['phone'];

        $coach_AI = $this->coach->with(['space'])->where(['coach.phone'=>$phone])->find();
        $coach_SC = $this->coachsc->with(['space'])->where(['coachsc.phone'=>$phone])->find();

        $type = 1;
        $arr = $this->getdata($coach_AI,$coach_SC,$openid,$phone,$type);

        

        if (!$arr){
            $this->error('当前数据异常');
        } 
        if($arr){
            $this->success('返回成功', $arr);
        }else{
            $this->error('您无权进入小程序，请联系管理员');
        }

    }

    /*
    * 第一次登录
    * @param $code string
    * @param $rawData string
    * @param $signatrue string
    * @param $encryptedData string
    * @param $iv string
    * @return $code 成功码
    * @return $session3rd 第三方3rd_session
    * @return $data 用户数据
    */
    public function openid_logintest()
    {
        //开发者使用登陆凭证 code 获取 session_key 和 openid
        $APPID = $this->APPID;//自己配置
        $AppSecret = $this->AppSecret;//自己配置
        $params = $this->request->post();
        
        if(empty($params['code'])){
            $this->error('参数缺失');
        }
        $code =  $params['code'];
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid=" . $APPID . "&secret=" . $AppSecret . "&js_code=" . $code . "&grant_type=authorization_code";
        $arr = $this->common->curl($url); // 一个使用curl实现的get方法请求
        $arr = json_decode($arr,true);
        $openid = trim($arr['openid']);
        $coach_AI = $this->coach->with(['space'])->where(['coach.openid'=>$openid])->find();
        $coach_SC = $this->coachsc->with(['space'])->where(['coachsc.openid'=>$openid])->find();
        $type = 2;
        $data['coach'] = $this->getdata($coach_AI,$coach_SC,$openid,'',$type);

        if(!$data['coach']){
            $this->success('登录成功',"");
        }
        $this->success('登陆成功',$data);
    }

    public function logout(){
        $params = $this->request->post();

        if(empty($params['phone'])){
            $this->error('参数缺失');
        }
        $phone =  $params['phone'];
        $data['openid'] = '';
        $res = $this->coach->where('phone',$phone)->update($data);
        $res1 = $this->coachsc->where('phone',$phone)->update($data);

        if($res || $res1){
            $this->success('注销成功');
        }else{
            $this->error('登出失败');

        }
        
    }
    public function avatar() {
        $params = $this->request->post();

        if(empty($params['avatarUrl']) || empty($params['coach_id']) || empty($params['type_index'])){
            $this->error('获取头像地址失败');
        }
        $where['coach_id'] = $params['coach_id'];
        $update['avatarurl'] = $params['avatarUrl'];
        if($params['type_index'] == 'AI'){
            $this->coach->where($where)->update($update);
        }elseif($params['type_index'] == 'SC'){
            $this->coachsc->where($where)->update($update);
        }
        $this->success('返回成功');
    }

    public function test(){
        $phone = '17724649151';
        $where['coach.phone'] = $phone;
        $coach_space = $this->coach->with(['space'])->where($where)->find();
        if($coach_space){
            $coach['phone'] = $coach_space['phone'];
            $coach['name'] = $coach_space['name'];
            $coach['coach_id'] = $coach_space['coach_id'];
            $coach['space_id'] = $coach_space['space_id'];
            // $coach['openid'] = $openid;
            // $coach['period_surplus'] = $coach_space['space']['period_surplus'];
            $info['coach'] = $coach;
            // $info['openid'] = $openid;
        }else{
            $this->error('您无权进入小程序，请联系管理员');
        }
    }


    public function getdata($coach_AI,$coach_SC,$openid,$phone,$type)
    {
        $arr = [];
        $msg['openid'] = $openid;

        if($coach_AI){
            $coach = [];
            $coach['phone'] = $coach_AI['phone'];
            $coach['coach_type'] = 'AI';
            $coach['name'] = $coach_AI['name'];
            $coach['coach_id'] = $coach_AI['coach_id'];
            $coach['avatarurl'] = $coach_AI['avatarurl'];
            $coach['space_id'] = $coach_AI['space_id'];
            $coach['space_name'] = $coach_AI['space']['space_name'];
            array_push($arr,$coach);
            if($type == 1){
                $this->coach->where(['phone'=>$phone])->update($msg);
            }
        }
        if($coach_SC){
            $coach = [];
            $coach['phone'] = $coach_SC['phone'];
            $coach['coach_type'] = 'SC';
            $coach['name'] = $coach_SC['name'];
            $coach['coach_id'] = $coach_SC['coach_id'];
            $coach['avatarurl'] = $coach_AI['avatarurl'];
            $coach['space_id'] = $coach_SC['space_id'];
            $coach['space_name'] = $coach_SC['space']['space_name'];
            array_push($arr,$coach);
            if($type == 1){
                $this->coachsc->where(['phone'=>$phone])->update($msg);
            }
        }
        return $arr ;
    }
}
