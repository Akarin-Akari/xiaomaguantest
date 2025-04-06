<?php

namespace app\admin\controller\studentconfig;

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
class Studyprocess extends Backend
{
    
    /**
     * Studyprocess模型对象
     * @var \app\admin\model\Studyprocess
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Studyprocess;
        $this->view->assign("statusList", $this->model->getStatusList());
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
                    ->with(['space','student','admin','place'])
                    ->where($where)
                    ->where($where_space)
                    ->order($sort, $order)
                    ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id','process_name','stu_id','status','study_time','starttime','endtime','createtime']);
                $row->visible(['space']);
				$row->getRelation('space')->visible(['space_name']);
				$row->visible(['student']);
				$row->getRelation('student')->visible(['name']);
                $row->visible(['admin']);
				$row->getRelation('admin')->visible(['nickname']);
                $row->visible(['place']);
				$row->getRelation('place')->visible(['place_name']);
            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids,['admin','space','student']);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $keersort = Db::name('keer_sort')->where(['cooperation_id'=>$row['cooperation_id']])->find();
        $sequence = explode(',',$keersort['sequence']);
        $process_name = explode(',',$keersort['process_name']);
        $key = array_search($row['process_name'], $sequence);
        $row['process_name'] = $process_name[$key];
        // var_dump($key);exit;
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
}
