<?php

namespace app\admin\controller\studentconfig;

use app\common\controller\Backend;
use Exception;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Intentstudent extends Backend
{
    
    /**
     * Intentstudent模型对象
     * @var \app\admin\model\Intentstudent
     */
    protected $model = null;
    protected $recommender = null;
    protected $course = null;
    protected $space = null;
    protected $cooperation_list = null;
    protected $space_admin_list = null;
    protected $space_list = null;
    protected $group_type = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Intentstudent;
        $this->recommender = new \app\admin\model\Recommender;
        $this->course = new \app\admin\model\Course;
        $this->space = new \app\admin\model\Space;
        $this->cooperation_list = $_SESSION['think']['admin']['cooperation_list'];
        $this->space_admin_list = $_SESSION['think']['admin']['space_admin_list'];
        $this->space_list = $_SESSION['think']['admin']['space_list'];
        $this->group_type = $_SESSION['think']['admin']['group_type'];
        $this->view->assign("sexList", $this->model->getSexList());
        $this->view->assign("regisstatuslist", $this->model->getRegisStatusList());
        $this->view->assign("carTypeList", $this->model->getCarTypeList());
        $this->view->assign("recommenderList",$this->recommender_list());
        $this->view->assign("spaceList", $this->spaceList());


        
        $this->view->assign("intentionList", $this->model->getIntentionList());
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
            $space_list = $this->space_list;
            // var_dump($space_list);exit;
            $where_space = [];
            if(in_array($_SESSION['think']['admin']['group_type'],[42])){
                $user_phone = $_SESSION['think']['admin']['user_phone'];
                $recommender = model('recommender')->where('phone',$user_phone)->find()['id'];
                $where_space['recommender.leader'] = $recommender;
            }else{
                $where_space['intentstudent.space_id'] = ['in',$space_list];
            }
            // var_dump($where);exit;
            if(in_array($_SESSION['think']['admin']['group_type'],[11,21,23])){
                $list = $this->model
                ->with(['admin','space','signupsource','recommender'])
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);
            }else{
                $list = $this->model
                ->with(['admin','space','signupsource','recommender'])
                ->where($where)
                ->where($where_space)
                ->order($sort, $order)
                ->paginate($limit);
            }
            foreach ($list as $row) {
                $row->visible(['id','regis_status','phone','follower','stu_id','name','sex','car_type','intention','remarks','createtime','updatetime']);
                $row->visible(['admin']);
				$row->getRelation('admin')->visible(['nickname']);
				$row->visible(['space']);
                $row->getRelation('space')->visible(['space_name']);
                $row->visible(['signupsource']);
                $row->getRelation('signupsource')->visible(['sign_up_source_name']);
                $row->visible(['recommender']);
                $row->getRelation('recommender')->visible(['name']);
            }
            foreach($list as $k=>$v){
                $list[$k]['phone'] =substr_replace($v['phone'], '****', 3, 4);
            }
            $result = array("total" => $list->total(), "rows" => $list->items());
            // var_dump($result);exit;
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if(in_array(!$this->group_type,[11,24,31,33,34,41,42,43])){
            $this->error('当前人员暂无权限添加学员信息');
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $params['stu_id'] = 'CSN'.date("YmdHis") . mt_rand(100000, 999999);
                $result = false;
                if(in_array($_SESSION['think']['admin']['group_type'],[0,1,2,3])){
                    $params['space_id'] = $params['space_id'];
                }else{
                    $params['space_id'] = $this->space_list[0];
                }
                $cooperation_id = $this->space->where('id',$params['space_id'])->find()['cooperation_id'];
                $params['cooperation_id'] = $cooperation_id;
                $this->student_validate($params,'');
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
        $this->view->assign("courseList", $this->courseList($this->cooperation_list));

        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        if(in_array(!$this->group_type,[11,24,31,33,34,41,42,43])){
            $this->error('当前人员暂无权限添加学员信息');
        }
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
                if($params['follower']){
                    $recommender= $this->recommender->with('admin')->where('follower.id',$params['follower'])->find();
                    $space_id = $recommender['admin']['id'];
                    $params['space_id'] = $this->space->where('space_admin_id',$space_id)->find()['id'];
                    $params['cooperation_id'] = $recommender['admin']['pid'];
                }

                $this->student_validate($params,$row->id);

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
        $this->view->assign("courseList", $this->courseList($this->cooperation_list[0]));
        return $this->view->fetch();
    }

    public function courseList($cooperation_id)
    {
        $where['course.cooperation_id'] = ['in',$cooperation_id];
        $res = $this->course->with('course_log')->where($where)->select();
        $course = [];
        foreach($res as $v){
            array_push($course,$v['course_log']->toArray());
        }
        return $course;
    }

    public function spaceList()
    {
        $where['id'] = ['in',$this->space_list];
        $space = $this->space->where($where)->field(['id','space_name'])->select();
        return $space;
    }

    public function recommender_space_list()
    {
        $where['id'] = ['in',$this->space_list];
        $list = [];
        $space = $this->space->where($where)->field(['space_admin_id'])->select();
        foreach($space as $v){
            array_push($list,$v['space_admin_id']);
        }
        if(in_array($_SESSION['think']['admin']['group_type'],[2])){
            array_push($list,$_SESSION['think']['admin']['id']);
        }
        if(in_array($_SESSION['think']['admin']['group_type'],[3])){
            array_push($list,$_SESSION['think']['admin']['pid']);
        }
        return $list;
    }

    public function recommender_list()
    {
        $group_type = $this->group_type;
        if(in_array($group_type,[34,41,42,43])){
            $child_admin = $this->space_admin_list;
        }else{
            $space_admin_list = $this->space_admin_list;
            $cooperation_list = $this->cooperation_list;
            $child_admin = array_merge($space_admin_list,$cooperation_list);
        }
        $where['space_id'] = ['in',$child_admin];
        $recommender = $this->recommender->where($where)->field(['id','name'])->select();
        return $recommender;
    }

    public function student_validate($params,$id)
    {
        if(!$id){
            $phone = $this->model->where('phone',$params['phone'])->find();
            if($params['vx_name']){
                $vx_name = $this->model->where('vx_name',$params['vx_name'])->find();
            }
        }else{
            $where_phone['phone'] = $params['phone'];
            $where_phone['id'] = ['neq',$id];
            $phone = $this->model->where($where_phone)->find();
            if($params['vx_name']){
                $where_vx_name['vx_name'] = $params['vx_name'];
                $where_vx_name['id'] = ['neq',$id];
                $vx_name = $this->model->where($where_vx_name)->find();
            }
        }
        if($phone){
            $this->error('手机号已重复');
        }
        if($params['vx_name']){
            if($vx_name){
                $this->error('微信用户已重复');
            }
        }
    }

}
