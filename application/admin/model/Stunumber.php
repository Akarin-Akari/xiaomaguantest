<?php

namespace app\admin\model;

use think\Model;


class Stunumber extends Model
{

    

    

    // 表名
    protected $name = 'stu_number';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'audited_text'
    ];
    

    
    public function getAuditedList()
    {
        return ['0' => __('Audited 0'), '1' => __('Audited 1')];
    }


    public function getAuditedTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['audited']) ? $data['audited'] : '');
        $list = $this->getAuditedList();
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


    public function student()
    {
        return $this->belongsTo('Student', 'stu_id', 'stu_id', [], 'LEFT')->setEagerlyType(0);
    }
}
