<?php

namespace app\admin\model;

use think\Model;


class Reportcardai extends Model
{

    

    

    // 表名
    protected $name = 'report_card_ai';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'subject_type_text',
        'endtime_text',
        'pass_time_text'
    ];
    

    
    public function getSubjectTypeList()
    {
        return ['subject1' => __('Subject_type subject1'), 'subject2' => __('Subject_type subject2'), 'subject3' => __('Subject_type subject3'), 'subject4' => __('Subject_type subject4')];
    }


    public function getSubjectTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['subject_type'] ?? '');
        $valueArr = explode(',', $value);
        $list = $this->getSubjectTypeList();
        return implode(',', array_intersect_key($list, array_flip($valueArr)));
    }


    public function getEndtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['endtime']) ? $data['endtime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getPassTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['pass_time']) ? $data['pass_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setSubjectTypeAttr($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }

    protected function setEndtimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setPassTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


    public function admin()
    {
        return $this->belongsTo('Admin', 'cooperation_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function space()
    {
        return $this->belongsTo('Space', 'space_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function car()
    {
        return $this->belongsTo('app\admin\model\machine\Car', 'machine_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function student()
    {
        return $this->belongsTo('Student', 'stu_id', 'stu_id', [], 'LEFT')->setEagerlyType(0);
    }


    public function coachsc()
    {
        return $this->belongsTo('app\admin\model\coach\Sc', 'coach_id', 'coach_id', [], 'LEFT')->setEagerlyType(0);
    }

    public function machineai()
    {
        return $this->belongsTo('app\admin\model\machine\Ai', 'machine_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
