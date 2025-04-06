<?php

namespace app\api\controller\student;

use app\common\controller\Api;
use PDO;
use think\cache;
use think\Db;

/**
 * 开机流程所需接口
 */
class Test extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    protected $urlHead = 'https://aivipdriver.com';

    private $ordersc = null;
    private $common = null;
    private $order = null;
    private $stutj = null;
    private $temporaryorder = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->ordersc = new \app\admin\model\Ordersc;
        $this->order = new \app\admin\model\Order;
        $this->temporaryorder = new \app\admin\model\Temporaryorder;
        // $this->stutj = new \app\admin\model\Stutj;
        $this->common = new \app\api\controller\Common;
    }

    function shuju(){
        $params = Cache::get('machine_FJSZxmg202306003fdone');
        var_dump($params);
    }


    function array_unset_tt($arr,$key='stu_id'){
        //建立一个目标数组
        $res = array();
        foreach ($arr as $value) {
            //查看有没有重复项
            if(isset($res[$value[$key]])){
                unset($value[$key]);  //有：销毁
            }else{
                $res[$value[$key]] = $value;
            }
        }
        return $res;
    }

    public function test1()
    {
        $where['ordersc.createtime'] = 1665652517;
        $order = $this->ordersc->with(['car'])->where($where)->select();
        var_dump($order->toArray());
    }

    /**
     * 科技平台下预约订单
     */
    public function send_get_order()
    {
        $params['stu_id'] = 'CSN20210425112607670801';
        $params['machine_code'] = '测A001学';
        //公钥加密
        // ksort($params);
        // $token = reset($params);
        // $params['token'] = $this->rsa->pubEncrypt($token);
        $url = $this->urlHead.'/api/student/boot_sc/getscorder';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }

    public function change_test()
    {
        $data = date('Y-m-d');
        // $starttime = strtotime($data.'00:00:00');
        $endtime = strtotime($data.'23:59:59');
        // var_dump($starttime,$endtime);exit;
        $con = mysqli_connect('localhost','aivipdriver','fk4NmCpkA7cdRFbX','aivipdriver') or die('数据库连接不上');

        //未开始的取消
        $countSql1 = "UPDATE fa_order SET order_status='cancel_refunded' WHERE order_status='paid' AND reserve_starttime <".$endtime."";
        mysqli_query($con,$countSql1);
        //执行中的完成
        $countSql2 = "UPDATE fa_order SET order_status='finished' WHERE order_status='executing' AND reserve_starttime <".$endtime."";
        mysqli_query($con,$countSql2);
        //已受理未执行的完成
        $countSql3 = "UPDATE fa_order SET order_status='finished' WHERE order_status='accept_unexecut' AND reserve_starttime <".$endtime."";
        mysqli_query($con,$countSql3);

        //未开始的取消
        $countSql1 = "UPDATE fa_temporary_order SET order_status='cancel_refunded' WHERE order_status='paid' AND reserve_starttime <".$endtime."";
        mysqli_query($con,$countSql1);
        //执行中的完成
        $countSql2 = "UPDATE fa_temporary_order SET order_status='finished' WHERE order_status='executing' AND reserve_starttime <".$endtime."";
        mysqli_query($con,$countSql2);
        //已受理未执行的完成
        $countSql3 = "UPDATE fa_temporary_order SET order_status='finished' WHERE order_status='accept_unexecut' AND reserve_starttime <".$endtime."";
        mysqli_query($con,$countSql3);

    }

    public function change_process()
    {
        $where['process_name'] = 'xinshou';
        $res = $this->studyprocess->where($where)->select();
        foreach($res as $v){
            $update['place_id'] = 1;
            $this->studyprocess->where()->update();
        }
        var_dump($res->count());exit;
    }
    public function getordertest()
    {
        $params['stu_id'] = 'CSN20210727195747782765';
        $params['machine_code'] = '10020';
        $params['student_type'] = 'student';
        if(empty($params['stu_id']) || empty($params['machine_code']) || empty($params['student_type'])){
            $this->error('参数缺失');
        }
        $stu_id = $params['stu_id'];
        $student_type = $params['student_type'];
        $machine_code = $params['machine_code'];
        $today_start = strtotime(date('Y-m-d 00:00:00',time()));
        $today_end = $today_start + 24*3600-1;

        $where_machine['machine_code'] = $machine_code;
        $machine = $this->machinecar->with('space')->where($where_machine)->find();
        $allow = $machine['space']['allow_space'];
        $arr_allow = explode(',',$allow);
        array_push($arr_allow,$machine['space']['id']);
        if($student_type== 'student'){
            $where_allow['stu_id'] = $stu_id;
            $student = $this->student->where($where_allow)->find();
            $space_id = $student['space_id'];
            $allow_res = in_array($space_id,$arr_allow);
            if($allow_res == false){
                $this->error('当前场馆不对您开放，请与客服人员确认后再上机');
            }
            if($student['car_type'] !=$machine['car_type']){
                $this->error('当前车型与模拟器车型不匹配，请更换车型');
            }
        }
        //以前没上机的直接完成
        $this->finish_order($stu_id,$student_type,$today_start);

        
    }

    /**
     * 完成历史异常订单
     */
    public function finish_order($stu_id,$student_type,$today_start)
    {
        if($student_type == 'student'){
            //开机的结束
            $where_finish['stu_id'] = $stu_id;
            $where_finish['reserve_starttime'] = ['<',$today_start];
            $where_finish['order_status'] = ['in',['accept_unexecut','executing']];
            $update_finish['order_status'] = 'finished';
            $this->order->where($where_finish)->update($update_finish);

            //没开机的取消
            $where_cancel['stu_id'] = $stu_id;
            $where_finish['reserve_starttime'] = ['<',$today_start];
            $where_finish['order_status'] = 'paid';
            $update_cancel['order_status'] = 'cancel_refunded';
            $this->order->where($where_finish)->update($update_finish);
        }else{
            $where_finish['stu_id'] = $stu_id;
            $where_finish['reserve_starttime'] = ['<',$today_start];
            $where_finish['order_status'] = ['in',['accept_unexecut','executing']];
            $update_finish['order_status'] = 'finished';
            $this->temporaryorder->where($where_finish)->update($update_finish);
            //没开机的取消
            $where_cancel['stu_id'] = $stu_id;
            $where_finish['reserve_starttime'] = ['<',$today_start];
            $where_finish['order_status'] = 'paid';
            $update_cancel['order_status'] = 'cancel_refunded';
            $this->temporaryorder->where($where_finish)->update($update_finish);
        }
    }




    /**
     * 科技平台下预约订单
     */
    public function send_submit_order()
    {
        $params['stu_id'] = 'CSN20211105172515623068';
        $params['coach_id'] = 'CTN20210823105628842019';
        $params['reserve_starttime'] = '2022-05-20 16:00:00';
        $params['reserve_endtime'] = '2022-05-20 18:00:00';
        $params['subject_type'] = 'subject2';
        $params['student_type'] = 'student';
        
        //公钥加密
        // ksort($params);
        // $token = reset($params);
        // $params['token'] = $this->rsa->pubEncrypt($token);
        $url = $this->urlHead.'/api/student/reserve_order/submit_order';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }

    public function send_order_quire()
    {
        $params['page'] = 1;
        $params['order_status'] = '';
        $params['stu_id'] = 'CSN20201111194721493894';
        $params['student_type'] = 'student';
        
        //公钥加密
        // ksort($params);
        // $token = reset($params);
        // $params['token'] = $this->rsa->pubEncrypt($token);
        $url = $this->urlHead.'/api/student/reserve_order/order_quire';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }

    public function send_evaluation()
    {
        $params['ordernumber'] = 'CON20201116193242828047';
        $params['space_evaluate'] = '教得好';
        $params['overall'] = 2;
        $params['stu_id'] = 'CSN20201111194721493894';
        //公钥加密
        // ksort($params);
        // $token = reset($params);
        // $params['token'] = $this->rsa->pubEncrypt($token);
        $url = $this->urlHead.'/api/student/reserve_order/evaluation';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }

    public function send_cancel_order()
    {
        $params['ordernumber'] = 'CON20210422163929682368';
        $params['student_type'] = 'intent_student';
        //公钥加密
        // ksort($params);
        // $token = reset($params);
        // $params['token'] = $this->rsa->pubEncrypt($token);
        $url = $this->urlHead.'/api/student/reserve_order/cancel_order';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }


    public function send_finished()
    {
        $params['ordernumber'] = 'CON20201116193242828047';
        $params['student_type'] = 'student';
        //公钥加密
        // ksort($params);
        // $token = reset($params);
        // $params['token'] = $this->rsa->pubEncrypt($token);
        $url = $this->urlHead.'/api/student/reserve_order/finished';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }

    public function send_space_list()
    {
        $params['lat'] = '';
        $params['lng'] = '';
        $params['city'] = '上海市';
        $params['stu_id'] = '';
        $params['student_type'] = '';
        $params['cooperation_id'] = '';
        $url = $this->urlHead.'/api/student/get_space/spacelisttest';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }

    public function send_spacedetail()
    {
        $params['space_id'] = 5;
        $url = $this->urlHead.'/api/student/get_space/spacedetail';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }

    public function send_coach_list()
    {
        $params['lat'] = '';
        $params['lng'] = '';
        $params['stu_id'] = 'CSN20210414163724824675';
        $params['student_type'] = 'intent_student';
        $params['request_date'] = '2021-04-17';

        $url = $this->urlHead.'/api/student/coach/coachlist';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }

    public function send_evaluation_list()
    {
        $params['coach_id'] = 'CTNasdas';
        $params['page'] = 1;
        $params['student_type'] = 'intent_student';
        $url = $this->urlHead.'/api/student/coach/evaluation_list';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }

    public function send_coach_detail()
    {
        $params['coach_id'] = 'CTN20220411175836648272';
        $params['request_date'] = '2022-04-29';
        $params['stu_id'] = 'CSN20210418163834400617';
        $params['car_type'] = 'cartype1';
        $params['student_type'] = 'intent_student';
        $url = $this->urlHead.'/api/student/coach/coachdetails';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }
    
    public function send_getorder()
    {
        $params['machine_code'] = '10020';
        $params['stu_id'] = 'CSN20210414163724824675';
        $params['student_type'] = 'intent_student';
        $url = $this->urlHead.'/api/student/boot/getorder';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }

    /**
     * 现场下单
     */
    public function send_submitorder()
    {
        $params['machine_code'] = '10020';
        $params['stu_id'] = 'CSN20210408112823590703';
        $params['student_type'] = 'intent_student';
        $params['ordernumber'] = '';
        $params['reserve_starttime'] = time()-1000;
        $params['reserve_endtime'] = time()+3600*2-1000;
        $params['should_endtime'] = time()+3600*2-1000;
        $params['coach_id'] = 'CTN20201027111519116883';
        $params['order_status'] = 'paid';
        $params['ordertype'] = 2;
        $params['car_type'] = 'cartype1';
        $params['subject_type'] = 'subject2';
        $url = $this->urlHead.'/api/student/boot/submitorder';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }
    

    /**
     * 获取学员授权展示教员列表
     */
    public function send_boot_coach_list()
    {
        $params['machine_code'] = '123';
        $url = $this->urlHead.'/api/student/boot/boot_coach_list';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }

    public function send_test()
    {
        $params['ordernumber'] = 'CON20210412172345451930';
        $params['student_type'] = 'intent_student';
        $url = $this->urlHead.'/api/machine_boot/machine_start';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }

    

    public function test()
    {
        $where['phone'] = '';
        $count = $this->student->where($where)->count();
        var_dump($count);exit;
    }
}

