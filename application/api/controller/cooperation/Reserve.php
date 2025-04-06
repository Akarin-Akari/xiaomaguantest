<?php


namespace app\api\controller\cooperation;

use app\common\controller\Api;
use think\Cache;

/**
 * 开机流程所需接口
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
    public function test()
    {
        $res = Cache::get('reserve_quire_test');
        var_dump($res);
    }

    /**
     * 查询某馆预约订单
     */
    public function reserve_quiretest()
    {
        $params = $this->request->post();
        $params['space_id_list'] = '84';
        $params['request_date'] = '2022-11-11';
        $params['type_index'] = '1';
        if(empty($params['space_id_list']) || empty($params['request_date'])){
            $this->error('参数缺失');
        }
        if(!array_key_exists('type_index',$params)){
            $type_index = 0;
        }else{
            $type_index = $params['type_index'];
        }
        $space_id_list = $params['space_id_list'];
        $request_date = $params['request_date'];
        if($params['request_date'] < date('Y-m-d',time()) ){
            $this->error('无法查看历史预约情况');
        }
        $request_date = $params['request_date'];
        $reserve_starttime = strtotime($request_date);
        $reserve_endtime = strtotime($request_date.' 23:59:59');
        $where['reserve_starttime'] = ['between',[$reserve_starttime,$reserve_endtime]];
        $where['ordertype'] = ['in',[1,3]];
        $where['order_status'] = ['in',['paid','accept_unexecut','executing','finished']];
        if( $type_index == 0){
            $where['order.space_id'] = ['in',$space_id_list];
            $list = $this->order->with(['student','space','machinecar','admin','coach'])->where($where)->order('reserve_starttime desc')->select();
        }else{
            $where['ordersc.space_id'] = ['in',$space_id_list];
            $list = $this->ordersc->with(['student','space','car','admin','coachsc'])->where($where)->order('reserve_starttime desc')->select();
        }
        $arr = $this->get_reserve_order($list,$type_index);//获取上下午订单信息以及C1，C2统计
        $this->success('返回成功',$arr);
    }

    /**
     * 查询某馆预约订单
     */
    public function reserve_quire()
    {
        $params = $this->request->post();
        if(!array_key_exists('type_index',$params)){
            $type_index = 0;
        }else{
            $type_index = $params['type_index'];
        }
        $space_id_list = $params['space_id_list'];
        $request_date = $params['request_date'];
        if($params['request_date'] < date('Y-m-d',time()) ){
            $this->error('无法查看历史预约情况');
        }
        $request_date = $params['request_date'];
        $reserve_starttime = strtotime($request_date);
        $reserve_endtime = strtotime($request_date.' 23:59:59');
        $where['reserve_starttime'] = ['between',[$reserve_starttime,$reserve_endtime]];
        $where['ordertype'] = ['in',[1,3]];
        $where['order_status'] = ['in',['paid','accept_unexecut','executing','finished']];
        if( $type_index == 0){
            $where['order.space_id'] = ['in',$space_id_list];
            $list = $this->order->with(['student','space','machinecar','admin','coach'])->where($where)->order('reserve_starttime desc')->select();
        }else{
            $where['ordersc.space_id'] = ['in',$space_id_list];
            $list = $this->ordersc->with(['student','space','car','admin','coachsc'])->where($where)->order('reserve_starttime desc')->select();
        }
        $arr = $this->get_reserve_order($list,$type_index);//获取上下午订单信息以及C1，C2统计
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
            if($type_index == 0){
                $reserve_order[$k]['machine_code'] = $v['machinecar']['machine_code'];
                $reserve_order[$k]['coach'] = $v['coach']['name'];
            }else{
                $reserve_order[$k]['coach'] = $v['coachsc']['name'];
                $reserve_order[$k]['machine_code'] = $v['car']['machine_code'];
            }
            $reserve_order[$k]['name'] = $v['student']['name'];
            $reserve_order[$k]['ordernumber'] = $v['ordernumber'];
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

}