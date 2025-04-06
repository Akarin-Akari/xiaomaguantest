<?php

namespace app\admin\controller\auth;

use app\admin\model\AuthGroup;
use app\admin\model\AuthGroupAccess;
use app\common\controller\Backend;
use fast\Random;
use fast\Tree;
use think\Db;
use think\Validate;

/**
 * 管理员管理
 *
 * @icon   fa fa-users
 * @remark 一个管理员可以有多个角色组,左侧的菜单根据管理员所拥有的权限进行生成
 */
class Admin extends Backend
{

    /**
     * @var \app\admin\model\Admin
     */
    protected $model = null;
    protected $selectpageFields = 'id,username,nickname,avatar';
    protected $searchFields = 'id,username,nickname';
    protected $childrenGroupIds = [];
    protected $childrenAdminIds = [];
    protected $usr_group_type = null;
    protected $admin_pid = null;
    protected $recommender = null;
    protected $user_id = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Admin');
        $this->recommender = new \app\admin\model\Recommender;
        $this->childrenAdminIds = $this->auth->getChildrenAdminIds($this->auth->isSuperAdmin());
        $this->childrenGroupIds = $this->auth->getChildrenGroupIds($this->auth->isSuperAdmin());

        $groupList = collection(AuthGroup::where('id', 'in', $this->childrenGroupIds)->select())->toArray();

        Tree::instance()->init($groupList);
        $groupdata = [];
        if ($this->auth->isSuperAdmin()) {
            $result = Tree::instance()->getTreeList(Tree::instance()->getTreeArray(0));
            foreach ($result as $k => $v) {
                $groupdata[$v['id']] = $v['name'];
            }
        } else {
            $result = [];
            $groups = $this->auth->getGroups();
            foreach ($groups as $m => $n) {
                $childlist = Tree::instance()->getTreeList(Tree::instance()->getTreeArray($n['id']));
                $temp = [];
                foreach ($childlist as $k => $v) {
                    $temp[$v['id']] = $v['name'];
                }
                $result[__($n['name'])] = $temp;
            }
            $groupdata = $result;
        }
        $uid = $_SESSION['think']['admin']['id'];
        $admin_pid = $this->model->where('id',$uid)->find()['pid'];
        $this->admin_pid = $admin_pid;
        if($admin_pid ==0){
            $this->admin_pid = $uid;
        }
        $this->user_id =  $_SESSION['think']['admin']['id'];
        $this->usr_group_type = $_SESSION['think']['admin']['group_type'];
        $this->view->assign('groupdata', $groupdata);
        $this->assignconfig("admin", ['id' => $this->auth->id]);
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $childrenGroupIds = $this->childrenGroupIds;
            $groupName = AuthGroup::where('id', 'in', $childrenGroupIds)
                ->column('id,name');
            $authGroupList = AuthGroupAccess::where('group_id', 'in', $childrenGroupIds)
                ->field('uid,group_id')
                ->select();

