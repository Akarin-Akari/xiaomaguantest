<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\Upload;
use PDO;
use think\cache;
use think\Db;
/**
 * 开机流程所需接口
 */
class AiBoot extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    protected $common =  null;
    protected $jiangxi = null;
    protected $qt_boot = null;
    protected $machineai = null;
    protected $terminal = null;
    protected $car = null;
    protected $place = null;
    protected $ordersc = null;
    protected $studyprocessai = null;
    protected $keersort = null;
    protected $kesansort = null;
    protected $faceimage =null;
    protected $student = null;
    protected $intentstudent = null;
    protected $warnexamai = null;
    protected $reportcardai = null;
    protected $trainstatisticai = null;
    protected $temordersc = null;
    public function _initialize()
    {
        parent ::_initialize();
        $this->common = new \app\api\controller\Common;
        $this->qt_boot = new \app\api\controller\student\QtBoot;
        $this->machineai = new \app\admin\model\Machineai;
        $this->terminal = new \app\admin\model\Terminal;
        $this->car = new \app\admin\model\Car;
        $this->place = new \app\admin\model\Place;
        $this->ordersc = new \app\admin\model\Ordersc;
        $this->temordersc = new \app\admin\model\Temordersc;
        $this->studyprocessai = new \app\admin\model\Studyprocessai;
        $this->reportcardai = new \app\admin\model\Reportcardai;
        $this->keersort = new \app\admin\model\Keersort;
        $this->kesansort = new \app\admin\model\Kesansort;
        $this->student = new \app\admin\model\Student;
        $this->intentstudent = new \app\admin\model\IntentStudent;
        $this->trainstatisticai = new \app\admin\model\Trainstatisticai;
        $this->warnexamai = new \app\admin\model\Warnexamai;
    }


    /**
     * 开机获取logo
     */
    public function get_company_info()
    { 
        $params = $this->request->post();
        // Cache::set('get_company_info',$params);
        // $params['machine_code'] = 'JQR001';
        // $params['LocalserialNum'] = 'SZ_GanganZhong01';
        // $params['MainVersions'] = '1.1.1.2';
        // $params['LocalVersions'] = '1.1.1.3';
        // $params['UnitySceneName'] = 'Demo';
        // $params['ChineseSceneName'] = '场景1';
        // $params['updatetime'] = time();

        if(empty($params['machine_code']) || empty($params['LocalserialNum']) || empty($params['MainVersions']) || 
            empty($params['LocalVersions']) || empty($params['UnitySceneName']) || empty($params['ChineseSceneName'])){
            $this->error('参数缺失');
        }

        $machine_code = $params['machine_code'];
        unset($params['machine_code']);
        $this->machineai->where(['machine_code'=>$machine_code])->update($params);
        
        $res = $this->machineai->with('space')->where(['machine_code'=>$machine_code])->find();
        $info['info_msg'] = $res['space']['info_msg'];
        $info['logo_image'] = '/uploads/20211122/5f28562c9d9143d022e79cb6c403ded2.png';
        if($res['space']['logo_image']){
            $info['logo_image'] = $res['space']['logo_image'];
        }
        // $info['device'] = $device;
        $this->success('返回成功',$info);
    }

    function tets(){
        $res = Cache::get('ai_show');
        var_dump($res);
    }

    public function ai_show()
    {
        $params = $this->request->post();
        // Cache::set('ai_show',$params,3600);
        // $params['device'] = 'ZD001';
        // $params['LocalserialNum'] = 'SZ_GanganZhong01';
        // $params['MainVersions'] = '1.1.1.2';
        // $params['LocalVersions'] = '1.1.1.2';
        // $params['UnitySceneName'] = 'Demo';
        // $params['ChineseSceneName'] = '场景1';
        if(empty($params['device']) || empty($params['LocalserialNum']) || empty($params['MainVersions'])|| empty($params['LocalVersions'])||
             empty($params['UnitySceneName']) || empty($params['ChineseSceneName'])){
            $this->error('参数缺失');
        }
        $project_keer = ['xinshou','jichubj','fangxiangpan','qiting','daisu','chegan','cefangwei','daoche','quxian','zhijiaowan','banpo','ziyou','kaoshi'];
        $project_kesan = ['bianchaoting','jiajiandang','yejiandengguang','xian1','xian2','xian3','xian4','xian5','xian6','xian7','xian8','kaoshi3'];
        $where['machine_code'] = $params['device'];
        $terminal = $this->terminal->where($where)->find();
        $subject_type = $terminal['subject_type'];
        $space_id = $terminal['space_id'];
        unset($params['device']);
        $terminal = $this->terminal->where($where)->update($params);

        if($subject_type == 'subject2'){
            $project_arr = $project_keer;
        }elseif($subject_type == 'subject3'){
            $project_arr = $project_kesan;
        }
        $arr =[];
        foreach($project_arr as $v){
            $cefangwei_pass = $this->studyprocessai->where(['space_id'=>$space_id,'process_name'=>$v,'status'=>1])->count();
            $cefangwei_fail = $this->studyprocessai->where(['space_id'=>$space_id,'process_name'=>$v,'status'=>0])->count();
            if(($cefangwei_pass +$cefangwei_fail) !==0){
                $arr[$v] = number_format($cefangwei_pass*100/($cefangwei_pass +$cefangwei_fail),2);
            }else{
                $arr[$v] = strval(100);
            }
        }
        
        $machine_ai = $this->machineai->where(['space_id'=>$space_id])->field(['id'])->select();
        $machine_id= array_column($machine_ai->toArray(),'id');
        $where_car['machine_ai_id'] = ['in',$machine_id];
        $where_car['car.state'] = "1";
        $car = $this->car->with(['machineai'])->where($where_car)->select();
        $list['RegisteredVehicle'] = array_column($car->toArray(),'machine_code');;
        $list['ProjectAcceptanceRate'] = $arr;
        $this->success('返回成功',$list);

    }
    public function ai_showtest()
    {
        $params = $this->request->post();
        $params['device'] = 'ZD001';
        $params['LocalserialNum'] = 'XY_Taian01';
        $params['MainVersions'] = '1.1.8.4';
        $params['LocalVersions'] = '1.1.8.4';
        $params['UnitySceneName'] = 'JiangXi_XinYuB_2_1';
        $params['ChineseSceneName'] = '泰安 训练场';
        if(empty($params['device']) || empty($params['LocalserialNum']) || empty($params['MainVersions'])|| empty($params['LocalVersions'])||
             empty($params['UnitySceneName']) || empty($params['ChineseSceneName'])){
            $this->error('参数缺失');
        }
        $project_keer = ['xinshou','jichubj','fangxiangpan','qiting','daisu','chegan','cefangwei','daoche','quxian','zhijiaowan','banpo','ziyou','kaoshi'];
        $project_kesan = ['bianchaoting','jiajiandang','yejiandengguang','xian1','xian2','xian3','xian4','xian5','xian6','xian7','xian8','kaoshi3'];
        $where['machine_code'] = $params['device'];
        $terminal = $this->terminal->where($where)->find();
        $subject_type = $terminal['subject_type'];
        $space_id = $terminal['space_id'];
        unset($params['device']);
        // $terminal = $this->terminal->where($where)->update($params);

        if($subject_type == 'subject2'){
            $project_arr = $project_keer;
        }elseif($subject_type == 'subject3'){
            $project_arr = $project_kesan;
        }
        $arr =[];
        foreach($project_arr as $v){
            $cefangwei_pass = $this->studyprocessai->where(['space_id'=>$space_id,'process_name'=>$v,'status'=>1])->count();
            $cefangwei_fail = $this->studyprocessai->where(['space_id'=>$space_id,'process_name'=>$v,'status'=>0])->count();
            if(($cefangwei_pass +$cefangwei_fail) !==0){
                $arr[$v] = number_format($cefangwei_pass*100/($cefangwei_pass +$cefangwei_fail),2);
            }else{
                $arr[$v] = strval(100);
            }
        }
        
        $machine_ai = $this->machineai->where(['space_id'=>$space_id])->field(['id'])->select();
        $machine_id= array_column($machine_ai->toArray(),'id');
        $where_car['machine_ai_id'] = ['in',$machine_id];
        $where_car['car.state'] = "1";
        $car = $this->car->with(['machineai'])->where($where_car)->select();
        $list['RegisteredVehicle'] = array_column($car->toArray(),'machine_code');;
        $list['ProjectAcceptanceRate'] = $arr;
        $this->success('返回成功',$list);


    }

    /**
     * 学习记录
     */
    public function study_log()
    {
        $params = $this->request->post();
        // $params['ordernumber'] = 'CON20230813205946420483';
        // $params['kaochang'] = 'NC_Jinling01_NanChang_JinLingA_3_1';
        // $params['process_name'] = 'cefangwei';
        // $params['study_time'] = '200';
        // $params['deduct_points'] = [
        //     ['name'=>'侧方位',
        //     'weizhi'=>['lng','lat','方向'],
        //     'guize'=>'压线',
        //     'score'=>'20',],
        //     ['name'=>'扣分项目名称',
        //     'weizhi'=>'扣分位置',
        //     'guize'=>'扣分规则',
        //     'score'=>'20',]
        // ];
        // $params['trace'] = [['lng','lat','fangxiang'],['lng','lat','fangxiang']];
        // $params['status'] = '0';
        // Cache::set('study_log',$params,60*60);exit;
        if(empty($params['kaochang']) || empty($params['ordernumber'])|| empty($params['process_name'])|| empty($params['study_time'])){
            $this->error('参数缺失');
        }
        $order = $this->ordersc->where(['ordernumber'=>$params['ordernumber']])->find();

        if(array_key_exists('kaochang',$params)){
            $place = $this->place->where(['str_name'=>$params['kaochang']])->find();
            if($place){
                $params['place_id'] = $place['id'];
            }
            unset($params['kaochang']);
        }
        $params['stu_id'] = $order['stu_id'];
        $params['createtime'] = time();
        $params['space_id'] = $order['space_id'];
        $params['cooperation_id'] = $order['cooperation_id'];
        $res = null;
        if($params['study_time']<3600){
            unset($params['starttime']);
            $params['deduct_points'] = json_encode($params['deduct_points']);
            $params['trace'] = json_encode($params['trace']);
            // $params['trace'] = json_decode($params['trace'],1);
            $res = $this->studyprocessai->insert($params);
            
            if($res){
                $this->success('返回成功');
            }else{
                $this->error('上传成绩异常');
            }
        }
        
    }

    /**
     * 成绩单信息
     */
    public function reportcard()
    {
        $params = $this->request->post();
        // $params['ordernumber'] = 'CON20230813205946420483';
        // $params['subject_type'] = 'subject2';
        // $params['score'] = '80';
        // $params['kaochang'] = '福永考场';
        // $params['createtime'] = time();
        // $params['events'] = [
        //     [
        //         'subject'=>'侧方位扣分',
        //         'guize'=>'中途停车',
        //         'score'=>90,
        //         'pass_time'=> 30
        //     ],
        //     [
        //         'subject'=>'上车事项',
        //         'guize'=>'未系安全带',
        //         'score'=>20,
        //         'pass_time'=>''
        //     ]
        // ];

        if(empty($params['ordernumber'])|| empty($params['subject_type'])|| empty($params['kaochang'])){
            $this->error('参数缺失');
        }
        $events = $params['events'];

        unset($params['events']);
        $order = $this->ordersc->where(['ordernumber'=>$params['ordernumber']])->find();
        // var_dump($order->toArray());exit;
        if(!$order){
            $this->error('查询订单错误');
        }
        $params['stu_id'] = $order['stu_id'];
        $params['coach_id'] = $order['coach_id'];
        $params['machine_id'] = $order['machine_id'];
        $params['cooperation_id'] = $order['cooperation_id'];
        $params['space_id'] = $order['space_id'];
        $params['createtime'] = (int)$params['createtime'];
        $params['endtime'] = time();
        $params['pass_time'] = ($params['endtime'] - $params['createtime']);
        $report= $this->reportcardai->save($params);
        $id = $this->reportcardai->getLastInsID();
        $stu_train = $this->trainstatisticai->where(['stu_id'=>$params['stu_id']])->find();
        $warn_exam = $this->warnexamai->where(['cooperation_id'=>$order['cooperation_id'],'status'=>1])->find();
        if(!$stu_train && $warn_exam){
            $train_data['stu_id'] = $params['stu_id'];
            $train_data['cooperation_id'] = $params['cooperation_id'];
            $train_data['space_id'] = $params['space_id'];
            $train_data['createtime'] = $params['createtime'];
            
            if( $params['score'] >= $warn_exam['kesan_score']){
                $train_data['keer_hege'] = 1;
                $this->trainstatisticai->save($train_data);
            }else{
                $train_data['keer_hege'] = 0;
            }
        }elseif($stu_train && $warn_exam){
            if($params['subject_type'] == 'subject3'){
                //科目三
                $train_data['kesan_hege'] = ($stu_train['kesan_hege']+1);
            }else{
                //科目二
                $train_data['keer_hege'] = ($stu_train['keer_hege']+1);
            }
            $train_data['updatetime'] = $params['createtime'];

            $this->trainstatisticai->where(['stu_id'=>$params['stu_id']])->update($train_data);
        }
        
        if($events){
            foreach($events as $v){
                $v['report_card_id'] = $id;
                $v['car_type'] = 1;
                $res = Db::name('deduct_points')->insert($v);
            }
            $this->success('成绩单上传成功');
        }
        if($res && $report){
            $this->success('成绩单上传成功');
        }
        $this->error('添加失败');
    }

    /**
     * 成绩单信息
     */
    public function reportcardtest()
    {
        $params = $this->request->post();
        $params['ordernumber'] = 'CON20231101161503316398';
        $params['subject_type'] = 'subject2';
        $params['score'] = '80';
        $params['kaochang'] = '福永考场';
        $params['createtime'] = time();
        $params['events'] = [
            [
                'subject'=>'侧方位扣分',
                'guize'=>'中途停车',
                'score'=>90,
                'pass_time'=> 30
            ],
            [
                'subject'=>'上车事项',
                'guize'=>'未系安全带',
                'score'=>20,
                'pass_time'=>''
            ]
        ];

        if(empty($params['ordernumber'])|| empty($params['subject_type'])|| empty($params['kaochang'])){
            $this->error('参数缺失');
        }
        $events = $params['events'];

        unset($params['events']);
        $order = $this->ordersc->where(['ordernumber'=>$params['ordernumber']])->find();
        // var_dump($order->toArray());exit;
        if(!$order){
            $this->error('查询订单错误');
        }
        
        $params['stu_id'] = $order['stu_id'];
        $params['coach_id'] = $order['coach_id'];
        $params['machine_id'] = $order['machine_id'];
        $params['cooperation_id'] = $order['cooperation_id'];
        $params['space_id'] = $order['space_id'];
        $params['createtime'] = (int)$params['createtime'];
        $params['endtime'] = time();
        $params['pass_time'] = ($params['endtime'] - $params['createtime']);
        $report= $this->reportcardai->save($params);
        $id = $this->reportcardai->getLastInsID();
        $stu_train = $this->trainstatisticai->where(['stu_id'=>$params['stu_id']])->find();
        $warn_exam = $this->warnexamai->where(['cooperation_id'=>$order['cooperation_id'],'status'=>1])->find();
        if(!$stu_train && $warn_exam){
            $train_data['stu_id'] = $params['stu_id'];
            $train_data['cooperation_id'] = $params['cooperation_id'];
            $train_data['space_id'] = $params['space_id'];
            $train_data['createtime'] = $params['createtime'];
            
            if( $params['score'] >= $warn_exam['kesan_score']){
                $train_data['keer_hege'] = 1;
                $this->trainstatisticai->save($train_data);
            }else{
                $train_data['keer_hege'] = 0;
            }
        }elseif($stu_train && $warn_exam){
            if($params['subject_type'] == 'subject3'){
                //科目三
                $train_data['kesan_hege'] = ($stu_train['kesan_hege']+1);
            }else{
                //科目二
                $train_data['keer_hege'] = ($stu_train['keer_hege']+1);
            }
            $train_data['updatetime'] = $params['createtime'];

            $this->trainstatisticai->where(['stu_id'=>$params['stu_id']])->update($train_data);
        }
        
        if($events){
            foreach($events as $v){
                $v['report_card_id'] = $id;
                $v['car_type'] = 1;
                $res = Db::name('deduct_points')->insert($v);
            }
            $this->success('成绩单上传成功');
        }
        if($res && $report){
            $this->success('成绩单上传成功');
        }
        $this->error('添加失败');
    }

    public function getreportcard()
    {
        $res = Cache::get('reportcardtest');
        var_dump($res);
    }



    /**
     * 上机获取二维码
     */
    public function requestcode(){
        $params = $this->request->post();
        // $params['machine_code'] = 'JQR001';
        // Cache::set('requestcode',$params,60*60);
        if(empty($params['machine_code'])){
            $this->error('参数缺失');
        }

        $machine_code = $params['machine_code'];
        $machineai = $this->machineai->where('machine_code',$machine_code)->find();
        if($machineai){
            $data['student_code'] = $machineai['student_code'];
            if($machineai['space_id'] == 41){
                $res = Cache::get('machine_'.$machineai['machine_code']);
                if($res){
                    $data['student_code'] = '/uploads/code/student/3x3.png';
                }
            }
            $this->success('成功',$data);
        }else{
            $this->error('获取二维码失败');
        }

    }


    public function requestcodetest(){
        $params = $this->request->post();
        // $params['machine_code'] = 'JQR001';
        if(empty($params['machine_code'])){
            $this->error('参数缺失');
        }
        $machine_code = $params['machine_code'];
        $machineai = $this->machineai->where('machine_code',$machine_code)->find();
        if($machineai){
            $data['student_code'] = $machineai['student_code'];
            if($machineai['space_id'] == 41){
                $res = Cache::get('machine_'.$machineai['machine_code']);
                if($res){
                    $data['student_code'] = '/uploads/code/student/3x3.png';
                }
            }
            $this->success('成功',$data);
        }else{
            $this->error('获取二维码失败');
        }

    }



    /**
     * 循环获取登录确认后的学员信息
     */
    public function putmachine(){
        $params = $this->request->post();
        // $params['machine_code'] = 'JQR001';
        if(empty($params['machine_code'])){
            $this->error('参数缺失');
        }
        $machine_code = $params['machine_code'];
        // $res = Cache::get('machine_'.$machine_code);
        // var_dump($res);exit;
        $res = Cache::pull('machine_'.$machine_code);
        if($res){
            // $data = $res;
            $data['ordernumber'] = $res['ordernumber'];
            if($res['student_type'] == 'student'){
                $order = $this->ordersc->where('ordernumber',$res['ordernumber'])->find();
            }else{
                $order = $this->temordersc->where('ordernumber',$res['ordernumber'])->find();
            }
            if($res['student_type'] == 'student'){
                $data['starttime'] = (string)$order['starttime'];
                if($order['endtime'] == NULL){
                    $data['endtime'] = '';
                }else{
                    $data['endtime'] = (string)$order['endtime'];
                }
                $data['should_endtime'] = (string)$order['should_endtime'];

            }elseif($res['student_type'] == 'intent_student'){
                $data['starttime'] = (string)$order['starttime'];
                $data['endtime'] = '';
                $data['should_endtime'] = (string)$order['should_endtime'];
            }else{
                $this->error('学员标志异常');
            }
            
            $data['stu_name'] = $res['stu_name'];
            $data['stu_id'] = $res['stu_id'];
            $data['phone'] = $res['phone'];
            $data['subject_type'] = $res['subject_type'];
            $data['subject_type_text'] = $res['subject_type_text'];
            $data['car_type'] = $res['car_type'];
            $data['car_type_text'] = $res['car_type_text'];
            $data['idcard'] = $res['idcard'];
            $data['student_type'] = $res['student_type'];
            unset($data['address']);
            unset($data['imei']);
            unset($data['sim']);
            unset($data['sn']);
            unset($data['study_machine']);
            unset($data['terminal_equipment']);

            $this->success('返回成功',$data);
        }else{
            $this->error('返回学员信息失败');
        }
    }


    public function place_get_processtest()
    {
        $params = $this->request->post();
        $params['str_name'] = 'ZH_gangwan01_ZhuHai_GangWanA_3_1';
        $params['ordernumber'] = 'CON20211222113432510819';
        if(empty($params['str_name'])|| empty($params['ordernumber'])){
            $this->error('参数缺失');
        }
        $order = $this->ordersc->with(['student','space'])->where('ordernumber',$params['ordernumber'])->find();
        $student = $order['student'];
        $keersort = $this->keersort->where(['space_id'=>$order['space_id']])->find();
        // var_dump($order['space_id']);exit;
        $where_kesan['space_id'] = $order['space_id'];
        $where_kesan['place.str_name'] = $params['str_name'];
        $kesansort = $this->kesansort->with('place')->where($where_kesan)->find();
        // var_dump($kesansort);exit;

        // if(!$kesansort){
        //     $this->error('当前数据错误');
        // }
        // $sort_info = $this->get_study_number_test($keersort,$kesansort,$student);

        // $data['study_process'] = [
        //     'kesan_process'=>explode(',',$kesansort['sequence']),
        //     'kesan_c1_number'=>$sort_info['kesan_c1_number'],
        //     'kesan_c2_number'=>$sort_info['kesan_c2_number'],
        //     'kesan_study_number'=>$sort_info['kesan_study_number'],
        // ];

        // $this->success('返回成功',$data);
    }

    //获取学员练习进度记录
    public function place_get_process()
    {
        $params = $this->request->post();
        // $params['str_name'] = 'NC_Jinling01_NanChang_JinLingA_3_1';
        // $params['ordernumber'] = 'CON20211207195757141775';
        if(empty($params['str_name'])|| empty($params['ordernumber']) ){
            $this->error('参数缺失');
        }
        $order = $this->ordersc->with(['student','space'])->where('ordernumber',$params['ordernumber'])->find();
        $student = $order['student'];
        $keersort = $this->keersort->where(['space_id'=>$order['space_id']])->find();
        $where_kesan['space_id'] = $order['space_id'];
        $where_kesan['place.str_name'] = $params['str_name'];
        $kesansort = $this->kesansort->with('place')->where($where_kesan)->find();
        if(!$kesansort){
            $this->error('当前数据错误');
        }
        // $sort_info = $this->get_study_number_test($keersort,$kesansort,$student);

        // $data['study_process'] = [
        //     'kesan_process'=>explode(',',$kesansort['sequence']),
        //     'kesan_c1_number'=>$sort_info['kesan_c1_number'],
        //     'kesan_c2_number'=>$sort_info['kesan_c2_number'],
        //     'kesan_study_number'=>$sort_info['kesan_study_number'],
        // ];

        // $this->success('返回成功',$data);
    }

    public function str_to_int($str)
    {
        $list = [];
        foreach($str as $k=>$v){
            $list[$k] = (int)$v;
        }
        return $list;
    }


    

    public function get_upload_log()
    {
        $res = Cache::get('upload');
        var_dump($res);
        // 获取文件后缀名
        // $r = file_put_contents($name, $res);
        // $aa = move_uploaded_file($res['ABC']['tmp_name'], ROOT_PATH.'public/uploads/machine_log/123.text');
        // if($r){
        //     $this->success('成功');
        // }else{
        //     $this->error('失败');
        // }
        // if ( ($size < 512000000)  && $extension =="log")// 小于 5M 后缀名log
        // {
        //     if ($res["ABC"]["error"] > 0)
        //     {
        //         $this->error( "错误：: " . $res["ABC"]["error"] . "<br>");
        //     }
        // }else
        // {
        //     $this->error('非法的文件');
        // }

        // $time = date("Ymdhis");
        // $data = date("Ymd");
        // $uploadError = false;
        // $filepath = ROOT_PATH."public/uploads/machine_log/".$data;
        // if(!file_exists($filepath))
        //     $mkdir_file_dir = mkdir($filepath,0777,true); //获取到标题，在最终的目录下面建立一个文件夹用来存放分类指
        // $tmpFilePath = $res['ABC']['tmp_name'];
        // if ($tmpFilePath != "")
        // {
        //     $newname = $time.'-'.$res['ABC']['name'];
        //     $newFilePath = $filepath.'/'.$newname;
        //     if (!move_uploaded_file($tmpFilePath, $newFilePath))
        //         $uploadError = true;
        // }
        // if ($uploadError)
        //     $this->error('上传失败');
        // else
        //     $this->success('Uploaded Successfully');
    }


    /**
     * 获取模拟机日志
     */
    public function uploadLog()
    {
        // $file =  file_get_contents('php://input');
        // $file = $_FILES['files'];
        Cache::set('files_log',$_FILES);
        $name = $_FILES['files']['name'];
        $size = $_FILES["files"]["size"];
        // Cache::set('upload_name',$name);
        // Cache::set('upload_size',$size);
        // Cache::set('file',$file);
        // $this->success('Uploaded Successfully');
        // var_dump($res);
        $time = date("Ymdhis");
        $data = date("Ymd");
        $nameArray = explode(".",$name);
        $extension = end($nameArray);
        if ( ($size < 1024*1024*5)  && $extension =="log")// 小于 5M 后缀名log
        {
            if ($_FILES["files"]["error"] > 0)
            {
                $this->error( "错误：: " . $_FILES["files"]["error"] . "<br>");
            }
        }else
        {
            $this->error('非法的文件');
        }
        $uploadError = false;
        $filepath = ROOT_PATH."public/uploads/machine_ai_log/".$data;
        if(!file_exists($filepath))
            mkdir($filepath,0777,true); //获取到标题，在最终的目录下面建立一个文件夹用来存放分类指
        $tmpFilePath = $_FILES['files']['tmp_name'];
        if ($tmpFilePath != "")
        {
            $newname = $time.'-'.$_FILES['files']['name'];
            $newFilePath = $filepath.'/'.$newname;
            if (!move_uploaded_file($tmpFilePath, $newFilePath))
                $uploadError = true;
        }
        if ($uploadError)
            $this->error('上传失败');
        else
            $insert['machine_code'] = $_FILES['files']['name'];
            $insert['path'] = "uploads/machine_ai_log/".$data."/".$newname;
            $insert['createtime'] = time();
            Db::name('machine_log')->insert($insert);
            $this->success('Uploaded Successfully');
    }

    //获取学员上机图片
    // public function getimage(){
    //     $res = file_get_contents('php://input');
    //     $idcard = ((array)json_decode($res))['Idcard'];
    //     $LoginType = ((array)json_decode($res))['LoginType'];
    //     $ordernumber = ((array)json_decode($res))['ordernumber'];
    //     $image = ((array)((array)json_decode($res))['imgData'])['ImgContent'];
    //     if(!$idcard){
    //         $this->error('没有身份证号');
    //     }
        
    //     $student = $this->student->where('idcard',$idcard)->find();
    //     if(!$student){
    //         $this->error('查无此身份证');
    //     }
    //     if(array_key_exists('ordernumber',(array)json_decode($res))){
    //         $faceimage = $this->faceimage->where('ordernumber',$ordernumber)->find();
    //     }else{
    //         $faceimage = $this->faceimage->where('stu_id',$student['stu_id'])->find();
    //     }

    //     //设置图片名称
    //     $date = time();
    //     $imageName = $idcard.$date.'.jpg';
    //     //判断是否有逗号 如果有就截取后半部分
    //     if (strstr($image,",")){
    //         $image = explode(',',$image);
    //         $image = $image[1];
    //     }

    //     //设置图片保存路径
    //     $path = ROOT_PATH."public/uploads/face/".date("Y-m-d");

    //     //判断目录是否存在 不存在就创建
    //     if (!is_dir($path)){
    //         mkdir($path,0777,true);
    //     }

    //     //图片路径
    //     $imageSrc= $path."/". $imageName;
    //     $mysql_path= "/uploads/face/".date("Y-m-d").'/'. $imageName;
    //     $update = [];
        
    //     //生成文件夹和图片
    //     $r = file_put_contents($imageSrc, base64_decode($image));
    //     if (!$r) {
    //         $this->error('图片生成失败');
    //     }else {
    //         $update['cooperation_id'] = $student['cooperation_id'];
    //         $update['space_id'] = $student['space_id'];
    //         $update['stu_id'] = $student['stu_id'];
    //         if(array_key_exists('ordernumber',(array)json_decode($res))){
    //             $update['ordernumber'] = $ordernumber;
    //         }
    //         if($LoginType == 2){
    //             $update['login_images'] = $mysql_path;
    //             $update['login_time'] = time();
    //         }elseif($LoginType == 4){
    //             $update['logout_images'] = $mysql_path;
    //             $update['middle_time'] = time();
    //         }elseif($LoginType == 5){
    //             $update['middle_images'] = $mysql_path;
    //             $update['logout_time'] = time();
    //         }
    //         if(!$faceimage){
    //             $this->faceimage->allowField(true)->save($update);
    //         }else{
    //             $update['updatetime'] = time();
    //             if(array_key_exists('ordernumber',(array)json_decode($res))){
    //                 $this->faceimage->where('ordernumber',$ordernumber)->update($update);
    //             }else{
    //                 $this->faceimage->where('stu_id',$student['stu_id'])->update($update);
    //             }
    //         }
    //         $this->success('图片生成成功');
    //     }
    // }

    /**
     * 模拟机开机 临时学员不用发开机接口
     */
    public function machine_start(){
        $params = $this->request->post();
        // $params['ordernumber'] = 'CON20230321143237594632';
        if(empty($params['ordernumber'])){
            $this->error('参数缺失');
        }
        $ordernumber = $params['ordernumber'];
        
        // Cache::set('order_first_status_'.$ordernumber,1,3600*2);
        $update['order_status'] = 'executing';

        $order = $this->ordersc->with('space')->where('ordernumber',$ordernumber)->find();

        $order_length = $order['space']['order_length'];
        $order_time = $order_length*60*60;

        if($order['starttime']){
            $order->save($update);
            $update['starttime'] = $order['starttime'];
            $update['should_endtime'] = $order['should_endtime'];
        }else{
            $update['starttime'] = time();
            // $update['should_endtime'] = time()+(3600*2+180);

            // if($order['machine_id'] == 8){
            $update['should_endtime'] = time()+$order_time+180;
            // }
            $order->save($update);
        }
        $order_log['ordernumber'] = $ordernumber;
        $order_log['start_or_end'] = 1;
        $order_log['createtime'] = time();
        $res = Db::name('order_log')->insert($order_log);
        $update['should_endtime'] = (string)$update['should_endtime'];
        $update['starttime'] = (string)$update['starttime'];
        if($res){
            $this->success('返回成功',$update);
        }else{
            $this->success('登录返回失败');
        }
    }


    public function machine_starttest(){
        $params = $this->request->post();
        // $params['ordernumber'] = 'CON20230321143237594632';
        if(empty($params['ordernumber'])){
            $this->error('参数缺失');
        }
        $ordernumber = $params['ordernumber'];
        
        // Cache::set('order_first_status_'.$ordernumber,1,3600*2);
        $update['order_status'] = 'executing';

        $order = $this->ordersc->with('space')->where('ordernumber',$ordernumber)->find();

        $order_length = $order['space']['order_length'];
        $order_time = $order_length*60*60;

        if($order['starttime']){
            $order->save($update);
            $update['starttime'] = $order['starttime'];
            $update['should_endtime'] = $order['should_endtime'];
        }else{
            $update['starttime'] = time();
            // $update['should_endtime'] = time()+(3600*2+180);
            // if($order['machine_id'] == 8){
            $update['should_endtime'] = time()+$order_time+180;
            // }
            $order->save($update);
        }
        $order_log['ordernumber'] = $ordernumber;
        $order_log['start_or_end'] = 1;
        $order_log['createtime'] = time();
        $res = Db::name('order_log')->insert($order_log);
        $update['should_endtime'] = (string)$update['should_endtime'];
        $update['starttime'] = (string)$update['starttime'];
        if($res){
            $this->success('返回成功',$update);
        }else{
            $this->success('登录返回失败');
        }
    }

    public function machine_end(){
        $params =$this->request->post();
        // Cache::set('end111',$params);
        // $params['ordernumber'] = 'CON20230907204016659643';
        if(empty($params['ordernumber']) ){
            $this->error('参数缺失');
        }
        $ordernumber = $params['ordernumber'];
        // $order = $this->ordersc->where('ordernumber',$ordernumber)->find();

        // $should_endtime = $order['should_endtime'];
        $update['endtime'] = time();
        $update['updatetime'] = time();
        // if(time()>$should_endtime){
        //     $update['endtime'] = $should_endtime;
        // }
        $update['order_status'] = 'finished';
        $res = $this->ordersc->where('ordernumber',$ordernumber)->update($update);
        if(!$res){
            $res = $this->temordersc->where('ordernumber',$ordernumber)->update($update);
        }
        $order_log['ordernumber'] = $ordernumber;
        $order_log['start_or_end'] = 2;
        $order_log['createtime'] = time();
        Db::name('order_log')->insert($order_log);
        if($res){
            $this->success('返回成功');
        }else{
            $this->error('登出返回失败');
        }
    }

    /**
     * 授权后通过这个值去开机
     */
    public function putordernumber(){
        $params = $this->request->post();

        if(empty($params['ordernumber'])){
            $this->error('参数缺失');
        }
        $res = Cache::pull('ordernumber_'.$params['ordernumber']);

        if($res){
            $this->success('授权成功');
        }else{
            $this->error('当前没有授权');
        }
    }

    public function get_back_window_code(){
        $params = $this->request->post();
        if(empty($params['machine_code'])){
            $this->error('参数缺失');
        }
        $machine_code = $params['machine_code'];
        $machine = $this->machineai->where('machine_code',$machine_code)->find();
        if($machine){
            $path = $machine['back_window_code'];
            $data['back_window_code'] = $path;
            $this->success('返回成功',$data);
        }else{
            $this->error('查询无此机器码');
        }
    }

    public function get_window(){
        $params = $this->request->post();
        if(empty($params['machine_code'])){
            $this->error('参数缺失');
        }
        $machine_code = $params['machine_code'];
        $path = Cache::pull($machine_code.'back_window');
        if($path ==1){
            $this->success('返回成功');
        }else{
            $this->error('返回失败');
        }
    }

}
