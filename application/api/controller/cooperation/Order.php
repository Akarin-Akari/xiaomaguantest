<?php

namespace app\api\controller\cooperation;

use app\common\controller\Api;

/**
 * 开机流程所需接口
 */
class Order extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent ::_initialize();
        $this->order = new \app\admin\model\Order;
        $this->ordersc = new \app\admin\model\Ordersc;
        $this->temporaryorder = new \app\admin\model\Temporaryorder;
        $this->space = new \app\admin\model\Space;

        $this->common =  new \app\api\controller\Common;
    }


    public function order_quiretest(){
        $params = $this->request->post();
        // $params['space_id_list'] = '84';
        // $params['page'] = 1;
        // $params['starttime'] = '2022-11-03';
        // $params['endtime'] = '2022-11-10';
        // $params['order_status'] = '';
        // $params['student_type'] = 'student';
        // $params['ordertype'] = 1;
        // $params['type_index'] = 1;
        if(empty($params['space_id_list']) || empty($params['page']) || empty($params['starttime']) ||
        empty($params['endtime']) ||!array_key_exists('order_status', $params)|| empty($params['student_type']) ||empty($params['ordertype'])){
           $this->error('参数缺失');
        }
        $student_type = $params['student_type'];
        $space_id_list = $params['space_id_list'];
        $order_status = $params['order_status'];
        $page = $params['page'];
        $starttime = strtotime($params['starttime'].'00:00:00');
        $endtime = strtotime($params['endtime'].'23:59:59');
        $space = $this->space->where(['id'=>$space_id_list])->find();
        if($space['space_type'] == 'ai_car'){
            $type_index = 'AI';
        }elseif($space['space_type'] == 'car'){
            $type_index = 'SC';
        }else{
            $row = [];
            $list['order'] = $row;
            $this->success('返回成功',$list);
        }
        $pagenum = 10;
        $numl = $pagenum*($page-1);
        $row = [];
        if($student_type == 'student'){
            if($type_index == 'AI'){
                $where['order.reserve_starttime'] = ['between',[$starttime,$endtime]];
                $where['order.space_id'] = $space_id_list;
                $where['order.ordertype'] = $params['ordertype'];
                if($order_status ){
                    $where['order.order_status'] = $order_status;
                }
                $orderlist = $this->order->with(['admin','student'])->where($where)->limit($numl,$pagenum)->order('reserve_starttime desc')->select();
            }else{
                $where['ordersc.reserve_starttime'] = ['between',[$starttime,$endtime]];
                $where['ordersc.space_id'] = $space_id_list;
                $where['ordersc.ordertype'] = $params['ordertype'];
                if($order_status ){
                    $where['ordersc.order_status'] = $order_status;
                }
                $orderlist = $this->ordersc->with(['admin','student'])->where($where)->limit($numl,$pagenum)->order('reserve_starttime desc')->select();
            }
            foreach($orderlist as $k=>$v){
                $week = $this->common->getWeek($v['reserve_starttime']);
                $row[$k]['week'] = $week;
                $row[$k]['stu_id'] = $v['stu_id'];
                $row[$k]['ordernumber'] = $v['ordernumber'];
                $row[$k]['study_space_name'] = $v['admin']['nickname'];
                $student_source = $this->space->where('id',$v['student']['space_id'])->find();
                $row[$k]['source_space_name'] = $student_source['space_name'];
                $row[$k]['reserve_starttime'] = $v['reserve_starttime'];
                $row[$k]['reserve_endtime'] = $v['reserve_endtime'];
                $row[$k]['order_status'] = $v['order_status'];
                $row[$k]['student_name'] = $v['student']['name'];
                $row[$k]['car_type'] = $v['car_type_text'];
                $row[$k]['subject_type'] = $v['subject_type_text'];
            }

            $list['order'] = $row;
        }else{
            $where['temporaryorder.reserve_starttime'] = ['between',[$starttime,$endtime]];
            $where['temporaryorder.space_id'] = ['in',$space_id_list];
            $where['temporaryorder.order_status'] = $order_status;
            if($order_status ){
                $where['temporaryorder.ordertype'] = $params['ordertype'];
            }
            $orderlist = $this->temporaryorder->with(['admin','intentstudent','coach'])->where($where)->limit($numl,$pagenum)->order('id desc')->select();

            foreach($orderlist as $k=>$v){
                $week = $this->common->getWeek($v['reserve_starttime']);
                $row[$k]['week'] = $week;
                $row[$k]['stu_id'] = $v['stu_id'];
                $row[$k]['ordernumber'] = $v['ordernumber'];
                $row[$k]['study_space_name'] = $v['admin']['nickname'];
                $row[$k]['source_space_name'] = '';
                // $row[$k]['coach_name'] = $v['coach']['name'];
                $row[$k]['reserve_starttime'] = $v['reserve_starttime'];
                $row[$k]['reserve_endtime'] = $v['reserve_endtime'];
                $row[$k]['order_status'] = $v['order_status'];
                $row[$k]['student_name'] = $v['intentstudent']['name'];
                $row[$k]['car_type'] = $v['car_type_text'];
                $row[$k]['subject_type'] = $v['subject_type_text'];
            }
            $list['order'] = $row;
        }
        $this->success('返回成功',$list);
    }

    /**
     * 查询订单列表
     */
    public function order_quire(){
        $params = $this->request->post();
        
        if(empty($params['space_id_list']) || empty($params['page']) || empty($params['starttime']) ||
        empty($params['endtime']) ||!array_key_exists('order_status', $params)|| empty($params['student_type']) ||empty($params['ordertype'])){
           $this->error('参数缺失');
        }
        if(empty($params['space_id_list']) || empty($params['page']) || empty($params['starttime']) ||
        empty($params['endtime']) ||!array_key_exists('order_status', $params)|| empty($params['student_type']) ||empty($params['ordertype'])){
           $this->error('参数缺失');
        }
        $student_type = $params['student_type'];
        $space_id_list = $params['space_id_list'];
        $order_status = $params['order_status'];
        $page = $params['page'];
        $starttime = strtotime($params['starttime'].'00:00:00');
        $endtime = strtotime($params['endtime'].'23:59:59');
        $space = $this->space->where(['id'=>$space_id_list])->find();
        if($space['space_type'] == 'ai_car'){
            $type_index = 'AI';
        }elseif($space['space_type'] == 'car'){
            $type_index = 'SC';
        }else{
            $row = [];
            $list['order'] = $row;
            $this->success('返回成功',$list);
        }
        $pagenum = 10;
        $numl = $pagenum*($page-1);
        $row = [];
        if($student_type == 'student'){
            if($type_index == 'AI'){
                $where['order.reserve_starttime'] = ['between',[$starttime,$endtime]];
                $where['order.space_id'] = $space_id_list;
                $where['order.ordertype'] = $params['ordertype'];
                if($order_status ){
                    $where['order.order_status'] = $order_status;
                }
                $orderlist = $this->order->with(['admin','student'])->where($where)->limit($numl,$pagenum)->order('reserve_starttime desc')->select();
            }else{
                $where['ordersc.reserve_starttime'] = ['between',[$starttime,$endtime]];
                $where['ordersc.space_id'] = $space_id_list;
                $where['ordersc.ordertype'] = $params['ordertype'];
                if($order_status ){
                    $where['ordersc.order_status'] = $order_status;
                }
                $orderlist = $this->ordersc->with(['admin','student'])->where($where)->limit($numl,$pagenum)->order('reserve_starttime desc')->select();
            }
            foreach($orderlist as $k=>$v){
                $week = $this->common->getWeek($v['reserve_starttime']);
                $row[$k]['week'] = $week;
                $row[$k]['stu_id'] = $v['stu_id'];
                $row[$k]['ordernumber'] = $v['ordernumber'];
                $row[$k]['study_space_name'] = $v['admin']['nickname'];
                $student_source = $this->space->where('id',$v['student']['space_id'])->find();
                $row[$k]['source_space_name'] = $student_source['space_name'];
                $row[$k]['reserve_starttime'] = $v['reserve_starttime'];
                $row[$k]['reserve_endtime'] = $v['reserve_endtime'];
                $row[$k]['order_status'] = $v['order_status'];
                $row[$k]['student_name'] = $v['student']['name'];
                $row[$k]['car_type'] = $v['car_type_text'];
                $row[$k]['subject_type'] = $v['subject_type_text'];
            }

            $list['order'] = $row;
        }else{
            $where['temporaryorder.reserve_starttime'] = ['between',[$starttime,$endtime]];
            $where['temporaryorder.space_id'] = ['in',$space_id_list];
            $where['temporaryorder.order_status'] = $order_status;
            if($order_status ){
                $where['temporaryorder.ordertype'] = $params['ordertype'];
            }
            $orderlist = $this->temporaryorder->with(['admin','intentstudent','coach'])->where($where)->limit($numl,$pagenum)->order('id desc')->select();

            foreach($orderlist as $k=>$v){
                $week = $this->common->getWeek($v['reserve_starttime']);
                $row[$k]['week'] = $week;
                $row[$k]['stu_id'] = $v['stu_id'];
                $row[$k]['ordernumber'] = $v['ordernumber'];
                $row[$k]['study_space_name'] = $v['admin']['nickname'];
                $row[$k]['source_space_name'] = '';
                // $row[$k]['coach_name'] = $v['coach']['name'];
                $row[$k]['reserve_starttime'] = $v['reserve_starttime'];
                $row[$k]['reserve_endtime'] = $v['reserve_endtime'];
                $row[$k]['order_status'] = $v['order_status'];
                $row[$k]['student_name'] = $v['intentstudent']['name'];
                $row[$k]['car_type'] = $v['car_type_text'];
                $row[$k]['subject_type'] = $v['subject_type_text'];
            }
            $list['order'] = $row;
        }
        $this->success('返回成功',$list);
    }

    /**
     * 查看详情
     */
    public function detail(){
        $params = $this->request->post();
        if(empty($params['ordernumber']) || empty($params['student_type']) || empty($params['space_id'])){
            $this->error('参数缺失');
        }
        $ordernumber = $params['ordernumber'];
        $student_type = $params['student_type'];

        $space = $this->space->where(['id'=>$params['space_id']])->find();
        if($space['space_type'] == 'ai_car'){
            $type_index = 'AI';
        }elseif($space['space_type'] == 'car'){
            $type_index = 'SC';
        }else{
            $this->success('返回成功',[]);
        }
        $res = $this->common->get_order_detail($ordernumber,$student_type,$type_index);
        // var_dump($row);exit;
        if(!$res){
            $this->error('返回失败');
        }
        $this->success('返回成功',$res);
    }

    /**
     * 获取当前馆历史记录
     */
    public function get_stu_history(){
        $params = $this->request->post();
        if(empty($params['stu_id']) || empty($params['student_type']) || empty($params['ordernumber'])){
            $this->error('参数缺失');
        }
        $stu_id = $params['stu_id'];
        $student_type = $params['student_type'];
        $ordernumber = $params['ordernumber'];
        $list = $this->common->get_stu_history($stu_id,$student_type,$ordernumber);
        $this->success('返回成功',$list);
    }
    
    /**
     * 完成
     */
    public function finish(){
        $params = $this->request->post();
        if(empty($params['ordernumber']) || empty($params['student_type'])){
            $this->error('参数缺失');
        }
        $ordernumber = $params['ordernumber'];
        $student_type = $params['student_type'];
        $this->common->finish($ordernumber,$student_type);
    }

    /**
     * 取消订单
     */
    public function cancel_refunded(){
        $params = $this->request->post();
        if(empty($params['ordernumber'])){
            $this->error('参数缺失');
        }
        $ordernumber = $params['ordernumber'];
        $update['order_status'] = 'cancel_refunded';
        $student_type = 'student';
        $order = $this->order->where('ordernumber',$ordernumber)->find();
        if(!$order){
            $student_type = 'intent_student';
            $order = $this->temporaryorder->where('ordernumber',$ordernumber)->find();
        }

        if($student_type == 'student'){
            $res = $this->order->where('ordernumber',$ordernumber )->update($update);
        }else{
            $res = $this->temporaryorder->where('ordernumber',$ordernumber)->update($update);
        }
        
        if($res){
            $this->success('取消成功');
        }else{
            $this->error('取消订单异常');
        }
    }
}
