<?php 
namespace app\api\controller\test;
use app\common\controller\Api;


/**
 * 首页接口
 */
class Ceshipay extends Api
{


    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    public function test()
    {
        $this->pay = new \app\api\controller\pay\Upay;
        $terminal_sn = '100047030026807752';
        $terminal_key = '6fe05729c46e7a7e99f771260eb2e097';
        $res = $this->pay->query($terminal_sn, $terminal_key);
        var_dump($res);
    }

}