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
 * @icon fa fa-terminal
 */
class Terminal extends Backend
{
    
    /**
     * Terminal模型对象
     * @var \app\admin\model\Terminal
     */
    protected $model = null;
    protected $space_lists = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Terminal;
        $this->space_lists = $_SESSION['think']['admin']['space_list'];

        $this->view->assign("spaceList", $this->getSpaceList());
        $this->view->assign("subjecttypeList", $this->model->getSubjectTypeList());
        $this->view->assign("stateList", $this->model->getStateList());
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

            $list = $this->model
                    ->with(['admin','space'])
                    ->where($where)
                    ->order($sort, $order)
                    ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id','machine_code','LocalserialNum','subject_type','LocalVersions','MainVersions','UnitySceneName','ChineseSceneName','state','remark','createtime','updatetime']);
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

                $params['machine_code'] = trim($params['machine_code']);
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
               
                $params['machine_code'] = trim($params['machine_code']);

                // $params['terminal_equipment'] = trim($params['terminal_equipment']);;
                // $params['study_machine'] = trim($params['study_machine']);
                $where['machine_code'] = $row['machine_code'];
                $where['id'] = ['neq',$row['id']];
                $machine_code = $this->model->where($where)->find();
                if($machine_code){
                    $this->error(__('Machine_code exists'));
                }

                $result = false;

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
}
