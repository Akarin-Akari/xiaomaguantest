<?php

namespace app\api\controller\recommender;

use app\common\controller\Api;
use think\cache;

/**
 * 开机流程所需接口
 */
class Ticket extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    protected $card = null;

    public function _initialize()
    {
        $this->card = new \app\admin\model\Card;

    }

    public function index()
    {
        $params = $this->request->post();
        if(empty($params['recommender_id'])|| empty($params['page'])){
            $this->error('参数缺失');
        }
        $page = $params['page'];
        $pagenum = 10;
        $numl = $pagenum*($page-1);

        $where['verify_id'] = $params['recommender_id'];
        $res = $this->card->with(['coach','student'])->where($where)->limit($numl,$pagenum)->select();
        $list = $this->getdata($res);
        if($res){
            $this->success('返回成功',$list);
        }

    }

    
    function getdata($res){
        $list = [];
        foreach($res as $k=>$v){
            $list[$k]['coach_name'] = $v['coach']['name'];
            $list[$k]['stu_name'] = $v['student']['name'];
            $list[$k]['coupon_id'] = $v['coupon_id'];
            $list[$k]['verifytime'] = date('Y-m-d H:i:s',$v['verifytime']);
        }
        return $list;
    }

    public function verify() {
        $params = $this->request->post();
        if(empty($params['coupon_id']) || empty($params['verify_id'])){
            $this->error('参数缺失');
        }
        $where['coupon_id'] = $params['coupon_id'];
        $res = $this->card->where($where)->find();
        if($res){
            if($res['verify_id'] || $res['type']== 1 || $res['verifytime']){
                $this->error('当前二维码已核销，核销时间为:'.date('Y-m-d H:i:s',$res['verifytime']));
            }
        }

        $update['verify_id'] = $params['verify_id'];
        $update['verifytime'] =  time();
        $update['type'] = 1;
        $up_res = $this->card->where($where)->update($update);
        
        if($up_res){
            $this->success('返回成功');
        }
    }

}
