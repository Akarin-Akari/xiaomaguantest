<?php


namespace app\admin\controller\coachconfig;

use app\common\controller\Backend;
use Exception;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\Session;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Paike extends Backend
{

    /**
     * Coach模型对象
     * @var \app\admin\model\Coach
     */
    protected $model = null;
    protected $order = null;
    protected $space = null;
    protected $space_lists = null;
    protected $command = null;

    protected $noNeedRight = ['day','detail','operation','add_paike'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Coach;
        $this->order = new \app\admin\model\Order;
        $this->space = new \app\admin\model\Space;
        $this->space_lists = $_SESSION['think']['admin']['space_list'];
        $this->command = new \app\admin\controller\Command;
        $this->view->assign("spaceList", $this->command->getSpaceList());
        $this->view->assign("kebiao", $this->kebiao());

        $this->view->assign("getday", $this->getday());
    }

    public function import()
    {
        parent::import();
    }
    
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        // Session::set('coach_id',0);exit;

        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $space_list = $_SESSION['think']['admin']['space_list'];
            $where_space['space.id'] = ['in',$space_list];
            $list = $this->model
                    ->with(['admin','space'])
                    ->where($where)
                    ->where($where_space)
                    ->order($sort, $order)
                    ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id','name']);
				$row->visible(['space']);
				$row->getRelation('space')->visible(['space_name']);
            }
            $result = array("total" => $list->total(), "rows" => $list->items());
            
            return json($result);
        }
        return $this->view->fetch('index');

    }

    public function add_paike()
    {
        $coach_id = $_GET['coach_id'];
        $id  = $_GET['id'];
        $day  = $_GET['day'];
        $row['coach_id'] = $coach_id ;
        $time_list = Db::name('coach_config_time_people')->where(['coach_id'=>$coach_id])->select();
        $row['reserve_starttime'] = date('Y-m-d',strtotime('+'.$day.' day')).$time_list[$id]['starttimes'];
        $row['reserve_endtime'] = date('Y-m-d',strtotime('+'.$day.' day')).$time_list[$id]['endtimes'];
        $row['coach_name'] = $this->model->where(['id'=>$coach_id])->find()['name'];
        $row['coach_id'] = $coach_id;
        $this->view->assign("row",$row);
        return $this->view->fetch();
    }

    public function submit_paike()
    {
        if($_POST){
            if(empty($_POST['phone'])){
                $this->error('请添加学员');
            }
            $student = Db::name('student')->where(['phone'=>$_POST['phone']])->find();
            $coach = $this->model->where(['id'=>$_POST['coach_id']])->find();

            if(!$student){
                $this->error('学员手机号错误,请先添加学员信息');
            }
            $order['ordernumber'] = 'CON'.date("YmdHis") . mt_rand(100000, 999999);
            $order['stu_id'] = $student['stu_id'];
            $order['ordertype'] = 1;
            $order['cooperation_id'] = $coach['cooperation_id'];
            $order['space_id'] = $coach['space_id'];
            $order['coach_id'] = $coach['coach_id'];
            
            $order['reserve_starttime'] = $_POST['reserve_starttime'];
            $order['reserve_endtime'] = $_POST['reserve_endtime'];
            $order['subject_type'] = 'subject2';
            $order['order_status'] = 'paid';
            $order['car_type'] = $student['car_type'];
            $order['remarks'] = '';
            
            // $order['student_boot_type'] = 1;
            // $order['payModel'] = 2;
            $order['evaluation'] = 0;

            $res = $this->order->save($order);
            // $this->order->insert();
            $this->success('返回成功');
        }
    }

    public function kebiao()
    {
        // $day = date('Y-m-d',time());
        $ids = Session::get('coach_id');
        $day = Session::get('day');
        // var_dump($ids,Session::get('day'));
        $coach = $this->model->order('id desc')->find();
        if(!$ids){
            $ids = $coach['id'];
            Session::set('coach_id',$ids);
        }
        $choose_coach =  $this->model->where(['id'=>$ids])->find();
        Session::set('coach_name',$choose_coach['name']);

        if(!$day){
            Session::set('day',0);
        }
        // Session::set('day',0);
        // Session::set('coach_id',$ids);
        $day = date('Y-m-d',strtotime('+'.$day.' day'));
        $coach_list = $this->gettime($ids,$day);
        $arr['time_list'] = $coach_list;
        $arr['id'] = $ids;
        $arr['day'] = 0;
        return $arr;
    }

    public function detail($ids)
    {
        // var_dump($ids);
        Session::set('coach_id',$ids);
        $day = Session::get('day');

        $choose_coach =  $this->model->where(['id'=>$ids])->find();
        // Session::set('coach_name',$choose_coach['name']);

        $day = date('Y-m-d',strtotime('+'.$day.' day'));
        $coach_list = $this->gettime($ids,$day);

        $arr['time_list'] = $coach_list;
        $arr['id'] = $ids;
        $arr['choose_coach'] = $choose_coach['name'];
        $arr['day'] = Session::get('day');
        $this->success($arr);
    }

    public function day()
    {
        $day = $_POST['day'];
        // var_dump($day);exit;
        Session::set('day',$day);
        $ids = Session::get('coach_id');
        // var_dump($ids,$day);exit;
        $day = date('Y-m-d',strtotime('+'.$day.' day'));
        $coach_list = $this->gettime($ids,$day);
        // $this->view->assign("kebiao", $coach_list);
        $arr['time_list'] = $coach_list;
        $arr['id'] = $ids;
        $arr['day'] = $_POST['day'];
        $this->success($arr);
    }


    public function operation()
    {
        $id = $_POST['id'];
        $item = $_POST['item'];
        $inner = $_POST['inner'];
        $day = $_POST['day'];
        $config_time = Db::name('coach_config_time_people')->where(['coach_id'=>$id])->select();
        $time = strtotime(date('Y-m-d',strtotime('+'.$day.' day')).$config_time[$item]['starttimes']);
        
        if($inner == '开启状态'){
            $res = Db::name('open_time')->where(['coach_id'=>$id,'starttime'=>$time])->delete();
        }else{
            $coach = $this->model->where(['id'=>$id])->find();
            $add['cooperation_id'] = $coach['cooperation_id'];
            $add['space_id'] = $coach['space_id'];
            $add['coach_id'] = $id;
            $add['starttime'] = $time;
            $add['createtime'] = time();
            // $add['updatetime'] = time();
            $res = Db::name('open_time')->insert($add);
        }
        if($res){
            $day = date('Y-m-d',strtotime('+'.$day.' day'));
            $coach_list = $this->gettime($id,$day);
            $arr['time_list'] = $coach_list;
            $arr['id'] = $id;
            $arr['day'] = $_POST['day'];
            $this->success($arr);
        }
        $this->error();
    }

    


    public function gettime($ids,$day)
    {
        $datetime_date = $day;
        $datetime = strtotime($datetime_date);
        $coach = $this->model->where(['id'=>$ids])->find();
        $leavetime = Db::name('coach_leave')->where(['coach_id'=>$coach['coach_id']])->select();
        $coach_reservetime = Db::name('coach_config_time_people')->where(['coach_id'=>$coach['id']])->select();
        if($leavetime){
            $coach['timelist'] = $this->get_time($coach_reservetime,$leavetime,$datetime_date);
        }else{
            $coach['timelist'] = $coach_reservetime;
        }

        $coach_list = $this->coach_order($coach,$datetime);
        $where['starttime'] = ['between',[strtotime($day),strtotime('+1 day',strtotime($day))-1]];
        $where['coach_id'] = $ids;
        // var_dump($where);
        $open_time = Db::name('open_time')->where($where)->select();
        // var_dump($open_time);exit;
        // if($open_time){
        $coach_list = $this->open_time($coach_list,$open_time,$day);
        // }
        return $coach_list;
    }

    public function open_time($coach_list,$open_time,$day)
    {
        // var_dump($coach_list,$open_time,$day);
        // exit;
        // $timelist = $coach_list;
        foreach($coach_list as $k=>$v){
            $coach_list[$k]['time_status'] = '关闭状态';
            foreach($open_time as $kk=>$vv){
                //可预约开始时间
                $timeBegin = strtotime($day.$v['starttimes']);
                // var_dump($timeBegin,$vv['starttime']);

                if($timeBegin == $vv['starttime']){
                    $coach_list[$k]['time_status'] = '开启状态';
                    $coach_list[$k]['status'] = '关闭中';
                }
            }
            if(time() > strtotime($day.$v['starttimes'])){
                $coach_list[$k]['time_status'] = '';
                // continue;
            }
        }
        // var_dump($coach_list);
        return $coach_list;
    }

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

    /**
     * 教员当天所有时间段订单情况
     */
    function coach_order($coach,$request_datetime){
        $count = 0;
        $Day = $request_datetime;
        $timelist = $coach['timelist'];
        // var_dump(123);

        foreach($coach['timelist'] as $k=>$v){
            $people_upper = $v['number'];
            $timelist[$k]['status'] = '开启中';
            // var_dump(strtotime(date('Y-m-d'.$v['starttimes'],$Day)));
            
            if(time() > strtotime(date('Y-m-d'.$v['starttimes'],$Day))){
                $timelist[$k]['status'] = '关闭中';
                // continue;
            }
            $date = date('Y-m-d',$request_datetime);
            $starttime = strtotime($date.$v['starttimes']);
            $endtime = strtotime($date.$v['endtimes']);

            $where['reserve_starttime'] =  $starttime;
            $where['reserve_endtime'] = $endtime;
            $where['coach_id'] = $coach['coach_id'];
            $where['order_status'] = [['neq','cancel_refunded'],['neq','cancel_unrefunded']];

            $where['car_type'] = 'cartype1';
            $timelist[$k]['cartype1'] = Db::name('order')->where($where)->count();
            $where['car_type'] = 'cartype2';
            $timelist[$k]['cartype2'] = Db::name('order')->where($where)->count();
            $count = $timelist[$k]['cartype1']+$timelist[$k]['cartype2'];
            $people_upper = $people_upper-$count;
            $timelist[$k]['number'] = $v['number'];
            $timelist[$k]['people_upper'] = $people_upper;
            
            // var_dump(strtotime(date('Y-m-d'.$v['starttimes'],$Day)));
        }
        // var_dump($timelist);exit;

        return $timelist;
    }
    public function getday()
    {
        $arr = [];
        array_push($arr,date('Y年m月d日',time()).'&nbsp;星期'.$this->n2c(date('N',time())));
        array_push($arr,date('Y年m月d日',strtotime('+1 day')).'&nbsp;星期'.$this->n2c(date('N',strtotime('+1 day'))));
        array_push($arr,date('Y年m月d日',strtotime('+2 day')).'&nbsp;星期'.$this->n2c(date('N',strtotime('+2 day'))));
        array_push($arr,date('Y年m月d日',strtotime('+3 day')).'&nbsp;星期'.$this->n2c(date('N',strtotime('+3 day'))));
        array_push($arr,date('Y年m月d日',strtotime('+4 day')).'&nbsp;星期'.$this->n2c(date('N',strtotime('+4 day'))));
        array_push($arr,date('Y年m月d日',strtotime('+5 day')).'&nbsp;星期'.$this->n2c(date('N',strtotime('+5 day'))));
        // array_push($arr,date('Y年m月d日',strtotime('+6 day')).'&nbsp;星期'.$this->n2c(date('N',strtotime('+6 day'))));
        return $arr;
    }

    function n2c($x){
        $arr_n = array("一","二","三","四","五","六","七");
        return $arr_n[$x-1];
    }
}