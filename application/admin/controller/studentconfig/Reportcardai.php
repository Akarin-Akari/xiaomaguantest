<?php

namespace app\admin\controller\studentconfig;

use app\common\controller\Backend;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Reportcardai extends Backend
{
    
    /**
     * Reportcardai模型对象
     * @var \app\admin\model\Reportcardai
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Reportcardai;
        $this->view->assign("subjectTypeList", $this->model->getSubjectTypeList());
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
                    ->with(['student','coachsc','space','car','admin','machineai'])
                    ->where($where)
                    ->order($sort, $order)
                    ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id','ordernumber','subject_type','score','kaochang','createtime','endtime','pass_time']);
                $row->visible(['student']);
				$row->getRelation('student')->visible(['name']);
				$row->visible(['coachsc']);
				$row->getRelation('coachsc')->visible(['name']);
				$row->visible(['space']);
				$row->getRelation('space')->visible(['space_name']);
				$row->visible(['admin']);
				$row->getRelation('admin')->visible(['nickname']);
                $row->visible(['car']);
				$row->getRelation('car')->visible(['machine_code']);
                $row->visible(['machineai']);
				$row->getRelation('machineai')->visible(['machine_ai_id']);
            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

}
