<?php

namespace app\admin\controller\config;

use app\common\controller\Backend;
use think\Db;
use Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Cooperation extends Backend
{
    
    /**
     * Cooperation模型对象
     * @var \app\admin\model\Cooperation
     */
    protected $model = null;
    protected $cooperation_list = null;
    protected $command = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->cooperation_list = $_SESSION['think']['admin']['cooperation_list'];
        $this->command = new \app\admin\controller\Command;
        $this->view->assign("cooperationList", $this->command->getCooperationList());

        $this->model = new \app\admin\model\Cooperation;
        $this->view->assign("aiAgreeScList", $this->model->getAiAgreeScList());
        $this->view->assign("getReserveWarnList", $this->model->getReserveWarnList());
        $this->view->assign("getWarnDateStatusList", $this->model->getWarnDateStatusList());
        $this->view->assign("getWarnContinueList", $this->model->getWarnContinueList());
        
        $this->view->assign("getForbiddenPayStateList", $this->model->getForbiddenPayStateList());
        $this->view->assign("getForbiddenTmpStuList", $this->model->getForbiddenTmpStuList());
        $this->view->assign("getForbiddenTmpStuAiList", $this->model->getForbiddenTmpStuAiList());
        $this->view->assign("getForbiddenNotReserveList", $this->model->getForbiddenNotReserveList());
        $this->view->assign("getForbiddenNotReserveAiList", $this->model->getForbiddenNotReserveAiList());
        $this->view->assign("aiPassCoachList", $this->model->getAiPassCoachList());
        $this->view->assign("keerPassScList", $this->model->getKeerPassScList());
        $this->view->assign("kesanPassScList", $this->model->getKesanPassScList());
        $this->view->assign("getDistributeStateList", $this->model->getDistributeStateList());
        $this->view->assign("getPromopteDayStateList", $this->model->getPromopteDayStateList());
        $this->view->assign("getPayCooperationStateList", $this->model->getPayCooperationStateList());

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

            $where_cooperation['cooperation.cooperation_id'] = ['in',$this->cooperation_list];

            $list = $this->model
                    ->with(['admin'])
                    ->where($where)
                    ->where($where_cooperation)
                    ->order($sort, $order)
                    ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id','ai_agree_sc','distribute_state','promote_day','reserve_warn','warn_start_time','warn_end_time','warn_date','warn_text','warn_continue','order_num','promote_day_state','forbidden_pay_state','ai_pass_coach','keer_pass_sc','forbidden_not_reserve','forbidden_tmp_stu','keer_pass_time','kesan_pass_sc','kesan_pass_time','reserve_day','icon_image','createtime','updatetime']);
                $row->visible(['admin']);
				$row->getRelation('admin')->visible(['nickname']);
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
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $result = false;
                
                $res = $this->model->where(['cooperation_id'=>$params['cooperation_id']])->find();
                if($res){
                    $this->error('当前合作方已添加，请勿重复添加');
                }
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }

                    $result = $this->model->allowField(true)->save($params);
                    
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    if($params['forbidden_tmp_stu'] == 1){
                        $update['order_num'] = $params['order_num'];
                        Db::name('student')->where(['cooperation_id'=> $params['cooperation_id']])->update($update);
                    }
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params['reserve_warn'] = implode(',',$params['reserve_warn']);
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
}
