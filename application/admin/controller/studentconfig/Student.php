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
use app\admin\library\Auth;
use PhpOffice\PhpSpreadsheet\Calculation\Statistical\Averages;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as Xlsxn;
use think\Loader;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Student extends Backend
{
    
    /**
     * Student模型对象
     * @var \app\admin\model\Student
     */
    protected $model = null;
    protected $coachsc = null;
    protected $recommender = null;
    protected $space =null;
    protected $course = null;
    protected $signupsource = null;
    protected $installment = null;
    protected $paymentsource = null;
    protected $intentstudent = null;

    protected $command = null;
    protected $group_type = null;
    protected $cooperation_list = null;
    protected $space_list =  null;
    protected $space_admin_list =  null;
    protected $contract =  null;
    protected $cooperation =  null;
    protected $processcooperation =  null;
    protected $promotestu =  null;
    protected $funds_class =  null;
    protected $common =  null;
    

    protected $noNeedRight = ['createcontract','export','detail1','selectpage','selectcontract'];

    public function _initialize()
    {
        parent::_initialize();
        
        $this->model = new \app\admin\model\Student;
        $this->contract = new \app\admin\model\Contract;
        $this->coachsc = new \app\admin\model\Coachsc;
        $this->recommender = new \app\admin\model\Recommender;
        $this->space = new \app\admin\model\Space;
        $this->course = new \app\admin\model\Course;
        $this->cooperation = new \app\admin\model\Cooperation;
        $this->signupsource = new \app\admin\model\Signupsource;
        $this->installment = new \app\admin\model\Installment;
        $this->paymentsource = new \app\admin\model\Paymentsource;
        $this->intentstudent = new \app\admin\model\Intentstudent;
        $this->funds_class = new \app\admin\model\Fundsclass;
        $this->processcooperation = new \app\admin\model\Processcooperation;
        $this->common = new \app\api\controller\Common;
        $this->group_type = $_SESSION['think']['admin']['group_type'];
        
        $this->cooperation_list = $_SESSION['think']['admin']['cooperation_list'];
        $this->space_list = $_SESSION['think']['admin']['space_list'];
        $this->space_admin_list = $_SESSION['think']['admin']['space_admin_list'];
        $this->command = new \app\admin\controller\Command;
        $this->view->assign('KeerStudySignList',$this->get_keer_process_sort());
        $this->view->assign('KesanStudySignList',$this->get_kesan_process_sort());
        $this->view->assign("cooperationList", $this->command->getCooperationList());

        $this->view->assign("fundsclassList", $this->fundsclass_List($this->cooperation_list));
        
        $this->view->assign("spaceList", $this->spaceList());
        $this->view->assign("sexList", $this->model->getSexList());
        $this->view->assign("carTypeList", $this->model->getCarTypeList());
        $this->view->assign("studySignList", $this->model->getStudySignList());
        $this->view->assign("recommenderList",$this->recommender_list());
        $this->view->assign("contractList",$this->select_contract($this->cooperation_list));
        $this->view->assign("paymentprocessList", $this->model->getPaymentProcessList());
        $this->view->assign("subjectTypeList", $this->model->getSubjectTypeList());
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
            $filter = json_decode($this->request->get('filter',true));
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $space_list = $_SESSION['think']['admin']['space_list'];
            $where_space = [];
            if(in_array($this->group_type ,[42])){
                $user_phone = $_SESSION['think']['admin']['user_phone'];
                $recommender = model('recommender')->where('phone',$user_phone)->find()['id'];
                $where_space['recommender.leader'] = $recommender;
            }else{
                $where_space['student.space_id'] = ['in',$space_list];
            }
            if(in_array($this->group_type ,[11,21,23])){
                $list = $this->model
                    ->with(['admin','space','courselog','recommender','signupsource'])
                    ->where($where)
                    ->order($sort, $order)
                    ->paginate($limit);
            }else{
                $list = $this->model
                    ->with(['admin','space','courselog','recommender','signupsource'])
                    ->where($where)
                    ->where($where_space)
                    ->order($sort, $order)
                    ->paginate($limit);
            }

            foreach ($list as $row) {
                $row->visible(['id','fchrSNCode','order_num','fj_id','name','nation','cooperation_id','pay_cooperation','introducer','contract_path','cooperation_id','idcard','contract_state','vx_name','phone','stu_id','car_type','payment_process','registtime','remarks','study_sign','study_process','subject_type','regis_type','regis_money','recommender','follower','createtime','updatetime']);
                $row->visible(['admin']);
				$row->getRelation('admin')->visible(['nickname']);
				$row->visible(['space']);
                $row->getRelation('space')->visible(['space_name']);
                $row->visible(['signupsource']);
                $row->getRelation('signupsource')->visible(['sign_up_source_name']);
				$row->visible(['courselog']);
                $row->getRelation('courselog')->visible(['course']);
                $row->visible(['recommender']);
                $row->getRelation('recommender')->visible(['name']);
            }

            if( $this->group_type == 11){
                foreach($list as $k=>$v){
                    $list[$k]['phone'] =substr_replace($v['phone'], '****', 3, 4);
                }
                // $list[$k]['yicixing'] = 1;
            }
            $result = array("total" => $list->total(), "rows" => $list->items());
            return json($result);
            
        }
        return $this->view->fetch();
    }


    
    /**
     * 详情
     */
    public function detail($ids)
    {
        $row = $this->model->get(['id' => $ids]);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $this->view->assign("paymentSourceList", $this->paymentSourceList($this->cooperation_list));        
        $this->view->assign("courseList", $this->courseList($this->cooperation_list));
        // $this->view->assign("studyProcessList", $this->getStudyProcessList($this->cooperation_list));
        $this->view->assign("signupsourceList", $this->signupsourceList($this->cooperation_list));
        $this->view->assign("coach_sc_keer", $this->coach_sc_keer($this->cooperation_list));
        $this->view->assign("coach_sc_kesan", $this->coach_sc_kesan($this->cooperation_list));
        $this->view->assign("row", $row->toArray());
        return $this->view->fetch();
    }


    
    /**
     * selectpage
     * @Internal
     * @return \think\response\Json
     */
    public function selectpage()
    {
        $type = $this->request->param('type');
        $params = $this->request->request();


        $page = $this->request->request("pageNumber");
        //分页大小
        $pagesize = $this->request->request("pageSize");

        // return parent::selectpage(); // TODO: Change the autogenerated stub

        if ($type == "all") {
            //显示的字段
            $where[$params['showField']] = ['like','%'.$params[$params['showField']].'%'];
            $where['cooperation_id'] =['in',$this->cooperation_list];
            $list = $this->model->where($where)->page($page, $pagesize)->field('concat(name,"(",phone,")","所剩次数","(",order_num,")") as name,id')->select();
            // var_dump($where);exit;
            $total = $this->model->where($where)->count();
            return json(['list' => $list, 'total' => $total]);
        } else {
            return $this->index();
        }
    }

    public function selectpage2()
    {
        $type = $this->request->param('type');
        $params = $this->request->request();


        $page = $this->request->request("pageNumber");
        //分页大小
        $pagesize = $this->request->request("pageSize");

        // return parent::selectpage(); // TODO: Change the autogenerated stub

        if ($type == "all") {
            //显示的字段
            $where[$params['showField']] = ['like','%'.$params[$params['showField']].'%'];
            $where['cooperation_id'] =['in',$this->cooperation_list];
            $list = $this->model->where($where)->page($page, $pagesize)->field('concat(name,"(",phone,")") as name,id,order_num')->select();
            // var_dump($where);exit;
            $total = $this->model->where($where)->count();
            return json(['list' => $list, 'total' => $total]);
        } else {
            return $this->index();
        }
    }

    /**
     * 添加
     */
    public function add()
    {
        if(in_array(!$this->group_type,[11,24,31,33,34,41,42,43])){
            $this->error('当前人员暂无权限添加学员信息');
        };
        $this->get_keer_process_sort();
        $this->get_kesan_process_sort();
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");

            if ($params) {
                $params = $this->preExcludeFields($params);
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                if($params['contract_path'] ){
                    $params['contract_state'] = 1;
                }
                $params['keer_study_sign'] = implode(',',$params['keer_study_sign']);
                // $params['kesan_study_sign'] = implode(',',$params['kesan_study_sign']);

                $this->student_validate($params,'');


                $space = $this->space->where(['id'=>$params['space_id']])->find();
                if(!$space){
                    $this->error(['赋驾互通数据异常']);
                }
                $intentstudent = $this->intentstudent->where(['phone'=>$params['phone']])->find();


                if($intentstudent){
                    $params['stu_id'] = $intentstudent['stu_id'];
                    $stu = $this->model->where(['stu_id'=>$intentstudent['stu_id']])->find();
                    if($stu){
                        $this->error('当前学员编号已存在');
                    }
                }else{
                    $params['stu_id'] = 'CSN'.date("YmdHis") . mt_rand(100000, 999999);
                }
                if(!preg_match("/^1[3456789]\d{9}$/", $params['phone'])){
                   $this->error('手机号错误');
                }

                if($params['photoimage1'] && !$params['photoimage']){
                    $params['photoimage'] = $params['photoimage1'];
                }
                $params['cooperation_id'] = model('Space')->where('id',$params['space_id'])->find()['cooperation_id'];
                // $params['process'] = implode(',',$params['process']);
                $coo = $this->cooperation->where(['cooperation_id'=>$params['cooperation_id']])->find();
                if($coo){
                    if($coo['forbidden_tmp_stu'] == 1){
                        $params['order_num'] = $coo['order_num'];
                    }
                }

                $course = $this->course->with('courselog')->where(['courselog.id'=>$params['course_id']])->find();
                // var_dump($course->toArray());
                // var_dump($params);
                // exit;
                
                $stu_id = $params['stu_id'];
                $space_id = $params['space_id'];
                $cooperation_id = $params['cooperation_id'];
                $money = $params['money'];

                $shijiao = 0;
                $installment_id = [];


                if($params['course_id'] && $money[0]){
                    foreach($money as $k=>$v){
                        if($v){
                            $insert['stu_id'] = $stu_id;
                            $insert['space_id'] = $space_id;
                            $insert['cooperation_id'] = $cooperation_id;
                            $insert['payment_number'] = $params['payment_number'][$k];
                            $insert['payment_source'] = $params['payment_source'][$k];
                            // $insert['fund_class_id'] = $params['fundsclass'][$k];
                            
                            $insert['money'] = $v;
                            $insert['pay_time'] = time();
                            if($params['payment_number']){
                                $shijiao += $v;
                                $insert['pay_status'] = 'yes';
                            }
                            $this->installment->insert($insert);
                            $id = $this->installment->getLastInsID();
                            array_push($installment_id,$id);
                            unset($insert);
                        }
                        
                    }
    
                    $params['installment_id'] = implode(',',$installment_id);
                    if($shijiao == $course['courselog']['money']){
                        $params['payment_process'] = 'payed';
                    }elseif($shijiao < $course['courselog']['money'] && $shijiao >0){
                        $params['payment_process'] = 'paying';
                    }else{
                        $params['payment_process'] = 'unpaid';
                    }

                    $params['tuition'] = $shijiao;
                    $params['arrears'] = $course['courselog']['money'] - $shijiao;
                }
                
                $fujia = $params;
                $fujia['space_id'] = $space['fj_id'];

                $url = "https://admin.aivipdriver.com/api/xiaomaguan/student/add_student";
                $res = $this->common->post($url,$fujia);

                if($res){
                    $json_res = json_decode($res,1);
                    if($json_res['code'] !== 1){
                        $this->error($json_res['msg']);
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
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("paymentSourceList", $this->paymentSourceList($this->cooperation_list));        
        $this->view->assign("courseList", $this->courseList($this->cooperation_list));
        // $this->view->assign("studyProcessList", $this->getStudyProcessList($this->cooperation_list));
        $this->view->assign("signupsourceList", $this->signupsourceList($this->cooperation_list));
        $this->view->assign("coach_sc_keer", $this->coach_sc_keer($this->cooperation_list));
        $this->view->assign("coach_sc_kesan", $this->coach_sc_kesan($this->cooperation_list));
        
        if(!$this->signupsourceList($this->cooperation_list)){
            $this->error('当前场馆没有配置报名来源，无法添加学员','config/signupsource');
        }
        if(!$this->courseList($this->cooperation_list)){
            $this->error('当前场馆没有配置报名课程，无法添加学员','config/course');
        }
        // if(!$this->getStudyProcessList($this->cooperation_list)){
        //     $this->error('当前场馆没有配置学习进度，无法添加学员','config/processcooperation');
        // }
        if(!$this->paymentSourceList($this->cooperation_list)){
            $this->error('当前场馆没有配置支付方式，无法添加学员','config/paymentsource');
        }

        return $this->view->fetch('add',['addtype'=>'add']);
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
        
        $cooperation_id = $row['cooperation_id'];
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        
        $course = DB::name('course_log')->where('id',$row['course_id'])->find();
        $where_installment['stu_id'] = $row['stu_id'];
        $installment = model('installment')->where($where_installment)->order('times asc')->select();
        $length = count($installment);
        $shijiao_list = array_column($installment->toArray(),'money');
        $installment_ids = array_column($installment->toArray(),'id');
        $shijiao = array_sum($shijiao_list);

        $this->cooperationList();
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if($params['contract_path'] ){
                $params['contract_state'] = 1;
            }
            $params['keer_study_sign'] = implode(',',$params['keer_study_sign']);
            // $params['kesan_study_sign'] = implode(',',$params['kesan_study_sign']);
            $space = model('Space')->where('id',$params['space_id'])->find();
            $params['cooperation_id'] = $space['cooperation_id'];
            $this->student_validate($params,$row->id);
            if ($params) {
                $params = $this->preExcludeFields($params);
                $course = DB::name('course_log')->where('id',$params['course_id'])->find();
                $result = false;
                // $params['process'] = implode(',',$params['process']);
                Db::startTrans();

                if(key_exists('pay_cooperation',$params)){
                    $pay_cooperation = explode(',',$row['pay_cooperation']);
                    foreach($params['pay_cooperation'] as $v){
                        if($v &&!in_array($v,$pay_cooperation)){
                            array_push($pay_cooperation,$v);
                        }
                        
                    }
                    $params['pay_cooperation'] = implode(',',$pay_cooperation);
                }
                $fujia = $params;
                unset($fujia['cooperation_id']);
                $fujia['space_id'] = $space['fj_id'];
                $fujia['stu_id'] = $row['stu_id'];
                
                $url = "https://admin.aivipdriver.com/api/xiaomaguan/student/add_student";
                $res = $this->common->post($url,$fujia);
                if($res){
                    $json_res = json_decode($res,1);
                    if($json_res['code'] !== 1){
                        $this->error($json_res['msg']);
                    }
                }
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }

                    if($course){
                        $money = $params['money'];

                        if($params['money'][0]){
                            foreach($money as $k=>$v){
                                $insert['stu_id'] = $row['stu_id'];
                                $insert['space_id'] = $params['space_id'];
                                $insert['cooperation_id'] = $cooperation_id;
                                $insert['payment_number'] = $params['payment_number'][$k];
                                $insert['payment_source'] = $params['payment_source'][$k];
                                $insert['money'] = $v;
                                $insert['pay_time'] = time();
                                $shijiao += $v;
                                $this->installment->insert($insert);
                                $id = $this->installment->getLastInsID();
                                array_push($installment_ids,$id);
                                unset($insert);
                            }
                            
                        }
                        if($shijiao >= $course['money']){
                            $params['payment_process'] = 'payed';
                        }elseif($shijiao < $course['money'] && $shijiao >0){
                            // $params['payment_process'] = 'paying';
                        }else{
                            $params['payment_process'] = 'unpaid';
                        }
                    }
                    
                    $params['installment_id'] = implode(',',$installment_ids);
                    unset($params['money']);
                    unset($params['payment_source']);
                    unset($params['payment_number']);
                    unset($params['pay_time']);
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
        // var_dump($installment);exit;
        $this->view->assign("row", $row);
        $this->view->assign("length", $length);
        $this->view->assign("installment", $installment);
        $this->view->assign("paymentSourceList", $this->paymentSourceList($this->cooperation_list));
        $this->view->assign("signupsourceList", $this->signupsourceList($cooperation_id));
        $this->view->assign("cooperationList", $this->cooperationList($row));
        $row['pay_cooperation'] = explode(',',$row['pay_cooperation']);

        
        $this->view->assign("courseList", $this->courseList($cooperation_id));
        // $this->view->assign("studyProcessList", $this->getStudyProcessList($cooperation_id));
        $this->view->assign("coach_sc_keer", $this->coach_sc_keer($this->cooperation_list));
        $this->view->assign("coach_sc_kesan", $this->coach_sc_kesan($this->cooperation_list));
        return $this->view->fetch();
    }



    /**
     * 选择合同
     */
    public function select_contract($cooperation_list)
    {
        $where['cooperation_id'] = ['in',$cooperation_list];
        $contract = $this->contract->where($where)->select();
        return $contract;
    }

    /**
     * 创建合同
     */
    public function createcontract($ids=null)
    {
        $row = $this->model->get($ids);
        if ($this->request->post()) {
                $params = $this->request->post("row/a");
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


        if(!$this->select_contract($row['cooperation_id'])->toArray()){
            $this->error('当前合作方没有配置合同信息');
        }
        if(!$row['course_id']){
            $this->error('当前学员没有报名课程');
        }
        $this->view->assign("row", $row);
        $this->view->assign("contractList",$this->select_contract($row['cooperation_id']));
        return $this->view->fetch('selectcontract');
       
    }

    public function selectcontract()
    {
        if ($this->request->post()){
            $params = $this->request->post();
            $row = $this->model->where(['stu_id'=>$params['stu_id']])->find();
            if(!$row['course_log']){
                $this->error('当前合同没有绑定报名课程');
            }
            if($params['contract_id']&& !$row['contract_path']){
                $contract = $this->contract->where(['id'=>$params['contract_id']])->find();
                $path = $contract['file_path'];

                $new_name = md5($row['name'].time()).'.docx';
                $file_title = 'uploads/'.date('Ymd').'/';
                $filepath = ROOT_PATH.'public/'.$file_title ;
                if(!file_exists($filepath))
                    $mkdir_file_dir = mkdir($filepath,0777,true);
                $new_path = $file_title.$new_name;
                
                copy(ROOT_PATH.'public'.$path,ROOT_PATH.'public/'.$new_path);
                $tmp = new \PhpOffice\PhpWord\TemplateProcessor(ROOT_PATH.'public'.$path);
                $arr = $tmp->getVariables();
                if(in_array('学员姓名',$arr)){
                    $tmp->setValue('学员姓名',$row['name']);
                }

                if(in_array('联系电话',$arr)){
                    $tmp->setValue('联系电话',$row['phone']);
                }
                if(in_array('邮箱',$arr)){
                    $tmp->setValue('邮箱',$row['email']);
                }
                if(in_array('身份证号',$arr)){
                    $tmp->setValue('身份证号',$row['idcard']);
                }
                if(in_array('性别',$arr)){
                    $tmp->setValue('性别',$row['sex_text']);
                }
                if(in_array('培训车型',$arr)){
                    $tmp->setValue('培训车型',$row['car_type_text']);
                }
                if(in_array('应收金额',$arr)){
                    $tmp->setValue('应收金额',$row['course_log']['money']);
                    $tmp->setValue('应收金额大写', $this->convert_number_to_rmb($row['course_log']['money']));
                    $tmp->setValue('优惠金额',$row['preferential']);
                    $tmp->setValue('实收金额',$row['tuition']);
                    $tmp->setValue('欠费金额',$row['course_log']['money'] - $row['preferential'] - $row['tuition']);
                }
                
                if(in_array('学员地址',$arr)){
                    $tmp->setValue('学员地址',$row['address']);
                }

                


                $tmp->saveAs(ROOT_PATH.'public/'.$new_path);
                // $new_path = 'uploads/20230324/5598767e12c27c225a036d898cb2cd40.docx';
                $this->success('返回成功',$new_path);
            }
        }
        $this->error('当前无操作');
    }


    public function convert_number_to_rmb($number) {
        $units = array('元', '十', '百', '千', '万', '十万');
        $digit = array('零', '壹', '贰', '叁', '肆', '伍', '陆', '柒', '捌', '玖');
        // 将整数部分拆分成数组
        $int_array = str_split($number);

        // 初始化结果
        $result = '';

        // 添加元和整数部分的大写
        for ($i = 0; $i < count($int_array); $i++){
            $unit_key = (count($int_array)) - $i -1;
            $digit_key = $int_array[$i];
            $result .= $digit[$digit_key] . $units[$unit_key];
        }
        $result .= '整';
        return $result;
    }


    public function coach_sc_keer($cooperation_id)
    {
        $where['cooperation_id'] = ['in',$cooperation_id];
        $where['subject_type'] = ['like','%subject2%'];
        $coachsc = $this->coachsc->where($where)->field(['coach_id','name'])->select();
        return $coachsc;
    }




    public function coach_sc_kesan($cooperation_id)
    {
        $where['cooperation_id'] = ['in',$cooperation_id];
        $where['subject_type'] = ['like','%subject3%'];
        $coachsc = $this->coachsc->where($where)->field(['coach_id','name'])->select();
        return $coachsc;
    }




    public function get_keer_process_sort()
    {
        $key = ['xinshou','jichubj','fangxiangpan','qiting','daisu','chegan','cefangwei','daoche','quxian','zhijiaowan','banpo','ziyou','kaoshi'];
        $value =  ['新手教学','基础部件教学','方向盘练习','启停练习','怠速练习','车感训练','侧方位停车','倒车入库','曲线行驶','直角转弯','半坡起步','自由练习','考试模式'];
        $list = array_combine($key,$value);
        return $list;
    }

    public function get_kesan_process_sort()
    {
        $key  = ['bianchaoting','jiajiandang','yejiandengguang','xian1','xian2','xian3','xian4','xian5','xian6','xian7','xian8','kaoshi3'];
        $value  = ['变道、超车、停车','加减档训练','模拟夜间灯光训练','一号线','二号线','三号线','四号线','五号线','六号线','七号线','八号线','考试模式'];
        $list = array_combine($key,$value);
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
        // var_dump($recommender->toArray());exit;
        return $recommender;
    }

    public function fundsclass_List($cooperation_id)
    {
        $where['cooperation_id'] = ['in', $cooperation_id];
        $payment_source = $this->funds_class->with(['admin'])->where($where)->order(['cooperation_id'])->select();
        $list = [];
        foreach($payment_source as $k=>$v){
            $list[$k]['id'] = $v['id'];
            $list[$k]['state'] = $v['state'];
            if($this->group_type == '11'){
                $list[$k]['name'] = $v['name'].'('.$v['admin']['nickname'].')';
            }else{
                $list[$k]['name'] = $v['name'];
            }
        }
        return $list;
    }

    public function getStudyProcessList($cooperation_id)
    {
        $where['cooperation_id'] = ['in',$cooperation_id];
        $process = $this->processcooperation->with(['process','admin'])->where($where)->select();
        $list = [];
        foreach($process as $k=>$v){
            if(in_array($this->group_type,[11,21])){
                $list[$v['process']['id']]  = $v['process']['process_name'].'('.$v['admin']['nickname'].')';
            }else{
                $list[$v['process']['id']] = $v['process']['process_name'];
            }
        }
        return $list;
    }

    public function courseList($cooperation_id)
    {
        $where['course.cooperation_id'] = ['in',$cooperation_id];
        $res = $this->course->with(['course_log','admin'])->where($where)->select();
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
    
    public function paymentSourceList($cooperation_id)
    {
        $where['paymentsource.cooperation_id'] = ['in',$cooperation_id];
        $res = $this->paymentsource->with('admin')->where($where)->select();
        $list = [];
        foreach($res as $k=>$v){
            if(in_array($this->group_type,[11,21])){
                $v['payment_source'] = $v['payment_source'].'('.$v['admin']['nickname'].')';
            }
            array_push($list,$v);
        }
        return $list;
    }

    public function signupsourceList($cooperation_id)
    {
        $where['cooperation_id'] = ['in',$cooperation_id];
        $res = $this->signupsource->with('admin')->where($where)->select();
        foreach($res as $v){
            if(in_array($this->group_type,[11,21])){
                $v['sign_up_source_name']= $v['sign_up_source_name'].'('.$v['admin']['nickname'].')';
            }
        }
        return $res;
    }

    // public function nationList($cooperation_id)
    // {
    //     $where['cooperation_id'] = ['in',$cooperation_id];
    //     $res = $this->nation->with('admin')->where($where)->order('cooperation_id ASC,state DESC')->select();
    //     foreach($res as $v){
    //         if(in_array($this->group_type,[11,21])){
    //             $v['name']= $v['name'].'('.$v['admin']['nickname'].')';
    //         }
    //     }
    //     return $res;
    // }
    

    public function spaceList()
    {
        $where['id'] = ['in',$this->space_list];
        $res = $this->space->where($where)->field(['id','space_name'])->select();
        return $res;
    }

    public function cooperationList()
    {
        
        $where['id'] = ['in',$this->cooperation_list];
        $res = Db::name('admin')->where($where)->field(['id','nickname'])->select();

        // if($this->group_type == 11){
        //     $pay_cooperation = explode(',',$student['pay_cooperation']);
        //     if(in_array($student['cooperation_id'],$pay_cooperation)){

        //     }
        //     // var_dump($this->cooperation_list);exit;
        //     // var_dump($student['cooperation_id']);
        //     // var_dump($student['pay_cooperation']);
        //     // exit;
        //     // var_dump($res);exit;
        // }
        return $res;
    }

    public function student_validate($params,$id)
    {
        
        if(!$id){
            $phone = $this->model->where('phone',$params['phone'])->find();
        }else{
            $where_phone['phone'] = $params['phone'];
            $where_phone['id'] = ['neq',$id];
            $phone = $this->model->where($where_phone)->find();
        }
        if($phone){
            $this->error('手机号已重复');
        }
    }


    /**
     * 导入
     */
    public function import()
    {
        $file = $this->request->request('file');

        if (!$file) {
            $this->error(__('Parameter %s can not be empty', 'file'));
        }

        $filePath = ROOT_PATH . DS . 'public' . DS . $file;
        if (!is_file($filePath)) {
            $this->error(__('No results were found'));
        }
        //实例化reader
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        if (!in_array($ext, ['csv', 'xls', 'xlsx'])) {
            $this->error(__('Unknown data format'));
        }
        if ($ext === 'csv') {
            $file = fopen($filePath, 'r');
            $filePath = tempnam(sys_get_temp_dir(), 'import_csv');
            $fp = fopen($filePath, "w");
            $n = 0;
            while ($line = fgets($file)) {
                $line = rtrim($line, "\n\r\0");
                $encoding = mb_detect_encoding($line, ['utf-8', 'gbk', 'latin1', 'big5']);
                if ($encoding != 'utf-8') {
                    $line = mb_convert_encoding($line, 'utf-8', $encoding);
                }
                if ($n == 0 || preg_match('/^".*"$/', $line)) {
                    fwrite($fp, $line . "\n");
                } else {
                    fwrite($fp, '"' . str_replace(['"', ','], ['""', '","'], $line) . "\"\n");
                }
                $n++;
            }
            fclose($file) || fclose($fp);

            $reader = new Csv();
        } elseif ($ext === 'xls') {
            $reader = new Xls();
        } else {
            $reader = new Xlsx();
        }
        //导入文件首行类型,默认是注释,如果需要使用字段名称请使用name
        $importHeadType = isset($this->importHeadType) ? $this->importHeadType : 'comment';

        $table = $this->model->getQuery()->getTable();
        $database = \think\Config::get('database.database');
        $fieldArr = [];
        $list = db()->query("SELECT COLUMN_NAME,COLUMN_COMMENT FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND TABLE_SCHEMA = ?", [$table, $database]);
        foreach ($list as $k => $v) {
            if ($importHeadType == 'comment') {
                $fieldArr[$v['COLUMN_COMMENT']] = $v['COLUMN_NAME'];
            } else {
                $fieldArr[$v['COLUMN_NAME']] = $v['COLUMN_NAME'];
            }
        }
        // var_dump($list);exit;

        //加载文件
        $insert = [];

        try {
            if (!$PHPExcel = $reader->load($filePath)) {
                $this->error(__('Unknown data format'));
            }

            $currentSheet = $PHPExcel->getSheet(0);  //读取文件中的第一个工作表
           
            $allColumn = $currentSheet->getHighestDataColumn(); //取得最大的列号
            $allRow = $currentSheet->getHighestRow(); //取得一共有多少行
            $maxColumnNumber = Coordinate::columnIndexFromString($allColumn);
            $fields = [];
            for ($currentRow = 1; $currentRow <= 1; $currentRow++) {
                for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();
                    $fields[] = $val;
                }
            }

            for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
                $values = [];
                for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                    $val = $currentSheet->getCellByColumnAndRow($currentColumn, $currentRow)->getValue();

                    $values[] = is_null($val) ? '' : $val;

                }
                $row = [];

                $temp = array_combine($fields, $values);


                if($fields[0] == '合作方id' && $fields[1]=='场馆id'){
                    $row['space_id'] = $values[1];
                    $row['cooperation_id'] = $values[0];
                }else{
                    $this->error('当前无合作方或者场馆ID标识');
                    continue ;
                }

                
                foreach ($temp as $k => $v) {
                    if (isset($fieldArr[$k]) && $k !== '') {
                        $row[$fieldArr[$k]] = $v;
                    }
                }
                if($row['sex'] == '男'){
                    $row['sex'] = 'male';
                }else{
                    $row['sex'] = 'female';
                }
                if($row['car_type'] == 'C1'){
                    $row['car_type'] = 'cartype1';
                }elseif($row['car_type'] == 'C2'){
                    $row['car_type'] = 'cartype2';
                }elseif($row['car_type'] == 'A1'){
                    $row['car_type'] = 'cartype3';
                }elseif($row['car_type'] == 'A2'){
                    $row['car_type'] = 'cartype4';
                }elseif($row['car_type'] == 'A3'){
                    $row['car_type'] = 'cartype5';
                }elseif($row['car_type'] == 'B1'){
                    $row['car_type'] = 'cartype6';
                }elseif($row['car_type'] == 'B2'){
                    $row['car_type'] = 'cartype7';
                }elseif($row['car_type'] == 'B2'){
                    $row['car_type'] = 'cartype7';
                }

                // $toTimestamp = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($values[2]);
                // var_dump($toTimestamp);
                

                if(!$row['phone']  || (strlen($row['phone']) !==11)){
                    continue;
                }

                if(array_key_exists('registtime',$row)){
                    if($row['registtime']){
                        $row['registtime'] = strtotime($row['registtime']);
                    }
                }
                $re = $this->model->where(['phone'=>$row['phone']])->find();
                
                if($re){
                    continue;
                }

                $row['stu_id'] = 'CSN'.date("YmdHis") . mt_rand(100000, 999999);
                // $row['cooperation_id'] = 244;
                // $row['space_id'] = 76;
                
                // if($row['car_type'] == 'C1'){
                //     $row['car_type'] = 'cartype1';
                // }else{
                //     $row['car_type'] = 'cartype2';
                // }
                if(array_key_exists('sex',$row)){
                    if($row['sex'] == '男'){
                        $row['sex'] = 'male';
                    }else{
                        $row['sex'] = 'female';
                    }
                }else{
                    $row['sex'] = 'male';
                }
                
                
                // $row['car_type'] = 'cartype1';
                // $row['registtime']= $row['registtime'];
                $row['createtime'] = time();
                if ($row) {
                    $insert[] = $row;
                }
            }
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }

        if (!$insert) {
            $this->error(__('No rows were updated'));
        }
        // var_dump($insert[count($insert) - 1]);
        // $this->model->insert($insert[1]);

        try {
            //是否包含admin_id字段
            $has_admin_id = false;
            foreach ($fieldArr as $name => $key) {
                if ($key == 'admin_id') {
                    $has_admin_id = true;
                    break;
                }
            }
            if ($has_admin_id) {
                $auth = Auth::instance();
                foreach ($insert as &$val) {
                    if (!isset($val['admin_id']) || empty($val['admin_id'])) {
                        $val['admin_id'] = $auth->isLogin() ? $auth->id : 0;
                    }
                }
            }
            $this->model->saveAll($insert);
        } catch (PDOException $exception) {
            $msg = $exception->getMessage();
            if (preg_match("/.+Integrity constraint violation: 1062 Duplicate entry '(.+)' for key '(.+)'/is", $msg, $matches)) {
                $msg = "导入失败，包含【{$matches[1]}】的记录已存在";
            };
            $this->error($msg);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }

        $this->success();
    }


    /**
     * 导出
     */
    public function detail1()
    {
        if ($this->request->isPost()) {
            set_time_limit(0);

            $search = $this->request->post('search');
            $ids = $this->request->post('ids');
            $filter = $this->request->post('filter');
            $op = $this->request->post('op');

            if(strpos('car_type',$op)){
                str_replace('car_type','student.car_type',$op);
            }

            if(strpos('car_type',$filter)){
                str_replace('car_type','student.car_type',$filter);
            }

            $student = new \app\admin\model\Student;
            //$excel = new PHPExcel();
            $spreadsheet = new Spreadsheet();

            $spreadsheet->getProperties()
                ->setCreator("FastAdmin")
                ->setLastModifiedBy("FastAdmin")
                ->setTitle("标题")
                ->setSubject("Subject");
            $spreadsheet->getDefaultStyle()->getFont()->setName('Microsoft Yahei');
            $spreadsheet->getDefaultStyle()->getFont()->setSize(12);

            $worksheet = $spreadsheet->setActiveSheetIndex(0);
            
            
            // $whereIds = $ids == 'all' ? '1=1' : ['id' => ['in', explode(',', $ids)]];
            $this->request->get(['search' => $search, 'ids' => $ids, 'filter' => $filter, 'op' => $op]);
            // list($where, $sort, $order, $offset, $limit) = $this->buildparams_new();
            

            $line = 1;

            //设置过滤方法
            $this->request->filter(['strip_tags']);

            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $wheror = [];
            list($where, $sort, $order, $offset, $limit) = $this->buildparams_new();
            if(!strpos($op,'admin.nickname')){
                $wheror['student.cooperation_id'] = ['in', $_SESSION['think']['admin']['cooperation_list']];
            }

            if(!strpos($op,'space.space_name')){
                $wheror['student.space_id'] = ['in', $_SESSION['think']['admin']['space_list']];
            }


            $total = $student
                ->where($where)
                ->where($wheror)
                ->with(['space','admin','course_log']) 
                // ->order($sort,$order)
                ->count();

            $list = $student
                ->where($where)
                ->where($wheror)
                ->with(['space','admin','course_log']) 
                // ->order($sort,$order)
                ->limit($offset, $limit)
                ->select();
        

            $list = collection($list)->toArray();

            $result = array("total" => $total, "rows" => $list);
            
            
            $first = array_keys($list[0]);

            if(!file_exists( ROOT_PATH.'public/uploads/'.date('Ymd').'/'))
                mkdir(ROOT_PATH.'public/uploads/'.date('Ymd',time()).'/',0777,true);
            foreach ($first as $index => $item) {
                $worksheet->setCellValueByColumnAndRow($index, 1, __($item));
            }
            
            // $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(__DIR__ . '/muban/test.xls');  //读取模板
            
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $worksheet = $spreadsheet->getActiveSheet();     //指向激活的工作表
            $worksheet->setTitle('模板测试标题');

            $cartype = [];
            $course = [];
            $worksheet->getCell('A1')->setValue('报名时间');    //id
            $worksheet->getCell('B1')->setValue('姓名');    //id
            $worksheet->getCell('C1')->setValue('性别');    //id
            $worksheet->getCell('D1')->setValue('民族');    //id
            $worksheet->getCell('E1')->setValue('手机号');    //id
            $worksheet->getCell('F1')->setValue('身份证');    //id
            $worksheet->getCell('G1')->setValue('课程名称');    //id
            $worksheet->getCell('H1')->setValue('缴费金额');    //id
            $worksheet->getCell('I1')->setValue('备注');    //id

            for($i=0;$i<$total;++$i){
                // if(){

                // }
                //向模板表中写入数据
                // $worksheet->setCellValue('A'.($i+2), '模板测试内容');   //送入A1的内容
                $worksheet->getCell('A'.($i+2))->setValue($result['rows'][$i]['registtime_text']);  //报名时间
                $worksheet->getCell('B'.($i+2))->setValue($result['rows'][$i]['name']);  //姓名
                $worksheet->getCell('C'.($i+2))->setValue(__($result['rows'][$i]['sex_text']));  //性别
                $worksheet->getCell('D'.($i+2))->setValue($result['rows'][$i]['nation']);  //民族
                $worksheet->getCell('E'.($i+2))->setValue($result['rows'][$i]['phone']);  //手机号
                $worksheet->getCell('F'.($i+2))->setValue($result['rows'][$i]['idcard']);  //身份证
                $worksheet->getCell('G'.($i+2))->setValue($result['rows'][$i]['course_log']['course']);  //课程名称
                $worksheet->getCell('H'.($i+2))->setValue($result['rows'][$i]['tuition']);  //缴费金额
                $worksheet->getCell('I'.($i+2))->setValue($result['rows'][$i]['remarks']);  //备注
                // $worksheet->getCell('E'.($i+2))->setValue($result['rows'][$i]['money']);  //金额
                if(!array_key_exists($result['rows'][$i]['car_type_text'],$cartype)){
                    $cartype[ __($result['rows'][$i]['car_type_text'])] = 1;
                }else{
                    $cartype[ __($result['rows'][$i]['car_type_text'])] = ($cartype[ __($result['rows'][$i]['car_type_text'])] +1);
                }

                if(!array_key_exists($result['rows'][$i]['course_log']['course'],$course)){
                    $course[$result['rows'][$i]['course_log']['course']] = 1;
                }else{
                    $course[$result['rows'][$i]['course_log']['course']] = ($course[$result['rows'][$i]['course_log']['course']] +1);
                }
                
            }
            $cartype_str = '';
            foreach($cartype as $k=>$v){
                $cartype_str = $cartype_str.$k.':'.$v.'人,';
            }


            $course_str = '';
            foreach($course as $k=>$v){
                $course_str = $course_str.$k.':'.$v.'人,';
            }
        
            $worksheet->getCell('B'.($total+3))->setValue('总人数：'.$total.'人,'.$cartype_str); 
            $worksheet->getCell('B'.($total+4))->setValue('总人数：'.$total.'人,'.$course_str); 

            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');

            $filename = md5(time().'.xlsx').'.xlsx';
            
            $writer = new Xlsxn($spreadsheet);
            $host = $_SERVER['HTTP_HOST'];
            // var_dump(ROOT_PATH.'public/uploads/'.$filename);
            // var_dump($host.'/uploads/'.$filename);exit;
            $writer->save(ROOT_PATH.'public/uploads/'.date('Ymd').'/'.$filename);
            
            // header('location:https://'.$host.'/uploads/'.date('Ymd').'/'.$filename);
            // var_dump(ROOT_PATH.'public/uploads/'.date('Ymd').'/'.$filename);exit;
            json_encode(array('error'=>false, 'export_path'=>'download_excel/' . ROOT_PATH.'public/uploads/'.date('Ymd').'/'.$filename));


            header ( 'Content-Type: application/vnd.ms-excel' ); 
            header ( 'Content-Disposition: attachment;filename="'.ROOT_PATH.'public/uploads/'.date('Ymd').'/'.$filename); 
            header ( 'Cache-Control: max-age=0' ); 
            $writer -> save ( 'php://output' ); 
            unlink(ROOT_PATH.'public/uploads/'.date('Ymd').'/'.$filename);
        }
        $this->success('返回成功');
    }
    public function buildparams_new()
    {
        $searchfields = 'id';
        $relationSearch = false;
        $search = $this->request->get("search", '');
        $filter = $this->request->get("filter", '');
        $op = $this->request->get("op", '', 'trim');
        $sort = $this->request->get("sort", !empty($this->model) && $this->model->getPk() ? $this->model->getPk() : 'id');
        $order = $this->request->get("order", "DESC");
        $offset = $this->request->get("offset/d", 0);
        $limit = $this->request->get("limit/d", 999999);

        // var_dump($search,$op,$sort,$order,$offset,$filter );exit;
        //新增自动计算页码
        $page = $limit ? intval($offset / $limit) + 1 : 1;
        if ($this->request->has("page")) {
            $page = $this->request->get("page/d", 1);
        }
        
        $this->request->get([config('paginate.var_page') => $page]);

        $filter = (array)json_decode(html_entity_decode(htmlspecialchars_decode($filter)), true);
        $op = (array)json_decode(html_entity_decode(htmlspecialchars_decode($op)), true);

        $filter = $filter ? $filter : [];
        $where = [];
        $alias = [];
        $bind = [];
        $name = '';
        $aliasName = 'student.';

        $sortArr = explode(',', $sort);
        foreach ($sortArr as $index => & $item) {
            $item = stripos($item, ".") === false ? $aliasName . trim($item) : $item;
        }
        unset($item);
        $sort = implode(',', $sortArr);
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            $where[] = [$aliasName . $this->dataLimitField, 'in', $adminIds];
        }

        if ($search) {
            $searcharr = is_array($searchfields) ? $searchfields : explode(',', $searchfields);
            foreach ($searcharr as $k => &$v) {
                $v = stripos($v, ".") === false ? $aliasName . $v : $v;
            }
            unset($v);
            $where[] = [implode("|", $searcharr), "LIKE", "%{$search}%"];
        }
        $index = 0;
        foreach ($filter as $k => $v) {
            if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $k)) {
                continue;
            }
            $sym = isset($op[$k]) ? $op[$k] : '=';
            if (stripos($k, ".") === false) {
                $k = $aliasName . $k;
            }
            $v = !is_array($v) ? trim($v) : $v;
            $sym = strtoupper(isset($op[$k]) ? $op[$k] : $sym);
            //null和空字符串特殊处理
            if (!is_array($v)) {
                if (in_array(strtoupper($v), ['NULL', 'NOT NULL'])) {
                    $sym = strtoupper($v);
                }
                if (in_array($v, ['""', "''"])) {
                    $v = '';
                    $sym = '=';
                }
            }

            switch ($sym) {
                case '=':
                case '<>':
                    $where[] = [$k, $sym, (string)$v];
                    break;
                case 'LIKE':
                case 'NOT LIKE':
                case 'LIKE %...%':
                case 'NOT LIKE %...%':
                    $where[] = [$k, trim(str_replace('%...%', '', $sym)), "%{$v}%"];
                    break;
                case '>':
                case '>=':
                case '<':
                case '<=':
                    $where[] = [$k, $sym, intval($v)];
                    break;
                case 'FINDIN':
                case 'FINDINSET':
                case 'FIND_IN_SET':
                    $v = is_array($v) ? $v : explode(',', str_replace(' ', ',', $v));
                    $findArr = array_values($v);
                    foreach ($findArr as $idx => $item) {
                        $bindName = "item_" . $index . "_" . $idx;
                        $bind[$bindName] = $item;
                        $where[] = "FIND_IN_SET(:{$bindName}, `" . str_replace('.', '`.`', $k) . "`)";
                    }
                    break;
                case 'IN':
                case 'IN(...)':
                case 'NOT IN':
                case 'NOT IN(...)':
                    $where[] = [$k, str_replace('(...)', '', $sym), is_array($v) ? $v : explode(',', $v)];
                    break;
                case 'BETWEEN':
                case 'NOT BETWEEN':
                    $arr = array_slice(explode(',', $v), 0, 2);
                    if (stripos($v, ',') === false || !array_filter($arr)) {
                        continue 2;
                    }
                    //当出现一边为空时改变操作符
                    if ($arr[0] === '') {
                        $sym = $sym == 'BETWEEN' ? '<=' : '>';
                        $arr = $arr[1];
                    } elseif ($arr[1] === '') {
                        $sym = $sym == 'BETWEEN' ? '>=' : '<';
                        $arr = $arr[0];
                    }
                    $where[] = [$k, $sym, $arr];
                    break;
                case 'RANGE':
                case 'NOT RANGE':
                    $v = str_replace(' - ', ',', $v);
                    $arr = array_slice(explode(',', $v), 0, 2);
                    if (stripos($v, ',') === false || !array_filter($arr)) {
                        continue 2;
                    }
                    //当出现一边为空时改变操作符
                    if ($arr[0] === '') {
                        $sym = $sym == 'RANGE' ? '<=' : '>';
                        $arr = $arr[1];
                    } elseif ($arr[1] === '') {
                        $sym = $sym == 'RANGE' ? '>=' : '<';
                        $arr = $arr[0];
                    }

                    $tableArr = explode('.', $k);
                    // if (count($tableArr) > 1 && $tableArr[0] != $name && !in_array($tableArr[0], $alias) && !empty($this->model)) {
                    //     //修复关联模型下时间无法搜索的BUG
                    //     $relation = Loader::parseName($tableArr[0], 1, false);
                    //     $alias[$this->model->$relation()->getTable()] = $tableArr[0];
                    // }
                    $where[] = [$k, str_replace('RANGE', 'BETWEEN', $sym) . ' TIME', $arr];
                    break;
                case 'NULL':
                case 'IS NULL':
                case 'NOT NULL':
                case 'IS NOT NULL':
                    $where[] = [$k, strtolower(str_replace('IS ', '', $sym))];
                    break;
                default:
                    break;
            }
            $index++;
        }

        if (!empty($this->model)) {
            $this->model->alias($alias);
        }
        $model = $this->model;
        $where = function ($query) use ($where, $alias, $bind, &$model) {
            if (!empty($model)) {
                $model->alias($alias);
                $model->bind($bind);
            }
            foreach ($where as $k => $v) {
                if (is_array($v)) {
                    call_user_func_array([$query, 'where'], $v);
                } else {
                    $query->where($v);
                }
            }
        };
        return [$where, $sort, $order, $offset, $limit, $page, $alias, $bind];
    }

    
}
