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
class Kesansort extends Backend
{
    
    /**
     * Kesansort模型对象
     * @var \app\admin\model\Kesansort
     */
    protected $model = null;
    protected $space = null;
    protected $cooperation_list = null;
    protected $space_list = null;
    protected $space_admin_list = null;
    protected $place = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->space = new \app\admin\model\Space;
        $this->cooperation_list = $_SESSION['think']['admin']['cooperation_list'];
        $this->space_list = $_SESSION['think']['admin']['space_list'];
        $this->space_admin_list = $_SESSION['think']['admin']['space_admin_list'];

        $this->model = new \app\admin\model\Kesansort;
        $this->place = new \app\admin\model\Place;

        $this->view->assign("spaceList", $this->spaceList());
        $this->view->assign("placeList", $this->get_place());


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
                    ->with(['admin','space','place'])
                    ->where($where)
                    ->order($sort, $order)
                    ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id','sequence','process_name','kesan_c1_number','kesan_c2_number','createtime','updatetime']);
                $row->visible(['admin']);
				$row->getRelation('admin')->visible(['nickname']);
				$row->visible(['space']);
				$row->getRelation('space')->visible(['space_name']);

                $row->visible(['place']);
				$row->getRelation('place')->visible(['place_name']);
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
        $row['sequence'] = ['bianchaoting','jiajiandang','yejiandengguang','xian1','xian2','xian3','xian4','xian5','xian6','xian7','xian8','kaoshi3'];
        $row['process_name'] = ['变道、超车、停车','加减档训练','模拟夜间灯光训练','一号线','二号线','三号线','四号线','五号线','六号线','七号线','八号线','考试模式'];
        $row['kesan_c1_number'] = [-1,-1,0,0,0,0,-1,-1,-1,-1,-1,0];
        $row['kesan_c2_number'] = [-1,-1,0,0,0,0,-1,-1,-1,-1,-1,0];

        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $where['space_id'] = $params['space_id'];
                $where['place_id'] = $params['place_id'];
                $place_res = $this->model->where($where)->find();
                if($place_res){
                    $this->error('当前场馆已创建');
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
                    $params['cooperation_id'] = model('Space')->where('id',$params['space_id'])->find()['cooperation_id'];

                    foreach($params['process_name'] as $k=>$v){
                        $params['sequence'][$k] = $row['sequence'][array_search($v,$row['process_name'])] ;
                    }
                    $params['sequence'] = implode(',',$params['sequence']);
                    $params['process_name'] = implode(',',$params['process_name']);
                    $params['kesan_c1_number'] = implode(',',$params['kesan_c1_number']);
                    $params['kesan_c2_number'] = implode(',',$params['kesan_c2_number']);
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
        // var_dump($row['process_name']);exit;
        $this->view->assign("row", $row);
        $this->view->assign("process_name", $row['process_name']);
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        $sequence = explode(',',$row['sequence']);
        $process_name = explode(',',$row['process_name']);
        $kesan_c1_number = explode(',',$row['kesan_c1_number']);
        $kesan_c2_number = explode(',',$row['kesan_c2_number']);
        $list = $this->get_list($process_name,$kesan_c1_number,$kesan_c2_number);
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
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }

                    foreach($params['process_name'] as $k=>$v){
                        $params['sequence'][$k] = $sequence[array_search($v,$process_name)] ;
                    }
                    $params['sequence'] = implode(',',$params['sequence']);
                    $params['process_name'] = implode(',',$params['process_name']);
                    $params['kesan_c1_number'] = implode(',',$params['kesan_c1_number']);
                    $params['kesan_c2_number'] = implode(',',$params['kesan_c2_number']);
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
        $this->view->assign("list", $list);
        return $this->view->fetch();
    }

    public function spaceList()
    {
        $where['id'] = ['in',$this->space_list];
        $res = $this->space->where($where)->field(['id','space_name'])->select();
        return $res;
    }

    public function get_list($process_name,$kesan_c1_number,$kesan_c2_number)
    {
        $list = [];
        foreach($process_name as $k=>$v){
            $list[$k]['process_name'] = $v;
            $list[$k]['kesan_c1_number'] = $kesan_c1_number[$k];
            $list[$k]['kesan_c2_number'] = $kesan_c2_number[$k];
        }
        return $list;
    }

    public function get_place()
    {
        $res = $this->place->select();
        return $res;
    }
}
