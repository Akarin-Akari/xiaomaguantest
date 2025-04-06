<?php

namespace app\admin\controller\statistic;

use app\common\controller\Backend;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Stunumber extends Backend
{
    
    /**
     * Stunumber模型对象
     * @var \app\admin\model\Stunumber
     */
    protected $model = null;
    protected $cooperation_list = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Stunumber;
        $this->cooperation_list = $_SESSION['think']['admin']['cooperation_list'];

        $this->view->assign("auditedList", $this->model->getAuditedList());
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
            $where_cooperation['stunumber.cooperation_id'] = ['in',$this->cooperation_list];

            $list = $this->model
                    ->with(['admin','space','student'])
                    ->where($where)
                    ->order($sort, $order)
                    ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id','stu_id','createtime']);
                $row->visible(['admin']);
				$row->getRelation('admin')->visible(['nickname']);
				$row->visible(['space']);
				$row->getRelation('space')->visible(['space_name']);
                $row->visible(['student']);
				$row->getRelation('student')->visible(['name']);
            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

}
