<?php

namespace app\admin\model;

use think\Model;


class Coachsc extends Model
{

    

    

    // 表名
    protected $name = 'coach_sc';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'sex_text',
        'car_type_text',
        'subject_type_text',
        'fstdrilictime_text',
        'succtime_text',
        'failuretime_text',
        'hiretime_text',
        'leavetime_text',
        'teach_state_text'
    ];
    

    
    public function getSexList()
    {
        return ['male' => __('Sex male'), 'female' => __('Sex female')];
    }

    public function getCarTypeList()
    {
        return ['cartype1' => __('Car_type cartype1'), 'cartype2' => __('Car_type cartype2')];
    }

    public function getSubjectTypeList()
    {
        return ['subject1' => __('Subject_type subject1'), 'subject2' => __('Subject_type subject2'), 'subject3' => __('Subject_type subject3'), 'subject4' => __('Subject_type subject4')];
    }

    public function getTeachStateList()
    {
        return ['yes' => __('Teach_state yes'), 'no' => __('Teach_state no')];
    }


    public function getSexTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['sex']) ? $data['sex'] : '');
        $list = $this->getSexList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getCarTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['car_type'] ?? '');
        $valueArr = explode(',', $value);
        $list = $this->getCarTypeList();
        return implode(',', array_intersect_key($list, array_flip($valueArr)));
    }


    public function getSubjectTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['subject_type'] ?? '');
        $valueArr = explode(',', $value);
        $list = $this->getSubjectTypeList();
        return implode(',', array_intersect_key($list, array_flip($valueArr)));
    }


    public function getFstdrilictimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['fstdrilictime']) ? $data['fstdrilictime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getSucctimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['succtime']) ? $data['succtime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getFailuretimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['failuretime']) ? $data['failuretime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getHiretimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['hiretime']) ? $data['hiretime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getLeavetimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['leavetime']) ? $data['leavetime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getTeachStateTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['teach_state']) ? $data['teach_state'] : '');
        $list = $this->getTeachStateList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setCarTypeAttr($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }

    protected function setSubjectTypeAttr($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }

    protected function setFstdrilictimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setSucctimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setFailuretimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setHiretimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setLeavetimeAttr($value)
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
}
