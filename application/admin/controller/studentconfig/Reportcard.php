<?php

namespace app\admin\controller\studentconfig;

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
class Reportcard extends Backend
{
    
    /**
     * Reportcard模型对象
     * @var \app\admin\model\Reportcard
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Reportcard;
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

            $space_list = $_SESSION['think']['admin']['space_list'];
            $where_space['space.id'] = ['in',$space_list];

            $list = $this->model
                ->with(['student','admin','coach','machinecar','space'])
                ->where($where)
                ->where($where_space)
                ->order($sort, $order)
                ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id','subject_type','score','kaochang','reporttime','ordernumber','createtime']);
                $row->visible(['student']);
				$row->getRelation('student')->visible(['name','stu_id']);
				$row->visible(['admin']);
				$row->getRelation('admin')->visible(['nickname']);
				$row->visible(['coach']);
				$row->getRelation('coach')->visible(['name']);
				$row->visible(['machinecar']);
				$row->getRelation('machinecar')->visible(['machine_code']);
                $row->visible(['space']);
				$row->getRelation('space')->visible(['space_name']);

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
        $this->error('当前此功能不开放');
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids,['machinecar','coach','admin','student']);
        $points_detail = Db::name('deduct_points')->where(['report_card_id'=>$row['id']])->select();
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        $this->view->assign("row", $row);
        $this->view->assign("points_detail", $points_detail);
        return $this->view->fetch();
    }
}
