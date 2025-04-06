<?php

namespace app\admin\controller\config;

use app\common\controller\Backend;

/**
 * 款项分类
 *
 * @icon fa fa-circle-o
 */
class Fundsclass extends Backend
{
    
    /**
     * Fundsclass模型对象
     * @var \app\admin\model\Fundsclass
     */
    protected $model = null;
    protected $command = null;
    protected $cooperation_list = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Fundsclass;
        $this->command = new \app\admin\controller\Command;
        $this->cooperation_list = $_SESSION['think']['admin']['cooperation_list'];
        $this->view->assign("cooperationList", $this->command->getCooperationList());

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
            $where_cooperation['fundsclass.cooperation_id'] = ['in',$this->cooperation_list];

            $list = $this->model
                    ->with(['admin'])
                    ->where($where)
                    ->where($where_cooperation)
                    ->order($sort, $order)
                    ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id','name','state','createtime','updatetime','deletetime']);
                $row->visible(['admin']);
				$row->getRelation('admin')->visible(['nickname']);
            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

}
