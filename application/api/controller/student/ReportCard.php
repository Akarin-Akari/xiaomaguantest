<?php

namespace app\api\controller\student;

use app\common\controller\Api;
use think\cache;
use think\Db;

/**
 * 首页接口
 */
class ReportCard extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    protected $reportcard = null;
    protected $common = null;

    public function _initialize()
    {
        parent ::_initialize();
        $this->reportcard = new \app\admin\model\Reportcard;
        $this->common = new \app\api\controller\Common;
    }

    public function report_card()
    {
        $params = $this->request->post();
        $params['stu_id'] = 'CSN20210720192636229322';
        if(empty($params['stu_id'])){
            $this->error('参数缺失');
        }
        $reportcard = $this->reportcard->with(['machinecar','coach','admin','space'])->where(['stu_id'=>$params['stu_id']])->select();
        $data = [];
        foreach($reportcard as $k=>$v){
            $data[$k]['stu_id'] = $v['stu_id'];
            $data[$k]['kaochang'] = $v['kaochang'];
            $data[$k]['createtime'] = $v['createtime_text'];
            $data[$k]['id'] = $v['id'];
            $data[$k]['score'] = $v['score'];
            $data[$k]['machine_code'] = $v['machinecar']['machine_code'];
            $data[$k]['coach'] = $v['coach']['name'];
        }
        $this->success('返回成功',$data);
    }

    public function report_card_detail()
    {
        $params = $this->request->post();
        $params['report_card_id'] = '14';
        if(empty($params['report_card_id'])){
            $this->error('参数缺失');
        }
        $where['reportcard.id'] = $params['report_card_id'];
        $reportcard = $this->reportcard->with(['machinecar','coach','admin','space'])->where($where)->find();
        $data['stu_id'] = $reportcard['stu_id'];
        $data['ordernumber'] = $reportcard['ordernumber'];
        $data['kaochang'] = $reportcard['kaochang'];
        $data['createtime'] = $reportcard['createtime'];
        $data['score'] = $reportcard['score'];
        $data['machine_code'] = $reportcard['machinecar']['machine_code'];
        $data['coach'] = $reportcard['coach']['name'];
        $data['details'] = Db::name('deduct_points')->where(['report_card_id'=>$params['report_card_id']])->select();
        $this->success('返回成功',$data);
    }
}
