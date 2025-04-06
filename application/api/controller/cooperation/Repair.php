<?php

namespace app\api\controller\cooperation;

use app\common\controller\Api;
use think\Db;
use think\Cache;
/**
 * 报修
 */
class Repair extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent ::_initialize();
        $this->repair = new \app\admin\model\Repair;
        $this->common = new \app\api\controller\Common;
    }

    /**
     * 维修列表
     */
    public function repairlist(){
        $params = $this->request->post();
        // $params['space_id'] = '5';
        // $params['page'] = '1';
        // $params['status'] = '1';
        // $params['starttime'] = '2020-12-20';
        // $params['endtime'] = '2020-12-23';
        if(empty($params['space_id']) || empty($params['page']) || empty($params['status']) || empty($params['starttime'])||empty($params['endtime'])){
            $this->error('参数缺失');
        }
        $page = $params['page'];
        $status = $params['status'];
        $starttime = strtotime($params['starttime']);
        $endtime = strtotime($params['endtime'].'23:59:59');
        $pagenum = 10;
        $numl = $pagenum*($page-1);
        $space_id = $params['space_id'];
        $where['repair.space_id'] = $space_id;
        $where['repair.status'] = $status;
        $where['repair.createtime'] = ['between',[$starttime,$endtime]];
        $list = $this->repair->with(['machinecar'])->where($where)->limit($numl,$pagenum)->order('id desc')->select();
        $data = [];
        foreach($list as $k=>$v){
            // var_dump($v->toArray());exit;
            $data[$k]['id'] = $v['id'];
            $data[$k]['machine_code'] = $v['machinecar']['machine_code'];
            $data[$k]['status'] = $v['status'];
            $data[$k]['remarks'] = $v['remarks'];
            $data[$k]['createtime'] = $v['createtime'];
            $data[$k]['type'] = $v['type'];
        }
        $this->success('返回成功',$data);
    }

    /**
     * 添加维修
     */
    public function add(){
        $params = $this->request->post();
        if(empty($params['space_id'])){
            $this->error('参数缺失');
        }
        $type = $params['type'];
        $space_id = $params['space_id'];
        if($type ==0){
            $where['space_id'] = $space_id;
            $where['state'] = 1;
            $machine_list = Db::name('machine_car')->where($where)->field(['id','machine_code'])->select();
        }else if($type ==1){
            if(empty($params['machine_id']) ||empty($params['repair_type'])){
                $this->error('参数缺失');
            }
            $update['space_id']= $params['space_id'];
            $update['cooperation_id']= $this->space->where('id',$space_id)->find()['cooperation_id'];
            $update['machine_id'] = $params['machine_id'];
            $update['status'] = 1;
            $update['images']= $params['images'];
            $update['type'] = $params['repair_type'];
            $update['remarks'] = $params['remarks'];
            $result = $this->repair->allowField(true)->save($update);
            if($result){
                $this->success('返回成功');
            }else{
                $this->error('返回失败');
            }
        }
        $this->success('返回成功',$machine_list);
        
    }

    /**
     * 编辑维修
     */
    public function edit(){
        $params = $this->request->post();
        if(empty($params['id'])){
            $this->error('参数缺失');
        }

        $type = $params['type'];
        $repair_id = $params['id'];
        $repair = $this->repair->where('id',$repair_id)->find();
        $machine_list = Db::name('machine_car')->where('space_id',$repair['space_id'])->field(['id','machine_code'])->select();
        $date['machine_list'] = $machine_list;
        $date['repair'] = $repair;
        if($type ==1){
            if(empty($params['remarks']) || empty($params['machine_id']) ||empty($params['repair_type'])){
                $this->error('参数缺失');
            }
            $update['machine_id'] = $params['machine_id'];
            $update['images'] = $params['images'];
            $update['type'] = $params['repair_type'];
            $update['remarks'] = $params['remarks'];
            $result = $repair->allowField(true)->save($update);
            if($result){
                $this->success('返回成功');
            }else{
                $this->error('提交失败请重新提交');
            }
        }
        $this->success('返回成功',$date);
    }

    /**
     * 完成维修
     */
    public function finish(){
        $params = $this->request->post();
        if(empty($params['id'])){
            $this->error('参数缺失');
        }
        $id = $params['id'];
        $where['id'] = $id;
        $repair = $this->repair->where($where)->find();
        if(!$repair){
            $this->error('操作有误');
        }
        $update['status'] = 4;
        $res = $repair->allowField(true)->save($update);
        if($res){
            $this->success('提交成功');
        }
        $this->error('提交失败，请重新提交');
    }
}
