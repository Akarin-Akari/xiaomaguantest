<?php

namespace app\api\controller\student;

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
    protected $student = null;


    public function _initialize()
    {
        $this->card = new \app\admin\model\Card;
        $this->student = new \app\admin\model\Student;
        
    }

    public function index() {
        $params = $this->request->post();
        if(empty($params['stu_id']) || empty($params['page']) ){
            $this->error('参数缺失');
        }
        $page = $params['page'];
        $pagenum = 10;
        $numl = $pagenum*($page-1);
        $res = [];

        $where['card.stu_id'] = $params['stu_id'];
        $res = $this->card->with(['coach','student','recommender'])->where($where)->limit($numl,$pagenum)->order('type asc')->select();

        $data = $this->getdata($res);
        if($res){
            $this->success('返回成功',$data);
        }
        $this->success('无信息',$res);
    }

    
    function getdata($res){
        $list = [];
        foreach($res as $k=>$v){
            $list[$k]['coach_name'] = $v['coach']['name'];
            $list[$k]['recommender'] = $v['recommender']['name'];
            $list[$k]['coupon_id'] = $v['coupon_id'];
            $list[$k]['coupon_code_path'] = $v['coupon_code_path'];
            $list[$k]['type'] = $v['type'];
            if($v['verifytime']){
                $list[$k]['verifytime'] = date('Y-m-d H:i:s',$v['verifytime']);
            }else{
                $list[$k]['verifytime'] = null;
            }
            
        }
        return $list;
    }

}
