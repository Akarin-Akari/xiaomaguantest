<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\Upload;
use PDO;
use think\cache;
use think\Db;
use Yansongda\Supports\Str;

/**
 * 开机流程所需接口
 */
class MachineBoot extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    protected $common = null;
    protected $jiangxi = null;
    protected $machinecar = null;
    protected $place = null;
    protected $studyprocess = null;
    protected $reportcard = null;
    protected $keersort = null;
    protected $kesansort = null;
    protected $faceimage = null;
    protected $student = null;
    protected $trainstatistic = null;
    protected $warnexam = null;
    protected $qt_boot = null;
    protected $order = null;
    protected $temporaryorder = null;
    protected $intentstudent = null;
    protected $cooperation = null;
    protected $stutj = null;
    protected $jishi = null;

    protected $ordertime = null;
    
    public function _initialize()
    {
        parent ::_initialize();
        $this->common = new \app\api\controller\Common;
        $this->qt_boot = new \app\api\controller\student\QtBoot;
        $this->machinecar = new \app\admin\model\Machinecar;
        $this->place = new \app\admin\model\Place;
        $this->order = new \app\admin\model\Order;
        $this->temporaryorder = new \app\admin\model\Temporaryorder;
        $this->studyprocess = new \app\admin\model\Studyprocess;
        $this->reportcard = new \app\admin\model\Reportcard;
        $this->keersort = new \app\admin\model\Keersort;
        $this->kesansort = new \app\admin\model\Kesansort;
        // $this->stutj = new \app\admin\model\Stutj;
        $this->student = new \app\admin\model\Student;
        $this->intentstudent = new \app\admin\model\IntentStudent;
        $this->trainstatistic = new \app\admin\model\Trainstatistic;
        $this->warnexam = new \app\admin\model\Warnexam;
        $this->cooperation = new \app\admin\model\Cooperation;
        $this->ordertime = new \app\admin\model\Ordertime;
        $this->jishi = new \app\api\controller\xiaomaguan\Index;
        
    }

    public function test()
    {
        $activate = Cache::get('activate');
        var_dump($activate);
    }

    public function activate(){
        $params = $this->request->post();
        // $params['machine_code']= 'FJSZxmg202306004fdone';
        // $params['activate_id']= '41f825106d87fce06122df56c1bb489e';
        // $params['state'] = 1; //1/2
        Cache::set('activate',$params);
        if(empty($params['activate_id'])|| empty($params['machine_code'])){
            $this->error('参数缺失');
        }
        if($params['state'] == 1){
            $where['machine_code']=$params['machine_code'];
            $where['activate_id']=$params['activate_id'];
            $res = $this->machinecar->where($where)->find();
            // var_dump(123);exit;
            if($res){
                if($res['activate_state'] == '1'){
                    $this->error('当前机器码已激活');
                }else{
                    $update['activate_state'] = 1;
                    $res = $this->machinecar->where($where)->update($update);
                    if($res){
                        $this->success('激活成功');
                    }
                }
            }else{
                $this->error('当前无此匹配的激活码');
            }
        }else{
            $where['activate_id'] = $params['activate_id'];
            $where['activate_state']= '1';
            $res = $this->machinecar->where($where)->find();
            if($res){
                $this->success('验证成功');
            }else{
                $this->error('验证失败');
            }
        }
    }

    public function activate_new(){
        $params = $this->request->post();
        // $params['machine_code']= '10035';
        // $params['activate_id']= '8f19d5d651f6748d134e88fd3c51cc72';//94dfa9d7ebdd8e2e0b8c3a64f7314bc7,8f19d5d651f6748d134e88fd3c51cc72
        // $params['state'] = 2; //1(未激活)/2(已激活)
        // Cache::set('activate',$params);
        if(empty($params['activate_id'])|| empty($params['machine_code'])){
            $this->error('参数缺失');
        }
        if($params['state'] == 1){
            $where['machine_code']=$params['machine_code'];
            $where['activate_id']=$params['activate_id'];
            $res = $this->machinecar->where($where)->find();
            $info = [];
            $info['arr'] = [];
            // if($res['insurance_end_time'] < time()){
            //     $info['guobao'] = '1';
            // }else{
            //     $info['guobao'] = '0';

            // }
            $info['guobao'] = (string)$res['insurance_end_time'];

            if($res){
                if($res['activate_state'] == '1'){
                    $this->error('当前机器码已激活',$info);
                }else{
                    $update['activate_state'] = 1;
                    $res = $this->machinecar->where($where)->update($update);
                    if($res){
                        $this->success('激活成功',$info);
                    }
                }
            }else{
                $this->error('当前无此匹配的激活码,请重新激活');
            }
        }else{
            $info = [];
            $info['arr'] = [];
            $where['activate_id'] = $params['activate_id'];
            $where['machine_code']=$params['machine_code'];
            $where['activate_state']= '1';
            $res = $this->machinecar->where($where)->find();
            // if($res['insurance_end_time'] < time()){
            //     $info['guobao'] = '1';
            // }else{
            //     $info['guobao'] = '0';
            // }
            $info['guobao'] = (string)$res['insurance_end_time'];

            if($res){
                $this->success('验证成功',$info);
            }else{
                $this->error('验证失败,请重新激活');
            }
        }
    }



    public function study_subject(){
        $params = $this->request->post();
        // $params['ordernumber'] = 'FJYF202110004';
        // $params['student_type'] = 1;
        if(empty($params['ordernumber']) || empty($params['student_type'])){
            $this->error('参数缺失');
        }
        $arr = [];
        $this->success('返回成功',$arr,[]);

    }
    
    function qingkong(){
        $where['machine_code'] = '10032';
        $update['activate_state'] = 0;
        $res = $this->machinecar->where($where)->update($update);
        if($res){
            $this->success('返回成功');
        }else{
            $this->error('返回失败');
        }
    }

    /**
     * 开机获取logo
     */
    public function get_company_info()
    {
        $params = $this->request->post();
        if(empty($params['machine_code'])){
            $this->error('参数缺失');
        }

        if(array_key_exists('LocalserialNum',$params)){
            // Cache::set('version',$params);
            $update['LocalserialNum'] = $params['LocalserialNum'];
        }

        if(array_key_exists('MainVersions',$params) && array_key_exists('LocalVersions',$params)){
            // Cache::set('version',$params);
            $update['main_versions'] = $params['MainVersions'];
            $update['local_versions'] = $params['LocalVersions'];
            $this->machinecar->where(['machine_code'=>$params['machine_code']])->update($update);
        }
        $res = $this->machinecar->with('space')->where(['machine_code'=>$params['machine_code']])->find();
        $info['info_msg'] = $res['space']['info_msg'];
        $info['logo_image'] = '/uploads/20240118/b9f4fe70cd1c3e5cecce8fc8074f26d0.png';
        if($res['space']['logo_image']){
            $info['logo_image'] = $res['space']['logo_image'];
        }
        // $device = [];
        // if($res['']){
        //     $device = $this->jiangxi->device($params['machine_code']);
        //     Cache::set('device'.$params['machine_code'],$device);
        // }
        // $info['device'] = $device;
        $this->success('返回成功',$info);
    }

    /**
     * 开机获取logo
     */
    public function get_company_info_new()
    {
        $params = $this->request->post();
        // $params['machine_code'] = '10020';
        if(empty($params['machine_code'])){
            $this->error('参数缺失');
        }

        if(array_key_exists('LocalserialNum',$params)){
            // Cache::set('version',$params);
            $update['LocalserialNum'] = $params['LocalserialNum'];
        }

        if(array_key_exists('MainVersions',$params) && array_key_exists('LocalVersions',$params)){
            // Cache::set('version',$params);
            $update['main_versions'] = $params['MainVersions'];
            $update['local_versions'] = $params['LocalVersions'];
            $this->machinecar->where(['machine_code'=>$params['machine_code']])->update($update);
        }
        $res = $this->machinecar->with('space')->where(['machine_code'=>$params['machine_code']])->find();
        $info['info_msg'] = $res['space']['info_msg'];
        $info['logo_image'] = '/uploads/20211122/5f28562c9d9143d022e79cb6c403ded2.png';
        if($res['space']['logo_image']){
            $info['logo_image'] = $res['space']['logo_image'];
        }
        // $device = [];
        // if($res['']){
        //     $device = $this->jiangxi->device($params['machine_code']);
        //     Cache::set('device'.$params['machine_code'],$device);
        // }
        // $info['device'] = $device;
        $this->success('返回成功',$info);
    }

    /**
     * 学习记录
     */
    public function study_log()
    {
        $params = $this->request->post();
        // $params['stu_id'] = 'CSN20210813165217581764';
        // $params['ordernumber'] = 'CON20211103141133348803';
        // $params['status'] = 1;
        // $params['process_name'] = 'jichubj';
        // $params['starttime'] = time()-60*10;
        // Cache::set('test_study_log',$params);
        if(empty($params['stu_id']) || empty($params['ordernumber'])|| empty($params['process_name'])|| empty($params['starttime'])){
            $this->error('参数缺失');
        }
        $order = $this->order->where(['ordernumber'=>$params['ordernumber']])->find();
        if(array_key_exists('kaochang',$params)){
            $place = $this->place->where(['str_name'=>$params['kaochang']])->find();
            if($place){
                $params['place_id'] = $place['id'];
            }
            unset($params['kaochang']);
        }
        $params['createtime'] = time();
        $params['study_time'] = $params['starttime'];
        $params['space_id'] = $order['space_id'];
        $params['cooperation_id'] = $order['cooperation_id'];
        $res = null;
        if($params['starttime']<3600){
            unset($params['starttime']);
            $res = $this->studyprocess->insert($params);
            // if($res){
                // $stu_train = $this->trainstatistic->where(['stu_id'=>$params['stu_id']])->find();
                // if($stu_train){
                //     $this->trainstatistic->insert();
                // }else{
                //     $this->trainstatistic->update();
                // }
            // }
            if($res){
                $this->success('返回成功');
            }else{
                $this->error('上传成绩异常');
            }
        }
        
    }

    /**
     * 成绩单信息
     */
    public function reportcard()
    {
        $params = $this->request->post();
        if(empty($params['ordernumber'])|| empty($params['subject_type'])|| empty($params['kaochang'])){
            $this->error('参数缺失');
        }
        $events = $params['events'];

        unset($params['events']);
        $order = $this->order->where(['ordernumber'=>$params['ordernumber']])->find();
        if(!$order){
            $this->error('查询订单错误');
        }
        $params['stu_id'] = $order['stu_id'];
        $params['coach_id'] = $order['coach_id'];
        $params['machine_id'] = $order['machine_id'];
        $params['cooperation_id'] = $order['cooperation_id'];
        $params['space_id'] = $order['space_id'];
        $params['createtime'] = (int)$params['createtime'];
        $params['endtime'] = time();
        $params['pass_time'] = ($params['endtime'] - $params['createtime']);
        $report= $this->reportcard->save($params);
        $id = $this->reportcard->getLastInsID();
        $stu_train = $this->trainstatistic->where(['stu_id'=>$params['stu_id']])->find();
        $warn_exam = $this->warnexam->where(['cooperation_id'=>$order['cooperation_id'],'status'=>1])->find();
        if(!$stu_train && $warn_exam){
            $train_data['stu_id'] = $params['stu_id'];
            $train_data['cooperation_id'] = $params['cooperation_id'];
            $train_data['space_id'] = $params['space_id'];
            $train_data['createtime'] = $params['createtime'];
            if( $params['score'] >= $warn_exam['kesan_score']){
                $train_data['keer_hege'] = 1;
                $this->trainstatistic->save($train_data);
            }else{
                $train_data['keer_hege'] = 0;
            }
            
        }elseif($stu_train && $warn_exam){
            if($params['subject_type'] == 'subject3'){
                //科目三
                $train_data['kesan_hege'] = ($stu_train['kesan_hege']+1);
            }else{
                //科目二
                $train_data['keer_hege'] = ($stu_train['keer_hege']+1);
            }
            $train_data['updatetime'] = $params['createtime'];
            $this->trainstatistic->where(['stu_id'=>$params['stu_id']])->update($train_data);
        }
        
        if($events){
            foreach($events as $v){
                $v['report_card_id'] = $id;
                $v['car_type'] = 0;
                $res = Db::name('deduct_points')->insert($v);
            }
            $this->success('成绩单上传成功');
        }
        if($res && $report){
            $this->success('成绩单上传成功');
        }
        $this->error('添加失败');
    }

    public function study_logtest()
    {
        $params = $this->request->post();
        $params['stu_id'] = 'CSN20210930144134517999';
        $params['ordernumber'] = 'CON20220601120759313331';
        $params['status'] = 1;
        $params['process_name'] = 'jichubj';
        $params['starttime'] = 50;
        // Cache::set('test_study_log',$params);
        if(empty($params['stu_id']) || empty($params['ordernumber'])|| empty($params['process_name'])|| empty($params['starttime'])){
            $this->error('参数缺失');
        }
        $place = null;
        $order = $this->order->with(['student'])->where(['ordernumber'=>$params['ordernumber']])->find();
        if(array_key_exists('kaochang',$params)){
            $place = $this->place->where(['str_name'=>$params['kaochang']])->find();
            if($place){
                $params['place_id'] = $place['id'];
            }
            unset($params['kaochang']);
        }
        $params['createtime'] = time();
        $params['study_time'] = $params['starttime'];
        $params['space_id'] = $order['space_id'];
        $params['cooperation_id'] = $order['cooperation_id'];
        $res = null;
        if($params['starttime']<3600){
            $res = $this->studyprocess->insert($params);
            if($res){
                $stu_train = $this->trainstatistic->where(['stu_id'=>$params['stu_id']])->find();
                if(!$stu_train){
                    $train_data['stu_id'] = $params['stu_id'];
                    $train_data['cooperation_id'] = $params['cooperation_id'];
                    $train_data['space_id'] = $params['space_id'];
                    $train_data['createtime'] = $params['createtime'];
                    if($place){
                        //科目三
                        $train_data['kesan_leiji'] = $params['starttime'];
                    }else{
                        //科目二
                        $train_data['keer_leiji'] = $params['starttime'];
                    }
                    $this->trainstatistic->insert($train_data);
                }else{
                    if($place){
                        //科目三
                        $train_data['kesan_leiji'] = $stu_train['kesan_leiji']+$params['starttime'];
                    }else{
                        //科目二
                        $train_data['keer_leiji'] = $stu_train['keer_leiji']+$params['starttime'];
                    }
                    $train_data['updatetime'] = $params['createtime'];
                    $this->trainstatistic->where(['stu_id'=>$params['stu_id']])->update($train_data);
                }
            }
        }
        if($res){
            $this->success('返回成功');
        }
        $this->error('返回失败');
    }


     /**
     * 成绩单信息
     */
    public function reportcardtest()
    {
        // $params = $this->request->post();
        $params['ordernumber'] = 'CON20231101161503316398';
        $params['subject_type'] = 'subject2';
        $params['score'] = '90';
        $params['kaochang'] = 'kaoshi';
        $params['createtime'] = time();
        $params['events'] = [
            [
                'subject'=>'侧方位扣分',
                'guize'=>'中途停车',
                'score'=>90,
                'pass_time'=> 30
            ],
            [
                'subject'=>'上车事项',
                'guize'=>'未系安全带',
                'score'=>20,
                'pass_time'=>''
            ]
        ];

        if(empty($params['ordernumber'])|| empty($params['subject_type'])|| empty($params['kaochang'])){
            $this->error('参数缺失');
        }
        $events = $params['events'];

        unset($params['events']);
        $order = $this->order->where(['ordernumber'=>$params['ordernumber']])->find();
        if(!$order){
            $this->error('查询订单错误');
        } 
        $params['stu_id'] = $order['stu_id'];
        $params['coach_id'] = $order['coach_id'];
        $params['machine_id'] = $order['machine_id'];
        $params['cooperation_id'] = $order['cooperation_id'];
        $params['space_id'] = $order['space_id'];
        $params['createtime'] = (int)$params['createtime'];
        $params['endtime'] = time();
        $params['pass_time'] = ($params['endtime'] - $params['createtime']);
        $report= $this->reportcard->save($params);
        $id = $this->reportcard->getLastInsID();
        $stu_train = $this->trainstatistic->where(['stu_id'=>$params['stu_id']])->find();
        $warn_exam = $this->warnexam->where(['cooperation_id'=>$order['cooperation_id'],'status'=>1])->find();
        if(!$stu_train && $warn_exam){
            $train_data['stu_id'] = $params['stu_id'];
            $train_data['cooperation_id'] = $params['cooperation_id'];
            $train_data['space_id'] = $params['space_id'];
            $train_data['createtime'] = $params['createtime'];
            if( $params['score'] >= $warn_exam['kesan_score']){
                $train_data['keer_hege'] = 1;
                $this->trainstatistic->save($train_data);
            }else{
                $train_data['keer_hege'] = 0;
            }
        }elseif($stu_train && $warn_exam){
            if($params['subject_type'] == 'subject3'){
                //科目三
                $train_data['kesan_hege'] = ($stu_train['kesan_hege']+1);
            }else{
                //科目二
                $train_data['keer_hege'] = ($stu_train['keer_hege']+1);
            }
            $train_data['updatetime'] = $params['createtime'];
            $this->trainstatistic->where(['stu_id'=>$params['stu_id']])->update($train_data);
        }
        
        if($events){
            foreach($events as $v){
                $v['report_card_id'] = $id;
                $res = Db::name('deduct_points')->insert($v);
            }
            $this->success('成绩单上传成功');
        }
        if($res && $report){
            $this->success('成绩单上传成功');
        }
        $this->error('添加失败');
    }

    public function getreportcard()
    {
        $res = Cache::get('reportcardtest');
        var_dump($res);
    }




    public function logo_message()
    {
        $params = $this->request->post();
        $params['machine_code'] = '10020';
        if(empty($params['machine_code'])){
            $this->error('参数缺失');
        }
        $res = $this->machinecar->with(['admin'])->where(['machine_code'=>$params['machine_code']])->find();
        $data['image_url'] = $res['avatar'];
    }

    /**
     * 上机获取二维码
     */
    public function requestcode(){
        $params = $this->request->post();
        // $params['machine_code'] = 'FJSZxmg202311008fdone';
        if(empty($params['machine_code'])){
            $this->error('参数缺失');
        }

        $machine_code = $params['machine_code'];
        $machinecar = $this->machinecar->where('machine_code',$machine_code)->find();
        if($machinecar){
            $data['student_code'] = $machinecar['student_code'];
            if($machinecar['space_id'] == 41){

                $res = Cache::get('machine_'.$machinecar['machine_code']);
                $data['student_code'] = $this->qt_boot->get_qr_code($machinecar['machine_code']);
                if($res){
                    $data['student_code'] = '/uploads/code/student/3x3.png';
                }
            }
            $this->success('成功',$data);
        }else{
            $this->error('获取二维码失败');
        }

    }


    public function requestcodetest(){
        $params = $this->request->post();
        $params['machine_code'] = '10022';
        if(empty($params['machine_code'])){
            $this->error('参数缺失');
        }
        $machine_code = $params['machine_code'];
        $machinecar = $this->machinecar->where('machine_code',$machine_code)->find();
        if($machinecar){
            $data['student_code'] = $machinecar['student_code'];
            if($machinecar['space_id'] == 41){
                $res = Cache::get('machine_'.$machinecar['machine_code']);
                $data['student_code'] = $this->qt_boot->get_qr_code($machinecar['machine_code']);
                if($res){
                    $data['student_code'] = '/uploads/code/student/3x3.png';
                }
            }
            $this->success('成功',$data);
        }else{
            $this->error('获取二维码失败');
        }

    }

    public function puttest()
    {
        $res = Cache::get('order1');
        $machine_code = Cache::pull('testmachine');
        var_dump($machine_code,$res);
    }

    public function putmachine_t(){
        $params = $this->request->post();
        // $params['machine_code'] = 'FJSZxmg202306003fdone';
        if(empty($params['machine_code'])){
            $this->error('参数缺失');
        }
        $machine_code = $params['machine_code'];
        $res = Cache::pull('machine_'.$machine_code);
        if($res){
            $data = $res;
            $data['student_type'] = $res['student_type'];
            $data['ordernumber'] = $res['ordernumber'];
            if($res['student_type'] == 'student'){
                $order = $this->order->with(['student','space'])->where('ordernumber',$res['ordernumber'])->find();
                $student = $order['student'];
            }elseif($res['student_type'] == 'intent_student'){
                $order = $this->temporaryorder->with(['intentstudent','space'])->where('ordernumber',$res['ordernumber'])->find();
                $student = $order['intentstudent'];
                $student['idcard'] = '';
            }
            $data['starttime'] = (string)$order['starttime'];
            if($order['endtime'] == NULL){
                $data['endtime'] = '';
            }else{
                $data['endtime'] = (string)$order['endtime'];
            }
            $data['should_endtime'] = (string)$order['should_endtime'];
            $data['stu_name'] = $student['name'];
            $data['stu_id'] = $student['stu_id'];
            $data['phone'] = $student['phone'];
            $data['subject_type'] = $order['subject_type'];
            $data['subject_type_text'] = $order['subject_type_text'];
            $data['car_type'] = $order['car_type'];
            $data['car_type_text'] = $order['car_type_text'];

            $data['idcard'] = $student['idcard'];
            $keersort = $this->keersort->where(['space_id'=>$order['space_id']])->find();
            $data['pass_status'] = (int)$order['space']['pass_status'];
            $kesansort = $this->kesansort->where(['space_id'=>$order['space_id']])->find();
            $sort_info = $this->get_study_number($keersort,$kesansort,$student,$data['student_type']);
            $data['study_process'] = [
                'keer_process'=> explode(',',$keersort['sequence']),
                'keer_c1_number'=>$sort_info['keer_c1_number'],
                'keer_c2_number'=>$sort_info['keer_c2_number'],
                'keer_study_number'=>$sort_info['keer_study_number'],
                'kesan_process'=>explode(',',$kesansort['sequence']),
                'kesan_c1_number'=>$sort_info['kesan_c1_number'],
                'kesan_c2_number'=>$sort_info['kesan_c2_number'],
                'kesan_study_number'=>$sort_info['kesan_study_number'],
            ];
            // $data['student_type'] = 'student';
            // $data['pass_status'] = 0;
            
            unset($data['address']);
            unset($data['imei']);
            unset($data['sim']);
            unset($data['sn']);
            unset($data['study_machine']);
            unset($data['terminal_equipment']);
            // $data['curator_code'] = $this->curatorcode( $machine_code,$res['ordernumber'],$data['student_type']);
            // $data['coach_code'] = $this->coachcode( $machine_code,$res['ordernumber'],$data['student_type']);
            $this->success('返回成功',$data);
        }else{
            $this->error('返回学员信息失败');
        }
    }

    public function putmachine_test(){
        $params = $this->request->post();
        $params['machine_code'] = 'FJSZxmg202306003fdone';
        if(empty($params['machine_code'])){
            $this->error('参数缺失');
        }
        $machine_code = $params['machine_code'];
        $res = Cache::get('machine_'.$machine_code);
        if($res){
            $data = $res;
            $data['student_type'] = $res['student_type'];
            $data['ordernumber'] = $res['ordernumber'];
            if($res['student_type'] == 'student'){
                $order = $this->order->with(['student','space'])->where('ordernumber',$res['ordernumber'])->find();
                $student = $order['student'];
            }elseif($res['student_type'] == 'intent_student'){
                $order = $this->temporaryorder->with(['intentstudent','space'])->where('ordernumber',$res['ordernumber'])->find();
                $student = $order['intentstudent'];
                $student['idcard'] = '';
            }
            $data['starttime'] = (string)$order['starttime'];
            if($order['endtime'] == NULL){
                $data['endtime'] = '';
            }else{
                $data['endtime'] = (string)$order['endtime'];
            }
            $data['should_endtime'] = (string)$order['should_endtime'];
            $data['stu_name'] = $student['name'];
            $data['stu_id'] = $student['stu_id'];
            $data['phone'] = $student['phone'];
            $data['subject_type'] = $order['subject_type'];
            $data['subject_type_text'] = $order['subject_type_text'];
            $data['car_type'] = $student['car_type'];
            $data['car_type_text'] = $order['car_type_text'];

            $data['idcard'] = $student['idcard'];
            $keersort = $this->keersort->where(['space_id'=>$order['space_id']])->find();
            $data['pass_status'] = (int)$order['space']['pass_status'];
            $kesansort = $this->kesansort->where(['space_id'=>$order['space_id']])->find();
            $sort_info = $this->get_study_number($keersort,$kesansort,$student,$data['student_type']);
            $data['study_process'] = [
                'keer_process'=> explode(',',$keersort['sequence']),
                'keer_c1_number'=>$sort_info['keer_c1_number'],
                'keer_c2_number'=>$sort_info['keer_c2_number'],
                'keer_study_number'=>$sort_info['keer_study_number'],
                'kesan_process'=>explode(',',$kesansort['sequence']),
                'kesan_c1_number'=>$sort_info['kesan_c1_number'],
                'kesan_c2_number'=>$sort_info['kesan_c2_number'],
                'kesan_study_number'=>$sort_info['kesan_study_number'],
            ];
            // $data['student_type'] = 'student';
            // $data['pass_status'] = 0;
            
            unset($data['address']);
            unset($data['imei']);
            unset($data['sim']);
            unset($data['sn']);
            unset($data['study_machine']);
            unset($data['terminal_equipment']);
            // $data['curator_code'] = $this->curatorcode( $machine_code,$res['ordernumber'],$data['student_type']);
            // $data['coach_code'] = $this->coachcode( $machine_code,$res['ordernumber'],$data['student_type']);
            $this->success('返回成功',$data);
        }else{
            $this->error('返回学员信息失败');
        }
    }


    public function place_get_processtest()
    {
        $params = $this->request->post();
        $params['str_name'] = 'ZH_gangwan01_ZhuHai_GangWanA_3_1';
        $params['ordernumber'] = 'CON20211222113432510819';
        $params['student_type'] = 'intent_student';
        if(empty($params['str_name'])|| empty($params['ordernumber']) || empty($params['student_type'])){
            $this->error('参数缺失');
        }
        $student_type = $params['student_type'];
        if($student_type == 'student'){
            $order = $this->order->with(['student','space'])->where('ordernumber',$params['ordernumber'])->find();
            $student = $order['student'];
        }elseif($student_type== 'intent_student'){
            $order = $this->temporaryorder->with(['intentstudent','space'])->where('ordernumber',$params['ordernumber'])->find();
            $student = $order['intentstudent'];
            $student['idcard'] = '';
        }
        $keersort = $this->keersort->where(['space_id'=>$order['space_id']])->find();
        // var_dump($order['space_id']);exit;
        $where_kesan['space_id'] = $order['space_id'];
        $where_kesan['place.str_name'] = $params['str_name'];
        $kesansort = $this->kesansort->with('place')->where($where_kesan)->find();
        // var_dump($kesansort);exit;

        // if(!$kesansort){
        //     $this->error('当前数据错误');
        // }
        $sort_info = $this->get_study_number_test($keersort,$kesansort,$student,$student_type);

        $data['study_process'] = [
            'kesan_process'=>explode(',',$kesansort['sequence']),
            'kesan_c1_number'=>$sort_info['kesan_c1_number'],
            'kesan_c2_number'=>$sort_info['kesan_c2_number'],
            'kesan_study_number'=>$sort_info['kesan_study_number'],
        ];

        $this->success('返回成功',$data);
    }

    //获取学员练习进度记录
    public function place_get_process()
    {
        $params = $this->request->post();
        // $params['str_name'] = 'NC_Jinling01_NanChang_JinLingA_3_1';
        // $params['ordernumber'] = 'CON20211207195757141775';
        // $params['student_type'] = 'student';
        if(empty($params['str_name'])|| empty($params['ordernumber']) || empty($params['student_type'])){
            $this->error('参数缺失');
        }
        $student_type = $params['student_type'];
        if($student_type == 'student'){
            $order = $this->order->with(['student','space'])->where('ordernumber',$params['ordernumber'])->find();
            $student = $order['student'];
        }elseif($student_type== 'intent_student'){
            $order = $this->temporaryorder->with(['intentstudent','space'])->where('ordernumber',$params['ordernumber'])->find();
            $student = $order['intentstudent'];
            $student['idcard'] = '';
        }
        $keersort = $this->keersort->where(['space_id'=>$order['space_id']])->find();
        $where_kesan['space_id'] = $order['space_id'];
        $where_kesan['place.str_name'] = $params['str_name'];
        $kesansort = $this->kesansort->with('place')->where($where_kesan)->find();
        if(!$kesansort){
            $this->error('当前数据错误');
        }
        $sort_info = $this->get_study_number_test($keersort,$kesansort,$student,$student_type);

        $data['study_process'] = [
            'kesan_process'=>explode(',',$kesansort['sequence']),
            'kesan_c1_number'=>$sort_info['kesan_c1_number'],
            'kesan_c2_number'=>$sort_info['kesan_c2_number'],
            'kesan_study_number'=>$sort_info['kesan_study_number'],
        ];

        $this->success('返回成功',$data);
    }

    public function str_to_int($str)
    {
        $list = [];
        foreach($str as $k=>$v){
            $list[$k] = (int)$v;
        }
        return $list;
    }

    public function get_study_number_test($keersort,$kesansort,$student,$student_type)
    {
        $keer_c1_number = $this->str_to_int(explode(',',$keersort['keer_c1_number']));
        $keer_c2_number = $this->str_to_int(explode(',',$keersort['keer_c2_number']));
        $kesan_c1_number = $this->str_to_int(explode(',',$kesansort['kesan_c1_number']));
        $kesan_c2_number = $this->str_to_int(explode(',',$kesansort['kesan_c2_number']));

        $keer_list = [];
        $kesan_list = [];
        if($student_type == 'student'){
            $student['keer_study_sign'] = explode(',',$student['keer_study_sign']);
            $keer_sequence = $keersort['sequence'];
            $keer_res = explode(',',$keer_sequence);
            foreach($keer_res as $k=>$v){
                if(in_array($v,$student['keer_study_sign'])){
                    $keer_c1_number[array_search($v,$keer_res)] = 0;
                    $keer_c2_number[array_search($v,$keer_res)] = 0;
                }
                $where['process_name'] = $v;
                $where['stu_id'] = $student['stu_id'];
                $where['status'] = 1;
                $keer_list[$k] = $this->studyprocess->where($where)->count();
            }
            $student['kesan_study_sign'] = explode(',',$student['kesan_study_sign']);
            $kesan_sequence = $kesansort['sequence'];
            $kesan_res = explode(',',$kesan_sequence);
            foreach($kesan_res as $k=>$v){
                if(in_array($v,$student['kesan_study_sign'])){
                    $kesan_c1_number[array_search($v,$kesan_res)] = 0;
                    $kesan_c2_number[array_search($v,$kesan_res)] = 0;
                }
                $where['process_name'] = $v;
                $where['stu_id'] = $student['stu_id'];
                $where['status'] = 1;
                if(array_key_exists('place',$kesansort)){
                    $where['place_id'] = $kesansort['place']['id'];
                }
                $kesan_list[$k] = $this->studyprocess->where($where)->count();
            }
        }else{
            $keer_c1_number_new = [];
            $keer_c2_number_new = [];
            foreach($keer_c1_number as $k=>$v){
                $keer_c1_number_new[$k] = $keer_c1_number[$k];
                $keer_c2_number_new[$k] = $keer_c2_number[$k];
                if($keer_c1_number[$k] !== -1){
                    $keer_c1_number_new[$k]= 0;
                }
                if($keer_c2_number[$k] !== -1){
                    $keer_c2_number_new[$k] = 0;
                }
                $keer_list[$k] = 0;
            }
            $keer_c1_number = $keer_c1_number_new;
            $keer_c2_number = $keer_c2_number_new;

            $kesan_c1_number_new = [];
            $kesan_c2_number_new = [];
            foreach($kesan_c1_number as $k=>$v){
                $kesan_c1_number_new[$k] = $kesan_c1_number[$k];
                $kesan_c2_number_new[$k] = $kesan_c2_number[$k];
                if($kesan_c1_number[$k] != -1){
                    $kesan_c1_number_new[$k]= 0;
                }
                if($kesan_c2_number[$k] !== -1){
                    $kesan_c2_number_new[$k] = 0;
                }
                $kesan_list[$k] = 0;
            }

            $kesan_c1_number = $kesan_c1_number_new;
            $kesan_c2_number = $kesan_c2_number_new;
        }
        $sort['keer_study_number'] = $keer_list;
        $sort['keer_c1_number'] = $keer_c1_number;
        $sort['keer_c2_number'] = $keer_c2_number;
        $sort['kesan_study_number'] = $kesan_list;
        $sort['kesan_c1_number'] = $kesan_c1_number;
        $sort['kesan_c2_number'] = $kesan_c2_number;
        return $sort;
    }

    public function get_study_number($keersort,$kesansort,$student,$student_type)
    {
        $keer_c1_number = $this->str_to_int(explode(',',$keersort['keer_c1_number']));
        $keer_c2_number = $this->str_to_int(explode(',',$keersort['keer_c2_number']));
        $kesan_c1_number = $this->str_to_int(explode(',',$kesansort['kesan_c1_number']));
        $kesan_c2_number = $this->str_to_int(explode(',',$kesansort['kesan_c2_number']));

        $keer_list = [];
        $kesan_list = [];
        if($student_type == 'student'){
            $student['keer_study_sign'] = explode(',',$student['keer_study_sign']);
            $keer_sequence = $keersort['sequence'];
            $keer_res = explode(',',$keer_sequence);
            foreach($keer_res as $k=>$v){
                if(in_array($v,$student['keer_study_sign'])){
                    $keer_c1_number[array_search($v,$keer_res)] = 0;
                    $keer_c2_number[array_search($v,$keer_res)] = 0;
                }
                $where['process_name'] = $v;
                $where['stu_id'] = $student['stu_id'];
                $where['status'] = 1;
                $keer_list[$k] = $this->studyprocess->where($where)->count();
            }
            $student['kesan_study_sign'] = explode(',',$student['kesan_study_sign']);
            $kesan_sequence = $kesansort['sequence'];
            $kesan_res = explode(',',$kesan_sequence);
            foreach($kesan_res as $k=>$v){
                if(in_array($v,$student['kesan_study_sign'])){
                    $kesan_c1_number[array_search($v,$kesan_res)] = 0;
                    $kesan_c2_number[array_search($v,$kesan_res)] = 0;
                }
                $where['process_name'] = $v;
                $where['stu_id'] = $student['stu_id'];
                $where['status'] = 1;
                $kesan_list[$k] = $this->studyprocess->where($where)->count();
            }
        }else{
            $keer_c1_number_new = [];
            $keer_c2_number_new = [];
            foreach($keer_c1_number as $k=>$v){
                $keer_c1_number_new[$k] = $keer_c1_number[$k];
                $keer_c2_number_new[$k] = $keer_c2_number[$k];
                if($keer_c1_number[$k] !== -1){
                    $keer_c1_number_new[$k]= 0;
                }
                if($keer_c2_number[$k] !== -1){
                    $keer_c2_number_new[$k] = 0;
                }
                $keer_list[$k] = 0;
            }
            $keer_c1_number = $keer_c1_number_new;
            $keer_c2_number = $keer_c2_number_new;

            $kesan_c1_number_new = [];
            $kesan_c2_number_new = [];
            foreach($kesan_c1_number as $k=>$v){
                $kesan_c1_number_new[$k] = $kesan_c1_number[$k];
                $kesan_c2_number_new[$k] = $kesan_c2_number[$k];
                if($kesan_c1_number[$k] != -1){
                    $kesan_c1_number_new[$k]= 0;
                }
                if($kesan_c2_number[$k] !== -1){
                    $kesan_c2_number_new[$k] = 0;
                }
                $kesan_list[$k] = 0;
            }

            $kesan_c1_number = $kesan_c1_number_new;
            $kesan_c2_number = $kesan_c2_number_new;
        }
        $sort['keer_study_number'] = $keer_list;
        $sort['keer_c1_number'] = $keer_c1_number;
        $sort['keer_c2_number'] = $keer_c2_number;
        $sort['kesan_study_number'] = $kesan_list;
        $sort['kesan_c1_number'] = $kesan_c1_number;
        $sort['kesan_c2_number'] = $kesan_c2_number;
        return $sort;
    }

    public function get_study_numbertest($keersort,$student,$student_type)
    {
        $keer_c1_number = $this->str_to_int(explode(',',$keersort['keer_c1_number']));
        $keer_c2_number = $this->str_to_int(explode(',',$keersort['keer_c2_number']));
        if($student_type == 'student'){
            $student['keer_study_sign'] = $student['keer_study_sign'];
            $student['kesan_study_sign'] =$student['kesan_study_sign'];
            $sequence = $keersort['sequence'];
            $res = explode(',',$sequence);
            foreach($res as $k=>$v){
                if(in_array($v,$student['keer_study_sign'])){
                    $keer_c1_number[array_search($v,$res)] = 0;
                    $keer_c2_number[array_search($v,$res)] = 0;
                }
                $where['process_name'] = $v;
                $where['stu_id'] = $student['stu_id'];
                $where['status'] = 1;
                $list[$k] = $this->studyprocess->where($where)->count();
            }
        }else{
            foreach($keer_c1_number as $k=>$v){
                $list[$k] = 0;
            }
        }
        $keersort['study_number'] = $list;
        $keersort['keer_c1_number'] = $keer_c1_number;
        $keersort['keer_c2_number'] = $keer_c2_number;
        return $keersort;
    }


    /**
     * 获取教员馆长授权码二维码并返回学员信息
     */
    public function putmachine(){
        $params = $this->request->post();
        // $params['machine_code'] = '10020';
        if(empty($params['machine_code'])){
            $this->error('参数缺失');
        }
        $machine_code = $params['machine_code'];
        $res = Cache::pull('machine_'.$machine_code);
        if($res){
            $data = $res;
            $data['student_type'] = $res['student_type'];
            $data['ordernumber'] = $res['ordernumber'];
            // $status = (int)Cache::get('order_first_status_'.$res['ordernumber']);
            // $data['order_first_status'] = $status;
            if($res['student_type'] == 'student'){
                $order = $this->order->where('ordernumber',$res['ordernumber'])->find();
                // $orderlevel = $this->order->where('stu_id', $res['stu_id'])->order('id desc')->select();
                $student = $order['student'];
            }else{
                $order = $this->temporaryorder->with(['intentstudent'])->where('ordernumber',$res['ordernumber'])->find();
                $student = $order['intentstudent'];
                $student['idcard'] = '';
            }
            $data['starttime'] = (string)$order['starttime'];
            if($order['endtime'] == NULL){
                $data['endtime'] = '';
            }else{
                $data['endtime'] = (string)$order['endtime'];
            }
            $data['should_endtime'] = (string)$order['should_endtime'];
            $data['stu_name'] = $student['name'];
            $data['stu_id'] = $student['stu_id'];
            $data['phone'] = $student['phone'];
            $data['subject_type'] = $order['subject_type'];
            $data['subject_type_text'] = $order['subject_type_text'];
            $data['car_type'] = $order['car_type'];
            $data['car_type_text'] = $order['car_type_text'];
            $data['idcard'] = $student['idcard'];
            // var_dump($student->toArray());exit;

            unset($data['address']);
            unset($data['imei']);
            unset($data['sim']);
            unset($data['sn']);
            unset($data['study_machine']);
            unset($data['terminal_equipment']);
            // $data['curator_code'] = $this->curatorcode( $machine_code,$res['ordernumber'],$data['student_type']);
            // $data['coach_code'] = $this->coachcode( $machine_code,$res['ordernumber'],$data['student_type']);
            $this->success('返回成功',$data);
        }else{
            $this->error('返回学员信息失败');
        }
    }

    public function curatorcode($machine_code,$ordernumber,$student_type)
    {
        //获取token
        $APPID = 'wxc4975f7bb938dbd4';
        $AppSecret = 'd8c6d15f97da17a70e4bb31cb7fa7c10';
        $tokenUrl= "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" .$APPID ."&secret=".$AppSecret;
        $getArr=array();
        $tokenArr=json_decode($this->common->send_post($tokenUrl,$getArr,"GET"));
        $access_token=$tokenArr->access_token;
        //生成二维码
        $path="pages/orderauth/orderauth?machine_code=".$machine_code.'&ordernumber='.$ordernumber.'&student_type='.$student_type;
        $width=500;
        $data = [
            'access_token'=>$access_token,
            "path"=>$path,
        ];
        $post_data= json_encode($data,true);
        $url="https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token=".$access_token;
        $result= $this->common->api_notice_increment($url,$post_data);
        $date = time().mt_rand(100000, 999999);
        $filepath = ROOT_PATH."public/uploads/code/booot";
        if(!file_exists($filepath))
            $mkdir_file_dir = mkdir($filepath,0777,true);
        $imagepath = '/uploads/code/boot/curatorcode'.$machine_code.$date.'.jpg';
        file_put_contents(ROOT_PATH.'public'.$imagepath,$result);
        return $imagepath;
    }

    public function coachcode($machine_code,$ordernumber,$student_type)
    {
        //获取token
        $APPID = 'wx65ab2477b850e783';
        $AppSecret = '269770e1921892000a49135a60e0c55a';
        $tokenUrl= "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" .$APPID ."&secret=".$AppSecret;
        $getArr=array();
        $tokenArr=json_decode($this->common->send_post($tokenUrl,$getArr,"GET"));
        $access_token=$tokenArr->access_token;
        //生成二维码
        $path="pages/orderauth/orderauth?machine_code=".$machine_code.'&ordernumber='.$ordernumber.'&student_type='.$student_type;
        $width=500;
        $data = [
            'access_token'=>$access_token,
            "path"=>$path,
        ];
        $post_data= json_encode($data,true);
        $url="https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token=".$access_token;
        $result= $this->common->api_notice_increment($url,$post_data);
        $date = time().mt_rand(100000, 999999);
        $imagepath = '/uploads/code/boot/coachcode'.$machine_code.$date.'.jpg';
        file_put_contents(ROOT_PATH.'public'.$imagepath,$result);
        return $imagepath;
    }

    

    public function get_upload_log()
    {
        $res = Cache::get('upload');
        var_dump($res);
        // 获取文件后缀名
        // $r = file_put_contents($name, $res);
        // $aa = move_uploaded_file($res['ABC']['tmp_name'], ROOT_PATH.'public/uploads/machine_log/123.text');
        // if($r){
        //     $this->success('成功');
        // }else{
        //     $this->error('失败');
        // }
        // if ( ($size < 512000000)  && $extension =="log")// 小于 5M 后缀名log
        // {
        //     if ($res["ABC"]["error"] > 0)
        //     {
        //         $this->error( "错误：: " . $res["ABC"]["error"] . "<br>");
        //     }
        // }else
        // {
        //     $this->error('非法的文件');
        // }

        // $time = date("Ymdhis");
        // $data = date("Ymd");
        // $uploadError = false;
        // $filepath = ROOT_PATH."public/uploads/machine_log/".$data;
        // if(!file_exists($filepath))
        //     $mkdir_file_dir = mkdir($filepath,0777,true); //获取到标题，在最终的目录下面建立一个文件夹用来存放分类指
        // $tmpFilePath = $res['ABC']['tmp_name'];
        // if ($tmpFilePath != "")
        // {
        //     $newname = $time.'-'.$res['ABC']['name'];
        //     $newFilePath = $filepath.'/'.$newname;
        //     if (!move_uploaded_file($tmpFilePath, $newFilePath))
        //         $uploadError = true;
        // }
        // if ($uploadError)
        //     $this->error('上传失败');
        // else
        //     $this->success('Uploaded Successfully');
    }


    public function uploadLogtest()
    {
        // $upload_name = Cache::get('upload_name');
        // $upload_size = Cache::get('upload_size');
        $files = Cache::get('files_log');
        // var_dump($upload_name,$upload_size);
        var_dump($files);
        exit;
    }

    /**
     * 获取模拟机日志
     */
    public function uploadLog()
    {
        // $file =  file_get_contents('php://input');
        // $file = $_FILES['files'];
        Cache::set('files_log',$_FILES);
        $name = $_FILES['files']['name'];
        $size = $_FILES["files"]["size"];
        // Cache::set('upload_name',$name);
        // Cache::set('upload_size',$size);
        // Cache::set('file',$file);
        // $this->success('Uploaded Successfully');
        // var_dump($res);
        $time = date("Ymdhis");
        $data = date("Ymd");
        $nameArray = explode(".",$name);
        $extension = end($nameArray);
        if ( ($size < 1024*1024*5)  && $extension =="log")// 小于 5M 后缀名log
        {
            if ($_FILES["files"]["error"] > 0)
            {
                $this->error( "错误：: " . $_FILES["files"]["error"] . "<br>");
            }
        }else
        {
            $this->error('非法的文件');
        }
        $uploadError = false;
        $filepath = ROOT_PATH."public/uploads/machine_log/".$data;
        if(!file_exists($filepath))
            mkdir($filepath,0777,true); //获取到标题，在最终的目录下面建立一个文件夹用来存放分类指
        $tmpFilePath = $_FILES['files']['tmp_name'];
        if ($tmpFilePath != "")
        {
            $newname = $time.'-'.$_FILES['files']['name'];
            $newFilePath = $filepath.'/'.$newname;
            if (!move_uploaded_file($tmpFilePath, $newFilePath))
                $uploadError = true;
        }
        if ($uploadError)
            $this->error('上传失败');
        else
            $insert['machine_code'] = $_FILES['files']['name'];
            $insert['path'] = "uploads/machine_log/".$data."/".$newname;
            $insert['createtime'] = time();
            Db::name('machine_log')->insert($insert);
            $this->success('Uploaded Successfully');
    }

    /**
     * 模拟机开机
     */
    public function machine_start(){
        $params = $this->request->post();
        // $params['ordernumber'] = 'CON20231225180321586936';
        // $params['student_type'] = 'student';
        if(empty($params['ordernumber']) || empty($params['student_type'])){
            $this->error('参数缺失');
        }
        $ordernumber = $params['ordernumber'];
        $student_type = $params['student_type'];
        
        // Cache::set('order_first_status_'.$ordernumber,1,3600*2);
        $update['order_status'] = 'executing';

        if($student_type == 'student'){
            $order = $this->order->with(['space','machinecar','student'])->where('ordernumber',$ordernumber)->find();
            if(!$order){
                $this->error('订单错误');
            }
            $machinecar = $order['machinecar'];
            unset($order['machinecar']);
            $stu = $order['student'];
            $stu['student_type'] = 'student';
            unset($order['student']);
        }else{
            $order = $this->temporaryorder->with(['space','machinecar','intent_student'])->where('ordernumber',$ordernumber)->find();
            if(!$order){
                $this->error('订单错误');
            }
            $machinecar = $order['machinecar'];
            unset($order['machinecar']);
            $stu = $order['intent_student'];
            $stu['student_type'] = 'intent_student';
            unset($order['intent_student']);
        }
        $order_length = $order['space']['order_length'];
        $order_time = $order_length*60*60;
        if($student_type == 'student'){
            $jishi_data['IdCard'] = $stu['idcard'];
            if($order['subject_type'] == 'subject2'){
                $jishi_data['subject'] = '2';
            }else{
                $jishi_data['subject'] = '3';
            }
            $jishi_data['subject'] = $jishi_data['subject'];
            $jishi_data['SN'] = $machinecar['machine_code'];
            $this->jishi->login($jishi_data);
        }
        if($order['starttime']){
            $order->save($update);
            $update['starttime'] = $order['starttime'];
            $update['should_endtime'] = $order['should_endtime'];
        }else{
            $update['starttime'] = time();

            // $update['should_endtime'] = time()+(3600*2+180);
            $coo = $this->cooperation->where(['cooperation_id'=>$order['cooperation_id']])->find();
            $stu = $stu->toArray();
            if($coo['forbidden_tmp_stu'] == 1 && $student_type == 'student'){
                $update1['order_num'] = $stu['order_num'] -1;
                $this->student->where(['stu_id'=>$order['stu_id']])->update($update1);
            }

            // if($order['machine_id'] == 8){
            $update['should_endtime'] = time()+$order_time+180;
            // }
            $ordertime = $this->ordertime->where(['cooperation_id'=>$order['cooperation_id']])->find();
            if($ordertime){
                if($ordertime['state'] == 1){
                    if($student_type == 'student'){
                        if($stu['cooperation_id'] !== $order['cooperation_id']){
                            $update['should_endtime'] = time()+$ordertime['tem_time_limit']*60;
                        }
                    }else{
                        $update['should_endtime'] = time()+$ordertime['tem_time_limit']*60;
                    }
                    $stu_num = Db::name('stu_number')->where(['stu_id'=>$order['stu_id']])->find();

                    if(!$stu_num){
                        $insert_stu_number['cooperation_id'] = $order['cooperation_id'];
                        $insert_stu_number['space_id'] = $order['space_id'];
                        $insert_stu_number['stu_id'] = $order['stu_id'];
                        $insert_stu_number['createtime'] = time();
                        Db::name('stu_number')->insert($insert_stu_number);
                    }
                }
            }
            $fujia['order'] = $order->toArray();
            $fujia['student'] = $stu;
            $url = "https://admin.aivipdriver.com/api/xiaomaguan/student/add_ai_order";
            $res = $this->common->post($url,$fujia);
            // if($res){
            //     $json_res = json_decode($res,1);
            // }

            $order->save($update);
        }
        $order_log['ordernumber'] = $ordernumber;
        $order_log['start_or_end'] = 1;
        $order_log['createtime'] = time();
        $res = Db::name('order_log')->insert($order_log);

        $update['should_endtime'] = (string)$update['should_endtime'];
        $update['starttime'] = (string)$update['starttime'];
        



        if($res){
            $this->success('返回成功',$update);
        }else{
            $this->success('登录返回失败');
        }
    }


    public function machine_starttest(){
        $params = $this->request->post();
        $params['ordernumber'] = 'CON20231026145711338580';
        $params['student_type'] = 'student';
        if(empty($params['ordernumber']) || empty($params['student_type'])){
            $this->error('参数缺失');
        }
        $ordernumber = $params['ordernumber'];
        $student_type = $params['student_type'];
        
        // Cache::set('order_first_status_'.$ordernumber,1,3600*2);
        $update['order_status'] = 'executing';

        if($student_type == 'student'){
            $order = $this->order->with('space')->where('ordernumber',$ordernumber)->find();
        }else{
            $order = $this->temporaryorder->with('space')->where('ordernumber',$ordernumber)->find();
        }
        $order_length = $order['space']['order_length'];
        $order_time = $order_length*60*60;

        if($order['starttime']){
            $order->save($update);
            $update['starttime'] = $order['starttime'];
            $update['should_endtime'] = $order['should_endtime'];
        }else{
            $update['starttime'] = time();
            // $update['should_endtime'] = time()+(3600*2+180);
            $coo = $this->cooperation->where(['cooperation_id'=>$order['cooperation_id']])->find();
            $stu = $this->student->where(['stu_id'=>$order['stu_id']])->find();

            if($coo['forbidden_tmp_stu'] == 1 && $student_type == 'student'){
                $update1['order_num'] = $stu['order_num'] -1;
                $this->student->where(['stu_id'=>$order['stu_id']])->update($update1);
            }
            // if($order['machine_id'] == 8){
            $update['should_endtime'] = time()+$order_time+180;
            // }
            $ordertime = $this->ordertime->where(['cooperation_id'=>$order['cooperation_id']])->find();
            if($ordertime){
                if($ordertime['state'] == 1){
                    if($student_type == 'student'){
                        if($stu['cooperation_id'] !== $order['cooperation_id']){
                            $update['should_endtime'] = time()+$ordertime['tem_time_limit']*60;
                        }
                    }else{
                        $update['should_endtime'] = time()+$ordertime['tem_time_limit']*60;
                    }
                    $stu_num = Db::name('stu_number')->where(['stu_id'=>$order['stu_id']])->find();
                    if(!$stu_num){
                        $insert_stu_number['cooperation_id'] = $order['cooperation_id'];
                        $insert_stu_number['space_id'] = $order['space_id'];
                        $insert_stu_number['stu_id'] = $order['stu_id'];
                        $insert_stu_number['createtime'] = time();
                        Db::name('stu_number')->insert($insert_stu_number);
                    }
                }
            }
            $order->save($update);
        }
        $order_log['ordernumber'] = $ordernumber;
        $order_log['start_or_end'] = 1;
        $order_log['createtime'] = time();
        $res = Db::name('order_log')->insert($order_log);
        $stutj = $this->stutj->where(['stu_id'=>$order['stu_id']])->find();
        if(!$stutj){
            if($student_type == 'student' ){
                $student = $this->student->where(['stu_id'=>$order['stu_id']])->find();
            }else{
                $student = $this->intentstudent->where(['stu_id'=>$order['stu_id']])->find();
            }
            if($student){
                $save_tj['stu_id'] = $order['stu_id'];
                $save_tj['phone'] = $student['phone'];
                $save_tj['createtime'] = time();
                $save_tj['name'] = $student['name'];
                $save_tj['student_type'] = $student_type;
                $this->stutj->save($save_tj);
            }
            
        }
        $update['should_endtime'] = (string)$update['should_endtime'];
        $update['starttime'] = (string)$update['starttime'];
        if($res){
            $this->success('返回成功',$update);
        }else{
            $this->success('登录返回失败');
        }
    }

    public function machine_end(){
        $params =$this->request->post();
        // Cache::set('end',$params);
        // $params['ordernumber'] = 'CON20231221191022454606';
        // $params['student_type'] = 'student';
        // $params['level'] = '科目二直角转弯，科目二半坡起步';
        if(empty($params['ordernumber']) || !array_key_exists('level',$params) || empty($params['student_type'])){
            $this->error('参数缺失');
        }
        $ordernumber = $params['ordernumber'];
        $level = $params['level'];
        $update['level'] = $level ;
        $student_type = $params['student_type'];
        if($student_type == 'student'){
            $order = $this->order->with(['student','machinecar'])->where('ordernumber',$ordernumber)->find();
            $student = $order['student'];
            $machinecar = $order['machinecar'];
            unset($order['student']);
            unset($order['machinecar']);
        }elseif($student_type == 'intent_student'){
            $order = $this->temporaryorder->where('ordernumber',$ordernumber)->find();
        }else{
            $this->error('学员类型错误');
        }
        $should_endtime = $order['should_endtime'];
        $update['endtime'] = time();
        $update['updatetime'] = time();
        if(time()>$should_endtime){
            $update['endtime'] = $should_endtime;
        }
        $update['order_status'] = 'finished';
        if($student_type == 'student'){
            $res = $this->order->where('ordernumber',$ordernumber)->update($update);
        }else{
            $res = $this->temporaryorder->where('ordernumber',$ordernumber)->update($update);
        }
        $order_log['ordernumber'] = $ordernumber;
        $order_log['start_or_end'] = 2;
        $order_log['createtime'] = time();
        Db::name('order_log')->insert($order_log);

        $url = "https://admin.aivipdriver.com/api/xiaomaguan/student/add_ai_order_end";
        $this->common->post($url,$params);

        
        if($res){
            if($student_type == 'student'){
                $jishi_data['IdCard'] = $student['idcard'];
                if($order['subject_type'] == 'subject2'){
                    $jishi_data['subject'] = '2';
                }else{
                    $jishi_data['subject'] = '3';
                }
                $jishi_data['subject'] = $jishi_data['subject'];
                $jishi_data['SN'] = $machinecar['machine_code'];
                $this->jishi->logout($jishi_data);
            }
            $this->success('返回成功');
        }else{
            $this->error('登出返回失败');
        }
    }

    /**
     * 授权后通过这个值去开机
     */
    public function putordernumber(){
        $params = $this->request->post();

        if(empty($params['ordernumber'])){
            $this->error('参数缺失');
        }
        $res = Cache::pull('ordernumber_'.$params['ordernumber']);

        if($res){
            $this->success('授权成功');
        }else{
            $this->error('当前没有授权');
        }
    }

    public function get_back_window_code(){
        $params = $this->request->post();
        if(empty($params['machine_code'])){
            $this->error('参数缺失');
        }
        $machine_code = $params['machine_code'];
        $machine = $this->machinecar->where('machine_code',$machine_code)->find();
        if($machine){
            $path = $machine['back_window_code'];
            $data['back_window_code'] = $path;
            $this->success('返回成功',$data);
        }else{
            $this->error('查询无此机器码');
        }
    }

    public function get_window(){
        $params = $this->request->post();
        if(empty($params['machine_code'])){
            $this->error('参数缺失');
        }
        $machine_code = $params['machine_code'];
        $path = Cache::pull($machine_code.'back_window');
        if($path ==1){
            $this->success('返回成功');
        }else{
            $this->error('返回失败');
        }
    }

}
