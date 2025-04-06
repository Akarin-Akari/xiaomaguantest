<?php

namespace app\admin\controller\config;

use app\common\controller\Backend;
use Exception;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;

use function app\common\controller\read;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Warnexam extends Backend
{
    
    /**
     * Warnexam模型对象
     * @var \app\admin\model\Warnexam
     */
    protected $model = null;
    protected $multiFields = 'ismenu';
    protected $command = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Warnexam;
        $this->command = new \app\admin\controller\Command;
        $this->view->assign("cooperationList", $this->command->getCooperationList());
        $this->view->assign("statusList", $this->model->getStatusList());

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
            $where_arr['cooperation_id'] = ['in',$_SESSION['think']['admin']['cooperation_list']];
            $where_arr['keer_score'] = ['>',0];
            $where_arr['kesan_score'] = ['>',0];
            $list = $this->model
                    ->with(['admin'])
                    ->where($where)
                    ->where($where_arr)
                    ->order($sort, $order)
                    ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id','keer_hege','keer_score','kesan_hege','kesan_score','status','createtime','updatetime']);
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

                $warn = $this->model->where(['cooperation_id'=>$params['cooperation_id']])->find();
                
                if($params['keer_score'] < 80 || $params['kesan_score'] < 80){
                    $this->error('请正确输入合格分数');
                }

                if($warn){
                    if($warn['keer_score'] == 0 && $warn['kesan_score'] ==0){
                        $this->add_warn($params['cooperation_id'],$params);

                        $result = $warn->where(['cooperation_id'=>$params['cooperation_id']])->update($params);
                        $this->success();
                    }else{
                        $this->error('当前合作方已存在预警信息');
                    }
                }

                $result = false;

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
                    $this->add_warn($params['cooperation_id'],$params);
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }

    public function add_warn($cooperation_id,$warn)
    {
        $time = \fast\Date::unixtime('day',0) -1;
        $students = Db::name('student')->where(['cooperation_id'=>$cooperation_id])->select();
        $stu_ids = array_column($students,'stu_id');
        $report_card = Db::name('study_process')->where(['cooperation_id'=>$cooperation_id])->select();
        if($warn['status'] == 0){
            return true;
        }
        $reportcard_stu_ids = array_column($report_card,'stu_id') ;
        $ids = array_unique(array_intersect($reportcard_stu_ids,$stu_ids)) ;
        foreach($ids as $v){
            $leiji = [];
            //科二合格次数
            $keer_count = Db::name('study_process')->where(['stu_id'=>$v,'process_name'=>'kaoshi','status'=>1,'createtime'=>['lt',$time]])->count();
            $kesan_count = Db::name('study_process')->where(['stu_id'=>$v,'process_name'=>['in',['xian1','xian2','xian3','xian4','xian5','xian6','xian7','xian8','xian9']],'status'=>1,'createtime'=>['lt',$time]])->count();

            if($keer_count >= $warn['keer_hege']){
                $leiji['keer_hege'] = $keer_count;
            }

            if($kesan_count >= $warn['kesan_hege']){
                $leiji['kesan_hege'] = $kesan_count;
            }

            $stu_info = Db::name('student')->where(['stu_id'=>$v])->find();
            $student_warn = Db::name('student_warn')->where(['stu_id'=>$v])->find();
            $train_statistic = Db::name('train_statistic')->where(['stu_id'=>$v])->find();
            $leiji['cooperation_id'] = $stu_info['cooperation_id'];
            $leiji['space_id'] = $stu_info['space_id'];
            $leiji['createtime'] = time();
            // if(!$train_statistic){
            //     Db::name('train_statistic')->insert($leiji);
            // }else{
            //     Db::name('train_statistic')->where(['stu_id'=>$v])->update($leiji);
            // }

            $leiji['stu_id'] = $v;
            $leiji['keer_hege_status'] = $warn['status'];
            $leiji['kesan_hege_status'] = $warn['status'];
            if((!$student_warn && array_key_exists('keer_hege',$leiji) ) || (!$student_warn && array_key_exists('kesan_hege',$leiji))){
                Db::name('student_warn')->insert($leiji);
            }elseif(($student_warn && array_key_exists('keer_hege',$leiji)) || ($student_warn && array_key_exists('kesan_hege',$leiji))){
                $leiji['updatetime'] = time();
                unset($leiji['stu_id']);
                Db::name('student_warn')->where(['stu_id'=>$v])->update($leiji);
            }
            
            unset($train_statistic);
            unset($leiji);

        }
    }

    public function change($ids)
    {
        $row = $this->model->get($ids);
        if($row['status'] == 0){
            $update['status'] = 1;
        }else{
            $update['status'] = 0;
        }
        $res = $row->allowField(true)->save($update);
        if($res){
            $this->success('操作成功');
        }
        $this->error('操作失败');
        // var_dump($ids);
    }

    
    /**
     * 删除
     */
    public function del($ids = "")
    {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = $ids ? $ids : $this->request->post("ids");
        if ($ids) {
            $pk = $this->model->getPk();
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                $this->model->where($this->dataLimitField, 'in', $adminIds);
            }
            $list = $this->model->where($pk, 'in', $ids)->select();
            $count = 0;
            $res = 0;

            Db::startTrans();
            try {
                foreach ($list as $k => $v) {
                    if($v['leiji_status'] == 0 ){
                        $res = $this->model->where(['cooperation_id'=>$v['cooperation_id']])->delete();
                        Db::name('student_warn')->where(['cooperation_id'=>$v['cooperation_id']])->delete();
                    }else{
                        $update['keer_hege'] = 0;
                        $update['keer_hege_status'] = 0;
                        $update['kesan_hege'] = 0;
                        $update['kesan_hege_status'] = 0;
                        Db::name('student_warn')->where(['cooperation_id'=>$v['cooperation_id']])->update($update);
                        $update1['keer_hege'] = 0;
                        $update1['keer_score'] = 0;
                        $update1['kesan_hege'] = 0;
                        $update1['kesan_score'] = 0;
                        $update1['status'] = 0;
                        $res = $this->model->where(['cooperation_id'=>$v['cooperation_id']])->update($update1);
                    }

                    // $count += $v->delete();
                }
                Db::commit();
            } catch (PDOException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            if($res){
                $this->success();
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

}
