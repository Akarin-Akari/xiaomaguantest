<?php

namespace app\api\controller\cooperation;

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
    const HEAD_URL = 'https://aivipdriver.com/';
    public function _initialize()
    {
        parent::_initialize();
        $this->common = new \app\api\controller\Common;
    }

    /**
     * 统计页面统计数据
     */
    public function send_statistic()
    {
        $params['starttime'] = '2020-11-02' ;
        $params['endtime'] = '2021-11-27' ;
        $params['space_id_list'] = [5];
        
        //公钥加密
        // ksort($params);
        // $token = reset($params);
        // $params['token'] = $this->rsa->pubEncrypt($token);
        $url = self::HEAD_URL.'api/cooperation/statistic/getstatistic';
        $res = $this->common->post($url,$params);
        var_dump($res);exit;
    }


    /**
     * 按照时间段统计详情
     */
    public function send_statistic_detail()
    {
        $params['space_id_list'] = 5;
        $params['starttime'] = '2020-11-02' ;
        $params['endtime'] = '2020-11-27' ;
        
        //公钥加密
        // ksort($params);
        // $token = reset($params);
        // $params['token'] = $this->rsa->pubEncrypt($token);
        $url = self::HEAD_URL.'api/cooperation/statistic/statistic_detail';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }
    
    /**
     * 预约订单列表
     */
    public function send_cooperation_reserve_order()
    {
        $params['space_id_list'] = [5,12];
        $params['request_date'] = '2020-12-04';
        
        //公钥加密
        // ksort($params);
        // $token = reset($params);
        // $params['token'] = $this->rsa->pubEncrypt($token);
        $url = self::HEAD_URL.'api/cooperation/reserve/reserve_quire';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }

    /**
     * 查询订单详情
     */
    public function send_order_detail()
    {
        $params['ordernumber'] = 'CON20201124113049553383';
        $params['student_type'] = 'student';
        
        //公钥加密
        // ksort($params);
        // $token = reset($params);
        // $params['token'] = $this->rsa->pubEncrypt($token);
        $url = self::HEAD_URL.'api/coach/order/detail';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }

    public function send_getorder()
    {
        $params['ordernumber'] = 'CON20201126150848578369';
        $params['student_type'] = 'student' ;
        
        //公钥加密
        // ksort($params);
        // $token = reset($params);
        // $params['token'] = $this->rsa->pubEncrypt($token);
        $url = self::HEAD_URL.'api/cooperation/boot/getorder';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }

    public function send_submitorder()
    {
        $params['ordernumber'] = 'CON20201124113049553383';
        $params['coach_id'] = 'CON20201116193242828047' ;
        $params['student_type'] = 'student' ;
        
        //公钥加密
        // ksort($params);
        // $token = reset($params);
        // $params['token'] = $this->rsa->pubEncrypt($token);
        $url = self::HEAD_URL.'api/coach/boot/getorder';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }

    /**
     * 查询订单
     */
    public function send_order_quire()
    {
        $params['space_id_list'] = [5];
        $params['starttime'] = '2020-11-20' ;
        $params['endtime'] = '2020-11-27' ;
        $params['page'] = 1 ;
        $params['order_status'] = 'finished' ;
        $params['student_type'] = 'intent_student' ;
        
        //公钥加密
        // ksort($params);
        // $token = reset($params);
        // $params['token'] = $this->rsa->pubEncrypt($token);
        $url = self::HEAD_URL.'api/cooperation/order/order_quire';
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
        $url = self::HEAD_URL.'api/cooperation/order/finish';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }

    

    public function send_get_recharge()
    {
        $params['student_type'] = 'student';
        $params['ordernumber'] = 'CON20201124113049553383';
        //公钥加密
        // ksort($params);
        // $token = reset($params);
        // $params['token'] = $this->rsa->pubEncrypt($token);
        $url = self::HEAD_URL.'api/cooperation/recharge/get_recharge';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }

    /**
     * 教员列表
     */
    public function send_coach_list()
    {
        $params['student_type'] = 'student';
        $params['ordernumber'] = 'CON20201124113049553383';
        //公钥加密
        // ksort($params);
        // $token = reset($params);
        // $params['token'] = $this->rsa->pubEncrypt($token);
        $url = self::HEAD_URL.'api/cooperation/recharge/getcoachlist';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }

    /**
     * 添加教员
     */
    public function send_add()
    {
        $params['space_id'] = 5;
        $params['info'] = [
            'coach_id' => 'C1',
            'sex' => 'male',
            'name' => '教员测试1',
            'phone' => '12674826253',
            'photoimage' => '',
            'car_type' => 'cartype1',
            'subject_type' => 'subject2',
            'idcard1image' => '',
            'bank_num' => '',
            'opening_bank' => '',
            'security_card' => '',
            'idcard2image' => '',
            'drilicenceimage' => '',
            'fstdrilictime' => '',
            'succtime' => '',
            'failuretime' => '',
            'teach_state' => 'yes',
            'space_id' => 5,
            'params' =>[
                0 => [
                    'id'=> '',
                    'starttimes' => '08:00:00',
                    'endtimes' => '10:00:00',
                    'c1_number' => 1,
                    'c2_number' => 2,
                ],
                1 => [
                    'id'=> '',
                    'starttimes' => '10:00:00',
                    'endtimes' => '12:00:00',
                    'c1_number' => 2,
                    'c2_number' => 0,
                ]
            ]
        ];
        //公钥加密
        // ksort($params);
        // $token = reset($params);
        // $params['token'] = $this->rsa->pubEncrypt($token);
        $url = self::HEAD_URL.'api/cooperation/recharge/add';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }

    /**
     * 编辑教员
     */
    public function send_edit()
    {
        $params['coach_id'] = 'CTN20201127164627531203';
        $params['type'] = 1;
        $params['info'] = [
            'space_id'=>5,
            'sex' => 'male',
            'name' => '教员测试1',
            'phone' => '12674826253',
            'photoimage' => '',
            'car_type' => 'cartype1',
            'subject_type' => 'subject1',
            'idcard1image' => '',	
            'bank_num' => '',
            'opening_bank' => '',
            'security_card' => '',
            'idcard2image' => '',
            'drilicenceimage' => '',
            'fstdrilictime' => '',
            'succtime' => '',
            'failuretime' => '',
            'teach_state' => 'yes',
            'space_id' => 5,
            'params' =>[
                0 => [
                    'id'=>'11',
                    'starttimes' => '08:00:00',
                    'endtimes' => '10:00:00',
                    'c1_number' => 1,
                    'c2_number' => 2,
                ],
            ]
        ];
        //公钥加密
        // ksort($params);
        // $token = reset($params);
        // $params['token'] = $this->rsa->pubEncrypt($token);
        $url = self::HEAD_URL.'api/cooperation/recharge/edit';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }
    
    /**
     * 教员请假列表
     */
    public function send_coach_leave_list()
    {
        $params['space_id'] = 5;
        $params['page'] = 1;
        $params['starttime'] = '2020-11-20';
        $params['endtime'] = '2020-11-28';
        //公钥加密
        // ksort($params);
        // $token = reset($params);
        // $params['token'] = $this->rsa->pubEncrypt($token);
        $url = self::HEAD_URL.'api/cooperation/coachconfig/coach_leave_list';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }

    /**
     * 添加教员请假
     */
    public function send_coach_leave_add()
    {
        $params['space_id'] = 5;
        $params['type'] = 0;
        $params['coach_id'] = 'CTN20201127164627531203';
        $params['starttime'] = '2020-11-29 12:00:00';
        $params['endtime'] = '2020-11-29 14:00:00';
        $params['leave_reason'] = '请假';
        //公钥加密
        // ksort($params);
        // $token = reset($params);
        // $params['token'] = $this->rsa->pubEncrypt($token);
        $url = self::HEAD_URL.'api/cooperation/coachconfig/coach_leave_add';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }

    /**
     * 编辑教员请假
     */
    public function send_coach_leave_edit()
    {
        $params['id'] = 2;
        $params['type'] = 1;
        $params['starttime'] = '2020-10-27 11:00:00';
        $params['endtime'] = '2020-10-27 12:00:00';
        $params['leave_reason'] = '请假';
        //公钥加密
        // ksort($params);
        // $token = reset($params);
        // $params['token'] = $this->rsa->pubEncrypt($token);
        $url = self::HEAD_URL.'api/cooperation/coachconfig/coach_leave_edit';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }


    /**
     * 维修列表
     */
    public function send_repairlist()
    {
        $params['space_id'] = 12;
        $params['page'] = 1;
        $params['status'] = 1;
        $params['starttime'] = '2020-12-10';
        $params['endtime'] = '2020-12-11';
        //公钥加密
        // ksort($params);
        // $token = reset($params);
        // $params['token'] = $this->rsa->pubEncrypt($token);
        $url = self::HEAD_URL.'api/cooperation/repair/repairlist';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }

    /**
     * 添加维修
     */
    public function send_add_repair()
    {
        $params['space_id'] = 5;
        $params['type'] = 1;
        $params['remarks'] = '坏了';
        $params['machine_id'] = 2;
        $params['repair_type'] = 1;
        $params['space_id'] = 5;
        $params['images'] = '';
        //公钥加密
        // ksort($params);
        // $token = reset($params);
        // $params['token'] = $this->rsa->pubEncrypt($token);
        $url = self::HEAD_URL.'api/cooperation/repair/add';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }
    
    /**
     * 编辑维修
     */
    public function send_edit_repair()
    {
        $params['id'] = 3;
        $params['type'] = 1;
        $params['remarks'] = '坏了嘛';
        $params['machine_id'] = 2;
        $params['repair_type'] = 1;
        $params['images'] = '';
        //公钥加密
        // ksort($params);
        // $token = reset($params);
        // $params['token'] = $this->rsa->pubEncrypt($token);
        $url = self::HEAD_URL.'api/cooperation/repair/add';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }

    
    /**
     * 完成维修
     */
    public function send_finish_repair()
    {
        $params['repair_id'] = 1;
        //公钥加密
        // ksort($params);
        // $token = reset($params);
        // $params['token'] = $this->rsa->pubEncrypt($token);
        $url = self::HEAD_URL.'api/cooperation/repair/finish';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }




}