<?php

namespace app\api\controller\jishi;
use app\api\XsApi;

use app\common\controller\Api;

/**
 * 开机流程所需接口
 */
class BeiDou extends XsApi
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent ::_initialize();

    }

    public function get_info(){
        $params = $this->request->post();
        $params['machine_code'] = '10020';
        if(empty($params['machine_code'])){
            $this->error('参数缺失');
        }
        $data['lng'] = '114';
        $data['lat'] = '27';
        
        $this->success('返回成功',$data);
    }
}
