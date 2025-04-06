<?php

namespace app\api\controller\recommender;

use app\common\controller\Api;
use think\Db;
use think\Cache;

/**
 * 开机流程所需接口
 */
class Test extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $this->common = new \app\api\controller\Common;
    }

    /**
     * 意向学员
     */
    public function post_add_intention_student()
    {
        $params['name'] = '测试2';
        $params['phone'] = 15777163429;
        $params['vx_name'] = 'ceshi';
        $params['sex'] = 'male';
        $params['car_type'] = 'cartype2';
        $params['course_id'] = 1;
        $params['sign_up_source'] = 1;
        $params['follower'] = 1;
        $params['cooperation_id'] = 8;
        $params['space_id'] = 36;
        $params['intention'] = 3;
        $params['remarks'] = '备注随便写';

        //公钥加密
        // ksort($params);
        // $token = reset($params);
        // $params['token'] = $this->rsa->pubEncrypt($token);
        $url = 'https://aicarshow.com/api/recommender/index/add_intention_student';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }

    /**
     * 意向学员选择列表
     */
    public function post_add_intention_student_list()
    {
        $params['follower'] = 2;
        //公钥加密
        // ksort($params);
        // $token = reset($params);
        // $params['token'] = $this->rsa->pubEncrypt($token);
        $url = 'https://aicarshow.com/api/recommender/index/add_intention_student_list';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }


    /**
     * 正式学员
     */
    public function post_add_student()
    {
        $params['name'] = '测试';
        $params['phone'] = 17635252335;
        $params['email'] = '123@qq.com';
        $params['idcard'] = '312312';
        $params['vx_name'] = 'ceshi';
        $params['sex'] = 'male';
        $params['car_type'] = 'cartype1';
        $params['period_surplus']  = 10;
        $params['contract_path'] = '';
        $params['photoimage'] = '/uploads/20201031/36758183c42cdc60ae68a8cb05747266.jpg';
        $params['idcard1image'] = '/uploads/20201110/7d9382fe8875c73a671cc1b1fd533b46.jpg';
        $params['idcard2image'] = '/uploads/20201110/bdfbd19031592b4cd43bcb3620668577.jpg';
        $params['regis_type'] = 1;
        $params['regislx'] = 1;
        $params['registtime'] = time();
        $params['course_id'] = 1;
        $params['recommender_id'] = 1;
        $params['follower'] = 1;
        $params['cooperation_id'] = 8;
        $params['space_id'] = 12;
        $params['address'] = '';
        $params['payment'] = [
            [
                'payment_id'=>'ADb12312a',
                'payment_source'=>1,
                'money'=>500,
            ],[
                'payment_id'=>'asdasdasd',
                'payment_source'=>2,
                'money'=>3500,
            ]
        ];
       
        //公钥加密
        // ksort($params);
        // $token = reset($params);
        // $params['token'] = $this->rsa->pubEncrypt($token);
        $url = 'https://aicarshow.com/api/recommender/index/add_student';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }

    /**
     * 正式学员选择列表
     */
    public function post_add_student_list()
    {
        $params['cooperation_id'] = 8;
        //公钥加密
        // ksort($params);
        // $token = reset($params);
        // $params['token'] = $this->rsa->pubEncrypt($token);
        $url = 'https://aicarshow.com/api/recommender/index/add_student_list';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }

    /**
     * 跟进人
     */
    public function send_get_follower(){
        $params['name'] = '龙井';
        //公钥加密
        ksort($params);
        $token = reset($params);
        // $params['token'] = $this->rsa->pubEncrypt($token);
        $url = 'https://aicarshow.com/api/recommender/index/get_follower';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }

    /**
     * 跟踪学员信息
     */
    public function get_student_list(){
        $params['recommender_id'] = 3;
        $params['starttime'] = '2020-12-01';
        $params['endtime'] = '2020-12-09';
        $params['type'] = 'intent_student';
        $params['name'] = '';
        $params['intention'] = 'intent_student';
        $params['page'] = 1;
        
        //公钥加密
        ksort($params);
        $token = reset($params);
        // $params['token'] = $this->rsa->pubEncrypt($token);
        $url = 'https://aicarshow.com/api/recommender/index/get_student';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }

    /**
     * 我的页面统计数据
     */
    public function send_get_my_total(){
        $params['recommender_id'] = 9;
        //公钥加密
        // ksort($params);
        // $token = reset($params);
        // $params['token'] = $this->rsa->pubEncrypt($token);
        $url = 'https://aicarshow.com/api/recommender/login/get_my_total';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }


    /**
     * 个人排行
     */
    public function send_get_leaderboard(){
        $params['recommender_id'] = 1;
        //公钥加密
        // ksort($params);
        // $token = reset($params);
        // $params['token'] = $this->rsa->pubEncrypt($token);
        $url = 'https://aicarshow.com/api/recommender/total/get_leaderboard';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }


    /**
     * 场馆组排行
     */
    public function send_get_total_space_leaderboard()
    {
        $params['recommender_id'] = 1;
        $params['type'] = 'month';
        //公钥加密
        // ksort($params);
        // $token = reset($params);
        // $params['token'] = $this->rsa->pubEncrypt($token);
        $url = 'https://aicarshow.com/api/recommender/total/get_total_space_leaderboard';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }
    


    public function car_login()
    {
        $params['encryptedData'] = 'Sr2M6CE2eYipCHwJ278yUlVM3jESid4hl0rQEWSTUQCryAo9S2h6pFPUa9rr2jpPTUnGiDLizlpXW0zCGuW6A1IihUBHo3mdj9RdvRkqVsN7WapUrm/qRln+67ch9OX91mcBhxpIM3TOnkLJ6HQgKO0jtamanYFufzTGzDjAp5sNCojTw/PfUdsB4SzCT2z5yV8cRRPhtfzDoFVZgbbBFA==';
        $params['iv'] = '/EANi5VfIDPV7mrI9sprRg==';
        $params['code'] = '063I15ml2svWW54Uehnl2zEFmv4I15md';
        $url = 'https://aicarshow.com/api/recommender/login/car_login';
        $res = $this->common->post($url,$params);
        var_dump($res);
    }


    public function test()
    {
        $res = Cache::get('student_info_test');
        var_dump($res);
    }
}
