<?php

namespace app\api\controller\student;

use app\common\controller\Api;
use PhpOffice\PhpSpreadsheet\Calculation\Statistical\Averages;
use think\cache;
use think\Db;

/**
 * 开机流程所需接口
 */
class BootAi extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    protected $car = null;
    protected $student = null;
    protected $device = null;
    protected $space = null;
    protected $order = null;
    protected $ordersc = null;
    protected $temordersc = null;
    protected $admin = null;
    protected $authgroup = null;
    protected $authgroupaccess = null;
    protected $common = null;
    protected $cooperation = null;
    protected $coach = null;
    protected $machineai = null;
    protected $intentstudent = null;

    public function _initialize()
    {
        parent ::_initialize();
        $this->car = new \app\admin\model\Car;
        $this->student = new \app\admin\model\Student;
        $this->intentstudent = new \app\admin\model\Intentstudent;
        $this->device = new \app\admin\model\Device;
        $this->space = new \app\admin\model\Space;
        $this->ordersc = new \app\admin\model\Ordersc;
        $this->order = new \app\admin\model\Order;
        $this->temordersc = new \app\admin\model\Temordersc;
        $this->admin = new \app\admin\model\Admin;
        $this->authgroup = new \app\admin\model\AuthGroup;
        $this->authgroupaccess = new \app\admin\model\AuthGroupAccess;
        $this->common = new \app\api\controller\Common;
        $this->cooperation = new \app\admin\model\Cooperation;
        $this->coach = new \app\admin\model\Coach;
        $this->machineai = new \app\admin\model\Machineai;

    }

    
    public function getorder()
    {
        $params = $this->request->post();
        
        // $params['stu_id'] = 'CSN20210425112607670801';
        // $params['machine_code'] = '粤B12312';
        Cache::set('getorder',$params,60);
        if(empty($params['stu_id']) || empty($params['machine_code'])){
            $this->error('参数缺失');
        }
        $stu_id = $params['stu_id'];
        $machine_code = $params['machine_code'];
        $today_start = strtotime(date('Y-m-d 00:00:00',time()));
        $today_end = $today_start + 24*3600-1;
        $machine_ai = $this->machineai->with('space')->where(['machineai.machine_code'=>$machine_code])->find();
        $student_type = $params['student_type'];
        if(!$machine_ai){
            $this->error('扫码异常，当前机器人无法找到信息');
        }
        $student = $this->getstudent($stu_id,$machine_ai,$student_type);
        
        $space_id = $machine_ai['space_id'];

        $order_length = $machine_ai['space']['order_length'];
        $order_time = $order_length*60*60;

        $where['space_id'] = $space_id;
        $where['teach_state'] = 'yes';
        $coachlist = Db::name('coach_sc')->where($where)->field(['coach_id','name'])->select();
        //违约订单
        $this->not_coming_order($stu_id,$today_start);

        //完成正在进行的订单
        $this->finish_today_order($stu_id,$today_start,$today_end);

        //其他馆的异常订单
        $this->unusual_order($stu_id,$machine_ai,$today_start,$today_end);

        //订单是否可以开机
        $this->order_boot($stu_id,$today_start,$today_end,$machine_code,$order_time,$coachlist,$student_type);


        //没有订单,生成订单
        $this->create_order($stu_id,$machine_ai,$today_start,$today_end,'paid',$order_time,$student['subject_type'],$coachlist);

        $this->error('订单异常');
    }


    public function submitorder(){
        $params = $this->request->post();
        
        // $params["machine_code"]= "JQR001";
        // $params["stu_id"]="CSN20210425112607670801";
        // $params["ordernumber"]= "";
        // $params["reserve_starttime"]="1664530483";
        // $params["reserve_endtime"]="1664541283";
        // $params["should_endtime"]="1664541283";
        // $params["order_status"]= "paid";
        // $params["ordertype"]= "2";
        // $params["car_type"]= "cartype1";
        // $params["subject_type"]= "subject2";

        if(empty($params['stu_id'])|| empty($params['reserve_starttime'])||empty($params['reserve_endtime'])|| empty($params['machine_code']) ||
         empty($params['order_status']) || empty($params['ordertype']) || empty($params['car_type']) 
         || empty($params['subject_type']) || empty($params['should_endtime']) || empty($params['start_type']) || empty($params['student_type'])){
            $this->error('参数缺失');
        }
        // var_dump($params);exit;
        $this->time_valatite($params['reserve_starttime'],$params['reserve_endtime']);

        $student_type = $params['student_type'];
        if($student_type == 'student'){
            $student = $this->student->where(['stu_id'=>$params['stu_id']])->find();
        }else{
            $student = $this->intentstudent->where(['stu_id'=>$params['stu_id']])->find();
        }
        $student['student_type'] = $params['student_type'];
        $stu_id = $params['stu_id'];
        //提交不能太频繁
        $submit_stu_id = Cache::get('submit_id'.$stu_id);
        if($submit_stu_id){
            $this->error('请不要频繁提交操作');
        }
        
        
        $machine_ai = $this->machineai->with(['space'])->where(['machine_code'=>$params['machine_code']])->find();
        $car = $this->car->where(['machine_ai_id'=>$machine_ai['id']])->find();
        $params['machine_id'] = $car['id'];
        $params['machine_ai_id'] = $machine_ai['id'];
        $params['space_id'] = $machine_ai['space_id'];
        $params['cooperation_id'] = $machine_ai['cooperation_id'];
        // $whereorder['order_status'] = ['in',['paid','accept_unexecut','executing']];
        // $whereorder['stu_id'] = $stu_id ;
        // $whereorder['ordertype'] = 2;

        $start_type = $params['start_type'];
        $params['starttime'] = time();
        unset($params['start_type']);
        unset($params['machine_code']);
        unset($params['student_type']);
        $order = $params;
        $res = '';
        if( $start_type== 1){//开始学习
            $update['student_boot_type'] = 1;
            // $params['order_status'] = 'executing';
            if(!$params['ordernumber']){
                $params['ordernumber'] = 'CON'.date("YmdHis") . mt_rand(100000, 999999);
                if($student_type == 'student'){
                    $res = $this->ordersc->save($params);
                }else{
                    $res = $this->temordersc->save($params);
                }
                $this->put_info($params,$machine_ai,$student);
            }else{
                $ordernumber = $params['ordernumber'];
                unset($params['ordernumber']);
               
                $this->put_info($order,$machine_ai,$student);
                $res = $this->ordersc->where(['ordernumber'=>$ordernumber])->update($params);
            }
        }elseif($start_type == 2){//继续学习
            
            $this->put_info($params,$machine_ai,$student);
            $this->error('授权成功');
        }else{
            $this->error('异常数据');
        }
        if($res){
            $this->put_info($params,$machine_ai,$student);
            $this->error('授权成功');
        }else{
            $this->error('上传失败');
        }
        
        
    }
  


    public function put_info($order,$machine_ai,$student)
    {
        $data['ordernumber'] = $order['ordernumber'];
        // $student = $this->student->where('stu_id',$order['stu_id'])->find();
        // $machinecar = $this->machinecar->where('id',$order['machine_id'])->find();
        $data['sim'] = $machine_ai['sim'];
        // $data['address'] = $machine_ai['address'];
        $data['imei'] = $machine_ai['imei'];
        $data['sn'] = $machine_ai['sn'];
        $data['terminal_equipment'] = $machine_ai['terminal_equipment'];
        $data['study_machine'] = $machine_ai['study_machine'];
        if($student['student_type'] == 'student'){
            $data['idcard']= $student['idcard'];
        }else{
            $data['idcard']= '';
        }
        $data['stu_id'] = $student['stu_id'];
        $data['stu_name'] = $student['name'];
        $data['phone'] =  $student['phone'];
        $data['subject_type'] = $machine_ai['subject_type'];
        $data['subject_type_text'] = $machine_ai['subject_type_text'];
        $data['car_type'] = $machine_ai['car_type'];
        $data['car_type_text'] = $machine_ai['car_type_text'];

        $data['student_type'] = $student['student_type'];
        Cache::set('machine_'.$machine_ai['machine_code'],$data,5*60);
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
                $count = $this->order->where($count_order)->count();
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
            $data['coachlist'] = $coachlist;

            $data['order_status'] = $order['order_status'];
            $data['ordertype'] = $order['ordertype'];
            $data['car_type'] = $order['car_type'];
            $data['car_type_text'] = $order['car_type_text'];
            
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
    public function create_order($stu_id,$space_machine,$today_start,$today_end,$order_status,$order_time,$subject_type,$coachlist){
        $where['order_status'] = ['neq','cancel_refunded'];
        $where['stu_id'] = $stu_id;
        $where['student_boot_type'] = 0;
        $where['space_id'] = $space_machine['space_id'];
        $where['machine_id'] = $space_machine['id'];
        $where['reserve_starttime'] = ['between',[$today_start,$today_end]];
        
        $order = $this->ordersc->where($where)->find();
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

    public function finish_today_order($stu_id,$today_start,$today_end)
    {
        $where['starttime'] = ['between',[$today_start,$today_end]];
        $where['order_status'] = 'executing';
        $where['stu_id'] = $stu_id;
        $where['endtime'] = ['<',time()];
        $order = $this->ordersc->where($where)->select();
        if($order){
            $update['order_status']= 'finished';
            $this->ordersc->where($where)->update($update);
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
            $data['coach_name'] = $this->coach->where('coach_id',$order['coach_id'])->find()['name'];
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
    
    

    public function not_coming_order($stu_id,$today_start){
        //开机的结束
        $where_finish['stu_id'] = $stu_id;
        $where_finish['reserve_starttime'] = ['<',$today_start];
        $where_finish['order_status'] = ['in',['accept_unexecut','executing']];
        $update_finish['order_status'] = 'finished';

        $this->ordersc->where($where_finish)->update($update_finish);

        //没开机的取消
        $where_cancel['stu_id'] = $stu_id;
        $where_finish['reserve_starttime'] = ['<',$today_start];
        $where_finish['order_status'] = 'paid';
        $update_cancel['order_status'] = 'cancel_refunded';
        // $order = $this->ordersc->where($where_finish)->find();
        $this->ordersc->where($where_finish)->update($update_finish);
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
