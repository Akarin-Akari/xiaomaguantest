<?php

namespace app\admin\model;

use think\Model;
use traits\model\SoftDelete;

class Device extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'device';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'state_text',
        'type_text'
    ];
    

    
    public function getStateList()
    {
        return ['0' => __('State 0'), '1' => __('State 1')];
    }

    public function getTypeList()
    {
        return ['type1' => __('Type type1'), 'type2' => __('Type type2'), 'type3' => __('Type type3'), 'type4' => __('Type type4')];
    }


    public function getStateTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['state']) ? $data['state'] : '');
        $list = $this->getStateList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function admin()
    {
        return $this->belongsTo('Admin', 'cooperation_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    

    public function space()
    {
        return $this->belongsTo('Space', 'space_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
