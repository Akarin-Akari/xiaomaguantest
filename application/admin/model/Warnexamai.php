<?php

namespace app\admin\model;

use think\Model;


class Warnexamai extends Model
{

    

    

    // 表名
    protected $name = 'warn_exam_ai';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'status_text',
        'leiji_status_text',
        'delatetime_text'
    ];
    

    
    public function getStatusList()
    {
        return ['0' => __('Status 0'), '1' => __('Status 1')];
    }

    public function getLeijiStatusList()
    {
        return ['0' => __('Leiji_status 0'), '1' => __('Leiji_status 1')];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getLeijiStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['leiji_status']) ? $data['leiji_status'] : '');
        $list = $this->getLeijiStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getDelatetimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['delatetime']) ? $data['delatetime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setDelatetimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


    public function admin()
    {
        return $this->belongsTo('Admin', 'cooperation_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
