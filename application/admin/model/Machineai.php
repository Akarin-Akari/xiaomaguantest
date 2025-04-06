<?php

namespace app\admin\model;

use think\Model;
use traits\model\SoftDelete;

class Machineai extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'machine_ai';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'car_type_text',
        'subject_type_text',
        'state_text'
    ];
    

    
    public function getCarTypeList()
    {
        return ['cartype1' => __('Car_type cartype1'), 'cartype2' => __('Car_type cartype2')];
    }

    public function getSubjectTypeList()
    {
        return ['subject2' => __('Subject_type subject2'), 'subject3' => __('Subject_type subject3')];
    }

    public function getStateList()
    {
        return ['0' => __('State 0'), '1' => __('State 1')];
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
        $value = $value ? $value : (isset($data['subject_type']) ? $data['subject_type'] : '');
        $list = $this->getSubjectTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getStateTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['state']) ? $data['state'] : '');
        $list = $this->getStateList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setCarTypeAttr($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
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
