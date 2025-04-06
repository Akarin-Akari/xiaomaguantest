<?php

namespace app\api\controller\student;

use app\common\controller\Api;
use PDO;
use think\cache;
use think\Db;

/**
 * 首页接口
 */
class ReserveOrder extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    protected $coach = null;
    protected $coachsc = null;
    protected $space = null;
    protected $order = null;
    protected $student = null;
    protected $intentstudent = null;
    protected $ordersc = null;
    protected $temporaryorder = null;
    protected $temordersc = null;
    protected $evaluation = null;
    protected $studyprocess = null;
    protected $studyprocessai = null;
    protected $common = null;
    protected $pickuporder = null;
    protected $pickupcar = null;

    public function _initialize()
    {
        parent ::_initialize();
        $this->coach = new \app\admin\model\Coach;
        $this->coachsc = new \app\admin\model\Coachsc;
        $this->space = new \app\admin\model\Space;
        $this->order = new \app\admin\model\Order;
        $this->student = new \app\admin\model\Student;
        $this->intentstudent = new \app\admin\model\Intentstudent;
        $this->ordersc = new \app\admin\model\Ordersc;
        $this->temporaryorder = new \app\admin\model\Temporaryorder;
        $this->temordersc = new \app\admin\model\Temordersc;
        $this->evaluation = new \app\admin\model\Evaluation;
        $this->studyprocess = new \app\admin\model\Studyprocess;
        $this->studyprocessai = new \app\admin\model\Studyprocessai;
        $this->evaluation = new \app\admin\model\Evaluation;
        $this->common = new \app\api\controller\Common;
        $this->pickuporder = new \app\admin\model\Pickuporder;
        $this->pickupcar = new \app\admin\model\Pickupcar;


    }

    public function test()
    {
        $params = Cache::get('yuyuetest');
        // if($params['pick_up']){
        //     $pick_up = str_replace('&quot;','"',$params['pick_up']);
        //     $pick_up = json_decode($pick_up,true);
        // }

        var_dump($params['coach']->toArray());
        var_dump($params['reserve_starttime']);
        var_dump($params['reserve_endtime']);
        var_dump($params['coach_type']);
        var_dump($params['car_type']);
    }


    public function order_detail()
    {
        $params = $this->request->post();
        if(empty($params['ordernumber']) || empty($params['type']) || empty($params['stu_type']) ){
            $this->error('参数缺失');
        }

        $res = [];
        // $params['ordernumber'] = 'CON20220731103610981704';
        $where['ordernumber'] = $params['ordernumber'];
        if($params['type'] == 'AI'){
            if($params['stu_type'] == 'student'){
                $res = $this->order->with(['student','coach','space'])->where($where)->find();
            }elseif($params['stu_type'] == 'intent_student'){
                $res = $this->temporaryorder->with(['intent_student','coach','space'])->where($where)->find();
                $res['student'] = $res['intent_student'];
                unset($res['intent_student']);
            }else{
                $this->error('类型参数异常');
            }
            // $studyprocess =$this->studyprocess->where($where)->select();

            $keer_study = $this->studyprocess->where(['ordernumber'=>$params['ordernumber']])->whereNull('place_id')->select();

            $kesan_study = $this->studyprocess->where(['place_id'=>['<>','NULL'],'ordernumber'=>$params['ordernumber']])->select();
        }elseif($params['type'] == 'SC'){
            $res = $this->ordersc->with(['student','coachsc','space'])->where($where)->find();
            $res['coach'] = $res['coachsc'];
            unset($res['coachsc']);
            // $studyprocess = $this->studyprocessai->where($where)->select();

            $keer_study = $this->studyprocess->where(['ordernumber'=>$params['ordernumber']])->whereNull('place_id')->select();

            $kesan_study = $this->studyprocess->where(['place_id'=>['<>','NULL'],'ordernumber'=>$params['ordernumber']])->select();

        }else{
            $this->error('类型参数异常');
        }
        $studyprocess['keer_process'] = [];
        if($keer_study){
            foreach($keer_study as $v){
                $process = [];
                if(!array_key_exists($v['process_name'],$studyprocess['keer_process'])){
                    $process['times'] = 1;
                    $process['pass_times'] = 0;
                    if($v['status'] == 1){
                        $process['pass_times'] = 1;
                    }
                    $studyprocess['keer_process'][__($v['process_name'])]['times'] = $process['times'];
                    $studyprocess['keer_process'][__($v['process_name'])]['pass_times'] = $process['pass_times'];
                }else{
                    $studyprocess['keer_process'][$v['process_name']]['times'] += 1;
                    if($v['status'] == 1){
                        $studyprocess['keer_process'][$v['process_name']]['pass_times'] +=1;
                    }
                }
            }
        }
        
        $studyprocess['kesan_process'] = [];

        if($kesan_study){
            foreach($kesan_study as $v){
                $process = [];

                if(!array_key_exists(__($v['process_name']),$studyprocess['kesan_process'])){
                    $process['times'] = 1;
                    $process['pass_times'] = 0;
                    if($v['status'] == 1){
                        $process['pass_times'] = 1;
                    }
                    // $studyprocess['kesan_process'][__($v['process_name'])] = [];
                    $studyprocess['kesan_process'][__($v['process_name'])]['times'] = $process['times'];
                    $studyprocess['kesan_process'][__($v['process_name'])]['pass_times'] = $process['pass_times'];
                    // array_push($studyprocess['kesan_process'][__($v['process_name'])],$process);
                }else{
                    $studyprocess['kesan_process'][__($v['process_name'])]['times'] += 1;
                    if($v['status'] == 1){
                        $studyprocess['kesan_process'][__($v['process_name'])]['pass_times'] +=1;
                    }
                }
            }
        }
        $res['studyprocess'] = $studyprocess;
        $this->success('返回成功',$res);
    }
    /**
     * 提交订单
     */
    public function submit_order()
    {
        $params = $this->request->post();
        if(empty($params['stu_id']) || empty($params['coach_id']) || empty($params['reserve_starttime']) 
        || empty($params['reserve_endtime']) || empty($params['subject_type']) || empty($params['student_type'])){
            $this->error('参数缺失');
        }
        $address = [];
        if(array_key_exists('pick_up',$params)){
            if($params['pick_up']){
                $address = str_replace('&quot;','"',$params['pick_up']);
                $address = json_decode($address,true);
            }
            
        }
        // Cache::set('reserve_test',$params);
        $stu_id = $params['stu_id'];
        $subject_type = $params['subject_type'];
        $coach_id = $params['coach_id'];
        $student_type = $params['student_type'];
        $reserve_starttime = strtotime($params['reserve_starttime']);
        $reserve_endtime = strtotime($params['reserve_endtime']);
        $coach_type = 'AI';

        if(array_key_exists('coach_type',$params)){
            $coach_type = $params['coach_type'];
        }
        if($coach_type == 'AI'){
            $order_type = 'machine';
            $coach = $this->coach->where('coach_id',$coach_id)->find();
        }else{
            $order_type = 'car';
            $coach = $this->coachsc->where('coach_id',$coach_id)->find();
        }
        $space = $this->space->where('id',$coach['space_id'])->find();
        $space_id = $coach['space_id'];
        //提交不能太频繁
        $submit_stu_id = Cache::get('submit_id'.$stu_id);
        if($submit_stu_id){
            $this->error('请不要频繁提交操作');
        }

        $this->timevalidate($reserve_starttime,$reserve_endtime);
        //是否有未完成的单

        $this->order_validate($stu_id,$coach_type);

        
        
        //验证是否有权限约此场馆
        $student = $this->common->student_validate($stu_id,$space,$subject_type,$student_type,$order_type);

        //当前时间段是否可约验证
        $this->coach_validatetest($student['car_type'],$coach,$reserve_starttime,$reserve_endtime,$coach_type);
        
        
        $order['order_status'] = 'paid';
        $order['payModel'] = 2;
        $order['ordernumber'] = 'CON'.date("YmdHis") . mt_rand(100000, 999999);
        $order['space_id'] = $space_id;
        $order['cooperation_id'] = $this->space->where('id',$space_id)->find()['cooperation_id'];
        $order['stu_id'] = $stu_id;
        $order['coach_id'] = $coach_id;
        $order['reserve_starttime'] = $reserve_starttime;
        $order['reserve_endtime'] = $reserve_endtime;
        $order['ordertype'] = 1;
        $order['car_type'] = $student['car_type'];
        $order['subject_type'] = $subject_type;
        if($order){
            if($student_type == 'student'){
                if($coach_type == 'AI'){
                    $this->order->save($order);
                }else{
                    $this->ordersc->save($order);
                }
            }else{
                $this->temporaryorder->save($order);
            }
            Cache::set('submit_id'.$stu_id,$stu_id,2);
            $this->success('返回成功');

        }else{
            $this->error('参数错误，请重新提交');
        }
    }


    /**
     * 提交订单
     */
    public function submit_pickup_order()
    {
        $params = $this->request->post();
        if(empty($params['stu_id']) || empty($params['pickupid']) || empty($params['reserve_starttime']) 
        || empty($params['reserve_endtime']) || empty($params['student_type'])){
            $this->error('参数缺失');
        }

        $address = [];
        if(array_key_exists('pick_up',$params)){
            if($params['pick_up']){
                $address = str_replace('&quot;','"',$params['pick_up']);
                $address = json_decode($address,true);
            }
            
        }
        // Cache::set('reserve_test',$params);
        $stu_id = $params['stu_id'];
        $pickup_id = $params['pickupid'];
        $student_type = $params['student_type'];
        $reserve_starttime = strtotime($params['reserve_starttime']);
        $reserve_endtime = strtotime($params['reserve_endtime']);

        $pickupcar = $this->pickupcar->with(['space'])->where('pickupcar.id',$pickup_id)->find();
        $space_id = $pickupcar['space_id'];
        //提交不能太频繁
        $submit_stu_id = Cache::get('submit_id'.$stu_id);
        if($submit_stu_id){
            $this->error('请不要频繁提交操作');
        }

        $this->timevalidate($reserve_starttime,$reserve_endtime);
        //是否有未完成的单

        $this->pickorder_validate($stu_id,$student_type);
        
        //验证是否有权限约此场馆
        // $student = $this->common->student_validate($stu_id,$space,$subject_type,$student_type,$order_type);

        //当前时间段是否可约验证
        $this->pickup_validate($pickup_id,$reserve_starttime,$reserve_endtime);
        
        
        $order['order_status'] = 'paid';
        $order['payModel'] = 2;
        $order['ordernumber'] = 'CON'.date("YmdHis") . mt_rand(100000, 999999);
        $order['space_id'] = $space_id;
        $order['cooperation_id'] = $this->space->where('id',$space_id)->find()['cooperation_id'];
        $order['stu_id'] = $stu_id;
        $order['pickup_id'] = $pickup_id;
        $order['reserve_starttime'] = $reserve_starttime;
        $order['reserve_endtime'] = $reserve_endtime;
        $order['ordertype'] = 1;
        if($order){
            if($student_type == 'student'){
                $this->pickuporder->save($order);
            }else{

            }
            Cache::set('submit_id'.$stu_id,$stu_id,2);
            $this->success('返回成功');

        }else{
            $this->error('参数错误，请重新提交');
        }
    }

    /**
     * 提交订单
     */
    public function submit_ordertest()
    {
        $params = $this->request->post();
        if(empty($params['stu_id']) || empty($params['coach_id']) || empty($params['reserve_starttime']) 
        || empty($params['reserve_endtime']) || empty($params['subject_type']) || empty($params['student_type'])){
            $this->error('参数缺失');
        }
        $address = [];
        if(array_key_exists('pick_up',$params)){
            if($params['pick_up']){
                $address = str_replace('&quot;','"',$params['pick_up']);
                $address = json_decode($address,true);
            }
            
        }
        // Cache::set('reserve_test',$params);
        $stu_id = $params['stu_id'];
        $subject_type = $params['subject_type'];
        $coach_id = $params['coach_id'];
        $student_type = $params['student_type'];
        $reserve_starttime = strtotime($params['reserve_starttime']);
        $reserve_endtime = strtotime($params['reserve_endtime']);
        $coach_type = 'AI';

        if(array_key_exists('coach_type',$params)){
            $coach_type = $params['coach_type'];
        }
        if($coach_type == 'AI'){
            $order_type = 'machine';
            $coach = $this->coach->where('coach_id',$coach_id)->find();
        }else{
            $order_type = 'car';
            $coach = $this->coachsc->where('coach_id',$coach_id)->find();
        }
        $space = $this->space->where('id',$coach['space_id'])->find();
        $space_id = $coach['space_id'];
        //提交不能太频繁
        $submit_stu_id = Cache::get('submit_id'.$stu_id);
        if($submit_stu_id){
            $this->error('请不要频繁提交操作');
        }

        $this->timevalidate($reserve_starttime,$reserve_endtime);
        //是否有未完成的单

        $this->order_validate($stu_id,$coach_type);

        
        
        //验证是否有权限约此场馆
        $student = $this->common->student_validate($stu_id,$space,$subject_type,$student_type,$order_type);

        //当前时间段是否可约验证
        $this->coach_validatetest($student['car_type'],$coach,$reserve_starttime,$reserve_endtime,$coach_type);
        
        
        $order['order_status'] = 'paid';
        $order['payModel'] = 2;
        $order['ordernumber'] = 'CON'.date("YmdHis") . mt_rand(100000, 999999);
        $order['space_id'] = $space_id;
        $order['cooperation_id'] = $this->space->where('id',$space_id)->find()['cooperation_id'];
        $order['stu_id'] = $stu_id;
        $order['coach_id'] = $coach_id;
        $order['reserve_starttime'] = $reserve_starttime;
        $order['reserve_endtime'] = $reserve_endtime;
        $order['ordertype'] = 1;
        $order['car_type'] = $student['car_type'];
        $order['subject_type'] = $subject_type;
        if($order){
            if($student_type == 'student'){
                if($coach_type == 'AI'){
                    
                    $this->order->save($order);
                }else{
                    $this->ordersc->save($order);
                }
                
            }else{
                $this->temporaryorder->save($order);
            }
            Cache::set('submit_id'.$stu_id,$stu_id,2);
            //交表馆学员不让预约
            if(in_array($student['space_id'],[40])){
                $this->error('当前学员不支持预约，请联系场馆后再操作');
            }
            $this->success('返回成功');

        }else{
            $this->error('参数错误，请重新提交');
        }
    }

    /**
     * 查询订单
     */
    public function order_quire()
    {
        $params = $this->request->post();
        if(empty($params['page']) || empty($params['stu_id']) || empty($params['student_type'])){
            $this->error('参数缺失');

        }
        $page = $params['page'];
        $stu_id = $params['stu_id'];
        $order_status= $params['order_status'];
        $student_type = $params['student_type'];
        
        $pagenum = 10;
        $numl = $pagenum*($page-1);
        $where['stu_id'] = $stu_id;

        if(!$order_status){
            unset($where['order_status']);
        }else{
            $where['order_status'] = $order_status;
            // if($order_status == 'finished'){
            //     $where['evaluation'] = 0;
            // }
        }

        if($student_type == 'student'){
            if(array_key_exists('type_index',$params)){
                if($params['type_index'] == 0){
                    $order = $this->order->with(['coach','space'])->where($where)->limit($numl,$pagenum)->order('reserve_starttime desc')->select();
                }elseif($params['type_index'] == 1){
                    $order = $this->ordersc->with(['coachsc','space'])->where($where)->limit($numl,$pagenum)->order('reserve_starttime desc')->select();
                }elseif($params['type_index'] == 2){
                    $order = $this->pickuporder->with(['pickupcar','space'])->where($where)->limit($numl,$pagenum)->order('reserve_starttime desc')->select();
                }
            }else{
                $order = $this->order->with(['coach'])->where($where)->limit($numl,$pagenum)->order('reserve_starttime desc')->select();
            }
        }else{
            if($params['type_index'] == 0){
                $order = $this->temporaryorder->with(['coach','space'])->where($where)->limit($numl,$pagenum)->order('reserve_starttime desc')->select();
            }elseif($params['type_index'] == 1){
                $order = $this->temordersc->with(['coachsc','space'])->where($where)->limit($numl,$pagenum)->order('reserve_starttime desc')->select();
            }elseif($params['type_index'] == 2){
                $order = $this->pickuporder->with(['pickupcar','space'])->where($where)->limit($numl,$pagenum)->order('reserve_starttime desc')->select();
            }
        }
        $list = [];

        foreach($order as $k=>$v){
            if($params['type_index'] == 0){
                $list[$k]['coach'] = $v['coach']['name'];
                $list[$k]['coach_id'] =  $v['coach']['coach_id'];
            }elseif($params['type_index'] == 1){
                $list[$k]['coach'] = $v['coachsc']['name'];
                $list[$k]['coach_id'] =  $v['coachsc']['coach_id'];
            }
            $list[$k]['space_detail'] = $v['space'];
            // $list[$k]['space_name'] = $v['space']['space_name'];
            $list[$k]['ordernumber'] = $v['ordernumber'];
            $list[$k]['order_status'] = $v['order_status'];
            $list[$k]['reserve_starttime'] = $v['reserve_starttime'];
            $list[$k]['reserve_endtime'] = $v['reserve_endtime'];

            if($params['type_index'] == 2){
                $list[$k]['coach'] = $v['pickupcar']['machine_code'];
                $list[$k]['coach_id'] =  $v['pickup_id'];
            }else{
                $list[$k]['starttime'] = $v['starttime'];
                $list[$k]['endtime'] = $v['endtime'];
                $list[$k]['should_endtime'] = $v['should_endtime'];
                $list[$k]['evaluation'] =  $v['evaluation'];
                $list[$k]['car_type'] = $v['car_type'];
                $list[$k]['traintype'] = $v['car_type_text'];
                $list[$k]['car_type_text'] = $v['car_type_text'];
                $list[$k]['subject_type'] = $v['subject_type'];
            }

        }
        $this->success('返回成功',$list);
    }


    public function order_quiretest()
    {
        $params = $this->request->post();
        if(empty($params['page']) || empty($params['stu_id']) || empty($params['student_type'])){
            $this->error('参数缺失');

        }
        $page = $params['page'];
        $stu_id = $params['stu_id'];
        $order_status= $params['order_status'];
        $student_type = $params['student_type'];
        
        $pagenum = 10;
        $numl = $pagenum*($page-1);
        $where['stu_id'] = $stu_id;

        if(!$order_status){
            unset($where['order_status']);
        }else{
            $where['order_status'] = $order_status;
            // if($order_status == 'finished'){
            //     $where['evaluation'] = 0;
            // }
        }
        if($student_type == 'student'){
            if(array_key_exists('type_index',$params)){
                if($params['type_index'] == 0){
                    $order = $this->order->with(['coach','space'])->where($where)->limit($numl,$pagenum)->order('reserve_starttime desc')->select();
                }else{
                    $order = $this->ordersc->with(['coachsc','space'])->where($where)->limit($numl,$pagenum)->order('reserve_starttime desc')->select();
                }
            }else{
                $order = $this->order->with(['coach','space'])->where($where)->limit($numl,$pagenum)->order('reserve_starttime desc')->select();
            }
        }else{
            if($params['type_index'] == 0){
                $order = $this->temporaryorder->with(['coach','space'])->where($where)->limit($numl,$pagenum)->order('reserve_starttime desc')->select();
            }else{
                $order = $this->temordersc->with(['coachsc','space'])->where($where)->limit($numl,$pagenum)->order('reserve_starttime desc')->select();
            }
        }
        $list = [];
        foreach($order as $k=>$v){
            $list[$k]['ordernumber'] = $v['ordernumber'];
            $list[$k]['car_type'] = $v['car_type'];
            $list[$k]['traintype'] = $v['car_type_text'];
            $list[$k]['car_type_text'] = $v['car_type_text'];
            $list[$k]['subject_type'] = $v['subject_type'];
            $list[$k]['reserve_starttime'] = $v['reserve_starttime'];
            $list[$k]['reserve_endtime'] = $v['reserve_endtime'];
            $list[$k]['starttime'] = $v['starttime'];
            $list[$k]['endtime'] = $v['endtime'];
            $list[$k]['should_endtime'] = $v['should_endtime'];
            $list[$k]['order_status'] = $v['order_status'];
            if(array_key_exists('type_index',$params)){
                if($params['type_index'] == 0){
                    $list[$k]['coach'] = $v['coach']['name'];
                    $list[$k]['coach_id'] =  $v['coach']['coach_id'];
                }else{
                    $list[$k]['coach'] = $v['coachsc']['name'];
                    $list[$k]['coach_id'] =  $v['coachsc']['coach_id'];
                }
            }else{
                $list[$k]['coach'] = $v['coach']['name'];
                $list[$k]['coach_id'] =  $v['coach']['coach_id'];
            }
            $list[$k]['evaluation'] =  $v['evaluation'];
            $list[$k]['space_detail'] = $v['space'];
            $list[$k]['space_detail']['name'] = $v['space']['space_name'];
        }
        $this->success('返回成功',$list);
    }
    /**
     * 评价提交
     */
    public function evaluation(){
        $params = $this->request->post();
        if(empty($params['stu_id']) || empty($params['ordernumber']) || empty($params['space_evaluate']) || empty($params['overall']) || empty($params['student_type'])){
            $this->error('参数缺失');
        }
        $student_type = $params['student_type'];
        $where['stu_id'] = $params['stu_id'];
        $where['ordernumber'] = $params['ordernumber'];
        if($student_type == 'student'){
            $order = $this->order->where($where)->find();
        }else{
            $order = $this->temporaryorder->where($where)->find();
        }

        if(!$order){
            $order = $this->ordersc->where($where)->find();
            if($order){
                if($order['evaluation'] == 1){
                    $this->error('当前订单已评论');
                }
                
                $params['coach_id'] = $order['coach_id'];
                $params['space_id'] = $order['space_id'];
                $params['student_type'] = $student_type;
                $params['cooperation_id'] = $order['cooperation_id'];
                
                $res = $this->evaluation->save($params);
                $update['evaluation'] = 1;
                $res_order = $this->ordersc->where($where)->update($update);
                if($res_order){
                    $this->success('返回成功');
                }else{
                    $this->error('提交失败，请重新提交');
                }
            }
            $this->error('所查订单有误');
        }
        if($order['evaluation'] == 1){
            $this->error('当前订单已评论');
        }
        $params['coach_id'] = $order['coach_id'];
        $params['space_id'] = $order['space_id'];
        $params['student_type'] = $student_type;
        $params['cooperation_id'] = $order['cooperation_id'];
        $res = $this->evaluation->save($params);
        $update['evaluation'] = 1;
        if($student_type == 'student'){
            $res_order = $this->order->where($where)->update($update);
        }else{
            $res_order = $this->temporaryorder->where($where)->update($update);
        }
        if($res_order){
            $this->success('返回成功');
        }else{
            $this->error('提交失败，请重新提交');
        }
    }

    /**
     * 取消订单
     */
    public function cancel_order(){
        $params = $this->request->post();

        //公钥加密
        if(empty($params['ordernumber']) || empty($params['student_type'])){
            $this->error('参数缺失');
        }
        $student_type = $params['student_type'];
        $date_now = time();
        $where['order_status'] = 'paid';
        $where['ordernumber'] = $params['ordernumber'];
        $data['order_status'] = 'cancel_refunded';
        $data['updatetime'] = time();
        if($student_type == 'student'){
            $order = $this->order->with('student')->where($where)->find();
        }else{
            $order = $this->temporaryorder->with('intentstudent')->where($where)->find();
            $order['student'] = $order['intentstudent'];
            unset($order['intentstudent']);
        }
        if(!$order){
            $this->success('查询无此订单');
        }
        // var_dump($order->toArray());exit;
        $reserve_starttime = $order['reserve_starttime'];
        $advance_cancel_times =  $this->space->where('id',$order['space_id'])->find()['advance_cancel_times'];
        $date = date('Y-m-d',$reserve_starttime);
        $time = explode(':',$advance_cancel_times);
        $ftime= $time[0]*3600+$time[1]*60+$time[2];
        if($date_now >$reserve_starttime){
            $this->error('您状态订单无法已无法取消，请联系客服');
        }
        $cha = $reserve_starttime-$date_now;
        if(($cha-$ftime)>0){
            // if($order['student']['space_id'] == 24){
            //     //扣学时
            //     $deduct_surplus['period_surplus'] = $order['student']['period_surplus'] + 2;
            //     $this->student->where('stunumber',$order['stu_id'])->update($deduct_surplus);
            // }
            if($student_type == 'student'){
                $res = $this->order->where($where)->update($data);
                if($order['pickup_id']){
                    Db::name('pick_up')->where(['id'=>$order['pickup_id']])->update(['status'=>2]);
                }
            }else{
                $res = $this->temporaryorder->where($where)->update($data);
            }
            if($res){
                $this->success('提交成功');
            }else{
                $this->error('取消异常，请重新提交');
            }
        }else{
            $this->error('请提前'.$advance_cancel_times.'小时取消订单，当前订单请联系客服取消');
        }
    }

    /**
     * 取消订单
     */
    public function cancel_order1(){
        $params = $this->request->post();

        //公钥加密
        if(empty($params['ordernumber']) || empty($params['student_type'])){
            $this->error('参数缺失');
        }
        $date_now = time();
        $where['order_status'] = 'paid';
        $where['ordernumber'] = $params['ordernumber'];
        $data['order_status'] = 'cancel_refunded';
        $data['updatetime'] = time();

        $order = $this->ordersc->with('student')->where($where)->find();

        if(!$order){
            $this->success('查询无此订单');
        }
        // var_dump($order->toArray());exit;
        $reserve_starttime = $order['reserve_starttime'];
        $advance_cancel_times =  $this->space->where('id',$order['space_id'])->find()['advance_cancel_times'];
        $time = explode(':',$advance_cancel_times);
        $ftime= $time[0]*3600+$time[1]*60+$time[2];
        if($date_now >$reserve_starttime){
            $this->error('您状态订单无法已无法取消，请联系客服');
        }
        $cha = $reserve_starttime-$date_now;
        if(($cha-$ftime)>0){
            
            if($order['student']['space_id'] == 24){
                //扣学时
                $deduct_surplus['period_surplus'] = $order['student']['period_surplus'] + 2;
                $this->student->where('stunumber',$order['stu_id'])->update($deduct_surplus);
            }
            $res = $this->ordersc->where($where)->update($data);

            if($res){
                $this->success('提交成功');
            }else{
                $this->error('取消异常，请重新提交');
            }
        }else{
            $this->error('请提前'.$advance_cancel_times.'小时取消订单，当前订单请联系客服取消');
        }
    }


    /**` 1` ny   90 2 898
     * 完成订单
     */
    public function finish(){
        $params = $this->request->post();
        // $params['ordernumber'] = 'CON20210422113058603554';
        // $params['student_type'] = 'intent_student';
        if(empty($params['ordernumber']) || empty($params['student_type'])){
            $this->error('参数缺失');
        }
        $this->common->finish($params['ordernumber'],$params['student_type']);
    }


    /**
     * 日期验证
     */
    public function timevalidate($reserve_starttime, $reserve_endtime){
        if($reserve_starttime < time() || $reserve_endtime < time() ){
            $this->error('预约时间段已过时，请选择正确的时间');
        }
    }

    public function order_validate($stu_id,$coach_type)
    {
        $where_order_exist['stu_id'] = $stu_id;
        $where_order_exist['order_status'] = ['in',['paid','unpaid','accept_unexecut','executing']];
        if($coach_type == 'AI'){
            $order_exist = $this->order->with(['space'])->where($where_order_exist)->find();
        }else{
            $order_exist = $this->ordersc->with(['space'])->where($where_order_exist)->find();
        }
        if($order_exist){
            $this->error('已有未完成的订单，请先完成后再提交订单');
        }
    }

    public function pickorder_validate($stu_id,$student_type)
    {
        $where_order_exist['stu_id'] = $stu_id;
        $where_order_exist['order_status'] = ['in',['paid','unpaid','accept_unexecut','executing']];
        if($student_type == 'student'){
            $order_exist = $this->pickuporder->with(['space'])->where($where_order_exist)->find();
        }else{
            
        }
        if($order_exist){
            $this->error('已有未完成的订单，请先完成后再提交订单');
        }
    }

    public function coach_validate($car_type,$coach,$reserve_starttime,$reserve_endtime,$coach_type)
    {
        
        if($coach['teach_state'] == 'no'){
            $this->error('当前教员不在教学状态，请选择其他教练');
        }
        $coach_id = $coach['coach_id'];
        
        $reserve_starttimes= date('H:i:s',$reserve_starttime);
        $reserve_endtimes= date('H:i:s',$reserve_endtime);

        if(!$coach){
            $this->error('无法找到当前教员');
        }

        if($coach_type == 'AI'){
            $coach_time_list = Db::name('coach_config_time_people')->where('coach_id',$coach['id'])->select();
        }else{
            $coach_time_list = Db::name('coach_sc_config_time_people')->where('coach_id',$coach['id'])->select();
        }
        $num = 0;
        foreach($coach_time_list as $k=>$v){
            if($v['starttimes'] == $reserve_starttimes && $v['endtimes'] == $reserve_endtimes){
                $num +=1;
                $number = $v['number'];
                $where_order['coach_id'] = $coach_id;
                $where_order['reserve_starttime'] = $reserve_starttime;
                $where_order['reserve_endtime'] = $reserve_endtime;
                $where_order['order_status'] = ['neq','cancel_refunded'];

                if($coach_type == 'AI'){
                    $order_number = $this->order->where($where_order)->count();
                }else{
                    $order_number = $this->ordersc->where($where_order)->count();
                }

                if(($number - $order_number) <=0){
                    $this->error('当前时间段不可预约');
                }
                $status = 1;
            }
        }
        if($num == 0){
            $this->error('预约时间填写错误,无法找到预约时间段');
        }
    }

    public function coach_validatetest($car_type,$coach,$reserve_starttime,$reserve_endtime,$coach_type)
    {
        $info['coach'] = $coach;
        $info['reserve_starttime'] = $reserve_starttime;
        $info['reserve_endtime'] = $reserve_endtime;
        $info['coach_type'] = $coach_type;
        if($coach['teach_state'] == 'no'){
            $this->error('当前教员不在教学状态，请选择其他教练');
        }
        $coach_id = $coach['coach_id'];
        
        $reserve_starttimes= date('H:i:s',$reserve_starttime);
        $reserve_endtimes= date('H:i:s',$reserve_endtime);

        if(!$coach){
            $this->error('无法找到当前教员');
        }

        if($coach_type == 'AI'){
            $coach_time_list = Db::name('coach_config_time_people')->where('coach_id',$coach['id'])->select();
        }else{
            $coach_time_list = Db::name('coach_sc_config_time_people')->where('coach_id',$coach['id'])->select();
        }
        $num = 0;
        foreach($coach_time_list as $k=>$v){
            if($v['starttimes'] == $reserve_starttimes && $v['endtimes'] == $reserve_endtimes){
                $num +=1;
                $number = $v['number'];
                $where_order['coach_id'] = $coach_id;
                $where_order['reserve_starttime'] = $reserve_starttime;
                $where_order['reserve_endtime'] = $reserve_endtime;
                $where_order['order_status'] = ['neq','cancel_refunded'];

                if($coach_type == 'AI'){
                    $order_number = $this->order->where($where_order)->count();
                }else{
                    $order_number = $this->ordersc->where($where_order)->count();
                }

                if(($number - $order_number) <=0){
                    $this->error('当前时间段已约满，不可预约');
                }
                $status = 1;
            }
        }

        if($num == 0){
            $this->error('预约时间填写错误,无法找到预约时间段');
        }
    }


    public function pickup_validate($pickup_id,$reserve_starttime,$reserve_endtime)
    {

        $reserve_starttimes= date('H:i:s',$reserve_starttime);
        $reserve_endtimes= date('H:i:s',$reserve_endtime);


        $coach_time_list = Db::name('pickup_config_time')->where('pickup_id',$pickup_id)->select();

        $num = 0;
        foreach($coach_time_list as $k=>$v){
            if($v['starttimes'] == $reserve_starttimes && $v['endtimes'] == $reserve_endtimes){
                $num +=1;
                $number = $v['number'];
                $where_order['pickup_id'] = $pickup_id;
                $where_order['reserve_starttime'] = $reserve_starttime;
                $where_order['reserve_endtime'] = $reserve_endtime;
                $where_order['order_status'] = ['neq','cancel_refunded'];

                $order_number = $this->pickuporder->where($where_order)->count();

                if(($number - $order_number) <=0){
                    $this->error('当前时间段已约满，不可预约');
                }
            }
        }

        if($num == 0){
            $this->error('预约时间填写错误,无法找到预约时间段');
        }
    }

}
