<?php

namespace app\admin\controller\orderconfig;

use app\common\controller\Backend;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Pickuporder extends Backend
{

    /**
     * Pickuporder模型对象
     * @var \app\admin\model\Pickuporder
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Pickuporder;
        $this->view->assign("paymodelList", $this->model->getPaymodelList());
        $this->view->assign("orderStatusList", $this->model->getOrderStatusList());
        $this->view->assign("ordertypeList", $this->model->getOrdertypeList());
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
                    ->with(['admin','space','student'])
                    ->where($where)
                    ->order($sort, $order)
                    ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id','ordernumber','stu_id','reserve_starttime','reserve_endtime','pickup_time','payModel','order_status','ordertype','createtime','updatetime']);
                $row->visible(['admin']);
				$row->getRelation('admin')->visible(['nickname']);
				$row->visible(['space']);
				$row->getRelation('space')->visible(['space_name']);
				$row->visible(['student']);
				$row->getRelation('student')->visible(['name','phone']);
                $row->visible(['pickupcar']);
				$row->getRelation('pickupcar')->visible(['machine_code','phone']);

            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

}
