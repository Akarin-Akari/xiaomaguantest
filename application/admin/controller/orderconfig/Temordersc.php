<?php

namespace app\admin\controller\orderconfig;

use app\common\controller\Backend;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Temordersc extends Backend
{

    /**
     * Temordersc模型对象
     * @var \app\admin\model\Temordersc
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Temordersc;
        $this->view->assign("carTypeList", $this->model->getCarTypeList());
        $this->view->assign("subjectTypeList", $this->model->getSubjectTypeList());
        $this->view->assign("paymodelList", $this->model->getPaymodelList());
        $this->view->assign("orderStatusList", $this->model->getOrderStatusList());
        $this->view->assign("coachBootTypeList", $this->model->getCoachBootTypeList());
        $this->view->assign("studentBootTypeList", $this->model->getStudentBootTypeList());
        $this->view->assign("evaluationList", $this->model->getEvaluationList());
        $this->view->assign("ordertypeList", $this->model->getOrdertypeList());
    }



    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */


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
                    ->with(['admin','space','student','coachsc','car','machineai'])
                    ->where($where)
                    ->order($sort, $order)
                    ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id','stu_id','car_type','subject_type','reserve_starttime','reserve_endtime','starttime','endtime','payModel','order_status','ordertype','createtime','updatetime']);
                $row->visible(['admin']);
				$row->getRelation('admin')->visible(['nickname']);
				$row->visible(['space']);
				$row->getRelation('space')->visible(['space_name']);
				$row->visible(['student']);
				$row->getRelation('student')->visible(['name','phone']);
				$row->visible(['coachsc']);
				$row->getRelation('coachsc')->visible(['name']);
				$row->visible(['car']);
				$row->getRelation('car')->visible(['machine_code']);
				$row->visible(['machineai']);
				$row->getRelation('machineai')->visible(['machine_code']);
            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

}
