<?php

namespace app\api\controller\recommender;

use app\common\controller\Api;
use think\Db;
use think\Cache;

/**
 * 开机流程所需接口
 */
class Index extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    private $token = 'vipdriver';
    private $common = null;
    private $intentstudent = null;
    private $student = null;
    private $recommender = null;
    private $space = null;
    private $admin = null;
    private $signupsource = null;
    private $course = null;
    private $paymentsource = null;
    private $promotestu = null;
    // private $trackprogress = null;
    private $ocr = null;
    private $authgroupaccess = null;
    private $authgroup = null;
    private $registype = null;
    
    public function _initialize()
    {
        parent::_initialize();
        $this->common = new \app\api\controller\Common;
        $this->intentstudent = new \app\admin\model\intent\Student;
        $this->student = new \app\admin\model\Student;
        $this->recommender = new \app\admin\model\Recommender;
        $this->space = new \app\admin\model\Space;
        $this->admin = new \app\admin\model\Admin;
        $this->signupsource = new \app\admin\model\Signupsource;
        $this->course= new \app\admin\model\Course;
        $this->paymentsource= new \app\admin\model\payment\Source;
        // $this->promotestu = new \app\admin\model\Promotestu;
        // $this->trackprogress = new \app\admin\model\Trackprogress;
        $this->authgroupaccess = new \app\admin\model\AuthGroupAccess;
        $this->authgroup = new \app\admin\model\AuthGroup;
        // $this->registype = new \app\admin\model\RegisType;
        
        // $this->rsa = new \app\api\controller\Rsa;
        $this->ocr = new \app\api\controller\recommender\Ocr;

    }

    public function test1()
    {
        $res = $this->recommender->select();
        foreach($res as $v){
            if($v['space_id']){
                $space = $this->space->where(['id'=>$v['space_id']])->find();
                
            }
        }
    }

    public function space_cooperation()
    {
        $update1['sign_status'] = 'yes';
        $where_recommender['id'] = 6;
        $re = $this->recommender->where($where_recommender)->update($update1);
        var_dump($re);
    }
    public function index()
    {
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
            Cache::set('test1',123123);
            echo $echostr;
            exit;
        } else {
            return false;
        }
    }

    public function get_statistics(){
        $params = $this->request->post();
        // $params['starttime'] = '2021-06-17';
        // $params['endtime'] = '2021-06-17';
        // $params['recommender_id'] = '3';
        if(empty($params['starttime']) ||empty($params['endtime']) || empty($params['recommender_id'])){
            $this->error('缺少参数');
        }
        $starttime = strtotime($params['starttime'].'00:00:00');
        $endtime   = strtotime($params['endtime'].'23:59:59');
        $recommender_id   = $params['recommender_id'];
        $cooperation = $this->recommender->where('id',$recommender_id)->find();

        $static['student_count'] = $this->get_student_count($recommender_id,$starttime,$endtime);
        $static['intent_student_count'] = $this->get_intent_student_count($recommender_id,$starttime,$endtime);
        $static['student_finished_count'] = $this->get_student_finished_count($recommender_id,$starttime,$endtime);
        $leader_id = $cooperation['leader'];
        $group = $this->recommender->where('leader',$leader_id)->field(['id','leader','name'])->select();
        $static['group_student_count'] = $this->get_group_student($group,$starttime,$endtime,$recommender_id);
        $static['group_intent_student_count'] = $this->get_group_intent_student($group,$starttime,$endtime,$recommender_id);
        $static['group_student_finished_count'] = $this->get_group_student_finished($group,$starttime,$endtime,$recommender_id);
        $this->success('返回成功',$static);
    }

    public function get_group_student($group,$starttime,$endtime,$recommender_id)
    {
        $list = [];
        
        foreach($group as $k=>$v){

            $where['follower'] = $v['id'];
            $where['createtime'] = ['between',[$starttime,$endtime]];
            $list[$k]['total'] = $this->student->where($where)->count();
            $list[$k]['name'] = $v['name'];
            $list[$k]['id'] = $v['id'];
        }
        $arr = array_column($list,'total');
        array_multisort($arr,SORT_DESC,$list);
        $list_id = array_column($list, 'id');
        // $list_sum = array_column($list, 'total');
        $arr1['self']= array_search($recommender_id, $list_id)+1;
        $arr1['top']= $list[0]['name'];
        $arr1['sum'] = array_sum($arr);
        return $arr1;
    }

    public function get_group_intent_student($group,$starttime,$endtime,$recommender_id)
    {
        $list = [];
        foreach($group as $k=>$v){
            $where['follower'] = $v['id'];
            $where['createtime'] = ['between',[$starttime,$endtime]];
            $list[$k]['total'] = $this->intentstudent->where($where)->count();
            $list[$k]['name'] = $v['name'];
            $list[$k]['id'] = $v['id'];
        }
        $arr = array_column($list,'total');
        array_multisort($arr,SORT_DESC,$list);
        $list_id = array_column($list, 'id');
        // $list_sum = array_column($list, 'total');
        $arr1['self']= array_search($recommender_id, $list_id)+1;
        $arr1['top']= $list[0]['name'];
        $arr1['sum'] = array_sum($arr);
        return $arr1;
    }

    /**
     * 获取小组中毕业人员
     */
    public function get_group_student_finished($group,$starttime,$endtime,$recommender_id)
    {
        $list = [];
        foreach($group as $k=>$v){
            $where['follower'] = $v['id'];
            $where['createtime'] = ['between',[$starttime,$endtime]];
            $where['process']  = ',2,';
            $list[$k]['total'] = $this->student->where($where)->count();
            $list[$k]['name'] = $v['name'];
            $list[$k]['id'] = $v['id'];
        }
        $arr = array_column($list,'total');
        array_multisort($arr,SORT_DESC,$list);
        $list_id = array_column($list, 'id');
        // $list_sum = array_column($list, 'total');
        $arr1['self']= array_search($recommender_id, $list_id)+1;
        $arr1['top']= $list[0]['name'];
        $arr1['sum'] = array_sum($arr);
        return $arr1;
    }


    /**
     * 获取已签约学员
     */
    public function get_student_count($recommender_id,$starttime,$endtime)
    {
        $where['follower']  = $recommender_id;
        $where['createtime']  = ['between',[$starttime,$endtime]];
        $num = $this->student->where($where)->count();
        return $num;
    }

    /**
     * 获取准学员人数
     */
    public function get_intent_student_count($recommender_id,$starttime,$endtime)
    {
        $where['follower']  = $recommender_id;
        $where['createtime']  = ['between',[$starttime,$endtime]];
        $num = $this->intentstudent->where($where)->count();
        return $num;
    }

    /**
     * 获取销售人员个人毕业学员人数
     */
    public function get_student_finished_count($recommender_id,$starttime,$endtime)
    {
        $where['follower']  = $recommender_id;
        $where['createtime']  = ['between',[$starttime,$endtime]];
        $where['process']  = ',2,';
        $num = $this->student->where($where)->count();
        return $num;
    }

    /**
     * 添加意向学员
     */
    public function add_intention_student(){
        //私钥解密
        $params = $this->request->post();
        // Cache::set('params',$params);
        if(empty($params['phone']) || empty($params['name']) || empty($params['car_type']) || empty($params['follower'])){
            $this->error('参数缺失');
        }
        $phone = $params['phone'];
        $student_phone = $this->student->where('phone',$phone)->find();

        $where_intent_phone['phone'] = $phone;
        $where_intent_phone['follower'] = ['neq',''];
        $intentstudent_phone = $this->intentstudent->where($where_intent_phone)->find();

        if($student_phone&&$intentstudent_phone){
            $this->error('当前学员已录入');
        }elseif($intentstudent_phone){
            $this->error('当前学员已是意向学员');
        }elseif($student_phone){
            $this->error('当前学员已是正式学员');
        }else{
            $recommender = $this->recommender->with('admin')->where('recommender.id',$params['follower'])->find();
            $params['space_id'] = $this->space->where('space_admin_id',$recommender['space_id'])->find()['id'];
            $params['cooperation_id'] = $recommender['admin']['pid'];
            $params['regis_status'] = 1;
            $params['stu_id'] = 'CSN'.date("YmdHis") . mt_rand(100000, 999999);
            $res = $this->intentstudent->save($params);
            if($res){
                $this->success('返回成功');
            }
            $this->error('添加意向学员失败');
        }

    }
    public function intent_student_validate($params,$id)
    {
        if(!$id){
            $phone = $this->intentstudent->where('phone',$params['phone'])->find();
            $vx_name = $this->intentstudent->where('vx_name',$params['vx_name'])->find();
        }
        if($phone || $vx_name){
            $this->error('当前学员已录入');
        }
    }
    /**
     * 添加意向学员选择列表
     */
    public function add_intention_student_list()
    {
        $params = $this->request->post();
        // $params['cooperation_id'] = 2;
        if(empty($params['cooperation_id'])){
            $this->error('参数缺失');
        }
        $list['course'] = $this->get_course_list($params);
        $list['sign_up_source'] = $this->get_sign_up_source_list($params);
        $this->success('返回成功',$list);
    }

    
    /**
     * 获取身份证信息
     */
    public function get_ocr(){
        $params = $this->request->post();
        $idcardimage = 'https://www.aivipdriver.com/'.$params['image_url'];
        // $idcardimage = 'https://www.aicarshow.com/uploads/20201109/02fa6db6269daf3bb892c5d83fa86669.jpg';
        $res = $this->ocr->ocr($idcardimage);
        if($res['code'] == 0){
            $this->error($res['message']);
        }
        $info['name'] = $res['Name'];
        $info['idcard'] = $res['IdNum'];
        $info['sex'] = 'male';
        if($res['Sex'] == '女'){
            $info['sex'] = 'female';
        }
        $this->success('返回成功',$info);
    }

    /**
     * 获取跟进人
     */
    public function get_follower(){
        $params = $this->request->post();
        $where['name'] = ['like','%'.$params['name'].'%'];
        // $where['space_id'] = $params['space_id'];
        $recommender = $this->recommender->where($where)->field(['id','space_id','name'])->select();
        $list = [];
        foreach($recommender as $k=>$v){
            $list[$k]['id'] = $v['id'];
            $space = $this->admin->where('id',$v['space_id'])->find()['nickname'];
            $list[$k]['info'] = $v['name'].'('.$space.')';
            unset($space);
        }
        $this->success('返回成功',$list);

    }
    
    /**
     * 获取学员信息
     */
    public function get_student_list(){
        $params = $this->request->post();
        // $params['recommender_id']=3;
        // $params['page'] =1;
        // $params['type'] = 'student';
        // $params['starttime'] ='2021-02-01';
        // $params['endtime'] ='2021-06-07';
        if(empty($params['recommender_id']) || empty($params['page']) || empty($params['type']) || empty($params['starttime']) || empty($params['endtime'])){
            $this->error('参数缺失');
        }
        $where['follower'] = $params['recommender_id'];
        $starttime = strtotime($params['starttime'].'00:00:00');
        $endtime = strtotime($params['endtime'].'23:59:59');
        $recommender = $this->recommender->where(['id'=>$params['recommender_id']])->find();
        if(!$recommender){
            $this->error('参数缺失');
        }
        $res = [];
        $page = $params['page'];
        $pagenum = 10;
        $numl = $pagenum*($page-1);

        if(!empty($params['name'])){
            $where['name'] = $params['name'];
        }
        if($params['type'] == 'intent_student'){
            $where['regis_status'] = 1;
            $where['createtime'] = ['between',[$starttime,$endtime]];
            $res = $this->intentstudent->where($where)->field(['stu_id','name','car_type','createtime','sex'])->limit($numl,$pagenum)->select();
            // var_dump($res->toArray());exit;
        }elseif($params['type'] == 'student'){
            $where['student.createtime'] = ['between',[$starttime,$endtime]];
            $student = $this->student->with('courselog')->where($where)->select();
            foreach($student as $k=>$v){
                $res[$k]['stu_id'] = $v['stu_id'];
                $res[$k]['name'] = $v['name'];
                // $res[$k]['phone'] = $v['phone'];
                $res[$k]['car_type'] = $v['car_type_text'];
                $res[$k]['course'] = $v['courselog']['course'];
                $res[$k]['createtime'] = $v['registtime'];
                
            }
        }else{
            $this->error('获取学员列表数据异常');
        }
        $this->success('返回成功',$res);
    }
    
    // public function promoter()
    // {
    //     $params = $this->request->post();
    //     // $params['recommender_id']=3;
    //     // $params['page'] =1;
    //     // $params['starttime'] ='2023-02-22';
    //     // $params['endtime'] ='2021-02-26';
    //     if(empty($params['recommender_id']) || empty($params['page']) || empty($params['starttime'])|| empty($params['endtime'])){
    //         $this->error('参数缺失');
    //     }
    //     $page = $params['page'];
    //     $pagenum = 10; 
    //     $numl = $pagenum*($page-1);

    //     $where['follower'] = $params['recommender_id'];
    //     $where['promotestu.createtime'] = ['between',[strtotime($params['starttime']),strtotime($params['endtime'].'23:59:59')]];
    //     $res = $this->promotestu->with(['courselog'])->where($where)->limit($numl,$pagenum)->order('id desc')->select();
    //     $this->success('返回成功',$res);
    // }

    // public function promoter_detail()
    // {
    //     $params = $this->request->post();
    //     // Cache::set('promoter_detail',$params);
    //     // $params['recommender_id']= 4;
    //     // $params['stu_id'] = 'CSN20230301165503958039';

    //     if(empty($params['recommender_id']) || empty($params['stu_id'])){
    //         $this->error('参数缺失');
    //     }

    //     $where['follower'] = $params['recommender_id'];
    //     $where['stu_id'] = $params['stu_id'];
    //     $res = $this->promotestu->with(['courselog','recommender'])->where($where)->find();
    //     // var_dump($res);exit;
    //     $trackprogress = $this->trackprogress->with(['recommender'])->where(['trackprogress.id'=>['in',explode(',',$res['track_progress_id'])]])->select();
        
    //     $list = [];
    //     foreach($trackprogress as $k=>$v){
    //         $list[$k]['name'] = $v['recommender']['name'];
    //         $list[$k]['content'] = $v['content'];
    //         $list[$k]['createtime'] = date('Y-m-d h:i:s',$v['createtime']);
    //     }
    //     $res['trackprogress'] = $list;

    //     $this->success('返回成功',$res);
    // }

    // public function submit_content()
    // {
    //     $params = $this->request->post();
    //     // $params['recommender_id']=3;
    //     // $params['recommender_id'] = $params;
    //     if(empty($params['stu_id']) || empty($params['recommender_id']) || empty($params['content'])){
    //         $this->error('参数缺失');
    //     }

    //     $where['follower'] = $params['recommender_id'];
    //     $where['stu_id'] = $params['stu_id'];

    //     $res = $this->promotestu->with(['courselog','recommender'])->where($where)->find();
        
    //     $add['content'] = $params['content'];
    //     $add['stu_id'] = $res['stu_id'];
    //     $add['createtime'] = time();
    //     $add['cooperation_id'] = $res['cooperation_id'];
    //     $add['follower'] = $params['recommender_id'];
    //     $result = $this->trackprogress->save($add);

    //     // if(!$result){
    //     //     $this->error('添加失败');
    //     // }
    //     $arr = explode(',',$res['track_progress_id']);
    //     array_push($arr,$this->trackprogress->id);


    //     // var_dump(array_push(,$id));exit;
    //     if($res['track_progress_id']){
    //         $update['track_progress_id'] = implode(',',$arr);
    //     }else{
    //         $update['track_progress_id'] = $this->trackprogress->id;
    //     }
    //     $result1 = $this->promotestu->where($where)->update($update);
        
    //     // var_dump($res);exit;
    //     $trackprogress = $this->trackprogress->with(['recommender'])->where(['trackprogress.id'=>['in',$arr]])->select();
        
    //     $list = [];
    //     foreach($trackprogress as $k=>$v){
    //         $list[$k]['name'] = $v['recommender']['name'];
    //         $list[$k]['content'] = $v['content'];
    //         $list[$k]['createtime'] = date('Y-m-d h:i:s',$v['createtime']);
    //     }
    //     $res['trackprogress'] = $list;
    //     if($result1){
    //         $this->success('返回成功',$res);
    //     }else{
    //         $this->error('添加失败');
    //     }
    // }

    public function submit_abandon()
    {
        $params = $this->request->post();
        // $params['recommender_id']=3;
        // $params['recommender_id'] = $params;
        if(empty($params['stu_id']) || empty($params['recommender_id'])){
            $this->error('参数缺失');
        }

        $where['follower'] = $params['recommender_id'];
        $where['stu_id'] = $params['stu_id'];

        $update['abandon'] = '1';
        $res = $this->promotestu->where($where)->update($update);

        if($res){
            $this->success('返回成功',$res);
        }
        $this->error('添加失败');
    }

    public function subscribe()
    {
        $params = $this->request->post();
        // $params['recommender_id']=3;
        // $params['recommender_id'] = $params;
        if(empty($params['recommender_id'])){
            $this->error('参数缺失');
        }

        $where['id'] = $params['recommender_id'];
        $update['message_status'] = 'yes';
        $res = $this->recommender->where($where)->update($update);
        if($res){
            $this->success('返回成功',$res);
        }else{
            $this->error('授权失败，请刷新页面重新授权');
        }
    }

    //学员跟踪信息详情
    public function student_detail()
    {
        $params = $this->request->post();
        // $params['stu_id'] = 'CSN20210408194516481490';
        // $params['type'] = 'intent_student';
        if(empty($params['stu_id']) || empty($params['type'])){
            $this->error('参数缺失');
        }
        if($params['type'] == 'intent_student'){
            $where['stu_id'] = $params['stu_id'];
            $student = $this->intentstudent->with(['space','signupsource','courselog'])->where($where)->find();
            $data = $this->get_intent_student_detail($student);
        }else{
            $where['student.stu_id'] = $params['stu_id'];
            $student = $this->student->with(['space','signupsource','courselog','admin'])->where($where)->find();
            $data = $student;
            $data = $this->get_student_detail($student);
        }
        $this->success('返回成功',$data);
    }
    
    public function get_student_detail($student)
    {
        // var_dump($student->toArray());exit;
        $params['stu_id'] = $student['stu_id'];
        $params['name'] = $student['name'];
        $params['cooperation'] = $student['admin']['nickname'];
        $params['idcard'] = $student['idcard'];
        $params['idcard1image'] = $student['idcard1image'];
        $params['idcard2image'] = $student['idcard2image'];
        $params['photoimage'] = $student['photoimage'];
        $params['space_name'] = $student['space']['space_name'];
        $params['sex'] = $student['sex'];
        $params['phone'] = $student['phone'];
        $params['course'] = $student['courselog']['course'];
        $params['money'] = explode(',',$student['courselog']['installment']);
        $params['signupsource'] = $student['signupsource']['sign_up_source_name'];
        $params['car_type'] = $student['car_type_text'];
        $params['contract_path'] = $student['contract_path'];
        $params['registtime'] = $student['registtime'];
        $installment= explode(',',$student['installment_id']);
        // var_dump($student->toArray());exit;
        foreach($installment as $k=>$v){
            $installment_info = Db::name('installment')->where('id',$v)->find();
            $params['installment'][$k]['money'] = $installment_info['money'];
            $params['installment'][$k]['times'] = $installment_info['times'];
            $params['installment'][$k]['payment_number'] = $installment_info['payment_number'];
            $params['installment'][$k]['pay_status'] = $installment_info['pay_status'];
            $params['installment'][$k]['payment_source'] = Db::name('payment_source')->where('id',$installment_info['payment_source'])->find()['payment_source'];
        }
        unset($params['money']);
        // $params['address'] = $student['address'];
        return $params;
    }

    public function get_intent_student_detail($student)
    {
        $params['name'] = $student['name'];
        $params['sex'] = $student['sex'];
        $params['phone'] = $student['phone'];
        // $params['vx_name'] = $student['vx_name'];
        // $params['space_name'] = $student['space']['space_name'];
        $params['car_type'] = $student['car_type_text'];
        // var_dump($student->toArray());exit;
        $params['course'] = $student['course_log']['course'];
        $params['intent_iImage'] = $student['intent_iImage'];
        // $params['registype'] = $student['registype']['type'];
        $params['signupsource'] = $student['signupsource']['sign_up_source_name'];
        $params['regis_status'] = $student['regis_status'];
        // $params['intention']= $student['intention'];
        $params['remarks']= $student['remarks'];
        return $params;
    }
    
    /**
     * 获取课程
     */
    public function get_course_list($params){
        // $params = $this->common->validateToken($params);
        $where['cooperation_id'] = $params['cooperation_id'];
        $res = $this->course->with('course_log')->where($where)->select();
        $list = [];
        foreach($res as $k=>$v){
            $list[$k]['course'] = $v['course_log']['course'];
            $list[$k]['course_id'] = $v['course_id'];
            $list[$k]['money'] = $v['course_log']['money'];
            $list[$k]['installment'] = explode(',',$v['course_log']['installment']);
            $list[$k]['status'] = $v['course_log']['status'];
        }
        return $list;
    }

    /**
     * 获取报名来源
     */
    public function get_sign_up_source_list($params){
        // $params = $this->common->validateToken($params);
        $where['cooperation_id'] = $params['cooperation_id'];
        $res = $this->signupsource->where($where)->field(['id','sign_up_source_name'])->select();
        return $res;
    }


    public function test()
    {
        $params = Cache::get('student');
        $installment = $params['installment'];
        $installment = str_replace('&quot;','"',$installment);
        $installment = json_decode($installment,true);
        if(empty($params['photoimage'])||empty($params['name'])||empty($params['phone'])||empty($params['idcard'])||empty($params['sex'])||
            empty($params['contract_path'])||empty($params['idcard1image'])||empty($params['idcard2image'])||empty($params['car_type'])||empty($params['sign_up_source'])||
            empty($params['recommender_id'])||empty($params['course_id'])||empty($params['installment'])){
                $this->error('缺少参数');
            }
        $params['registtime'] = time();
        $student_phone = $this->student->where('phone',$params['phone'])->find();
        $recommender = $this->recommender->with('admin')->where('recommender.id',$params['recommender_id'])->find();
        if(!$recommender){
            $this->error('请求数据错误，请联系管理处理后重试');
        }
        $params['space_id'] = $this->space->where('space_admin_id',$recommender['space_id'])->find()['id'];
        $params['cooperation_id'] = $recommender['admin']['pid'];
        // $student_vx = $this->student->where('vx_name',$params['vx_name'])->find();
        // $this->student_validate($params,'');
        // unset($params['study_sign']);
        // unset($params['subject_type']);
        // $params['registtime'] = strtotime($params['registtime']);
        // $space = $this->space->where('id',$params['space_id'])->find();
        // if($space['times_limit_status'] == 1){
        //     $params['period_surplus'] = $space['times_limit'];
        // }
        if($student_phone){
            $this->error('当前学员已是正式学员');
        }else{
            $installment = $params['installment'];
            $intent_phone = $this->intentstudent->where('phone',$params['phone'])->find();
            // $intent_vx = $this->intentstudent->where('vx_name',$params['vx_name'])->find();
            unset($params['installment']);
            $params['stu_id'] = 'CSN'.date("YmdHis") . mt_rand(100000, 999999);
            if($intent_phone){
                $params['stu_id'] = $intent_phone['stu_id'];
                $intention['regis_status'] = 2;
                $this->intentstudent->where('phone',$params['phone'])->update($intention);
            }
            $payment_res = $this->add_payment($installment,$params['stu_id'],$params['course_id'],$params['space_id'],$params['cooperation_id']);
            $params['payment_process'] = $payment_res['payment_process'];
            $params['installment_id'] = $payment_res['installment'];
            $params['follower'] = $params['recommender_id'];
            unset($params['recommender_id']);
            $this->student->allowField(true)->save($params);
            $this->success('返回成功');
        }
    }


    /**
     * 添加正式学员
     */
    public function add_student(){
        //私钥解密
        $params = $this->request->post();
        // Cache::set('student',$params);
        // $params['photoimage'] = '/uploads/20210526/3a8e9684d2f61b385678bb446954f22c.png';
        // $params['name'] = '测试3';
        // $params['phone'] =  '15778475464';
        // $params['idcard'] = '340822199604154541';
        // $params['sex'] = 'male';
        // $params['subject_type'] = "subject1,subject2" ;
        // $params['contract_path'] =  "/uploads/20210526/3a8e9684d2f61b385678bb446954f22c.png" ;
        // $params['idcard1image'] =  "/uploads/20210526/3a8e9684d2f61b385678bb446954f22c.png"  ;
        // $params['idcard2image'] =  "/uploads/20210526/3a8e9684d2f61b385678bb446954f22c.png"  ;
        // $params['car_type'] =  'cartype2';
        // $params['sign_up_source'] = "5" ;
        // $params['recommender_id'] =  4 ;
        // $params['course_id'] = 1 ;
        // $params['installment'] = [
        //     [
        //         'times'=>1,
        //         'payment_source' =>1,
        //         'platform_number' => 'zfb123',
        //         'pay_status' => 'yes'
        //     ],
        //     [
        //         'times'=>2,
        //         'payment_source' =>1,
        //         'platform_number' => 'zfb12123',
        //         'pay_status' => 'yes'
        //     ],
        //     [
        //         'times'=>3,
        //         'payment_source' =>1,
        //         'platform_number' => 'zfb321',
        //         'pay_status' => 'yes'
        //     ],
        //     [
        //         'times'=>4,
        //         'payment_source' =>1,
        //         'platform_number' => 'zfb456',
        //         'pay_status' => 'yes'
        //     ]
        // ];
        // Cache::set('installment_params',$params);
        if(empty($params['name'])||empty($params['phone'])||empty($params['idcard'])||empty($params['sex'])||empty($params['car_type'])||empty($params['sign_up_source'])||
            empty($params['recommender_id'])||empty($params['course_id'])||empty($params['installment']) || !key_exists('space_id',$params)
            || !key_exists('cooperation_id',$params)){
            $this->error('缺少参数');
        }
        $params['registtime'] = time();
        $student_phone = $this->student->where('phone',$params['phone'])->find();
        $student_idcard = $this->student->where('idcard',$params['idcard'])->find();
        $recommender = $this->recommender->with('admin')->where('recommender.id',$params['recommender_id'])->find();
        if(!$recommender){
            $this->error('请求数据错误，请联系管理处理后重试');
        }

        if($params['cooperation_id'] == 148){
            $this->error('当前驾校无法添加正式学员');
        }
        if($student_phone || $student_idcard){
            $this->error('当前学员已是正式学员,手机号或身份证已重复');
        }else{
            $installment = $params['installment'];
            $intent_phone = $this->intentstudent->where('phone',$params['phone'])->find();
            // $intent_vx = $this->intentstudent->where('vx_name',$params['vx_name'])->find();
            unset($params['installment']);
            $params['stu_id'] = 'CSN'.date("YmdHis") . mt_rand(100000, 999999);
            if($intent_phone){
                $params['stu_id'] = $intent_phone['stu_id'];
                $intention['regis_status'] = 2;
                $this->intentstudent->where('phone',$params['phone'])->update($intention);
            }
            $payment_res = $this->add_payment($installment,$params['stu_id'],$params['course_id'],$params['space_id'],$params['cooperation_id']);
            $params['payment_process'] = $payment_res['payment_process'];
            $params['installment_id'] = $payment_res['installment'];
            $params['follower'] = $params['recommender_id'];
            $params['process'] = 1;
            unset($params['recommender_id']);
            $this->student->allowField(true)->save($params);
            $this->success('返回成功');
        }
    }

    public function student_validate($params,$id)
    {
        if(!$id){
            $phone = $this->student->where('phone',$params['phone'])->find();
            $idcard = $this->student->where('idcard',$params['idcard'])->find();
        }
        if($phone || $idcard){
            $this->error('当前学员已录入');
        }
    }

    /**
     * 添加正式学员选择列表
     */
    public function add_student_list()
    {
        $params = $this->request->post();
        // $params['cooperation_id'] = 2;
        // $params['space_id'] = null;
        $params['space_id'] = trim($params['space_id']);
        if(!key_exists('cooperation_id',$params) || !key_exists('space_id',$params)){
            $this->error('缺少参数');
        }
        
        // $params = $this->common->validateToken($params);
        $list['course'] = $this->get_course_list($params);
        $list['payment_source'] = $this->get_paymentsource_list($params);
        $list['sign_up_source'] = $this->get_sign_up_source_list($params);
        $list['space_list'] = $this->get_space_list($params);
        // $list['regis_type'] = $this->get_regis_type_list($params);
        if(!$list['space_list']){
            $this->error('绑定场馆为空');
        }
        $this->success('返回成功',$list);
    }

    public function get_space_list($params)
    {
        if($params['space_id'] == 0){
            $where['cooperation_id'] = $params['cooperation_id'];
        }else{
            $where['id'] = $params['space_id'];
        }
        $res = Db::name('space')->where($where)->field('id,space_name')->select();
        return $res;
    }

    public function space_list($params)
    {
        $where['cooperation_id'] = $params['cooperation_id'];
        $res = Db::name('space')->where($where)->field(['id','space_name'])->select();
        return $res;
    }


    /**
     * 获取付款方式
     */
    public function get_paymentsource_list($params){
        $where['cooperation_id'] = $params['cooperation_id'];
        $res = $this->paymentsource->where($where)->field(['id','payment_source'])->select();

        return $res;
    }


    /**
     * 添加学员缴费流水号
     */
    public function add_payment($payment,$stu_id,$course_id,$space_id,$cooperation_id)
    {
        // var_dump(Cache::set('add_payment',$payment));
        // $this->error('金额不能为空');

        
        $payment = str_replace('&quot;','"',$payment);
        $payment = json_decode($payment,true);
        $insert = [];
        $money = 0;
        $shijiao = 0;
        $installment_id = [];
        foreach($payment as $k=>$v){
            if(!array_key_exists('money',$v)){
                $this->error('金额不能为空');
                continue;
                // Db::name('installment')->where('stu_id',$stu_id)->delete();
            }
            $money += $v['money'];
            $v['pay_status'] = 'yes';


            if($v['money'] && !key_exists('platform_number', $v)){
                $v['pay_status'] = 'no';
            }else{
                $platform_number = Db::name('installment')->where('platform_number',$v['platform_number'])->find();
                if($platform_number){
                    // Db::name('installment')->where('stu_id',$stu_id)->delete();
                    $this->error($v['platform_number'].'此流水号已存在,请勿重复添加');
                }
            }

            // if(!$v['platform_number'] && $v['pay_status'] =='yes'){
            //     Db::name('installment')->where('stu_id',$stu_id)->delete();
            //     $this->error('提交数据错误');
            // }
           
            $insert['stu_id'] = $stu_id;
            $insert['times'] = $k;
            $insert['space_id'] = $space_id;
            $insert['cooperation_id'] = $cooperation_id;
            if(key_exists('platform_number', $v)){
                $insert['payment_number'] = $v['platform_number'];
            }

            $insert['payment_source'] = $v['payment_source'];
            $insert['money'] = $v['money'];
            $insert['pay_status'] = $v['pay_status'];
            if($v['pay_status'] == 'yes'){
                $shijiao += $v['money'];
            }
            $insert['pay_time'] = time();
            Db::name('installment')->insert($insert);
            $id = Db::name('installment')->getLastInsID();
            array_push($installment_id,$id);
            unset($insert);
        }
        if($course_id){
            $course_money = Db::name('course_log')->where('id',$course_id)->find()['money'];
            Cache::set('money','course_money'.$course_money.'---'.'money---'.$money);
            if($course_money != $money){
                Db::name('installment')->where('stu_id',$stu_id)->delete();
                $this->error('金额总和与实际金额不匹配，请重新填写');
            }
        }
        $installment = implode(',',$installment_id);
        $res['installment'] = $installment;
        if($shijiao == $course_money){
            $res['payment_process'] = 'payed';
        }elseif($shijiao >0 && $shijiao <$course_money){
            $res['payment_process'] = 'paying';
        }elseif($shijiao ==0){
            $res['payment_process'] = 'unpaid';
        }
        $res['tuition'] = $shijiao;
        return $res;
    }

    /**
     * 获取报名方式
     */
    // public function get_regis_type_list($params){
    //     // $params = $this->common->validateToken($params);
    //     $where['cooperation_id'] = $params['cooperation_id'];
    //     $res = $this->registype->where($where)->field(['id','type'])->select();
    //     return $res;
    // }


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
            $data['space_id'] = $source_id;
            $data['cooperation_id'] = $this->space->where('space_admin_id',$source_id)->find()['cooperation_id'];
        }
        return $data;
    }
}
