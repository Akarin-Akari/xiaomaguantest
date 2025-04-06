<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\common\exception\UploadException;
use app\common\library\Upload;
use fast\Random;
use think\addons\Service;
use think\Cache;
use think\Config;
use think\Db;
use think\Lang;
use think\Response;
use think\Validate;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as Xlsxn;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\Loader;

/**
 * Ajax异步请求接口
 * @internal
 */
class Ajax extends Backend
{

    protected $noNeedLogin = ['lang'];
    protected $noNeedRight = ['*'];
    protected $layout = '';
    protected $space =  null;

    public function _initialize()
    {
        parent::_initialize();
        $this->space = new \app\admin\model\Space;

        //设置过滤方法
        $this->request->filter(['trim', 'strip_tags', 'htmlspecialchars']);
    }

    /**
     * 加载语言包
     */
    public function lang()
    {
        $this->request->get(['callback' => 'define']);
        $header = ['Content-Type' => 'application/javascript'];
        if (!config('app_debug')) {
            $offset = 30 * 60 * 60 * 24; // 缓存一个月
            $header['Cache-Control'] = 'public';
            $header['Pragma'] = 'cache';
            $header['Expires'] = gmdate("D, d M Y H:i:s", time() + $offset) . " GMT";
        }

        $controllername = input("controllername");
        //默认只加载了控制器对应的语言名，你还根据控制器名来加载额外的语言包
        $this->loadlang($controllername);
        return jsonp(Lang::get(), 200, $header, ['json_encode_param' => JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE]);
    }

    /**
     * 上传文件
     */
    public function upload()
    {
        Config::set('default_return_type', 'json');

        //必须还原upload配置,否则分片及cdnurl函数计算错误
        Config::load(APP_PATH . 'extra/upload.php', 'upload');

        $chunkid = $this->request->post("chunkid");
        if ($chunkid) {
            if (!Config::get('upload.chunking')) {
                $this->error(__('Chunk file disabled'));
            }
            $action = $this->request->post("action");
            $chunkindex = $this->request->post("chunkindex/d");
            $chunkcount = $this->request->post("chunkcount/d");
            $filename = $this->request->post("filename");
            $method = $this->request->method(true);
            if ($action == 'merge') {
                $attachment = null;
                //合并分片文件
                try {
                    $upload = new Upload();
                    $attachment = $upload->merge($chunkid, $chunkcount, $filename);
                } catch (UploadException $e) {
                    $this->error($e->getMessage());
                }
                $this->success(__('Uploaded successful'), '', ['url' => $attachment->url, 'fullurl' => cdnurl($attachment->url, true)]);
            } elseif ($method == 'clean') {
                //删除冗余的分片文件
                try {
                    $upload = new Upload();
                    $upload->clean($chunkid);
                } catch (UploadException $e) {
                    $this->error($e->getMessage());
                }
                $this->success();
            } else {
                //上传分片文件
                //默认普通上传文件
                $file = $this->request->file('file');
                try {
                    $upload = new Upload($file);
                    $upload->chunk($chunkid, $chunkindex, $chunkcount);
                } catch (UploadException $e) {
                    $this->error($e->getMessage());
                }
                $this->success();
            }
        } else {
            $attachment = null;
            //默认普通上传文件
            $file = $this->request->file('file');
            try {
                $upload = new Upload($file);
                $attachment = $upload->upload();
            } catch (UploadException $e) {
                $this->error($e->getMessage());
            }

            $this->success(__('Uploaded successful'), '', ['url' => $attachment->url, 'fullurl' => cdnurl($attachment->url, true)]);
        }
    }

