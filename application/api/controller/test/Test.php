<?php

namespace app\api\controller\test;

use app\api\controller\student\Uniformmessage;
use app\common\controller\Api;
use think\cache;
use think\Db;

/**
 * 开机流程所需接口
 */
class Test extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    protected $studyprocess =  null;
    protected $reportcard =  null;
    protected $student =  null;
    protected $order =  null;
    protected $ordersc =  null;
    protected $space =  null;
    protected $Uniformmessage =  null;
    protected $train_statistic =  null;
    protected $train_statistic_ai =  null;
    protected $studyprocessai =  null;
    protected $reportcardai =  null;
    protected $intentstudent =  null;


    public function _initialize()
    {
        $this->studyprocessai = new \app\admin\model\Studyprocessai;
        $this->studyprocess = new \app\admin\model\Studyprocess;
        $this->reportcard =new \app\admin\model\Reportcard;
        $this->student =new \app\admin\model\Student;
        $this->intentstudent =new \app\admin\model\Intentstudent;

        $this->space =new \app\admin\model\Space;
        $this->ordersc =new \app\admin\model\Ordersc;
        $this->order =new \app\admin\model\Order;
        $this->train_statistic =new \app\admin\model\Trainstatistic;
        $this->train_statistic_ai =new \app\admin\model\Trainstatisticai;
        $this->Uniformmessage = new \app\api\controller\student\Uniformmessage;
        parent ::_initialize();
    }

    public function shuju(){
        // $where['space_id'] = 22;
        // $update['space_id'] = 6;
        $res = $this->intentstudent->find();
        var_dump($res->toArray());exit;
    }


    function cishu(){
        $cooperation_ids = "2,110,127,131,134,138,145,148,151,154,161,165,169,177,182,188,193,198,201,204,207,196,213,216,223,233,236,244,295,252,259,262,286,288,311,314,326,332,335,339,346,351,400,413,417,420,423,431,434,438";
        // $data = date('Y-m-d');
        // $starttime = strtotime($data.'00:00:00');
        // $endtime = strtotime($data.'23:59:59');
        // var_dump($starttime,$endtime);exit;
        $con = mysqli_connect('localhost','aivipdriver','Y47rezNyJLTA7sYZ','aivipdriver') or die('数据库连接不上');
        //未开始的取消
        $countSql1 = "SELECT * FROM fa_train_statistic WHERE cooperation_id in (".$cooperation_ids.")";
        $res = mysqli_query($con,$countSql1);
        foreach($res as $v){
            $keer_leiji_sql = "SELECT SUM(study_time) as keer_leiji FROM fa_study_process WHERE stu_id = '".$v['stu_id']."' AND place_id is null ";
            $keer_leiji_res = mysqli_query($con,$keer_leiji_sql);
            foreach($keer_leiji_res as $v1){
                $keer_leiji = 0;
                if($v1['keer_leiji']){
                    $keer_leiji = $v1['keer_leiji'];
                }
            }
            
            $keer_hege_sql = "SELECT COUNT(id) as keer_hege FROM fa_study_process WHERE stu_id = '".$v['stu_id']."' AND place_id is null AND status = '"."1'";

            $keer_hege_res = mysqli_query($con,$keer_hege_sql);
            foreach($keer_hege_res as $v2){
                $keer_hege = $v2['keer_hege'];
            }
            
            $kesan_leiji_sql = "SELECT SUM(study_time) as kesan_leiji FROM fa_study_process WHERE stu_id = '".$v['stu_id']."' AND place_id is not null ";
            $kesan_leiji_res = mysqli_query($con,$kesan_leiji_sql);
            foreach($kesan_leiji_res as $v3){
                $kesan_leiji = 0;
                if($v3['kesan_leiji']){
                    $kesan_leiji = $v3['kesan_leiji'];
                }
            }
            $kesan_hege_sql = "SELECT COUNT(id) as kesan_hege FROM fa_study_process WHERE stu_id = '".$v['stu_id']."' AND place_id is not null AND status = '"."1'";
            $kesan_hege_res = mysqli_query($con,$kesan_hege_sql);
            foreach($kesan_hege_res as $v4){
                $kesan_hege = $v4['kesan_hege'];
            }


            $order = "SELECT COUNT(id) as num FROM fa_order WHERE stu_id = '".$v['stu_id']."'";
            $order_res = mysqli_query($con,$order);
            foreach($order_res as $v5){
                $total_order = $v5['num'];
            }
            // var_dump($kesan_hege_sql);
            // var_dump($kesan_leiji_sql);
            // var_dump($keer_hege_sql);
            // var_dump($keer_leiji_sql);
            // exit;
            $update_Sql = "UPDATE fa_train_statistic set keer_leiji = ".$keer_leiji.",keer_hege = ".$keer_hege.",kesan_leiji = ".$kesan_leiji.",kesan_hege = ".$kesan_hege.",total_order = ".$total_order." where stu_id = '".$v['stu_id']."'";
            mysqli_query($con,$update_Sql);
            exit;
        }

    }

    public function shua()
    {
       
    }

    public function test1()
    {
        $where['cooperation_id'] = 148;
        $res = $this->train_statistic->where($where)->select();
        foreach($res as $v){
            $where2['stu_id'] = $v['stu_id'];
            $where2['order_status'] = ['in',['executing','finished']];
            // $where2['subject_type'] = 'subject3';
            // $where2['score'] = ['>=',90];
            // $count = $this->reportcard->where($where2)->count();
            $count = $this->order->where($where2)->count();

            // if( $count!== 0){
            //     var_dump($count);
            // }
            $update['total_order'] = $count;
            // $this->train_statistic->where(['stu_id'=>$v['stu_id']])->update($update);
        }

    }

    public function test2()
    {

        $where['cooperation_id'] = ['not in',148];
        $cooperation_id = Db::name('space')->group('cooperation_id')->field('cooperation_id')->select();
        $list = array_column($cooperation_id,'cooperation_id');
        var_dump($list);exit;

        $where['cooperation_id'] = ['in',$list];
        $res = $this->train_statistic->where($where)->select();
        var_dump(count($res));exit;
        foreach($res as $v){
            // var_dump($v->toArray());
            $where2['stu_id'] = $v['stu_id'];
            $where2['createtime'] = ['<',1693411200];
            // $where2['order_status'] = ['in',['executing','finished']];
            // $times1 = $this->studyprocess->where($where2)->whereNull('place_id')->sum('study_time');
            // $where2['stu_id'] = $v['stu_id'];
            // $where2['createtime'] = ['<',1693411200];
            // $times2 = $this->studyprocess->where($where2)->whereNotNull('place_id')->sum('study_time');
            // $update['keer_leiji'] = $times1;
            // $update['kesan_leiji'] = $times2;
            // if($update['keer_leiji'] && $update['kesan_leiji']){
            //     $a = $this->studyprocess->where($where2)->whereNull('place_id')->select();
            //     var_dump($v->toArray());
            //     var_dump($update);
            //     var_dump($a->toArray());
            //     exit;
            // }
            // $this->train_statistic->where(['stu_id'=>$v['stu_id']])->update($update);
        }
    }
    
    function strsplit($string, $len = 1)
    {
        $start = 0;
        $strlen = mb_strlen($string);
        while ($strlen) {
            $array[] = strtoupper(mb_substr($string, $start, $len, "utf8"));
            $string = mb_substr($string, $len, $strlen, "utf8");
            $strlen = mb_strlen($string);
        }
        return $array;
    }

    public function jiexi()
    {
        $str = '7E800900008E8617724649151002009C001302030002009C313636313733333432363737323134340000007331363631373333343236373732313434323230393134303030360137393334323438323732323335323137333737333134333335323535323232350001600910101032132100000000FA0003000000000000000101B406B006E834B500E600E600D62209141010100104000003520502047D01BF7E';
        $str = substr($str,2,-2);
        $str = str_ireplace('7D01','7D',$str);
        $str = str_ireplace('7D02','7E',$str);
        if(strlen($str) !== 316){
            $this->error('分钟学时数据位数异常');
        }
        $header = substr($str,0,32);
        $body = substr($str,32,-2);
        $hexOrArr = substr($str,-2);
        $info['header']['version'] = substr($header,0,2) ;
        $info['header']['msgID'] = substr($str,2,4);
        $info['header']['msgAttrubute'] =  substr($header,6,4);
        $info['header']['terminalPhone'] = substr($header,10,16);
        $info['header']['fchrMsgNO'] = hexdec(substr($header,26,4)) ;
        $info['header']['advance'] = substr($header,30,2);
        $info['body']['throughType'] = substr($body,0,2);
        $info['body']['throughID'] = substr($body,2,4);
        $info['body']['msgAttrubute'] = substr($body,6,4);
        $info['body']['trainFlowID'] = hexdec(substr($body,10,4));
        $info['body']['terminalYGID'] = hex2bin(substr($body,14,32));
        $info['body']['length'] = hexdec(substr($body,46,8))/2;
        $xueshiNum= substr($body,54,52);
        $info['body']['xueshiNum']['terminalPhone'] = hex2bin(substr($xueshiNum,0,32));
        $info['body']['xueshiNum']['dateymd'] = hex2bin(substr($xueshiNum,32,12));
        $info['body']['xueshiNum']['recordID'] = hex2bin(substr($xueshiNum,44,8));
        $info['body']['type'] = substr($body,106,2);
        $info['body']['YGStudentID'] = hex2bin(substr($body,108,32));
        $info['body']['YGCoachID'] = hex2bin(substr($body,140,32));
        $info['body']['fchrFlowID'] = hexdec(substr($body,172,8));
        $info['body']['recordTime'] = substr($body,180,6);
        $info['body']['subjectCode'] = substr($body,186,10);
        $info['body']['recordType'] = substr($body,196,2);
        $info['body']['speedMax'] = hexdec(substr($body,198,4))/10;
        $info['body']['mileage'] = hexdec(substr($body,202,4))/10;
        $gnss = substr($body,206,76);
        $info['body']['gnss']['warning'] = substr($gnss,0,8);
        $info['body']['gnss']['status'] = substr($gnss,8,8);
        $info['body']['gnss']['lat'] = hexdec(substr($gnss,16,8))/1000000;
        $info['body']['gnss']['lng'] = hexdec(substr($gnss,24,8))/1000000;
        $info['body']['gnss']['speed'] = hexdec(substr($gnss,32,4))/10;
        $info['body']['gnss']['GPSspeed'] = hexdec(substr($gnss,36,4))/10;
        $info['body']['gnss']['direction'] = hexdec(substr($gnss,40,4));
        $info['body']['gnss']['ymdhis'] = substr($gnss,44,12);
        $info['body']['gnss']['msg1ID'] = substr($gnss,56,2);
        $info['body']['gnss']['msg1length'] = substr($gnss,58,2);
        $info['body']['gnss']['mileage_total'] =hexdec( substr($gnss,60,8))/10;
        $info['body']['gnss']['msg2ID'] = substr($gnss,68,2);
        $info['body']['gnss']['msg2length'] = substr($gnss,70,2);
        $info['body']['gnss']['engine_speed'] = hexdec(substr($gnss,72,4));
        $info['hexOrArr'] = $hexOrArr;
        var_dump($info);exit;
    }

    public function index()
    {
        $where['study_time'] = ['<',0];
        // $where['process_name'] = ['in',['bianchaoting','jiajiandang','yejiandengguang','xian1','xian2','xian3','xian4','xian5','xian6','xian7','xian8','kaoshi3']];
        $res= $this->studyprocess->where($where)->delete();
        var_dump($res);
        // $this->success('请求成功');
    }

    
    public function toole()
    {
        $where['space_id'] = ['in',[38]];
        $student = $this->student->where($where)->select();
        
        // foreach($student as $v){
        //     $update['space_id'] = 38;
        //     $update['cooperation_id'] = 131;
        //     $res = $this->student->where(['id'=>$v['id']])->update($update);
        // }
        var_dump(count($student));
        // $temporaryorder = $this->temporaryorder->where(['machine_id'=>106])->select();

        // $order = $this->order->where(['ordernumber'=>'CON20220506132907572810'])->find();
        // var_dump($order->toArray());exit;
        // var_dump(count($order),count($temporaryorder));
    }

    public function ceshi()
    {
        $res = $this->studyprocess->select();
        $process_name = array_column($res->toArray(),'process_name');
        $process_name = array_unique($process_name);
        var_dump($process_name);
    }

    /**
     * 新增预警数据
     */
    public function add_warn()
    {
        $starttime = \fast\Date::unixtime('day',-1);
        $endtime = \fast\Date::unixtime('day',0) -1;
        $where1['studyprocess.createtime'] = ['between',[$starttime,$endtime]];
        $where1['studyprocess.status'] = '1';
        $where1['studyprocess.process_name'] = ['in',['xian1','xian2','xian3','xian4','xian5','xian6','xian7','xian8','xian9','kaoshi3','kaoshi']];
        $where1['warnexam.status'] = '1';
        $stu_pro1 = $this->studyprocess->with(['warnexam'])->where($where1)->select();
        $where2['studyprocess.createtime'] = ['between',[$starttime,$endtime]];
        $where2['warnexam.leiji_status'] = '1';
        $stu_pro2 = $this->studyprocess->with(['warnexam'])->where($where2)->select();
        
        // $return = array_unique($stu_pro);

        // var_dump($stu_pro1->toArray());exit;
        $stu_arr = [];
        //将每日增量的科二累计，科二合格，科三累计，科三合格累加起来
        foreach($stu_pro1 as $v){
            if(key_exists($v['stu_id'],$stu_arr)){
                if($v['process_name'] =='kaoshi'){
                    $stu_arr[$v['stu_id']]['keer_hege'] += 1;
                }elseif(in_array($v['process_name'],['xian1','xian2','xian3','xian4','xian5','xian6','xian7','xian8','xian9','kaoshi3'])){
                    $stu_arr[$v['stu_id']]['kesan_hege'] += 1;
                }
            }else{
                if($v['place_id']){
                    $stu_arr[$v['stu_id']]['keer_hege'] = 0;
                    $stu_arr[$v['stu_id']]['kesan_hege'] = 1;
                    $stu_arr[$v['stu_id']]['keer_leiji'] = 0;
                    $stu_arr[$v['stu_id']]['kesan_leiji'] = 0;
                    $stu_arr[$v['stu_id']]['cooperation_id'] = $v['cooperation_id'];
                    $stu_arr[$v['stu_id']]['space_id'] = $v['space_id'];
                }else{
                    $stu_arr[$v['stu_id']]['keer_hege'] = 1;
                    $stu_arr[$v['stu_id']]['kesan_hege'] = 0;
                    $stu_arr[$v['stu_id']]['keer_leiji'] = 0;
                    $stu_arr[$v['stu_id']]['kesan_leiji'] = 0;
                    $stu_arr[$v['stu_id']]['cooperation_id'] = $v['cooperation_id'];
                    $stu_arr[$v['stu_id']]['space_id'] = $v['space_id'];
                }
            }
        }

        foreach($stu_pro2 as $v){
            if(key_exists($v['stu_id'],$stu_arr)){
                if($v['place_id']){
                    $stu_arr[$v['stu_id']]['kesan_leiji'] += $v['study_time'];
                }else{
                    $stu_arr[$v['stu_id']]['keer_leiji'] += $v['study_time'];
                }
            }else{
                if($v['place_id']){
                    $stu_arr[$v['stu_id']]['keer_leiji'] = 0;
                    $stu_arr[$v['stu_id']]['kesan_leiji'] = $v['study_time'];
                    $stu_arr[$v['stu_id']]['cooperation_id'] = $v['cooperation_id'];
                    $stu_arr[$v['stu_id']]['space_id'] = $v['space_id'];
                }else{
                    $stu_arr[$v['stu_id']]['keer_leiji'] = $v['study_time'];
                    $stu_arr[$v['stu_id']]['kesan_leiji'] = 0;
                    $stu_arr[$v['stu_id']]['cooperation_id'] = $v['cooperation_id'];
                    $stu_arr[$v['stu_id']]['space_id'] = $v['space_id'];
                }
            }
        }
        foreach($stu_arr as $k=>$v){
            $train_statistic = null;
            $update = null;
            $train_statistic = Db::name('train_statistic')->where(['stu_id'=>$k])->find();

            $stu_warn = Db::name('student_warn')->where(['stu_id'=>$k])->find();
            if(key_exists('keer_hege',$v)){
                $update['keer_hege'] = $train_statistic['keer_hege'] +$v['keer_hege'];
            }
            if(key_exists('kesan_hege',$v)){
                $update['kesan_hege'] = $train_statistic['kesan_hege'] +$v['kesan_hege'];
            }
            if(key_exists('keer_leiji',$v)){
                $update['keer_leiji'] = $train_statistic['keer_leiji'] + $v['keer_leiji'];
            }
            if(key_exists('kesan_leiji',$v)){
                $update['kesan_leiji'] = $train_statistic['kesan_leiji'] + $v['kesan_leiji'];
            }
            
            if($stu_warn){
                // var_dump($update);
                Db::name('student_warn')->where(['stu_id'=>$k])->update($update);
            }else{
                $update['stu_id'] = $k;
                $update['cooperation_id'] = $v['cooperation_id'];
                $update['space_id'] = $v['space_id'];
                $update['createtime'] = time();
                // var_dump($update);
                Db::name('student_warn')->where(['stu_id'=>$k])->insert($update);
            }
        }

        // $rep_card  = Db::name('report_card')->where($where)->select();


    }


    public function add_warn_ai()
    {
        $starttime = \fast\Date::unixtime('day',-1);
        $endtime = \fast\Date::unixtime('day',0) -1;
        $where1['studyprocessai.createtime'] = ['between',[$starttime,$endtime]];
        $where1['studyprocessai.status'] = '1';
        $where1['studyprocessai.process_name'] = ['in',['xian1','xian2','xian3','xian4','xian5','xian6','xian7','xian8','xian9','kaoshi3','kaoshi']];
        $where1['warnexamai.status'] = '1';
        $stu_pro1 = $this->studyprocessai->with(['warnexam'])->where($where1)->select();
        $where2['studyprocess.createtime'] = ['between',[$starttime,$endtime]];
        $where2['warnexam.leiji_status'] = '1';
        $stu_pro2 = $this->studyprocessai->with(['warnexam'])->where($where2)->select();
        
        // $return = array_unique($stu_pro);

        // var_dump($stu_pro1->toArray());exit;
        $stu_arr = [];
        //将每日增量的科二累计，科二合格，科三累计，科三合格累加起来
        foreach($stu_pro1 as $v){
            if(key_exists($v['stu_id'],$stu_arr)){
                if($v['process_name'] =='kaoshi'){
                    $stu_arr[$v['stu_id']]['keer_hege'] += 1;
                }elseif(in_array($v['process_name'],['xian1','xian2','xian3','xian4','xian5','xian6','xian7','xian8','xian9','kaoshi3'])){
                    $stu_arr[$v['stu_id']]['kesan_hege'] += 1;
                }
            }else{
                if($v['place_id']){
                    $stu_arr[$v['stu_id']]['keer_hege'] = 0;
                    $stu_arr[$v['stu_id']]['kesan_hege'] = 1;
                    $stu_arr[$v['stu_id']]['keer_leiji'] = 0;
                    $stu_arr[$v['stu_id']]['kesan_leiji'] = 0;
                    $stu_arr[$v['stu_id']]['cooperation_id'] = $v['cooperation_id'];
                    $stu_arr[$v['stu_id']]['space_id'] = $v['space_id'];
                }else{
                    $stu_arr[$v['stu_id']]['keer_hege'] = 1;
                    $stu_arr[$v['stu_id']]['kesan_hege'] = 0;
                    $stu_arr[$v['stu_id']]['keer_leiji'] = 0;
                    $stu_arr[$v['stu_id']]['kesan_leiji'] = 0;
                    $stu_arr[$v['stu_id']]['cooperation_id'] = $v['cooperation_id'];
                    $stu_arr[$v['stu_id']]['space_id'] = $v['space_id'];
                }
            }
        }

        foreach($stu_pro2 as $v){
            if(key_exists($v['stu_id'],$stu_arr)){
                if($v['place_id']){
                    $stu_arr[$v['stu_id']]['kesan_leiji'] += $v['study_time'];
                }else{
                    $stu_arr[$v['stu_id']]['keer_leiji'] += $v['study_time'];
                }
            }else{
                if($v['place_id']){
                    $stu_arr[$v['stu_id']]['keer_leiji'] = 0;
                    $stu_arr[$v['stu_id']]['kesan_leiji'] = $v['study_time'];
                    $stu_arr[$v['stu_id']]['cooperation_id'] = $v['cooperation_id'];
                    $stu_arr[$v['stu_id']]['space_id'] = $v['space_id'];
                }else{
                    $stu_arr[$v['stu_id']]['keer_leiji'] = $v['study_time'];
                    $stu_arr[$v['stu_id']]['kesan_leiji'] = 0;
                    $stu_arr[$v['stu_id']]['cooperation_id'] = $v['cooperation_id'];
                    $stu_arr[$v['stu_id']]['space_id'] = $v['space_id'];
                }
            }
        }
        foreach($stu_arr as $k=>$v){
            $train_statistic = null;
            $update = null;
            $train_statistic = Db::name('train_statistic_ai')->where(['stu_id'=>$k])->find();

            $stu_warn = Db::name('student_warn_ai')->where(['stu_id'=>$k])->find();
            if(key_exists('keer_hege',$v)){
                $update['keer_hege'] = $train_statistic['keer_hege'] +$v['keer_hege'];
            }
            if(key_exists('kesan_hege',$v)){
                $update['kesan_hege'] = $train_statistic['kesan_hege'] +$v['kesan_hege'];
            }
            if(key_exists('keer_leiji',$v)){
                $update['keer_leiji'] = $train_statistic['keer_leiji'] + $v['keer_leiji'];
            }
            if(key_exists('kesan_leiji',$v)){
                $update['kesan_leiji'] = $train_statistic['kesan_leiji'] + $v['kesan_leiji'];
            }
            
            if($stu_warn){
                // var_dump($update);
                Db::name('student_warn_ai')->where(['stu_id'=>$k])->update($update);
            }else{
                $update['stu_id'] = $k;
                $update['cooperation_id'] = $v['cooperation_id'];
                $update['space_id'] = $v['space_id'];
                $update['createtime'] = time();
                // var_dump($update);
                Db::name('student_warn_ai')->where(['stu_id'=>$k])->insert($update);
            }
        }
    }



    public function FunctionName()
    {
        $cooperation_id = 223;
        $warn['keer_leiji'] = 2;
        $warn['kesan_leiji'] = 2;
        $warn['leiji_status'] = 1;
        $time = \fast\Date::unixtime('day',0) -1;
        $students = Db::name('student')->where(['cooperation_id'=>$cooperation_id])->select();
        $stu_ids = array_column($students,'stu_id');
        $process = Db::name('study_process')->where(['cooperation_id'=>$cooperation_id])->select();

        $process_stu_ids = array_column($process,'stu_id') ;
        $ids = array_unique(array_intersect($process_stu_ids,$stu_ids)) ;
        foreach($ids as $v){
            // var_dump($v);
            $leiji = [];

            //科二累计时间
            $keer_study_time = Db::name('study_process')->where(['stu_id'=>$v,'createtime'=>['lt',$time],'cooperation_id'=>$cooperation_id])->whereNull('place_id')->select();
            $keer_study_time_array = array_column($keer_study_time,'study_time');
            // $keer_sum_time = round(array_sum($keer_study_time_array)/3600,2);
            $keer_sum_time = array_sum($keer_study_time_array);

            //科三累计时间
            $kesan_study_time = Db::name('study_process')->where(['stu_id'=>$v,'place_id'=>['<>','NULL'],'createtime'=>['lt',$time],'cooperation_id'=>$cooperation_id])->select();
            $kesan_study_time_array = array_column($kesan_study_time,'study_time');
            // $kesan_sum_time = round(array_sum($kesan_study_time_array)/3600,2);
            $kesan_sum_time = array_sum($kesan_study_time_array);

            // $leiji['leiji_status'] = $warn['leiji_status'];
            if($keer_sum_time >= $warn['keer_leiji']*3600){
                $leiji['keer_leiji'] = array_sum($keer_study_time_array);
            }

            if($kesan_sum_time >= $warn['kesan_leiji']*3600){
                $leiji['kesan_leiji'] =array_sum($kesan_study_time_array);
            }
            // var_dump($keer_sum_time);


            $stu_info = Db::name('student')->where(['stu_id'=>$v])->find();
            $student_warn = Db::name('student_warn')->where(['stu_id'=>$v])->find();

            $train_statistic = Db::name('train_statistic')->where(['stu_id'=>$v])->find();
            $leiji['cooperation_id'] = $stu_info['cooperation_id'];
            $leiji['space_id'] = $stu_info['space_id'];
            $leiji['createtime'] = time();
            if((!$student_warn && array_key_exists('keer_leiji',$leiji)) || (!$student_warn && array_key_exists('kesan_leiji',$leiji))){
                if($warn['leiji_status']){
                    $leiji['stu_id'] = $stu_info['stu_id'];
                    unset($leiji['leiji_status']);
                    if(array_key_exists('keer_leiji',$leiji)){
                        $leiji['keer_leiji_status'] = 1;
                    }
                    if(array_key_exists('kesan_leiji',$leiji)){
                        $leiji['kesan_leiji_status'] = 1;
                    }
                    Db::name('student_warn')->insert($leiji);
                }
                // unset($leiji['leiji_status']);
                // if(!$train_statistic){
                //     $leiji['stu_id'] = $stu_info['stu_id'];
                //     Db::name('train_statistic')->insert($leiji);
                // }else{
                //     unset($leiji['stu_id']);
                //     Db::name('train_statistic')->where(['stu_id'=>$v])->update($leiji);
                // }
            }elseif(($student_warn && array_key_exists('keer_leiji',$leiji)) || ($student_warn && array_key_exists('kesan_leiji',$leiji))){
                if($warn['leiji_status'] && !$student_warn){
                    $leiji['stu_id'] = $stu_info['stu_id'];
                    // $leiji['']
                    Db::name('student_warn')->insert($leiji);
                }elseif(!$warn['leiji_status'] &&$student_warn){
                    $leiji['updatetime'] = time();
                    unset($leiji['leiji_status']);

                    Db::name('student_warn')->where(['stu_id'=>$v])->update($leiji);
                }
                // if(array_key_exists('keer_leiji',$leiji)){
                //     unset($leiji['leiji_status']);
                // }
                // if(!$train_statistic){
                //     $leiji['stu_id'] = $stu_info['stu_id'];
                //     Db::name('train_statistic')->insert($leiji);
                // }else{
                //     $leiji['updatetime'] = time();
                //     Db::name('train_statistic')->where(['stu_id'=>$v])->update($leiji);
                // }
            
            }
            unset($train_statistic);
            unset($leiji);
        }

    }


    public function yanzheng()
    {
        $res = Db::name('study_process')->where(['stu_id'=>'CSN20230309153051490132'])->select();
        $leiji_array = array_column($res,'study_time');
        $leiji = array_sum($leiji_array);
        var_dump($leiji/3600);exit;

    }

    public function ceshishuju()
    {
        $time = \fast\Date::unixtime('day',0) -1;
        // $res = Db::name('train_statistic')->limit(800,1000)->select();
        $res = Db::name('train_statistic')->limit(1501,200)->select();  
        exit;
        // var_dump($res);exit;
        foreach($res as $v){
            // $stu_proces = [];
            $update = [];
            $keer_leiji = 0;
            $keer_leiji_array = [];
            $kesan_leiji = 0;
            $kesan_leiji_array = [];
            // $update['keer_hege'] = 0;
            // $update['keer_leiji'] = 0;
            // $update['kesan_hege'] = 0;
            // $update['kesan_leiji'] = 0;
            // Db::name('student_warn')->where(['stu_id'=>$v['stu_id']])->update($update);

            // $stu_proces = Db::name('study_process')->where(['stu_id'=>$v['stu_id'],'place_id'=>['<>','NULL']])->select();

            $update['keer_hege'] = Db::name('study_process')->where(['stu_id'=>$v['stu_id'],'status'=>1,'process_name'=>['in',['xinshou','jichubj','fangxiangpan','qiting','daisu','chegan','cefangwei','daoche','quxian','zhijiaowan','banpo','ziyou']]])->count();

            $keer_leiji = Db::name('study_process')->where(['stu_id'=>$v['stu_id'],'process_name'=>['in',['xinshou','jichubj','fangxiangpan','qiting','daisu','chegan','cefangwei','daoche','quxian','zhijiaowan','banpo','ziyou']]])->select();

            $keer_leiji_array = array_column($keer_leiji,'study_time');
            $update['keer_leiji'] = array_sum($keer_leiji_array);
            
            $update['kesan_hege'] = Db::name('study_process')->where(['stu_id'=>$v['stu_id'],'status'=>1,'process_name'=>['in',['bianchaoting','jiajiandang','yejiandengguang','xian1','xian2','xian3','xian4','xian5','xian6','xian7','xian8','kaoshi3']]])->count();

            $kesan_leiji = Db::name('study_process')->where(['stu_id'=>$v['stu_id'],'process_name'=>['in',['bianchaoting','jiajiandang','yejiandengguang','xian1','xian2','xian3','xian4','xian5','xian6','xian7','xian8','kaoshi3']]])->select();

            $kesan_leiji_array = array_column($kesan_leiji,'study_time');
            $update['kesan_leiji'] = array_sum($kesan_leiji_array);
            // $update['total_order'] = Db::name('order')->where(['stu_id'=>$v['stu_id'],'order_status'=>'finished'])->count();
            $res = Db::name('train_statistic')->where(['id'=>$v['id']])->update($update);

            // if($stu_proces){
            //     if($v['kesan_hege_status'] == 1){
            //         $update['kesan_hege'] = count($stu_proces);
            //     }
            //     if($v['kesan_leiji_status'] == 1){
            //         $study_time_array = array_column($stu_proces,'study_time');
            //         $update['kesan_leiji'] = array_sum($study_time_array);
            //     }
            // }
            
            // $study_time_array = array_column($stu_proces,'study_time');
            // $arr['keer_leiji'] = array_sum($study_time_array);
            // var_dump($v['keer_leiji'].'----'.$arr['keer_leiji']);
            
            // var_dump($arr['keer_leiji']);exit;
        }
    }
    
    /**
     * 历史训练数据
     */
    public function xunlian_lishi()
    {
        $time = \fast\Date::unixtime('day',0) -1;

        $process = Db::name('study_process')->select();

        $process_stu_ids = array_column($process,'stu_id') ;
        $ids = array_unique($process_stu_ids);
        foreach($ids as $v){
            $stu_info = Db::name('student')->where(['stu_id'=>$v])->find();
            
            $arr = [];
            //科二累计时间
            $keer_study_time = Db::name('study_process')->where(['stu_id'=>$v,'createtime'=>['lt',$time]])->whereNull('place_id')->select();

            $keer_study_time_array = array_column($keer_study_time,'study_time');
            $keer_hege = Db::name('study_process')->where(['stu_id'=>$v,'process_name'=>'kaoshi','status'=>1,'createtime'=>['lt',$time]])->whereNull('place_id')->count();


            $arr['keer_leiji'] = array_sum($keer_study_time_array);
            $arr['keer_hege'] = $keer_hege;

            //科三累计时间
            $kesan_study_time = Db::name('study_process')->where(['stu_id'=>$v,'place_id'=>['<>','NULL'],'createtime'=>['lt',$time]])->select();
            $kesan_study_time_array = array_column($kesan_study_time,'study_time');
            $arr['kesan_leiji'] = array_sum($kesan_study_time_array);
            $kesan_hege = Db::name('study_process')->where(['stu_id'=>$v,'status'=>1,'process_name'=>['in','xian1','xian2','xian3','xian4','xian5','xian6','xian7','xian8','xian9'],'place_id'=>['<>','NULL'],'createtime'=>['lt',$time]])->count();

            $arr['kesan_hege'] = $kesan_hege;
            
            $train_statistic = Db::name('train_statistic')->where(['stu_id'=>$v])->find();
            $arr['cooperation_id'] = $stu_info['cooperation_id'];
            $arr['space_id'] = $stu_info['space_id'];

            $arr['stu_id'] = $v;
            if(!$stu_info){
                continue;
            }
            $arr['total_order'] = $this->order->where(['stu_id'=>$v,'order_status'=>'finished'])->count();
            if($train_statistic){
                $arr['updatetime'] = time();
                Db::name('train_statistic')->where(['stu_id'=>$v])->update($arr);
            }else{
                $arr['createtime'] = time();
                Db::name('train_statistic')->insert($arr);
            }
            
            unset($train_statistic);
            unset($arr);
            unset($stu_info);
        }

    }


    /**
     * 当日增量
     */
    public function xunlian_zengliang()
    {
        $starttime = \fast\Date::unixtime('day',0);
        $endtime = \fast\Date::unixtime('day',1) -1;

        $process = Db::name('study_process')->where(['createtime'=>['between',[$starttime,$endtime]]])->select();

        $process_stu_ids = array_column($process,'stu_id') ;
        $ids = array_unique($process_stu_ids);

        foreach($ids as $v){
            $stu_info = Db::name('student')->where(['stu_id'=>$v])->find();
            
            $arr = [];
            //科二累计时间
            $keer_study_time = Db::name('study_process')->where(['stu_id'=>$v,'createtime'=>['between',[$starttime,$endtime]]])->whereNull('place_id')->select();

            $keer_study_time_array = array_column($keer_study_time,'study_time');
            // $keer_hege = Db::name('study_process')->where(['stu_id'=>$v,'status'=>1,'process_name'=>'kaoshi','createtime'=>['between',[$starttime,$endtime]]])->whereNull('place_id')->count();

            $arr['keer_leiji'] = array_sum($keer_study_time_array);
            // $arr['keer_hege'] = $keer_hege;

            //科三累计时间
            $kesan_study_time = Db::name('study_process')->where(['stu_id'=>$v,'place_id'=>['<>','NULL'],'createtime'=>['between',[$starttime,$endtime]]])->select();
            $kesan_study_time_array = array_column($kesan_study_time,'study_time');
            $arr['kesan_leiji'] = array_sum($kesan_study_time_array);
            // $kesan_hege = Db::name('study_process')->where(['stu_id'=>$v,'status'=>1,'process_name'=>['in','xian1','xian2','xian3','xian4','xian5','xian6','xian7','xian8','xian9'],'place_id'=>['<>','NULL'],'createtime'=>['between',[$starttime,$endtime]]])->count();

            // $arr['kesan_hege'] = $kesan_hege;
            
            $train_statistic = Db::name('train_statistic')->where(['stu_id'=>$v])->find();
            $arr['cooperation_id'] = $stu_info['cooperation_id'];
            $arr['space_id'] = $stu_info['space_id'];
            $arr['createtime'] = time();

            $arr['stu_id'] = $v;
            if(!$stu_info){
                continue;
            }
            $arr['total_order'] = $this->order->where(['stu_id'=>$v,'order_status'=>'finished'])->count();
            if($train_statistic){
                $arr['keer_leiji'] = $arr['keer_leiji']+$train_statistic['keer_leiji'];
                $arr['kesan_leiji'] = $arr['kesan_leiji']+$train_statistic['kesan_leiji'];
                Db::name('train_statistic')->where(['stu_id'=>$v])->update($arr);
            }else{
                Db::name('train_statistic')->insert($arr);
            }
            
            unset($train_statistic);
            unset($arr);
            unset($stu_info);
        }

    }

    public function xunlian_zengliang_ai()
    {
        $starttime = \fast\Date::unixtime('day',0);
        $endtime = \fast\Date::unixtime('day',1) -1;

        $process = Db::name('study_process_ai')->where(['createtime'=>['between',[$starttime,$endtime]]])->select();

        $process_stu_ids = array_column($process,'stu_id') ;
        $ids = array_unique($process_stu_ids);

        foreach($ids as $v){
            $stu_info = Db::name('student')->where(['stu_id'=>$v])->find();
            
            $arr = [];
            //科二累计时间
            $keer_study_time = Db::name('study_process_ai')->where(['stu_id'=>$v,'createtime'=>['between',[$starttime,$endtime]]])->whereNull('place_id')->select();

            $keer_study_time_array = array_column($keer_study_time,'study_time');
            // $keer_hege = Db::name('study_process')->where(['stu_id'=>$v,'status'=>1,'process_name'=>'kaoshi','createtime'=>['between',[$starttime,$endtime]]])->whereNull('place_id')->count();

            $arr['keer_leiji'] = array_sum($keer_study_time_array);
            // $arr['keer_hege'] = $keer_hege;

            //科三累计时间
            $kesan_study_time = Db::name('study_process_ai')->where(['stu_id'=>$v,'place_id'=>['<>','NULL'],'createtime'=>['between',[$starttime,$endtime]]])->select();
            $kesan_study_time_array = array_column($kesan_study_time,'study_time');
            $arr['kesan_leiji'] = array_sum($kesan_study_time_array);
            // $kesan_hege = Db::name('study_process')->where(['stu_id'=>$v,'status'=>1,'process_name'=>['in','xian1','xian2','xian3','xian4','xian5','xian6','xian7','xian8','xian9'],'place_id'=>['<>','NULL'],'createtime'=>['between',[$starttime,$endtime]]])->count();

            // $arr['kesan_hege'] = $kesan_hege;
            
            $train_statistic = Db::name('train_statistic_ai')->where(['stu_id'=>$v])->find();
            $arr['cooperation_id'] = $stu_info['cooperation_id'];
            $arr['space_id'] = $stu_info['space_id'];
            $arr['createtime'] = time();

            $arr['stu_id'] = $v;
            if(!$stu_info){
                continue;
            }
            $arr['total_order'] = $this->ordersc->where(['stu_id'=>$v,'order_status'=>'finished'])->count();
            if($train_statistic){
                $arr['keer_leiji'] = $arr['keer_leiji']+$train_statistic['keer_leiji'];
                $arr['kesan_leiji'] = $arr['kesan_leiji']+$train_statistic['kesan_leiji'];
                Db::name('train_statistic_ai')->where(['stu_id'=>$v])->update($arr);
            }else{
                Db::name('train_statistic_ai')->insert($arr);
            }
            
            unset($train_statistic);
            unset($arr);
            unset($stu_info);
        }

    }
    public function studentcode1()
    {
        $machine_code = 10020;
        //获取token
        $APPID = 'wx7580189fba858f26';
        $AppSecret = 'dfd9ba054745cfc5a5eb815112ecb375';
        $tokenUrl= "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" .$APPID ."&secret=".$AppSecret;
        $getArr=array();
        $tokenArr=json_decode($this->send_post($tokenUrl,$getArr,"GET"));
        $access_token=$tokenArr->access_token;
        //生成二维码
        $page="pages/qrcode/qrcode";
        $scene = 'machine_code='.$machine_code;
        $width = 500;
        $data = [
            // 'access_token'=>$access_token,
            'scene'=>$scene,
            'page'=>$page,
            
        ];
        $post_data= json_encode($data,true);
        $url="https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=".$access_token;
        $result= $this->api_notice_increment($url,$post_data);
        $time = time();
        $date = date('Y-m-d',$time );
        $filepath = ROOT_PATH."public/uploads/code/student/".$date.'/';
        if(!file_exists($filepath)){
            mkdir($filepath,0777,true);
        }
        $imagepage = '/uploads/code/student/'.$date.'/'.$time.'-'.$machine_code.'.jpg';
        file_put_contents(ROOT_PATH.'public'.$imagepage,$result);
        return $imagepage;
    }

    public function change_test()
    {
        $data = date('Y-m-d');
        // $starttime = strtotime($data.'00:00:00');
        $endtime = strtotime($data.'23:59:59');
        // var_dump($starttime,$endtime);exit;
        $con = mysqli_connect('159.75.40.219','root','carshow123@','aicarshow') or die('数据库连接不上');

        //未开始的取消
        $countSql1 = "UPDATE fa_order SET order_status='cancel_refunded' WHERE order_status='paid' AND reserve_starttime <".$endtime."";
        mysqli_query($con,$countSql1);
        //执行中的完成
        $countSql2 = "UPDATE fa_order SET order_status='finished' WHERE order_status='executing' AND reserve_starttime <".$endtime."";
        mysqli_query($con,$countSql2);
        //已受理未执行的完成
        $countSql3 = "UPDATE fa_order SET order_status='finished' WHERE order_status='accept_unexecut' AND reserve_starttime <".$endtime."";
        mysqli_query($con,$countSql3);

    }
    
    public function space_id_admin_id()
    {
        $res = $this->space->select();
        foreach($res as $v){
            $data = [];
            $data['space_id'] = $v['id'];
            $data['cooperation_id'] = $v['cooperation_id'];
            Cache::set('admin_id'.$v['id'],$data,24*3600);
        }
    }

    public function id_to_cartype()
    {
        $con = mysqli_connect('172.16.16.4','root','carshow123@','carshow') or die('数据库连接不上');

        //未开始的取消
        $car_type = "SELECT * from fa_subject_car_type";
        $res = mysqli_query($con,$car_type);
        while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
            if($row['subject_cartype'] == 'C1'){
                Cache::set('car_type'.$row['id'],'cartype2',24*3600);
            }else{
                Cache::set('car_type'.$row['id'],'cartype1',24*3600);
            }
        }
    }

    public function id_to_subjecttype()
    {
        $con = mysqli_connect('172.16.16.4','root','carshow123@','carshow') or die('数据库连接不上');

        //未开始的取消
        $subject_type = "SELECT * from fa_subject_type";
        $res = mysqli_query($con,$subject_type);
        while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
            if($row['subject_type'] == '科目一'){
                Cache::set('subject_type'.$row['id'],'subject2',24*3600);
            }elseif($row['subject_type'] == '科目二'){
                Cache::set('subject_type'.$row['id'],'subject2',24*3600);
            }elseif($row['subject_type'] == '科目三'){
                Cache::set('subject_type'.$row['id'],'subject3',24*3600);
            }elseif($row['subject_type'] == '科目四'){
                Cache::set('subject_type'.$row['id'],'subject3',24*3600);
            }
        }
    }

    public function id_to_machine()
    {
        $machine = $this->machinecar->select();
        foreach($machine as $v){
            Cache::set('machine_code'.$v['machine_code'],$v['id'],24*3600);
        }
    }

    function oldId_to_newId(){
        Cache::set('space_'.'24','14');//盐田馆
        Cache::set('space_'.'26','12');//龙井
        Cache::set('space_'.'27','18');//大鹏
        Cache::set('space_'.'28','19');//梅林
        Cache::set('space_'.'29','17');//坂田
        Cache::set('space_'.'30','15');//测试馆
        Cache::set('space_'.'33','20');//华侨城馆
        Cache::set('space_'.'34','5');//科兴
        Cache::set('space_'.'36','28');//天河
        Cache::set('space_'.'38','22');//福永馆
        Cache::set('space_'.'42','21');//宝安馆
        Cache::set('space_'.'40','16');//交表馆
        Cache::set('space_'.'46','23');//东莞馆
        Cache::set('space_'.'47','27');//白云馆
        Cache::set('space_'.'50','24');//龙华馆
        Cache::set('space_'.'51','25');//园岭馆
        Cache::set('space_'.'53','26');//南洲馆
        Cache::set('space_'.'55','29');//2020年12月
        Cache::set('space_'.'57','30');//时代馆
        // Cache::set('school_'.'23','39');//盐田合作
        // Cache::set('school_'.'25','8');//卡尔迅
        // Cache::set('school_'.'37','80');//福永
        // Cache::set('school_'.'41','79');//宝体
        // Cache::set('school_'.'39','59');//交表
        // Cache::set('school_'.'45','82');//东莞
        // Cache::set('school_'.'36','95');//天河
        // Cache::set('school_'.'52','85');//南州
        // Cache::set('school_'.'49','87');//龙华
        // Cache::set('school_'.'61','99');//时代
        // Cache::set('school_'.'59','97');//活动
    }

    public function get_cooperation_id($space_id)
    {
        if(in_array($space_id,[5,12,15,17,18,19,20])){
            return 8;
        }elseif(in_array($space_id,[14])){
            return 39;
        }elseif(in_array($space_id,[16])){
            return 59;
        }elseif(in_array($space_id,[21,25])){
            return 78;
        }elseif(in_array($space_id,[22])){
            return 80;
        }elseif(in_array($space_id,[23])){
            return 82;
        }elseif(in_array($space_id,[24])){
            return 87;
        }elseif(in_array($space_id,[26])){
            return 85;
        }elseif(in_array($space_id,[27])){
            return 91;
        }elseif(in_array($space_id,[28])){
            return 95;
        }elseif(in_array($space_id,[29])){
            return 97;
        }elseif(in_array($space_id,[30])){
            return 99;
        }
    }
 
    public function coach()
    {
        $data = date('Y-m-d');
        // $starttime = strtotime($data.'00:00:00');
        $endtime = strtotime($data.'23:59:59');
        // var_dump($starttime,$endtime);exit;
        $con = mysqli_connect('172.16.16.4','root','carshow123@','carshow') or die('数据库连接不上');

        //未开始的取消
        $coach = "SELECT * from fa_coach";
        $res = mysqli_query($con,$coach);
        while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
            // var_dump($row);
            $coach_time = "SELECT * from fa_coach_config_time_people WHERE coach_id=".$row['id'];
            $time_res = mysqli_query($con,$coach_time);

            unset($row['id']);
            unset($row['subject_car_type_id']);
            unset($row['subject_type']);
            unset($row['school_id']);
            unset($row['vehicle']);
            unset($row['people_upper']);
            unset($row['coach_ic']);
            unset($row['shirt_size']);
            unset($row['trousers_size']);
            unset($row['receive']);
            $row['subject_type'] = 'subject2,subject3';
            $row['car_type'] = 'cartype1,cartype2';
            $new_space_id = Cache::get('space_'.$row['space_id']);
            $cooperation_id = $this->get_cooperation_id($new_space_id);
            $row['space_id'] = $new_space_id;
            $row['cooperation_id'] = $cooperation_id;
            // var_dump($row);
            // $this->coach->insert($row);
            $coach_id = $this->coach->getLastInsID();
            while($time = mysqli_fetch_array($time_res, MYSQLI_ASSOC)){
                $time['coach_id']= $coach_id;
                unset($time['id']);
                // Db::name('coach_config_time_people')->insert($time);
            }
        }
        $con->close();
    }

    public function coach_leave()
    {
        $data = date('Y-m-d');
        // $starttime = strtotime($data.'00:00:00');
        $endtime = strtotime($data.'23:59:59');
        // var_dump($starttime,$endtime);exit;
        $con = mysqli_connect('172.16.16.4','root','carshow123@','carshow') or die('数据库连接不上');

        //未开始的取消
        $coach = "SELECT * from fa_coach_leave";
        $res = mysqli_query($con,$coach);
        while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
            unset($row['id']);
            $coach = $this->coach->where('coach_id',$row['coach_id'])->find();
            if($coach){
                if(!$coach['cooperation_id']){
                    var_dump($coach['coach_id'],$coach['name']);
                    exit;
                }else{
                    $row['cooperation_id'] = $coach['cooperation_id'];
                    $row['space_id'] = $coach['space_id'];
                    // $this->coachleave->insert($row);
                }

                // exit;
            }
        }
    }
    public function machine_car()
    {
        $con = mysqli_connect('172.16.16.4','root','carshow123@','carshow') or die('数据库连接不上');

        //未开始的取消
        $sql = "SELECT * FROM fa_machine_car where id > 130";
        $res = mysqli_query($con,$sql);
        while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
            $row['student_code'] = $this->studentcode($row['machine_code']);
            $row['experience_code'] = $this->experience_code($row['machine_code']);
            $row['back_window_code'] = $this->backwindow_code($row['machine_code']);
            // $row['pay_code'] = '';
            // var_dump($row);exit;
            $row['car_type'] = Cache::get('car_type'.$row['study_car_type_id']);
            unset($row['id']);
            unset($row['pay_code']);
            unset($row['study_car_type_id']);
            unset($row['school_id']);
            $new_space_id = Cache::get('space_'.$row['space_id']);
            $cooperation_id = $this->get_cooperation_id($new_space_id);
            $row['space_id'] = $new_space_id;
            $row['cooperation_id'] = $cooperation_id;
            // $this->machinecar->insert($row);
        }
        $con->close();
    }

    public function machine_car_lost()
    {
        $con = mysqli_connect('172.16.16.4','root','carshow123@','carshow') or die('数据库连接不上');

        //未开始的取消
        $sql = "SELECT * FROM fa_machine_car where id <= 130";
        $res = mysqli_query($con,$sql);
        while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
            $machine_car = $this->machinecar->where('machine_code',$row['machine_code'])->find();
            if(!$machine_car){
                var_dump($row['machine_code']);
                // $row['student_code'] = $this->studentcode($row['machine_code']);
                // $row['experience_code'] = $this->experience_code($row['machine_code']);
                // $row['back_window_code'] = $this->backwindow_code($row['machine_code']);
                // $row['pay_code'] = '';
                // // var_dump($row);exit;
                // $con1 = mysqli_connect('172.16.16.4','root','carshow123@','aicarshow') or die('数据库连接不上');
                // $row['car_type'] = Cache::get('car_type'.$row['study_car_type_id']);
                // unset($row['id']);
                // unset($row['pay_code']);
                // unset($row['study_car_type_id']);
                // unset($row['school_id']);
                // $new_space_id = Cache::get('space_'.$row['space_id']);
                // $cooperation_id = $this->get_cooperation_id($new_space_id);
                // $row['space_id'] = $new_space_id;
                // $row['cooperation_id'] = $cooperation_id;
                // $this->machinecar->insert($row);
            }
        }
        $con->close();
    }

    public function student()
    {
        $con = mysqli_connect('172.16.16.4','root','carshow123@','carshow') or die('数据库连接不上');

        $sql = "SELECT * FROM fa_student where id>10615";
        $res = mysqli_query($con,$sql);
        $i = 0;
        while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
            // $con1 = mysqli_connect('172.16.16.4','root','carshow123@','aicarshow') or die('数据库连接不上');
            $row['car_type'] = Cache::get('car_type'.$row['study_car_type_id']);
            $row['stu_id'] = $row['stunumber'];
            unset($row['id']);
            unset($row['study_car_type_id']);
            unset($row['coach_idcard']);
            unset($row['stunumber']);
            $new_space_id = Cache::get('space_'.$row['space_id']);
            $cooperation_id = $this->get_cooperation_id($new_space_id);
            $row['space_id'] = $new_space_id;
            $row['cooperation_id'] = $cooperation_id;
            unset($row['school_id']);
            unset($row['cash_surplus']);
            unset($row['complete_period']);
            unset($row['photoimage']);
            unset($row['idcard1image']);
            unset($row['idcard2image']);
            unset($row['registtime']);
            unset($row['study_sign']);
            unset($row['study_process']);
            var_dump($row);exit;

            if($row['idcard'] && $row['name']){
                $i+=1;
                // $keys_array = array_keys($row);
                // $keys_str = implode(',',$keys_array);
                // $values_array = array_values($row);
                // $values_str = implode("','",$values_array);
                // $sql2 = "INSERT INTO fa_student (".$keys_str.") VALUES ('".$values_str."')";
                // $re = $con1->query($sql2);
                $re = $this->student->insert($row);
                var_dump($re);exit;
            }else{
                $i+=1;
                unset($row['idcard']);
                unset($row['period_surplus']);
                unset($row['space_id']);
                unset($row['cooperation_id']);
                // $keys_array = array_keys($row);
                // $keys_str = implode(',',$keys_array);
                // $values_array = array_values($row);
                // $values_str = implode("','",$values_array);
                // $sql2 = "INSERT INTO fa_intent_student (".$keys_str.") VALUES ('".$values_str."')";
                // $sql2 = "INSERT INTO fa_intent_student (openid,name,sex,phone,regis_status,createtime,updatetime,car_type,stu_id) VALUES ('oIZ7d4rAxDatNIjyDrNlYQXZY9Qk','王路','male','13632780377','1','1593790260','1600858876','cartype2','CSN20200703233100374669')";
                // $re = $con1->query($sql2);
                // var_dump($re,$sql2);
                $re = $this->intentstudent->insert($row);
                var_dump($re);exit;
            }
            var_dump($i);
            // var_dump($row);exit;

            // $new_space_id = Cache::get('space_'.$row['space_id']);
            // $cooperation_id = $this->get_cooperation_id($new_space_id);
            // $row['space_id'] = $new_space_id;
            // $row['cooperation_id'] = $cooperation_id;
            // $this->machinecar->insert($row);
        }
    }

    public function order()
    {
        $con = mysqli_connect('172.16.16.4','root','carshow123@','carshow') or die('数据库连接不上');

        $sql = "SELECT * FROM fa_order";
        $res = mysqli_query($con,$sql);
        $i = 0;
        while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
            $con1 = mysqli_connect('172.16.16.4','root','carshow123@','aicarshow') or die('数据库连接不上');
            $row['car_type'] = Cache::get('car_type'.$row['subject_cartype_id']);
            $row['subject_type'] = Cache::get('subject_type'.$row['subject_type_id']);
            $row['machine_id'] = Cache::get('machine_code'.$row['machine_code']);
            unset($row['id']);
            unset($row['subject_cartype_id']);
            unset($row['subject_type_id']);
            unset($row['belong_space_id']);
            unset($row['machine_code']);
            unset($row['serial_number']);
            unset($row['bank_serial_number']);
            unset($row['should_money']);
            unset($row['discount_money']);
            unset($row['actually_money']);
            unset($row['auditTime']);
            unset($row['school_id']);
            unset($row['authorize']);
            unset($row['payment_type']);
            unset($row['paytime']);
            $new_space_id = Cache::get('space_'.$row['space_id']);
            $cooperation_id = $this->get_cooperation_id($new_space_id);
            $row['space_id'] = $new_space_id;
            $row['cooperation_id'] = $cooperation_id;
            $sql = "SELECT * FROM `fa_student` WHERE stu_id='".$row['stu_id']."'";
            $stu = mysqli_query($con1,$sql);
            $i+=1;
            foreach($row as $k=>$v){
                if($v==''){
                    unset($row[$k]);
                }
            }
            $keys_array = array_keys($row);
            $keys_str = implode(',',$keys_array);
            var_dump($row);exit;
            $values_array = array_values($row);
            $values_str = implode("','",$values_array);
            if($stu->num_rows){
                $sql2 = "INSERT INTO fa_order (".$keys_str.") VALUES ('".$values_str."')";
                $or = $con1->query($sql2);
                var_dump($or);
                exit;
            }else{
                $sql3 = "SELECT * FROM `fa_intent_student` WHERE stu_id='".$row['stu_id']."'";
                $intent_stu = mysqli_query($con1,$sql3);
                if($intent_stu->num_rows){
                    $sql3 = "INSERT INTO fa_temporary_order (".$keys_str.") VALUES ('".$values_str."')";
                    $or = $con1->query($sql3);
                    var_dump($or);
                    exit;
                }else{
                    var_dump($row['ordernumber']);
                }
            }
            exit;
        }
    }

    public function error_stu()
    {
        $stu = ["CSN20200706074825209414","CSN20200709181123991311","CSN20200716110135954046","CSN20200819185532438147","CSN20200826115338435241","CSN20200904201802599539","CSN20200907210830229126","CSN20201108154008709499","CSN20201120115831815352","CSN20201120121334322042","CSN20201125140258347156","CSN20201205172059634343","CSN20200703233456532231","CSN20201229133138148625","CSN20210106142511206365","CSN20210106142545111958","CSN20210106142611467578"];
        foreach($stu as $v){
            $con = mysqli_connect('172.16.16.4','root','carshow123@','carshow') or die('数据库连接不上');
            $sql = "SELECT * FROM `fa_student` WHERE stunumber='".$v."'";
            $res = mysqli_query($con,$sql);
            while($row = mysqli_fetch_array($res, MYSQLI_ASSOC)){
                $row['car_type'] = Cache::get('car_type'.$row['study_car_type_id']);
                $row['stu_id'] = $row['stunumber'];
                unset($row['id']);
                unset($row['study_car_type_id']);
                unset($row['coach_idcard']);
                unset($row['stunumber']);
                $new_space_id = Cache::get('space_'.$row['space_id']);
                $cooperation_id = $this->get_cooperation_id($new_space_id);
                $row['space_id'] = $new_space_id;
                $row['cooperation_id'] = $cooperation_id;
                unset($row['school_id']);
                unset($row['cash_surplus']);
                unset($row['complete_period']);
                unset($row['photoimage']);
                unset($row['idcard1image']);
                unset($row['idcard2image']);
                unset($row['registtime']);
                unset($row['study_sign']);
                unset($row['study_process']);
                // var_dump($row);exit;
                if($row['idcard'] && $row['name']){
                    $re = $this->student->insert($row);
                }else{
                    unset($row['idcard']);
                    unset($row['period_surplus']);
                    unset($row['space_id']);
                    unset($row['cooperation_id']);
                    $re = $this->intentstudent->insert($row);
                }
                if(!$re){
                    var_dump($v);
                }
            }
        }
    }

    public function studentcode($machine_id)
    {
        //获取token
        $APPID = 'wx5de0d959a2e0f9cb';
        $AppSecret = '650fe51e8e1f10e93f7ae5025b53ff77';
        $tokenUrl= "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" .$APPID ."&secret=".$AppSecret;
        $getArr=array();
        $tokenArr=json_decode($this->send_post($tokenUrl,$getArr,"GET"));
        $access_token=$tokenArr->access_token;
        //生成二维码
        $path="/pages/qrcode/qrcode?machine_id=".$machine_id;
        $width = 500;
        $data = [
            'access_token'=>$access_token,
            "path"=>$path,
        ];
        $post_data= json_encode($data,true);
        $url="https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token=".$access_token;
        $result= $this->api_notice_increment($url,$post_data);
        $time = time();
        $date = date('Y-m-d',$time );
        $filepath = ROOT_PATH."public/uploads/code/student/".$date.'/';
        if(!file_exists($filepath)){
            mkdir($filepath,0777,true);
        }
        $imagepage = '/uploads/code/student/'.$date.'/'.$time.'-'.$machine_id.'.jpg';
        file_put_contents(ROOT_PATH.'public'.$imagepage,$result);
        return $imagepage;
    }

    public function experience_code($machine_id){
        //获取token
        $APPID = 'wx5de0d959a2e0f9cb';
        $AppSecret = '650fe51e8e1f10e93f7ae5025b53ff77';
        $tokenUrl= "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" .$APPID ."&secret=".$AppSecret;
        $getArr=array();
        $tokenArr=json_decode($this->send_post($tokenUrl,$getArr,"GET"));
        $access_token=$tokenArr->access_token;
        //生成二维码
        $path="/pages/expcode/expcode?machine_id=".$machine_id;
        $data = [
            'access_token'=>$access_token,
            "path"=>$path,
        ];
        $post_data= json_encode($data,true);
        $url="https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token=".$access_token;
        $result= $this->api_notice_increment($url,$post_data);
        $time = time();
        $date = date('Y-m-d',$time);
        $filepath = ROOT_PATH."public/uploads/code/experience/".$date.'/';
        if(!file_exists($filepath)){
            mkdir($filepath,0777,true);
        }
        $imagepage = '/uploads/code/experience/'.$date.'/'.$time.'-'.$machine_id.'.jpg';
        file_put_contents(ROOT_PATH.'public'.$imagepage,$result);
        return $imagepage;
    }
    public function backwindow_code($machine_id){
        //获取token
        $APPID = 'wx83c669dfc1701f18';
        $AppSecret = 'bc424ce1c66f2cd99f246556f665ca29';
        $tokenUrl= "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" .$APPID ."&secret=".$AppSecret;
        $getArr=array();
        $tokenArr=json_decode($this->send_post($tokenUrl,$getArr,"GET"));
        $access_token=$tokenArr->access_token;
        //生成二维码
        $path="pages/backwindow/backwindow?machine_id=".$machine_id;
        $width=500;
        $data = [
            'access_token'=>$access_token,
            "path"=>$path,
        ];
        $post_data= json_encode($data,true);
        $url="https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token=".$access_token;
        $result= $this->api_notice_increment($url,$post_data);
        $time = time();
        $date = date('Y-m-d',$time);
        $filepath = ROOT_PATH."public/uploads/code/backwindow/".$date.'/';
        if(!file_exists($filepath)) {
            mkdir($filepath,0777,true); 
        }
        $imagepage = '/uploads/code/backwindow/'.$date.'/'.$time.'-'.$machine_id.'.jpg';
        file_put_contents(ROOT_PATH.'public'.$imagepage,$result);
        return $imagepage;
    }

    function send_post($url, $post_data,$method='POST') {
        $postdata = http_build_query($post_data);
        $options = array(
            'http' => array(
                'method' => $method, //or GET
                'header' => 'Content-type:application/x-www-form-urlencoded',
                'content' => $postdata,
                'timeout' => 15 * 60 // 超时时间（单位:s）
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        return $result;
    }
    
    function api_notice_increment($url,$data)
    {
        $curl = curl_init();
        $a = strlen($data);
        $header = array("Content-Type: application/json; charset=utf-8","Content-Length: $a");
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl,CURLOPT_POST,1);
        curl_setopt($curl,CURLOPT_POSTFIELDS,$data);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    
    }

}