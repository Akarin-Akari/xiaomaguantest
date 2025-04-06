<?php

namespace app\admin\model;

use think\Model;


class Machinecar extends Model
{

    

    

    // 表名
    protected $name = 'machine_car';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'car_type_text',
        'activate_state_text',
        'state_text',
        'collor_text',
        'mode_text',
        'insurance_start_time_text',
        'insurance_end_time_text'
    ];
    

    
    public function getCarTypeList()
    {
        return ['cartype1' => __('Car_type cartype1'), 'cartype2' => __('Car_type cartype2')];
    }

    public function getActivateStateList()
    {
        return ['0' => __('Activate_state 0'), '1' => __('Activate_state 1')];
    }

    public function getStateList()
    {
        return ['0' => __('State 0'), '1' => __('State 1')];
    }

    public function getCollorList()
    {
        return ['1' => __('Collor 1'), '2' => __('Collor 2'), '3' => __('Collor 3'), '4' => __('Collor 4')];
    }

    public function getModeList()
    {
        return ['1' => __('Mode 1'), '2' => __('Mode 2'), '3' => __('Mode 3')];
    }


    public function getCarTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['car_type'] ?? '');
        $valueArr = explode(',', $value);
        $list = $this->getCarTypeList();
        return implode(',', array_intersect_key($list, array_flip($valueArr)));
    }


    public function getActivateStateTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['activate_state']) ? $data['activate_state'] : '');
        $list = $this->getActivateStateList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getStateTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['state']) ? $data['state'] : '');
        $list = $this->getStateList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getCollorTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['collor']) ? $data['collor'] : '');
        $list = $this->getCollorList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getModeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['mode']) ? $data['mode'] : '');
        $list = $this->getModeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getInsuranceStartTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['insurance_start_time']) ? $data['insurance_start_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getInsuranceEndTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['insurance_end_time']) ? $data['insurance_end_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setCarTypeAttr($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }

    protected function setInsuranceStartTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setInsuranceEndTimeAttr($value)
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
