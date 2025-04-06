<?php


namespace app\api\controller\cooperation;

use app\common\controller\Api;
use think\Cache;

/**
 * 充值列表
 */
class Recharge extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent ::_initialize();
        $this->recharge = new \app\admin\model\Recharge;
        $this->common = new \app\api\controller\Common;
    }

    /**
     * 获取充值记录
     */
    public function get_recharge(){
        $params = $this->request->post();
        // $params['space_id'] = 5;
        // $params['page'] = 1;
        if(empty($params['space_id']) || empty($params['page'])){
            $this->error('参数缺失');
        }
        // Cache::set('recharge_test',$params,60*10);

        $space_id = $params['space_id'];
        $page = $params['page'];
        $pagenum = 20;
        $numl = $pagenum*($page-1);
        $where['space_id'] = $space_id;
        $data = $this->recharge->where($where)->limit($numl,$pagenum)->order('id desc')->select();
        $res = [];
        foreach($data as $k=>$v){
            $res[$k]['payment_number'] = $v['payment_number'];
            $res[$k]['enddate'] = $v['enddate'];
            $res[$k]['reason'] = $v['reason'];
            $res[$k]['paytime'] = $v['paytime'];
            $res[$k]['createtime'] = $v['createtime'];
        }
        $this->success('返回成功',$res);
    }

    // public function deduct()
    // {
    //     $params = $this->request->post();
    //     if(empty($params['space_id']) || empty($params['page']) || empty($params['starttime'] || empty($params['endtime']))){
    //         $this->error('参数缺失');
    //     }
    //     $space_id = $params['space_id'];
    //     $page = $params['page'];
    //     $starttime = strtotime($params['starttime'].'00:00:00');
    //     $endtime = strtotime($params['endtime'].'23:59:59');
    //     $pagenum = 20;
    //     $numl = $pagenum*($page-1);
    //     $where['space_id'] = $space_id;
    //     $where['orderlast.createtime'] = ['between',[$starttime,$endtime]];
    //     $data = [];
    //     $res = $this->orderlast->with(['space'])->where($where)->limit($numl,$pagenum)->order('id desc')->select();
    //     foreach($res as $k=>$v){
    //         $data[$k]['ordernumber'] = $v['ordernumber'];
    //         $data[$k]['space_name'] = $v['space']['space_name'];
    //         $data[$k]['last_money'] = $v['last_money'];
    //         $data[$k]['createtime'] = $v['createtime'];
    //     }
    //     $this->success('返回成功',$data);
    // }
}