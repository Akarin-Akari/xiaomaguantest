<?php

namespace app\admin\model;

use think\Model;


class Studyprocessai extends Model
{

    

    

    // 表名
    protected $name = 'study_process_ai';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'subject_type_text',
        'status_text',
        'study_time_text'
    ];
    

    
    public function getSubjectTypeList()
    {
        return ['subject2' => __('Subject_type subject2'), 'subject3' => __('Subject_type subject3')];
    }

    public function getStatusList()
    {
        return ['0' => __('Status 0'), '1' => __('Status 1')];
    }


    public function getSubjectTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['subject_type']) ? $data['subject_type'] : '');
        $list = $this->getSubjectTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getStudyTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['study_time']) ? $data['study_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setStudyTimeAttr($value)
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


    public function student()
    {
        return $this->belongsTo('Student', 'stu_id', 'stu_id', [], 'LEFT')->setEagerlyType(0);
    }


    public function place()
    {
        return $this->belongsTo('Place', 'place_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
