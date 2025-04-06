<?php

namespace app\admin\model;

use think\Model;


class Machinestartlog extends Model
{

    

    

    // 表名
    protected $name = 'order_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'start_or_end_text'
    ];
    

    
    public function getStartOrEndList()
    {
        return ['1' => __('Start_or_end 1'), '2' => __('Start_or_end 2')];
    }


    public function getStartOrEndTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['start_or_end']) ? $data['start_or_end'] : '');
        $list = $this->getStartOrEndList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function student()
    {
        return $this->belongsTo('Student', 'stu_id', 'stu_id', [], 'LEFT')->setEagerlyType(0);
    }


}
