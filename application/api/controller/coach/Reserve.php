<?php

namespace app\api\controller\coach;

use app\common\controller\Api;

/**
 * 预约管理
 */
class Reserve extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent ::_initialize();
        $this->order = new \app\admin\model\Order;
        $this->ordersc = new \app\admin\model\Ordersc;
        $this->common =  new \app\api\controller\Common;
    }

    public function reserve_quiretest()
    {
        $params = $this->request->post();
        
        // $params['coach_id'] = 'CTN20220718112824186961';
        // $params['type_index'] = 'SC';
        // $params['request_date'] = '2022-11-12';

        if(empty($params['request_date'])){
            $this->error('参数缺失');
        }
        if(!array_key_exists('type_index',$params)){
            $type_index = 'AI';
        }else{
            $type_index = $params['type_index'];
        }
        $coach_id = $params['coach_id'];
        $request_date = $params['request_date'];
        if($params['request_date'] < date('Y-m-d',time()) ){
            $this->error('无法查看历史预约情况');
        }
        $request_date = $params['request_date'];
        $reserve_starttime = strtotime($request_date);
        $reserve_endtime = strtotime($request_date.' 23:59:59');
        $coach_id = $params['coach_id'];
        $where['reserve_starttime'] = ['between',[$reserve_starttime,$reserve_endtime]];
        $where['ordertype'] = ['in',[1,3]];
        $where['order_status'] = ['in',['paid','accept_unexecut','executing','finished']];
        if($type_index == 'AI'){
            $where['order.coach_id'] = $coach_id;
            $list = $this->order->with(['student','space','machinecar','admin'])->where($where)->order('reserve_starttime desc')->select();
        }elseif($type_index == 'SC'){
            $where['ordersc.coach_id'] = $coach_id;
            $list = $this->ordersc->with(['student','space','car','admin'])->where($where)->order('reserve_starttime desc')->select();
        }else{
            $list = [];
            $this->success('返回成功',$list);
        }
        $arr = $this->get_reserve_order($list,$type_index);//获取上下午订单信息以及C1，C2统计
        $statistic['week'] = $this->get_week_total($coach_id,$type_index);
        $statistic['month'] = $this->get_month_total($coach_id,$type_index);
        $statistic['total'] = $this->get_total($coach_id,$type_index);
        $arr['statistic'] = $statistic;
        $this->success('返回成功',$arr);
    }
    /**
     * 查询某教员预约订单
     */
    public function reserve_quire()
    {
        $params = $this->request->post();
        
        // $params['coach_id'] = 'CTN20231214141715724750';
        // $params['request_date'] = '2023-12-28';
        // $params['type_index'] = 'SC';
        if(empty($params['request_date'])){
            $this->error('参数缺失');
        }
        if(!array_key_exists('type_index',$params)){
            $type_index = 0;
        }else{
            $type_index = $params['type_index'];
        }
        $coach_id = $params['coach_id'];
        $request_date = $params['request_date'];
        if($params['request_date'] < date('Y-m-d',time()) ){
            $this->error('无法查看历史预约情况');
        }
        $request_date = $params['request_date'];
        $reserve_starttime = strtotime($request_date);
        $reserve_endtime = strtotime($request_date.' 23:59:59');
        $coach_id = $params['coach_id'];
        $where['reserve_starttime'] = ['between',[$reserve_starttime,$reserve_endtime]];
        $where['ordertype'] = ['in',[1,3]];
        $where['order_status'] = ['in',['paid','accept_unexecut','executing','finished']];
        if($type_index == 'AI'){
            $where['order.coach_id'] = $coach_id;
            $list = $this->order->with(['student','space','machinecar','admin'])->where($where)->order('reserve_starttime desc')->select();
        }else{
            $where['ordersc.coach_id'] = $coach_id;
            $list = $this->ordersc->with(['student','space','car','admin'])->where($where)->order('reserve_starttime desc')->select();
        }
        $arr = $this->get_reserve_order($list,$type_index);//获取上下午订单信息以及C1，C2统计
        $statistic['week'] = $this->get_week_total($coach_id,$type_index);
        $statistic['month'] = $this->get_month_total($coach_id,$type_index);
        $statistic['total'] = $this->get_total($coach_id,$type_index);
        $arr['statistic'] = $statistic;
        $this->success('返回成功',$arr);
    }

   

    public function get_reserve_order($list,$type_index){
        $reserve_order = [];
        $data['morning_C1'] = 0;
        $data['morning_C2'] = 0;
        $data['afternoon_C1'] = 0;
        $data['afternoon_C2'] = 0;
        $data['total'] = 0;
        foreach($list as $k=>$v){
            // var_dump($v->toArray());exit;
            $data['total'] +=1;
            $reserve_order[$k]['name'] = $v['student']['name'];
            if($type_index == 'AI'){
                $reserve_order[$k]['machine_code'] = $v['machinecar']['machine_code'];
            }else{
                $reserve_order[$k]['machine_code'] = $v['car']['machine_code'];
            }
            $reserve_order[$k]['reserve_starttime'] = $v['reserve_starttime'];
            $reserve_order[$k]['reserve_endtime'] = $v['reserve_endtime'];
            $reserve_order[$k]['order_status'] = $v['order_status'];
            $reserve_order[$k]['car_type'] = $v['car_type_text'];
            $starttime = $v['reserve_starttime'];
            $cut_time = strtotime(date('Y-m-d 12:00:00',$starttime));
            if($starttime < $cut_time && $reserve_order[$k]['car_type'] =='C1'){
                $data['morning_C1'] +=1;
            }elseif($starttime <$cut_time && $reserve_order[$k]['car_type'] =='C2'){
                $data['morning_C2'] +=1;
            }elseif($starttime >=$cut_time && $reserve_order[$k]['car_type'] =='C1'){
                $data['afternoon_C1'] +=1;
            }elseif($starttime >=$cut_time && $reserve_order[$k]['car_type'] =='C2'){
                $data['afternoon_C2'] +=1;
            }
            unset($cut_time,$starttime);
        }
        $arr['data'] = $data;
        $arr['reserve_order'] = $reserve_order;
        return $arr;
    }



    public function get_week_total($coach_id,$type_index){
        $week = strtotime($this->common->get_time()['week']);
        $where['coach_id'] = $coach_id;
        $where['order_status'] = 'finished';
        $where['starttime'] = ['>',$week];
        if($type_index == 0){
            $total = $this->order->where($where)->count()*2;
        }else{
            $total = $this->ordersc->where($where)->count()*2;
        }
        return $total.'小时';
    }

    //获取当月
    public function get_month_total($coach_id,$type_index){
        $month = $this->common->get_time()['month'];
        $where['coach_id'] = $coach_id;
        $where['order_status'] = 'finished';
        $where['starttime'] = ['>',$month];
        if($type_index == 0){
            $total = $this->order->where($where)->count()*2;
        }else{
            $total = $this->ordersc->where($where)->count()*2;
        }
        return $total.'小时';
    }
    
    public function get_total($coach_id,$type_index){
        $where['coach_id'] = $coach_id;
        $where['order_status'] = 'finished';
        if($type_index == 0){
            $total = $this->order->where($where)->count()*2;
        }else{
            $total = $this->ordersc->where($where)->count()*2;
        }
        return $total.'小时';
    }

}