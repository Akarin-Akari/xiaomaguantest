<?php

namespace app\admin\controller\orderconfig;

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
class Temporaryorder extends Backend
{
    
    /**
     * Temporaryorder模型对象
     * @var \app\admin\model\Temporaryorder
     */
    protected $model = null;

    protected $space_list = null;
    protected $coach = null;
    protected $group_type = null;
    
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Temporaryorder;
        $this->coach = new \app\admin\model\Coach;
        $this->space_list = $_SESSION['think']['admin']['space_list'];
        $this->group_type = $_SESSION['think']['admin']['group_type'];

        $this->view->assign("carTypeList", $this->model->getCarTypeList());
        $this->view->assign("subjectTypeList", $this->model->getSubjectTypeList());
        $this->view->assign("paymodelList", $this->model->getPaymodelList());
        $this->view->assign("orderStatusList", $this->model->getOrderStatusList());
        $this->view->assign("coachBootTypeList", $this->model->getCoachBootTypeList());
        $this->view->assign("studentBootTypeList", $this->model->getStudentBootTypeList());
        $this->view->assign("evaluationList", $this->model->getEvaluationList());
        $this->view->assign("ordertypeList", $this->model->getOrdertypeList());
        $this->view->assign("coachList", $this->coachList());
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
            $where_space['temporaryorder.space_id'] = ['in',$this->space_list];
            // var_dump($where_space);exit;
            $list = $this->model
                    ->with(['admin','coach','space','intentstudent','machinecar'])
                    ->where($where)
                    ->where($where_space)
                    ->order($sort, $order)
                    ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id','ordernumber','payModel','car_type','subject_type','level','reserve_starttime','reserve_endtime','starttime','endtime','should_endtime','order_status','coach_boot_type','student_boot_type','evaluation','ordertype','auditTime','remarks','createtime','updatetime']);
                $row->visible(['admin']);
				$row->getRelation('admin')->visible(['nickname']);
				$row->visible(['coach']);
				$row->getRelation('coach')->visible(['name']);
				$row->visible(['space']);
				$row->getRelation('space')->visible(['space_name']);
				$row->visible(['intentstudent']);
				$row->getRelation('intentstudent')->visible(['phone','vx_name','stu_id','name']);
				$row->visible(['machinecar']);
				$row->getRelation('machinecar')->visible(['machine_code']);
            }
            if( $this->group_type == 11){
                foreach($list as $k=>$v){
                    $list[$k]['intentstudent']['phone'] =substr_replace($v['intentstudent']['phone'], '****', 3, 4);
                }
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


    public function coachList()
    {   //17635252335
        $space_list = $this->space_list;
        $where['coach.space_id'] = ['in',$space_list];
        $where['coach.teach_state'] = 'yes';
        $res = $this->coach->with(['space'])->where($where)->order('space_id desc')->select();
        $list = [];
        foreach($res as $k=>$v){
            $list[$k]['coach_id']= $v['coach_id'];
            $list[$k]['name']= $v['name'].'('.$v['space']['space_name'].')';
        }
        return $list;
    }
}
