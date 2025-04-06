<?php

namespace app\api\controller\student;

use app\common\controller\Api;
use app\api\controller\wx\wxBizDataCrypt;
use think\Cache;
use think\Db;
use think\Request;

/**
 * 首页接口
 */
class Login extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    protected $APPID = 'wx71900f694b91a16c';
    protected $AppSecret = 'a2bbbd633cb79ca40a3bc4e211aa744e';
    protected $student = null;
    protected $intentstudent = null;
    protected $common = null;
    protected $cooperation = null;
    protected $space = null;
    protected $coach = null;
    protected $coachsc = null;
    protected $admin = null;
    protected $temporaryorder = null;
    protected $studyprocess = null;
    protected $reportcard = null;
    protected $keersort = null;
    protected $kesansort = null;
    protected $faceimage = null;
    protected $device = null;
    
    const ICON = '/uploads/20240118/b880a3c188cb1ab74cb06d38be8f7321.png';

    public function _initialize()
    {
        parent::_initialize();
        $this->student = new \app\admin\model\Student;
        $this->intentstudent = new \app\admin\model\Intentstudent;
        $this->common = new \app\api\controller\Common;
        $this->cooperation = new \app\admin\model\Cooperation;
        $this->space = new \app\admin\model\Space;
        $this->coach = new \app\admin\model\Coach;
        $this->coachsc = new \app\admin\model\Coachsc;
        $this->admin = new \app\admin\model\Admin;
        $this->temporaryorder = new \app\admin\model\Temporaryorder;
        $this->studyprocess = new \app\admin\model\Studyprocess;
        $this->reportcard = new \app\admin\model\Reportcard;
        $this->keersort = new \app\admin\model\Keersort;
        $this->kesansort = new \app\admin\model\Kesansort;
        $this->device = new \app\admin\model\Device;
        
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
        // $vx_image =  $params['vx_image'];
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

        $where['phone'] = $phone;
        //判断是否是馆长以及教员
        $this->phone_validate($phone);
        $student = $this->student->where($where)->find();
        if(!$student){
            $student = $this->intentstudent->where($where)->find();
            if(!$student){
                $student = $this->add_student($openid,$phone);
            }
            $student['student_type'] = 'intent_student';
            $student['subject2_total'] = $this->get_subject_total('temporary_order',$student['stu_id'],'subject2');
            $student['subject3_total'] = $this->get_subject_total('temporary_order',$student['stu_id'],'subject3');
            
            $data['student'] = $student;
            $msg['openid'] = $openid;
            // $student['icon'] = '';
            $student['icon'] = self::ICON;
            $student['reserve_day'] = 0;
            // $msg['vx_image'] = $vx_image;
            $this->intentstudent->where($where)->update($msg);
        }else{
            $data['phone'] = $phone;
            $student['subject2_total'] = $this->get_subject_total('order',$student['stu_id'],'subject2');
            $student['subject3_total'] = $this->get_subject_total('order',$student['stu_id'],'subject3');
            $student['student_type'] = 'student';
            
            $where_coo['cooperation_id'] = $student['cooperation_id'];
            $icon = $this->cooperation->where($where_coo)->find();
            if(!$icon){
                // $student['icon'] = '/uploads/20231129/b880a3c188cb1ab74cb06d38be8f7321.png';
                $student['icon'] = self::ICON;
                $student['reserve_day'] = 0;
            }else{
                $student['icon'] = $icon['icon_image'];
                $student['reserve_day'] = $icon['reserve_day'];
            }
            $data['student'] = $student;
            
            $msg['openid'] = $openid;
            // $msg['vx_image'] = $vx_image;
            $this->student->where($where)->update($msg);
        }
       
        if ($errCode == 0) {
            $this->success('返回成功', $data);
        } else {
            $this->error($errCode);
        }
    } 

    public function openid_logintest()
    {
        //开发者使用登陆凭证 code 获取 session_key 和 openid
        $APPID = $this->APPID;//自己配置
        $AppSecret = $this->AppSecret;//自己配置
        $params = $this->request->post();
        if($params){
            $code =  $params['code'];
            // $vx_image = $params['image'];
            $url = "https://api.weixin.qq.com/sns/jscode2session?appid=" . $APPID . "&secret=" . $AppSecret . "&js_code=" . $code . "&grant_type=authorization_code";
            $arr = $this->common->curl($url); // 一个使用curl实现的get方法请求
            $arr = json_decode($arr,true);
            $openid = trim($arr['openid']);
            //判断是否是馆长以及教员
            $this->openid_validate($openid);
            $student = $this->student->where('openid',$openid)->find();

            if(!$student){
                $intent_student = $this->intentstudent->where('openid',$openid)->find();
                if(!$intent_student){
                    $data['status'] = 2;
                    $data['openid'] = $openid;
                }else{
                    $data['status'] = 1;
                    $student = $this->student->where('phone',$intent_student['phone'])->find();
                    if($student){
                        $student['student_type'] = 'student';
                        $where_coo['cooperation_id'] = $student['cooperation_id'];
                        $this->student->where('phone',$intent_student['phone'])->update(['openid'=>$openid]);
                        $icon = $this->cooperation->where($where_coo)->find();
                        if(!$icon){
                            // $student['icon'] = '/uploads/20220223/5b397bc62be7d5ea4e8d3b6b8025beb5.png';
                            $student['icon'] = self::ICON;
                            $student['reserve_day'] = 0;
                        }else{
                            $student['icon'] = $icon['icon_image'];
                            $student['reserve_day'] = $icon['reserve_day'];
                        }
                        $student['subject2_total'] = $this->get_subject_total('order',$student['stu_id'],'subject2');
                        $student['subject3_total'] = $this->get_subject_total('order',$student['stu_id'],'subject3');
                    }else{
                        $student = $intent_student;
                        $student['student_type'] = 'intent_student';
                        $student['subject2_total'] = $this->get_subject_total('temporary_order',$student['stu_id'],'subject2');
                        $student['subject3_total'] = $this->get_subject_total('temporary_order',$student['stu_id'],'subject3');
                        $student['icon'] = self::ICON;
                        // $student['icon'] = '/uploads/20231129/b880a3c188cb1ab74cb06d38be8f7321.png';
                        $student['reserve_day'] = 0;
                    }
                }
            }else{
                $data['status'] = 1;
                $data['student'] = $student;
                $student['student_type'] = 'student';
                // $msg['vx_image'] = $vx_image;
                $where_coo['cooperation_id'] = $student['cooperation_id'];
                $icon = $this->cooperation->where($where_coo)->find();
                if(!$icon){
                    $student['icon'] = self::ICON;
                    // $student['icon'] = '/uploads/20231129/b880a3c188cb1ab74cb06d38be8f7321.png';
                    $student['reserve_day'] = 0;
                }else{
                    $student['icon'] = $icon['icon_image'];
                    $student['reserve_day'] = $icon['reserve_day'];
                }
                $student['subject2_total'] = $this->get_subject_total('order',$student['stu_id'],'subject2');
                $student['subject3_total'] = $this->get_subject_total('order',$student['stu_id'],'subject3');
                // $this->student->where('openid',$openid)->update($msg);
            }
            $data['student'] = $student;
            $this->success('返回成功',$data);
        }else{
            $this->error('返回失败，缺少参数');
        }
    }

    public function GetStudentInfo($student)
    {
        if($student['student_type'] == 'student'){
            $info['car_type'] = $student['car_type'];
            $info['car_type_text'] = $student['car_type_text'];
            $info['name'] = $student['name'];
            $info['phone'] = $student['phone'];
            $info['stu_id'] = $student['stu_id'];
            $info['sex'] = $student['sex'];
            $info['period_surplus'] = $student['period_surplus'];
            $info['cooperation_id'] = $student['cooperation_id'];
            $info['space_id'] = $student['space_id'];
            $info['student_type'] = $student['student_type'];
            $info['space_name'] = $student['space']['space_name'];
            $info['subject2_total'] = $this->get_subject_total('order',$student['stu_id'],'subject2');
            $info['subject3_total'] = $this->get_subject_total('order',$student['stu_id'],'subject3');
        }else{
            $info['car_type'] = $student['car_type'];
            $info['car_type_text'] = $student['car_type_text'];
            $info['phone'] = $student['phone'];
            $info['stu_id'] = $student['stu_id'];
            $info['name'] = '临时学员';
            $info['sex'] = $student['sex'];
            $info['period_surplus'] = 0;
            $info['cooperation_id'] = '';
            $info['space_id'] = '';
            $info['student_type'] = $student['student_type'];
            $info['space_name'] = '无绑定场馆';
            $info['subject2_total'] = $this->get_subject_total('temporary_order',$student['stu_id'],'subject2');
            $info['subject3_total'] = $this->get_subject_total('temporary_order',$student['stu_id'],'subject3');
        }
        return $info;
    }

    

    /*
    *第一次登录，通过openid登陆
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
        if($params){
            $code =  $params['code'];
            // $vx_image = $params['image'];
            $url = "https://api.weixin.qq.com/sns/jscode2session?appid=" . $APPID . "&secret=" . $AppSecret . "&js_code=" . $code . "&grant_type=authorization_code";
            $arr = $this->common->curl($url); // 一个使用curl实现的get方法请求
            $arr = json_decode($arr,true);

            $openid = trim($arr['openid']);
            //判断是否是馆长以及教员
            $this->openid_validate($openid);

            $student = $this->student->where('openid',$openid)->find();
            if(!$student){
                $intent_student = $this->intentstudent->where('openid',$openid)->find();
                if(!$intent_student){
                    $data['status'] = 2;
                    $data['openid'] = $openid;
                }else{
                    $data['status'] = 1;
                    $student = $this->student->where('phone',$intent_student['phone'])->find();

                    if($student){
                        $student['student_type'] = 'student';
                        $where_coo['cooperation_id'] = $student['cooperation_id'];
                        $update_openid['openid'] = $openid;
                        
                        $res = $this->student->where('phone',$intent_student['phone'])->update($update_openid);
                        $icon = $this->cooperation->where($where_coo)->find();
                        if(!$icon){
                            
                            // $student['icon'] = '/uploads/20231129/b880a3c188cb1ab74cb06d38be8f7321.png';
                            $student['icon'] = self::ICON;
                            $student['reserve_day'] = 0;
                        }else{
                            $student['icon'] = $icon['icon_image'];
                            $student['reserve_day'] = $icon['reserve_day'];
                        }
                        $student['subject2_total'] = $this->get_subject_total('order',$student['stu_id'],'subject2');
                        $student['subject3_total'] = $this->get_subject_total('order',$student['stu_id'],'subject3');
                    }else{
                        $student = $intent_student;
                        $student['student_type'] = 'intent_student';
                        $student['subject2_total'] = $this->get_subject_total('temporary_order',$student['stu_id'],'subject2');
                        $student['subject3_total'] = $this->get_subject_total('temporary_order',$student['stu_id'],'subject3');
                        // $student['icon'] = '/uploads/20220223/5b397bc62be7d5ea4e8d3b6b8025beb5.png';
                        $student['icon'] = self::ICON;
                        $student['reserve_day'] = 0;
                        $student['name'] = '临时学员';
                    }
                }
            }else{
                $data['status'] = 1;
                $data['student'] = $student;
                $student['student_type'] = 'student';
                // $msg['vx_image'] = $vx_image;
                $where_coo['cooperation_id'] = $student['cooperation_id'];
                $icon = $this->cooperation->where($where_coo)->find();
                if(!$icon){
                    // $student['icon'] = '/uploads/20231129/b880a3c188cb1ab74cb06d38be8f7321.png';
                    $student['icon'] = self::ICON;
                    $student['reserve_day'] = 0;
                }else{
                    $student['icon'] = $icon['icon_image'];
                    $student['reserve_day'] = $icon['reserve_day'];
                }
                $student['subject2_total'] = $this->get_subject_total('order',$student['stu_id'],'subject2');
                $student['subject3_total'] = $this->get_subject_total('order',$student['stu_id'],'subject3');
                // $this->student->where('openid',$openid)->update($msg);
            }
            $data['student'] = $student;
            $this->success('返回成功',$data);
        }else{
            $this->error('返回失败，缺少参数');
        }
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
        // $vx_image =  $params['vx_image'];
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

        $where['phone'] = $phone;
        //判断是否是馆长以及教员
        $this->phone_validate($phone);
        $student = $this->student->where($where)->find();
        if(!$student){
            $student = $this->intentstudent->where($where)->find();
            if(!$student){
                $student = $this->add_student($openid,$phone);
            }
            $student['student_type'] = 'intent_student';
            $student['subject2_total'] = $this->get_subject_total('temporary_order',$student['stu_id'],'subject2');
            $student['subject3_total'] = $this->get_subject_total('temporary_order',$student['stu_id'],'subject3');
            $student['name'] = '临时学员';

            $data['student'] = $student;
            $msg['openid'] = $openid;
            // $student['icon'] = '/uploads/20231129/b880a3c188cb1ab74cb06d38be8f7321.png';
            $student['icon'] = self::ICON;
            $student['reserve_day'] = 0;
            // $msg['vx_image'] = $vx_image;
            $this->intentstudent->where($where)->update($msg);
        }else{
            $data['phone'] = $phone;
            $student['subject2_total'] = $this->get_subject_total('order',$student['stu_id'],'subject2');
            $student['subject3_total'] = $this->get_subject_total('order',$student['stu_id'],'subject3');
            $student['student_type'] = 'student';
            
            $where_coo['cooperation_id'] = $student['cooperation_id'];
            $icon = $this->cooperation->where($where_coo)->find();
            if(!$icon){
                // $student['icon'] = '/uploads/20231129/b880a3c188cb1ab74cb06d38be8f7321.png';
                $student['icon'] = self::ICON;
                $student['reserve_day'] = 0;
            }else{
                $student['icon'] = $icon['icon_image'];
                $student['reserve_day'] = $icon['reserve_day'];
            }
            $data['student'] = $student;
            
            $msg['openid'] = $openid;
            // $msg['vx_image'] = $vx_image;
            $this->student->where($where)->update($msg);
        }
       
        if ($errCode == 0) {
            $this->success('返回成功', $data);
        } else {
            $this->error($errCode);
        }
    }

    /**
     * 获取学员牌
     */
    public function get_certificate()
    { 
        $params = $this->request->post();

        if(empty($params['stu_id'])){
            $this->error('参数缺失');
        }
        $list = [];
        $student = $this->student->where(['stu_id'=>$params['stu_id']])->find();
        $pay_cooperation = 0;
        
        $where_coo['cooperation_id'] = $student['cooperation_id'];
        $coo = $this->cooperation->where($where_coo)->find();
        $device = $this->device->where($where_coo)->find();

        if($coo && $device){
            $pay_cooperation = $coo['pay_cooperation'];
        }
        $coach_sc_keer = $this->coachsc->where(['coach_id'=>$student['coach_sc_keer']])->find();
        $coach_sc_kesan = $this->coachsc->where(['coach_id'=>$student['coach_sc_kesan']])->find();
        $course = Db::name('course_log')->where(['id'=>$student['course_id']])->find();
        $process = Db::name('process')->where(['id'=>$student['process']])->find();
        $keer_report = Db::name('report_card')->where(['stu_id'=>$student['stu_id'],'subject_type'=>'subject2','score'=>['>=',80]])->count();
        $kesan_report = Db::name('report_card')->where(['stu_id'=>$student['stu_id'],'subject_type'=>'subject3','score'=>['>=',90]])->count();
        $pay = $this->get_pay($coo,$coo['cooperation_id']);
        $list['caoch_sc_keer'] = $coach_sc_keer['name'];
        $list['caoch_sc_kesan'] = $coach_sc_kesan['name'];
        $list['course'] = $course['course'];
        $list['process'] = $process['process_name'];
        $list['keer_report'] = $keer_report;
        $list['kesan_report'] = $kesan_report;
        $list['contract_state'] = $student['contract_state'];
        $list['contract_path'] = $student['contract_path'];
        $list['tuition'] = $student['tuition'];
        $list['payment_process'] = $student['payment_process'];
        $list['pay_cooperation'] = $pay_cooperation;
        $list['pay'] = $pay;

        $this->success('返回成功',$list);
    }


    /**
     * 登出
     */
    public function logout(){
        $params = $this->request->post();
        if(empty($params['student_type'])|| empty($params['phone'])){
            $this->error('参数缺失');
        }
        if($params){
            $phone =  $params['phone'];
            $data['openid'] = '';
            if($params['student_type'] == 'student'){
                $res = $this->student->where('phone',$phone)->update($data);
            }else{
                $res = $this->intentstudent->where('phone',$phone)->update($data);
            }
            if($res){
                $this->success('登出成功');
            }else{
                $this->error('登出失败');
            }
        }else{
            $this->error('返回失败，参数错误');
        }
    }


    
    public function get_pay($coo,$cooperation_id){
        $pay = $this->device->where(['cooperation_id'=>$cooperation_id])->find();

        $pay['subject_img'] = 'https://xiaomaguan.com/uploads/20231129/b880a3c188cb1ab74cb06d38be8f7321.png';
        if($coo){
            $pay['subject_img'] = 'https://xiaomaguan.com'.$coo['icon_image'];
        }
        // $space_machine['pay']['total_amount'] = '';
        // $space_machine['pay']['terminal_sn'] = '';
        $pay['client_sn'] = $this->getPayId();
        
        return $pay;
    }


    public function getPayId()
    {
        $str = date('Ymd').substr(implode('', array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8); 
        return $str;
    }
    

    public function add_student($openid,$phone)
    {
        $student['stu_id'] = 'CSN'.date("YmdHis") . mt_rand(100000, 999999);
        $student['openid'] = $openid;
        $student['phone'] = $phone;
        if($student['phone'] =='' || $student['openid']==''){
            $this->error('登陆信息错误');
        }
        $this->intentstudent->save($student);
        $res = $this->intentstudent->where('stu_id',$student['stu_id'])->find();
        return $res;
    }


    public function get_subject_total($order_type,$stu_id,$subject_type)
    {
        $where['stu_id'] = $stu_id;
        $where['subject_type'] = $subject_type;
        $where['order_status'] = 'finished';
        $res= Db::name($order_type)->where($where)->count();
        return $res;
    }

    /**
     * 限制馆长，教员无法登录
     */
    public function phone_validate($phone)
    {
        $space = $this->space->field(['cooperation_id','space_admin_id'])->select();
        $curator = [];
        foreach($space as $v){
            array_push($curator,$v['cooperation_id'],$v['space_admin_id']);
        }
        $curator = array_unique($curator);
        $where_admin['id'] = ['in',$curator];
        $admin_phones = $this->admin->where($where_admin)->field(['nickname'])->select();
        $uID= array_column($admin_phones->toArray(),'nickname');
        if(array_search($phone, $uID)){
            $this->error('您已是馆长，无法在学员端登录');
        }
        $coach = $this->coach->where('phone',$phone)->find();
        if($coach){
            $this->error('您已是教员，无法在学员端登录');
        }
    }


    public function openid_validate($openid)
    {
        $space = $this->space->field(['cooperation_id','space_admin_id'])->select();
        $curator = [];
        foreach($space as $v){
            array_push($curator,$v['cooperation_id'],$v['space_admin_id']);
        }
        $curator = array_unique($curator);
        $where_admin['id'] = ['in',$curator];
        $admin_openids = $this->admin->where($where_admin)->field(['openid'])->select();
        $uID= array_column($admin_openids->toArray(),'openid');
        if(array_search($openid, $uID)){
            $this->error('您已是馆长，无法在学员端登录');
        }
        $coach = $this->coach->where('openid',$openid)->find();
        if($coach){
            $this->error('您已是教员，无法在学员端登录');
        }
    }

    public function test_openid_validate()
    {
        $openid = 'oNOCU4o31qzdsJy2EMD6AdQ0x-ms';
        $student = $this->student->where('openid',$openid)->find();
            if(!$student){
                $intent_student = $this->intentstudent->where('openid',$openid)->find();
                if(!$intent_student){
                    $data['status'] = 2;
                    $data['openid'] = $openid;
                }else{
                    $student = $intent_student;
                    $data['status'] = 1;
                    $student['subject2_total'] = $this->get_subject_total('temporary_order',$student['stu_id'],'subject2');
                    $student['subject3_total'] = $this->get_subject_total('temporary_order',$student['stu_id'],'subject3');
                    $student['student_type'] = 'intent_student';
                }
            }else{
                $data['status'] = 1;
                $data['student'] = $student;
                $student['student_type'] = 'student';
                // $msg['vx_image'] = $vx_image;
                $where_coo['cooperation_id'] = $student['cooperation_id'];
                $icon = $this->cooperation->where($where_coo)->find()['icon_image'];
                if(!$icon){
                    // $student['icon'] = '/uploads/20231129/b880a3c188cb1ab74cb06d38be8f7321.png';
                    $student['icon'] = self::ICON;
                }else{
                    $student['icon'] = $icon;
                }
                $student['subject2_total'] = $this->get_subject_total('order',$student['stu_id'],'subject2');
                $student['subject3_total'] = $this->get_subject_total('order',$student['stu_id'],'subject3');
                // $this->student->where('openid',$openid)->update($msg);
            }
            $data['student'] = $student;
            $this->success('返回成功',$data);
    }

    public function test_phone()
    {
        $phone = '15777163429';
        $space = $this->space->field(['cooperation_id','space_admin_id'])->select();
        $curator = [];
        foreach($space as $v){
            array_push($curator,$v['cooperation_id'],$v['space_admin_id']);
        }

        $curator = array_unique($curator);
        $where_admin['id'] = ['in',$curator];
        $admin_phones = $this->admin->where($where_admin)->field(['nickname'])->select();
        $uID= array_column($admin_phones->toArray(),'nickname');
        var_dump($uID);exit;
        if(array_search($phone, $uID)){
            $this->error('您已是馆长，无法在学员端登录');
        }
        $coach = $this->coach->where('phone',$phone)->find();
        if($coach){
            $this->error('您已是教员，无法在学员端登录');
        }
    }
}
