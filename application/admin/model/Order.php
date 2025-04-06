<?php

namespace app\admin\model;

use think\Model;


class Order extends Model
{

    

    

    // 表名
    protected $name = 'order';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'car_type_text',
        'subject_type_text',
        'reserve_starttime_text',
        'reserve_endtime_text',
        'starttime_text',
        'endtime_text',
        'should_endtime_text',
        'payModel_text',
        'order_status_text',
        'coach_boot_type_text',
        'student_boot_type_text',
        'evaluation_text',
        'ordertype_text'
    ];
    

    
    public function getCarTypeList()
    {
        return ['cartype1' => __('Car_type cartype1'), 'cartype2' => __('Car_type cartype2')];
    }

    public function getSubjectTypeList()
    {
        return ['subject1' => __('Subject_type subject1'), 'subject2' => __('Subject_type subject2'), 'subject3' => __('Subject_type subject3'), 'subject4' => __('Subject_type subject4')];
    }

    public function getPaymodelList()
    {
        return ['1' => __('Paymodel 1'), '2' => __('Paymodel 2')];
    }

    public function getOrderStatusList()
    {
        return ['unpaid' => __('Order_status unpaid'), 'paid' => __('Order_status paid'), 'accept_unexecut' => __('Order_status accept_unexecut'), 'executing' => __('Order_status executing'), 'finished' => __('Order_status finished'), 'cancel_unrefund' => __('Order_status cancel_unrefund'), 'cancel_refunded' => __('Order_status cancel_refunded')];
    }

    public function getCoachBootTypeList()
    {
        return ['0' => __('Coach_boot_type 0'), '1' => __('Coach_boot_type 1')];
    }

    public function getStudentBootTypeList()
    {
        return ['0' => __('Student_boot_type 0'), '1' => __('Student_boot_type 1')];
    }

    public function getEvaluationList()
    {
        return ['0' => __('Evaluation 0'), '1' => __('Evaluation 1')];
    }

    public function getOrdertypeList()
    {
        return ['1' => __('Ordertype 1'), '2' => __('Ordertype 2'), '3' => __('Ordertype 3')];
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


    public function getReserveStarttimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['reserve_starttime']) ? $data['reserve_starttime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getReserveEndtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['reserve_endtime']) ? $data['reserve_endtime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getStarttimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['starttime']) ? $data['starttime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getEndtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['endtime']) ? $data['endtime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getShouldEndtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['should_endtime']) ? $data['should_endtime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getPaymodelTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['payModel']) ? $data['payModel'] : '');
        $list = $this->getPaymodelList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getOrderStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['order_status']) ? $data['order_status'] : '');
        $list = $this->getOrderStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getCoachBootTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['coach_boot_type']) ? $data['coach_boot_type'] : '');
        $list = $this->getCoachBootTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getStudentBootTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['student_boot_type']) ? $data['student_boot_type'] : '');
        $list = $this->getStudentBootTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getEvaluationTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['evaluation']) ? $data['evaluation'] : '');
        $list = $this->getEvaluationList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getOrdertypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['ordertype']) ? $data['ordertype'] : '');
        $list = $this->getOrdertypeList();
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

    protected function setReserveStarttimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setReserveEndtimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setStarttimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setEndtimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setShouldEndtimeAttr($value)
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


    public function coach()
    {
        return $this->belongsTo('Coach', 'coach_id', 'coach_id', [], 'LEFT')->setEagerlyType(0);
    }
    public function machinecar()
    {
        return $this->belongsTo('Machinecar', 'machine_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
