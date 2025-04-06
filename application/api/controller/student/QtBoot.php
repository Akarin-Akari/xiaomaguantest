<?php

namespace app\api\controller\student;

use app\common\controller\Api;
use QRcode;
use think\cache;
use think\Db;

/**
 * 开机流程所需接口
 */
class QtBoot extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    const USER_KEY = '51DF5CEC7F2368680B0FF2A259EB5C4A';
    const QR_CODE ='http://intranet8.ngrok.91ygxc.com/service/xiaolu/machine/loginQRCode';//获取机器二维码接口
    const SHUT_DOWN = 'http://intranet8.ngrok.91ygxc.com/service/xiaolu/machine/record';

    public function _initialize()
    {
        parent ::_initialize();
        $this->machinecar = new \app\admin\model\Machinecar;
        $this->order = new \app\admin\model\Order;
        $this->temporaryorder = new \app\admin\model\Temporaryorder;
        $this->student = new \app\admin\model\Student;
        $this->intentstudent = new \app\admin\model\Intentstudent;

        $this->common = new \app\api\controller\Common;
    }

    /**
     * 获取订单信息
     */
    public function qt_order()
    {
        $res = file_get_contents("php://input");
        // $res = Cache::get('paramstest1');

        if($res){
            $params = json_decode($res,1);
        }else{
            $this->error('参数缺失');
        }
        // $params['phone'] = '15555555555';
        // $params['car_type'] = 'cartype1';
        // $params['machine_code'] = 'FJYG202111001';
        // $params['starttime'] = time();
        // $params['endtime'] = time();
        if(empty($params['phone']) || empty($params['car_type'])|| empty($params['machine_code'])||empty($params['starttime']) || empty($params['endtime'])){
            $this->error('参数缺失');
        }
        
        $this->check_sign($params);

        $phone = $params['phone'];
        $machine_code = $params['machine_code'];
        $machine_id = $this->machinecar->where(['machine_code'=>$machine_code])->find()['id'];
        $student = $this->getstudent($phone,$params['car_type']);
        $insert = [];
        $insert['ordernumber'] = 'CON'.date("YmdHis") . mt_rand(100000, 999999);
        $insert['stu_id'] = $student['stu_id'] ;
        $insert['machine_id'] = $machine_id ;
        $insert['space_id'] = $student['space_id'] ;
        $insert['cooperation_id'] = $student['cooperation_id'] ;
        $insert['car_type'] = $params['car_type'] ;
        $insert['reserve_starttime'] = $params['starttime'];
        $insert['reserve_endtime'] = $params['endtime'];
        $insert['should_endtime'] = $params['endtime'];
        $this->order->insert($insert);
        $order = $this->order->where(['ordernumber'=>$insert['ordernumber']])->find();
        $data['ordernumber'] = $order['ordernumber'];
        $machinecar = $this->machinecar->where('id',$order['machine_id'])->find();
        if($machinecar){
            $data['sim'] = $machinecar['sim'];
            $data['address'] = $machinecar['address'];
            $data['imei'] = $machinecar['imei'];
            $data['sn'] = $machinecar['sn'];
            $data['terminal_equipment'] = $machinecar['terminal_equipment'];
            $data['study_machine'] = $machinecar['study_machine'];
            Cache::set('machine_'.$machinecar['machine_code'],$data,5*60);
        }else{
            $this->error('返回失败，暂无当前机器码');
        }
        
        $data['idcard']= $student['idcard'];
        $data['stu_id'] = $student['stu_id'];
        $data['stu_name'] = $student['name'];
        $data['phone'] =  $student['phone'];
        $data['subject_type'] = $order['subject_type_text'];
        $data['student_type'] = 'student';
        Cache::set('machine_'.$machinecar['machine_code'],$data,5*60);
        $this->success('返回成功');
    }

    public function qt_order_test()
    {
        $res = Cache::get('paramstest1');
        // $res2 = Cache::get('paramstest2');
        $params = json_decode($res,1);
        // $res2 = json_decode($res2,1);

        if(empty($params['phone']) || empty($params['car_type'])|| empty($params['machine_code'])||empty($params['starttime']) || empty($params['endtime'])){
            $this->error('参数缺失');
        }
        $this->check_sign($params);

        $phone = $params['phone'];
        $machine_code = $params['machine_code'];
        $machine_id = $this->machinecar->where(['machine_code'=>$machine_code])->find()['id'];
        $student = $this->getstudent($phone,$params['car_type']);
        $insert = [];
        $insert['ordernumber'] = 'CON'.date("YmdHis") . mt_rand(100000, 999999);
        $insert['stu_id'] = $student['stu_id'] ;
        $insert['machine_id'] = $machine_id ;
        $insert['space_id'] = $student['space_id'] ;
        $insert['cooperation_id'] = $student['cooperation_id'] ;
        $insert['car_type'] = $params['car_type'] ;
        $insert['reserve_starttime'] = $params['starttime'];
        $insert['reserve_endtime'] = $params['endtime'];
        $insert['should_endtime'] = $params['endtime'];
        $this->order->insert($insert);
        $order = $this->order->where(['ordernumber'=>$insert['ordernumber']])->find();
        $data['ordernumber'] = $order['ordernumber'];
        $machinecar = $this->machinecar->where('id',$order['machine_id'])->find();
        if($machinecar){
            $data['sim'] = $machinecar['sim'];
            $data['address'] = $machinecar['address'];
            $data['imei'] = $machinecar['imei'];
            $data['sn'] = $machinecar['sn'];
            $data['terminal_equipment'] = $machinecar['terminal_equipment'];
            $data['study_machine'] = $machinecar['study_machine'];
            Cache::set('machine_'.$machinecar['machine_code'],$data,5*60);
        }else{
            $this->error('返回失败，暂无当前机器码');
        }
        
        $data['idcard']= $student['idcard'];
        $data['stu_id'] = $student['stu_id'];
        $data['stu_name'] = $student['name'];
        $data['phone'] =  $student['phone'];
        $data['subject_type'] = $order['subject_type_text'];
        $data['student_type'] = 'student';
        Cache::set('machine_'.$machinecar['machine_code'],$data,5*60);
        $this->success('返回成功');
    }

    public function get_code()
    {
        $res = Cache::get('paramstest1');
        $res = json_decode($res,1);
        // var_dump(Cache::get('paramstest1'));
        $params['starttime'] = $res['starttime'];
        $params['endtime'] = ($params['starttime']+360);
        $params['phone'] = $res['phone'];
        $params['signStr'] = $this->signStr();
        $params['timeStamp'] = time();
        $params['userKey'] = self::USER_KEY;
        ksort($params);
        $str = http_build_query($params, '', '&');
        $sign = md5($str);
        $params['sign'] = strtoupper($sign);
        $url = self::SHUT_DOWN;
        $data = json_encode($params);
        $r = $this->common->send_post_json($url,$data);
        $code = json_decode($r[1],1)['code'];
        if($code == 1){
            $this->success('返回成功');
        }else{
            $this->error(json_decode($r[1],1)['message']);
        }
    }

    /**
     * 获取二维码
     */
    public function get_qr_code($machine_code)
    {
        $params['machine_code'] = $machine_code;
        $params['signStr'] = $this->signStr();
        $params['timeStamp'] = time();
        $params['userKey'] = self::USER_KEY;
        ksort($params);

        $str = http_build_query($params, '', '&');
        $sign = md5($str);
        
        $params['sign'] = strtoupper($sign);
        $url = self::QR_CODE;
        $data = json_encode($params);
        $r = $this->common->send_post_json($url, $data );
        $qrString = json_decode($r[1],1)['data']['qrString'];
        $filename = $this->scerweima($qrString,$machine_code);
        return $filename;
    }
    

    public function scerweimatest(){
        require_once 'phpqrcode.php';
        $params['machine_code'] = '0027';
        $params['signStr'] = $this->signStr();
        $params['timeStamp'] = time();
        $params['userKey'] = self::USER_KEY;
        ksort($params);

        $str = http_build_query($params, '', '&');
        $sign = md5($str);
        
        $params['sign'] = strtoupper($sign);
        $url = self::QR_CODE;
        $data = json_encode($params);
        $r = $this->common->send_post_json($url, $data );
        $url = json_decode($r[1],1)['data']['qrString'];
        // $url = 'https://www.baidu.co?simulatorNum=0021&timeStamp=';
        $value = $url;					//二维码内容  
        $errorCorrectionLevel = 'H';	//容错级别  
        $matrixPointSize = 10;			//生成图片大小  
        //生成二维码图片
        $filename =  "uploads/qrcode/".$params['machine_code'].'.png';
        QRcode::png($value,$filename , $errorCorrectionLevel, $matrixPointSize, 2);  
        $logo = "uploads/code/logo/logo.png"; 	//准备好的logo图片   
        $QR = $filename;			//已经生成的原始二维码图  
        
        if (file_exists($logo)) {
            $QR = imagecreatefromstring(file_get_contents($QR));   		//目标图象连接资源。
            $logo = imagecreatefromstring(file_get_contents($logo));   	//源图象连接资源。
            $QR_width = imagesx($QR);			//二维码图片宽度   
            $QR_height = imagesy($QR);			//二维码图片高度   
            $logo_width = imagesx($logo);		//logo图片宽度   
            $logo_height = imagesy($logo);		//logo图片高度   
            $logo_qr_width = $QR_width / 4;   	//组合之后logo的宽度(占二维码的1/5)
            $scale = $logo_width/$logo_qr_width;   	//logo的宽度缩放比(本身宽度/组合后的宽度)
            $logo_qr_height = $logo_height/$scale;  //组合之后logo的高度
            $from_width = ($QR_width - $logo_qr_width) / 2;   //组合之后logo左上角所在坐标点
            
            //重新组合图片并调整大小
            /*
            *	imagecopyresampled() 将一幅图像(源图象)中的一块正方形区域拷贝到另一个图像中
            */
            imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,$logo_qr_height, $logo_width, $logo_height); 
        }
        $file_path = ROOT_PATH.'public/uploads/qrcode/'.$params['machine_code'].'.png';
        //输出图片  
        imagepng($QR, $file_path);
        
        return '/uploads/qrcode/'.$params['machine_code'].'.png';

    }


    private function scerweima($url,$machine_code){
        require_once 'phpqrcode.php';
        // $url = 'https://www.baidu.co?simulatorNum=0021&timeStamp=';
        $value = $url;					//二维码内容  
        $errorCorrectionLevel = 'H';	//容错级别  
        $matrixPointSize = 6;			//生成图片大小  
        //生成二维码图片
        $filename =  ROOT_PATH."public/uploads/qrcode/".$machine_code.'.png';
        QRcode::png($value,$filename , $errorCorrectionLevel, $matrixPointSize, 2);  
        $logo = ROOT_PATH."public/uploads/code/logo/logo.png"; 	//准备好的logo图片   
        $QR = $filename;			//已经生成的原始二维码图  
        
        if (file_exists($logo)) {
            $QR = imagecreatefromstring(file_get_contents($QR));   		//目标图象连接资源。
            $logo = imagecreatefromstring(file_get_contents($logo));   	//源图象连接资源。
            // var_dump($logo);exit;
            $QR_width = imagesx($QR);			//二维码图片宽度   
            $QR_height = imagesy($QR);			//二维码图片高度   
            $logo_width = imagesx($logo);		//logo图片宽度   
            $logo_height = imagesy($logo);		//logo图片高度   
            $logo_qr_width = $QR_width / 4;   	//组合之后logo的宽度(占二维码的1/5)
            $scale = $logo_width/$logo_qr_width;   	//logo的宽度缩放比(本身宽度/组合后的宽度)
            $logo_qr_height = $logo_height/$scale;  //组合之后logo的高度
            $from_width = ($QR_width - $logo_qr_width) / 2;   //组合之后logo左上角所在坐标点
            
            //重新组合图片并调整大小
            /*
            *	imagecopyresampled() 将一幅图像(源图象)中的一块正方形区域拷贝到另一个图像中
            */
            imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,$logo_qr_height, $logo_width, $logo_height); 
        }
        $file_path = ROOT_PATH.'public/uploads/qrcode/'.$machine_code.'.png';
        //输出图片  
        imagepng($QR, $file_path);
        
        return 'uploads/qrcode/'.$machine_code.'.png';

    }

    private function check_sign($params)
    {
        $sign = $params['sign'];
        $info['phone'] = $params['phone'];
        $info['car_type'] = $params['car_type'];
        $info['machine_code'] = $params['machine_code'];
        $info['starttime'] = $params['starttime'];
        $info['endtime'] = $params['endtime'];
        $info['signStr'] = $params['signStr'];
        $info['timeStamp'] = $params['timeStamp'];
        ksort($info);
        $str2 =  http_build_query($info, '', '&');
        $srtingSignTemp2 = $str2.'&userKey='.self::USER_KEY;
        $sign2 = strtoupper(md5($srtingSignTemp2)) ;
        if($sign !== $sign2){
            $this->error('sign值校验失败');
        }
    }

    // 随机字符
    private function rand_char(){
        $base = 62;
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return $chars[mt_rand(1, $base) - 1];
    }

    private function getstudent($phone,$car_type)
    {
        $student = $this->student->where(['phone'=>$phone])->find();

        if(!$student){
            $params['phone'] = $phone;
            $params['car_type'] = $car_type;
            $params['space_id'] = 41;
            $params['cooperation_id'] = 151;
            $params['stu_id'] = 'QTN'.date("YmdHis") . mt_rand(100000, 999999);
            $this->student->insert($params);
            $student = $this->student->where(['phone'=>$phone])->find();
        }
        
        return $student;
    }

    private function signStr()
    {
        $str_time = $this->dec62($this->msectime());
        // 8位随机字符串
        $code = $this->rand_char().$str_time;
        return $code;
    }

    private function msectime(){
        $arr = explode(' ', microtime());
        $tmp1 = $arr[0];
        $tmp2 = $arr[1];
        return (float)sprintf('%.0f', (floatval($tmp1) + floatval($tmp2)) * 1000);
    }

    // 10进制转62进制
    private function dec62($dec){
        $base = 62;
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $ret = '';
        for($t = floor(log10($dec) / log10($base)); $t >= 0; $t--){
            $a = floor($dec / pow($base, $t));
            $ret .= substr($chars, $a, 1);
            $dec -= $a * pow($base, $t);
        }
        return $ret;
    }
}