            $adminGroupName = [];
            foreach ($authGroupList as $k => $v) {
                if (isset($groupName[$v['group_id']])) {
                    $adminGroupName[$v['uid']][$v['group_id']] = $groupName[$v['group_id']];
                }
            }
            $groups = $this->auth->getGroups();
            foreach ($groups as $m => $n) {
                $adminGroupName[$this->auth->id][$n['id']] = $n['name'];
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $list = $this->model
                ->where($where)
                ->where('id', 'in', $this->childrenAdminIds)
                ->field(['password', 'salt', 'token'], true)
                ->order($sort, $order)
                ->paginate($limit);

            foreach ($list as $k => &$v) {
                $groups = isset($adminGroupName[$v['id']]) ? $adminGroupName[$v['id']] : [];
                $v['groups'] = implode(',', array_keys($groups));
                $v['groups_text'] = implode(',', array_values($groups));
            }
            unset($v);
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $this->token();
            $params = $this->request->post("row/a");

            if ($params) {
                if (!Validate::is($params['password'], '\S{6,16}')) {
                    $this->error(__("Please input correct password"));
                }
                $first = $params['first'];
                $pid = $params['second'];
                unset($params['first']);
                unset($params['second']);
                $params['pid'] = $pid;

                $params['salt'] = Random::alnum();
                $params['password'] = md5(md5($params['password']) . $params['salt']);
                $params['avatar'] = '/assets/img/avatar.png'; //设置新管理员默认头像。
                $result = $this->model->validate('Admin.add')->save($params);
                if ($result === false) {
                    $this->error($this->model->getError());
                }
                $dataset[] = ['uid' => $this->model->id, 'group_id' => $first ];
                model('AuthGroupAccess')->saveAll($dataset);
                if($first  == 8){
                    $space['space_name'] = $params['nickname'];
                    $space['cooperation_id'] = $pid;
                    $space['space_admin_id'] = $this->model->id;
                    model('Space')->save($space);
                    array_push($_SESSION['think']['admin']['space_list'],model('Space')->id);
                    array_push($_SESSION['think']['admin']['space_admin_list'],$this->model->id);
                }elseif(in_array($first,[10,13])){ //11对应13
                    $recommender['name'] = $params['nickname'];
                    if($first == 13){
                        $recommender['cooperation_id'] = $pid;
                    }else{
                        $space = model('Space')->where(['space_admin_id'=>$pid])->find();
                        $recommender['space_id'] = $space['id'];
                        if($space){
                            $recommender['cooperation_id'] = $space['cooperation_id'];
                        }
                    }
                    $recommender['leader'] = $this->model->id;
                    $recommender['phone'] = $params['username'];
                    $this->recommender->allowField(true)->save($recommender);
                }
                $this->success();
            }
            $this->error();
        }
        return $this->view->fetch();
    }

    
    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $usr_group_type =  $this->usr_group_type;
        $row = $this->model->get(['id' => $ids]);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if (!in_array($row->id, $this->childrenAdminIds)) {
            $this->error(__('You have no permission'));
        }
        $pid = model('AuthGroupAccess')->where('uid',$ids)->find()['group_id'];
        $first = $pid;
        // var_dump($row->toArray());exit;
        if ($this->request->isPost()) {
            $this->token();
            $params = $this->request->post("row/a");
            if ($params) {
                if ($params['password']) {
                    if (!Validate::is($params['password'], '\S{6,16}')) {
                        $this->error(__("Please input correct password"));
                    }
                    $params['salt'] = Random::alnum();
                    $params['password'] = md5(md5($params['password']) . $params['salt']);
                } else {
                    unset($params['password'], $params['salt']);
                }
                $first = $params['first'];
                $pid = $params['second'];
                unset($params['first']);
                unset($params['second']);
                $params['pid'] = $pid;
                // var_dump($params);exit;
                
                //这里需要针对user_phone做唯一验证
                $adminValidate = \think\Loader::validate('Admin');
                $adminValidate->rule([
                    'user_phone' => 'require|regex:/^1[0-9]{10}$/|unique:admin,user_phone,' . $row->id,
                    'password' => 'regex:\S{32}',
                ]);

                if(in_array($first,[3,7,11,15])){
                    $recommender['name'] = $params['nickname'];
                    $recommender['phone'] = $params['user_phone'];
                    $where['phone']= $params['user_phone'];
                    model('Recommender')->where($where)->update($recommender);
                }
                $result = $row->validate('Admin.edit')->save($params);
                if ($result === false){
                    $this->error($row->getError());
                }

                if($first  == 8){
                    $space['space_name'] = $params['nickname'];
                    $space['cooperation_id'] = $pid;
                    model('Space')->where('space_admin_id',$ids)->update($space);
                }

                // 先移除所有权限
                model('AuthGroupAccess')->where('uid', $row->id)->delete();

                // $group = $this->request->post("group/a");

                // // 过滤不允许的组别,避免越权
                // $group = array_intersect($this->childrenGroupIds, $group);

                $dataset = [];
                $dataset[] = ['uid' => $row->id, 'group_id' => $first];

                model('AuthGroupAccess')->saveAll($dataset);
                $this->success();
            }
            $this->error();
        }
        $grouplist = $this->auth->getGroups($row['id']);
        $groupids = [];
        foreach ($grouplist as $k => $v) {
            $groupids[] = $v['id'];
        }
        if(in_array($usr_group_type,[1])){
            $this->error(__('You can not add user'));
        }
        $this->view->assign("row", $row);
        $this->view->assign("first", $first);
        $this->view->assign("groupids", $groupids);
        return $this->view->fetch();
    }


    /**
     * 删除
     */
    public function del($ids = "")
    {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = $ids ? $ids : $this->request->post("ids");
        if ($ids) {
            $ids = array_intersect($this->childrenAdminIds, array_filter(explode(',', $ids)));
            // 避免越权删除管理员
            $childrenGroupIds = $this->childrenGroupIds;
            $adminList = $this->model->where('id', 'in', $ids)->where('id', 'in', function ($query) use ($childrenGroupIds) {
                $query->name('auth_group_access')->where('group_id', 'in', $childrenGroupIds)->field('uid');
            })->select();
            if ($adminList) {
                $deleteIds = [];
                foreach ($adminList as $k => $v) {
                    $deleteIds[] = $v->id;
                }
                $deleteIds = array_values(array_diff($deleteIds, [$this->auth->id]));
                if ($deleteIds) {
                    Db::startTrans();
                    try {
                        $this->model->destroy($deleteIds);
                        model('AuthGroupAccess')->where('uid', 'in', $deleteIds)->delete();
                        Db::commit();
                    } catch (\Exception $e) {
                        Db::rollback();
                        $this->error($e->getMessage());
                    }
                    $this->success();
                }
                $this->error(__('No rows were deleted'));
            }
        }
        $this->error(__('You have no permission'));
    }

    /**
     * 批量更新
     * @internal
     */
    public function multi($ids = "")
    {
        // 管理员禁止批量操作
        $this->error();
    }

    /**
     * 下拉搜索
     */
    public function selectpage()
    {
        $this->dataLimit = 'auth';
        $this->dataLimitField = 'id';
        return parent::selectpage();
    }
}
