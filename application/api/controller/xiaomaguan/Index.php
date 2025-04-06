<?php

namespace app\api\controller\xiaomaguan;

use app\common\controller\Api;
use PDO;
use think\Db;
use think\cache;

/**
 * 开机流程所需接口
 */
class Index extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    protected $common =  null;
    protected $space =  null;
    protected $machinecar =  null;

    const DOMAIN = 'http://wjp.gpscx.com';//正式域名

    const GET_TOKEN = '/oauth/token';//获取token

    const LOGIN = '/api/SimulatorOpenApi/ThirdPartyLogin';//登录
    const LOGOUT = '/api/SimulatorOpenApi/LoginOut';//登录
    public function _initialize()
    {
        parent ::_initialize();
        $this->common = new \app\api\controller\Common;
        $this->machinecar = new \app\admin\model\Machinecar;
        $this->space = new \app\admin\model\Space;

    }

    public function get_token(){
        $post_data['client_id'] = 'nanshuntong';
        $post_data['client_secret'] = 'a4vehaU*(y';
        $post_data['grant_type'] = 'client_credentials';
        $header = "Content-type:application/x-www-form-urlencoded";
        $res = $this->post(self::DOMAIN.self::GET_TOKEN,$post_data,'POST',$header);
        $res_tojson = json_decode($res,1);
        Cache::set('js_token',$res_tojson['access_token'],60*60*2);
        return $res_tojson['access_token'];
    }

    public function login($post_data){
        // $post_data['IdCard'] = '441423200207300410';
        // $post_data['subject'] = 2;
        // $post_data['SN'] = 'FJSZxmg202311012fdone';
        $res = $this->curl_post(self::DOMAIN.self::LOGIN,$post_data);
        // $res_tojson = json_decode($res,1);
    }


    public function logout($post_data){
        // $post_data['IdCard'] = '441423200207300410';
        // $post_data['subject'] = 2;
        // $post_data['SN'] = 'FJSZxmg202311012fdone';
        $res = $this->curl_post(self::DOMAIN.self::LOGOUT,$post_data);
        // $res_tojson = json_decode($res,1);
    }



    public function curl_post($url,$post_data){
        $token = Cache::get('js_token');
        if(!$token){
            $token = $this->get_token();
        }
        $curl = curl_init();

        curl_setopt_array($curl, array(
           CURLOPT_URL => $url,
           CURLOPT_RETURNTRANSFER => true,
           CURLOPT_ENCODING => '',
           CURLOPT_MAXREDIRS => 10,
           CURLOPT_TIMEOUT => 0,
           CURLOPT_FOLLOWLOCATION => true,
           CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
           CURLOPT_CUSTOMREQUEST => 'POST',
           CURLOPT_POSTFIELDS =>'{
                "IdCard":"'.$post_data["IdCard"].'",
                "subject":"'.$post_data["subject"].'",
                "SN":"'.$post_data["SN"].'"
            }',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer '.$token,
                'Content-Type: application/json'
            ),
        ));
        $response = curl_exec($curl);
        
        curl_close($curl);
        
    }


    function post($url, $post_data,$method='POST',$header) {
        $postdata = http_build_query($post_data);
        $options = array(
            'http' => array(
                'method' => $method, //or GET
                'header' => $header,
                'content' => $postdata,
                'timeout' => 15 * 60 // 超时时间（单位:s）
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        return $result;
    }
    
    public function machinecar(){
        $params = $this->request->post();
        if(empty($params['machine_code']) || empty($params['activate_id']) || empty($params['insurance_start_time']) || 
            empty($params['insurance_start_time']) || empty($params['operate']) || empty($params['cooperation_id'])|| empty($params['space_id'])){
            $this->error('参数缺失');
        }
        
        $space = $this->space->where(['fj_id'=>$params['space_id']])->find();

        if($space){
            $params['cooperation_id'] = $space['cooperation_id'];
            $params['space_id'] = $space['id'];
        }else{
            $this->error('找不到对应场馆id');
        }

        if($params['operate'] == 'add'){
            $ma = $machinecar = $this->machinecar->where(['machine_code'=>$params['machine_code']])->find();
            if($ma){
                $this->error('已存在');
            }
            $params['student_code'] = $this->studentcode($params['machine_code']);
            unset($params['operate']);
            $res = $this->machinecar->allowField(true)->save($params);
        }elseif($params['operate'] == 'edit'){
            // $params['old_machine_code'];
            $machinecar = $this->machinecar->where(['machine_code'=>$params['old_machine_code']])->find();

            if($machinecar){
                unset($params['operate']);
                if($params['old_machine_code'] !== $params['machine_code']){
                    $params['student_code'] = $this->studentcode($params['machine_code']);
                }else{
                    unset($params['student_code']);
                }
                // var_dump();exit;
                unset($params['old_machine_code']);
                $res =  $machinecar->allowField(true)->save($params);
            }else{
                unset($params['operate']);
                unset($params['old_machine_code']);
                $params['student_code'] = $this->studentcode($params['machine_code']);
                $res = $this->machinecar->allowField(true)->save($params);
            }
        }
        $this->success('返回成功',$res);

    }

    public function studentcode($machine_code)
    {
        //获取token
        $APPID = 'wx71900f694b91a16c';
        $AppSecret = 'a2bbbd633cb79ca40a3bc4e211aa744e';
        $tokenUrl= "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" .$APPID ."&secret=".$AppSecret;
        $getArr=array();
        $tokenArr=json_decode($this->send_post($tokenUrl,$getArr,"GET"));
        $access_token=$tokenArr->access_token;
        //生成二维码
        $path="/pages/qrcode/qrcode?machine_code=".$machine_code;
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
        $imagepage = '/uploads/code/student/'.$date.'/'.$time.'-'.$machine_code.'.jpg';
        file_put_contents(ROOT_PATH.'public'.$imagepage,$result);
        return $imagepage;
    }


    function api_notice_increment($url,$data)
    {
        $curl = curl_init();
        $a = strlen($data);
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

}
