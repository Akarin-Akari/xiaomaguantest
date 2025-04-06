<?php


namespace app\api\controller\student;

use app\common\controller\Api;
use think\cache;

/**
 * 首页接口
 */
class GetSpace extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        $this->space = new \app\admin\model\Space;
        $this->common = new \app\api\controller\Common;
        parent ::_initialize();
    }


    
    /**
     * params ：lat 纬度 22.548166； lng 经度113.943555 len_type （1:m or 2:km);
     *获取场馆列表
    */
    public function spacelist(){
        $params = $this->request->post();

        if(empty($params['city']) || !array_key_exists('student_type',$params)|| !array_key_exists('cooperation_id',$params) || !array_key_exists('lat',$params)||!array_key_exists('lng',$params)){
            $this->error('参数缺失');
        }
        if($params){
            $lat = $params['lat'];
            $lng = $params['lng'];
            if($lat == '' || $lng == '' ){
                $lat= 22.54841;
                $lng= 113.94232;
            }
            $where['city'] = ['like','%'.$params['city'].'%'];
            $where['space_state'] = 'yes';
            if($params['student_type'] == 'student'){
                $where['cooperation_id'] = $params['cooperation_id'];
            }else{
                $where['cooperation_id'] = '';
            }
            if($params['city'] == '上海市'){
                unset($where['cooperation_id']);
            }
            $res= $this->space->where($where)->select();
            $list = [];
            foreach($res as $k=>$v){
                $address = explode('/',$v['city']);
                $list[$k]['id'] = $v['id'];
                $list[$k]['province'] = '';
                $list[$k]['city'] = '';
                if($v['city']){
                    $list[$k]['province'] = $address[0];
                    $list[$k]['city'] = $address[1];
                }
                $list[$k]['name'] = $v['space_name'];
                $list[$k]['region_info'] = $v['region_info'];
                $list[$k]['address'] = $v['address'];
                $list[$k]['space_state'] = $v['space_state'];
                $list[$k]['regionimage'] = $v['regionimage'];
                $list[$k]['images'] = $v['images'];
                if($lat !='' || $lng !=''){
                    $list[$k]['distance'] = $this->common->GetDistance($v['lat'],$v['lng'],$lat,$lng);
                }
                $list[$k]['lat'] = $v['lat'];
                $list[$k]['lng'] = $v['lng'];
                Cache::set('space_detail_'.$v['id'],$list[$k]);
                unset($reserve);
            }
            sort($list);
            $arr = array_column($list, 'distance');
            array_multisort($arr, SORT_ASC, $list);
            // if($lat !='' || $lng !=''){
            //     $list = $this->getlist($list);
            // }
            sort($list);
            $this->success('返回成功', $list);
        }else{
            $this->error('参数缺失');
        }
    }

    /**
     * 获取单个场馆详情
    */
    public function spacedetail(){
        $params = $this->request->post();
        $space_id = $params['space_id'];
        $res= $this->space->where('id',$space_id)->find();
        $this->success('返回成功', $res);
    }   
    
}
