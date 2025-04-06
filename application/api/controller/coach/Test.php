<?php

namespace app\api\controller\coach;

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

    protected $common = null;
    protected $coach = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->common = new \app\api\controller\Common;
        $this->coach = new \app\admin\model\Coach;
    }

    public function index()
    {
        
    }
    
    public function send_getorder()
    {
        $params['ordernumber'] = 'CON20201208104640628716';
        $params['student_type'] = 'student' ;
        
        //公钥加密
        // ksort($params);
        // $token = reset($params);
        // $params['token'] = $this->rsa->pubEncrypt($token);
        $url = 'https://aicarshow.com/api/coach/boot/getorder';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }

    public function send_submitorder()
    {
        $params['ordernumber'] = 'CON20201127115324174775';
        $params['coach_id'] = 'CTN20210311103714746874' ;
        $params['student_type'] = 'intent_student' ;
        
        //公钥加密
        // ksort($params);
        // $token = reset($params);
        // $params['token'] = $this->rsa->pubEncrypt($token);
        $url = 'https://aicarshow.com/api/coach/boot/getorder';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }

    /**
     * 教员查询订单
     */
    public function send_order_quire()
    {
        $params['coach_id'] = 'CTN20210311103714746874';
        $params['starttime'] = '2020-12-01' ;
        $params['endtime'] = '2020-12-26' ;
        $params['page'] = 1 ;
        $params['order_status'] = 'paid' ;
        $params['ordertype'] = 2 ;
        $params['student_type'] = 'student' ;
        
        //公钥加密
        // ksort($params);
        // $token = reset($params);
        // $params['token'] = $this->rsa->pubEncrypt($token);
        $url = 'https://aicarshow.com/api/coach/order/order_quire';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }

    /**
     * 教员查询订单详情
     */
    public function send_order_detail()
    {
        $params['ordernumber'] = 'CTN20210311103714746874';
        $params['student_type'] = 'student';
        
        //公钥加密
        // ksort($params);
        // $token = reset($params);
        // $params['token'] = $this->rsa->pubEncrypt($token);
        $url = 'https://aicarshow.com/api/coach/order/detail';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }


    /**
     * 学员学习历史记录
     */
    public function send_stu_history()
    {
        $params['stu_id'] = 'CSN20201111161409568486';
        $params['space_id'] = 5;
        $params['student_type'] = 'student';
        $params['ordernumber'] = 'CON20201124113049553383';
        //公钥加密
        // ksort($params);
        // $token = reset($params);
        // $params['token'] = $this->rsa->pubEncrypt($token);
        $url = 'https://aicarshow.com/api/coach/order/get_stu_history';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }

    /**
     * 完成订单
     */
    public function send_finish_order()
    {
        $params['student_type'] = 'student';
        $params['ordernumber'] = 'CON20201124113049553383';
        //公钥加密
        // ksort($params);
        // $token = reset($params);
        // $params['token'] = $this->rsa->pubEncrypt($token);
        $url = 'https://aicarshow.com/api/coach/order/finish';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }

    /**
     * 教员预约订单列表
     */
    public function send_coach_reserve_order()
    {
        $params['coach_id'] = 'CTN20210311103714746874';
        $params['request_date'] = '2021-04-30';
        
        //公钥加密
        // ksort($params);
        // $token = reset($params);
        // $params['token'] = $this->rsa->pubEncrypt($token);
        $url = 'https://aicarshow.com/api/coach/reserve/reserve_quire';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }
    
}