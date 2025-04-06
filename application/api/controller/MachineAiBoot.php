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
class MachineAiBoot extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    protected $common = null;
    protected $jiangxi = null;
    protected $machineai = null;
    protected $place = null;
    protected $studyprocess = null;
    protected $reportcard = null;
    protected $keersort = null;
    protected $kesansort = null;
    protected $faceimage = null;
    protected $student = null;
    protected $trainstatistic = null;
    protected $warnexam = null;
    protected $ordersc = null;
    
    public function _initialize()
    {
        parent ::_initialize();
        $this->common = new \app\api\controller\Common;
        $this->machineai = new \app\admin\model\Machineai;
        $this->place = new \app\admin\model\Place;
        $this->studyprocess = new \app\admin\model\Studyprocess;
        $this->reportcard = new \app\admin\model\Reportcard;
        $this->keersort = new \app\admin\model\Keersort;
        $this->kesansort = new \app\admin\model\Kesansort;
        $this->student = new \app\admin\model\Student;
        $this->trainstatistic = new \app\admin\model\Trainstatistic;
        $this->warnexam = new \app\admin\model\Warnexam;
        $this->ordersc = new \app\admin\model\Ordersc;
        
    }

    public function test()
    {
        $params = Cache::get('version');
        // var_dump($params);
        $update['main_versions'] = $params['MainVersions'];
        $update['local_versions'] = $params['LocalVersions'];
        $this->machineai->where(['machine_code'=>$params['machine_code']])->update($update);
    }

    /**
     * 开机获取logo
     */
    public function get_company_info()
    {
        $params = $this->request->post();
        $params['machine_code'] = 'JQR001';
        if(empty($params['machine_code'])){
            $this->error('参数缺失');
        }

        if(array_key_exists('MainVersions',$params) && array_key_exists('LocalVersions',$params)){
            // Cache::set('version',$params);
            $update['main_versions'] = $params['MainVersions'];
            $update['local_versions'] = $params['LocalVersions'];
            $this->machineai->where(['machine_code'=>$params['machine_code']])->update($update);
        }
        $res = $this->machineai->with('space')->where(['machine_code'=>$params['machine_code']])->find();
        $info['info_msg'] = $res['space']['info_msg'];
        $info['logo_image'] = '/uploads/20211122/5f28562c9d9143d022e79cb6c403ded2.png';
        if($res['space']['logo_image']){
            $info['logo_image'] = $res['space']['logo_image'];
        }
         $this->success('返回成功',$info);
    }



    public function getreportcard()
    {
        $res = Cache::get('reportcardtest');
        var_dump($res);
    }






    public function get_upload_log()
    {
        $res = Cache::get('upload');
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
     * 获取机器人日志
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


    /**
     * 模拟机开机
     */
    public function machine_start(){
        $params = $this->request->post();
        // $params['ordernumber'] = 'CON20210629170951790472';
        // $params['student_type'] = 'intent_student';
        if(empty($params['ordernumber']) || empty($params['student_type'])){
            $this->error('参数缺失');
        }
        $ordernumber = $params['ordernumber'];
        $student_type = $params['student_type'];
        
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
        // $params['ordernumber'] = 'CON20211103204144977826';
        // $params['student_type'] = 'student';
        if(empty($params['ordernumber']) || empty($params['student_type'])){
            $this->error('参数缺失');
        }
        $ordernumber = $params['ordernumber'];
        $student_type = $params['student_type'];
        
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
        Cache::set('end',$params);
        // $params['ordernumber'] = 'CON20210629170951790472';
        // $params['student_type'] = 'intent_student';
        // $params['level'] = '科目二直角转弯，科目二半坡起步';
        if(empty($params['ordernumber']) || !array_key_exists('level',$params) || empty($params['student_type'])){
            $this->error('参数缺失');
        }
        $ordernumber = $params['ordernumber'];
        $level = $params['level'];
        $update['level'] = $level ;
        $student_type = $params['student_type'];
        $order = $this->ordersc->where('ordernumber',$ordernumber)->find();

        $should_endtime = $order['should_endtime'];
        $update['endtime'] = time();
        $update['updatetime'] = time();
        if(time()>$should_endtime){
            $update['endtime'] = $should_endtime;
        }
        $update['order_status'] = 'finished';
        $res = $this->ordersc->where('ordernumber',$ordernumber)->update($update);

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
