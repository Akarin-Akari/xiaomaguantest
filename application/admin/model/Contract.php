<?php

namespace app\admin\model;

use think\Model;


class Contract extends Model
{

    

    

    // 表名
    protected $name = 'contract';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'state_text',
        'validitytime_text'
    ];
    

    
    public function getStateList()
    {
        return ['0' => __('State 0'), '1' => __('State 1')];
    }


    public function getStateTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['state']) ? $data['state'] : '');
        $list = $this->getStateList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getValiditytimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['validitytime']) ? $data['validitytime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setValiditytimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


    public function admin()
    {
        return $this->belongsTo('Admin', 'cooperation_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
