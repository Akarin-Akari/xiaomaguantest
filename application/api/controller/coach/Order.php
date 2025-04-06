<?php

namespace app\api\controller\coach;

use app\common\controller\Api;

/**
 * 订单管理
 */
class Order extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    protected $coach = null;
    protected $temporaryorder = null;
    protected $order = null;
    protected $ordersc = null;
    protected $space = null;
    protected $common = null;
    
    public function _initialize()
    {
        parent ::_initialize();
        $this->coach = new \app\admin\model\Coach;
        $this->temporaryorder = new \app\admin\model\Temporaryorder;
        $this->order = new \app\admin\model\Order;
        $this->ordersc = new \app\admin\model\Ordersc;
        $this->space = new \app\admin\model\Space;
        $this->common =  new \app\api\controller\Common;
    }

    public function order_quiretest(){
        $params = $this->request->post();
        $params['coach_id'] = 'CTN20220426172721215122';
        $params['starttime'] = '2023-07-04';
        $params['endtime'] = '2023-07-05    ';
        $params['page'] = 1;
        $params['order_status'] = '';
        $params['ordertype'] = 1;
        $params['student_type'] = 'student';
        $params['type_index'] = 'AI';
        if(empty($params['coach_id']) || empty($params['page']) || empty($params['starttime']) ||
        empty($params['endtime']) || empty($params['student_type']) ||empty($params['ordertype'])){
           $this->error('参数缺失');
       }
       $student_type = $params['student_type'];
       $coach_id = $params['coach_id'];
       $order_status = $params['order_status'];
       $page = $params['page'];
       $starttime = strtotime($params['starttime'].'00:00:00');
       $endtime = strtotime($params['endtime'].'23:59:59');

       $pagenum = 10;
       $numl = $pagenum*($page-1);
       $row = [];
       if($student_type == 'student'){
           
            if(array_key_exists('type_index',$params)){
               if($params['type_index'] == 'AI'){
                   $where['order.reserve_starttime'] = ['between',[$starttime,$endtime]];
                   if($params['ordertype'] == 1){
                       $where['order.ordertype'] = ['in',[1,3]];
                   }else{
                       $where['order.ordertype'] = 2;
                   }
                   $where['order.coach_id'] = $coach_id;
                   if($order_status){
                       $where['order.order_status'] = $order_status;
                   }
                   // var_dump($numl,$pagenum);
                   $orderlist = $this->order->with(['admin','student','coach'])->where($where)->limit($numl,$pagenum)->order('reserve_starttime desc')->select();
               }elseif($params['type_index'] == 'SC'){
                   $where['ordersc.reserve_starttime'] = ['between',[$starttime,$endtime]];
                   if($params['ordertype'] == 1){
                       $where['ordersc.ordertype'] = ['in',[1,3]];
                   }else{
                       $where['ordersc.ordertype'] = 2;
                   }
                   $where['ordersc.coach_id'] = $coach_id;
                   if($order_status){
                       $where['ordersc.order_status'] = $order_status;
                   }
                   $orderlist = $this->ordersc->with(['admin','student','coachsc'])->where($where)->limit($numl,$pagenum)->order('reserve_starttime desc')->select();

               }else{
                   $this->success('返回成功',[]);
               }
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

               if(array_key_exists('type_index',$params)){
                   if($params['type_index'] == 'AI'){
                       $row[$k]['coach_name'] = $v['coach']['name'];
                   }elseif($params['type_index'] == 'SC'){
                       $row[$k]['coach_name'] = $v['coachsc']['name'];
                   }else{
                       $this->success('返回成功',[]);
                   }
               }
               $row[$k]['student_name'] = $v['student']['name'];
               $row[$k]['car_type'] = $v['car_type_text'];
               $row[$k]['subject_type'] = $v['subject_type_text'];
           }
           $list['order'] = $row;
       }else{
           $where['temporaryorder.reserve_starttime'] = ['between',[$starttime,$endtime]];
           $where['temporaryorder.coach_id'] = $coach_id;
           $where['temporaryorder.ordertype'] = $params['ordertype'];
           if($order_status){
               $where['temporaryorder.order_status'] = $order_status;
           }
           $orderlist = $this->temporaryorder->with(['admin','intentstudent','coach'])->where($where)->limit($numl,$pagenum)->order('reserve_starttime desc')->select();
           foreach($orderlist as $k=>$v){
               $week = $this->common->getWeek($v['reserve_starttime']);
               $row[$k]['week'] = $week;
               $row[$k]['stu_id'] = $v['stu_id'];
               $row[$k]['ordernumber'] = $v['ordernumber'];
               $row[$k]['study_space_name'] = $v['admin']['nickname'];
               $row[$k]['source_space_name'] = '';
               $row[$k]['coach_name'] = $v['coach']['name'];
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

        if(empty($params['coach_id']) || empty($params['page']) || empty($params['starttime']) ||
         empty($params['endtime']) || empty($params['student_type']) ||empty($params['ordertype'])){
            $this->error('参数缺失');
        }
        $student_type = $params['student_type'];
        $coach_id = $params['coach_id'];
        $order_status = $params['order_status'];
        $page = $params['page'];
        $starttime = strtotime($params['starttime'].'00:00:00');
        $endtime = strtotime($params['endtime'].'23:59:59');

        $pagenum = 10;
        $numl = $pagenum*($page-1);
        $row = [];
        if($student_type == 'student'){
            
             if(array_key_exists('type_index',$params)){
                if($params['type_index'] == 'AI'){
                    $where['order.reserve_starttime'] = ['between',[$starttime,$endtime]];
                    if($params['ordertype'] == 1){
                        $where['order.ordertype'] = ['in',[1,3]];
                    }else{
                        $where['order.ordertype'] = 2;
                    }
                    $where['order.coach_id'] = $coach_id;
                    if($order_status){
                        $where['order.order_status'] = $order_status;
                    }
                    // var_dump($numl,$pagenum);
                    $orderlist = $this->order->with(['admin','student','coach'])->where($where)->limit($numl,$pagenum)->order('reserve_starttime desc')->select();
                }elseif($params['type_index'] == 'SC'){
                    $where['ordersc.reserve_starttime'] = ['between',[$starttime,$endtime]];
                    if($params['ordertype'] == 1){
                        $where['ordersc.ordertype'] = ['in',[1,3]];
                    }else{
                        $where['ordersc.ordertype'] = 2;
                    }
                    $where['ordersc.coach_id'] = $coach_id;
                    if($order_status){
                        $where['ordersc.order_status'] = $order_status;
                    }
                    $orderlist = $this->ordersc->with(['admin','student','coachsc'])->where($where)->limit($numl,$pagenum)->order('reserve_starttime desc')->select();

                }else{
                    $this->success('返回成功',[]);
                }
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

                if(array_key_exists('type_index',$params)){
                    if($params['type_index'] == 'AI'){
                        $row[$k]['coach_name'] = $v['coach']['name'];
                    }elseif($params['type_index'] == 'SC'){
                        $row[$k]['coach_name'] = $v['coachsc']['name'];
                    }else{
                        $this->success('返回成功',[]);
                    }
                }
                $row[$k]['student_name'] = $v['student']['name'];
                $row[$k]['car_type'] = $v['car_type_text'];
                $row[$k]['subject_type'] = $v['subject_type_text'];
            }
            $list['order'] = $row;
        }else{
            $where['temporaryorder.reserve_starttime'] = ['between',[$starttime,$endtime]];
            $where['temporaryorder.coach_id'] = $coach_id;
            $where['temporaryorder.ordertype'] = $params['ordertype'];
            if($order_status){
                $where['temporaryorder.order_status'] = $order_status;
            }
            $orderlist = $this->temporaryorder->with(['admin','intentstudent','coach'])->where($where)->limit($numl,$pagenum)->order('reserve_starttime desc')->select();
            foreach($orderlist as $k=>$v){
                $week = $this->common->getWeek($v['reserve_starttime']);
                $row[$k]['week'] = $week;
                $row[$k]['stu_id'] = $v['stu_id'];
                $row[$k]['ordernumber'] = $v['ordernumber'];
                $row[$k]['study_space_name'] = $v['admin']['nickname'];
                $row[$k]['source_space_name'] = '';
                $row[$k]['coach_name'] = $v['coach']['name'];
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
        if(empty($params['ordernumber']) || empty($params['student_type']) ){
            $this->error('参数缺失');
        }
        $ordernumber = $params['ordernumber'];
        $student_type = $params['student_type'];
        $type_index = 'AI';

        if(array_key_exists('type_index',$params)){
            $type_index = $params['type_index'];
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
    public function stu_history(){
        $params = $this->request->post();
        // $params['stu_id'] = 'CSN20210930144134517999';
        // $params['space_id'] = '12';
        // $params['student_type'] = 'student';
        // $params['ordernumber'] = 'CON20220825155415181306';
        // $params['type_index'] = 0;
        if(empty($params['stu_id']) || empty($params['space_id']) || empty($params['student_type']) || empty($params['ordernumber'])){
            $this->error('参数缺失');
        }
        $stu_id = $params['stu_id'];
        $space_id = $params['space_id'];
        $student_type = $params['student_type'];
        $ordernumber = $params['ordernumber'];
        $type_index = 0;

        if(array_key_exists('type_index',$params)){
            if(!$params['type_index'] == 0){
                $type_index = 1;
            }
        }
        // $list = $this->common->get_stu_history($stu_id,$student_type,$ordernumber);
        $list = $this->common->get_stu_history($stu_id,$space_id,$student_type,$ordernumber,$type_index);
        $this->success('返回成功',$list);
    }
    

    /**
     * 当前学员所有馆的一个历史记录
     */
    public function get_stu_historytest($stu_id,$space_id,$student_type,$ordernumber,$type_index){
        $where['stu_id'] = $stu_id;
        $where['order_status'] = 'finished';
        $where['ordernumber'] = ['neq',$ordernumber];
        $where['order.space_id'] = $space_id;

        if($student_type=='student'){
            if($type_index == 0){
                $order = $this->order->with(['space'])->where($where)->select();
            }else{
                $where['ordersc.space_id'] = $space_id;
                $order = $this->ordersc->with(['space'])->where($where)->select();
            }
        }else{
            $order = $this->temporaryorder->with(['space'])->where($where)->select();
        }
        $list = [];
        if($order){
            foreach($order as $k=>$v){
                $list[$k]['subject_type'] = $v['subject_type_text'];
                $list[$k]['space_name'] = $v['space']['space_name'];
                $list[$k]['day'] = date('Y-m-d',$v['reserve_starttime']);
                $list[$k]['time'] = date('H:i',$v['starttime']).'-'.date('H:i',$v['endtime']);
            }
        }
        return $list;
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


}
