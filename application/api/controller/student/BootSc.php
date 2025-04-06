<?php

namespace app\api\controller\student;

use app\common\controller\Api;
use think\cache;
use think\Db;

/**
 * 开机流程所需接口
 */
class BootSc extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    protected $car = null ;
    protected $student = null ;
    protected $device = null ;
    protected $intentstudent = null ;
    protected $space = null ;
    protected $ordersc = null ;
    protected $admin = null ;
    protected $authgroup = null ;
    protected $authgroupaccess = null ;
    protected $common = null ;
    protected $cooperation = null ;
    protected $coachsc = null ;
    protected $temordersc = null ;


    public function _initialize()
    {
        parent ::_initialize();
        $this->car = new \app\admin\model\Car;
        $this->coachsc = new \app\admin\model\coach\Sc;
        $this->student = new \app\admin\model\Student;
        $this->device = new \app\admin\model\Device;
        $this->intentstudent = new \app\admin\model\Intentstudent;
        $this->space = new \app\admin\model\Space;
        $this->ordersc = new \app\admin\model\Ordersc;
        $this->temordersc = new \app\admin\model\Temordersc;
        $this->admin = new \app\admin\model\Admin;
        $this->authgroup = new \app\admin\model\AuthGroup;
        $this->authgroupaccess = new \app\admin\model\AuthGroupAccess;
        $this->common = new \app\api\controller\Common;
        $this->cooperation = new \app\admin\model\Cooperation;

    }


    public function getscorder()
    {
        $params = $this->request->post();
        
        // $params['stu_id'] = 'CSN20210425112607670801';
        // $params['machine_code'] = '粤B12312';
        // $params['student_type'] = 'student';
        // var_dump($params['machine_code']);exit;
        if(empty($params['stu_id']) || empty($params['machine_code'])|| empty($params['student_type'])){
            $this->error('参数缺失');
        }
        
        $stu_id = $params['stu_id'];
        $student_type = $params['student_type'];
        $machine_code = $params['machine_code'];
        $today_start = strtotime(date('Y-m-d 00:00:00',time()));
        $today_end = $today_start + 24*3600-1;
        $space_machine = $this->car->with('space')->where(['car.machine_code'=>$machine_code])->find();
        
        if(!$space_machine){
            $this->error('扫码异常，当前车牌号无法找到信息');
        }
        $student = $this->getstudent($stu_id,$space_machine,$params['student_type']);

        $space_id = $space_machine['space_id'];

        $order_length = $space_machine['space']['order_length'];
        $order_time = $order_length*60*60;

        $where['space_id'] = $space_id;
        $where['teach_state'] = 'yes';
        $coachlist = Db::name('coach_sc')->where($where)->field(['coach_id','name'])->select();


        //违约订单
        $this->not_coming_order($stu_id,$today_start,$student_type);

        //完成正在进行的订单
        $this->finish_today_order($stu_id,$today_start,$today_end,$student_type);

        //其他馆的异常订单
        $this->unusual_order($stu_id,$space_machine,$today_start,$today_end);

        //订单是否可以开机
        $this->order_boot($stu_id,$today_start,$today_end,$machine_code,$order_time,$coachlist,$student_type);
        
        //没有订单,生成订单
        $this->create_order($stu_id,$space_machine,$today_start,$today_end,'paid',$order_time,$student['subject_type'],$coachlist,$student_type);

        $this->error('订单异常');
    }


    public function submitorder(){
        $params = $this->request->post();
        
        // $params["machine_code"]= "粤B4a454";
        // $params["stu_id"]="CSN20210425112607670801";
        // $params["student_type"]= "student";
        // $params["ordernumber"]= "";
        // $params["reserve_starttime"]="1664530483";
        // $params["reserve_endtime"]="1664541283";
        // $params["should_endtime"]="1664541283";
        // $params["order_status"]= "paid";
        // $params["ordertype"]= "2";
        // $params["car_type"]= "cartype1";
        // $params["subject_type"]= "subject2";
        // $params["start_type"]= "1";//订单状态，1:开始学习,2:继续学习,3:下车

        if(empty($params['stu_id'])|| empty($params['reserve_starttime'])||empty($params['reserve_endtime'])|| empty($params['machine_code']) ||
         empty($params['order_status']) || empty($params['ordertype']) || empty($params['car_type']) 
         || empty($params['subject_type']) || empty($params['should_endtime']) || empty($params['start_type'])){
            $this->error('参数缺失');
        }
        $this->time_valatite($params['reserve_starttime'],$params['reserve_endtime']);

        $student_type = $params['student_type'];
        $order = $params;
        
        $stu_id = $params['stu_id'];
        //提交不能太频繁
        $submit_stu_id = Cache::get('submit_id'.$stu_id);
        if($submit_stu_id){
            $this->error('请不要频繁提交操作');
        }
        
        
        $machine = $this->car->with(['space'])->where('car.machine_code',$params['machine_code'])->find();
        $params['machine_id'] = $machine['id'];
        $params['space_id'] = $machine['space_id'];
        $params['cooperation_id'] = $machine['cooperation_id'];
        $whereorder['order_status'] = ['in',['paid','accept_unexecut','executing']];
        $whereorder['stu_id'] = $stu_id ;
        $whereorder['ordertype'] = 2;
        // $student = $this->student->with(['space'])->where('stu_id',$stu_id)->find();

        $start_type = $params['start_type'];
        $params['starttime'] = time();
        unset($params['start_type']);
        unset($params['machine_code']);
        unset($params['student_type']);

        if( $start_type== 1){//开始学习
            $update['student_boot_type'] = 1;
            $params['order_status'] = 'executing';
            if(!$params['ordernumber']){
                $params['ordernumber'] = 'CON'.date("YmdHis") . mt_rand(100000, 999999);
                if($student_type == 'student'){
                    $res = $this->ordersc->save($params);
                }elseif($student_type == 'intent_student'){
                    $res = $this->temordersc->save($params);
                }
            }else{
                $ordernumber = $params['ordernumber'];
                unset($params['ordernumber']);
                if($student_type == 'student'){
                    $res = $this->ordersc->where(['ordernumber'=>$ordernumber])->update($params);
                }elseif($student_type == 'intent_student'){
                    $res = $this->temordersc->where(['ordernumber'=>$ordernumber])->update($params);
                }
            }
        }elseif($start_type == 2){//继续学习
            $this->success('返回成功');
        }elseif($start_type == 3){//下车
            unset($params['starttime']);
            $params['endtime'] = time();
            $params['order_status'] = 'finished';
            if($student_type== 'student'){
                $res = $this->ordersc->where(['ordernumber'=>$params['ordernumber']])->update($params);
            }elseif($student_type == 'intent_student'){
                $res = $this->temordersc->where(['ordernumber'=>$params['ordernumber']])->update($params);
            }
        }
        if($res){
            $this->success('返回成功');
        }else{
            $this->error('上传失败');
        }

        
    }



    /**
     * 确认订单
     */
    public function sureorder(){
        $params = $this->request->post();
        // $params['machine_code'] = '粤B4a454';
        // $params['stu_id'] = 'CSN20210409102726705658';
        // $params['ordernumber'] = '';
        // $params['reserve_starttime'] = time();
        // $params['reserve_endtime'] = time()+3600;
        // $params['should_endtime'] = time()+3600;
        // $params['coach_id'] = 'CTN20210311103714746874';
        // $params['order_status'] = 'paid';
        // $params['ordertype'] = 2;
        // $params['car_type'] = 'cartype1';
        // $params['subject_type'] = 'subject2';
        if(empty($params['stu_id']) ||empty($params['reserve_starttime'])||empty($params['reserve_endtime'])|| empty($params['machine_code']) ||
         empty($params['order_status']) || empty($params['ordertype']) || empty($params['coach_id']) || empty($params['car_type']) 
         || empty($params['subject_type']) || empty($params['should_endtime']) ){
            $this->error('参数缺失');
        }
        $this->time_valatite($params['reserve_starttime'],$params['reserve_endtime']);
        $stu_id = $params['stu_id'];
        $student = $this->student->with(['space'])->where('stu_id',$stu_id)->find();

        if(!$student){
            $this->error('当前学员编号异常');
        }
        if(!empty($params['ordernumber'])){
            $should_endtime = $this->ordersc->where('ordernumber',$params['ordernumber'])->find();
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




    public function getstudent($stu_id,$machine,$student_type)
    {
        if($student_type == 'student'){
            $student = $this->student->where('stu_id',$stu_id)->find();
            $student['student_type'] = 'student';
        }else{
            $student = $this->intentstudent->where('stu_id',$stu_id)->find();
            $student['student_type'] = 'intent_student';
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
                $count = $this->ordersc->where($count_order)->count();
                if($count >= $space['times_limit_cooperation']){
                    $this->error('当前场馆对您学习次数有限，请联系您报名所属场馆了解详情后再上机');
                }
            }
        }else{
            $this->error('当前场馆不对您开放，请联系管理员后再操作');
        }

    }

    public function order_boot($stu_id,$today_start,$today_end,$machine_code,$order_time,$coachlist,$student_type)
    {
        //订单开机过的直接开机
        $where['stu_id'] = $stu_id;
        $where['reserve_starttime'] = ['between',[$today_start,$today_end]];
        $where['order_status'] = ['neq','cancel_refunded'];
        
        if($student_type == 'student'){
            $order =$this->ordersc->with(['space','car'])->where($where)->order('reserve_starttime desc')->find();
        }else{
            $order =$this->temordersc->with(['space','car'])->where($where)->order('reserve_starttime desc')->find();
        }
        
        $data = [];
        if($order){
            $data['ordernumber'] = $order['ordernumber'];
            $data['space_name'] = $order['space']['space_name'];
            $data['space_id'] = $order['space_id'];
            $data['machine_id'] = $order['car']['id'];
            $data['reserve_starttime'] = $order['reserve_starttime'];
            $data['reserve_endtime'] = $order['reserve_endtime'];
            $data['starttime'] = $order['starttime'];
            $data['endtime'] =  $order['endtime'];
            $data['should_endtime'] = $order['should_endtime'];

            $data['order_status'] = $order['order_status'];
            $data['ordertype'] = $order['ordertype'];
            $data['car_type'] = $order['car_type'];
            $data['car_type_text'] = $order['car_type_text'];
            $data['coachlist'] = $coachlist;

            if($order['ordertype'] == 1){
                $data['ordertype_name'] = '预约下单';
            }elseif($order['ordertype'] == 2){
                $data['ordertype_name'] = '现场下单';
            }elseif($order['ordertype'] == 3){
                $data['ordertype_name'] = '后台下单';
            }
            if($order['starttime']){
                //有开始时间
                if($order['should_endtime']<time()){
                    $data = [];
                }
            }else{
                //没有开始时间
                $data['should_endtime'] = time()+$order_time;
            }
        }
        if($data){
            $this->success('返回成功',$data);
        }
        
    }

    /**
     * 创建订单
     */
    public function create_order($stu_id,$space_machine,$today_start,$today_end,$order_status,$order_time,$subject_type,$coachlist,$student_type){
        $where['order_status'] = ['neq','cancel_refunded'];
        $where['stu_id'] = $stu_id;
        $where['student_boot_type'] = 0;
        $where['space_id'] = $space_machine['space_id'];
        $where['machine_id'] = $space_machine['id'];
        $where['reserve_starttime'] = ['between',[$today_start,$today_end]];

        if($student_type == 'student'){
            $order = $this->ordersc->where($where)->find();
        }else{
            $order = $this->temordersc->where($where)->find();
        }
        // var_dump($order->toArray());exit;
        if(!$order){
            $data['space_name'] = $space_machine['space']['space_name'];
            $data['space_id'] = $space_machine['space_id'];
            $data['cooperation_id'] = $space_machine['cooperation_id'];
            $data['machine_id'] = $space_machine['id'];
            $data['pay_status'] = $space_machine['space']['pay_status'];
            $data['reserve_starttime'] = time();
            $data['coachlist'] = $coachlist;

            $data['starttime'] = '';
            $data['endtime'] = '';
            $data['should_endtime'] = time()+$order_time ;
            $data['reserve_endtime'] = time()+$order_time;
            $data['subject_type'] = $subject_type;

            $data['order_status'] = $order_status;
            $data['ordertype'] = 2;
            if($data['ordertype'] == 1){
                $data['ordertype_name'] = '预约下单';
            }elseif($data['ordertype'] == 2){
                $data['ordertype_name'] = '现场下单';
            }elseif($data['ordertype'] == 3){
                $data['ordertype_name'] = '后台下单';
            }
            $data['car_type'] = $space_machine['car_type'];
            $data['car_type_text'] = $space_machine['car_type_text'];
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

        $update['order_status']= 'finished';

        if($student_type == 'student'){
            $this->ordersc->where($where)->update($update);
        }else{
            $this->temordersc->where($where)->update($update);
        }   
    }

    public function machine_validate($stu_id,$space_machine){
        $student = $this->student->where('stu_id',$stu_id)->find();
        if($student['car_type'] != $space_machine['car_type']){
            $this->error('您当前车型为'.$student['car_type_text'].'当前机器为'.$space_machine['car_type_text'].'请选择车型机器上机');
        }
    }

    
    public function reserve_order($stu_id,$today_start,$today_end,$machine_id,$space_id,$type,$order_time){
        $where['order_status'] = 'paid';
        $where['stu_id'] = $stu_id;
        $where['student_boot_type'] = 0;
        $where['space_id'] = $space_id;
        $where['reserve_starttime'] = ['between',[$today_start,$today_end]];
        $order = $this->ordersc->where($where)->find();
        if($order){
            if($type ==2){
                $this->error('已有预约，请转到学员登录');
            }
            $data['space_name'] = $this->space->where('id',$order['space_id'])->find()['space_name'];
            $data['space_id'] = $space_id;
            $data['machine_code'] = $this->car->where('id',$machine_id)->find()['machine_code'];
            $data['reserve_starttime'] = $order['reserve_starttime'];
            $data['reserve_endtime'] = $order['reserve_endtime'];
            $data['coach_id'] = $order['coach_id'];
            $data['coach_name'] = $this->coachsc->where('coach_id',$order['coach_id'])->find()['name'];
            $data['subject_type'] = $order['subject_type_text'];
            $data['car_type'] = $order['car_type_text'];
            $data['starttime'] = NULL;
            $data['endtime'] = NULL;
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
        $machinecar = $this->car->where('machine_id',$machine_id)->find();
        if(!$machinecar){
            $this->error('机器码错误，请上传正确机器码');
        }
        return $machinecar['space_id'];
    }


    

    public function coach_boot($stu_id,$today_start,$today_end,$machine_id){
        $where['order_status'] = ['neq','cancel_refunded'];
        $where['stu_id'] = $stu_id;
        $where['reserve_starttime'] = ['between',[$today_start,$today_end]];
        $where['should_endtime'] = ['>',time()];
        $where['machine_id'] = $machine_id;
        $order = $this->ordersc->where($where)->find();

        if($order){
            $this->success('当前订单您已授权。','',2);
        }
    }
    
    

    public function not_coming_order($stu_id,$today_start,$student_type){
        //开机的结束
        $where_finish['stu_id'] = $stu_id;
        $where_finish['reserve_starttime'] = ['<',$today_start];
        $where_finish['order_status'] = ['in',['accept_unexecut','executing']];
        
        $where_cancel['stu_id'] = $stu_id;
        $where_cancel['reserve_starttime'] = ['<',$today_start];
        $where_cancel['order_status'] = 'paid';

        $update_finish['order_status'] = 'finished';
        $update_cancel['order_status'] = 'cancel_refunded';

        if($student_type == 'student'){
            $this->ordersc->where($where_finish)->update($update_finish);
            //没开机的取消
            $this->ordersc->where($where_finish)->update($update_finish);
        }else{
            $this->temordersc->where($where_finish)->update($update_finish);
            //没开机的取消
            $this->temordersc->where($where_finish)->update($update_finish);
        }
    }

    public function unusual_order($stu_id,$space_machine,$today_start,$today_end)
    {
        $where['order_status'] = ['in',['unpaid','paid','accept_unexecut','executing']];
        $where['stu_id'] = $stu_id;
        $where['space_id'] = ['neq',$space_machine['space_id']];
        $where['reserve_starttime'] = ['between',[$today_start,$today_end]];
        $order = $this->ordersc->where($where)->select();

        foreach($order as $v){
            if($v['order_status'] == 'executing'){
                $update['order_status'] = 'finished';
                $where_order['id'] = $v['id'];
                $this->ordersc->where($where_order)->update($update);
            }else{
                $update['order_status'] = 'cancel_refunded';
                $where_order['id'] = $v['id'];
                $this->ordersc->where($where_order)->update($update);
            }
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
        $machinecar = $this->car->where('machine_code',$params['machine_code'])->find();
        $where['space_id'] = $machinecar['space_id'];
        $where['teach_state'] = 'yes';
        $coach = Db::name('coach_sc')->where($where)->field(['coach_id','name'])->select();
        if($coach){
            $data = $coach;
            $this->success('返回成功', $data);
        }else{
            $this->error('当前没有教员');
        }
    }

    

}