    /**
     * 通用排序
     */
    public function weigh()
    {
        //排序的数组
        $ids = $this->request->post("ids");
        //拖动的记录ID
        $changeid = $this->request->post("changeid");
        //操作字段
        $field = $this->request->post("field");
        //操作的数据表
        $table = $this->request->post("table");
        if (!Validate::is($table, "alphaDash")) {
            $this->error();
        }
        //主键
        $pk = $this->request->post("pk");
        //排序的方式
        $orderway = strtolower($this->request->post("orderway", ""));
        $orderway = $orderway == 'asc' ? 'ASC' : 'DESC';
        $sour = $weighdata = [];
        $ids = explode(',', $ids);
        $prikey = $pk && preg_match("/^[a-z0-9\-_]+$/i", $pk) ? $pk : (Db::name($table)->getPk() ?: 'id');
        $pid = $this->request->post("pid", "");
        //限制更新的字段
        $field = in_array($field, ['weigh']) ? $field : 'weigh';

        // 如果设定了pid的值,此时只匹配满足条件的ID,其它忽略
        if ($pid !== '') {
            $hasids = [];
            $list = Db::name($table)->where($prikey, 'in', $ids)->where('pid', 'in', $pid)->field("{$prikey},pid")->select();
            foreach ($list as $k => $v) {
                $hasids[] = $v[$prikey];
            }
            $ids = array_values(array_intersect($ids, $hasids));
        }

        $list = Db::name($table)->field("$prikey,$field")->where($prikey, 'in', $ids)->order($field, $orderway)->select();
        foreach ($list as $k => $v) {
            $sour[] = $v[$prikey];
            $weighdata[$v[$prikey]] = $v[$field];
        }
        $position = array_search($changeid, $ids);
        $desc_id = isset($sour[$position]) ? $sour[$position] : end($sour);    //移动到目标的ID值,取出所处改变前位置的值
        $sour_id = $changeid;
        $weighids = array();
        $temp = array_values(array_diff_assoc($ids, $sour));
        foreach ($temp as $m => $n) {
            if ($n == $sour_id) {
                $offset = $desc_id;
            } else {
                if ($sour_id == $temp[0]) {
                    $offset = isset($temp[$m + 1]) ? $temp[$m + 1] : $sour_id;
                } else {
                    $offset = isset($temp[$m - 1]) ? $temp[$m - 1] : $sour_id;
                }
            }
            if (!isset($weighdata[$offset])) {
                continue;
            }
            $weighids[$n] = $weighdata[$offset];
            Db::name($table)->where($prikey, $n)->update([$field => $weighdata[$offset]]);
        }
        $this->success();
    }


