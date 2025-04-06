<?php

namespace app\admin\controller\statisticai;

use app\common\controller\Backend;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Trainstatisticai extends Backend
{
    
    /**
     * Trainstatisticai模型对象
     * @var \app\admin\model\Trainstatisticai
     */
    protected $model = null;
    protected $studyprocessai = null;
    protected $place = null;


    protected $noNeedRight = ['detail1','detail2'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Trainstatisticai;
        $this->place = new \app\admin\model\Place;
        $this->studyprocessai = new \app\admin\model\Studyprocessai;

    }

    public function import()
    {
        parent::import();
    }

    /**
     * 查看
     */
    public function index()
    {
        //当前是否为关联查询
        $this->relationSearch = true;
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $list = $this->model
                    ->with(['admin','space','student'])
                    ->where($where)
                    ->order($sort, $order)
                    ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id','total_order','keer_hege','keer_leiji','kesan_hege','kesan_leiji','updatetime','daletetime']);
                $row->visible(['admin']);
				$row->getRelation('admin')->visible(['nickname']);
				$row->visible(['space']);
				$row->getRelation('space')->visible(['space_name']);
				$row->visible(['student']);
				$row->getRelation('student')->visible(['name']);
            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }


    public function detail1($ids)
    {
        $starttime = \fast\Date::unixtime('day',0);
        $row = $this->model->get($ids,'student');
        $keer_study_time = $this->studyprocessai->where(['stu_id'=>$row['stu_id'],'cooperation_id'=>$row['cooperation_id'],'createtime'=>['<',$starttime]])->whereNull('place_id')->select();
        $arr = [];
        $arr['xinshou'] = 0;
        $arr['jichubj'] = 0;
        $arr['fangxiangpan'] = 0;
        $arr['qiting'] = 0;
        $arr['daisu'] = 0;
        $arr['chegan'] = 0;
        $arr['cefangwei'] = 0;
        $arr['daoche'] = 0;
        $arr['quxian'] = 0;
        $arr['zhijiaowan'] = 0;
        $arr['banpo'] = 0;
        $arr['ziyou'] = 0;
        $arr['kaoshi'] = 0;
        $arr['pass'] = 0;
        $arr['gailv'] = 0;
        // $keys = array_column($keer_study_time->toArray(),'process_name');
        // $keys = array_unique($keys);
        // var_dump($keys);exit;
        $num = 0;
        foreach($keer_study_time as $v){
            if($v['process_name'] == 'kaoshi'){
                $arr['kaoshi'] +=1;
                if($v['status'] == 1){
                    $arr['pass'] +=1;
                }
 
            }
            if($v['process_name'] == 'kaoshi'){
                continue;
            }
            $arr[$v['process_name']] += $v['study_time'];
        }
        if($arr['kaoshi']!==0){
            $arr['gailv'] = (round($arr['pass']/$arr['kaoshi'],3)*100).'%';
        }else{
            $arr['gailv'] = 0;
        }
        foreach($arr as $k=>$v){
            if(!in_array($k,['pass','gailv','kaoshi'])){
                $arr[$k] = round($v/3600,3);
                $num += $v;
            }
        }
        $num = round($num/3600,3);
        $this->view->assign("row", $arr);
        $this->view->assign("num", $num);
        return $this->view->fetch();
    }

    public function detail2($ids)
    {
        $starttime = \fast\Date::unixtime('day',0);
        $row = $this->model->get($ids,'student');
        $keer_study_time = $this->studyprocessai->where(['stu_id'=>$row['stu_id'],'place_id'=>['<>','NULL'],'cooperation_id'=>$row['cooperation_id'],'createtime'=>['<',$starttime]])->select();
        $arr = [];
        $process_name = array_column($keer_study_time->toArray(),'process_name');
        $process_name = array_unique($process_name);

        $place = $this->place->select();
        // var_dump($place->toArray());exit;

        $place_ids = array_column($place->toArray(),'id');

        foreach($keer_study_time as $v){

            $key = array_search($v['place_id'],$place_ids);

            // var_dump();exit;
            // var_dump($v->toArray());exit;
            $arr[$place[$key]['place_name']][] = $v->toArray();
            // var_dump($v['process_name']);
            // var_dump($arr[$v['process_name']]+$v['study_time']);
        }
        $arr_new = [];
        $list = [];

        foreach($arr as $k=>$v){
            if(!array_key_exists($k,$arr_new)){
                $arr_new[$k] = [];
            }
            foreach($v as $vv){
                if(!array_key_exists($vv['process_name'],$arr_new[$k])){
                    $arr_new[$k][$vv['process_name']] = 0;
                }
                $arr_new[$k][$vv['process_name']] += $vv['study_time'];
            }
        }

        $num = 0;
        foreach($arr_new as $k=>$v){
            $info['kaochangname'] = $k;
            foreach($process_name as $vv){
                if(in_array($vv,array_keys($arr_new[$k]))){
                    $info[$vv] = round($arr_new[$k][$vv]/3600,3);
                    $num += $arr_new[$k][$vv];
                }else{
                    $info[$vv]  = 0;
                }
            }
            $list[] = $info;
        }
        // var_dump($list,$process_name,$num);exit;

        array_unshift($process_name,'考场名称');
        $this->view->assign("row", $list);
        $this->view->assign("num", $num);
        $this->view->assign("process_name", $process_name);
        return $this->view->fetch();
    }
}
