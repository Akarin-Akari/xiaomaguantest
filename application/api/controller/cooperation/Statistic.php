<?php

namespace app\api\controller\cooperation;

use app\common\controller\Api;
use think\cache;

/**
 * 开机流程所需接口
 */
class Statistic extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent ::_initialize();
        $this->order = new \app\admin\model\Order;
        $this->ordersc = new \app\admin\model\Ordersc;
        $this->temporaryorder = new \app\admin\model\Temporaryorder;
        $this->machinecar = new \app\admin\model\Machinecar;
        $this->car = new \app\admin\model\Car;
        $this->coach = new \app\admin\model\Coach;
        $this->coachsc = new \app\admin\model\Coachsc;
        $this->space = new \app\admin\model\Space;
        
        $this->common = new \app\api\controller\Common;
    }

    /**
     * 统计数据
     */
    public function  getstatistictest(){
        $params = $this->request->post();
        $params['space_id_list'] = '84';
        $params['starttime'] = '2022-11-10';
        $params['endtime'] = '2022-11-12';
        if(empty($params['space_id_list']) || empty($params['starttime']) || empty($params['endtime'])){
            $this->error('参数缺失');
        }
        $space_id_list = $params['space_id_list'];
        $starttime = $params['starttime'];
        $endtime = $params['endtime'];
        $starttime = strtotime($starttime);
        $endtime = strtotime($endtime.'23:59:59');
        $date = round(($endtime - $starttime)/3600/24);
        $where['id'] = $space_id_list;
        $space = $this->space->where($where)->find();
        
        $data['C1_reserve'] = $this->get_total($space_id_list,'cartype1',$starttime,$endtime,1,$space);
        $data['C2_reserve'] = $this->get_total($space_id_list,'cartype2',$starttime,$endtime,1,$space);
        $data['C1_spot'] = $this->get_total($space_id_list,'cartype1',$starttime,$endtime,2,$space);
        $data['C2_spot'] = $this->get_total($space_id_list,'cartype2',$starttime,$endtime,2,$space);
        $data['spot_total'] = $data['C1_spot'] + $data['C2_spot'];
        $data['C1_finished'] = $this->get_finished($space_id_list,'cartype1',$starttime,$endtime,$space);
        $data['C2_finished'] =  $this->get_finished($space_id_list,'cartype2',$starttime,$endtime,$space);
        $data['reserve_total'] = $data['C1_reserve'] + $data['C2_reserve'];
        $data['finished_total'] = $data['C1_finished']+ $data['C2_finished'];
        $space_total =  $this->get_space_total($space_id_list,$date,$space);
        $data['total'] = $space_total['total'];
        $data['machine_car_num'] = $space_total['machine_car_num'];
        $data['coach_num'] =  $space_total['coach_num'];
        if($data){
            $this->success('返回成功',$data);
        }
    }

    /**
     * 统计数据
     */
    public function  getstatistic(){
        $params = $this->request->post();
        if(empty($params['space_id_list']) || empty($params['starttime']) || empty($params['endtime'])){
            $this->error('参数缺失');
        }
        $space_id_list = $params['space_id_list'];
        $starttime = $params['starttime'];
        $endtime = $params['endtime'];
        $starttime = strtotime($starttime);
        $endtime = strtotime($endtime.'23:59:59');
        $date = round(($endtime - $starttime)/3600/24);
        $where['id'] = $space_id_list;
        $space = $this->space->where($where)->find();
        $data['C1_reserve'] = $this->get_total($space_id_list,'cartype1',$starttime,$endtime,1,$space);
        $data['C2_reserve'] = $this->get_total($space_id_list,'cartype2',$starttime,$endtime,1,$space);
        $data['C1_spot'] = $this->get_total($space_id_list,'cartype1',$starttime,$endtime,2,$space);
        $data['C2_spot'] = $this->get_total($space_id_list,'cartype2',$starttime,$endtime,2,$space);
        $data['spot_total'] = $data['C1_spot'] + $data['C2_spot'];
        $data['C1_finished'] = $this->get_finished($space_id_list,'cartype1',$starttime,$endtime,$space);
        $data['C2_finished'] =  $this->get_finished($space_id_list,'cartype2',$starttime,$endtime,$space);
        $data['reserve_total'] = $data['C1_reserve'] + $data['C2_reserve'];
        $data['finished_total'] = $data['C1_finished']+ $data['C2_finished'];
        $space_total =  $this->get_space_total($space_id_list,$date,$space);
        $data['total'] = $space_total['total'];
        $data['machine_car_num'] = $space_total['machine_car_num'];
        $data['coach_num'] =  $space_total['coach_num'];
        if($data){
            $this->success('返回成功',$data);
        }
    }

    /**
     * 获取订单详情信息
     */
    public function statistic_detail(){
        $params = $this->request->post();
        if(empty($params['starttime']) || empty($params['endtime']) ||empty($params['space_id_list'])){
            $this->error('参数缺失');
        }
        // Cache::set('statistic_detail',$params,10*60);
        $space_id_list = $params['space_id_list'];
        $starttime = $params['starttime'];
        $endtime = $params['endtime'];
        $starttime = strtotime($starttime);
        $endtime = strtotime($endtime.'23:59:59');

        $res = [];
        $aa = 0;
        for($stime=$starttime;$stime<=$endtime;$stime +=3600*24){
            $etime = $stime + 3600*24;
            $where['starttime'] = ['between',[$stime,$etime]];
            $where['order_status'] = 'finished';
            $where['space_id'] = $space_id_list;
            $first_time = $this->order->where($where)->order('starttime asc')->find()['starttime'];
            $last_time = $this->order->where($where)->order('starttime desc')->find()['starttime'];
            
            if(!$first_time){
                continue;
            }
            $date_first_time = strtotime(date('Y-m-d H:00:00',$first_time));
            $date_last_time = strtotime(date('Y-m-d H:00',$last_time));
            // var_dump($starttime,$date_last_time);
            for($s2time=$date_first_time;$s2time<=$date_last_time;$s2time +=3600){
                $s2end = $s2time +3600 -1;
                $s3end = $s2time +3600;
                $where_order['starttime'] = ['between',[$s2time,$s2end]];
                $where_order['order_status'] = 'finished';
                $where_order['order.space_id'] = $space_id_list;
                $total = $this->order->with(['student','space','coach'])->where($where_order)->count()*2;
                $list = $this->order->with(['student','space','coach'])->where($where_order)->select();
                if(!$list){
                    continue;
                }
                if(!isset($res[date('H:00',$s2time).'-'.date('H:00',$s3end)]['info'] )){
                    $res[date('H:00',$s2time).'-'.date('H:00',$s3end)]['total'] = 0;
                }
                if(!isset($res[date('H:00',$s2time).'-'.date('H:00',$s3end)]['info'] )){
                    $res[date('H:00',$s2time).'-'.date('H:00',$s3end)]['info'] = [];
                }
                foreach($list as $v){
                    $msg['spacename'] = $v['space']['space_name'];
                    $msg['name'] = $v['student']['name'];
                    $msg['car_type'] = $v['car_type_text'];
                    $msg['subject_type'] = $v['subject_type_text'];
                    $msg['coachname'] = $v['coach']['name'];
                    array_push($res[date('H:00',$s2time).'-'.date('H:00',$s3end)]['info'],$msg);
                    unset($msg);
                }
                $res[date('H:00',$s2time).'-'.date('H:00',$s3end)]['total'] += $total;
                $aa +=$total;
            }
        }
        $this->success('返回成功',$res);
    }


    //预约统计/现场下单统计
    public function get_total($space_id,$cartype,$starttime,$endtime,$ordertype,$space){
        $where['space_id'] = $space_id;
        $where['car_type'] = $cartype;
        $where['ordertype'] = $ordertype;
        $where['reserve_starttime'] = ['between',[$starttime,$endtime]];
        $where['order_status'] = ['neq','cancel_refunded'];
        if($ordertype == 2){
            $where['order_status'] = 'finished';
        }
        if($space['space_type'] == 'ai_car'){
            // var_dump($where);exit;

            $count1 = $this->order->where($where)->count();
            $count2 = $this->temporaryorder->where($where)->count();
            $count = $count1+$count2;
        }else{
            $count = $this->ordersc->where($where)->count();
        }
        // var_dump($where_reserve);exit;
        
        return $count*$space['order_length'];
    }

    //已完成订单
    public function get_finished($space_id,$cartype,$starttime,$endtime,$space){
        $where['space_id'] = $space_id;
        $where['car_type'] = $cartype;
        $where['reserve_starttime'] = ['between',[$starttime,$endtime]];
        $where['order_status'] = 'finished';
        if($space['space_type'] == 'ai_car'){
            $count1 = $this->order->where($where)->count();
            $count2 = $this->temporaryorder->where($where)->count();
            $count = $count1+$count2;
        }else{
            $count = $this->ordersc->where($where)->count();
        }
        
        return $count*$space['order_length'];
    }

    public function get_space_total($space_id,$date,$space){
        $where['space_id'] = $space_id;
        $where['state'] = 1;
        
        $where_coach['teach_state'] = 'yes';
        $where_coach['space_id'] =  $space_id;

        if($space['space_type'] == 'ai_car'){
            $count = $this->machinecar->where($where)->count();
            $total = $count*14*$date;

            $data['total'] = $total;
            $data['machine_car_num'] = $count;
            $data['coach_num'] = $this->coach->where($where_coach)->count();
        }else{
            $count = $this->car->where($where)->count();
            $total = $count*14*$date;

            $data['total'] = $total;
            $data['machine_car_num'] = $count;
            $data['coach_num'] = $this->coachsc->where($where_coach)->count();
        }

        
        return $data;
    }


    
}
