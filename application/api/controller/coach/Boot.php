<?php

namespace app\api\controller\coach;

use app\common\controller\Api;
use think\cache;

/**
 * 开机流程所需接口
 */
class Boot extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent ::_initialize();
        $this->order = new \app\admin\model\Order;
        $this->temporaryorder = new \app\admin\model\Temporaryorder;
        $this->student = new \app\admin\model\Student;
        $this->intentstudent = new \app\admin\model\IntentStudent;
        $this->machinecar = new \app\admin\model\Machinecar;
        $this->space = new \app\admin\model\Space;
        $this->coach = new \app\admin\model\Coach;

        $this->common = new \app\api\controller\Common;
    }

    /**
     * 获取要授权订单信息
     */
    public function getorder(){
        $params = $this->request->post();
        if(empty($params['ordernumber']) || empty($params['student_type'])){
            $this->error('参数缺失');
        }
        $student_type = $params['student_type'];
        if($student_type == 'student'){
            $order = $this->order->with(['admin','space','coach'])->where('order.ordernumber',$params['ordernumber'])->find();
        }else{
            $order = $this->temporaryorder->with(['admin','space','coach'])->where('temporaryorder.ordernumber',$params['ordernumber'])->find();
        }
        if(!$order){
            $this->error('当前没有订单');
        }
        if($order['coach_boot_type'] ==1){
            Cache::set('ordernumber_'.$order['ordernumber'],1,60*30);
            $this->error('此单已授权');
        }
        $data['student_type']= $student_type;
        $data['subject_type'] = $order['subject_type_text'];
        $data['car_type'] = $order['car_type_text'];
        $data['coach'] = $order['coach']['name'];
        if($student_type == 'student'){
            $student = $this->student->where('stu_id',$order['stu_id'])->find();
        }else{
            $student = $this->intentstudent->where('stu_id',$order['stu_id'])->find();
        }
        $data['student_phone']= $student['phone'];
        $data['student_name']= $student['name'];
        $data['school_name'] = $order['admin']['nickname'];
        $data['space_name'] = $order['space']['space_name'];
        $data['ordertype'] = $order['ordertype'];
        $data['reserve_starttime'] = $order['reserve_starttime'];
        $data['reserve_endtime'] = $order['reserve_endtime'];
        $this->success('返回成功',$data);
    }

    /**
     * 提交授权订单
     */
    public function submitorder(){
        $params = $this->request->post();
        if(empty($params['ordernumber']) || empty($params['coach_id']) || empty($params['student_type'])){
            $this->error('参数缺失');
        }
        $ordernumber = $params['ordernumber'];
        $coach_id = $params['coach_id'];
        $student_type = $params['student_type'];

        //提交不能太频繁
        $submit_stu_id = Cache::get('submit_id'.$coach_id);
        if($submit_stu_id){
            $this->error('请不要频繁操作');
        }
        $student_type = 'student';
        $where['ordernumber'] = $ordernumber;
        $where['coach_boot_type'] = 0;
        $order = $this->order->where($where)->find();
        if(!$order){
            $student_type = 'intent_student';
            $order = $this->temporaryorder->where($where)->find();
        }
        if(!$order){
            $this->error('当前没有订单授权');
        }
        if($order['coach_boot_type'] == 1 ){
            Cache::set('ordernumber_'.$ordernumber,1,2*60*60);
            $this->error('已提交授权');
        }
        $update['coach_boot_type'] = 1;
        $machine_space = $this->machinecar->with(['space'])->where('machinecar.id',$order['machine_id'])->find();
        $space_id = $machine_space['space_id'];
        $period_surplus = $machine_space['space']['period_surplus'];
        $period_surplus = $period_surplus - 2;
        if($period_surplus < 0){
            $this->error('当前没有课时请充值后授权开机');
        }
        $update['order_status'] = 'accept_unexecut';
        $update['boot_person'] = $coach_id;

        //扣款记录
        if($student_type == 'student'){
            $res1 = $this->order->where('ordernumber',$order['ordernumber'])->update($update);
        }else{
            $res1 = $this->temporaryorder->where('ordernumber',$order['ordernumber'])->update($update);
        }
        $update2['period_surplus'] = $period_surplus;
        $res2 = $this->space->where('id',$space_id)->update($update2);
        if($res1 || $res2){
            Cache::set('submit_id'.$coach_id,$coach_id,2);
            Cache::set('ordernumber_'.$ordernumber,1,30*60);
            $this->success('返回成功');
        }else{
            $this->error('提交失败');
        }
    }

    /**
     * 取消订单
     */
    public function cancel_refunded(){
        $params = $this->request->post();
        if(empty($params['ordernumber']) || empty($params['student_type']) || empty($params['coach_id'])){
            $this->error('参数缺失');
        }
        $ordernumber = $params['ordernumber'];
        $update['order_status'] = 'cancel_refunded';
        $student_type = $params['student_type'];
        if($student_type == 'student'){
            $res = $this->order->where('ordernumber',$ordernumber )->update($update);
        }else{
            $res = $this->temporaryorder->where('ordernumber',$ordernumber)->update($update);
        }
        
        if($res){
            $this->success('取消成功');
        }else{
            $this->error('取消失败');
        }
    }

    public function xcx_back_window(){
        $params = $this->request->post();
        if(empty($params['machine_code'])|| empty($params['coach_id'])){
            $this->error('参数缺失');
        }
        $machine_code = $params['machine_code'];
        $coach_id = $params['coach_id'];
        $res = $this->coach->where('coach_id',$coach_id)->find();
        if($res){
            Cache::set('back_window'.$machine_code,1);
            $this->success('授权成功');
        }else{
            $this->error('授权失败');
        }
    }

    /**
     * 获取退出桌面的二维码
     */
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

    /**
     * 确认是否返回界面
     */
    public function get_window(){
        $params = $this->request->post();
        if(empty($params['machine_code'])){
            $this->error('参数缺失');
        }
        $machine_code = $params['machine_code'];
        $path = Cache::pull('back_window'.$machine_code);
        if($path ==1){
            $this->success('返回成功');
        }else{
            $this->error('返回失败');
        }
    }

}
