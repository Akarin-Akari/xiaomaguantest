<?php

namespace app\admin\controller\statisticai;

use app\common\controller\Backend;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Keerhegeai extends Backend
{
    
    /**
     * Keerhege模型对象
     * @var \app\admin\model\Keerhege
     */
    protected $model = null;
    protected $cooperation_list = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Keerhegeai;
        $this->view->assign("keerHegeStatusList", $this->model->getKeerHegeStatusList());
        $this->view->assign("kesanHegeStatusList", $this->model->getKesanHegeStatusList());
        $this->view->assign("keerLeijiStatusList", $this->model->getKeerLeijiStatusList());
        $this->view->assign("kesanLeijiStatusList", $this->model->getKesanLeijiStatusList());
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
            $this->cooperation_list = $_SESSION['think']['admin']['cooperation_list'];

            $where_cooperation['keerhegeai.cooperation_id'] = ['in',$this->cooperation_list];
            // var_dump($where);exit;
            $where1['keerhegeai.keer_hege'] = ['<>','NULL'];
            $list = $this->model
                    ->with(['admin','space','student','warnexamai'])
                    ->where($where)
                    ->where($where1)
                    ->where($where_cooperation)
                    ->order($sort, $order)
                    ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id','keer_hege','keer_hege_status','createtime','updatetime']);
                $row->visible(['admin']);
				$row->getRelation('admin')->visible(['nickname']);
				$row->visible(['space']);
				$row->getRelation('space')->visible(['space_name']);
                $row->visible(['student']);
				$row->getRelation('student')->visible(['name']);
                $row->visible(['warnexamai']);
				$row->getRelation('warnexamai')->visible(['keer_leiji']);

            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    public function change($ids)
    {
        $row = $this->model->get($ids);
        if($row['keer_hege_status'] == 0){
            $update['keer_hege_status'] = 1;
        }else{
            $update['keer_hege_status'] = 0;
        }
        $res = $row->allowField(true)->save($update);
        if($res){
            $this->success('操作成功');
        }
        $this->error('操作失败');
        // var_dump($ids);
    }

}
