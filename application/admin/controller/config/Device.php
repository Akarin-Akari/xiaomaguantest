<?php

namespace app\admin\controller\config;

use app\admin\model\Devicelog;
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
class Device extends Backend
{
    
    /**
     * Device模型对象
     * @var \app\admin\model\Device
     */
    protected $model = null;
    protected $unpay = null;
    protected $command = null;
    protected $devicelog = null;
    protected $cooperation_list = null;
    protected $space_list = null;
    protected $space = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->unpay = new \app\api\controller\pay\Upay;
        $this->model = new \app\admin\model\Device;
        $this->space = new \app\admin\model\Space;

        $this->space_list = $_SESSION['think']['admin']['space_list'];


        $this->command = new \app\admin\controller\Command;
        $this->view->assign("spaceList", $this->spaceList());
        $this->view->assign("stateList", $this->model->getStateList());
        $this->view->assign("typeList", $this->model->getTypeList());
        $this->devicelog = new \app\admin\model\Devicelog;
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
            $where_space['device.space_id'] = ['in',$this->space_list];
            $list = $this->model
                    ->with(['admin','space'])
                    ->where($where)
                    ->where($where_space)
                    ->order($sort, $order)
                    ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id','amount','type','stu_amount','device_id','terminal_sn','terminal_key','yicixing_state','createtime','updatetime']);
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
                $result = false;
                // $space = $this->model->where('space_id',$params['space_id'])->find();
                // if($space){
                //     $this->error('当前场馆已存在付款设备');
                // }
                $params['cooperation_id'] = model('Space')->where('id',$params['space_id'])->find()['cooperation_id'];
                $activate = $this->unpay->activate($params['code']);
                if($activate['result_code'] =='200' ){
                    $params['terminal_sn'] = $activate['biz_response']['terminal_sn'];
                    $params['terminal_key'] = $activate['biz_response']['terminal_key'];
                    $params['device_id'] = $activate['biz_response']['device_id'];
                    $this->unpay->checkin($params['device_id'] ,$params['terminal_sn'], $params['terminal_key']);
                }else{
                    $this->error($activate['error_message']);
                }
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    // var_dump($params);exit;
                    $result = $this->model->allowField(true)->save($params);
                    $params['status'] = 1;
                    $result = $this->devicelog->allowField(true)->save($params);
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

                // $space = $this->model->where('space_id',$params['space_id'])->find();
                // if($space && $space['space_id'] !==$row['space_id']){
                //     $this->error('当前场馆已存在付款设备');
                // }
                $params['cooperation_id'] = model('Space')->where('id',$params['space_id'])->find()['cooperation_id'];
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
                    // $row
                    $result = $this->devicelog->allowField(true)->save($params);
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

    public function spaceList()
    {
        $where['id'] = ['in',$this->space_list];
        $res = $this->space->where($where)->field(['id','space_name'])->select();
        return $res;
    }
    

}
