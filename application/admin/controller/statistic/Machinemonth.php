<?php

namespace app\admin\controller\statistic;

use app\common\controller\Backend;
use think\Db;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Machinemonth extends Backend
{

    /**
     * Machinemonth模型对象
     * @var \app\admin\model\Machinemonth
     */
    protected $model = null;

    protected $noNeedRight = ['detail1', 'detail2'];

    protected $space = null;
    protected $space_list = null;
    protected $cooperation_list = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Machinemonth;
        $this->space = new \app\admin\model\Space;
        $this->space_list = $_SESSION['think']['admin']['space_list'];
        $this->cooperation_list = $_SESSION['think']['admin']['cooperation_list'];
        $this->view->assign("carTypeList", $this->model->getCarTypeList());
        $this->view->assign("stateList", $this->model->getStateList());
        $this->view->assign("collorList", $this->model->getCollorList());
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
            $filter = $this->request->request("filter");
            $filter = (array)json_decode($filter, true);
            // var_dump($this->space_list);exit;
            $where_cooperation['machinemonth.cooperation_id'] = ['in', $this->cooperation_list];
            $list = $this->model
                ->with(['admin', 'space'])
                ->where($where_cooperation)
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id', 'machine_code', 'collor']);
                $row->visible(['admin']);
                $row->getRelation('admin')->visible(['nickname']);
                $row->visible(['space']);
                $row->getRelation('space')->visible(['space_name']);
                // $row['business'] = '8h';
                // var_dump($row->toArray());exit;
            }

            $result = array("total" => $list->total(), "rows" => $list->items());


            return json($result);
        }
        return $this->view->fetch();
    }

    public function detail1($ids)
    {
        $machine = $this->model->get($ids, 'space');
        $month = \fast\Date::unixtime('day', -1);
        $day = (int)date('d', $month);
        $year = date('Y-m', $month);
        $space_end_time = $machine['space']['endtimes'];

        if ($machine['space']['endtimes'] == '00:00:00') {
            $space_end_time = strtotime(date('Y-m-d ', strtotime('+1 day')));
        } else {
            $space_end_time = strtotime(date('Y-m-d ', time()) . $space_end_time);
        }

        $machine_time = ($space_end_time - strtotime(date('Y-m-d' . $machine['space']['starttimes']))) / (60 * 60 * $machine['space']['order_length']);
        $list = [];
        for ($i = 1; $i <= $day; $i++) {
            $starttime = strtotime($year . '-' . $i . ' 00:00:00');
            $endtime = strtotime($year . '-' . ($i + 1) . ' 00:00:00') - 1;
            $where['machine_id'] = $ids;
            $where['order_status'] = 'finished';
            $where['reserve_starttime'] = ['between', [$starttime, $endtime]];
            $count1 = Db::name('order')->where($where)->count();
            $count2 = Db::name('temporary_order')->where($where)->count();
            $count = $count1 + $count2;

            $arr['date'] = date('Y年m月d日', $starttime);
            $arr['count'] = $count;
            $arr['ratio'] = round($count / $machine_time * 100, 2);
            // $arr['count'] = $count;
            array_push($list, $arr);
        };
        $arr_count = array_column($list, 'ratio');
        $sum = array_sum($arr_count);
        $ratio = round($sum / $day, 2);

        $this->view->assign("ratio", $ratio);
        $this->view->assign("row", $machine);
        $this->view->assign("list", $list);
        return $this->view->fetch();
    }

    public function detail2($ids)
    {
        $machine = $this->model->get($ids, 'space');
        $month = \fast\Date::unixtime('month', 0);
        $month_day = (int)date('m', $month);
        $year = date('Y', $month);
        $machine_time = (strtotime(date('Y-m-d' . $machine['space']['endtimes'])) - strtotime(date('Y-m-d' . $machine['space']['starttimes']))) / (60 * 60 * $machine['space']['order_length']);
        $list = [];
        for ($i = 1; $i <= $month_day; $i++) {
            $starttime = strtotime($year . '-' . $i . '-01');
            if ($i == $month_day) {
                $endtime = \fast\Date::unixtime('day', 0) - 1;
            } else {
                $endtime = strtotime($year . '-' . ($i + 1) . '-01') - 1;
            }
            $day = (int)date('d', $endtime) - (int)date('d', $starttime);
            $where['machine_id'] = $ids;
            $where['order_status'] = 'finished';
            $where['reserve_starttime'] = ['between', [$starttime, $endtime]];
            $count1 = Db::name('order')->where($where)->count();
            $count2 = Db::name('temporary_order')->where($where)->count();
            $count = $count1 + $count2;
            $arr['date'] = date('Y年m月', $starttime);
            $arr['count'] = $count;
            $arr['ratio'] = round($count / ($day * $machine_time) * 100, 2);
            // $arr['count'] = $count;
            array_push($list, $arr);
        };
        $arr_count = array_column($list, 'ratio');
        $sum = array_sum($arr_count);
        $ratio = round($sum / $month_day, 2);
        $this->view->assign("ratio", $ratio);
        $this->view->assign("list", $list);
        $this->view->assign("row", $machine);

        return $this->view->fetch();
    }
}
