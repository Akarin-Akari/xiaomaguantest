<?php

namespace app\api\controller\student;

use app\common\controller\Api;
use think\cache;
use think\Db;

/**
 * 首页接口
 */
class CoachTest extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    protected $order = null;
    protected $ordersc = null;
    protected $common = null;
    protected $coachleave = null;
    protected $coachscleave = null;
    protected $evaluation = null;
    protected $coach = null;
    protected $student = null;
    protected $coachsc = null;
    protected $space = null;
    protected $cooperation = null;
    protected $reportcard = null;

    public function _initialize()
    {
        parent ::_initialize();
        $this->order = new \app\admin\model\Order;
        $this->ordersc = new \app\admin\model\Ordersc;
        $this->common = new \app\api\controller\Common;
        $this->coachleave = new \app\admin\model\Coachleave;
        $this->coachscleave = new \app\admin\model\Coachscleave;
        $this->evaluation = new \app\admin\model\Evaluation;
        $this->coach = new \app\admin\model\Coach;
        $this->student = new \app\admin\model\Student;
        $this->coachsc = new \app\admin\model\Coachsc;
        $this->space = new \app\admin\model\Space;
        $this->cooperation = new \app\admin\model\Cooperation;
        $this->reportcard = new \app\admin\model\Reportcard;

    }

    public function coachlisttest()
    {
        $params = $this->request->post();
     
        // $params['lat'] = '';
        // $params['lng'] = '';
        // $params['region'] = '梅州市';
        // $params['car_type'] = 'cartype1';
        // $params['cooperation_id'] = '288';
        // $params['student_type'] = 'student';
        // $params['request_date'] = '2022-12-29';
        if(empty($params['request_date'])|| empty($params['car_type']) ||empty($params['region'])){
            $this->error('参数缺失');
        }
        $datetime1_date = $params['request_date'];
        $car_type = $params['car_type'];
        $city = $params['region'];
        $lat = $params['lat'];
        $lng = $params['lng'];
        //当天日期
        $date = date("Y-m-d");
        if($datetime1_date < $date){
            $this->error('当前时间已过期，无法查看');
        }

        //request日期
        $datetime1 = strtotime($datetime1_date);
        $where['space_state'] = 'yes';
        $where['city'] =  ['like','%'.$city.'%'];
        $where['space_type'] = 'ai_car';

        if($params['student_type'] == 'student'){
            $where['cooperation_id'] = $params['cooperation_id'];
        }else{
            $where['cooperation_id'] = '';
        }
        if($city == '上海市'){
            unset($where['cooperation_id']);
        }
        $res= $this->space->where($where)->select();
        $list = [];
        foreach($res as $k=>$v){
            $list[$k]['advance_cancel_times'] = $v['advance_cancel_times'];
            $list[$k]['day'] = $v['day'];
            $address = explode('/',$v['city']);
            $list[$k]['province'] = '';
            $list[$k]['city'] = '';
            if($v['city']){
                $list[$k]['province'] = $address[0];
                $list[$k]['city'] = $address[1];
            }
            $list[$k]['id'] = $v['id'];
            $list[$k]['space_id'] = $v['id'];
            $list[$k]['lat'] = $v['lat'];
            $list[$k]['lng'] = $v['lng'];
            $list[$k]['name'] = $v['space_name'];
            $list[$k]['address'] = $v['address'];
            
            $list[$k]['day_status'] = $this->day_limit($v['day'],$datetime1_date,$date);
            if($list[$k]['day_status'] == 0){
                $list[$k]['space_state'] = 'no';
                unset($list[$k]);
                sort($list);
                continue;
            }
            if($lat !='' || $lng !=''){
                $list[$k]['distance'] = $this->common->GetDistance($v['lat'],$v['lng'],$lat,$lng);
            }
            $list[$k]['coach'] = $this->getcoach($v['id'],$car_type);
            if(!$list[$k]['coach']){
                unset($list[$k]);
                continue ;
            }
            foreach($list[$k]['coach'] as $kk=>$vv){
                //获取当前教员当天的请假情况
                $where_coach['coach_id'] = $vv['coach_id'];
                $leavetime = $this->coachleave->where($where_coach)->find();
                $coach_reservetime =  Db::name('coach_config_time_people')->where('coach_id',$vv['id'])->select();
                if($leavetime){
                    $list[$k]['coach'][$kk]['leavetime'] = $leavetime;
                    $timelist = $this->get_time($coach_reservetime,$leavetime,$datetime1_date);
                    $list[$k]['coach'][$kk]['timelist'] = $timelist;
                }else{
                    $list[$k]['coach'][$kk]['timelist'] = $coach_reservetime;
                }

                
                $coach_list = $this->coach_order($list[$k]['coach'][$kk],$datetime1,$car_type);
                $list[$k]['coach'][$kk]['timelist'] = $coach_list;
                unset($coach_list);
                unset($timelist);
                unset($leavetime);
            }
            $list[$k]['space_state'] = $v['space_state'];
        }
        sort($list);
        if($lat){
            $arr = array_column($list, 'distance');
            array_multisort($arr, SORT_ASC, $list);
        }
        $this->success('返回成功', $list);
    }
    /**
     * 获取教员列表
     * car_type:C1  date: 2020-6-18
    */
    public function coachlist()
    {
        $params = $this->request->post();
        if(empty($params['request_date'])|| empty($params['car_type']) ||empty($params['region'])){
            $this->error('参数缺失');
        }
        $datetime1_date = $params['request_date'];
        $car_type = $params['car_type'];
        $city = $params['region'];
        $lat = $params['lat'];
        $lng = $params['lng'];
        //当天日期
        $date = date("Y-m-d");
        if($datetime1_date < $date){
            $this->error('当前时间已过期，无法查看');
        }

        //request日期
        $datetime1 = strtotime($datetime1_date);
        $where['space_state'] = 'yes';
        $where['city'] =  ['like','%'.$city.'%'];
        $where['space_type'] = 'ai_car';

        if($params['student_type'] == 'student'){
            $where['cooperation_id'] = $params['cooperation_id'];
        }else{
            $where['cooperation_id'] = '';
        }
        if($city == '上海市'){
            unset($where['cooperation_id']);
        }
        $res= $this->space->where($where)->select();
        $list = [];
        foreach($res as $k=>$v){
            $list[$k]['advance_cancel_times'] = $v['advance_cancel_times'];
            $list[$k]['day'] = $v['day'];
            $address = explode('/',$v['city']);
            $list[$k]['province'] = '';
            $list[$k]['city'] = '';
            if($v['city']){
                $list[$k]['province'] = $address[0];
                $list[$k]['city'] = $address[1];
            }
            $list[$k]['id'] = $v['id'];
            $list[$k]['space_id'] = $v['id'];
            $list[$k]['lat'] = $v['lat'];
            $list[$k]['lng'] = $v['lng'];
            $list[$k]['name'] = $v['space_name'];
            $list[$k]['address'] = $v['address'];
            
            $list[$k]['day_status'] = $this->day_limit($v['day'],$datetime1_date,$date);
            if($list[$k]['day_status'] == 0){
                $list[$k]['space_state'] = 'no';
                unset($list[$k]);
                sort($list);
                continue;
            }
            if($lat !='' || $lng !=''){
                $list[$k]['distance'] = $this->common->GetDistance($v['lat'],$v['lng'],$lat,$lng);
            }
            $list[$k]['coach'] = $this->getcoach($v['id'],$car_type);
            if(!$list[$k]['coach']){
                unset($list[$k]);
                continue ;
            }
            foreach($list[$k]['coach'] as $kk=>$vv){
                //获取当前教员当天的请假情况
                $where_coach['coach_id'] = $vv['coach_id'];
                $leavetime = $this->coachleave->where($where_coach)->find();
                $coach_reservetime =  Db::name('coach_config_time_people')->where('coach_id',$vv['id'])->select();
                if($leavetime){
                    $list[$k]['coach'][$kk]['leavetime'] = $leavetime;
                    $timelist = $this->get_time($coach_reservetime,$leavetime,$datetime1_date);
                    $list[$k]['coach'][$kk]['timelist'] = $timelist;
                }else{
                    $list[$k]['coach'][$kk]['timelist'] = $coach_reservetime;
                }

                $coach_list = $this->coach_order($list[$k]['coach'][$kk],$datetime1,$car_type);
                $list[$k]['coach'][$kk]['timelist'] = $coach_list;
                unset($coach_list);
                unset($timelist);
                unset($leavetime);
            }
            $list[$k]['space_state'] = $v['space_state'];
        }
        sort($list);
        if($lat){
            $arr = array_column($list, 'distance');
            array_multisort($arr, SORT_ASC, $list);
        }
        $this->success('返回成功', $list);
    }




    /**
     * 获取实车教练列表
     */
    public function coachsctest()
    {
        $params = $this->request->post();

        // $params['request_date'] = '2022-11-11';
        // $params['stu_id'] = 'CSN20210425112607670801';
        // $params['region'] = '梅州市';
        
        if(empty($params['request_date']) || empty($params['stu_id'])){
            $this->error('参数缺失');
        }
        $datetime1_date = $params['request_date'];
        //当天日期
        $date = date("Y-m-d");
        if($datetime1_date < $date){
            $this->error('当前时间已过期，无法查看');
        }
        $city = $params['region'];

        //request日期
        $datetime1 = strtotime($datetime1_date);
        $student = $this->student->where(['stu_id'=>$params['stu_id']])->find();
        $cooperation = $this->cooperation->where(['cooperation_id'=>$student['cooperation_id']])->find();
        
        $list = [];
        $where['city'] =  ['like','%'.$city.'%'];
        // var_dump($student['cooperation_id']);exit;
        $where['cooperation_id'] =  $student['cooperation_id'];
        $where['space_type'] = 'car';
        $where['space_state'] = 'yes';
        
        $car_type = $student['car_type'];

        if($cooperation['distribute_state']){
            $coach = [];
            if($student['coach_sc_keer']){
                array_push($coach,$student['coach_sc_keer']);
            }
            if($student['coach_sc_kesan']){
                array_push($coach,$student['coach_sc_kesan']);
            }
            if($coach){
                $list = $this->distribute_coach_sc_time($coach,$datetime1,$datetime1_date,$date,$car_type,$city);
            }

        }else{
            $space = $this->space->where($where)->select();
            foreach($space as $k=>$v){
                $list[$k]['advance_cancel_times'] = $v['advance_cancel_times'];
                $list[$k]['day'] = $v['day'];
                $address = explode('/',$v['city']);
                $list[$k]['province'] = '';
                $list[$k]['city'] = '';
                if($v['city']){
                    $list[$k]['province'] = $address[0];
                    $list[$k]['city'] = $address[1];
                }
                $list[$k]['id'] = $v['id'];
                $list[$k]['space_id'] = $v['id'];
                $list[$k]['lat'] = $v['lat'];
                $list[$k]['lng'] = $v['lng'];
                $list[$k]['name'] = $v['space_name'];
                $list[$k]['address'] = $v['address'];
                
                $list[$k]['day_status'] = $this->day_limit($v['day'],$datetime1_date,$date);
                if($list[$k]['day_status'] == 0){
                    $list[$k]['space_state'] = 'no';
                    unset($list[$k]);
                    sort($list);
                    continue;
                }
                
                $list[$k]['coach'] = $this->getcoachsc($v['id'],$car_type);
                
                if(!$list[$k]['coach']){
                    unset($list[$k]);
                    continue ;
                }
                foreach($list[$k]['coach'] as $kk=>$vv){
                    //获取当前教员当天的请假情况
                    $where_coach['coach_id'] = $vv['coach_id'];
                    $leavetime = $this->coachscleave->where($where_coach)->find();
                    $coach_reservetime =  Db::name('coach_sc_config_time_people')->where('coach_id',$vv['id'])->select();
                    if($leavetime){
                        $list[$k]['coach'][$kk]['leavetime'] = $leavetime;
                        $timelist = $this->get_time($coach_reservetime,$leavetime,$datetime1_date);
                        $list[$k]['coach'][$kk]['timelist'] = $timelist;
                    }else{
                        $list[$k]['coach'][$kk]['timelist'] = $coach_reservetime;
                    }
                    $coach_list = $this->coach_sc_order($list[$k]['coach'][$kk],$datetime1,$car_type);

                    $list[$k]['coach'][0]['timelist'] = $coach_list;

                    unset($coach_list);
                    unset($timelist);
                    unset($leavetime);
                }
                $list[$k]['space_state'] = $v['space_state'];
            }
        }
        $this->success('返回成功', $list);
    }

    /**
     * 获取实车教员
     */
    public function distribute_coach_sc_time($coach,$datetime1,$datetime1_date,$date,$car_type,$city)
    {
        $list = [];
        foreach($coach as $k=>$v){
            $space = [];
            //获取当前教员当天的请假情况
            $coach = $this->coachsc->with('space')->where(['coach_id'=>$v])->find();
            $space = $coach['space'];
            unset($coach['space']);
            $list[$k]['coach'][0] = $coach->toArray();

            $list[$k]['advance_cancel_times'] = $coach['space']['advance_cancel_times'];
            $list[$k]['day'] = $space['day'];
            $city_status = strstr($space['city'],$city);
            if(!$city_status){
                continue;
            }
            $address = explode('/',$space['city']);
            $list[$k]['province'] = '';
            $list[$k]['city'] = '';
            if($space['city']){
                $list[$k]['province'] = $address[0];
                $list[$k]['city'] = $address[1];
            }
            $list[$k]['id'] = $space['id'];
            $list[$k]['space_id'] = $space['id'];
            $list[$k]['lat'] = $space['lat'];
            $list[$k]['lng'] = $space['lng'];
            $list[$k]['name'] = $space['space_name'];
            $list[$k]['address'] = $space['address'];
            
            $list[$k]['day_status'] = $this->day_limit($space['day'],$datetime1_date,$date);
            if($list[$k]['day_status'] == 0){
                $list[$k]['space_state'] = 'no';
                unset($list[$k]);
                continue;
            }
            
            $where_coach['coach_id'] = $v;
            $leavetime = $this->coachscleave->where($where_coach)->find();
            $coach_reservetime =  Db::name('coach_sc_config_time_people')->where('coach_id',$coach['id'])->select();
            if($leavetime){
                // $list[$k]['coach'][0]['leavetime'] = $leavetime;
                $timelist = $this->get_time($coach_reservetime,$leavetime,$datetime1_date);
                $list[$k]['coach'][0]['timelist'] = $timelist;
                // var_dump($timelist);exit;
            }else{
                $list[$k]['coach'][0]['timelist'] = $coach_reservetime;
            }
            $coach_list = $this->coach_sc_order($list[$k]['coach'][0],$datetime1,$car_type);
            $list[$k]['coach'][0]['timelist'] = $coach_list;
            unset($coach_list);
            unset($timelist);
            unset($leavetime);
            $list[$k]['space_state'] = $coach['space']['space_state'];
            // $where_coach['coach_id'] = $vv['coach_id'];
            // $leavetime = $this->coachscleave->where($where_coach)->find();
            // $coach_reservetime =  Db::name('coach_sc_config_time_people')->where('coach_id',$vv['id'])->select();
            // if($leavetime){
            //     $list[$k]['coach'][$kk]['leavetime'] = $leavetime;
            //     $timelist = $this->get_time($coach_reservetime,$leavetime,$datetime1_date);
            //     $list[$k]['coach'][$kk]['timelist'] = $timelist;
            // }else{
            //     $list[$k]['coach'][$kk]['timelist'] = $coach_reservetime;
            // }

            // $coach_list = $this->coach_sc_order($list[$k]['coach'][$kk],$datetime1,$car_type);
            // $list[$k]['coach'][$kk]['timelist'] = $coach_list;
            // unset($coach_list);
            // unset($timelist);
            // unset($leavetime);
            // unset($people_upper);
        }
        return $list ;
    }  


    public function coachsc()
    {
        $params = $this->request->post();

        $params['request_date'] = '2023-04-27';
        $params['stu_id'] = 'CSN20210425112607670801';
        $params['region'] = '梅州市';
        if(empty($params['request_date']) || empty($params['stu_id']) || empty($params['region'])){
            $this->error('参数缺失');
        }
        $datetime1_date = $params['request_date'];
        //当天日期
        $date = date("Y-m-d");
        if($datetime1_date < $date){
            $this->error('当前时间已过期，无法查看');
        }
        $city = $params['region'];

        //request日期
        $datetime1 = strtotime($datetime1_date);
        $student = $this->student->where(['stu_id'=>$params['stu_id']])->find();
        $cooperation = $this->cooperation->where(['cooperation_id'=>$student['cooperation_id']])->find();
        
        $list = [];
        $where['city'] =  ['like','%'.$city.'%'];
        // var_dump($student['cooperation_id']);exit;
        $where['cooperation_id'] =  $student['cooperation_id'];
        $where['space_type'] = 'car';
        $where['space_state'] = 'yes';
        
        
        $car_type = $student['car_type'];

        //科三练车未缴清费用限制上车
        if($cooperation){
            if($cooperation['forbidden_pay_state'] == '1'){
                if($student['payment_process'] !=='payed'){
                    $this->error('返回成功',$list);
                }
            }
        }
        //是否按照分配教练预约实车
        if($cooperation['distribute_state']){
            $coach = [];
            if($student['coach_sc_keer']){
                array_push($coach,$student['coach_sc_keer']);
            }
            if($student['coach_sc_kesan']){
                array_push($coach,$student['coach_sc_kesan']);
            }
            if($coach){
                $list = $this->distribute_coach_sc_time($coach,$datetime1,$datetime1_date,$date,$car_type,$city);
            }
        }else{
            $space = $this->space->where($where)->select();
            foreach($space as $k=>$v){
                $list[$k]['advance_cancel_times'] = $v['advance_cancel_times'];
                $list[$k]['day'] = $v['day'];
                $address = explode('/',$v['city']);
                $list[$k]['province'] = '';
                $list[$k]['city'] = '';
                if($v['city']){
                    $list[$k]['province'] = $address[0];
                    $list[$k]['city'] = $address[1];
                }
                $list[$k]['id'] = $v['id'];
                $list[$k]['space_id'] = $v['id'];
                $list[$k]['lat'] = $v['lat'];
                $list[$k]['lng'] = $v['lng'];
                $list[$k]['name'] = $v['space_name'];
                $list[$k]['address'] = $v['address'];
                
                $list[$k]['day_status'] = $this->day_limit($v['day'],$datetime1_date,$date);
                if($list[$k]['day_status'] == 0){
                    $list[$k]['space_state'] = 'no';
                    unset($list[$k]);
                    sort($list);
                    continue;
                }
                
                $list[$k]['coach'] = $this->getcoachsc($v['id'],$car_type);
                
                if(!$list[$k]['coach']){
                    unset($list[$k]);
                    continue ;
                }
                foreach($list[$k]['coach'] as $kk=>$vv){
                    //获取当前教员当天的请假情况
                    $where_coach['coach_id'] = $vv['coach_id'];
                    $leavetime = $this->coachscleave->where($where_coach)->select();
                    $coach_reservetime =  Db::name('coach_sc_config_time_people')->where('coach_id',$vv['id'])->select();
                    if($leavetime){
                        $list[$k]['coach'][$kk]['leavetime'] = $leavetime;
                        $timelist = $this->get_time($coach_reservetime,$leavetime,$datetime1_date);
                        $list[$k]['coach'][$kk]['timelist'] = $timelist;
                    }else{
                        $list[$k]['coach'][$kk]['timelist'] = $coach_reservetime;
                    }

                    if(!$timelist){
                        unset($list[$k]['coach'][$kk]);
                        continue;
                    }

                    $coach_list = $this->coach_sc_order($list[$k]['coach'][$kk],$datetime1,$car_type);
                    $list[$k]['coach'][$kk]['timelist'] = $coach_list;
                    unset($coach_list);
                    unset($timelist);
                    unset($leavetime);
                }
                $list[$k]['space_state'] = $v['space_state'];
                if(!$list[$k]['coach']->toarray()){
                    unset($list[$k]);
                    continue;
                }
            }
        }
        $this->success('返回成功', $list);
    }

    /**
     * 评论列表
     */
    public function evaluation_list(){
        $params = $this->request->post();

        if(empty($params['coach_id']) || empty($params['page']) || empty($params['student_type'])){
            $this->error('参数缺失');
        }
        $pagenum = 10;
        $page = $params['page'];
        $student_type = $params['student_type'];
        $numl = $pagenum*($page-1);
        if($student_type == 'student'){
            $evaluation_list = $this->evaluation->where('coach_id',$params['coach_id'])->limit($numl,$pagenum)->select();
        }else{
            $evaluation_list = $this->evaluation->where('coach_id',$params['coach_id'])->limit($numl,$pagenum)->select();
        }
        if($evaluation_list){
            $evaluation_list = $evaluation_list;
            $this->success('返回成功',$evaluation_list);
        }else{
            $this->success('此教员暂时没有评价');
        }
    }


    /**
     * 教员预约详情
    */
    public function coachdetails(){
        $params = $this->request->post();
        // $params['coach_id'] = 'CTN20210311103714746874';
        // $params['request_date'] = '2023-06-25';
        // $params['student_type'] = 'student';
        // $params['stu_id'] = 'CSN20210425112607670801';
        // $params['car_type'] = 'cartype2';

        if(empty($params['coach_id']) || empty($params['request_date']) || empty($params['student_type']) || empty($params['car_type']) || empty($params['stu_id'])){
            $this->error('参数缺失');
        }
        $coach_id = $params['coach_id'];
        $request_date = $params['request_date'];
        $car_type = $params['car_type'];
        $stu_id = $params['stu_id'];
        $student_type = $params['student_type'];

        $date = date("Y-m-d");
        if($request_date < $date){
            $this->error('当前时间已过期，无法查看');
        }
        //请求日期
        $request_datetime= strtotime($request_date);
        //请求第二天日期
        $request_datetime2 = $request_datetime+86400;
        $coach = $this->coach->with('space')->where('coach_id',$coach_id)->find();
        $coach['space']['pick_up_status'] = (int)$coach['space']['pick_up_status'];
        $pick_up_status = $coach['space']['pick_up_status'];
        $student = $this->student->where(['stu_id'=>$params['stu_id']])->find();
        
        $coo = $this->cooperation->where(['cooperation_id'=>$coach['cooperation_id']])->find();
        if($coo['forbidden_not_reserve'] == 1 && $student['order_num'] <= 0 ){
            $this->error('已超过学员预约次数，请联系驾校管理员');
        }
        if(strstr($coo['reserve_warn'],'status1')){
            $this_time = time();
            $warn_start_time = date('Y-m-d '.$coo['warn_start_time'],time());
            if($coo['warn_date_status'] == 1){
                $warn_end_time = date('Y-m-d '.$coo['warn_end_time'],strtotime('+1 day'));
            }
            if($request_date ==  $date){
                $request_datetime = time();
            }

            if(($this_time > strtotime($warn_start_time) && $this_time < strtotime($warn_end_time) )&& ($request_datetime > strtotime($warn_start_time) && $request_datetime < strtotime($warn_end_time))){
                if($coo['warn_continue'] == 1){
                    $this->error($coo['warn_text2']);
                }else{
                    $coach = $this->getcoachinfo($coach);
                    if($coach['cooperation_id'] == 244 && $student['cooperation_id'] == 244 && $student['tuition'] >=3000 && $pick_up_status == 1){
                        $coach['pick_up_status'] = 1;
                    }

                    //redis获取场馆预约时间段如果有教员单独配置时间人数
                    $reservetime = Db::name('coach_config_time_people')->where('coach_id',$coach['id'])->select();
                    //获取教员当天的请假情况
                    $where_coach['coach_id'] = $coach_id;
                    $where_coach['starttime'] = ['>=',$request_datetime-(24*3600*7)];
                    $where_coach['endtime'] = ['<',$request_datetime2+(24*3600*7)];
                    $leavetime = $this->coachleave->where($where_coach)->select();
                    $timelist = $reservetime;
                    if($leavetime){
                        $timelist = $this->get_time($reservetime,$leavetime,$request_date);
                    }
                    $open_time = Db::name('open_time')->where(['coach_id'=>$coach['id'],'starttime'=>['between',[$request_datetime,$request_datetime2]]])->select();
                    $coach['detail_time_list'] = $this->get_detail_time_list($timelist,$reservetime,$request_date,$coach,$open_time);
            
                    $this->error($coo['warn_text'],$coach,2);
                }
            }
        }
        
        $coach = $this->getcoachinfo($coach);
        
        if($coach['cooperation_id'] == 244 && $student['cooperation_id'] == 244 && $student['tuition'] >=3000 && $pick_up_status == 1){
            $coach['pick_up_status'] = 1;
        }

        //redis获取场馆预约时间段如果有教员单独配置时间人数
        $reservetime = Db::name('coach_config_time_people')->where('coach_id',$coach['id'])->select();
        //获取教员当天的请假情况
        $where_coach['coach_id'] = $coach_id;
        $where_coach['starttime'] = ['>=',$request_datetime-(24*3600*7)];
        $where_coach['endtime'] = ['<',$request_datetime2+(24*3600*7)];
        $leavetime = $this->coachleave->where($where_coach)->select();
        $timelist = $reservetime;
        if($leavetime){
            $timelist = $this->get_time($reservetime,$leavetime,$request_date);
        }
        $open_time = Db::name('open_time')->where(['coach_id'=>$coach['id'],'starttime'=>['between',[$request_datetime,$request_datetime2]]])->select();
        $coach['detail_time_list'] = $this->get_detail_time_list($timelist,$reservetime,$request_date,$coach,$open_time);
 
        $this->success('返回成功',$coach);
    }
    


    /**
     * 实车教员预约详情
    */
    public function coachscdetails(){
        $params = $this->request->post();
        // $params['coach_id']= 'CTN20220713170814623428';
        // $params['request_date'] =  '2022-08-11';
        // $params['stu_id'] =  'CSN20210425112607670801';
        // $params['car_type'] =  'cartype1';
        if(empty($params['coach_id']) || empty($params['request_date']) || empty($params['car_type']) || empty($params['stu_id']) ){
            $this->error('参数缺失');
        }
        $coach_id = $params['coach_id'];
        $request_date = $params['request_date'];
        $car_type = $params['car_type'];

        $date = date("Y-m-d");
        if($request_date < $date){
            $this->error('当前时间已过期，无法查看');
        }
        //请求日期
        $request_datetime = strtotime($request_date);
        //请求第二天日期
        $request_datetime2 = $request_datetime+86400;
        $student = $this->student->where(['stu_id'=>$params['stu_id']])->find();
        // $coach_sc_keer = $student['coach_sc_keer'];
        // $coach_sc_kesan = $student['coach_sc_kesan'];

        //预约验证
        $this->reserve_validate($student);

        $coach = $this->coachsc->with('space')->where('coach_id',$coach_id)->find();
        $coach['space']['pick_up_status'] = (int)$coach['space']['pick_up_status'];

        $pick_up_status = $coach['space']['pick_up_status'];
        // $space = $this->space->where('id',$coach['space_id'])->find();
        $coach = $this->getcoachinfo($coach);
        // $coach['pick_up_status'] = $coach['space']['pick_up_status'];
        $coach['pick_up_status'] = 0;
        if($coach['cooperation_id'] == 244 && $student['cooperation_id'] == 244 && $student['tuition'] >3000 && $pick_up_status == 1){
            $coach['pick_up_status'] = 1;
        }
        // var_dump($coach_sc_keer,$coach_sc_kesan);exit;
        $subject_type = explode(',',$coach['subject_type']);
        $coach['subject_type'] = [];
        if(in_array('subject2',$subject_type)){
            array_push($coach['subject_type'],['value'=>'科目二','name'=>'subject2']);
        }
        if(in_array('subject3',$subject_type)){
            array_push($coach['subject_type'],['value'=>'科目三','name'=>'subject3']);
        }
        $coo = $this->cooperation->where(['cooperation_id'=>$coach['cooperation_id']])->find();

        if(strstr($coo['reserve_warn'],'status1')){
            $this_time = time();
            $warn_start_time = date('Y-m-d '.$coo['warn_start_time'],time());
            if($coo['warn_date_status'] == 1){
                $warn_end_time = date('Y-m-d '.$coo['warn_end_time'],strtotime('+1 day'));
            }
            if($request_date ==  $date){
                $request_datetime = time();
            }
            if(($this_time > strtotime($warn_start_time) && $this_time < strtotime($warn_end_time) )&& ($request_datetime > strtotime($warn_start_time) && $request_datetime < strtotime($warn_end_time))){
                if($coo['warn_continue'] == 1){
                    $this->error($coo['warn_text2']);
                }else{
                    //redis获取场馆预约时间段如果有教员单独配置时间人数
                    $reservetime = Db::name('coach_sc_config_time_people')->where('coach_id',$coach['id'])->select();
                    //获取教员当天的请假情况
                    $where_coach['coach_id'] = $coach_id;
                    $where_coach['starttime'] = ['>=',$request_datetime-(24*3600*7)];
                    $where_coach['endtime'] = ['<',$request_datetime2+(24*3600*7)];
                    $leavetime = $this->coachscleave->where($where_coach)->select();
                    $timelist = $reservetime;
                    if($leavetime){
                        $timelist = $this->get_time($reservetime,$leavetime,$request_date);
                    }
                    $open_time = Db::name('open_time')->where(['coach_id'=>$coach['id'],'starttime'=>['between',[$request_datetime,$request_datetime2]]])->select();

                    $coach['detail_time_list'] = $this->get_sc_detail_time_list($timelist,$reservetime,$request_date,$coach,$open_time);

                    $this->error($coo['warn_text'],$coach,2);
                }
            }
        }
        //redis获取场馆预约时间段如果有教员单独配置时间人数
        $reservetime = Db::name('coach_sc_config_time_people')->where('coach_id',$coach['id'])->select();
        //获取教员当天的请假情况
        $where_coach['coach_id'] = $coach_id;
        $where_coach['starttime'] = ['>=',$request_datetime-(24*3600*7)];
        $where_coach['endtime'] = ['<',$request_datetime2+(24*3600*7)];
        $leavetime = $this->coachscleave->where($where_coach)->select();
        $timelist = $reservetime;
        if($leavetime){
            $timelist = $this->get_time($reservetime,$leavetime,$request_date);
        }
        $open_time = Db::name('open_time')->where(['coach_id'=>$coach['id'],'starttime'=>['between',[$request_datetime,$request_datetime2]]])->select();

        $coach['detail_time_list'] = $this->get_sc_detail_time_list($timelist,$reservetime,$request_date,$coach,$open_time);

        $this->success('返回成功',$coach);
    }


    public function coachscdetailstest(){
        $params = $this->request->post();
        // $params['coach_id']= 'CTN20220713170814623428';
        // $params['request_date'] =  '2022-12-29';
        // $params['stu_id'] =  'CSN20210425112607670801';
        // $params['car_type'] =  'cartype1';
        if(empty($params['coach_id']) || empty($params['request_date']) || empty($params['car_type']) || empty($params['stu_id']) ){
            $this->error('参数缺失');
        }
        $coach_id = $params['coach_id'];
        $request_date = $params['request_date'];
        $car_type = $params['car_type'];

        $date = date("Y-m-d");
        if($request_date < $date){
            $this->error('当前时间已过期，无法查看');
        }
        //请求日期
        $request_datetime = strtotime($request_date);
        //请求第二天日期
        $request_datetime2 = $request_datetime+86400;
        $student = $this->student->where(['stu_id'=>$params['stu_id']])->find();
        // $coach_sc_keer = $student['coach_sc_keer'];
        // $coach_sc_kesan = $student['coach_sc_kesan'];

        $coach = $this->coachsc->with('space')->where('coach_id',$coach_id)->find();
        
        //预约验证
        $this->reserve_validate($student);

        $pick_up_status = $coach['space']['pick_up_status'];
        // $space = $this->space->where('id',$coach['space_id'])->find();
        $coach = $this->getcoachinfo($coach);
        // $coach['pick_up_status'] = $coach['space']['pick_up_status'];
        $coach['pick_up_status'] = 0;
        if($coach['cooperation_id'] == 244 && $student['cooperation_id'] == 244 && $student['tuition'] >3000 && $pick_up_status == 1){
            $coach['pick_up_status'] = 1;
        }
        // var_dump($coach_sc_keer,$coach_sc_kesan);exit;
        $subject_type = explode(',',$coach['subject_type']);
        $coach['subject_type'] = [];
        if(in_array('subject2',$subject_type)){
            array_push($coach['subject_type'],['value'=>'科目二','name'=>'subject2']);
        }
        if(in_array('subject3',$subject_type)){
            array_push($coach['subject_type'],['value'=>'科目三','name'=>'subject3']);
        }
        
        //redis获取场馆预约时间段如果有教员单独配置时间人数
        $reservetime = Db::name('coach_sc_config_time_people')->where('coach_id',$coach['id'])->select();
        //获取教员当天的请假情况
        $where_coach['coach_id'] = $coach_id;
        $where_coach['starttime'] = ['>=',$request_datetime-(24*3600*7)];
        $where_coach['endtime'] = ['<',$request_datetime2+(24*3600*7)];
        $leavetime = $this->coachscleave->where($where_coach)->select();
        $timelist = $reservetime;
        if($leavetime){
            $timelist = $this->get_time($reservetime,$leavetime,$request_date);
        }
        $open_time = Db::name('open_time')->where(['coach_id'=>$coach['id'],'starttime'=>['between',[$request_datetime,$request_datetime2]]])->select();

        $coach['detail_time_list'] = $this->get_sc_detail_time_list($timelist,$reservetime,$request_date,$coach,$open_time);

        $this->success('返回成功',$coach);
    }

    public function reserve_validate($student)
    {
        $cooperation = $this->cooperation->where(['cooperation_id'=>$student['cooperation_id']])->find();
        if($cooperation['keer_pass_sc'] && $cooperation['keer_pass_time'] >0){
            $where_keer['stu_id'] = $student['stu_id'];
            $where_keer['subject_type'] = 'subject2';
            $where_keer['score'] = ['>=',90];
            $keer_time = $this->reportcard->where($where_keer)->count();
            if($keer_time < $cooperation['keer_pass_time']){
                $this->error('根据当前配置需要您通过'.$cooperation['keer_pass_time'].'次模拟器后才可预约实车教练,当前你通过模拟器次数为'.$keer_time);
            }
        }

        if($cooperation['kesan_pass_sc'] && $cooperation['kesan_pass_time'] >0){
            $where_kesan['stu_id'] = $student['stu_id'];
            $where_kesan['subject_type'] = 'subject3';
            $where_kesan['score'] = ['>=',90];
            $keer_time = $this->reportcard->where($where_kesan)->count();
            if($keer_time < $cooperation['keer_pass_time']){
                $this->error('根据当前配置需要您通过'.$cooperation['keer_pass_time'].'次模拟器后才可预约实车教练,当前你通过模拟器次数为'.$keer_time);
            }
        }
    }


    public function get_sc_detail_time_list($timelist,$reservetime,$request_date,$coach,$open_time){
        $time = array_column($open_time,'starttime');

        $Day = $request_date;
        $detail_time_list = $timelist;
        $list = [];
        foreach($reservetime as $k=>$v){
            $list[$k]['starttimes'] = substr($v['starttimes'],0,5);
            $list[$k]['endtimes'] = substr($v['endtimes'],0,5);
            if(in_array($v,$timelist)){
                if(time() > strtotime($Day.$v['starttimes'])){
                    $list[$k]['empty_num'] = 0;
                    continue;
                }
                $number = $v['number'];
                $where['coach_id'] = $coach['coach_id'];
                $where['reserve_starttime'] = strtotime($Day.$v['starttimes']);
                $where['reserve_endtime'] = strtotime($Day.$v['endtimes']);
                $where['order_status'] = ['neq','cancel_refunded'];
                $order_number = $this->ordersc->where($where)->count();
                $list[$k]['empty_num'] = $number-$order_number;
            }else{
                $list[$k]['empty_num'] = 0;
            }
            if(in_array(strtotime($Day.$v['starttimes']),$time)){
                $list[$k]['empty_num'] = 0;
            }
        }
        sort($list);
        return $list;
    }

    /**
     * 获取请求日中教员每个时间段可预约情况
     */
    public function get_detail_time_list($timelist,$reservetime,$request_date,$coach,$open_time){
        $time = array_column($open_time,'starttime');

        $Day = $request_date;
        $list = [];
        foreach($reservetime as $k=>$v){
            $list[$k]['starttimes'] = substr($v['starttimes'],0,5);
            $list[$k]['endtimes'] = substr($v['endtimes'],0,5);
            if(in_array($v,$timelist)){
                if(time() > strtotime($Day.$v['starttimes'])){
                    $list[$k]['empty_num'] = 0;
                    continue;
                }
                $number = $v['number'];
                // $where['car_type '] = $car_type;
                $where['coach_id'] = $coach['coach_id'];
                $where['reserve_starttime'] = strtotime($Day.$v['starttimes']);
                $where['reserve_endtime'] = strtotime($Day.$v['endtimes']);
                $where['order_status'] = ['neq','cancel_refunded'];
                $order_number = $this->order->where($where)->count();
                $list[$k]['empty_num'] = $number-$order_number;
            }else{
                $list[$k]['empty_num'] = 0;
            }
            if(in_array(strtotime($Day.$v['starttimes']),$time)){
                $list[$k]['empty_num'] = 0;
            }
        }
        sort($list);
        return $list;
    }

    public function getcoachinfo($coach){
        $info['id'] = $coach['id'];
        $info['coach_id'] = $coach['coach_id'];
        $info['name'] = $coach['name'];
        $info['photoimage'] = $coach['photoimage'];
        $info['phone'] = $coach['phone'];
        $info['space_id'] = $coach['space_id'];
        $info['cooperation_id'] = $coach['cooperation_id'];
        $info['car_type'] = $coach['car_type'];
        $info['teach_state'] = $coach['teach_state'];
        $info['pick_up_maxmile'] = $coach['space']['pick_up_maxmile'];
        $info['pick_up_status'] = $coach['space']['pick_up_status'];
        $info['day'] = $coach['space']['day'];
        $info['lng'] = $coach['space']['lng'];
        $info['lat'] = $coach['space']['lat'];
        $info['coach_remark'] = $coach['coach_remark'];
        $info['subject_type'] = $coach['subject_type'];
        return $info;
    }

    /**
     * 可预约天数判断
     */
    function day_limit($reserveday,$datetime1,$datetime2)
    {
        $reserveday = $reserveday;
        $datetime1 = date_create($datetime1);
        $datetime2 = date_create($datetime2);
        $interval = date_diff($datetime2, $datetime1);
        $res = $interval->format('%R%a');
        if( $res >=0 && $res<=$reserveday){
            return 1;
        }else{
            return 0;
        }
    }

    /**
     * 判断当前时间是否在时间段内，如果不在，则将当前时间段拿出来,返回能预约的时间
     */
    public function get_time($reservetime,$leavetime,$datetime1_date){
        
        $timelist = $reservetime;
        foreach($leavetime as $k=>$v){
            foreach($reservetime as $kk=>$vv){
                //获取哪一天的请假
                $Day = $datetime1_date;
                //可预约开始时间
                $timeBegin = strtotime($Day.$vv['starttimes']);
                //可预约结束时间
                $timeEnd = strtotime($Day.$vv['endtimes']);
                //请假开始时间
                // var_dump($v);exit;
                $curr_time1 = $v['starttime'];
                //请假结束时间
                $curr_time2 = $v['endtime'];
                //请假时间是否在预约时间段之间
                if(($curr_time1 > $timeBegin && $curr_time1 < $timeEnd) || ($curr_time2 > $timeBegin && $curr_time2 <= $timeEnd) || ($timeBegin > $curr_time1 && $timeEnd < $curr_time2)){
                    unset($timelist[$kk]);
                    // break ;
                }
            }
        }
        $timelist = array_values($timelist);
        return $timelist;
    }

    function getcoach($space_id,$car_type){
        $where['space_id'] = $space_id;
        $where['car_type'] = ['like','%'.$car_type.'%'];
        $where['teach_state'] = 'yes';
        $coach = $this->coach->where($where)->field(['id','name','coach_id','cooperation_id','phone','photoimage','car_type','teach_state','praise'])->select();
        return $coach;
    }


    function getcoachsc($space_id,$car_type){
        $where['space_id'] = $space_id;
        $where['car_type'] = ['like','%'.$car_type.'%'];
        $where['teach_state'] = 'yes';
        $coach = $this->coachsc->where($where)->field(['id','name','coach_id','cooperation_id','phone','photoimage','car_type','teach_state','praise'])->select();
        return $coach;
    }

    /**
     * 馆场距离排序
     */
    function getlist($list){
        $b = $list;
        $a = [];
        foreach($list as $key=>$val){
            $a[] = $val['distance'];//这里要注意$val['nums']不能为空，不然后面会出问题
        }
        //$a先排序
        sort($a);
        $a = array_flip($a);
        $result = [];
        foreach($b as $k=>$v){
            $temp1 = $v['distance'];
            $temp2 = $a[$temp1];
            $result[$temp2] = $v;
        }
        //这里还要把$result进行排序，健的位置不对
        ksort($result);
        //然后就是你想看到的结果了
        return $result;
    }


    function coach_sc_ordertest($coach,$request_datetime,$car_type){
        $count = 0;
        $num  = 0;
        $Day = $request_datetime;
        $timelist = $coach[0]['timelist'];
        foreach($coach['timelist'] as $k=>$v){
            $people_upper = $v['number'];
            if(time() > strtotime(date('Y-m-d'.$v['starttimes'],$Day))){
                $timelist[$k]['number'] = 0;
                continue;
            }
            $date = date('Y-m-d',$request_datetime);
            $starttime = strtotime($date.$v['starttimes']);
            $endtime = strtotime($date.$v['endtimes']);

            $where['reserve_starttime'] =  $starttime;
            $where['reserve_endtime'] = $endtime;
            $where['coach_id'] = $coach['coach_id'];
            $where['order_status'] = ['neq','cancel_refunded'];
            $where['car_type'] = $car_type;
            $order_count = $this->ordersc->where($where)->count();
            // if(){

            // }
            $count += $order_count;
            $people_upper = $people_upper-$order_count;
            $timelist[$k]['number'] = $people_upper;

            unset($people_upper);
        }
        return $timelist;
    }

    /**
     * 教员当天所有时间段订单情况
     */
    function coach_sc_order($coach,$request_datetime,$car_type){
        $count = 0;
        $num  = 0;

        $Day = $request_datetime;
        $timelist = $coach['timelist'];
        foreach($coach['timelist'] as $k=>$v){
            $people_upper = $v['number'];
            if(time() > strtotime(date('Y-m-d'.$v['starttimes'],$Day))){
                $timelist[$k]['number'] = 0;
                continue;
            }
            $date = date('Y-m-d',$request_datetime);
            $starttime = strtotime($date.$v['starttimes']);
            $endtime = strtotime($date.$v['endtimes']);

            $where['reserve_starttime'] =  $starttime;
            $where['reserve_endtime'] = $endtime;
            $where['coach_id'] = $coach['coach_id'];
            $where['order_status'] = ['neq','cancel_refunded'];
            $where['car_type'] = $car_type;
            $order_count = $this->ordersc->where($where)->count();
            // if(){

            // }
            $count += $order_count;
            $people_upper = $people_upper-$order_count;
            $timelist[$k]['number'] = $people_upper;

            unset($people_upper);
        }
        return $timelist;
    }

     /**
     * 教员当天所有时间段订单情况
     */
    function coach_order($coach,$request_datetime,$car_type){
        $count = 0;
        $num  = 0;
        $Day = $request_datetime;
        $timelist = $coach['timelist'];
        foreach($coach['timelist'] as $k=>$v){
            $people_upper = $v['number'];
            if(time() > strtotime(date('Y-m-d'.$v['starttimes'],$Day))){
                $timelist[$k]['number'] = 0;
                continue;
            }
            $date = date('Y-m-d',$request_datetime);
            $starttime = strtotime($date.$v['starttimes']);
            $endtime = strtotime($date.$v['endtimes']);

            $where['reserve_starttime'] =  $starttime;
            $where['reserve_endtime'] = $endtime;
            $where['coach_id'] = $coach['coach_id'];
            $where['order_status'] = ['neq','cancel_refunded'];
            // $where['car_type'] = $car_type;
            $order_count = $this->order->where($where)->count();
            $count += $order_count;
            $people_upper = $people_upper-$order_count;
            $timelist[$k]['number'] = $people_upper;

            unset($people_upper);
        }
        return $timelist;
    }
}

    