    /**
     * 导出学员数据
     */
    public function export()
    {
        if ($this->request->isPost()) {
            set_time_limit(0);

            $search = $this->request->post('search');
            $ids = $this->request->post('ids');
            $filter = $this->request->post('filter');
            $op = $this->request->post('op');

            if(strpos('car_type',$op)){
                str_replace('car_type','student.car_type',$op);
            }

            if(strpos('car_type',$filter)){
                str_replace('car_type','student.car_type',$filter);
            }

            $student = new \app\admin\model\Student;
            //$excel = new PHPExcel();
            $spreadsheet = new Spreadsheet();

            $spreadsheet->getProperties()
                ->setCreator("FastAdmin")
                ->setLastModifiedBy("FastAdmin")
                ->setTitle("标题")
                ->setSubject("Subject");
            $spreadsheet->getDefaultStyle()->getFont()->setName('Microsoft Yahei');
            $spreadsheet->getDefaultStyle()->getFont()->setSize(12);

            $worksheet = $spreadsheet->setActiveSheetIndex(0);
            
            
            // $whereIds = $ids == 'all' ? '1=1' : ['id' => ['in', explode(',', $ids)]];
            $this->request->get(['search' => $search, 'ids' => $ids, 'filter' => $filter, 'op' => $op]);
            list($where, $sort, $order, $offset, $limit) = $this->buildparams_new();
            

            $line = 1;

            //设置过滤方法
            $this->request->filter(['strip_tags']);

            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $wheror = [];
            list($where, $sort, $order, $offset, $limit) = $this->buildparams_new();
            if(!strpos($op,'admin.nickname')){
                $wheror['student.cooperation_id'] = ['in', $_SESSION['think']['admin']['cooperation_list']];
            }

            if(!strpos($op,'space.space_name')){
                $wheror['student.space_id'] = ['in', $_SESSION['think']['admin']['space_list']];
            }


          
            $total = $student
                ->where($where)
                ->where($wheror)
                ->with(['space','admin','course_log','nation']) 
                // ->order($sort,$order)
                ->count();

            $list = $student
                ->where($where)
                ->where($wheror)
                ->with(['space','admin','course_log','nation']) 
                // ->order($sort,$order)
                ->limit($offset, $limit)
                ->select();
        

            $list = collection($list)->toArray();

            $result = array("total" => $total, "rows" => $list);
            
            
            $first = array_keys($list[0]);

            if(!file_exists( ROOT_PATH.'public/uploads/'.date('Ymd').'/'))
                mkdir(ROOT_PATH.'public/uploads/'.date('Ymd',time()).'/',0777,true);
            foreach ($first as $index => $item) {
                $worksheet->setCellValueByColumnAndRow($index, 1, __($item));
            }
            
            // $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(__DIR__ . '/muban/test.xls');  //读取模板
            
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $worksheet = $spreadsheet->getActiveSheet();     //指向激活的工作表
            $worksheet->setTitle('模板测试标题');

            $cartype = [];
            $course = [];
            $worksheet->getCell('A1')->setValue('ID');    //id
            $worksheet->getCell('B1')->setValue('学员编号');    //id
            $worksheet->getCell('C1')->setValue('姓名');    //id
            $worksheet->getCell('D1')->setValue('性别');    //id
            $worksheet->getCell('E1')->setValue('民族');    //id
            $worksheet->getCell('F1')->setValue('手机号');    //id
            $worksheet->getCell('G1')->setValue('身份证');    //id
            $worksheet->getCell('H1')->setValue('课程名称');    //id
            $worksheet->getCell('I1')->setValue('缴费金额');    //id
            $worksheet->getCell('J1')->setValue('报名时间');    //id
            $worksheet->getCell('K1')->setValue('备注');    //id

            for($i=0;$i<$total;++$i){
                // if(){

                // }
                //向模板表中写入数据
                // $worksheet->setCellValue('A'.($i+2), '模板测试内容');   //送入A1的内容
                $worksheet->getCell('A'.($i+2))->setValue($result['rows'][$i]['id']);    //id
                $worksheet->getCell('B'.($i+2))->setValue($result['rows'][$i]['stu_id']);  //学员编号
                $worksheet->getCell('C'.($i+2))->setValue($result['rows'][$i]['name']);  //姓名
                $worksheet->getCell('D'.($i+2))->setValue(__($result['rows'][$i]['sex_text']));  //性别
                $worksheet->getCell('E'.($i+2))->setValue($result['rows'][$i]['nation']['name']);  //民族
                $worksheet->getCell('F'.($i+2))->setValue($result['rows'][$i]['phone']);  //手机号
                $worksheet->getCell('G'.($i+2))->setValue($result['rows'][$i]['idcard']);  //身份证
                $worksheet->getCell('H'.($i+2))->setValue($result['rows'][$i]['course_log']['course']);  //课程名称
                $worksheet->getCell('I'.($i+2))->setValue($result['rows'][$i]['tuition']);  //缴费金额
                $worksheet->getCell('J'.($i+2))->setValue($result['rows'][$i]['registtime_text']);  //报名时间
                $worksheet->getCell('K'.($i+2))->setValue($result['rows'][$i]['remarks']);  //备注
                // $worksheet->getCell('E'.($i+2))->setValue($result['rows'][$i]['money']);  //金额
                if(!array_key_exists($result['rows'][$i]['car_type_text'],$cartype)){
                    $cartype[ __($result['rows'][$i]['car_type_text'])] = 1;
                }else{
                    $cartype[ __($result['rows'][$i]['car_type_text'])] = ($cartype[ __($result['rows'][$i]['car_type_text'])] +1);
                }

                if(!array_key_exists($result['rows'][$i]['course_log']['course'],$course)){
                    $course[$result['rows'][$i]['course_log']['course']] = 1;
                }else{
                    $course[$result['rows'][$i]['course_log']['course']] = ($course[$result['rows'][$i]['course_log']['course']] +1);
                }

                
            }
            $cartype_str = '';
            foreach($cartype as $k=>$v){
                $cartype_str = $cartype_str.$k.':'.$v.'人,';
            }


            $course_str = '';
            foreach($course as $k=>$v){
                $course_str = $course_str.$k.':'.$v.'人,';
            }
        
            $worksheet->getCell('B'.($total+3))->setValue('总人数：'.$total.'人,'.$cartype_str); 
            $worksheet->getCell('B'.($total+4))->setValue('总人数：'.$total.'人,'.$course_str); 

            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');

            $filename = md5(time().'.xlsx').'.xlsx';
            
            $writer = new Xlsxn($spreadsheet);
            $host = $_SERVER['HTTP_HOST'];
            // var_dump(ROOT_PATH.'public/uploads/'.$filename);
            // var_dump($host.'/uploads/'.$filename);exit;
            $writer->save(ROOT_PATH.'public/uploads/'.date('Ymd').'/'.$filename);
            
            // header('location:https://'.$host.'/uploads/'.date('Ymd').'/'.$filename);
            // var_dump(ROOT_PATH.'public/uploads/'.date('Ymd').'/'.$filename);exit;
            json_encode(array('error'=>false, 'export_path'=>'download_excel/' . ROOT_PATH.'public/uploads/'.date('Ymd').'/'.$filename));


            header ( 'Content-Type: application/vnd.ms-excel' ); 
            header ( 'Content-Disposition: attachment;filename="'.ROOT_PATH.'public/uploads/'.date('Ymd').'/'.$filename); 
            header ( 'Cache-Control: max-age=0' ); 
            $writer -> save ( 'php://output' ); 
            unlink(ROOT_PATH.'public/uploads/'.date('Ymd').'/'.$filename);
        }
    }

