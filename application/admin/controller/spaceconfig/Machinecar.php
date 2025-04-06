<?php

namespace app\admin\controller\spaceconfig;

use app\common\controller\Backend;
use Exception;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Machinecar extends Backend
{
    
    /**
     * Machinecar模型对象
     * @var \app\admin\model\Machinecar
     */
    protected $model = null;

    protected $space_lists = [];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Machinecar;
        $this->space_lists = $_SESSION['think']['admin']['space_list'];
        $this->view->assign("spaceList", $this->getSpaceList());
        $this->view->assign("carTypeList", $this->model->getCarTypeList());
        $this->view->assign("collorList", $this->model->getCollorList());
        $this->view->assign("stateList", $this->model->getStateList());
        $this->view->assign("modeList", $this->model->getModeList());
    }

    public function import()
    {
        parent::import();
    }


    /**
     * 查看
     */
    public function index()
    {
        //当前是否为关联查询
        $this->relationSearch = true;
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $where_space['space.id'] = ['in',$this->space_lists];
            $list = $this->model
                    ->with(['admin','space'])
                    ->where($where)
                    ->where($where_space)
                    ->order($sort, $order)
                    ->paginate($limit);
            foreach ($list as $row) {
                $row->visible(['id','fchrMachineDeviceID','mode','collor','LocalserialNum','machine_code','insurance_start_time','insurance_end_time','remark','main_versions','local_versions','car_type','sim','imei','sn','address','student_code','experience_code','pay_code','back_window_code','state','createtime','updatetime']);
                $row->visible(['admin']);
				$row->getRelation('admin')->visible(['nickname']);
				$row->visible(['space']);
				$row->getRelation('space')->visible(['space_name']);
            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                if(in_array($_SESSION['think']['admin']['group_type'],[0,1,2,3])){
                    $params['space_id'] = $params['space'][0];
                    unset($params['space']);
                }else{
                    $params['space_id'] = $params['space'][0];
                    unset($params['space']);
                }
                $params['cooperation_id'] = model('Space')->where('id',$params['space_id'])->find()['cooperation_id'];

                $params['student_code'] = $this->studentcode($params['machine_code']);
                // $params['experience_code'] = $this->experience_code($params['machine_code']);
                // $params['back_window_code'] = $this->backwindow_code($params['machine_code']);

                $params['insurance_start_time'] = strtotime($params['insurance_start_time']);
                $params['insurance_end_time'] = strtotime($params['insurance_end_time']);
                $insert['insurance_start_time'] = $params['insurance_start_time'];
                $insert['insurance_end_time'] = $params['insurance_end_time'];
                
                $params['machine_code'] = trim($params['machine_code']);
                $params['sim'] = trim($params['sim']);
                $params['sn'] = trim($params['sn']);
                $params['fchrMachineDeviceID'] = trim($params['fchrMachineDeviceID']);
                // $params['terminal_equipment'] = trim($params['terminal_equipment']);
                // $params['study_machine'] = trim($params['study_machine']);
                $machine_code = $this->model->where('machine_code',$params['machine_code'])->find();
                if($machine_code){
                    $this->error(__('Machine_code exists'));
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    Db::name('insurance')->insert($insert);
                    $last_id = Db::name('insurance')->getLastInsID();
                    $params['insurance_ids'] = $last_id;
                    

                    $result = $this->model->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        // var_dump($row->toArray());
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                // $params['experience_code'] = $this->experience_code($params['machine_code']);
                // $params['back_window_code'] = $this->backwindow_code($params['machine_code']);

                $params['insurance_start_time'] = strtotime($params['insurance_start_time']);
                $params['insurance_end_time'] = strtotime($params['insurance_end_time']);

                if($params['activate_id'] !== $row['activate_id']){
                    $params['activate_state'] = 0;
                }

                // $params['cooperation_id'] = model('Space')->where('id',$params['space_id'])->find()['cooperation_id'];
                if($params['insurance_start_time'] !== $row['insurance_start_time'] || $params['insurance_end_time'] !== $row['insurance_end_time']){
                    $insert['insurance_start_time'] = $params['insurance_start_time'];
                    $insert['insurance_end_time'] = $params['insurance_end_time'];
                    $insert['remark'] = $params['insurance_remark'];
                    Db::name('insurance')->insert($insert);
                    $last_id = Db::name('insurance')->getLastInsID();
                    if(!$row['insurance_ids']){
                        $params['insurance_ids'] = $last_id;
                    }else{
                        $arr = explode(',',$row['insurance_ids']);
                        array_push($arr,$last_id);
                        $params['insurance_ids'] = implode(',',$arr);

                    }
                }
                $params['machine_code'] = trim($params['machine_code']);
                if(trim($params['machine_code']) !== $row['machine_code']){
                    $params['student_code'] = $this->studentcode($params['machine_code']);
                }
                $params['sim'] = trim($params['sim']);
                $params['sn'] = trim($params['sn']);
                // $params['terminal_equipment'] = trim($params['terminal_equipment']);;
                // $params['study_machine'] = trim($params['study_machine']);
                $where['machine_code'] = $row['machine_code'];
                $where['id'] = ['neq',$row['id']];
                $machine_code = $this->model->where($where)->find();
                if($machine_code){
                    $this->error(__('Machine_code exists'));
                }

                $result = false;

                // $params['terminal_equipment'] = trim($params['terminal_equipment']);
                // $params['study_machine'] = trim($params['study_machine']);

                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }

                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $insurance_ids = explode(',',$row['insurance_ids']);
        $where_insurance['id'] = ['in',$insurance_ids];
        $insuranceList= Db::name('insurance')->where($where_insurance)->select();

        $this->view->assign("row", $row);
        $this->view->assign("insuranceList", $insuranceList);
        return $this->view->fetch();
    }

    public function getSpaceList()
    {   
        $where['id'] = ['in',$this->space_lists];
        $space = Model('Space')->where($where)->select();
        $list = [];
        foreach($space as $k=>$v){
            $list[$k]['space_id'] = $v['id'];
            $list[$k]['space_name'] =$v['space_name'];
        }
        return $list;
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


    public function toMd5($ids = null){
        $data = $this->request->post();
        $machine_code = $data['machine_code'];
        $sn = $data['sn'];
        
        $time = time();
        $md5 = md5($machine_code.$sn.$time);
        
        $this->success($md5);
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
