<?php

namespace app\admin\model;

use think\Model;


class Sqbinstallment extends Model
{

    

    

    // 表名
    protected $name = 'sqb_installment';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'subject_type_text'
    ];
    

    
    public function getSubjectTypeList()
    {
        return ['1' => __('Subject_type 1'), '2' => __('Subject_type 2')];
    }


    public function getSubjectTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['subject_type']) ? $data['subject_type'] : '');
        $list = $this->getSubjectTypeList();
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
