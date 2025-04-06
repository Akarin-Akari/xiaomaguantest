<?php

namespace app\admin\controller\config;

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
class Warnpractice extends Backend
{
    
    /**
     * Warnpractice模型对象
     * @var \app\admin\model\Warnpractice
     */
    protected $model = null;
    protected $command = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Warnpractice;
        $this->command = new \app\admin\controller\Command;
        $this->view->assign("cooperationList", $this->command->getCooperationList());
        $this->view->assign("leijiStatusList", $this->model->getLeijiStatusList());
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
            $where_arr['keer_leiji'] = ['>',0];
            $where_arr['kesan_leiji'] = ['>',0];
            $list = $this->model
                    ->with(['admin'])
                    ->where($where)
                    ->where($where_arr)
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
                if($warn){
                    if($warn['keer_leiji'] == 0 && $warn['kesan_leiji'] ==0){
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
        if($warn['leiji_status'] == 0){
            return true;
        }
        $students = Db::name('student')->where(['cooperation_id'=>$cooperation_id])->select();
        $stu_ids = array_column($students,'stu_id');
        $process = Db::name('study_process')->where(['cooperation_id'=>$cooperation_id])->select();

        $process_stu_ids = array_column($process,'stu_id') ;
        $ids = array_unique(array_intersect($process_stu_ids,$stu_ids)) ;
        $leiji_status = $warn['leiji_status'];
        foreach($ids as $v){
            $leiji = [];
            
            //科二累计时间
            $keer_study_time = Db::name('study_process')->where(['stu_id'=>$v,'place_id'=>'','createtime'=>['lt',$time],'cooperation_id'=>$cooperation_id])->select();
            $keer_study_time_array = array_column($keer_study_time,'study_time');
            // $keer_sum_time = round(array_sum($keer_study_time_array)/3600,2);
            $keer_sum_time = array_sum($keer_study_time_array);

            //科三累计时间
            $kesan_study_time = Db::name('study_process')->where(['stu_id'=>$v,'place_id'=>['<>','NULL'],'createtime'=>['lt',$time],'cooperation_id'=>$cooperation_id])->select();
            $kesan_study_time_array = array_column($kesan_study_time,'study_time');
            // $kesan_sum_time = round(array_sum($kesan_study_time_array)/3600,2);
            $kesan_sum_time = array_sum($kesan_study_time_array);

            $leiji['keer_leiji_status'] = $warn['leiji_status'];
            $leiji['kesan_leiji_status'] = $warn['leiji_status'];

            if($keer_sum_time >= $warn['keer_leiji']*3600){
                $leiji['keer_leiji'] = array_sum($keer_study_time_array);
            }

            if($kesan_sum_time >= $warn['kesan_leiji']*3600){
                $leiji['kesan_leiji'] =array_sum($kesan_study_time_array);
            }

            $stu_info = Db::name('student')->where(['stu_id'=>$v])->find();
            $student_warn = Db::name('student_warn')->where(['stu_id'=>$v])->find();

            $train_statistic = Db::name('train_statistic')->where(['stu_id'=>$v])->find();
            $leiji['cooperation_id'] = $stu_info['cooperation_id'];
            $leiji['space_id'] = $stu_info['space_id'];
            $leiji['createtime'] = time();
            // var_dump($leiji);
            if((!$student_warn && array_key_exists('keer_leiji',$leiji)) || (!$student_warn && array_key_exists('kesan_leiji',$leiji))){
                if($leiji_status){
                    $leiji['stu_id'] = $stu_info['stu_id'];
                    Db::name('student_warn')->insert($leiji);
                }
                
            }elseif(($student_warn && array_key_exists('keer_leiji',$leiji)) || ($student_warn && array_key_exists('kesan_leiji',$leiji))){
                if($leiji_status && !$student_warn){
                    $leiji['stu_id'] = $stu_info['stu_id'];
                    Db::name('student_warn')->insert($leiji);
                }elseif(!$leiji_status &&$student_warn){
                    $leiji['updatetime'] = time();
                    Db::name('student_warn')->where(['stu_id'=>$v])->update($leiji);
                }
            }
            unset($train_statistic);
            unset($leiji);
        }

    }

    public function change($ids)
    {
        $row = $this->model->get($ids);
        if($row['leiji_status'] == 0){
            $update['leiji_status'] = 1;
        }else{
            $update['leiji_status'] = 0;
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
                    if($v['status'] == 0 ){
                        $res = $this->model->where(['cooperation_id'=>$v['cooperation_id']])->delete();
                        Db::name('student_warn')->where(['cooperation_id'=>$v['cooperation_id']])->delete();
                    }else{
                        $update['keer_leiji'] = 0;
                        $update['keer_leiji_status'] = 0;
                        $update['kesan_leiji'] = 0;
                        $update['kesan_leiji_status'] = 0;
                        Db::name('student_warn')->where(['cooperation_id'=>$v['cooperation_id']])->update($update);

                        $update1['keer_leiji'] = 0;
                        $update1['kesan_leiji'] = 0;
                        $update1['leiji_status'] = 0;
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
