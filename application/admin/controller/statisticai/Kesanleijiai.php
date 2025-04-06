<?php

namespace app\admin\controller\statisticai;

use app\common\controller\Backend;
use think\Db;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Kesanleijiai extends Backend
{
    
    /**
     * Kesanleiji模型对象
     * @var \app\admin\model\Kesanleiji
     */
    protected $model = null;
    protected $cooperation_list = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Kesanleijiai;
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
            $where_cooperation['kesanleijiai.cooperation_id'] = ['in',$this->cooperation_list];
            $where1['kesanleijiai.kesan_leiji'] = ['<>','NULL'];
            $list = $this->model
                    ->with(['admin','space','student','warnexamai'])
                    ->where($where)
                    ->where($where1)
                    ->where($where_cooperation)
                    ->order($sort, $order)
                    ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id','kesan_leiji','kesan_leiji_status','createtime','updatetime']);
                $row->visible(['admin']);
				$row->getRelation('admin')->visible(['nickname']);
				$row->visible(['space']);
				$row->getRelation('space')->visible(['space_name']);
				$row->visible(['student']);
				$row->getRelation('student')->visible(['name']);
                $row->visible(['warnexamai']);
				$row->getRelation('warnexamai')->visible(['kesan_leiji']);
            }
            foreach($list as $k=>$v){
                $list[$k]['kesan_leiji'] = number_format($v['kesan_leiji']/3600,3);
            }
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    public function change($ids)
    {
        $row = $this->model->get($ids);
        if($row['kesan_leiji_status'] == 0){
            $update['kesan_leiji_status'] = 1;
        }else{
            $update['kesan_leiji_status'] = 0;
        }
        $res = $row->allowField(true)->save($update);
        if($res){
            $this->success('操作成功');
        }
        $this->error('操作失败');
        // var_dump($ids);
    }

}
