<?php

namespace app\admin\model;

use think\Model;


class Evaluation extends Model
{

    

    

    // 表名
    protected $name = 'evaluation';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'student_type_text',
        'overall_text'
    ];
    

    
    public function getStudentTypeList()
    {
        return ['student' => __('Student_type student'), 'intent_student' => __('Student_type intent_student')];
    }

    public function getOverallList()
    {
        return ['1' => __('Overall 1'), '2' => __('Overall 2'), '3' => __('Overall 3'), '4' => __('Overall 4'), '5' => __('Overall 5')];
    }


    public function getStudentTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['student_type']) ? $data['student_type'] : '');
        $list = $this->getStudentTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getOverallTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['overall']) ? $data['overall'] : '');
        $list = $this->getOverallList();
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
