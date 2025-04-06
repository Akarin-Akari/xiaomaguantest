<?php

namespace app\api\controller\recommender;

use app\common\controller\Api;
use app\api\controller\wx\wxBizDataCrypt;

/**
 * 登录接口
 */
class Login extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    protected $recommender = null;
    protected $authgroupaccess =  null;
    protected $authgroup = null;
    protected $common =  null;
    protected $intentstudent =  null;
    protected $student =  null;
    protected $space =  null;
    protected $admin =  null;
    
    protected $APPID = 'wx150f6a4b1d19145f';
    protected $AppSecret = 'e060d82328c2b9aa3185b349c2b99151';

    public function _initialize()
    {
        parent::_initialize();
        $this->recommender = new \app\admin\model\Recommender;
        $this->intentstudent = new \app\admin\model\intent\Student;
        $this->student = new \app\admin\model\Student;
        $this->space = new \app\admin\model\Space;
        $this->admin = new \app\admin\model\Admin;
        $this->authgroupaccess = new \app\admin\model\AuthGroupAccess;
        $this->authgroup = new \app\admin\model\AuthGroup;
        $this->common = new \app\api\controller\Common;
    }

    /**
     * 第二次授权登录获取手机号
     */
    public function phone_login()
    {
        $params = $this->request->post();
        //开发者使用登陆凭证 code 获取 session_key 和 openid
        $APPID = $this->APPID;
        $AppSecret = $this->AppSecret;
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
        $session_key = $arr['session_key'];
        if(!array_key_exists('openid',$arr)){
            $this->error('openid错误');
        }
        $openid = $arr['openid'];
        $encryptedData = $params['encryptedData'];
        $iv = $params['iv'];
        $pc = new wxBizDataCrypt($APPID, $session_key);
        $errCode = $pc->decryptData($encryptedData, $iv, $data);  //其中$data包含用户的所有数据
        $data = json_decode($data,true);
        $phone = $params['phone'];
        $where['recommender.phone'] = $phone;
        $recommender = $this->recommender->with('admin')->where($where)->find();

        if($recommender){
            $pid = $recommender['admin']['pid'];
            $group_id = $this->authgroupaccess->where('uid',$pid)->find()['group_id'];
            // $group = $this->authgroup->where('id',$group_id)->find();
            // $group_type = $group['group_type'];
            if($group_id == 6 ||  $group_id == 9){
                $info['space_id'] = $recommender['space_id'];
                $info['phone'] = $recommender['phone'];
                $info['recommender_id'] = $recommender['id'];
                $info['name'] = $recommender['name'];
                $info['leader'] = $recommender['leader'];
                $info['createtime'] = $recommender['createtime'];
                $info['sex'] = $recommender['sex'];
                $info['id'] = $recommender['id'];
                $info['cooperation_id'] = $recommender['cooperation_id'];
                $info['message_status'] = $recommender['message_status'];
                $info['card_type'] = $recommender['card_type'];
                $data['recommender'] = $info;
                $data['recommender'] = $info;
                
                $msg['openid'] = $openid;
                $recommender->save($msg);
                if ($errCode == 0){
                    $this->success('返回成功', $data);
                }else{
                    $this->error($errCode);
                }
            }else{
                $this->error('获取参数有误');
            }
        }else{
            $this->error('您无权进入小程序，请联系管理员');
        }
    }


    public function phone_logintest()
    {
        $params = $this->request->post();
        //开发者使用登陆凭证 code 获取 session_key 和 openid
        $APPID = $this->APPID;
        $AppSecret = $this->AppSecret;
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
        $session_key = $arr['session_key'];
        if(!array_key_exists('openid',$arr)){
            $this->error('openid错误');
        }
        $openid = $arr['openid'];
        $encryptedData = $params['encryptedData'];
        $iv = $params['iv'];
        $pc = new wxBizDataCrypt($APPID, $session_key);
        $errCode = $pc->decryptData($encryptedData, $iv, $data);  //其中$data包含用户的所有数据
        $data = json_decode($data,true);
        $phone = $params['phone'];
        $where['recommender.phone'] = $phone;
        $recommender = $this->recommender->with('admin')->where($where)->find();

        if($recommender){
            $pid = $recommender['admin']['pid'];
            $group_id = $this->authgroupaccess->where('uid',$pid)->find()['group_id'];
            // $group = $this->authgroup->where('id',$group_id)->find();
            // $group_type = $group['group_type'];
            if($group_id == 6 ||  $group_id == 9){
                $info['space_id'] = $recommender['space_id'];
                $info['phone'] = $recommender['phone'];
                $info['recommender_id'] = $recommender['id'];
                $info['name'] = $recommender['name'];
                $info['leader'] = $recommender['leader'];
                $info['createtime'] = $recommender['createtime'];
                $info['sex'] = $recommender['sex'];
                $info['id'] = $recommender['id'];
                $info['cooperation_id'] = $recommender['cooperation_id'];
                $info['message_status'] = $recommender['message_status'];
                $info['card_type'] = $recommender['card_type'];

                $data['recommender'] = $info;
                
                $msg['openid'] = $openid;
                $recommender->save($msg);
                if ($errCode == 0){
                    $this->success('返回成功', $data);
                }else{
                    $this->error($errCode);
                }
            }else{
                $this->error('获取参数有误');
            }
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
        if(!array_key_exists('openid',$arr)){
            $this->error('openid错误');
        }
        $openid = trim($arr['openid']);
        $recommender = $this->recommender->with('admin')->where('recommender.openid',$openid)->find();
        if(!$recommender){
            $data['status'] = 2;
            $data['recommender'] = '';
        }else{
            $data['status'] = 1;
            $pid = $recommender['admin']['pid'];
            $group  = $this->authgroupaccess->where(['uid'=>$pid])->find();

            if($group['group_id'] ==  6 || $group['group_id']== 9){
                $info['space_id'] = $recommender['space_id'];
                $info['phone'] = $recommender['phone'];
                $info['recommender_id'] = $recommender['id'];
                $info['name'] = $recommender['name'];
                $info['leader'] = $recommender['leader'];
                $info['createtime'] = $recommender['createtime'];
                $info['sex'] = $recommender['sex'];
                $info['id'] = $recommender['id'];
                $info['cooperation_id'] = $recommender['cooperation_id'];
                $info['message_status'] = $recommender['message_status'];
                $info['card_type'] = $recommender['card_type'];
                $data['recommender'] = $info;
            }else{
                $this->error('获取参数有误');
            }
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
        $res = $this->recommender->where('phone',$phone)->update($data);
        if($res){
            $this->success('注销成功');
        }
        $this->error('注销失败');
    }

    /**
     * 获取我的页面统计数据
     */
    public function get_my_total(){
        $params = $this->request->post();
        $recommender_id = $params['recommender_id'];
        $group_recommender = $this->get_recommender_leader($recommender_id);
        $date = $this->common->get_time();
        $month = strtotime($date['month']);
        $day = strtotime($date['day']);
        $month_firstday = strtotime($date['last_month']['firstday']);
        $month_lastday = strtotime($date['last_month']['lastday']);
        $data['month_intent_count'] = $this->get_intent_count($month,time(),$recommender_id);//意向学员本月
        $data['last_month_intent_count'] = $this->get_intent_count($month_firstday,$month_lastday,$recommender_id);//意向学员上个月
        $data['month_student_count'] = $this->get_student_count($month,time(),$recommender_id);//正式学员本月
        $data['last_month_student_count'] = $this->get_student_count($month_firstday,$month_lastday,$recommender_id);//正式学员本月
        
        $data['group_month_intent_count'] = $this->get_intent_count($month,time(),$group_recommender);//本组销售意向学员本月
        $data['group_last_month_intent_count'] = $this->get_intent_count($month_firstday,$month_lastday,$group_recommender);//本组销售意向学员上个月
        $data['group_month_student_count'] = $this->get_student_count($month,time(),$group_recommender);//本组销售正式学员本月
        $data['group_last_month_student_count'] = $this->get_student_count($month_firstday,$month_lastday,$group_recommender);//本组销售正式学员本月
        if($data['last_month_intent_count'] >0  && $data['last_month_student_count'] > 0  &&$data['group_last_month_intent_count']>0 && $data['group_last_month_intent_count']>0){
            $data['person_intent_growth_rate'] =  ($data['month_intent_count'] - $data['last_month_intent_count']) / $data['last_month_intent_count'];
            $data['person_intent_growth_rate']  = round($data['person_intent_growth_rate'],4);
            $data['person_student_growth_rate'] =  ($data['month_student_count'] -$data['last_month_student_count']) / $data['last_month_student_count'];
            $data['person_student_growth_rate']  = round($data['person_student_growth_rate'],4);
            $data['group_intent_growth_rate'] =  ($data['group_month_intent_count'] -$data['group_last_month_intent_count'] )/ $data['group_last_month_intent_count'];
            $data['group_intent_growth_rate']  = round($data['group_intent_growth_rate'],4);
            $data['group_student_growth_rate'] =  ($data['group_month_student_count'] -$data['group_last_month_student_count']) / $data['group_last_month_student_count'];
            $data['group_student_growth_rate']  = round($data['group_student_growth_rate'],4);
        }
        // var_dump($data);exit;
        $this->success('返回成功',$data);
    }

    public function get_recommender_leader($recommender_id)
    {
        $space_id = $this->recommender->where('id',$recommender_id)->find()['space_id'];
        $recommender = $this->recommender->where('space_id',$space_id)->select();
        $group_recommender = [];
        foreach($recommender as $v){
            array_push($group_recommender,$v['id']);
        }
        return $group_recommender;
    }

    /**
     * 意向学员统计
     */
    public function get_intent_count($starttime,$endtime,$recommender_id)
    {
        // var_dump($starttime,$endtime);exit;
        $where['createtime'] = ['between',[$starttime,$endtime]];
        $where['follower'] = ['in',$recommender_id];
        $count = $this->intentstudent->where($where)->count();
        return $count;
    }

    /**
     * 正式学员统计
     */
    public function get_student_count($starttime,$endtime,$recommender_id)
    {
        $where['registtime'] = ['between',[$starttime,$endtime]];
        $where['follower'] = ['in',$recommender_id];
        $count = $this->student->where($where)->count();
        return $count;
    }

    /**
     * 销售归属场馆
     */
    public function recommender_get_space_id($source_id){
        $data = [];
        $group_id = $this->authgroupaccess->where('uid',$source_id)->find();
        $group_type = $this->authgroup->where('id',$group_id['group_id'])->find()['group_type'];
        if($group_type == 2){
            $data['space_id'] = '';
            $data['cooperation_id'] = $source_id;
        }
        if($group_type == 4){
            $space = $this->space->where('space_admin_id',$source_id)->find();
            $data['space_id'] = $space['id'];
            $data['cooperation_id'] = $space['cooperation_id'];
        }
        return $data;
    }

    public function test_carlogin()
    {
        $APPID = $this->APPID;//自己配置
        $AppSecret = $this->AppSecret;//自己配置
        // $params = $this->request->post();
        $params['code'] = '041VtHFa1lzK8B06JJHa1bG57e1VtHFY';
        // if(empty($params['code'])){
        //     $this->error('参数缺失');
        // }
        $code =  $params['code'];
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid=" . $APPID . "&secret=" . $AppSecret . "&js_code=" . $code . "&grant_type=authorization_code";
        $arr = $this->common->curl($url); // 一个使用curl实现的get方法请求
        $arr = json_decode($arr,true);
        $openid = trim($arr['openid']);
        // $where['recommender.openid']
        $recommender = $this->recommender->with('admin')->where('recommender.openid',$openid)->find();

        if(!$recommender){
            $data['recommender'] = '';
        }else{
            $pid = $recommender['admin']['pid'];
            $group_id = $this->authgroupaccess->where('uid',$pid)->find()['group_id'];
            $group = $this->authgroup->where('id',$group_id)->find();
            $group_type = $group['group_type'];
            if($group_type == '34'){
                $pid2 = $this->admin->where('id',$pid)->find()['pid'];
                $info['cooperation_id'] = $pid2;
                $info['space_id'] = $pid;
                $info['phone'] = $recommender['phone'];
                $info['name'] = $recommender['name'];
                $info['leader'] = $recommender['leader'];
                $data['recommender'] = $info;
            }else{
                $this->error('获取参数有误');
            }
        }
        $this->success('登陆成功',$data);
    }
    
}
