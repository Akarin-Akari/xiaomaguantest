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
class Course extends Backend
{
    
    /**
     * Course模型对象
     * @var \app\admin\model\Course
     */
    protected $model = null;
    protected $command = null;
    protected $cooperation_list = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->cooperation_list = $_SESSION['think']['admin']['cooperation_list'];
        $this->command = new \app\admin\controller\Command;
        $this->view->assign("cooperationList", $this->command->getCooperationList());
        $this->model = new \app\admin\model\Course;

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
            $where_cooperation['course.cooperation_id'] = ['in',$this->cooperation_list];

            $list = $this->model
                    ->with(['courselog','admin'])
                    ->where($where)
                    ->where($where_cooperation)
                    ->order($sort, $order)
                    ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id','createtime','updatetime']);
                $row->visible(['courselog']);
				$row->getRelation('courselog')->visible(['course','money','status','price']);
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
                $course['status'] = $params['status'];
                $course['course'] = $params['course'];
                $course['price'] = $params['price'];
                $course['money'] = $params['money'];
                $course['createtime'] = time();
                unset($params['course']);
                if($params['status'] == 'yes'){
                    $installment = $params['installment'];
                    $sum = array_sum($installment);
                    if($sum != $params['money']){
                        $this->error('分期金额总和与实际金额不匹配，请重新填写');
                    }
                    unset($params['money']);
                    $course['installment']=implode(',',$params['installment']);
                    unset($params['installment']);
                }
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    DB::name('course_log')->insert($course);
                    $course_log_id = DB::name('course_log')->getLastInsID();
                    $params['course_id'] = $course_log_id;
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
        $course = DB::name('course_log')->where('id',$row['course_id'])->find();
        $row['course'] = $course['course'];
        $row['money'] = $course['money'];
        $row['price'] = $course['price'];
        $row['status'] = $course['status'];
        $row['installment'] = explode(',',$course['installment']);
        $row['length'] = count($row['installment']);
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if($params['status'] == 'yes'){
                    $installment = $params['installment'];
                    $sum = array_sum($installment);

                    if($sum != $params['money']){
                        $this->error('分期金额总和与实际金额不匹配，请重新填写');
                    }
                    if($params['status'] == 'yes'){
                        $course['installment']=implode(',',$params['installment']);
                    }
                }else{
                    $params['installment'] = '';
                    $params['installment'] = explode(',',$params['installment']);
                }
                $course['course'] = $params['course'];
                $course['money'] = $params['money'];
                $course['status'] = $params['status'];
                $course['price'] = $params['price'];
                $course['createtime'] = time();
                
                $info['course'] = $params['course'];
                $info['money'] = $params['money'];
                $info['status'] = $params['status'];
                $info['installment'] = $course['installment'];
                
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    if($row['course'] != $params['course'] || $row['money'] != $params['money'] || $row['status'] != $params['status'] || $row['installment'] != $params['installment']){
                        unset($params['installment']);
                        unset($params['course']);
                        unset($params['money']);
                        unset($course['id']);
                        DB::name('course_log')->insert($course);
                        $course_log_id = DB::name('course_log')->getLastInsID();
                        $params['course_id'] = $course_log_id;
                        $result = $row->allowField(true)->save($params);

                    }
                    
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
