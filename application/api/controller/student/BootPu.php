<?php

namespace app\api\controller\student;

use app\common\controller\Api;
use think\cache;
use think\Db;

/**
 * 开机流程所需接口
 */
class BootPu extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    protected $student = null ;
    protected $device = null ;
    protected $space = null ;
    protected $admin = null ;
    protected $authgroup = null ;
    protected $authgroupaccess = null ;
    protected $common = null ;
    protected $cooperation = null ;
    protected $pickuporder = null ;


    public function _initialize()
    {
        parent ::_initialize();
        $this->student = new \app\admin\model\Student;
        $this->pickuporder = new \app\admin\model\Pickuporder;
        $this->device = new \app\admin\model\Device;
        $this->space = new \app\admin\model\Space;
        $this->admin = new \app\admin\model\Admin;
        $this->authgroup = new \app\admin\model\AuthGroup;
        $this->authgroupaccess = new \app\admin\model\AuthGroupAccess;
        $this->common = new \app\api\controller\Common;
        $this->cooperation = new \app\admin\model\Cooperation;

    }


    public function getorder()
    {
        $params = $this->request->post();
        
        // $params['stu_id'] = 'CSN20231109164906884188';
        // $params['machine_code'] = '粤B1234学';
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

        $order = $this->pickuporder->with(['car','student','admin','space'])->where(['car.machine_code'=>$machine_code])->find();
        
        if($order){
            $res = $this->orderdetail($order);
            $this->success('返回成功',$res);
        }else{
            $this->error('当前没有订单');
        }
    }


    public function submitorder()
    {
        $params = $this->request->post();
        
        // $params['stu_id'] = 'CSN20231109164906884188';
        // $params['machine_code'] = '粤B1234学';
        // $params['student_type'] = 'student';
        // $params['ordernumber'] = 'CON20231121195908894333';
        if(empty($params['stu_id']) || empty($params['machine_code'])|| empty($params['student_type'])|| empty($params['ordernumber'])){
            $this->error('参数缺失');
        }
        
        $stu_id = $params['stu_id'];

        // $where['student_type'] = $params['student_type'];
        $where['stu_id'] = $params['stu_id'];
        $where['ordernumber'] = $params['ordernumber'];
        $where['order_status'] = 'unpaid';

        $update['order_status'] = 'finished';
        $update['pickup_time'] = time();
        $order = [];
        if($params['student_type'] == 'student'){
            $order = $this->pickuporder->where($where)->update($update);
        }
        if($order){
            $this->success('返回成功');
        }else{
            $where2['stu_id'] = $params['stu_id'];
            $where2['ordernumber'] = $params['ordernumber'];
            $where2['order_status'] = 'finished';
            $order = $this->pickuporder->where($where2)->find();
            if($order){
                $this->error('当前订单已上车，无需重复提交');
            }
            $this->error('当前没有订单');
        }
    }


    public function orderdetail($order) {
        $data['ordernumber'] = $order['ordernumber'];
        $data['space_name'] = $order['space']['space_name'];
        $data['space_id'] = $order['space_id'];
        $data['cooperation_id'] = $order['cooperation_id'];
        $data['machine_id'] = $order['id'];
        $data['pay_status'] = $order['space']['pay_status'];
        $data['name'] = $order['student']['name'];
        $data['stu_id'] = $order['student']['stu_id'];
        $data['phone'] = $order['student']['phone'];
        $data['reserve_starttime'] = $order['reserve_starttime'];

        $data['reserve_endtime'] = $order['reserve_endtime'];

        $data['order_status'] = $order['order_status'];
        $data['ordertype'] = $order['ordertype'];
        return $data;
    }





    public function getPayId()
    {
        $str = date('Ymd').substr(implode('', array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8); 
        return $str;
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





    


    


}
