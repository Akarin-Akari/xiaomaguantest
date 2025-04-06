<?php


namespace app\api\controller\coach;

use app\common\controller\Api;

/**
 * 首页接口
 */
class Myspace extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent ::_initialize();
        $this->temporaryorder = new \app\admin\model\Temporaryorder;
        $this->order = new \app\admin\model\Order;

        $this->common = new \app\api\controller\Common;
    }

    /**
     * 查询扣款订单
     */
    public function order_quire()
    {
        $params = $this->request->post();
        if(empty($params['coach_id']) || empty($params['page']) || empty($params['starttime']) || empty($params['endtime']) || empty($params['student_type'])){
            $this->error('参数缺失');
        }
        $starttime = strtotime($params['starttime'].'00:00:00');
        $endtime = strtotime($params['endtime'].'23:59:59');
        $page = $params['page'];
        $coach_id = $params['coach_id'];
        $student_type = $params['student_type'];

        $where['starttime'] = ['between',[$starttime,$endtime]];
        $pagenum = 10;
        $numl = $pagenum*($page-1);
        $where['coach_id'] = $coach_id;
        if($params['order_status']){
            $where['order_status'] = $params['order_status'];
        }else{
            $where['order_status'] = ['in',['finished','executing']];
        }
        $list = [];
        if($student_type == 'intent_student'){
            $temporaryorder = $this->temporaryorder->with(['admin','intentstudent','machinecar'])->where($where)->limit($numl,$pagenum)->select();
            foreach($temporaryorder as $k=>$v){
                $list[$k]['name'] = $v['intentstudent']['name'];
                $list[$k]['phone'] =  $v['intentstudent']['phone'];
                $list[$k]['machine_code'] = $v['machinecar']['machine_code'];
                $list[$k]['ordernumber'] = $v['ordernumber'];
                $list[$k]['reserve_starttime'] = $v['reserve_starttime'];
                $list[$k]['reserve_endtime'] = $v['reserve_endtime'];
                $list[$k]['starttime'] = $v['starttime'];
                $list[$k]['endtime'] = $v['endtime'];
                $list[$k]['should_endtime'] = $v['should_endtime'];
                $list[$k]['order_status'] = $v['order_status'];
                $list[$k]['car_type'] = $v['car_type_text'];
                $list[$k]['subject_type'] = $v['subject_type'];
            }
        }else{
            $order = $this->order->with(['student','admin','machinecar'])->where($where)->limit($numl,$pagenum)->select();
            foreach($order as $k=>$v){
                $list[$k]['name'] = $v['student']['name'];
                $list[$k]['phone'] =  $v['student']['phone'];
                $list[$k]['machine_code'] = $v['machinecar']['machine_code'];
                $list[$k]['ordernumber'] = $v['ordernumber'];
                $list[$k]['reserve_starttime'] = $v['reserve_starttime'];
                $list[$k]['reserve_endtime'] = $v['reserve_endtime'];
                $list[$k]['starttime'] = $v['starttime'];
                $list[$k]['endtime'] = $v['endtime'];
                $list[$k]['should_endtime'] = $v['should_endtime'];
                $list[$k]['order_status'] = $v['order_status'];
                $list[$k]['car_type'] = $v['car_type_text'];
                $list[$k]['subject_type'] = $v['subject_type'];
            }
        }
        
        $this->success('返回成功',$list);
    }
    

}
