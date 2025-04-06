<?php

namespace app\api\controller\cooperation;

use app\common\controller\Api;
use app\common\library\token\driver\Redis;
use TencentCloud\Cdn\V20180606\Models\Cache as ModelsCache;
use think\Db;
use think\Cache;
/**
 * 开机流程所需接口
 */
class Coachconfig extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    protected $coach = null;
    protected $order = null;
    protected $temporaryorder = null;
    protected $evaluation = null;
    protected $coachleave = null;
    protected $common = null;
    protected $space = null;

    public function _initialize()
    {
        parent ::_initialize();
        $this->coach = new \app\admin\model\Coach;
        $this->order = new \app\admin\model\Order;
        $this->space = new \app\admin\model\Space;
        $this->temporaryorder = new \app\admin\model\Temporaryorder;
        $this->evaluation = new \app\admin\model\Evaluation;
        $this->coachleave = new \app\admin\model\Coachleave;

        $this->common = new \app\api\controller\Common;
    }

    public function getcoachlist(){
        $params = $this->request->post();
        // $params['space_id'] = 5;
        if(empty($params['space_id'])){
            $this->error('参数缺失');
        }
        $space_id = $params['space_id'];
        $coach = $this->coach->where('space_id',$space_id)->select();
        $list = [];
        foreach($coach as $k=>$v){
            $list[$k]['photoimage'] = $v['photoimage'];
            $list[$k]['coach_id'] = $v['coach_id'];
            $list[$k]['name'] = $v['name'];
            $list[$k]['car_type'] = $v['car_type_text'];
            $where_coach['coach_id'] = $v['coach_id'];
            $where_coach['order_status'] = 'finished';
            $total1 = $this->order->where($where_coach)->count();
            $total2 = $this->temporaryorder->where($where_coach)->count();
            $list[$k]['total_hour'] = ($total1 +$total2)*2;
            unset($car_type_id);
            unset($cartype);
        }
        $this->success('返回成功',$list);
    }
    
    public function add()
    {
        $params = $this->request->post();
        // Cache::set('addcoach',$params);
        // $params = Cache::get('addcoach');
        if(empty($params['space_id'])){
            $this->error('参数缺失');
        }
        Cache::set('params',$params);
        // exit;
        $info = str_replace('&quot;','"',$params['info']);
        $info = json_decode($info,true)[0];
        $type = $params['type'];
        $space_id = $params['space_id'];
        $space = $this->space->where('id',$space_id)->find();
        if(!$space){
            $this->error('场馆值错误');
        }
        if($type ==1){
            $info['coach_id'] = 'CTN'.date("YmdHis") . mt_rand(100000, 999999);
            // $info['fstdrilictime'] = strtotime($info['fstdrilictime']);
            // $info['succtime'] = strtotime($info['succtime']);
            // $info['failuretime'] = strtotime($info['failuretime']);
            $phone_coach = $this->coach->where('phone',$info['phone'])->find();
            $idcard_coach = $this->coach->where('idcard',$info['idcard'])->find();
            if($phone_coach || $idcard_coach){
                $this->error('手机号或身份证已存在');
            }
            $info['cooperation_id'] = $space['cooperation_id'];
            $config_time = $info['config_time'];
            unset($info['config_time']);
            unset($info['id']);
            $info['space_id'] = $space_id;
            $result = $this->coach->save($info);
            $id = $this->coach->getLastInsID();
            foreach($config_time as $v){
                $insert['coach_id'] = $id;
                $insert['starttimes'] = $v['starttimes'];
                $insert['endtimes'] = $v['endtimes'];
                $insert['c1_number'] = $v['c1_number'];
                $insert['c2_number'] = $v['c2_number'];
                Db::name('coach_config_time_people')->insert($insert);
                unset($insert);
            }
            $this->success('返回成功');
        }
        $this->success('返回成功');
    }


    public function test()
    {
        $params = Cache::get('params');
        $info = str_replace('&quot;','"',$params['info']);
        $info = json_decode($info,true)[0];
        $type = $params['type'];
        $coach_id = $params['coach_id'];
        $coach = $this->coach_validate($coach_id);
        $space_id = $coach['space_id'];
        $coach['config_time_people'] = Db::name('coach_config_time_people')->where('coach_id',$coach['id'])->select();
        $list = [];
        $config_ids = [];
        foreach($coach['config_time_people'] as $k=>$v){
            array_push($config_ids,$v['id']);
            $list[$k]['id'] = $v['id'];
            $list[$k]['starttimes'] = substr($v['starttimes'],0,5);
            $list[$k]['endtimes'] =  substr($v['endtimes'],0,5);
            $list[$k]['c1_number'] = $v['c1_number'];
            $list[$k]['c2_number'] = $v['c2_number'];
        }
        $coach['config_time_people'] = $list;
        $data['data'] = $coach;
        if($coach && ($type == 0)){
            $this->success('返回成功',$data);
        }elseif($type == 1){
            $info = str_replace('&quot;','"',$params['info']);
            $info = json_decode($info,true)[0];
            $info['id'] = $info['id'];
            $info['cooperation_id'] = $coach['cooperation_id'];
            $info['space_id'] = $space_id;
            // $info['fstdrilictime'] = strtotime($info['fstdrilictime']);
            // $info['succtime'] = strtotime($info['succtime']);
            // $info['failuretime'] = strtotime($info['failuretime']);
            $info['drilicenceimage'] = $info['drilicenceimage'];
            
            // var_dump($info);exit;
            $info['sex'] = 'male';
            $ids = [];
            $coach->allowField(true)->save($info);
            $coach['config_time_people'] = Db::name('coach_config_time_people')->where('coach_id',$info['id'])->select();
            $config_time = $info['config_time'];
            $list = [];
            
            foreach($config_time as $v){
                if($v['id']){
                    array_push($ids,$v['id']);
                    $edit_time['coach_id'] = $info['id'];
                    $edit_time['starttimes'] = $v['starttimes'];
                    $edit_time['endtimes'] = $v['endtimes'];
                    $edit_time['c1_number'] = $v['c1_number'];
                    $edit_time['c2_number'] = $v['c2_number'];
                    Db::name('coach_config_time_people')->where('id',$v['id'])->update($edit_time);
                }else{
                    $edit_time['coach_id'] = $info['id'];
                    $edit_time['starttimes'] = $v['starttimes'];
                    $edit_time['endtimes'] = $v['endtimes'];
                    $edit_time['c1_number'] = $v['c1_number'];
                    $edit_time['c2_number'] = $v['c2_number'];
                    Db::name('coach_config_time_people')->insert($edit_time);
                }
                unset($edit_time);
            }
            $res = array_diff($config_ids,$ids);
            if($res){
                foreach($res as $v){
                    Db::name('coach_config_time_people')->where('id',$v)->delete();
                }
            }

            $this->success('返回成功');
        }
    }


    public function edit(){
        // $params = Cache::get('editcoach');
        // Cache::set('editcoach',$params,10*60);

        $params = $this->request->post();
        if(empty($params['coach_id'])){
            $this->error('参数缺失');
        }
        Cache::set('params',$params);

        $type = $params['type'];
        $coach_id = $params['coach_id'];
        $coach = $this->coach_validate($coach_id);
        $space_id = $coach['space_id'];
        $coach['config_time_people'] = Db::name('coach_config_time_people')->where('coach_id',$coach['id'])->select();
        $list = [];
        $config_ids = [];
        foreach($coach['config_time_people'] as $k=>$v){
            array_push($config_ids,$v['id']);
            $list[$k]['id'] = $v['id'];
            $list[$k]['starttimes'] = substr($v['starttimes'],0,5);
            $list[$k]['endtimes'] =  substr($v['endtimes'],0,5);
            $list[$k]['c1_number'] = $v['c1_number'];
            $list[$k]['c2_number'] = $v['c2_number'];
        }
        $coach['config_time_people'] = $list;
        $data['data'] = $coach;
        if($coach && ($type == 0)){
            $this->success('返回成功',$data);
        }elseif($type == 1){
            $info = str_replace('&quot;','"',$params['info']);
            $info = json_decode($info,true)[0];
            $info['id'] = $info['id'];
            $info['cooperation_id'] = $coach['cooperation_id'];
            $info['space_id'] = $space_id;
            // $info['fstdrilictime'] = strtotime($info['fstdrilictime']);
            // $info['succtime'] = strtotime($info['succtime']);
            // $info['failuretime'] = strtotime($info['failuretime']);
            $info['drilicenceimage'] = $info['drilicenceimage'];
            
            // var_dump($info);exit;
            $ids = [];
            $coach->allowField(true)->save($info);
            $coach['config_time_people'] = Db::name('coach_config_time_people')->where('coach_id',$info['id'])->select();
            $config_time = $info['config_time'];
            $list = [];
            
            foreach($config_time as $v){
                if($v['id']){
                    array_push($ids,$v['id']);
                    $edit_time['coach_id'] = $info['id'];
                    $edit_time['starttimes'] = $v['starttimes'];
                    $edit_time['endtimes'] = $v['endtimes'];
                    $edit_time['c1_number'] = $v['c1_number'];
                    $edit_time['c2_number'] = $v['c2_number'];
                    Db::name('coach_config_time_people')->where('id',$v['id'])->update($edit_time);
                }else{
                    $edit_time['coach_id'] = $info['id'];
                    $edit_time['starttimes'] = $v['starttimes'];
                    $edit_time['endtimes'] = $v['endtimes'];
                    $edit_time['c1_number'] = $v['c1_number'];
                    $edit_time['c2_number'] = $v['c2_number'];
                    Db::name('coach_config_time_people')->insert($edit_time);
                }
                unset($edit_time);
            }
            $res = array_diff($config_ids,$ids);
            if($res){
                foreach($res as $v){
                    Db::name('coach_config_time_people')->where('id',$v)->delete();
                }
            }

            $this->success('返回成功');
        }
    }

    public function get_coach_evaluation(){
        $params = $this->request->post();
        // $params['coach_id'] = 'CTN20201027111519116883';
        // $params['page'] = 1;
        if(empty($params['coach_id'])  || empty($params['page'])){
            $this->error('参数缺失');
        }
        $page = $params['page'];
        $coach_id = $params['coach_id'];
        $pagenum = 10;
        $numl = $pagenum*($page-1);

        $this->coach_validate($coach_id);
        $res = $this->evaluation->with(['space','coach'])->where('coach.coach_id',$coach_id)->limit($numl,$pagenum)->select();
        $list = [];
        
        foreach($res as $k=>$v){
            $list[$k]['space_evaluate'] = $v['space_evaluate'];
            $list[$k]['ordernumber'] = $v['ordernumber'];
            $list[$k]['overall'] = $v['overall'];
            $list[$k]['space_name'] = $v['space']['space_name'];
            $list[$k]['coach_name'] = $v['coach']['name'];
            $list[$k]['createtime'] = $v['createtime'];
            if($v['student_type'] == 'student'){
                $student = $this->order->where('stu_id',$v['stu_id'])->find();
            }else{
                $student = $this->temporaryorder->where('stu_id',$v['stu_id'])->find();
            }
            $list[$k]['student_name'] = $student['name'];
        }
        $this->success('返回成功',$list);
    }


    public function coach_validate($coach_id){
        $coach = $this->coach->where('coach_id',$coach_id)->find();

        if(!$coach){
            $this->error('没有查询到教员');
        }
        return $coach;
    }
    
    /**
     * 教员请假列表
     */
    public function coach_leave_list(){
        $params = $this->request->post();
        if(empty($params['space_id']) || empty($params['page']) || empty($params['starttime']) || empty($params['endtime'])){
            $this->error('参数缺失');
        }
        $space_id = $params['space_id'];
        $starttime = strtotime($params['starttime']);
        $endtime = strtotime($params['endtime'].'23:59:59');
        $where['coach.space_id'] = $space_id;
        $where['coachleave.createtime'] = ['between',[$starttime,$endtime]];
        $res= $this->coachleave->with(['coach'])->where($where)->select();
        $list = [];
        foreach($res as $k=>$v){
            $list[$k]['id'] = $v['id'];
            $list[$k]['name'] = $v['coach']['name'];
            $list[$k]['coach_id'] = $v['coach_id'];
            $list[$k]['starttime'] = $v['starttime'];
            $list[$k]['endtime'] = $v['endtime'];
            $list[$k]['leave_reason'] = $v['leave_reason'];
        }
        $this->success('返回成功',$list);
    }

    /**
     * 添加教员请假
     */
    public function coach_leave_add(){
        $params = $this->request->post();
        if(empty($params['space_id'])){
            $this->error('参数缺失');
        }
        $space_id = $params['space_id'];
        $type = $params['type'];
        $where['space_id'] = $space_id;
        $where['teach_state'] = 'yes';
        $coachlist= Db::name('coach')->where($where)->field(['coach_id','name'])->select();
        if($type == 1){
            if(empty($params['coach_id']) || empty($params['starttime']) || empty($params['endtime']) || !isset($params['leave_reason'])){
                $this->error('参数缺失');
            }
            unset($params['type']);
            $params['starttime'] = strtotime($params['starttime']);
            $params['endtime'] = strtotime($params['endtime']);
            $params['cooperation_id'] = $this->coach->where($where)->find()['cooperation_id'];
            $res = $this->coachleave->save($params);
            if($res){
                $this->success('返回成功');
            }
        }
        $this->success('返回成功',$coachlist);
    }

    /**
     * 编辑教员请假
     */
    public function coach_leave_edit(){
        $params = $this->request->post();
        if(empty($params['id'])){
            $this->error('参数缺失');
        }
        $id = $params['id'];
        $type = $params['type'];
        $where['coach.id'] = $id;
        $row= $this->coachleave->with(['coach'])->where($where)->find();
        if($type == 1){
            if(empty($params['starttime']) || empty($params['endtime'])  ){
                $this->error('参数缺失');
            }
            $starttime = $params['starttime'];
            $endtime = $params['endtime'];
            $leave_reason = $params['leave_reason'];
            $edit['starttime'] = $starttime;
            $edit['endtime'] = $endtime;
            $edit['leave_reason'] = $leave_reason;
            $res = $row->allowField(true)->save($edit);
            if($res){
                $this->success('返回成功');
            }else{
                $this->error('编辑失败');
            }
        }
        $this->success('返回成功',$row);
    }

    
}
