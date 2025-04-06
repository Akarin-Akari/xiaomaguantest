<?php

namespace app\api\controller\pay;

use app\admin\model\Student;
use app\common\controller\Api;
use think\cache;
use think\Db;

use function GuzzleHttp\json_decode;

/**
 * 开机流程所需接口
 */
class Upay extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    protected $student = null;
    protected $intentstudent = null;
    protected $order = null;
    protected $machinecar = null;
    protected $temporaryorder = null;
    protected $common = null;
    protected $sqb_installment =  null;
    protected $installment =  null;
    

    const LOGIN_NAME= 'test';
    
    const DOMAIN = 'https://vsi-api.shouqianba.com';//收钱吧服务器域名

    const ACTIVATE = '/terminal/activate';//激活
    const CHECKIN = '/terminal/checkin';//签到
    const PAY = '/upay/v2/pay';//支付
    const PRECREATE = '/upay/v2/precreate';//预下单
    const REFUND = '/upay/v2/refund';//退款
    const CANCEL = '/upay/v2/cancel';//撤单
    const QUERY = '/upay/v2/query';//查询

    // const CODE = '82737225';//激活码内容
    const APPID = '2022032300004703';//app id，从服务商平台获取
    
    const TERMINAL_SN = '100047030021860355'; //收钱吧终端ID，不超过32位的纯数字
    const TERMINAL_KEY = 'f1b81f96e6b0fcc98afcb55fa3422444'; //终端密钥
    const VENDOR_SN = '91801517'; //vendor_sn(开发者序列号)
    const VENDOR_KEY = 'ac104e3f910ec3914baefc7d056f5dc5'; //vendor_key(开发者密钥)


    public function _initialize()
    {
        parent::_initialize();
        $this->student = new \app\admin\model\Student;
        $this->machinecar = new \app\admin\model\Machinecar;
        $this->intentstudent = new \app\admin\model\Intentstudent;
        $this->order = new \app\admin\model\Order;
        $this->temporaryorder = new \app\admin\model\Temporaryorder;
        $this->sqb_installment = new \app\admin\model\Sqbinstallment;
        $this->installment = new \app\admin\model\Installment;
        
        $this->common = new \app\api\controller\Common;
    }

    //激活接口
    function activate($code)
    {
        $vendor_sn = self::VENDOR_SN;
        $vendor_key = self::VENDOR_KEY;
        $api_domain =  self::DOMAIN;
        $url = $api_domain .self::ACTIVATE;

        $params['app_id'] = self::APPID;    //app id，从服务商平台获取
        $params['code'] = $code;              //激活码内容
        $params['device_id'] = $this->create_guid();
        // $params['device_id'] = '0020';                 //设备唯一身份ID
        // var_dump($params);exit;
        $ret = $this->pre_do_execute($params, $url, $vendor_sn, $vendor_key);
        if($ret['result_code'] =='200' ){
            $ret['biz_response']['device_id'] = $params['device_id'];
            Cache::set('activate'.$params['device_id'],$ret['biz_response']);
            return $ret;
        }else{
            return $ret;
        }
    }


    public function notify()
    {
        $params  = file_get_contents("php://input");
        // $info['time'] = time();
        $params = json_decode($params,1);

        Cache::set('payinfo_'.$params['client_sn'],$params,60*60);
        $pre = Cache::pull('pre_'.$params['client_sn']);

        if($pre){
            // Cache::set('pay_info',$params,3600*5);
            // Cache::set('pre_',$pre,3600*5);
            $arr_pre = json_decode(str_replace('&quot;','"',$pre['extended']),1);
            if($pre && $params['order_status'] == 'PAID'){
                $ordernumber = 'CON'.date("YmdHis") . mt_rand(100000, 999999);
                $machine = $this->machinecar->where(['machine_code'=>$arr_pre['machine_code']])->find();

                $sqb['ordernumber'] = $ordernumber;
                $sqb['stu_id'] = $arr_pre['stu_id'];
                $sqb['sn'] = $params['sn'];
                $sqb['client_sn'] = $params['client_sn'];
                $sqb['total_amount'] = $params['total_amount']/100;
                $sqb['subject'] = $params['subject'];
                $sqb['machine_code'] = $arr_pre['machine_code'];
                $sqb['cooperation_id'] = $machine['cooperation_id'];
                $sqb['space_id'] = $machine['space_id'];
                $sqb['createtime'] = substr_replace($params['ctime'],"",-3);
                if($arr_pre['student_type'] == 'student'){
                    Db::name('sqb')->insert($sqb);
                }else{
                    Db::name('sqb_int')->insert($sqb);
                }
                $this->create_order($arr_pre,$ordernumber,$machine);
            }
            
        }
        // $res = Cache::get('payinfo1');
        // $params = json_decode($res['time'],1);
        // var_dump($params);exit;
        // $jsonxml = json_encode(simplexml_load_string($res['time'], 'SimpleXMLElement', LIBXML_NOCDATA));
        // $result = json_decode($jsonxml, true);//转成数组， 
        // var_dump($result);
    }


 

    public function preorder()
    {
        $params = $this->request->post();
        
        Cache::set('pre_'.$params['client_sn'],$params,60*60*2);
    }

    public function sqb_installment()
    {
        $params  = file_get_contents("php://input");
        // $info['time'] = time();
        $params = json_decode($params,1);

        $pre = Cache::pull('pay_installment'.$params['client_sn']);

        if($pre['status'] == 'SUCCESS'){
            $arr_pre = json_decode(str_replace('&quot;','"',$pre['extended']),1);
            $sqb['stu_id'] = $arr_pre['stu_id'];
            $student = $this->student->where(['stu_id'=>$sqb['stu_id']])->find();
            $sqb['stu_id'] = $arr_pre['stu_id'];
            $sqb['sn'] = $params['sn'];
            $sqb['client_sn'] = $params['client_sn'];
            $sqb['total_amount'] = $params['total_amount']/100;
            $sqb['subject_type'] = 1;
            $sqb['cooperation_id'] = $student['cooperation_id'];
            $sqb['space_id'] = $student['space_id'];
            $sqb['createtime'] = substr_replace($params['ctime'],"",-3);
            Db::name('sqb_installment')->insert($sqb);
            $update['payment_number'] = $params['client_sn'];
            $update['pay_status'] = 'yes';
            $where['stu_id'] = $arr_pre['stu_id'];
            $where['subject_type'] = 1;
            $this->installment->where($where)->update($update);
            $update2['payment_process'] = 'paying';
            $this->student->where(['stu_id'=>$arr_pre['stu_id']])->update($update2);
        }
    }

    public function pay_installment()
    {
        $params = $this->request->post();
        
        Cache::set('pay_installment'.$params['client_sn'],$params,60*60*2);
    }
    
    public function test()
    {
        // $info['time'] = time();
        $params = Cache::get('payinfo_2024011210254101');
        $pre = Cache::get('pre_2024011210254101');
        $arr_pre = json_decode(str_replace('&quot;','"',$pre['extended']),1);
        $machine = $this->machinecar->where(['machine_code'=>$arr_pre['machine_code']])->find();
        $ordernumber = 'CON'.date("YmdHis") . mt_rand(100000, 999999);

        $sqb['stu_id'] = $arr_pre['stu_id'];
        $sqb['sn'] = $params['sn'];
        $sqb['client_sn'] = $params['client_sn'];
        $sqb['total_amount'] = $params['total_amount']/100;
        $sqb['subject'] = $params['subject'];
        $sqb['machine_code'] = $arr_pre['machine_code'];
        $sqb['cooperation_id'] = $machine['cooperation_id'];
        $sqb['space_id'] = $machine['space_id'];
        $sqb['createtime'] = substr_replace($params['ctime'],"",-3);
        $sqb['ordernumber'] = $ordernumber;

        if($pre && $params['order_status'] == 'PAID'){
            $this->create_ordertest($arr_pre,$sqb['ordernumber'],$machine);
            // $this->create_order($arr_pre,$sqb['ordernumber']);
        }
    }

    public function create_order($arr_pre,$ordernumber,$machine)
    {
        $order['ordernumber'] = $ordernumber;
        $order['cooperation_id'] = $machine['cooperation_id'];
        $order['space_id'] = $machine['space_id'];
        $order['machine_id'] = $machine['id'];
        $order['stu_id'] = $arr_pre['stu_id'];
        $order['ordertype'] = $arr_pre['ordertype'];
        $order['reserve_starttime'] = $arr_pre['reserve_starttime'];
        $order['reserve_endtime'] = $arr_pre['reserve_endtime'];
        $order['starttime'] = NULL;
        $order['endtime'] = NULL;
        $order['should_endtime'] = $arr_pre['should_endtime'];
        $order['order_status'] = $arr_pre['order_status'];
        $order['coach_id'] = $arr_pre['coach_id'];
        $order['car_type'] = $arr_pre['car_type'];
        $order['subject_type'] = $arr_pre['subject_type']['value'];
        $order['student_boot_type'] = 1;
        $order['payModel'] = 1;
        $order['evaluation'] = 0;

        if($arr_pre['student_type'] == 'student'){
            $student = $this->student->where(['stu_id'=>$arr_pre['stu_id']])->find();
            $this->order->save($order);
            $pay_cooperation = explode(',',$student['pay_cooperation']);
            $update = [];
            if($student['cooperation_id'] == $machine['cooperation_id']){
                $subject_type = explode(',',$student['subject_type']);
                if(!stristr($student['subject_type'],$order['subject_type'])){
                    array_push($subject_type,$order['subject_type']);
                    if($student['subject_type']){
                        $update['subject_type'] = implode(',',$subject_type);
                    }else{
                        $update['subject_type'] = $order['subject_type'];
                    }                }

            }

            if(!in_array($machine['cooperation_id'],$pay_cooperation)){
                array_push($pay_cooperation,$machine['cooperation_id']);
                $pay_cooperation_str = implode(',',$pay_cooperation);
                $update['pay_cooperation'] = $pay_cooperation_str;
                $this->student->where(['stu_id'=>$student['stu_id']])->update($update);
            }else{
                if($update){
                    $this->student->where(['stu_id'=>$student['stu_id']])->update($update);
                }
            }
            
            
            $data['idcard']= $student['idcard'];
        }else{
            $student = $this->intentstudent->where('stu_id',$order['stu_id'])->find();

            $this->order->save($order);
            
            // $student['name'] = '游客';
            $intent_to_student['name'] = '游客';
            $intent_to_student['stu_id'] = $student['stu_id'];
            $intent_to_student['phone'] = $student['phone'];
            $intent_to_student['subject_type'] = $order['subject_type'];
            $intent_to_student['cooperation_id'] = $order['cooperation_id'];
            $intent_to_student['space_id'] = $order['space_id'];
            
            $this->student->save($intent_to_student);
            
            $data['idcard']= '';
        }
        $data['sim'] = $machine['sim'];
        $data['address'] = $machine['address'];
        $data['imei'] = $machine['imei'];
        $data['sn'] = $machine['sn']; 
        $data['terminal_equipment'] = $machine['terminal_equipment'];
        $data['study_machine'] = $machine['study_machine'];
        $data['stu_id'] = $student['stu_id'];
        $data['stu_name'] =  $student['name'];
        $data['phone'] =  $student['phone'];
        $data['student_type'] = 'student';
        $data['ordernumber'] = $order['ordernumber'];
        $data['subject_type'] = __($order['subject_type']);
        Cache::set('submit_id'.$arr_pre['stu_id'],$arr_pre['stu_id'],3);
        // Cache::set('notifytest5',$arr_pre);

        Cache::set('machine_'.$arr_pre['machine_code'],$data,5*60);
    }

   
    public function create_ordertest($arr_pre,$ordernumber,$machine)
    {
        $order['ordernumber'] = $ordernumber;
        $order['cooperation_id'] = $machine['cooperation_id'];
        $order['space_id'] = $machine['space_id'];
        $order['machine_id'] = $machine['id'];
        $order['stu_id'] = $arr_pre['stu_id'];
        $order['ordertype'] = $arr_pre['ordertype'];
        $order['reserve_starttime'] = $arr_pre['reserve_starttime'];
        $order['reserve_endtime'] = $arr_pre['reserve_endtime'];
        $order['starttime'] = NULL;
        $order['endtime'] = NULL;
        $order['should_endtime'] = $arr_pre['should_endtime'];
        $order['order_status'] = $arr_pre['order_status'];
        $order['coach_id'] = $arr_pre['coach_id'];
        $order['car_type'] = $arr_pre['car_type'];
        $order['subject_type'] = $arr_pre['subject_type']['value'];
        $order['student_boot_type'] = 1;
        $order['payModel'] = 1;
        $order['evaluation'] = 0;

        if($arr_pre['student_type'] == 'student'){
            $student = $this->student->where(['stu_id'=>$arr_pre['stu_id']])->find();
            // $this->order->save($order);
            $pay_cooperation = explode(',',$student['pay_cooperation']);
            $update = [];
            if($student['cooperation_id'] == $machine['cooperation_id']){
                $subject_type = explode(',',$student['subject_type']);
                if(!stristr($student['subject_type'],$order['subject_type'])){
                    array_push($subject_type,$order['subject_type']);
                    if($student['subject_type']){
                        $update['subject_type'] = implode(',',$subject_type);
                    }else{
                        $update['subject_type'] = $order['subject_type'];
                    }                }

            }

            if(!in_array($machine['cooperation_id'],$pay_cooperation)){
                array_push($pay_cooperation,$machine['cooperation_id']);
                $pay_cooperation_str = implode(',',$pay_cooperation);
                $update['pay_cooperation'] = $pay_cooperation_str;
                $this->student->where(['stu_id'=>$student['stu_id']])->update($update);
            }else{
                if($update){
                    $this->student->where(['stu_id'=>$student['stu_id']])->update($update);
                }
            }
            
            
            $data['idcard']= $student['idcard'];
        }else{
            $student = $this->intentstudent->where('stu_id',$order['stu_id'])->find();

            $this->order->save($order);
            
            // $student['name'] = '游客';
            $intent_to_student['name'] = '游客';
            $intent_to_student['stu_id'] = $student['stu_id'];
            $intent_to_student['phone'] = $student['phone'];
            $intent_to_student['subject_type'] = $order['subject_type'];
            $intent_to_student['cooperation_id'] = $order['cooperation_id'];
            $intent_to_student['space_id'] = $order['space_id'];
            
            $this->student->save($intent_to_student);
            
            $data['idcard']= '';
        }
        $data['sim'] = $machine['sim'];
        $data['address'] = $machine['address'];
        $data['imei'] = $machine['imei'];
        $data['sn'] = $machine['sn']; 
        $data['terminal_equipment'] = $machine['terminal_equipment'];
        $data['study_machine'] = $machine['study_machine'];
        $data['stu_id'] = $student['stu_id'];
        $data['stu_name'] =  $student['name'];
        $data['phone'] =  $student['phone'];
        $data['student_type'] = 'student';
        $data['ordernumber'] = $order['ordernumber'];
        $data['subject_type'] = __($order['subject_type']);
        Cache::set('submit_id'.$arr_pre['stu_id'],$arr_pre['stu_id'],3);
        // Cache::set('notifytest5',$arr_pre);

        Cache::set('machine_'.$arr_pre['machine_code'],$data,5*60);
    }

    public function openid_where()
    {
        $where['openid'] = 'is not null';
        $student = Db::name('student')->where('openid','not null')->where('cooperation_id','244')->select();
        var_dump($student);
    }


    //签到接口
    function checkin($device_id,$terminal_sn,$terminal_key)
    {
        $terminal_sn = self::TERMINAL_SN;
        $terminal_key = self::TERMINAL_KEY;
        $api_domain =  self::DOMAIN;
        $url = $api_domain . self::CHECKIN;

        $params['terminal_sn'] = $terminal_sn;//终端号
        $params['device_id'] = $device_id;//设备唯一身份ID

        $ret = $this->pre_do_execute($params, $url, $terminal_sn, $terminal_key);
        //100047030021860355,3403aa0358f1006c58f342b7a4cd3ef5
        if($ret['result_code'] == 200){
            Cache::set('terminal_key_'.$params['device_id'],$ret['biz_response']['terminal_key']);
            return $ret;
            // var_dump($ret['biz_response']['terminal_key']);exit;
        }
        // return $ret;
    }



    //预下单接口
    function precreate()
    {
        $api_domain =  self::DOMAIN;
        $url = $api_domain .self::PRECREATE;
        $terminal_sn = self::TERMINAL_SN;
        $terminal_key = self::TERMINAL_KEY;
        $params['terminal_sn'] = $terminal_sn;      //收钱吧终端ID
        //$params['sn']='';         //收钱吧系统内部唯一订单号
        $params['client_sn'] = '123222';  //商户系统订单号,必须在商户系统内唯一；且长度不超过64字节
        $params['total_amount'] = '1';             //交易总金额
        $params['payway']='3'; //2:支付宝 3:微信
        $params['subject'] = '上机缴费';              //本次交易的概述
        // $params['operator'] = 'AI驾驶馆';             //发起本次交易的操作员
        $params['sub_payway']='4';           //内容为数字的字符串，如果要使用WAP支付，则必须传 "3", 使用小程序支付请传"4"
        //$params['payer_uid']='';          //消费者在支付通道的唯一id,微信WAP支付必须传open_id,支付宝WAP支付必传用户授权的userId
        //$params['description']='';           //对商品或本次交易的描述
        //$params['longitude']='';             //经纬度必须同时出现
        //$params['latitude']='';              //经纬度必须同时出现
        //$params['extended']='';              //收钱吧与特定第三方单独约定的参数集合,json格式，最多支持24个字段，每个字段key长度不超过64字节，value长度不超过256字节
        //$params['goods_details']='';         //商品详情
        //$params['reflect']='';               //任何调用者希望原样返回的信息
        //$params['notify_url']='';     //支付回调的地址
        $ret = $this->pre_do_execute($params, $url, $terminal_sn, $terminal_key);
        var_dump($ret);exit;
        // return $ret ;
    }



    //支付接口
    function  pay()
    {
        $terminal_sn = '100047030021962910';
        $terminal_key = '8b230fa4ea2dad7a66ee4ec4a376ce13';
        $api_domain =  self::DOMAIN;
        $url = $api_domain . self::PAY;

        $params['terminal_sn'] = $terminal_sn;              //终端号
        $params['client_sn'] = $this->getClient_Sn(16); //商户系统订单号,必须在商户系统内唯一；且长度不超过64字节
        $params['total_amount'] = '1';                      //交易总金额,以分为单位
        $params['payway'] = '3';                          //支付方式,1:支付宝 3:微信 4:百付宝 5:京东钱包
        // $params['dynamic_id'] = '';       //条码内容（支付包或微信条码号）
        $params['subject'] = '模拟器缴费';                        //交易简介
        $params['operator'] = 'caozuo';                        //门店操作员

        //$params['description']='';                        //对商品或本次交易的描述
        //$params['longitude']='';                          //经度(经纬度必须同时出现)
        //$params['latitude']='';                           //纬度(经纬度必须同时出现)
        //$params['device_id']='';                          //设备指纹
        //$params['extended']='';                           //扩展参数集合  { "goods_tag": "beijing"，"goods_id":"1"}
        //$params['goods_details']='';                      //商品详情 goods_details": [{"goods_id": "wx001","goods_name": "苹果笔记本电脑","quantity": 1,"price": 2,"promotion_type": 0}]
        //$params['reflect']='';                            //反射参数
        //$params['notify_url']='';                         //支付回调地址(如果支付成功通知时间间隔为1s,5s,30s,600s)

        $ret = $this->pre_do_execute($params, $url, $terminal_sn, $terminal_key);
        var_dump($ret);
        // return $ret;
    }



    //退款接口
    function refund($terminal_sn, $terminal_key)
    {
        $api_domain =  self::DOMAIN;
        $url = $api_domain . self::REFUND;

        $params['terminal_sn'] = $terminal_sn;       //收钱吧终端ID
        $params['sn'] = '';        //收钱吧系统内部唯一订单号（N）
        //$params['client_sn']='';   //商户系统订单号,必须在商户系统内唯一；且长度不超过64字节
        //$params['client_tsn']='';  //商户退款流水号一笔订单多次退款，需要传入不同的退款流水号来区分退款，如果退款请求超时，需要发起查询，并根据查询结果的client_tsn判断本次退款请求是否成功
        $params['refund_amount'] = '';              //退款金额
        $params['refund_request_no'] = '';        //商户退款所需序列号,表明是第几次退款(正常情况不可重复，意外状况爆出不变)
        $params['operator'] = '';                 //门店操作员
        //$params['extended'] = '';                    //扩展参数集合
        //$params['goods_details'] = '';               //商品详情

        $ret = $this->pre_do_execute($params, $url, $terminal_sn, $terminal_key);
        return $ret;
    }


    //查询接口
    function query($terminal_sn, $terminal_key)
    {
        $api_domain =  self::DOMAIN;
        $url = $api_domain.self::QUERY;

        $params['terminal_sn'] = $terminal_sn;      //收钱吧终端ID
        $params['sn']='';         //收钱吧系统内部唯一订单号
        $params['client_sn'] = '2022120157565310';    //商户系统订单号,必须在商户系统内唯一；且长度不超过64字节

        $ret = $this->pre_do_execute($params, $url, $terminal_sn, $terminal_key);
        return $ret;
    }


    //wap api pro 接口
    function wap_api_pro($terminal_sn, $terminal_key)
    {
        $params['terminal_sn'] = $terminal_sn;     //收钱吧终端ID
        $params['client_sn'] = '';    //商户系统订单号,必须在商户系统内唯一；且长度不超过64字节
        $params['total_amount'] = '';             //以分为单位,不超过10位纯数字字符串,超过1亿元的收款请使用银行转账
        $params['subject'] = '';              //本次交易的概述
        //$params['payway']='1';
        $params['notify_url'] = '';   //支付回调的地址
        $params['operator'] = '';                    //发起本次交易的操作员
        $params['return_url'] = "";  //处理完请求后，当前页面自动跳转到商户网站里指定页面的http路径

        ksort($params);  //进行升序排序


        $param_str = "";
        foreach ($params as $k => $v) {
            $param_str .= $k .'='.$v.'&';
        }

        $sign = strtoupper(md5($param_str . 'key=' . $terminal_key));

        $paramsStr = $param_str . "sign=" . $sign;
        $res = "https://qr.shouqianba.com/gateway?" . $paramsStr;

        //将这个url生成二维码扫码或在微信链接中打开可以完成测试
        file_put_contents('logs/wap_api_pro_' . date('Y-m-d') . '.txt', $res, FILE_APPEND);
    }



    function pre_do_execute($params, $url, $terminal_sn, $terminal_key)
    {
        $j_params = json_encode($params);
        $sign = $this->getSign($j_params . $terminal_key);
        $result = $this->httpPost($url, $j_params, $sign, $terminal_sn);
        return json_decode($result,1);
    }

    /**
     * 获得订单号
     */
    function getClient_Sn($codeLenth)  
    {
        $str_sn = '';
        for ($i = 0; $i < $codeLenth; $i++)
        {
            if ($i == 0)
                $str_sn .= rand(1, 9); // first field will not start with 0.
            else
                $str_sn .= rand(0, 9);
        }
        return $str_sn;
    }

    public function getid()
    {
        $res = $this->create_guid();
        var_dump('1231213');
    }


    /**
     * 生成唯一id
     */
    public function create_guid() {  
        if (function_exists ( 'com_create_guid' )) {
            return com_create_guid ();
          } else {
            mt_srand ( ( double ) microtime () * 10000 ); //optional for php 4.2.0 and up.随便数播种，4.2.0以后不需要了。
            $charid = strtoupper ( md5 ( uniqid ( rand (), true ) ) ); //根据当前时间（微秒计）生成唯一id.
            // $hyphen = chr ( 45 ); // "-"
            $uuid = '' . //chr(123)// "{"
            substr ( $charid, 0, 8 ) .  substr ( $charid, 8, 4 ) . substr ( $charid, 12, 4 ) .substr ( $charid, 16, 4 ) . substr ( $charid, 20, 12 );
            //.chr(125);// "}"
            return $uuid;
        }
    }

    /**
     * 签名
     */
    function getSign($signStr)   
    {
        $md5 = Md5($signStr);
        return $md5;
    }
    /**
     * 头部请求规则
     */
    function httpPost($url, $body, $sign, $sn)   
    {
        $header = array(
            "Format:json",
            "Content-Type: application/json",
            "Authorization:$sn" . ' ' . $sign
        );
        $result = $this->do_execute($url, $body, $header);
        return $result;
    }

    function do_execute($url, $postfield, $header)
    {
        //    var_dump($url);echo '<br>';
        //    var_dump($postfield);echo '<br>';
        //    var_dump($header);echo '<br>';exit;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);  // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postfield);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        $response = curl_exec($ch);
        //var_dump(curl_error($ch));  //查看报错信息
        // file_put_contents('logs/web_api_' . date('Y-m-d') . '.txt', date("Y-m-d H:i:s", time()) . "===" . "返回：" . $response . "\n" . "请求应用参数：" . $postfield . "\n" . "\n" . "请求url：" . $url . "\n", FILE_APPEND);
        // var_dump($url);
        // echo '<br>';
        // var_dump($response);
        // exit;
        curl_close($ch);
        return $response;
    }
}