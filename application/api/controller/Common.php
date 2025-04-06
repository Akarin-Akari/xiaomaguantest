<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\exception\UploadException;
use app\common\library\Upload;
use app\common\model\Area;
use app\common\model\Version;
use fast\Random;
use PDO;
use think\Config;
use think\Hook;

/**
 * 公共接口
 */
class Common extends Api
{
    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $student = null;
    protected $order = null;
    protected $ordersc = null;
    protected $temporaryorder = null;
    protected $space = null;
    protected $intentstudent = null;

    const MAP_KEY = 'SOWBZ-QT2RN-ZGVFU-S4IXP-7BRG5-FCFFM';
    public function _initialize()
    {
        $this->student = new \app\admin\model\Student;
        $this->intentstudent = new \app\admin\model\Intentstudent;
        $this->order = new \app\admin\model\Order;
        $this->ordersc = new \app\admin\model\Ordersc;
        $this->temporaryorder = new \app\admin\model\Temporaryorder;
        $this->space = new \app\admin\model\Space;
        // $this->zhenyang = new \app\api\controller\zhenyang\Info;
        parent ::_initialize();
    }

    /**
     * 加载初始化
     *
     * @param string $version 版本号
     * @param string $lng     经度
     * @param string $lat     纬度
     */
    public function init()
    {
        if ($version = $this->request->request('version')) {
            $lng = $this->request->request('lng');
            $lat = $this->request->request('lat');

            //配置信息
            $upload = Config::get('upload');
            //如果非服务端中转模式需要修改为中转
            if ($upload['storage'] != 'local' && isset($upload['uploadmode']) && $upload['uploadmode'] != 'server') {
                //临时修改上传模式为服务端中转
                set_addon_config($upload['storage'], ["uploadmode" => "server"], false);

                $upload = \app\common\model\Config::upload();
                // 上传信息配置后
                Hook::listen("upload_config_init", $upload);

                $upload = Config::set('upload', array_merge(Config::get('upload'), $upload));
            }

            $upload['cdnurl'] = $upload['cdnurl'] ? $upload['cdnurl'] : cdnurl('', true);
            $upload['uploadurl'] = preg_match("/^((?:[a-z]+:)?\/\/)(.*)/i", $upload['uploadurl']) ? $upload['uploadurl'] : url($upload['storage'] == 'local' ? '/api/common/upload' : $upload['uploadurl'], '', false, true);

            $content = [
                'citydata'    => Area::getCityFromLngLat($lng, $lat),
                'versiondata' => Version::check($version),
                'uploaddata'  => $upload,
                'coverdata'   => Config::get("cover"),
            ];
            $this->success('', $content);
        } else {
            $this->error(__('Invalid parameters'));
        }

    }

    /**
     * 上传文件
     * @ApiMethod (POST)
     * @param File $file 文件流
     */
    public function upload()
    {
        Config::set('default_return_type', 'json');
        //必须设定cdnurl为空,否则cdnurl函数计算错误
        Config::set('upload.cdnurl', '');
        $chunkid = $this->request->post("chunkid");
        if ($chunkid) {
            if (!Config::get('upload.chunking')) {
                $this->error(__('Chunk file disabled'));
            }
            $action = $this->request->post("action");
            $chunkindex = $this->request->post("chunkindex/d");
            $chunkcount = $this->request->post("chunkcount/d");
            $filename = $this->request->post("filename");
            $method = $this->request->method(true);
            if ($action == 'merge') {
                $attachment = null;
                //合并分片文件
                try {
                    $upload = new Upload();
                    $attachment = $upload->merge($chunkid, $chunkcount, $filename);
                } catch (UploadException $e) {
                    $this->error($e->getMessage());
                }
                $this->success(__('Uploaded successful'), ['url' => $attachment->url, 'fullurl' => cdnurl($attachment->url, true)]);
            } elseif ($method == 'clean') {
                //删除冗余的分片文件
                try {
                    $upload = new Upload();
                    $upload->clean($chunkid);
                } catch (UploadException $e) {
                    $this->error($e->getMessage());
                }
                $this->success();
            } else {
                //上传分片文件
                //默认普通上传文件
                $file = $this->request->file('file');
                try {
                    $upload = new Upload($file);
                    $upload->chunk($chunkid, $chunkindex, $chunkcount);
                } catch (UploadException $e) {
                    $this->error($e->getMessage());
                }
                $this->success();
            }
        } else {
            $attachment = null;
            //默认普通上传文件
            $file = $this->request->file('file');
            try {
                $upload = new Upload($file);
                $attachment = $upload->upload();
            } catch (UploadException $e) {
                $this->error($e->getMessage());
            }

            $this->success(__('Uploaded successful'), ['url' => $attachment->url, 'fullurl' => cdnurl($attachment->url, true)]);
        }

    }
    
