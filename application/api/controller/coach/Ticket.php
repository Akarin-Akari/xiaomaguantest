<?php

namespace app\api\controller\coach;

use app\common\controller\Api;
use Error;
use think\cache;

/**
 * 开机流程所需接口
 */
class Ticket extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    protected $card = null;
    protected $student = null;

    public function _initialize()
    {
        $this->card = new \app\admin\model\Card;
        $this->student = new \app\admin\model\Student;

    }

    public function detail()
    {
        $params = $this->request->post();
        if(empty($params['coupon_id'])){
            $this->error('参数缺失');
        }
        $res = [];

        $where['coupon_id'] = $params['coupon_id'];
        $res =  $this->card->where($where)->find();
        if($res){
            $this->success('返回成功',$res);
        }
        $this->success('无信息',$res);
    }

    public function index() {
        $params = $this->request->post();
        if(empty($params['coach_id']) || empty($params['page']) || !key_exists('type',$params)){
            $this->error('参数缺失');
        }
        $page = $params['page'];
        $pagenum = 10;
        $numl = $pagenum*($page-1);
        $res = [];

        $where['card.coach_id'] = $params['coach_id'];
        if($params['type'] == 2){
            $res = $this->card->with(['coach','student'])->where($where)->where('card.stu_id is null')->limit($numl,$pagenum)->select();
        }else{
            $where['type'] = $params['type'];
            // $where['stu_id'] = $params['type'];
            $res = $this->card->with(['coach','student'])->where($where)->where('card.stu_id is not null')->limit($numl,$pagenum)->select();

        }

        $data = $this->getdata($res);
        if($res){
            $this->success('返回成功',$data);
        }
        $this->success('无信息',$res);

    }

    public function submit_phone() {
        $params = $this->request->post();
        if(empty($params['coach_id']) || empty($params['coupon_id']) || empty($params['phone'])){
            $this->error('参数缺失');
        }

        $res = $this->student->where(['phone'=>$params['phone']])->find();

        if($res){
            $this->success('返回成功',$res);
        }else{
            $this->error('找不到学员信息请联系管理员');
        }
    }

    public function sure_phone(){
        $params = $this->request->post();
        if(empty($params['coach_id']) || empty($params['coupon_id']) || empty($params['stu_id'])){
            $this->error('参数缺失');
        }

        $student = $this->student->where(['stu_id'=>$params['stu_id']])->find();
        if(!$student){
            $this->error('没有此学员');
        }

        $where['coach_id'] = $params['coach_id'];
        $where['coupon_id'] = $params['coupon_id'];
        // $where['stu_id'] = $params['stu_id'];
        $res = $this->card->where($where)->find();
        if($res){
            if($res['type'] == 1 || $res['verify_id']){
                $this->error('当前券已核销');
            }
            if($res['stu_id']){
                $this->error('当前券已发放，请重新刷新页面选择券');
            }

            $update['coupon_code_path'] = $this->code($params['coupon_id']);
            $update['stu_id'] = $student['stu_id'];
            $res_up = $this->card->where(['coupon_id'=>$params['coupon_id']])->update($update);
            if($res_up){
                $this->success('发放成功');
            }
            $this->error('网络异常，请取消后刷新页面重新发放');
        }else{
            $this->error('无法找到当前券，请联系管理员');
        }
    }


    function getdata($res){
        $list = [];
        foreach($res as $k=>$v){
            $list[$k]['coach_name'] = $v['coach']['name'];
            $list[$k]['stu_name'] = $v['student']['name'];
            $list[$k]['coupon_id'] = $v['coupon_id'];
        }
        return $list;
    }
    


    public function code($code)
    {
        //获取token
        $APPID = 'wx150f6a4b1d19145f';
        $AppSecret = 'e060d82328c2b9aa3185b349c2b99151';
        $tokenUrl= "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" .$APPID ."&secret=".$AppSecret;
        $getArr=array();
        $tokenArr=json_decode($this->send_post($tokenUrl,$getArr,"GET"));
        $access_token=$tokenArr->access_token;
        //生成二维码
        $path="/pages/card/card?code=".$code;
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
        $filepath = ROOT_PATH."public/uploads/code/".$date.'/';
        if(!file_exists($filepath)){
            mkdir($filepath,0777,true);
        }
        $imagepage = '/uploads/code/'.$date.'/'.$code.'.jpg';
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
