<?php

namespace app\admin\controller\coachconfig;

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
class Coachsc extends Backend
{
    
    /**
     * Coachsc模型对象
     * @var \app\admin\model\Coachsc
     */
    protected $model = null;
    protected $command = null;
    protected $space = null;
    protected $space_lists = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Coachsc;
        $this->space_lists = $_SESSION['think']['admin']['space_list'];
        $this->command = new \app\admin\controller\Command;
        $this->space = new \app\admin\model\Space;
        $this->view->assign("space", $this->getspace());
        $this->view->assign("spaceList", $this->getSpaceList());
        $this->view->assign("sexList", $this->model->getSexList());
        $this->view->assign("carTypeList", $this->model->getCarTypeList());
        $this->view->assign("subjectTypeList", $this->model->getSubjectTypeList());
        $this->view->assign("teachStateList", $this->model->getTeachStateList());
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
            $space_list = $_SESSION['think']['admin']['space_list'];
            $where_space['space.id'] = ['in',$space_list];
            $list = $this->model
                    ->with(['admin','space'])
                    ->where($where)
                    ->where($where_space)
                    ->order($sort, $order)
                    ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id','coach_id','name','phone','sex','car_type','subject_type','photoimage','idcard1image','idcard2image','drilicenceimage','fstdrilictime','succtime','failuretime','bank_num','opening_bank','security_card','hiretime','leavetime','teach_state','praise','coach_remark','createtime','updatetime']);
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
                $params['coach_id'] = 'CTN'.date("YmdHis") . mt_rand(100000, 999999);
                $params['cooperation_id'] = model('Space')->where('id',$params['space_id'])->find()['cooperation_id'];
                $where_coach['phone'] = $params['phone'];
                $coach = $this->model->where($where_coach)->find();
                if($coach){
                    $this->error('手机号已存在');
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
                    $params['subject_type'] = implode(',', $params['subject_type']);
                    $result = $this->model->allowField(true)->save($params);
                    $id = $this->model->getLastInsID();
                    $interval_start_times = $params['interval_start_times'];
                    $interval_end_times = $params['interval_end_times'];
                    $number = $params['number'];
                    $data = [];
                    foreach($interval_start_times as $k=>$v){
                        $data['coach_id'] = $id;
                        $data['starttimes'] = $v;
                        $data['endtimes'] = $interval_end_times[$k];
                        $data['number'] = $number[$k];
                        Db::name('coach_sc_config_time_people')->insert($data);
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
        $reserve = Db::name('coach_sc_config_time_people')->where('coach_id',$ids)->select();
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
                $where_coach['phone'] = $params['phone'];
                $where_coach['coach_id'] = ['neq',$row['coach_id']];
                $coach = $this->model->where($where_coach)->find();
                if($coach){
                    $this->error('手机号已存在');
                }

                $params['subject_type'] = implode(',', $params['subject_type']);
                $params['space_id'] = $params['space_id'];
                $params['cooperation_id'] = model('Space')->where('id',$params['space_id'])->find()['cooperation_id'];
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
                        Db::name('coach_sc_config_time_people')->where('id',$id )->update($data);
                        unset($params[$key]);
                        unset($data);
                    }
                }
                $difference = array_diff($start_times_id, $new_start_times_id);
                foreach($difference as $v){
                    $res = Db::name('coach_sc_config_time_people')->where(['id'=>$v])->delete();
                }
                foreach($params as $key => $value){
                    $exp_key = explode('_', $key);
                    if($exp_key[0] == 'new'){
                        $newdata = [];
                        $newdata['starttimes'] = $value[0];
                        $newdata['endtimes'] = $value[1];
                        $newdata['number'] = $value[2];
                        $newdata['coach_id'] = $ids;
                        Db::name('coach_sc_config_time_people')->insert($newdata);
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

    
    public function order_length($cooperation_id)
    {
        $where['cooperation_id'] = ['in',$cooperation_id];
        $res = model('Processcooperation')->with(['process','admin'])->where($where)->select();
        return $res;
    }

    public function getspace()
    {
        $space_list = $this->getSpaceList();
        $space_ids = array_column($space_list, 'space_id');
        $where_space['id'] = ['in',$space_ids];
        $where_space['space_type'] = 'car';

        $space = $this->space->where($where_space)->select();
        return $space;
    }

    public function getSpaceList()
    {   
        $space_list = $_SESSION['think']['admin']['space_list'];
        $where['id'] = ['in',$space_list];
        $where['space_type'] = 'car';
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

}
