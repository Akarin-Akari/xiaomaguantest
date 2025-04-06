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
class Space extends Backend
{
    
    /**
     * Space模型对象
     * @var \app\admin\model\Space
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Space;
        $this->view->assign("subjectTypeList", $this->model->getSubjectTypeList());
        $this->view->assign("carTypeList", $this->model->getCarTypeList());
        $this->view->assign("spaceTypeList", $this->model->getSpaceTypeList());
        
        $this->view->assign("timesLimitStatusList", $this->model->getTimesLimitStatusList());
        $this->view->assign("PassStatusList", $this->model->getPassStatusList());
        $this->view->assign("PickUpStatusList", $this->model->getPickUpStatusList());
        $this->view->assign("PayStatusList", $this->model->getPayStatusList());
        
        $this->view->assign("timesLimitCooperationStatusList", $this->model->getTimesLimitCooperationStatusList());
        $this->view->assign("spaceStateList", $this->model->getSpaceStateList());
        // $this->view->assign("modeList", $this->model->getModeList());
        
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
            // var_dump($space_list);exit;
            $where_space['space.id'] = ['in',$space_list];
            // var_dump($space_list);exit;
            $list = $this->model
                    ->with(['admin'])
                    ->where($where)
                    ->where($where_space)
                    ->order($sort, $order)
                    ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id','pick_up_status','pass_status','pay_status','curator_name','temporary_limit','order_length','process_limit','space_type','space_phone','space_name','period_surplus','area','subject_type','car_type','allow_space','times_limit_status','times_limit','times_limit_cooperation_status','times_limit_cooperation','city','address','lng','lat','region_info','regionimage','images','starttimes','endtimes','max_time_slot','day','advance_cancel_times','space_state','praise','createtime','updatetime']);
                $row->visible(['admin']);
				$row->getRelation('admin')->visible(['nickname']);
            }
            $result = array("total" => $list->total(), "rows" => $list->items());
            foreach($result['rows'] as $k=>$v){
                $v['allow_space'] = explode(',',$v['allow_space']);
                $allow_space = [];
                foreach($v['allow_space'] as $vv){
                    $allow_space[] = $this->model->where('id',$vv)->find()['space_name'];
                }
                // var_dump($v->toArray());exit;
                $result['rows'][$k]['allow_space'] = implode(',',$allow_space);
            }

            return json($result);
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
        $row['allow_space'] = explode(',',$row['allow_space']);


        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                // var_dump($params);exit;
                $params = $this->preExcludeFields($params);
                if(array_key_exists('allow_space',$params)){
                    $params['allow_space'] = implode(',', $params['allow_space']);
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
                    $update['nickname'] = $params['space_name'];
                    Db::name('admin')->where(['id'=>$row['space_admin_id']])->update($update);
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
        $this->view->assign("spaceList", $this->getSpaceList($ids));
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    public function getSpaceList($ids)
    {   
        $where['id'] = ['neq',$ids];
        $space = $this->model->where($where)->order('cooperation_id asc')->select();
        $list = [];
        foreach($space as $k=>$v){
            $list[$k]['space_id'] = $v['id'];
            $list[$k]['space_name'] =$v['space_name'];
        }
        return $list;
    }
}
