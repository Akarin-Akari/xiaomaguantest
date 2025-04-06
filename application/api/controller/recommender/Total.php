<?php

namespace app\api\controller\recommender;

use app\common\controller\Api;

/**
 * 排行榜页面
 */
class Total extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $this->common = new \app\api\controller\Common;
    }

    /**
     * 个人排行
     */
    public function get_leaderboard()
    {
        $params = $this->request->post();
        $date = $this->common->get_time();
        $recommender_id = $params['recommender_id'];
        $day = strtotime($date['day']);
        $week = strtotime($date['week']);
        $month = strtotime($date['month']);

        $data['day_count'] = $this->get_student_count($recommender_id,$day);
        $data['week_count'] = $this->get_student_count($recommender_id,$week);
        $data['month_count'] = $this->get_student_count($recommender_id,$month);
        $data['total_count'] = $this->get_student_count($recommender_id,'');

        $space = $this->recommender->with(['admin'])->where('recommender.id',$recommender_id)->find();
        $data['space_name'] = $space['admin']['nickname'];
        $data['week_leaderboard'] = $this->get_space_student_count($space['space_id'],$week);
        $uID= array_column($data['week_leaderboard'],'id');
        $data['leaderboard'] = array_search($recommender_id, $uID) +1;
        $this->success('返回成功',$data);
    }

    /**
     * 场馆小组的排行榜
     */
    public function get_total_space_leaderboard()
    {
        $params = $this->request->post();
        $type = $params['type'];
        $date = $this->common->get_time();
        $recommender_id = $params['recommender_id'];
        if($type == 'day'){
            $starttime = strtotime($date['day']);
        }elseif($type == 'week'){
            $starttime = strtotime($date['week']);
        }elseif($type == 'month'){
            $starttime = strtotime($date['month']);
        }
        $endtime = time();
        $space = $this->recommender->with(['admin'])->where('recommender.id',$recommender_id)->find();
        $data['space_name'] = $space['admin']['nickname'];

        $recommender = $this->recommender->where('space_id',$space['space_id'])->limit(5)->select();
        $data = [];
        foreach($recommender as $k=>$v){
            $where['follower'] = $v['id'];
            if($type == 'total'){
                $where['registtime'] = ['<',$endtime];
            }else{
                $where['registtime'] = ['between',[$starttime,$endtime]];
            }
            $data[$k]['name'] =  $v['name'];
            $data[$k]['id'] =  $v['id'];
            $data[$k]['count'] = $this->student->where($where)->count();
        }
        array_multisort(array_column($data,'count'),SORT_DESC,$data);

        $uID= array_column($data,'id');
        $res['leaderboard'] = array_search($recommender_id, $uID) +1;
        $res['recommender'] = $data;
        $this->success('返回成功',$res);
    }

    /**
     * 按馆录入正式学员排行榜
     */
    public function get_space_student_count($space_id,$starttime)
    {
        $endtime = time();
        $recommender = $this->recommender->where('space_id',$space_id)->limit(5)->select();
        $data = [];
        foreach($recommender as $k=>$v){
            $where['follower'] = $v['id'];
            $where['registtime'] = ['between',[$starttime,$endtime]];
            $data[$k]['name'] =  $v['name'];
            $data[$k]['id'] =  $v['id'];
            $data[$k]['count'] = $this->student->where($where)->count();
        }
        array_multisort(array_column($data,'count'),SORT_DESC,$data);
        return $data;
    }
    
    /**
     * 个人销售录入正式学员统计
     */
    public function get_student_count($recommender_id,$starttime)
    {
        $endtime = time();
        $where['registtime'] = ['between',[$starttime,$endtime]];
        if(!$starttime){
            $where['registtime'] = ['<=',$endtime];
        }
        $where['follower'] = $recommender_id;
        $count = $this->student->where($where)->count();
        return $count;
    }


}