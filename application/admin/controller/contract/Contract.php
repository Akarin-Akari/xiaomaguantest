<?php

namespace app\admin\controller\contract;

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
class Contract extends Backend
{
    
    /**
     * Agreement模型对象
     * @var \app\admin\model\Agreement
     */
    protected $model = null;
    protected $cooperation_list = null;
    protected $course = null;
    protected $group_type = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->cooperation_list = $_SESSION['think']['admin']['cooperation_list'];

        $this->model = new \app\admin\model\Contract;
        $this->course = new \app\admin\model\Course;
        $this->group_type = $_SESSION['think']['admin']['group_type'];
        $this->view->assign("StateList", $this->model->getStateList());
        $this->view->assign("courseList", $this->courseList($this->cooperation_list ));
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


            $where_cooperation['cooperation_id'] = ['in',$this->cooperation_list] ;
            $list = $this->model
                    ->with(['admin'])
                    ->where($where)
                    ->where($where_cooperation)
                    ->order($sort, $order)
                    ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id','file_path','state','name','validitytime','createtime','updatetime']);
                $row->visible(['admin']);
				$row->getRelation('admin')->visible(['nickname']);
            }

            $result = array("total" => $list->total(), "rows" => $list->items());
            $this->view->assign("courseList", $this->courseList($this->cooperation_list));

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
                $cooperation_id = Db::name('course')->where(['course_id'=>$params['course_id'][0]])->find()['cooperation_id'];

                $params['course_id'] = implode(',',$params['course_id']);
                $params['cooperation_id'] = $cooperation_id;
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
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $Tpl = 'https://xiaomaguan.com/uploads/20240117/15515f5f8a598af7b1c3a432b83c4f77.docx';

        $this->assign('Tpl',$Tpl);
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
                $params = $this->preExcludeFields($params);
                $result = false;
                $params['course_id'] = implode(',',$params['course_id']);

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

    public function courseList($cooperation_id)
    {
        $where['course.cooperation_id'] = ['in',$cooperation_id];
        $res = $this->course->with(['course_log','admin'])->where($where)->order('cooperation_id asc')->select();
        $course = [];
        foreach($res as $v){
            if(in_array($this->group_type,[11,21])){
                $v['course_log']['course'] = $v['course_log']['course'].'('.$v['admin']['nickname'].')';
            }else{
                $v['course_log']['course'] = $v['course_log']['course'];
            }
            array_push($course,$v['course_log']->toArray());
        }
        return $course;
    }

    public function variable()
    {
        return $this->view->fetch();
    }
}