    public function buildparams_new()
    {
        $searchfields = 'id';
        $relationSearch = false;
        $search = $this->request->get("search", '');
        $filter = $this->request->get("filter", '');
        $op = $this->request->get("op", '', 'trim');
        $sort = $this->request->get("sort", !empty($this->model) && $this->model->getPk() ? $this->model->getPk() : 'id');
        $order = $this->request->get("order", "DESC");
        $offset = $this->request->get("offset/d", 0);
        $limit = $this->request->get("limit/d", 999999);

        // var_dump($search,$op,$sort,$order,$offset,$filter );exit;
        //新增自动计算页码
        $page = $limit ? intval($offset / $limit) + 1 : 1;
        if ($this->request->has("page")) {
            $page = $this->request->get("page/d", 1);
        }
        
        $this->request->get([config('paginate.var_page') => $page]);

        $filter = (array)json_decode(html_entity_decode(htmlspecialchars_decode($filter)), true);
        $op = (array)json_decode(html_entity_decode(htmlspecialchars_decode($op)), true);

        $filter = $filter ? $filter : [];
        $where = [];
        $alias = [];
        $bind = [];
        $name = '';
        $aliasName = 'student.';

        $sortArr = explode(',', $sort);
        foreach ($sortArr as $index => & $item) {
            $item = stripos($item, ".") === false ? $aliasName . trim($item) : $item;
        }
        unset($item);
        $sort = implode(',', $sortArr);
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            $where[] = [$aliasName . $this->dataLimitField, 'in', $adminIds];
        }

