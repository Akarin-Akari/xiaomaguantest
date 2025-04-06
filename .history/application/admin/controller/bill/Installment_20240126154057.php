<?php

namespace app\admin\controller\bill;

use app\common\controller\Backend;
use Exception;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Installment extends Backend
{
    
    /**
     * Installment模型对象
     * @var \app\admin\model\Installment
     */
    protected $model = null;
    public $space = null;
    public $student = null;
    public $course_log = null;
    public $space_list = null;
    public $cooperation_list = null;
    public $FundsClassIdList = null ;
    public $paymentSourseList = null ;
    public $payment_source = null ;
    public $funds_class = null ;
    public $group_type = null ;
    
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Installment;
        $this->space = new \app\admin\model\Space;
        $this->student = new \app\admin\model\Student;
        $this->course_log = new \app\admin\model\course\Log;
        $this->funds_class = new \app\admin\model\Fundsclass;

        $this->payment_source = new \app\admin\model\Paymentsource;
        $this->space_list = $_SESSION['think']['admin']['space_list'];
        $this->cooperation_list = $_SESSION['think']['admin']['cooperation_list'];
        $this->group_type = $_SESSION['think']['admin']['group_type'];
        $this->view->assign("auditList", $this->model->getAuditList());
        $this->view->assign("spaceList", $this->spaceList());
        $this->view->assign("payStatusList", $this->model->getPayStatusList());
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
            $where_cooperation['installment.cooperation_id'] = ['in',$this->cooperation_list];

            $list = $this->model
                    ->with(['admin','space','fundsclass','student','paymentsource'])
                    ->where($where)
                    ->where($where_cooperation)
                    ->order($sort, $order)
                    ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id','times','stu_id','money','platform_number','payment_number','audit','pay_status','pay_time','deletetime']);
                $row->visible(['admin']);
				$row->getRelation('admin')->visible(['nickname']);
				$row->visible(['space']);
				$row->getRelation('space')->visible(['space_name']);
				$row->visible(['fundsclass']);
				$row->getRelation('fundsclass')->visible(['name','state']);
				$row->visible(['student']);
				$row->getRelation('student')->visible(['name','stu_id']);
				$row->visible(['paymentsource']);
				$row->getRelation('paymentsource')->visible(['payment_source']);
            }
            $money = $this->model->with(['admin','space','fundsclass','student','paymentsource'])->where($where)->where($where_cooperation)->sum('money');

            $result = array("total" => $list->total(), "rows" => $list->items(),'money'=>$money);

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
            $student = $this->student->where('stu_id',$params['stu_id'])->find();
            if(!$student){
                $this->error('学员不存在');
            }
            $params['cooperation_id'] = $student['cooperation_id'];
            $params['space_id'] = $student['space_id'];
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
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
                    $student = $this->student->with('course_log')->where(['stu_id'=>$params['stu_id']])->find();
                    
                    $installment_id= explode(',',$student['installment_id']);
                    $where_installment['id'] = ['in',$installment_id];
                    $installment = model('installment')->where($where_installment)->order('times asc')->select();

                    $result = $this->model->allowField(true)->save($params);
                    $id = $this->model->getLastInsID();
                    array_push($installment_id,$id);
                    $shijiao_list = array_column($installment->toArray(),'money');
                    $shijiao = array_sum($shijiao_list);
                    $shijiao += $params['money'];
                    if($shijiao >= $student['course_log']['money']){
                        $update_stu['payment_process'] = 'payed';
                    }elseif($shijiao < $student['course_log']['money'] && $shijiao >0){
                        $update_stu['payment_process'] = 'paying';
                    }else{
                        $update_stu['payment_process'] = 'unpaid';
                    }
                    $update_stu['installment_id'] = implode(',',$installment_id);
                    $this->student->where(['stu_id'=>$params['stu_id']])->update($update_stu);
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
        $cooperation_id = $this->cooperation_list;
        
        $this->FundsClassIdList = $this->getFundsClassIdList($cooperation_id);
        $this->paymentSourseList = $this->getpaymentSourseList($cooperation_id);
        $this->view->assign("paymentSourseList", $this->paymentSourseList );
        $this->view->assign("FundsClassIdList", $this->FundsClassIdList );
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

        $student = $this->student->where('stu_id',$row['stu_id'])->find();
        if(!$student){
            $this->error('学员数据错误');
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
        $this->view->assign("paymentSourseList", $this->getpaymentSourseList($student['cooperation_id']));
        return $this->view->fetch();
    }


    public function audit($ids=null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        var_dump($row->toArray());exit;
        $params['audit'] = 'yes';

        $row->allowField(true)->save($params);
        $update['audit'] = 'yes';
        $result = $this->student->where(['stu_id'=>$row['stu_id']])->update($update);
        if ($result !== false) {
            $this->success();
        } else {
            $this->error(__('No rows were updated'));
        }
    }


    public function spaceList()
    {
        $where['space.id'] = ['in',$this->space_list];
        $res = $this->space->with(['admin'])->where($where)->order('cooperation_id asc')->select();
        $list = [];
        foreach($res as $k=>$v){
            $list[$k]['id'] = $v['id'];
            $list[$k]['space_name'] = $v['space_name'].'--'.$v['admin']['nickname'];
            if($this->group_type == '11'){
                $list[$k]['space_name'] = $v['space_name'].'--'.$v['admin']['nickname'];
            }else{
                $list[$k]['space_name'] = $v['space_name'];
            }
        }
        return $list;
    }


    public function getpaymentSourseList($cooperation_id)
    {
        $where['cooperation_id'] = ['in', $cooperation_id];
        $payment_source = $this->payment_source->with(['admin'])->where($where)->order(['cooperation_id'])->select();
        $list = [];
        foreach($payment_source as $k=>$v){
            $list[$k]['id'] = $v['id'];
            if($this->group_type == '11'){
                $list[$k]['payment_source'] = $v['payment_source'].'---'.$v['admin']['nickname'];
            }else{
                $list[$k]['payment_source'] = $v['payment_source'];
            }
        }
        return $list;
    }

    public function getFundsClassIdList($cooperation_id)
    {

        $where['cooperation_id'] = ['in', $cooperation_id];
        $payment_source = $this->funds_class->with(['admin'])->where($where)->order(['cooperation_id'])->select();
        $list = [];
        foreach($payment_source as $k=>$v){
            $list[$k]['id'] = $v['id'];
            if($this->group_type == '11'){
                $list[$k]['name'] = $v['name'].'---'.$v['admin']['nickname'];
            }else{
                $list[$k]['name'] = $v['name'];
            }
        }
        return $list;
    }


    public function export()
    {
        if ($this->request->isPost()) {
            set_time_limit(0);
            $search = $this->request->post('search');
            $ids = $this->request->post('ids');
            $filter = $this->request->post('filter');
            $op = $this->request->post('op');
            $columns = $this->request->post('columns');

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
            $whereIds = $ids == 'all' ? '1=1' : ['id' => ['in', explode(',', $ids)]];
            $this->request->get(['search' => $search, 'ids' => $ids, 'filter' => $filter, 'op' => $op]);
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            
            $line = 1;

            //设置过滤方法
            $this->request->filter(['strip_tags']);

            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $total = $this->model
                ->where($whereIds)
                ->order($sort,$order)
                ->count();

            $list = $this->model
                ->where($whereIds)
                ->order($sort,$order)
                ->limit($offset, $limit)
                ->select();

            var_dump($list);
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

            for($i=0;$i<$total;++$i){
                //向模板表中写入数据
                // $worksheet->setCellValue('A'.($i+2), '模板测试内容');   //送入A1的内容
                $worksheet->getCell('A'.($i+2))->setValue($result['rows'][$i]['id']);    //id
                $worksheet->getCell('B'.($i+2))->setValue($result['rows'][$i]['stu_id']);  //学员编号
                $worksheet->getCell('C'.($i+2))->setValue($result['rows'][$i]['money']);  //金额
                // $worksheet->getCell('b3')->setValue($result['rows'][$i]['title']);  //标题
                // $worksheet->getCell('b4')->setValue($result['rows'][$i]['content']);  //内容

                //下载文档
                // header('Content-Type: application/vnd.ms-excel');
                // header('Content-Disposition: attachment;filename="'. date('Y-m-d')  .'test'.'.xlsx"');
                // header('Cache-Control: max-age=0');
                
            }
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');

            $filename = md5(time().'.xlsx').'.xlsx';
                
            $writer = new Xlsx($spreadsheet);
            $host = $_SERVER['HTTP_HOST'];
            // var_dump(ROOT_PATH.'public/uploads/'.$filename);
            // var_dump($host.'/uploads/'.$filename);exit;
            $writer->save(ROOT_PATH.'public/uploads/'.date('Ymd').'/'.$filename);
            header('location:https://'.$host.'/uploads/'.date('Ymd').'/'.$filename);
            return;
        }
    }
}
