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
class Pickupcar extends Backend
{

    /**
     * Pickupcar模型对象
     * @var \app\admin\model\Pickupcar
     */
    protected $model = null;
    protected $space = null;
    protected $command = null;
    protected $space_lists = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Pickupcar;
        $this->space = new \app\admin\model\Space;
        $this->command = new \app\admin\controller\Command;
        $this->space_lists = $_SESSION['think']['admin']['space_list'];
        $this->view->assign("spaceList", $this->getSpaceList());
        $this->view->assign("space", $this->getspace());
        $this->view->assign("carTypeList", $this->model->getCarTypeList());
        $this->view->assign("stateList", $this->model->getStateList());
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
                $row->visible(['id','machine_code','student_code','regionimage','state','remark','createtime','updatetime']);
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
                $params['student_code'] = $this->studentcode($params['machine_code']);

                $params['cooperation_id'] = model('Space')->where('id',$params['space_id'])->find()['cooperation_id'];

                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    $result = $this->model->allowField(true)->save($params);
                    $id = $this->model->getLastInsID();
                    $interval_start_times = $params['interval_start_times'];
                    $interval_end_times = $params['interval_end_times'];
                    $number = $params['number'];
                    $data = [];
                    foreach($interval_start_times as $k=>$v){
                        $data['pickup_id'] = $id;
                        $data['starttimes'] = $v;
                        $data['endtimes'] = $interval_end_times[$k];
                        $data['number'] = $number[$k];
                        Db::name('pickup_config_time')->insert($data);
                        unset($data);
                    }
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

        $this->view->assign("reserve",[]);
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $reserve = Db::name('pickup_config_time')->where('pickup_id',$ids)->select();
        $start_times_id = $this->get_start_times($reserve);
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
                $result = false;

                if(trim($params['machine_code']) !== $row['machine_code']){
                    $params['student_code'] = $this->studentcode($params['machine_code']);
                }
                $new_start_times_id = [];
                foreach($params as $key => $value){
                    $exp_key = explode('-', $key);
                    if($exp_key[0] == 'interval'){
                        $data = [];
                        $id = $exp_key[1];
                        $data['starttimes'] = $value[0];
                        $data['endtimes'] = $value[1];
                        $data['number'] = $value[2];
                        array_push($new_start_times_id,$id);
                        Db::name('coach_config_time_people')->where('id',$id )->update($data);
                        unset($params[$key]);
                        unset($data);
                    }
                }
                $difference = array_diff($start_times_id, $new_start_times_id);
                foreach($difference as $v){
                    $res = Db::name('coach_config_time_people')->where(['id'=>$v])->delete();
                }
                foreach($params as $key => $value){
                    $exp_key = explode('_', $key);
                    if($exp_key[0] == 'new'){
                        $newdata = [];
                        $newdata['starttimes'] = $value[0];
                        $newdata['endtimes'] = $value[1];
                        $newdata['number'] = $value[2];
                        $newdata['coach_id'] = $ids;
                        Db::name('coach_config_time_people')->insert($newdata);
                        unset($params[$key]);
                    }
                }
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
        $this->view->assign("row", $row);
        $this->view->assign("reserve", $reserve);
        return $this->view->fetch();
    }

    public function getspace()
    {
        $space_list = $this->getSpaceList();
        $space_ids = array_column($space_list, 'space_id');
        $where_space['id'] = ['in',$space_ids];
        $where_space['space_type'] = 'ai_car';

        $space = $this->space->where($where_space)->select();
        return $space;
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

    public function get_start_times($reserve)
    {
        $start_times_id = array_column($reserve, 'id');
        return $start_times_id;
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
        // $encoding = mb_detect_encoding($machine_code, "UTF-8,GB2312,GBK");
        // var_dump($gbkStr);exit;
        $path="/pages/pickupcode/pickupcode?machine_code=".$machine_code;
        // $path="/pages/sccode/sccode?machine_code=".$gbkStr;
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
        $filepath = ROOT_PATH."public/uploads/sccode/student/".$date.'/';
        if(!file_exists($filepath)){
            mkdir($filepath,0777,true);
        }
        $imagepage = '/uploads/sccode/student/'.$date.'/'.$time.'-'.$machine_code.'.jpg';
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
