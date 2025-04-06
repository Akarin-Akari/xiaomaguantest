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
class Order extends Backend
{
    
    /**
     * Order模型对象
     * @var \app\admin\model\Order
     */
    protected $model = null;

    protected $space_list = null;
    protected $group_type = null;
    protected $coach = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Order;
        $this->space_list = $_SESSION['think']['admin']['space_list'];
        $this->group_type = $_SESSION['think']['admin']['group_type'];

        $this->coach = new \app\admin\model\Coach;
        $this->view->assign("coachList", $this->coachList());
        $this->view->assign("carTypeList", $this->model->getCarTypeList());
        $this->view->assign("subjectTypeList", $this->model->getSubjectTypeList());
        $this->view->assign("paymodelList", $this->model->getPaymodelList());
        $this->view->assign("orderStatusList", $this->model->getOrderStatusList());
        $this->view->assign("coachBootTypeList", $this->model->getCoachBootTypeList());
        $this->view->assign("studentBootTypeList", $this->model->getStudentBootTypeList());
        $this->view->assign("evaluationList", $this->model->getEvaluationList());
        $this->view->assign("ordertypeList", $this->model->getOrdertypeList());
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

            var_dump($this->group_type);exit;
            if($this->group_type == '11'){
                var_dump(123);
            }
            $where_space['order.space_id'] =['in', $this->space_list];
            $list = $this->model
                ->with(['admin','space','coach','student','machinecar'])
                ->where($where)
                ->where($where_space)
                ->order($sort, $order)
                ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id','ordernumber','stu_id','car_type','subject_type','level','reserve_starttime','reserve_endtime','starttime','payModel','order_status','coach_boot_type','student_boot_type','evaluation','ordertype','createtime','updatetime']);
                $row->visible(['admin']);
				$row->getRelation('admin')->visible(['nickname']);
				$row->visible(['space']);
				$row->getRelation('space')->visible(['space_name']);
				$row->visible(['coach']);
				$row->getRelation('coach')->visible(['name']);
				$row->visible(['student']);
				$row->getRelation('student')->visible(['name','phone','id']);
				$row->visible(['machinecar']);
				$row->getRelation('machinecar')->visible(['machine_code']);
                // $row->visible(['pickup']);
				// $row->getRelation('pickup')->visible(['name']);
            }

            if( $this->group_type == 11){
                foreach($list as $k=>$v){
                    $list[$k]['student']['phone'] =substr_replace($v['student']['phone'], '****', 3, 4);
                }
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
                $student = Db::name('student')->where('phone',$params['phone'])->find();
                if(!$student){
                    $this->error('没有学员信息，请添加学员信息后再操作');
                }
                $params['ordernumber'] = 'CON'.date("YmdHis") . mt_rand(100000, 999999);
                $coach = Db::name('coach')->where('coach_id',$params['coach_id'])->find();
                $params['space_id'] = $coach['space_id'];
                $params['cooperation_id'] = $coach['cooperation_id'];
                $params['stu_id'] = $student['stu_id'];
                $params['car_type'] = $student['car_type'];
                $params['payModel'] = '2';
                $params['ordertype'] = 3;

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
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
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $machine_list = $this->machinelist($row['space_id']);
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        // var_dump($row->toArray());exit;
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                $coach = Db::name('coach')->where('coach_id',$params['coach_id'])->find();
                $params['space_id'] = $coach['space_id'];
                $params['cooperation_id'] = $coach['cooperation_id'];
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    $result = $row->allowField(true)->save($params);
                    if($row['pickup_id'] && $params['order_status'] == 'cancel_refunded'){
                        Db::name('pick_up')->where(['id'=>$row['pickup_id']])->update(['status'=>2]);
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
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        $this->view->assign("machinelist", $machine_list);
        return $this->view->fetch();
    }

    public function coachList()
    {   
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

    public function machinelist($space_id)
    {
        $where['space_id'] = $space_id;
        $where['state'] = 1;
        $res = Db::name('machine_car')->where($where)->field(['id','machine_code'])->select();
        return $res;
    }

    
}
