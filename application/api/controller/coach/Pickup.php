<?php

namespace app\api\controller\coach;

use app\common\controller\Api;
use think\Db;
/**
 * 开机流程所需接口
 */
class Pickup extends Api
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
        $this->coach_pickup = new \app\admin\model\Coachpickup;
        $this->pick_up = new \app\admin\model\Pickup;
        
        $this->common =  new \app\api\controller\Common;
    }

    /**
     * 获取接送教员，学员
     */
    public function time_list(){
        $params = $this->request->post();
        // $params['coach_id'] = 'CTN20221123111205522192';
        // $params['request_date'] = '2022-12-07';
        if(empty($params['coach_id']) || empty($params['request_date'])){
           $this->error('参数缺失');
        }
        $coach_id = $params['coach_id'];
        $starttime = strtotime($params['request_date'].'00:00:00');
        $endtime = strtotime($params['request_date'].'23:59:59');

        $where1['order.reserve_starttime'] = ['between',[$starttime,$endtime]];
        $where1['order_status'] = 'paid';
        $where1['pickup.pick_up_coach'] = $coach_id;

        $orderlist1 = $this->order->with(['student','pickup','space'])->where($where1)->order('reserve_starttime asc')->select()->toArray();

        $where2['ordersc.reserve_starttime'] = ['between',[$starttime,$endtime]];
        $where2['pickup.pick_up_coach'] = $coach_id;
        $where2['order_status'] = 'paid';

        $orderlist2 = $this->ordersc->with(['student','pickup','space'])->where($where2)->order('reserve_starttime asc')->select()->toArray();


        $list = [];
        foreach($orderlist1 as $v){
            $starttime = '';
            $arr = [];
            $starttime = date('H:i:s',$v['reserve_starttime']);
            $arr['title'] = $v['student']['name'];
            $arr['place_name'] = $v['pickup']['name'];
            $arr['latitude'] = $v['pickup']['latitude'];
            $arr['longitude'] = $v['pickup']['longitude'];
            $arr['value'] = $v['pickup']['id'];
            $arr['status'] = $v['pickup']['status'];
            
            if(!array_key_exists($starttime,$list)){
                $arr['latitude_space']  = $v['space']['lat'];
                $arr['longitude_space'] = $v['space']['lng'];
                $list[$starttime] = [];
                array_push($list[$starttime],$arr);
            }else{
                array_push($list[$starttime],$arr);
            }
        }
        foreach($orderlist2 as $v){
            $starttime = '';
            $arr = [];
            $starttime = date('H:i:s',$v['reserve_starttime']);
            $arr['title'] = $v['student']['name'];
            $arr['place_name'] = $v['pickup']['name'];
            $arr['latitude'] = $v['pickup']['latitude'];
            $arr['longitude'] = $v['pickup']['longitude'];
            $arr['value'] = (string)$v['pickup']['id'];
            $arr['status'] = $v['pickup']['status'];
            
            if(!array_key_exists($starttime,$list)){
                $arr['latitude_space']  = $v['space']['lat'];
                $arr['longitude_space'] = $v['space']['lng'];
                $list[$starttime] = [];
                array_push($list[$starttime],$arr);
            }else{
                array_push($list[$starttime],$arr);
            }
        }

        $this->success('返回成功',$list);
    }

    public function choose_time()
    {
        $params = $this->request->post();
        // $params['space_id'] = 84;
        // $params['choose_time'] = '2022-12-07 11:00:00';

        if(empty($params['space_id']) || empty($params['choose_time'])){
           $this->error('参数缺失');
        }
        $starttime = strtotime($params['choose_time']);
        $where1['order.reserve_starttime'] = $starttime;
        $where1['order.space_id'] = $params['space_id'];
        $where1['order_status'] = 'paid';
        $where1['pickup_id'] = ['<>','NULL'];
        $where1['pickup.status'] = '0';
        $where1['pickup.pick_up_coach'] = ['<>','NULL'];

        // var_dump($where1);exit;
        $orderlist1 = $this->order->with(['student','pickup'])->where($where1)->order('reserve_starttime asc')->select();

        // var_dump($orderlist1);exit;
        $where2['ordersc.reserve_starttime'] = $starttime;
        $where2['ordersc.space_id'] = $params['space_id'];
        $where2['order_status'] = 'paid';
        $where2['pickup_id'] =  ['<>','NULL'];
        $where2['pickup.status'] = '0';
        $where2['pickup.pick_up_coach'] = ['<>','NULL'];

        $orderlist2 = $this->ordersc->with(['student','pickup'])->where($where2)->order('reserve_starttime asc')->select();

        $list = [];
        $coach = [];
        foreach($orderlist1 as $v){
            $arr = [];
            $choose_coach = [];
            $student['title'] = $v['student']['name'];
            $student['place_name'] = $v['pickup']['name'];
            $student['latitude'] = $v['pickup']['latitude'];
            $student['longitude'] = $v['pickup']['longitude'];
            $student['value'] = $v['pickup']['id'];
            $student['status'] = $v['pickup']['status'];

            if(in_array($v['pickup']['pick_up_coach'],$coach)){
                $choose_coach= $this->coach_pickup->where(['coach_id'=>$v['pickup']['pick_up_coach']])->find();
                $coach_key = array_search($v['pickup']['pick_up_coach'],$coach);
                array_push($list[$coach_key]['students'],$student);
            }else{
                $coach_list= $this->coach_pickup->where(['coach_id'=>$v['pickup']['pick_up_coach']])->find();
                $choose_coach['title'] = $coach_list['name'];
                $choose_coach['value'] = $coach_list['coach_id'];
                array_push($coach,$coach_list['coach_id']);

                $coach_key = array_search($v['pickup']['pick_up_coach'],$coach);
                array_push($list,$arr);
                $list[$coach_key]['choose_coach'] = [];
                $list[$coach_key]['students'] = [];
                array_push($list[$coach_key]['choose_coach'],$choose_coach); 
                array_push($list[$coach_key]['students'],$student);

            }
        }
        foreach($orderlist2 as $v){
            $arr = [];
            $choose_coach = [];
            $student['title'] = $v['student']['name'];
            $student['place_name'] = $v['pickup']['name'];
            $student['latitude'] = $v['pickup']['latitude'];
            $student['longitude'] = $v['pickup']['longitude'];
            $student['value'] = (string)$v['pickup']['id'];
            $student['status'] = $v['pickup']['status'];
            if(in_array($v['pickup']['pick_up_coach'],$coach)){
                $choose_coach= $this->coach_pickup->where(['coach_id'=>$v['pickup']['pick_up_coach']])->find();
                $coach_key = array_search($v['pickup']['pick_up_coach'],$coach);
                array_push($list[$coach_key]['students'],$student);
            }else{
                $coach_list= $this->coach_pickup->where(['coach_id'=>$v['pickup']['pick_up_coach']])->find();
                $choose_coach['title'] = $coach_list['name'];
                $choose_coach['value'] = $coach_list['coach_id'];
                array_push($coach,$coach_list['coach_id']);

                $coach_key = array_search($v['pickup']['pick_up_coach'],$coach);
                array_push($list,$arr);
                $list[$coach_key]['choose_coach'] = [];
                $list[$coach_key]['students'] = [];
                array_push($list[$coach_key]['choose_coach'],$choose_coach); 
                array_push($list[$coach_key]['students'],$student);

            }
        }
        $this->success('返回成功',$list);
    }

    public function shangche()
    {
        $params = $this->request->post();
        // $params['pick_up_student'] = [4,5];
        // $params['pick_up_coach'] = 'CTN20221123111327591820';
        // $params['status'] = 'del';
        if(empty($params['id']) ){
           $this->error('参数缺失');
        }
        $where['id'] = $params['id'];
        $update['status'] = '1';
        $student = $this->pick_up->where($where)->find();
        if($student['status'] == '1'){
            $this->success('当前学员已接送成功');
        }
        $res = $this->pick_up->where($where)->update($update);
        // $res = 1;
        if($res){
            $this->success('返回成功');
        }
        $this->error('当前学员数据异常,请联系管理员解决');
    }
    /**
     * 提交接送信息
     */
    public function submit_pickup()
    {
        $params = $this->request->post();
        // $params['pick_up_student'] = [4,5];
        // $params['pick_up_coach'] = 'CTN20221123111327591820';
        // $params['status'] = 'del';
        if(empty($params['pick_up_student']) || empty($params['status']) || !array_key_exists('pick_up_coach',$params)){
           $this->error('参数缺失');
        }

        if($params['status'] == 'add'){
            $update['pick_up_coach'] = $params['pick_up_coach'];
            $where['id'] = ['in',$params['pick_up_student']];
            $res = $this->pick_up->where($where)->update($update);
        }elseif($params['status'] == 'del'){
            $update['pick_up_coach'] = null;
            $where['id'] = ['in',$params['pick_up_student']];
            $res = $this->pick_up->where($where)->update($update);
        }
        // $res = 1;
        if($res){
            $this->success('返回成功');
        }
        $this->error('返回异常');


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
        $student_type = $params['student_type'];
        $space_id_list = $params['space_id_list'];
        $order_status = $params['order_status'];
        $page = $params['page'];
        $starttime = strtotime($params['starttime'].'00:00:00');
        $endtime = strtotime($params['endtime'].'23:59:59');

        $pagenum = 10;
        $numl = $pagenum*($page-1);
        $row = [];
        if($student_type == 'student'){
            if(array_key_exists('type_index',$params)){
                if($params['type_index'] == 0){
                    $where['order.reserve_starttime'] = ['between',[$starttime,$endtime]];
                    $where['order.space_id'] = ['in',$space_id_list];
                    $where['order.ordertype'] = $params['ordertype'];
                    if($order_status ){
                        $where['order.order_status'] = $order_status;
                    }
                    $orderlist = $this->order->with(['admin','student'])->where($where)->limit($numl,$pagenum)->order('reserve_starttime desc')->select();
                }else{
                    $where['ordersc.reserve_starttime'] = ['between',[$starttime,$endtime]];
                    $where['ordersc.space_id'] = ['in',$space_id_list];
                    $where['ordersc.ordertype'] = $params['ordertype'];
                    if($order_status ){
                        $where['ordersc.order_status'] = $order_status;
                    }
                    $orderlist = $this->ordersc->with(['admin','student'])->where($where)->limit($numl,$pagenum)->order('reserve_starttime desc')->select();
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
        if(empty($params['ordernumber']) || empty($params['student_type'])){
            $this->error('参数缺失');
        }
        $ordernumber = $params['ordernumber'];
        $student_type = $params['student_type'];
        $type_index = 0;

        if(array_key_exists('type_index',$params)){
            if(!$params['type_index'] == 0){
                $type_index = 1;
            }
        }
        $res = $this->common->get_order_detail($ordernumber,$student_type,$type_index);
        // var_dump($row);exit;
        if(!$res){
            $this->error('返回失败');
        }
        $this->success('返回成功',$res);
    }

    


}