    /**
     * 获取订单详情
     */
    public function get_order_detail($ordernumber,$student_type,$type_index)
    {
        $where['ordernumber'] = $ordernumber;
        if($student_type == 'student'){
            if($type_index == 'AI'){
                $order = $this->order->with(['space','student','coach'])->where($where)->find();
                $student_source = $this->space->where('id',$order['student']['space_id'])->find();
                $row['source_space_name'] = $student_source['space_name'];
            }else{
                $order = $this->ordersc->with(['space','student','coachsc'])->where($where)->find();

                $student_source = $this->space->where('id',$order['student']['space_id'])->find();
                $row['source_space_name'] = $student_source['space_name'];
            }
        }else{
            $order = $this->temporaryorder->with(['space','intentstudent','coach'])->where($where)->find();
            $row['source_space_name'] = '';
        }
        $week = $this->getWeek($order['reserve_starttime']);
        $row['week'] = $week;
        $row['ordernumber'] = $order['ordernumber'];
        $row['study_space_name'] = $order['space']['space_name'];
        $row['reserve_starttime'] = $order['reserve_starttime'];
        $row['reserve_endtime'] = $order['reserve_endtime'];
        if($type_index == 'AI'){
            $row['coach_name'] = $order['coach']['name'];
        }else{
            $row['coach_name'] = $order['coachsc']['name'];            
        }
        $row['order_status'] = $order['order_status'];
        $row['student_type'] = $student_type;
        if(in_array($order['order_status'],['executing','accept_unexecut'])){
            $row['edit_status'] = 'finish';
        }else{
            $row['edit_status'] = 'no_edit';
        }
        $row['starttime'] = $order['starttime'];
        $row['endtime'] = $order['endtime'];
        $row['ordernumber'] = $order['ordernumber'];
        $row['stu_id'] = $order['stu_id'];

        if($student_type == 'student'){
            $row['student_name'] = $order['student']['name'];
            $row['student_phone'] = $order['student']['phone'];
        }else{
            $row['student_name'] = $order['intentstudent']['name'];
            $row['student_phone'] = $order['intentstudent']['phone'];
        }
        $row['car_type'] = $order['car_type_text'];
        $row['subject_type'] = $order['subject_type_text'];
        $row['space_id'] = $order['space_id'];

        return $row;
    }


    
    public function curl($url){
        $info=curl_init();
        curl_setopt($info,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($info,CURLOPT_HEADER,0);
        curl_setopt($info,CURLOPT_NOBODY,0);
        curl_setopt($info,CURLOPT_TIMEOUT, 3);
        curl_setopt($info,CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($info,CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($info,CURLOPT_URL,$url);
        $output= curl_exec($info);
        curl_close($info);
        return $output;
    }

    function send_post($url, $post_data,$method='POST') {
        $postdata = http_build_query($post_data);
        $options = array(
            'http' => array(
                'method' => $method, //or GET
                'header' => 'Content-type:application/x-www-form-urlencoded',
                'content' => $postdata,
                'timeout' => 60 // 超时时间（单位:s）
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        return $result;
    }

    function send_post_json($url, $jsonStr) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen($jsonStr)
            )
        );
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    
        return array($httpCode, $response);
        
    }

    //获取本周，本月时间
    public function get_time(){
        $date=date('Y-m-d').' 00:00:00';  //当前日期
        $first=1; //$first =1 表示每周星期一为开始日期 0表示每周日为开始日期
        $w=date('w',strtotime($date));  //获取当前周的第几天 周日是 0 周一到周六是 1 - 6
        $week=date('Y-m-d H:i:s',strtotime("$date -".($w ? $w - $first : 6).' days')); //获取本周开始日期，如果$w是0，则表示周日，减去 6 天
        $month = date('Y-m-d H:i:s',mktime(0, 0, 0, date('m'), 1, date('Y')));
        $timestamp=strtotime($date);
        $firstday=date('Y-m-01',strtotime(date('Y',$timestamp).'-'.(date('m',$timestamp)-1).'-01'));
        $lastday=date('Y-m-d',strtotime("$firstday +1 month -1 day"));
        $data['day'] = $date;
        $data['week'] = $week;
        $data['month'] = $month;
        $data['last_month'] = [
            'firstday'=>$firstday.' 00:00:00',
            'lastday' =>$lastday.' 23:59:59'
        ];
        return $data;
    }

    function post($url, $post_data,$method='POST') {
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
    

    /** 
     * 完成订单
     */
    public function finish($ordernumber,$student_type){
        $where['order_status'] = ['in',['accept_unexecut','executing']];
        $where['ordernumber'] = $ordernumber;
        if($student_type == 'student'){
            $order = $this->order->where('ordernumber',$ordernumber)->find();
            if(!$order){
                $order = $this->ordersc->where('ordernumber',$ordernumber)->find();
                $type = 'SC';
            }else{
                $type = 'AI';
            }
        }else{
            $order = $this->temporaryorder->where('ordernumber',$ordernumber)->find();
        }
        if(!$order){
            $this->error('查询订单异常');
        }
        $update['order_status'] = 'finished';
        $update['updatetime'] = time();
        if($student_type == 'student'){
            if($type == 'AI'){
                $res = $this->order->where($where)->update($update);
            }elseif($type == 'SC'){
                $res = $this->ordersc->where($where)->update($update);
            }
        }else{
            $res = $this->temporaryorder->where($where)->update($update);
        }
        if($res){
            $this->success('返回成功');
        }
        $this->error('提交异常，请重新提交');
    }

    /**
     * 当前学员所有馆的一个历史记录
     */
    public function get_stu_history($stu_id,$space_id,$student_type,$ordernumber,$type_index){
        $where['stu_id'] = $stu_id;
        $where['order_status'] = 'finished';
        $where['ordernumber'] = ['neq',$ordernumber];
        $where['order.space_id'] = $space_id;

        if($student_type=='student'){
            if($type_index == 0){
                $order = $this->order->with(['space'])->where($where)->order('id desc')->select();
            }else{
                $where['ordersc.space_id'] = $space_id;
                $order = $this->ordersc->with(['space'])->where($where)->order('id desc')->select();
            }
        }else{
            $order = $this->temporaryorder->with(['space'])->where($where)->order('id desc')->select();
        }
        $list = [];
        if($order){
            foreach($order as $k=>$v){
                $list[$k]['subject_type'] = $v['subject_type_text'];
                $list[$k]['space_name'] = $v['space']['space_name'];
                $list[$k]['day'] = date('Y-m-d',$v['reserve_starttime']);
                $list[$k]['time'] = date('H:i',$v['starttime']).'-'.date('H:i',$v['endtime']);
            }
        }
        return $list;
    }

    /**
     * 
     */
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

    /**
     * 上传图片
     */
    public function uploadimage(){
        date_default_timezone_set("Asia/Shanghai"); //设置时区
        if($_FILES){
            $code = $_FILES['file'];//获取小程序传来的图片
            if(is_uploaded_file($_FILES['file']['tmp_name'])) {  
                //把文件转存到你希望的目录（不要使用copy函数）  
                $uploaded_file=$_FILES['file']['tmp_name'];
                $username = "min_img";
                //我们给每个用户动态的创建一个文件夹  
                $user_path=$_SERVER['DOCUMENT_ROOT']."/uploads/".$username.'/'.date("Y-m-d");
                //判断该用户文件夹是否已经有这个文件夹
                if(!file_exists($user_path)) {
                    //mkdir($user_path); 
                    mkdir($user_path,0777,true); 
                }
                
                //$move_to_file=$user_path."/".$_FILES['file']['name'];  
                $file_true_name=$_FILES['file']['name'];
    
                $move_to_file=$user_path."/".time().rand(1,1000)."-".date("Y-m-d").substr($file_true_name,strrpos($file_true_name,"."));//strrops($file_true,".")查找“.”在字符串中最后一次出现的位置  
                $upload = strstr($move_to_file,"/uploads");
                if(move_uploaded_file($uploaded_file,iconv("utf-8","gb2312",$move_to_file))) { 
                    $this->success("上传成功",$upload);
                }else {
                    $this->error("上传失败");
                }  
            } else {  
                $this->error("上传失败");
            }
        }
    }


    /**
     *  计算两组经纬度坐标 之间的距离
     *   params ：lat1 纬度1； lng1 经度1； lat2 纬度2； lng2 经度2； len_type （1:m or 2:km);
     *   return m or km
     */
	
    function GetDistance($lat1, $lng1, $lat2, $lng2, $len_type = 2, $decimal = 1)
    {
        $radLat1 = $lat1 * PI ()/ 180.0;   //PI()圆周率
        $radLat2 = $lat2 * PI() / 180.0;
        $a = $radLat1 - $radLat2;
        $b = ($lng1 * PI() / 180.0) - ($lng2 * PI() / 180.0);
        $s = 2 * asin(sqrt(pow(sin($a/2),2) + cos($radLat1) * cos($radLat2) * pow(sin($b/2),2)));
        $s = $s * 6378.137;
        $s = (int)round($s * 1000);
        return $s;
    }

    function getWeek($date_time)
    {
        $weekarray = ["星期日", "星期一", "星期二", "星期三", "星期四", "星期五", "星期六"];
        $xingqi = date("w", $date_time);
        return $weekarray[$xingqi];
    }

    /**
     * 预约验证学员
     */
    public function student_validate($stu_id,$space,$subject_type,$student_type,$order_type)
    {
        $space_id = $space['id'];
        //是否营业
        if($space['space_state'] =='no'){
            $this->error('当前场馆不开放');
        }
        if($student_type == 'intent_student'){
            // var_dump($stu_id);exit;
            $student = $this->intentstudent->where('stu_id',$stu_id)->find();
            // var_dump($student);exit;
            // if($space['temporary_limit'] == 0){
                $this->error('您是临时学员，无法预约');
            // }
        }else{
            $student = $this->student->with(['space'])->where('stu_id',$stu_id)->find();
            //正式学员
            $allow_space = explode(',',$space['allow_space']);
            $allow_space[] = (string)$space_id;
            if(in_array($student['space_id'],$allow_space)){
                //根据学员可学科目预约
                if($space['process_limit'] == 1){
                    if(!in_array($student['subject_type'],$subject_type)){
                        $this->error('无法预约当前科目');
                    }
                }
                if($student['cooperation_id'] == '244' && $order_type == 'car'){
                    $zhenyang['stu_phone'] = $student['phone'];
                    $zhenyang['order_type'] = $order_type;
                    $zhenyang['subject_type'] = $subject_type;
                    $this->getzhenyang($zhenyang);
                }
                // if(($space['id'] == $student['space_id'] )&& ($space['times_limit_status'] == 1)){
                //     //自馆学员,学时限制
                //     if( $student['period_surplus'] <= 0){
                //        $this->error('您当前没有学时，请联系管理员充值学时后再预约');
                //     }
                // }elseif(($space['id'] != $student['space_id']) && ($space['times_limit_cooperation_status'] == 1)){
                //     //合作馆学员,次数限制
                //     $count_order['order_status'] = ['in',['unpaid','paid','accept_unexecut','executing','finished']];
                //     $count_order['space_id'] = $space_id;
                //     $count = $this->order->where($count_order)->count();
                //     if($count >= $space['times_limit_cooperation']){
                //         $this->error('当前场馆对您学习次数有限，请联系您所属场馆了解详情后再预约');
                //     }
                // }
            }else{
                $this->error('当前场馆不对您开放，请联系管理员后再操作');
            }
            
        }
        if(!$student){
            $this->error('查无此学员');
        }
        return $student;
    }

    /**
     * 振阳接口判断学员是否可以预约
     */
    public function getzhenyang($data)
    {
        $url = 'http://a.jx5668.com/GDMZPAJXERP/public/studentAPI.ashx?command=isStuCanBook';
        // $data['stu_phone'] = '15777163429';
        // $data['order_type'] = 'machine';
        // $data['subject_type'] = 'subject2';
        $res = $this->send_zhenyang_post($url,'POST',$data);
        $res = json_decode($res,1);
        if($res['code'] == 0){
            $this->error($res['retMsg']);
        }

    }
    public function send_zhenyang_post($url,$type, $data){

        $postdata = http_build_query($data);
        $this->zhenyanglog("\n".'['.date('y-m-d H:i:s').']:'.json_encode($data));
        // var_dump($postdata);exit;
        // echo "发送参数:".$postdata."\n";
        $opts = array('http' =>
            array(
                'method' => $type,
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $postdata
            )
        );
        
        $context = stream_context_create($opts);
        $result = file_get_contents($url, false, $context);
        return $result;
    }

    public function zhenyanglog($data)
    {
        $date = date('ym',time());

        $filepath = ROOT_PATH.'runtime/zhenyanglog/'.$date;
        $filename = $filepath.'/'.date('ymd').'.log';
        if(!file_exists($filepath))
            mkdir($filepath,0777,true); //获取到标题，在最终的目录下面建立一个文件夹用来存放分类指
        file_put_contents($filename,$data."\n",FILE_APPEND | LOCK_EX);
        
    }
    public function stu_period_surplus($student)
    {

        if($student['space']['times_limit_status'] == 1){
            $update['period_surplus'] = $student['period_surplus'] - 2;
            $this->student->where('stu_id',$student['stu_id'])->update($update);
        }
    }

    public function getcity()
    {
        $params = $this->request->post();
        if($params['posttype']){
            $latitude = $params['latitude'];
            $longitude = $params['longitude'];
            $url = 'https://apis.map.qq.com/ws/geocoder/v1/?location='.$latitude.','.$longitude.'&key='.self::MAP_KEY;
            $res = $this->curl($url);
            $res = json_decode($res,true)['result']['address_component'];
        }else{
            $ip = $_SERVER["REMOTE_ADDR"];
            $url = 'https://apis.map.qq.com/ws/location/v1/ip?ip='.$ip.'&key='.self::MAP_KEY;
            $res = $this->curl($url);
            $res = json_decode($res,true)['result']['ad_info'];
        }
        $this->success('返回成功',$res);
    }
}
