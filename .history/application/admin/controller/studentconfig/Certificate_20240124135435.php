<?php

namespace app\admin\controller\studentconfig;

use app\common\controller\Backend;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Certificate extends Backend
{

    /**
     * Certificate模型对象
     * @var \app\admin\model\Certificate
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Certificate;
        $this->view->assign("sexList", $this->model->getSexList());
        $this->view->assign("carTypeList", $this->model->getCarTypeList());
        $this->view->assign("studySignList", $this->model->getStudySignList());
        $this->view->assign("subjectTypeList", $this->model->getSubjectTypeList());
        $this->view->assign("contractStateList", $this->model->getContractStateList());
        $this->view->assign("payStatusList", $this->model->getPayStatusList());
        $this->view->assign("paymentProcessList", $this->model->getPaymentProcessList());
        $this->view->assign("statementList", $this->model->getStatementList());
        $this->view->assign("auditedList", $this->model->getAuditedList());
        $this->view->assign("pzStatusList", $this->model->getPzStatusList());
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
                    ->with(['admin','space'])
                    ->where($where)
                    ->order($sort, $order)
                    ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id','name','phone','pz_status']);
                $row->visible(['admin']);
				$row->getRelation('admin')->visible(['nickname']);
				$row->visible(['space']);
				$row->getRelation('space')->visible(['space_name']);
            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }


    public function audit($ids=null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $params['pz_status'] = 1;
        $result = $row->allowField(true)->save($params);
        if ($result !== false) {
            $this->success();
        } else {
            $this->error(__('No rows were updated'));
        }
    }
}
