<?php

namespace app\api\controller\test;

use app\common\controller\Api;
use PDO;
use think\cache;
use think\Db;

/**
 * 首页接口
 */
class Test1 extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    protected $coach = null;
    protected $student = null;
    protected $coachsc = null;
    protected $space = null;
    protected $order = null;
    protected $ordersc = null;
    protected $temporaryorder = null;
    protected $evaluation = null;
    protected $common = null;
    protected $machinecar = null;
    
    public function _initialize()
    {
        parent ::_initialize();
        $this->coach = new \app\admin\model\Coach;
        $this->student = new \app\admin\model\Student;
        $this->machinecar = new \app\admin\model\Machinecar;
        $this->coachsc = new \app\admin\model\Coachsc;
        $this->space = new \app\admin\model\Space;
        $this->order = new \app\admin\model\Order;
        $this->ordersc = new \app\admin\model\Ordersc;
        $this->temporaryorder = new \app\admin\model\Temporaryorder;
        $this->evaluation = new \app\admin\model\Evaluation;
        
        $this->common = new \app\api\controller\Common;
    }
    
    public function shua()
    {
        $where['phone'] = '15777163428';
        $student = $this->student->where($where)->find();
   
        
        var_dump($subject_type);exit;
    }


    public function delstudent(){
        
    }

    public function test()
    {
        if(empty($_POST['image']) || empty($_POST['stu_id'])){
            $this->error('缺少参数');
        }

        $image= $_POST['image'];
        $stu_id = $_POST['stu_id'];

        
        $name = md5(time().rand(1111,9999).'jpg').'.jpg';

        $name2 = md5(time().rand(1111,9999).'docx').'.docx';
        if (strstr($image,",")){
            $image = explode(',',$image);
            $image = $image[1];
        }
        $username = "min_img";
        //我们给每个用户动态的创建一个文件夹
        $title = "/uploads/".$username.'/'.date("Y-m-d");
        $user_path= $_SERVER['DOCUMENT_ROOT'].$title;
        if(!file_exists($user_path)) {
            //mkdir($user_path); 
            mkdir($user_path,0777,true); 
        }
        //将签名保存下来
        $r = file_put_contents($user_path.'/'.$name, base64_decode($image));
        // var_dump($r);exit;

        // $user_path = '/www/wwwroot/aivipdriver/public/uploads/min_img/2023-04-03/714a3a06d6e99671ca913194668b9290.jpg';
        $where['stu_id'] = $stu_id;
        $update['sign_path'] = $title.'/'.$name;
        $update['contract_state'] = 1;
        $student = $this->student->where($where)->find();

        $tmp = new \PhpOffice\PhpWord\TemplateProcessor(ROOT_PATH.'public'.$student['contract_path']);

        $tmp->setImageValue('学员签名', ['path' => $user_path.'/'.$name,'width'=>80,'height'=>38]);
        $tmp->setValue('签署日期', date('Y-m-d',time()));
        // $tmp->setImageValue('学员签名',['path' => $user_path,'width'=>80,'height'=>38]);
        $title2 = "/uploads/".date("Ymd");
        $path2 = $_SERVER['DOCUMENT_ROOT'].$title2;


        if(!file_exists($path2)){
            mkdir($path2,0777,true);
        }

        $tmp->saveAs($path2.'/'.$name2);

        // var_dump(ROOT_PATH.'public'.$student['contract_path']);
        if(file_exists($path2.'/'.$name2)){
            $update['contract_path'] = $title2.'/'.$name2;
            $student->where( $where)->update($update);
            unlink(ROOT_PATH.'public'.$student['contract_path']);
            $this->success('返回成功',$update['contract_path']);
        }
    }

    public function getorder()
    {
        $cooperation_id = '127';
        $starttime = \fast\Date::unixtime('month',-1);
        $endtime = \fast\Date::unixtime('month',0) -1;
        $order = $this->temporaryorder->with(['intent_student'])->where(['temporaryorder.cooperation_id'=>$cooperation_id,'reserve_starttime'=>['between',[$starttime,$endtime]]])->select();
        // $phones = array_column($order->toArray(),'stu_id');
        // var_dump($phones);exit;
        $student = [];
        foreach($order as $v){
            $res = 0;
            $res = $this->temporaryorder->where(['stu_id'=>$v['stu_id'],'reserve_starttime'=>['<',$starttime]])->find();
            if(!$res){
                array_push($student,$v['stu_id']);
            }
        }
        $unique_arr = array_unique($student);
        $repeat_arr = array_diff_assoc($student,$unique_arr);
        $unique_arr = array_unique($repeat_arr);

        var_dump($unique_arr);
    }


   


   

    /**
     * 查询订单
     */
    public function order_quire()
    {
        $params = $this->request->post();
        if(empty($params['page']) || empty($params['stu_id']) || empty($params['student_type'])){
            $this->error('参数缺失');
        }
        $page = $params['page'];
        $stu_id = $params['stu_id'];
        $order_status= $params['order_status'];
        $student_type = $params['student_type'];
        
        $pagenum = 10;
        $numl = $pagenum*($page-1);
        $where['stu_id'] = $stu_id;

        if(!$order_status){
            unset($where['order_status']);
        }else{
            $where['order_status'] = $order_status;
        }
        if($student_type == 'student'){
            $order = $this->order->with(['coach'])->where($where)->limit($numl,$pagenum)->order('reserve_starttime desc')->select();
        }else{
            $order = $this->temporaryorder->with(['coach'])->where($where)->limit($numl,$pagenum)->order('reserve_starttime desc')->select();
        }
        $list = [];
        foreach($order as $k=>$v){
            $list[$k]['ordernumber'] = $v['ordernumber'];
            $list[$k]['car_type'] = $v['car_type'];
            $list[$k]['car_type_text'] = $v['car_type_text'];
            $list[$k]['subject_type'] = $v['subject_type'];
            $list[$k]['reserve_starttime'] = $v['reserve_starttime'];
            $list[$k]['reserve_endtime'] = $v['reserve_endtime'];
            $list[$k]['starttime'] = $v['starttime'];
            $list[$k]['endtime'] = $v['endtime'];
            $list[$k]['should_endtime'] = $v['should_endtime'];
            $list[$k]['order_status'] = $v['order_status'];
            $list[$k]['coach'] = $v['coach']['name'];
            $list[$k]['coach_id'] =  $v['coach']['coach_id'];
            $list[$k]['evaluation'] =  $v['evaluation'];
            $list[$k]['space_detail'] = Cache::get('space_detail_'.$v['space_id']);
        }
        $this->success('返回成功',$list);
    }

    /**
     * 评价提交
     */
    public function evaluation(){
        $params = $this->request->post();
        if(empty($params['stu_id']) || empty($params['ordernumber']) || empty($params['space_evaluate']) || empty($params['overall']) || empty($params['student_type'])){
            $this->error('参数缺失');
        }
        $student_type = $params['student_type'];
        $where['stu_id'] = $params['stu_id'];
        $where['ordernumber'] = $params['ordernumber'];
        if($student_type == 'student'){
            $order = $this->order->where($where)->find();
        }else{
            $order = $this->temporaryorder->where($where)->find();
        }

        if(!$order){
            $this->error('所查订单有误');
        }
        if($order['evaluation'] == 1){
            $this->error('当前订单已评论');
        }
        $params['coach_id'] = $order['coach_id'];
        $params['space_id'] = $order['space_id'];
        $params['student_type'] = $student_type;
        $params['cooperation_id'] = $order['cooperation_id'];
        $res = $this->evaluation->save($params);
        $update['evaluation'] = 1;
        if($student_type == 'student'){
            $res_order = $this->order->where($where)->update($update);
        }else{
            $res_order = $this->temporaryorder->where($where)->update($update);
        }
        if($res_order){
            $this->success('返回成功');
        }else{
            $this->error('提交失败，请重新提交');
        }
    }

    /**
     * 取消订单
     */
    public function cancel_order(){
        $params = $this->request->post();

        //公钥加密
        if(empty($params['ordernumber']) || empty($params['student_type'])){
            $this->error('参数缺失');
        }
        $student_type = $params['student_type'];
        $date_now = time();
        $where['order_status'] = 'paid';
        $where['ordernumber'] = $params['ordernumber'];
        $data['order_status'] = 'cancel_refunded';
        if($student_type == 'student'){
            $order = $this->order->with('student')->where($where)->find();
        }else{
            $order = $this->temporaryorder->with('intentstudent')->where($where)->find();
            $order['student'] = $order['intentstudent'];
            unset($order['intentstudent']);
        }
        if(!$order){
            $this->success('查询无此订单');
        }
        // var_dump($order->toArray());exit;
        $reserve_starttime = $order['reserve_starttime'];
        $advance_cancel_times =  $this->space->where('id',$order['space_id'])->find()['advance_cancel_times'];
        $date = date('Y-m-d',$reserve_starttime);
        $time = explode(':',$advance_cancel_times);
        $ftime= $time[0]*3600+$time[1]*60+$time[2];
        if($date_now >$reserve_starttime){
            $this->error('您状态订单无法已无法取消，请联系客服');
        }
        $cha = $reserve_starttime-$date_now;
        if(($cha-$ftime)>0){
            
            if($order['student']['space_id'] == 24){
                //扣学时
                $deduct_surplus['period_surplus'] = $order['student']['period_surplus'] + 2;
                $this->student->where('stunumber',$order['stu_id'])->update($deduct_surplus);
            }
            if($student_type == 'student'){
                $res = $this->order->where($where)->update($data);
            }else{
                $res = $this->temporaryorder->where($where)->update($data);
            }
            if($res){
                $this->success('提交成功');
            }else{
                $this->error('取消异常，请重新提交');
            }
        }else{
            $this->error('请提前'.$advance_cancel_times.'小时取消订单，当前订单请联系客服取消');
        }
    }


    /**
     * 完成订单
     */
    public function finish(){
        $params = $this->request->post();
        // $params['ordernumber'] = 'CON20210422113058603554';
        // $params['student_type'] = 'intent_student';
        if(empty($params['ordernumber']) || empty($params['student_type'])){
            $this->error('参数缺失');
        }
        $this->common->finish($params['ordernumber'],$params['student_type']);
    }


    /**
     * 日期验证
     */
    public function timevalidate($reserve_starttime, $reserve_endtime){
        if($reserve_starttime < time() || $reserve_endtime < time() ){
            $this->error('预约时间段已过时，请选择正确的时间');
        }
    }

    public function order_validate($stu_id,$coach_type)
    {
        $where_order_exist['stu_id'] = $stu_id;
        $where_order_exist['order_status'] = ['in',['paid','unpaid','accept_unexecut','executing']];
        if($coach_type == 'AI'){
            $order_exist = $this->order->with(['space'])->where($where_order_exist)->find();
        }else{
            $order_exist = $this->ordersc->with(['space'])->where($where_order_exist)->find();
        }
        if($order_exist){
            $this->error('已有未完成的订单，请先完成后再提交订单');
        }
    }

    public function coach_validate($car_type,$coach,$reserve_starttime,$reserve_endtime,$coach_type)
    {
        if($coach['teach_state'] == 'no'){
            $this->error('当前教员不在教学状态，请选择其他教练');
        }
        $coach_id = $coach['coach_id'];
        
        $reserve_starttimes= date('H:i:s',$reserve_starttime);
        $reserve_endtimes= date('H:i:s',$reserve_endtime);

        if(!$coach){
            $this->error('无法找到当前教员');
        }

        if($coach_type == 'AI'){
            $coach_time_list = Db::name('coach_config_time_people')->where('coach_id',$coach['id'])->select();
        }else{
            $coach_time_list = Db::name('coach_sc_config_time_people')->where('coach_id',$coach['id'])->select();
        }
        $num = 0;
        foreach($coach_time_list as $k=>$v){
            if($v['starttimes'] == $reserve_starttimes && $v['endtimes'] == $reserve_endtimes){
                $num +=1;
                $number = $v['number'];
                $where_order['car_type '] = $car_type;
                $where_order['coach_id'] = $coach_id;
                $where_order['reserve_starttime'] = $reserve_starttime;
                $where_order['reserve_endtime'] = $reserve_endtime;
                $where_order['order_status'] = ['neq','cancel_refunded'];
                $order_number = $this->order->where($where_order)->count();
                if(($number - $order_number) <=0){
                    $this->error('当前时间段不可预约');
                }
                $status = 1;
            }
        }
        if($num == 0){
            $this->error('预约时间填写错误,无法找到预约时间段');
        }
    }
}
