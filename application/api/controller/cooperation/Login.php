<?php

namespace app\api\controller\cooperation;

use app\common\controller\Api;
use app\api\controller\wx\wxBizDataCrypt;

/**
 * 登录接口
 */
class Login extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    protected $APPID = 'wx96f1f8c75c3a2ab3';
    protected $AppSecret = 'e08692b61412af3f4f779f287e9146be';
    protected $admin = null;
    protected $authgroupaccess = null;
    protected $authgroup = null;
    protected $space = null;
    protected $order = null;
    protected $temporaryorder = null;
    protected $common = null;

    public function _initialize()
    {
        parent ::_initialize();
        $this->admin = new \app\admin\model\Admin;
        $this->authgroupaccess = new \app\admin\model\AuthGroupAccess;
        $this->authgroup = new \app\admin\model\AuthGroup;
        $this->space = new \app\admin\model\Space;
        $this->order = new \app\admin\model\Order;
        $this->temporaryorder = new \app\admin\model\Temporaryorder;

        $this->common = new \app\api\controller\Common;
    }

    /**
     * 第二次授权登录获取手机号
     */
    public function phone_login()
    {
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
        $session_key = $arr['session_key'];

        $encryptedData = $params['encryptedData'];
        $iv = $params['iv'];
        $pc = new wxBizDataCrypt($APPID, $session_key);
        $errCode = $pc->decryptData($encryptedData, $iv, $data);  //其中$data包含用户的所有数据
        $data = json_decode($data,true);

        $phone = $params['phone'];
        if (preg_match("/^1[13456789]\d{9}$/", $phone)) {
        } else {
            $this->error('手机号码格式不正确');
        }
        $where['username'] = $phone;
        $info = $this->admin->where($where)->find();
        if(!$info){
            $this->error('您无权进入小程序，请联系管理员');
        }
        $space_list['space_list'] = $this->get_space_list($info['id'],$info['pid']);
        $space_list['curator']['user_name'] = $info['nickname'];
        $space_list['curator']['user_phone'] = $info['username'];
        $space_list['curator']['user_id'] = $info['id'];
        $data = $space_list;
        $msg['openid'] = $openid;
        $this->admin->where($where)->update($msg);
        if ($errCode == 0){
            $this->success('返回成功', $data);
        } else {
            $this->error($errCode);
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
        $admin = $this->admin->where('openid',$openid)->find();
        if(!$admin){
            $data['cooperation'] = '';
        }else{
            $space_list['space_list'] = $this->get_space_list($admin['id'],$admin['pid']);
            $space_list['curator']['user_name'] = $admin['nickname'];
            $space_list['curator']['user_phone'] = $admin['username'];
            $space_list['curator']['user_id'] = $admin['id'];
            $data = $space_list;
        }
        $this->success('返回成功',$data);
    }


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
        // $openid = 'oYW7Z5CHHciEaGkTSyjj2ESNJ4Fk';
        $admin = $this->admin->where('openid',$openid)->find();
        if(!$admin){
            $data['cooperation'] = '';
        }else{
            $space_list['space_list'] = $this->get_space_list($admin['id'],$admin['pid']);
            $space_list['curator']['user_name'] = $admin['nickname'];
            $space_list['curator']['user_phone'] = $admin['username'];
            $space_list['curator']['user_id'] = $admin['id'];
            $data = $space_list;
        }
        $this->success('返回成功',$data);
    }
    
    public function get_space_list($admin_id,$pid){
        $space_list = [];
        $space_id_list = [];
        $group_id = $this->authgroupaccess->where('uid',$admin_id)->find()['group_id'];
        $auth_group = $this->authgroup->where('id',$group_id)->find();
        if($auth_group['group_type'] == '11'){
            $space = $this->space->select();
        }elseif($auth_group['group_type'] == '24'){
            $where_space['cooperation_id'] = $admin_id;
            $space = $this->space->where($where_space)->select();
        }elseif($auth_group['group_type'] == '34'){
            $where_space['space_admin_id'] = $admin_id;
            $space = $this->space->where($where_space)->select();
        }elseif($auth_group['group_type'] == '31'){
            $where_space['cooperation_id'] = $pid;
            $space = $this->space->where($where_space)->select();
        }elseif($auth_group['group_type'] == '21'){
            $space = $this->space->select();
        }else{
            $this->error('您无权进入小程序，请联系管理员');
        }
        if(!$space){
            $this->error('您无权进入小程序，请联系管理员');
        }


        foreach($space as $k=>$v){
            $space_list[$k]['period_surplus'] = $v['period_surplus'];
            $space_list[$k]['name'] = $v['space_name'];
            $space_list[$k]['longitude'] = $v['lng'];
            $space_list[$k]['latitude'] = $v['lat'];
            $space_list[$k]['space_id'] = $v['id'];
            $space_list[$k]['study_total'] = $this->get_study_total($v['id']);
            array_push($space_id_list,$v['id']);
        }
        $data['space_list'] = $space_list;
        $data['space_id_list'] = $space_id_list;
        
        return $data;
    }

    /**
     * 登出
     */
    public function logout(){
        $params = $this->request->post();
        if(empty($params['phone'])){
            $this->error('参数缺失');
        }
        $phone = $params['phone'];
        $data['openid'] = '';
        $curator = $this->admin->where('username',$phone)->update($data);
        if($curator){
            $this->success('注销成功');
        }
        $this->error('注销失败');
        
    }

    /**
     * 获取累计使用小时
     */
    public function get_study_total($space_id){

        $where_order1['space_id'] = ['in',$space_id];
        $where_order1['order_status'] = 'finished';
        $order1 = $this->order->where($where_order1)->count();
        $where_order2['space_id'] = ['in',$space_id];
        $where_order2['order_status'] ='finished';
        $order2 = $this->temporaryorder->where($where_order2)->count();
        $total = ( $order1+$order2)*2 ;
        return $total;
    }

    public function logintest()
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
        $session_key = $arr['session_key'];

        $encryptedData = $params['encryptedData'];
        $iv = $params['iv'];
        $pc = new wxBizDataCrypt($APPID, $session_key);
        $errCode = $pc->decryptData($encryptedData, $iv, $data);  //其中$data包含用户的所有数据
        $data = json_decode($data,true);

        $phone = $params['phone'];
        if (preg_match("/^1[13456789]\d{9}$/", $phone)) {
        } else {
            $this->error('手机号码格式不正确');
        }
        $where['username'] = $phone;
        $info = $this->admin->where($where)->find();
        if(!$info){
            $this->error('您无权进入小程序，请联系管理员');
        }
        $space_list['space_list'] = $this->get_space_list($info['id'],$info['pid']);
        $space_list['curator']['user_name'] = $info['nickname'];
        $space_list['curator']['user_phone'] = $info['username'];
        $space_list['curator']['user_id'] = $info['id'];
        $data = $space_list;
        $msg['openid'] = $openid;
        $this->admin->where($where)->update($msg);
        if ($errCode == 0){
            $this->success('返回成功', $data);
        } else {
            $this->error($errCode);
        }
    }
    public function car_login_test()
    {
        $params['phone'] = '18689477027';

        $phone = $params['phone'];
        $where['username'] = $phone;
        $info = $this->admin->where($where)->find();
        if(!$info){
            $this->error('您无权进入小程序，请联系管理员');
        }
        $space_list['space_list'] = $this->get_space_listtest($info['id'],$info['pid']);
        $space_list['curator']['user_name'] = $info['nickname'];
        $space_list['curator']['user_phone'] = $info['username'];
        $space_list['curator']['user_id'] = $info['id'];
        $data = $space_list;
    }


    public function get_space_listtest($admin_id,$pid){
        $space_list = [];
        $space_id_list = [];
        $group_id = $this->authgroupaccess->where('uid',$admin_id)->find()['group_id'];
        $auth_group = $this->authgroup->where('id',$group_id)->find();
        if($auth_group['group_type'] == '11'){
            $where_space['space_type'] = 'ai_car';
            $space = $this->space->where($where_space)->select();
        }elseif($auth_group['group_type'] == '24'){
            $where_space['space_type'] = 'ai_car';
            $where_space['cooperation_id'] = $admin_id;
            $space = $this->space->where($where_space)->select();
        }elseif($auth_group['group_type'] == '34'){
            $where_space['space_type'] = 'ai_car';
            $where_space['space_admin_id'] = $admin_id;
            $space = $this->space->where($where_space)->select();
        }elseif($auth_group['group_type'] == '31'){
            $where_space['space_type'] = 'ai_car';
            $where_space['cooperation_id'] = $pid;
            $space = $this->space->where($where_space)->select();
        }elseif($auth_group['group_type'] == '21'){
            $where_space['space_type'] = 'ai_car';
            $space = $this->space->where($where_space)->select();
        }else{
            $this->error('您无权进入小程序，请联系管理员');
        }
        if(!$space){
            $this->error('您无权进入小程序，请联系管理员');
        }


        foreach($space as $k=>$v){
            $space_list[$k]['period_surplus'] = $v['period_surplus'];
            $space_list[$k]['name'] = $v['space_name'];
            $space_list[$k]['space_id'] = $v['id'];
            $space_list[$k]['study_total'] = $this->get_study_total($v['id']);
            array_push($space_id_list,$v['id']);
        }
        $data['space_list'] = $space_list;
        $data['space_id_list'] = $space_id_list;
        
        return $data;
    }

}