        if ($search) {
            $searcharr = is_array($searchfields) ? $searchfields : explode(',', $searchfields);
            foreach ($searcharr as $k => &$v) {
                $v = stripos($v, ".") === false ? $aliasName . $v : $v;
            }
            unset($v);
            $where[] = [implode("|", $searcharr), "LIKE", "%{$search}%"];
        }
        $index = 0;
        foreach ($filter as $k => $v) {
            if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $k)) {
                continue;
            }
            $sym = isset($op[$k]) ? $op[$k] : '=';
            if (stripos($k, ".") === false) {
                $k = $aliasName . $k;
            }
            $v = !is_array($v) ? trim($v) : $v;
            $sym = strtoupper(isset($op[$k]) ? $op[$k] : $sym);
            //null和空字符串特殊处理
            if (!is_array($v)) {
                if (in_array(strtoupper($v), ['NULL', 'NOT NULL'])) {
                    $sym = strtoupper($v);
                }
                if (in_array($v, ['""', "''"])) {
                    $v = '';
                    $sym = '=';
                }
            }

            switch ($sym) {
                case '=':
                case '<>':
                    $where[] = [$k, $sym, (string)$v];
                    break;
                case 'LIKE':
                case 'NOT LIKE':
                case 'LIKE %...%':
                case 'NOT LIKE %...%':
                    $where[] = [$k, trim(str_replace('%...%', '', $sym)), "%{$v}%"];
                    break;
                case '>':
                case '>=':
                case '<':
                case '<=':
                    $where[] = [$k, $sym, intval($v)];
                    break;
                case 'FINDIN':
                case 'FINDINSET':
                case 'FIND_IN_SET':
                    $v = is_array($v) ? $v : explode(',', str_replace(' ', ',', $v));
                    $findArr = array_values($v);
                    foreach ($findArr as $idx => $item) {
                        $bindName = "item_" . $index . "_" . $idx;
                        $bind[$bindName] = $item;
                        $where[] = "FIND_IN_SET(:{$bindName}, `" . str_replace('.', '`.`', $k) . "`)";
                    }
                    break;
                case 'IN':
                case 'IN(...)':
                case 'NOT IN':
                case 'NOT IN(...)':
                    $where[] = [$k, str_replace('(...)', '', $sym), is_array($v) ? $v : explode(',', $v)];
                    break;
                case 'BETWEEN':
                case 'NOT BETWEEN':
                    $arr = array_slice(explode(',', $v), 0, 2);
                    if (stripos($v, ',') === false || !array_filter($arr)) {
                        continue 2;
                    }
                    //当出现一边为空时改变操作符
                    if ($arr[0] === '') {
                        $sym = $sym == 'BETWEEN' ? '<=' : '>';
                        $arr = $arr[1];
                    } elseif ($arr[1] === '') {
                        $sym = $sym == 'BETWEEN' ? '>=' : '<';
                        $arr = $arr[0];
                    }
                    $where[] = [$k, $sym, $arr];
                    break;
                case 'RANGE':
                case 'NOT RANGE':
                    $v = str_replace(' - ', ',', $v);
                    $arr = array_slice(explode(',', $v), 0, 2);
                    if (stripos($v, ',') === false || !array_filter($arr)) {
                        continue 2;
                    }
                    //当出现一边为空时改变操作符
                    if ($arr[0] === '') {
                        $sym = $sym == 'RANGE' ? '<=' : '>';
                        $arr = $arr[1];
                    } elseif ($arr[1] === '') {
                        $sym = $sym == 'RANGE' ? '>=' : '<';
                        $arr = $arr[0];
                    }
                    $tableArr = explode('.', $k);
                    if (count($tableArr) > 1 && $tableArr[0] != $name && !in_array($tableArr[0], $alias) && !empty($this->model)) {
                        //修复关联模型下时间无法搜索的BUG
                        $relation = Loader::parseName($tableArr[0], 1, false);
                        $alias[$this->model->$relation()->getTable()] = $tableArr[0];
                    }
                    $where[] = [$k, str_replace('RANGE', 'BETWEEN', $sym) . ' TIME', $arr];
                    break;
                case 'NULL':
                case 'IS NULL':
                case 'NOT NULL':
                case 'IS NOT NULL':
                    $where[] = [$k, strtolower(str_replace('IS ', '', $sym))];
                    break;
                default:
                    break;
            }
            $index++;
        }
        if (!empty($this->model)) {
            $this->model->alias($alias);
        }
        $model = $this->model;
        $where = function ($query) use ($where, $alias, $bind, &$model) {
            if (!empty($model)) {
                $model->alias($alias);
                $model->bind($bind);
            }
            foreach ($where as $k => $v) {
                if (is_array($v)) {
                    call_user_func_array([$query, 'where'], $v);
                } else {
                    $query->where($v);
                }
            }
        };
        return [$where, $sort, $order, $offset, $limit, $page, $alias, $bind];
    }

    /**
     * 清空系统缓存
     */
    public function wipecache()
    {
        try {
            $type = $this->request->request("type");
            switch ($type) {
                case 'all':
                    // no break
                case 'content':
                    //内容缓存
                    rmdirs(CACHE_PATH, false);
                    Cache::clear();
                    if ($type == 'content') {
                        break;
                    }
                case 'template':
                    // 模板缓存
                    rmdirs(TEMP_PATH, false);
                    if ($type == 'template') {
                        break;
                    }
                case 'addons':
                    // 插件缓存
                    Service::refresh();
                    if ($type == 'addons') {
                        break;
                    }
                case 'browser':
                    // 浏览器缓存
                    // 只有生产环境下才修改
                    if (!config('app_debug')) {
                        $version = config('site.version');
                        $newversion = preg_replace_callback("/(.*)\.([0-9]+)\$/", function ($match) {
                            return $match[1] . '.' . ($match[2] + 1);
                        }, $version);
                        if ($newversion && $newversion != $version) {
                            Db::startTrans();
                            try {
                                \app\common\model\Config::where('name', 'version')->update(['value' => $newversion]);
                                \app\common\model\Config::refreshFile();
                                Db::commit();
                            } catch (\Exception $e) {
                                Db::rollback();
                                exception($e->getMessage());
                            }
                        }
                    }
                    if ($type == 'browser') {
                        break;
                    }
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        \think\Hook::listen("wipecache_after");
        $this->success();
    }

    /**
     * 读取分类数据,联动列表
     */
    public function category()
    {
        $type = $this->request->get('type', '');
        $pid = $this->request->get('pid', '');
        $where = ['status' => 'normal'];
        $categorylist = null;
        if ($pid || $pid === '0') {
            $where['pid'] = $pid;
        }
        if ($type) {
            $where['type'] = $type;
        }

        $categorylist = Db::name('category')->where($where)->field('id as value,name')->order('weigh desc,id desc')->select();

        $this->success('', '', $categorylist);
    }

    /**
     * 读取省市区数据,联动列表
     */
    public function area()
    {
        $params = $this->request->get("row/a");
        if (!empty($params)) {
            $province = isset($params['province']) ? $params['province'] : null;
            $city = isset($params['city']) ? $params['city'] : null;
        } else {
            $province = $this->request->get('province');
            $city = $this->request->get('city');
        }
        $where = ['pid' => 0, 'level' => 1];
        $provincelist = null;
        if ($province !== null) {
            $where['pid'] = $province;
            $where['level'] = 2;
            if ($city !== null) {
                $where['pid'] = $city;
                $where['level'] = 3;
            }
        }
        $provincelist = Db::name('area')->where($where)->field('id as value,name')->select();
        $this->success('', '', $provincelist);
    }

    /**
     * 生成后缀图标
     */
    public function icon()
    {
        $suffix = $this->request->request("suffix");
        $suffix = $suffix ? $suffix : "FILE";
        $data = build_suffix_image($suffix);
        $header = ['Content-Type' => 'image/svg+xml'];
        $offset = 30 * 60 * 60 * 24; // 缓存一个月
        $header['Cache-Control'] = 'public';
        $header['Pragma'] = 'cache';
        $header['Expires'] = gmdate("D, d M Y H:i:s", time() + $offset) . " GMT";
        $response = Response::create($data, '', 200, $header);
        return $response;
    }

    
    /**
     * 读取合作方信息
     */
    public function cooperation(){
        $group_id = Db::name('auth_group')->where('group_type',2)->find()['id'];
        $auth_group_access = Db::name('auth_group_access')->where('group_id',$group_id)->field(['uid'])->select();
        $list = [];
        foreach($auth_group_access as $k=>$v){
            $admin = Db::name('admin')->where('id',$v['uid'])->find();
            if(!$admin){
                continue;
            }
            $cooperation['value'] = $admin['id'];
            $cooperation['name'] = $admin['nickname'];
            array_push($list,$cooperation);
            unset($cooperation);
            unset($admin);

        }
        $this->success('', '', $list);
    }

    public function first()
    {
        $list = [];
        $childrenGroupIds = $this->auth->getChildrenGroupIds(true);
        unset($childrenGroupIds[0]);
        // var_dump($childrenGroupIds);exit;
        $where['id'] = ['in',$childrenGroupIds];
        $group_list = Db::name('AuthGroup')->where($where)->select();
        $list = [];
        foreach($group_list as $k=>$v){
            $list[$k]['value'] = $v['id'];
            $list[$k]['name'] = $v['name'];
        }
        $this->success('', '', $list);
    }

    public function second()
    {
        $list = [];
        $first = $this->request->get('first', '');
        $where['id'] = $first;
        $group = Db::name('AuthGroup')->where($where)->find()['group_type'];

        $lev = substr($group,0,1);
        $grade = substr( $group, 1, 2 );

        $usr_space_list = $_SESSION['think']['admin']['space_list'];
        $usr_cooperation_list = $_SESSION['think']['admin']['cooperation_list'];
        $usr_group = $_SESSION['think']['admin']['group_type'];
        $usr_space_admin_list = $_SESSION['think']['admin']['space_admin_list'];
        // var_dump($lev,$_SESSION['think']['admin']);exit;3
        $list = [];
        if(in_array($usr_group,[11,21,22,23])){
            $p_lev = ($lev - 1).'4';
            if($group == '99'){
                $p_lev = 21;
            }
            //超级权限获取所有父级
            if($lev == 2){
                $list = Db::name('admin')->where('id',1)->field('id as value,nickname as name')->select();
            }else{
                $where_pid_group['group_type'] = $p_lev;

                $pid_group = Db::name('AuthGroup')->where($where_pid_group)->find()['id'];
                if($group == '99'){
                    $pid_group = 2;
                }
                // var_dump($pid_group);exit;
                $where_p_admin_id['group_id'] = $pid_group;
                $p_uid = Db::name('AuthGroupAccess')->where($where_p_admin_id)->column('uid');
                $list = Db::name('Admin')->where('id','in',$p_uid)->field('id as value,nickname as name')->select();

                // var_dump($list);exit;
            }

        }elseif(in_array($usr_group,[24,31,32,33])){
            //创建合作方,合作方管理员，销售，财务
            // var_dump($usr_cooperation_list);exit;
            if($lev == 3){
                $list = Db::name('Admin')->where('id','in',$usr_cooperation_list)->field('id as value,nickname as name')->select();
            }else{
                $list = Db::name('Admin')->where('id','in',$usr_space_admin_list)->field('id as value,nickname as name')->select();
            }
        }elseif(in_array($usr_group,[34,41,42,43])){
            //场馆管理员，销售，财务，获取场馆id
            // var_dump($usr_space_list);
            $list = Db::name('Admin')->where('id','in',$usr_space_list)->field('id as value,nickname as name')->select();
        }
        $this->success('', '', $list);
    }



    /**
     * 读取场馆
     */
    public function space()
    {
        $cooperation_id = $this->request->get('cooperation_id', '');
        $space = $this->space->where('cooperation_id',$cooperation_id)->select();
        $list = [];
        foreach($space as $k=>$v){
            $admin = Db::name('admin')->where('id',$v['space_admin_id'])->field(['id','nickname'])->find();
            $list[$k]['value'] = $admin['id'];
            $list[$k]['name'] = $admin['nickname'];
            unset($admin);
        }
        $this->success('', '', $list);
    }

    /**
     * 读取场馆
     */
    public function spacelist()
    {
        $cooperation_id = $this->request->get('cooperation_id', '');
        $space = $this->space->where('cooperation_id',$cooperation_id)->select();
        $list = [];
        foreach($space as $k=>$v){
            $admin = Db::name('admin')->where('id',$v['space_admin_id'])->field(['id','nickname'])->find();
            $list[$k]['value'] = $admin['id'];
            $list[$k]['name'] = $admin['nickname'];
            unset($admin);
        }
        $this->success('', '', $list);
    }
}
