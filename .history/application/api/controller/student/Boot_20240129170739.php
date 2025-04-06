<?php

namespace app\api\controller\student;

use app\common\controller\Api;
use think\cache;
use think\Db;
/**
 * 开机流程所需接口
 */
class Boot extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    protected $machinecar = null;
    protected $student = null;
    protected $coach = null;
    protected $device = null;
    protected $intentstudent = null;
    protected $space = null;
    protected $order = null;
    protected $temporaryorder = null;
    protected $admin = null;
    protected $authgroup = null;
    protected $authgroupaccess = null;
    protected $common = null;
    protected $cooperation = null;
    protected $sqb = null;
    protected $ordertime = null;

    public function _initialize()
    {
        parent ::_initialize();
        $this->machinecar = new \app\admin\model\Machinecar;
        $this->student = new \app\admin\model\Student;
        $this->coach = new \app\admin\model\Coach;
        $this->device = new \app\admin\model\Device;
        $this->intentstudent = new \app\admin\model\Intentstudent;
        $this->space = new \app\admin\model\Space;
        $this->order = new \app\admin\model\Order;
        $this->temporaryorder = new \app\admin\model\Temporaryorder;
        $this->admin = new \app\admin\model\Admin;
        $this->authgroup = new \app\admin\model\AuthGroup;
        $this->authgroupaccess = new \app\admin\model\AuthGroupAccess;
        $this->common = new \app\api\controller\Common;
        $this->cooperation = new \app\admin\model\Cooperation;
        $this->sqb = new \app\admin\model\Sqb;
        $this->ordertime = new \app\admin\model\Ordertime;


    }

    /**
     * 确认订单
     */
    public function sureorder(){
        $params = $this->request->post();
        // $params['machine_code'] = '10020';
        // $params['stu_id'] = 'CSN20210409102726705658';
        // $params['student_type'] = 'intent_student';
        // $params['ordernumber'] = '';
        // $params['reserve_starttime'] = time();
        // $params['reserve_endtime'] = time()+3600;
        // $params['should_endtime'] = time()+3600;
        // $params['coach_id'] = 'CTN20210311103714746874';
        // $params['order_status'] = 'paid';
        // $params['ordertype'] = 2;
        // $params['car_type'] = 'cartype1';
        // $params['subject_type'] = 'subject2';
        if(empty($params['stu_id'])|| empty($params['student_type']) ||empty($params['reserve_starttime'])||empty($params['reserve_endtime'])|| empty($params['machine_code']) ||
         empty($params['order_status']) || empty($params['ordertype']) || empty($params['coach_id']) || empty($params['car_type']) 
         || empty($params['subject_type']) || empty($params['should_endtime']) ){
            $this->error('参数缺失');
        }
        $student_type = $params['student_type'];
        $this->time_valatite($params['reserve_starttime'],$params['reserve_endtime']);
        $stu_id = $params['stu_id'];
        if($student_type == 'student'){
            $student = $this->student->with(['space'])->where('stu_id',$stu_id)->find();
        }else{
            $student = $this->intentstudent->where('stu_id',$stu_id)->find();
        }
        if(!$student){
            $this->error('当前学员编号异常');
        }
        if(!empty($params['ordernumber'])){
            $should_endtime = $this->order->where('ordernumber',$params['ordernumber'])->find();
            if($should_endtime['student_boot_type'] == 1){
                $this->success('学员已授权');
            }
        }
        $this->success('返回成功');
    }

    public function getPayId()
    {
        $str = date('Ymd').substr(implode('', array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8); 
        return $str;
    }



    public function unusual_ordertest($stu_id,$space_machine,$today_start,$today_end,$student_type)
    {
        $where['order_status'] = ['in',['unpaid','paid','accept_unexecut','executing']];
        $where['stu_id'] = $stu_id;
        $where['space_id'] = ['neq',$space_machine['space_id']];
        $where['reserve_starttime'] = ['between',[$today_start,$today_end]];
        if($student_type == 'student'){
            $order = $this->order->where($where)->select();
            foreach($order as $v){
                if($v['order_status'] == 'executing'){
                    $update['order_status'] = 'finished';
                    $where_order['id'] = $v['id'];
                    $this->order->where($where_order)->update($update);
                }else{
                    $update['order_status'] = 'cancel_refunded';
                    $where_order['id'] = $v['id'];
                    $this->order->where($where_order)->update($update);
                }
            }
        }else{
            $order = $this->temporaryorder->where($where)->select();
            foreach($order as $v){
                if($v['order_status'] == 'executing'){
                    $update['order_status'] = 'finished';
                    $where_order['id'] = $v['id'];
                    $this->temporaryorder->where($where_order)->update($update);
                }else{
                    $update['order_status'] = 'cancel_refunded';
                    $where_order['id'] = $v['id'];
                    $this->temporaryorder->where($where_order)->update($update);
                }
            }
        }
    }

    public function getorder(){
        $params = $this->request->post();
        // Cache::set('params',$params);
        // $params['stu_id'] = 'CSN20240124195029982371';//CSN20231109164906884188
        // $params['machine_code'] = 'FJSZxmg202306003fdone';//10031
        // $params['student_type'] = 'intent_student';

        if(empty($params['stu_id']) || empty($params['machine_code']) || empty($params['student_type'])){
            $this->error('参数缺失');
        }

        $stu_id = $params['stu_id'];
        $student_type = $params['student_type'];
        $machine_code = $params['machine_code'];
        $student = $this->getstudent($stu_id,$student_type);
        $today_start = strtotime(date('Y-m-d 00:00:00',time()));
        $today_end = $today_start + 24*3600-1;
        $space_machine = $this->machinecar->with('space')->where(['machinecar.machine_code'=>$machine_code])->find();
        if(!$space_machine){
            $this->error('机器码错误');
        }
        
        if($space_machine['state'] == 0){
            $this->error('当前机器编码已失效');
        }
        $shiji_student_type = $params['student_type'];

        $pay_status = $space_machine['space']['pay_status'];
        


        $coo =$this->cooperation->where(['cooperation_id'=>$space_machine['cooperation_id']])->find();

        if($coo){
            if($coo['forbidden_tmp_stu'] == 1 && $shiji_student_type == 'intent_student'){
                $this->error('临时学员无法上机');
            }
        }
        $pay['subject2'] = [];
        $pay['subject3'] = [];
        $pay['subject_img'] = [];
        $pay['client_sn'] = [];
        if($pay_status && $params['student_type'] == 'student'){
            $where_pay_subject2['space_id'] = $space_machine['space_id'];
            $where_pay_subject2['type'] = 'type2';
            $where_pay_subject2['state'] = '1';
            $pay_subject2 = $this->device->where($where_pay_subject2)->find();
            $where_pay_subject3['space_id'] = $space_machine['space_id'];
            $where_pay_subject3['state'] = '1';
            $where_pay_subject3['type'] = 'type3';
            $pay_subject3 = $this->device->where($where_pay_subject3)->find();
            if($pay_subject2){
                $pay_subject2 = $pay_subject2->toArray();
            }
            if($pay_subject3){
                $pay_subject3 = $pay_subject3->toArray();
            }
            $pay['subject2'] = $pay_subject2;
            $pay['subject3'] = $pay_subject3;
            $pay['subject_img'] = 'https://xiaomaguan.com/uploads/20231129/b880a3c188cb1ab74cb06d38be8f7321.png';
            $pay['client_sn'] = $this->getPayId();
            if($coo){
                $pay['subject_img'] = 'https://xiaomaguan.com'.$coo['icon_image'];
            }            
            if($params['student_type'] == 'student'){
                $student = $this->student->where(['stu_id'=>$stu_id])->find();
                if($student['cooperation_id'] !== $space_machine['cooperation_id']){
                    $shiji_student_type = 'intent_student';
                    $pay['subject2']['amount'] = $pay['subject2']['amount'];
                    $pay['subject3']['amount'] = $pay['subject3']['amount'];
                }else{
                    $pay['subject2']['amount'] = $pay['subject2']['stu_amount'];
                    $pay['subject3']['amount'] = $pay['subject3']['stu_amount'];
                    unset($pay['subject2']['stu_amount']);
                    unset($pay['subject3']['stu_amount']);
                }
            }
            // else{
            //     // var_dump(123);exit;
            //     $pay['subject2']['amount'] = $pay['subject2']['amount'];
            //     $pay['subject3']['amount'] = $pay['subject3']['amount'];
            // }
            // $this->error('当前支付模块维护，请自行收费');
            if($shiji_student_type == 'student'){
                if($pay['subject2']['amount'] == 0 && $pay['subject2']['amount'] == 0){
                    $space_machine['space']['pay_status'] = 0;
                }
                
                if(stristr($student['subject_type'],'subject2')){
                    $pay['subject2'] = [];
                }
                if(stristr($student['subject_type'],'subject3')){
                    $pay['subject3'] = [];
                }

                if(!$pay['subject3'] && ! $pay['subject2']){
                    $space_machine['space']['pay_status'] = 0;
                }
            }
            // $space_machine['pay']['total_amount'] = '';
            // $space_machine['pay']['terminal_sn'] = '';
            
            // $space_machine['pay']['subject'] = '模拟器上机缴费';
            // if($pay['yicixing_state'] == 1){
            //     // $pay_cooperation = explode(',',$student['pay_cooperation']);
            //     $whe_sqb['stu_id'] = $stu_id;
            //     $whe_sqb['cooperation_id'] = $space_machine['cooperation_id'];
            //     $sqb = $this->sqb->where($whe_sqb)->count();
            //     if($sqb){
            //         if($shiji_student_type == 'intent_student'){
                        
            //         }else{
            //             $space_machine['pay'] = [];
            //             $space_machine['space']['pay_status'] = 0;
            //         }
            //     }
            // }
           
            
        }
        $space_machine['pay'] = $pay;

        $machine_id = $space_machine['id'];
        $space_id = $space_machine['space_id'];

        $order_length = $space_machine['space']['order_length'];
        $order_time = $order_length*60*60;

        $where['space_id'] = $space_id;
        $where['teach_state'] = 'yes';
        $coachlist = Db::name('coach')->where($where)->field(['coach_id','name'])->select();
        if(!$coachlist){
           $this->error('当前场馆未配置教员'); 
        }  
        //违约订单
        $this->not_coming_order($stu_id,$today_start,$today_end,$student_type);
        //完成正在进行的订单
        $this->finish_today_order($stu_id,$today_start,$today_end,$student_type);

        //其他馆的异常订单
        $this->unusual_order($stu_id,$space_machine,$today_start,$today_end,$student_type);

        // if($student_type == 'student'){
        //     $this->machine_validate($stu_id,$space_machine);
        // }
        //订单是否可以开机
        $this->order_boot($stu_id,$today_start,$today_end,$machine_code,$student_type,$student,$coachlist,$order_time,$pay);
        
        //学员，教员都没授权的预约单直接开机
        $this->reserve_order($stu_id,$today_start,$today_end,$machine_id,$space_id,1,$coachlist,$order_time);

        if($coo){
            if($coo['forbidden_not_reserve'] == 1){
                $this->error('当前驾校不支持非预约订单');
            }
            if($coo['forbidden_tmp_stu'] == 1 && $student['order_num'] <= 0 ){
                $this->error('已超过学员上机次数，请联系驾校管理员');
            }
        }

        $ordertime = $this->ordertime->where(['cooperation_id'=>$space_machine['cooperation_id']])->find();
        if($ordertime && $shiji_student_type == 'intent_student'){
            $ordertime1['cooperation_id'] = $space_machine['cooperation_id'];
            $ordertime1['stu_id'] = $stu_id;
            $ordertime1['order_status'] = 'finished';
            $num1 = $this->order->where($ordertime1)->count();
            $num2 = $this->temporaryorder->where($ordertime1)->count();

            if(($num1 + $num2) >=$ordertime['tem_num_limit']){
                $this->error('体验次数已满，请联系客服');
            }
        }
        //没有订单,生成订单
        $this->create_order($stu_id,$space_machine,$today_start,$today_end,'paid',$student_type,$coachlist,$order_time,$student,$ordertime,$shiji_student_type);

        $this->error('订单异常');
    }
    

    public function getstudent($stu_id,$student_type)
    {
        if($student_type == 'student'){
            $student = $this->student->where('stu_id',$stu_id)->find();
        }elseif($student_type == 'intent_student'){
            $student = $this->intentstudent->where('stu_id',$stu_id)->find();
            $student['subject_type'] = 'subject2';
            $student['pay_cooperation'] = '';
        }else{
            $this->error('学员类型错误');
        }
        return $student;
    }


    /**
     * 时间验证
     */
    public function time_valatite($reserve_starttime,$reserve_endtime){
        $time = strtotime(date('Y-m-d 00:00:00',time()));
        if(($time > $reserve_starttime) || ($time >$reserve_endtime)){
            $this->error('提交时间异常');
        }
    }

    /**
     * 正式学员验证场馆
     */
    public function space_validate($stu_id,$space){
        $student = $this->student->with(['space'])->where('stu_id',$stu_id)->find();
        $allow_space = explode(',',$space['allow_space']);
        $allow_space[] = (string)$space['id'];
        if(in_array($student['space_id'],$allow_space)){
            if(($student['space']['times_limit_status'] == 1)){
                //自馆学员,学时限制
                if( $student['period_surplus'] <= 0){
                    $this->error('您当前没有学时，请联系管理员充值学时后再操作');
                }
            }elseif(($space['id'] != $student['space_id']) && ($space['times_limit_cooperation_status'] == 1)){
                //合作馆学员,次数限制
                $count_order['order_status'] = ['in',['unpaid','paid','accept_unexecut','executing','finished']];
                $count_order['space_id'] = $space['id'];
                $count = $this->order->where($count_order)->count();
                if($count >= $space['times_limit_cooperation']){
                    $this->error('当前场馆对您学习次数有限，请联系您报名所属场馆了解详情后再上机');
                }
            }
        }else{
            $this->error('当前场馆不对您开放，请联系管理员后再操作');
        }

    }
    

    public function order_boot($stu_id,$today_start,$today_end,$machine_code,$student_type,$student,$coachlist,$order_time,$pay)
    {
        //订单开机过的直接开机
        $where['stu_id'] = $stu_id;
        $where['reserve_starttime'] = ['between',[$today_start,$today_end]];
        // $where['student_boot_type'] = 1;
        $where['order_status'] = ['neq','cancel_refunded'];

        if($student_type == 'student'){
            $order = $this->order->with(['space','machinecar'])->where($where)->order('reserve_starttime desc')->find();
        }else{
            $order =$this->temporaryorder->with(['space','machinecar'])->where($where)->order('reserve_starttime desc')->find();
        }
        // var_dump($order->toArray());exit;
        
        $data['ordernumber'] = $order['ordernumber'];
        $data['student_type'] =  $student_type;
        // var_dump(222);exit;
        //没开机的直接开机
        // var_dump($order);exit;

        // if($order && !$order['starttime']&& $order['student_boot_type']==1){
        //     var_dump(111);exit;
        // }else{
        //     var_dump(222);exit;
        // }

        if($order && !$order['starttime']&& $order['student_boot_type']==1){
            $machine = $this->machinecar->where('machine_code',$machine_code)->find();
            if($machine_code !==$order['machinecar']['machine_code']){
                $machine_change['machine_id'] = $machine['id'];
                $where_order['ordernumber'] = $order['ordernumber'];
                if($student_type == 'student'){
                    $this->order->where($where_order)->update($machine_change);
                }else{
                    $this->temporaryorder->where($where_order)->update($machine_change);
                }
                // $order['machine_code'] = $machine_code;
                $order['machinecar'] = $machine;
                $order['machine_id'] = $machine['id'];
                // Cache::set('testmachine',$machine_code);
                // Cache::set('order1',$order['machinecar']['machine_code']);
                $this->put_info($order,$student_type);
                $this->error('已为您更换机器码，授权成功');
            }
            $order['machine_code'] = $machine_code;
            $order['machine_id'] = $machine['id'];
            $this->put_info($order,$student_type);
            // Cache::set('order1',$machine['machine_code']);
            // Cache::set('testmachine',$machine_code);
            $this->error('授权成功');
        }elseif($order && $order['should_endtime'] > time() && $order['student_boot_type'] ==1 && $machine_code !==$order['machinecar']['machine_code']){
            $machine = $this->machinecar->where('machine_code',$machine_code)->find();
            $machine_change['machine_id'] = $machine['id'];
            $where_order['ordernumber'] = $order['ordernumber'];
            if($student_type == 'student'){
                $this->order->where($where_order)->update($machine_change);
            }else{
                $this->temporaryorder->where($where_order)->update($machine_change);
            }

            $order['machinecar'] = $machine;
            $order['machine_id'] = $machine['id'];
            // Cache::set('testmachine',$machine_code);
            // Cache::set('order1',$machine['machine_code']);
            // $order['machine_code'] = $machine_code;
            $this->put_info($order,$student_type);
            $this->error('已为您更换机器码，授权成功');
        }elseif($order && !$order['starttime'] && $order['reserve_starttime'] > $today_start && $order['reserve_starttime'] < $today_end && !$order['student_boot_type']){
            //有订单，预约在今日，学员没授权的，开机。
            $data['space_name'] = $order['space']['space_name'];
            $data['space_id'] = $order['space_id'];
            $data['machine_id'] = $order['machinecar']['id'];
            $data['reserve_starttime'] = $order['reserve_starttime'];
            $data['reserve_endtime'] = $order['reserve_endtime'];
            $data['starttime'] = '';
            $data['endtime'] = '';
            // $data['should_endtime'] = time()+(3600*2);

            // if($machine_code == '10031'){
                $data['should_endtime'] = time()+$order_time;
            // }

            $data['order_status'] = $order['order_status'];
            $data['ordertype'] = $order['ordertype'];
            if($order['ordertype'] == 1){
                $data['ordertype_name'] = '预约下单';
            }elseif($order['ordertype'] == 2){
                $data['ordertype_name'] = '现场下单';
            }elseif($order['ordertype'] == 3){
                $data['ordertype_name'] = '后台下单';
            }
            $data['car_type'] = $order['car_type'];
            $data['car_type_text'] = $order['car_type_text'];
            $data['coachlist'] = $coachlist;
            $data['ordernumber'] = $order['ordernumber'];
            $data['pay'] = $pay;

            $this->success('返回成功',$data);
        }elseif($order && $order['starttime'] && $order['should_endtime'] > time() && $order['student_boot_type'] ==1){
            //有订单，预约时间在今日，学员授权的，开机。
            $this->put_info($order,$student_type);
            $this->error('授权成功');
        }

        
    }

    /**
     * 创建订单
     */
    public function create_order($stu_id,$space_machine,$today_start,$today_end,$order_status,$student_type,$coachlist,$order_time,$student,$ordertime,$shiji_student_type){
        $where['order_status'] = ['neq','cancel_refunded'];
        $where['stu_id'] = $stu_id;
        $where['student_boot_type'] = 0;
        $where['space_id'] = $space_machine['space_id'];
        $where['machine_id'] = $space_machine['id'];
        $where['reserve_starttime'] = ['between',[$today_start,$today_end]];

        
        if($student_type == 'student'){
            $order = $this->order->where($where)->find();
        }else{
            $order = $this->temporaryorder->where($where)->find();
        }
        if(!$order){
            // var_dump($space_machine);exit;
            $data['space_name'] = $space_machine['space']['space_name'];
            $data['space_id'] = $space_machine['space_id'];
            $data['cooperation_id'] = $space_machine['cooperation_id'];
            $data['machine_id'] = $space_machine['id'];
            $data['pay'] = $space_machine['pay'];
            $data['pay_status'] = $space_machine['space']['pay_status'];
            
            // if($student_type == 'student' && $data['pay']){
            //     if($space_machine['pay']['stu_amount'] == 0){
            //         $data['pay_status'] = 0;
            //     }
            // }

            $data['reserve_starttime'] = time();
            // $data['pay'] = $space_machine['pay'];
            // $data['reserve_endtime'] = time()+3600*2;
            $data['starttime'] = '';
            $data['endtime'] = '';
            // $data['should_endtime'] = time()+(3600*2) + (60*3);
            // if($space_machine['machine_code'] == '10031'){
            $data['should_endtime'] = time()+$order_time + (60*3);
            $data['reserve_endtime'] = time()+$order_time;
            if($ordertime && $shiji_student_type == 'intent_student'){
                $data['should_endtime'] = time()+(60*$ordertime['tem_time_limit']);
                $data['reserve_endtime'] = time()+(60*$ordertime['tem_time_limit']);
            }
            // if($subject_type == 'subject3'){
            //     $data['subject_type'] = 'subject3';
            // }else{
            //     $data['subject_type'] = 'subject2';
            // }
            // }

            $data['order_status'] = $order_status;
            $data['ordertype'] = 2;
            $data['car_type'] = $student['car_type'];
            $data['car_type_text'] = $student['car_type_text'];
            $data['coachlist'] = $coachlist;
            $data['ordernumber'] = '';
            
            $this->success('返回成功',$data);
        }
    }

    public function finish_today_order($stu_id,$today_start,$today_end,$student_type)
    {
        $where['starttime'] = ['between',[$today_start,$today_end]];
        $where['order_status'] = 'executing';
        $where['stu_id'] = $stu_id;
        $where['endtime'] = ['<',time()];
        if($student_type == 'studednt'){
            $order = $this->order->where($where)->select();
            if($order){
                $update['order_status']= 'finished';
                $this->order->where($where)->update($update);
            }
        }else{
            $order = $this->temporaryorder->where($where)->select();
            if($order){
                $update['order_status']= 'finished';
                $this->temporaryorder->where($where)->update($update);
            }
        }
    }

    public function machine_validate($stu_id,$space_machine){
        $student = $this->student->where('stu_id',$stu_id)->find();
        if($student['car_type'] != $space_machine['car_type']){
            $this->error('您当前车型为'.$student['car_type_text'].'当前机器为'.$space_machine['car_type_text'].'请选择车型机器上机');
        }
    }

    
    public function reserve_order($stu_id,$today_start,$today_end,$machine_id,$space_id,$type,$coachlist,$order_time){
        $where['order_status'] = 'paid';
        $where['stu_id'] = $stu_id;
        $where['student_boot_type'] = 0;
        $where['space_id'] = $space_id;
        $where['reserve_starttime'] = ['between',[$today_start,$today_end]];
        $order = $this->order->where($where)->find();
        if($order){
            if($type ==2){
                $this->error('已有预约，请转到学员登录');
            }
            $data['space_name'] = $this->space->where('id',$order['space_id'])->find()['space_name'];
            $data['space_id'] = $space_id;
            $data['machine_code'] = $this->machinecar->where('id',$machine_id)->find()['machine_code'];
            $data['reserve_starttime'] = $order['reserve_starttime'];
            $data['reserve_endtime'] = $order['reserve_endtime'];
            $data['coach_id'] = $order['coach_id'];
            // var_dump($order['coach_id']);exit;

            $data['coach_name'] = $this->coach->where('coach_id',$order['coach_id'])->find()['name'];

            $data['subject_type'] = $order['subject_type_text'];
            $data['car_type'] = $order['car_type'];
            $data['starttime'] = NULL;
            $data['endtime'] = NULL;
            $data['coachlist'] = $coachlist;
            $data['order_status'] = $order['order_status'];
            // $data['should_endtime'] = time()+3600*2 + 180;
            // if($data['machine_code']== '10031'){
                $data['should_endtime'] = time()+$order_time + 180;
            // }
            
            $data['ordertype'] = 1;
            $data['ordernumber'] = $order['ordernumber'];
            $this->success('返回成功',$data);
        }
    }

    public function machine_confirm($machine_id){
        $machinecar = $this->machinecar->where('machine_id',$machine_id)->find();
        if(!$machinecar){
            $this->error('机器码错误');
        }
        return $machinecar['space_id'];
    }


    

    public function coach_boot($stu_id,$today_start,$today_end,$machine_id,$student_type){
        $where['order_status'] = ['neq','cancel_refunded'];
        $where['stu_id'] = $stu_id;
        $where['reserve_starttime'] = ['between',[$today_start,$today_end]];
        $where['should_endtime'] = ['>',time()];
        $where['machine_id'] = $machine_id;
        if($student_type == 'student'){
            $order = $this->order->where($where)->find();
        }else{
            $order = $this->temporaryorder->where($where)->find();
        }
        if($order){
            $this->put_info($order,$student_type);
            $this->success('当前订单您已授权。','',2);
        }
    }
    
    public function put_info($order,$student_type){
        $data['ordernumber'] = $order['ordernumber'];
        $student = $this->student->where('stu_id',$order['stu_id'])->find();
        $machinecar = $this->machinecar->where('id',$order['machine_id'])->find();
        $data['sim'] = $machinecar['sim'];
        $data['address'] = $machinecar['address'];
        $data['imei'] = $machinecar['imei'];
        $data['sn'] = $machinecar['sn'];
        $data['terminal_equipment'] = $machinecar['terminal_equipment'];
        $data['study_machine'] = $machinecar['study_machine'];
        $data['idcard']= $student['idcard'];
        $data['stu_id'] = $student['stu_id'];
        $data['stu_name'] = $student['name'];
        $data['phone'] =  $student['phone'];
        $data['subject_type'] = $order['subject_type_text'];
        $data['car_type_text'] = $order['car_type_text'];
        $data['student_type'] = $student_type;
        Cache::set('machine_'.$machinecar['machine_code'],$data,5*60);
    }

    public function not_coming_order($stu_id,$today_start,$today_end,$student_type){
        if($student_type == 'student'){
            //开机的结束
            $where_finish['stu_id'] = $stu_id;
            $where_finish['reserve_starttime'] = ['<',$today_start];
            $where_finish['order_status'] = ['in',['accept_unexecut','executing']];
            $update_finish['order_status'] = 'finished';
            $this->order->where($where_finish)->update($update_finish);

            //没开机的取消
            $where_cancel['stu_id'] = $stu_id;
            $where_finish['reserve_starttime'] = ['<',$today_start];
            $where_finish['order_status'] = 'paid';
            $update_cancel['order_status'] = 'cancel_refunded';
            $this->order->where($where_finish)->update($update_finish);
        }else{
            $where_finish['stu_id'] = $stu_id;
            $where_finish['reserve_starttime'] = ['<',$today_start];
            $where_finish['order_status'] = ['in',['accept_unexecut','executing']];
            $update_finish['order_status'] = 'finished';
            $this->temporaryorder->where($where_finish)->update($update_finish);
            //没开机的取消
            $where_cancel['stu_id'] = $stu_id;
            $where_finish['reserve_starttime'] = ['<',$today_start];
            $where_finish['order_status'] = 'paid';
            $update_cancel['order_status'] = 'cancel_refunded';
            $this->temporaryorder->where($where_finish)->update($update_finish);
        }
    }

    public function unusual_order($stu_id,$space_machine,$today_start,$today_end,$student_type)
    {
        $where['order_status'] = ['in',['unpaid','paid','accept_unexecut','executing']];
        $where['stu_id'] = $stu_id;
        $where['space_id'] = ['neq',$space_machine['space_id']];
        $where['reserve_starttime'] = ['between',[$today_start,$today_end]];
        if($student_type == 'student'){
            $order = $this->order->where($where)->select();
            foreach($order as $v){
                if($v['order_status'] == 'executing'){
                    $update['order_status'] = 'finished';
                    $where_order['id'] = $v['id'];
                    $this->order->where($where_order)->update($update);
                }else{
                    $update['order_status'] = 'cancel_refunded';
                    $where_order['id'] = $v['id'];
                    $this->order->where($where_order)->update($update);
                }
            }
        }else{
            $order = $this->temporaryorder->where($where)->select();
            foreach($order as $v){
                if($v['order_status'] == 'executing'){
                    $update['order_status'] = 'finished';
                    $where_order['id'] = $v['id'];
                    $this->temporaryorder->where($where_order)->update($update);
                }else{
                    $update['order_status'] = 'cancel_refunded';
                    $where_order['id'] = $v['id'];
                    $this->temporaryorder->where($where_order)->update($update);
                }
            }
        }
    }

    public function submitorder(){
        $params = $this->request->post();
        // $params['machine_code'] = '10020';
        // $params['stu_id'] = 'CSN20210409102726705658';
        // $params['student_type'] = 'intent_student';
        // $params['ordernumber'] = '';
        // $params['reserve_starttime'] = time();
        // $params['reserve_endtime'] = time()+3600;
        // $params['should_endtime'] = time()+3600;
        // $params['coach_id'] = 'CTN20210311103714746874';
        // $params['order_status'] = 'paid';
        // $params['ordertype'] = 2;
        // $params['car_type'] = 'cartype1';
        // $params['subject_type'] = 'subject2';
        if(empty($params['stu_id'])|| empty($params['student_type']) ||empty($params['reserve_starttime'])||empty($params['reserve_endtime'])|| empty($params['machine_code']) ||
         empty($params['order_status']) || empty($params['ordertype']) || empty($params['coach_id']) || empty($params['car_type']) 
         || empty($params['subject_type']) || empty($params['should_endtime']) ){
            $this->error('参数缺失');
        }
        // var_dump($params);exit;
        $student_type = $params['student_type'];
        $this->time_valatite($params['reserve_starttime'],$params['reserve_endtime']);
        $stu_id = $params['stu_id'];
        $machine = $this->machinecar->with(['space'])->where('machinecar.machine_code',$params['machine_code'])->find();
        $machine_id = $machine['id'];
        $space_id = $machine['space_id'];
        $whereorder['order_status'] = ['in',['paid','accept_unexecut','executing']];
        $whereorder['stu_id'] = $stu_id ;
        $whereorder['ordertype'] = 2;
        if($student_type == 'student'){
            // $order_exist= $this->order->where($whereorder)->count();
            $student = $this->student->with(['space'])->where('stu_id',$stu_id)->find();
            // $this->space_validate($stu_id,$machine['space']);
        }else{
            // $order_exist= $this->temporaryorder->where($whereorder)->count();
            $student = $this->intentstudent->where('stu_id',$stu_id)->find();
        }
        // if($order_exist){
        //     $this->error('已有未完成订单');
        // }
        //提交不能太频繁
        $submit_stu_id = Cache::get('submit_id'.$stu_id);
        if($submit_stu_id){
            $this->error('请不要频繁提交操作');
        }

        if(!empty($params['ordernumber'])){
            $update['machine_id'] =  $machine_id;
            $should_endtime = $this->order->where('ordernumber',$params['ordernumber'])->find();
            if($should_endtime['student_boot_type'] == 1){
                $this->success('学员已授权');
            }
            if($should_endtime['should_endtime'] == NULL){
                $update['starttime'] =  NULL;
                $update['should_endtime'] = time()+2*60*60;
            }
            $update['car_type'] = $params['car_type'];
            $update['subject_type'] = $params['subject_type'];
            $update['student_boot_type'] = 1;
            $update['coach_id'] = $params['coach_id'];

            if($student['subject_type'] == 'subject3'){
                $update['subject_type'] = 'subject3';
            }else{
                $update['subject_type'] = 'subject2';
            }

            // $update['updatetime'] = time();
            $this->order->where('ordernumber',$params['ordernumber'])->update($update);
            $res = 1;
            $data['ordernumber'] = $params['ordernumber'];
            $data['subject_type'] = __($params['subject_type']);
            // $data['subject_cartype'] = __($params['car_type']);
        }else{
            $order['cooperation_id'] = $machine['cooperation_id'];
            $order['ordernumber'] = 'CON'.date("YmdHis") . mt_rand(100000, 999999);
            $order['stu_id'] = $params['stu_id'];
            $order['machine_id'] = $machine_id;
            $order['ordertype'] = $params['ordertype'];
            $order['space_id'] = $space_id;
            $order['reserve_starttime'] = $params['reserve_starttime'];
            $order['reserve_endtime'] = $params['reserve_endtime'];
            $order['starttime'] = NULL;
            $order['endtime'] = NULL;
            $order['should_endtime'] = $params['should_endtime'];
            $order['order_status'] = $params['order_status'];
            $order['coach_id'] = $params['coach_id'];
            $order['car_type'] = $params['car_type'];
            $order['subject_type'] = $params['subject_type'];
            $order['student_boot_type'] = 1;
            $order['payModel'] = 2;
            $order['evaluation'] = 0;
            if($student['subject_type'] == 'subject3'){
                $order['subject_type'] = 'subject3';
            }else{
                $order['subject_type'] = 'subject2';
            }
            if($student_type == 'student'){
                $order['belond_space_id'] = $student['space_id'];
                $res = $this->order->save($order);
                //学员下单扣学时
                $this->common->stu_period_surplus($student);
                $data['idcard']= $student['idcard'];
            }else{
                $res = $this->temporaryorder->save($order);
                $data['idcard']= '';
            }
            $data['ordernumber'] = $order['ordernumber'];
            $data['subject_type'] = __($order['subject_type']);
            // $data['subject_cartype'] = __($order['car_type']);
        }
        if($res){
            $data['sim'] = $machine['sim'];
            $data['address'] = $machine['address'];
            $data['imei'] = $machine['imei'];
            $data['sn'] = $machine['sn']; 
            $data['terminal_equipment'] = $machine['terminal_equipment'];
            $data['study_machine'] = $machine['study_machine'];
            $data['stu_id'] = $student['stu_id'];
            $data['stu_name'] =  $student['name'];
            $data['phone'] =  $student['phone'];
            $data['student_type'] =  $params['student_type'];
            Cache::set('submit_id'.$stu_id,$stu_id,3);
            Cache::set('machine_'.$params['machine_code'],$data,5*60);
            $this->success('返回成功');
        }else{
            $this->error('提交失败，请重新提交');
        }
    }

    
    /**
     * 获取学员授权展示教员列表
     */
    public function boot_coach_list(){
        $params = $this->request->post();
        $params['machine_code'] = '10032';
        if(empty($params['machine_code'])){
            $this->error('参数缺失');
        }
        $machinecar = Db::name('machine_car')->where('machine_code',$params['machine_code'])->find();
        $where['space_id'] = $machinecar['space_id'];
        $where['teach_state'] = 'yes';
        $coach = Db::name('coach')->where($where)->field(['coach_id','name'])->select();
        if($coach){
            $data = $coach;
            $this->success('返回成功', $data);
        }else{
            $this->error('当前没有教员');
        }
    }

    

}
