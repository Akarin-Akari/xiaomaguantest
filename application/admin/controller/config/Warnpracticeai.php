<?php

namespace app\admin\controller\config;

use app\common\controller\Backend;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Warnpracticeai extends Backend
{

    /**
     * Warnpracticeai模型对象
     * @var \app\admin\model\Warnpracticeai
     */
    protected $model = null;
    protected $command = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Warnpracticeai;
        $this->command = new \app\admin\controller\Command;
        $this->view->assign("cooperationList", $this->command->getCooperationList());
        $this->view->assign("statusList", $this->model->getStatusList());
        $this->view->assign("leijiStatusList", $this->model->getLeijiStatusList());
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
                    ->with(['admin'])
                    ->where($where)
                    ->order($sort, $order)
                    ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id','keer_leiji','kesan_leiji','leiji_status','createtime','updatetime']);
                $row->visible(['admin']);
				$row->getRelation('admin')->visible(['nickname']);
            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

}
