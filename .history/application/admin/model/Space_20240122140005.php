<?php

namespace app\admin\model;

use think\Model;
use traits\model\SoftDelete;

class Space extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'space';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'space_type_text',
        'subject_type_text',
        'car_type_text',
        'pay_status_text',
        'times_limit_status_text',
        'times_limit_cooperation_status_text',
        'temporary_limit_text',
        'process_limit_text',
        'pass_status_text',
        'space_state_text',
        'pick_up_status_text'
    ];
    

    
    public function getSpaceTypeList()
    {
        return ['car' => __('Space_type car'), 'ai_car' => __('Space_type ai_car')];
    }

    public function getSubjectTypeList()
    {
        return ['subject1' => __('Subject_type subject1'), 'subject2' => __('Subject_type subject2'), 'subject3' => __('Subject_type subject3'), 'subject4' => __('Subject_type subject4')];
    }

    public function getCarTypeList()
    {
        return ['cartype1' => __('Car_type cartype1'), 'cartype2' => __('Car_type cartype2')];
    }

    public function getPayStatusList()
    {
        return ['0' => __('Pay_status 0'), '1' => __('Pay_status 1')];
    }

    public function getTimesLimitStatusList()
    {
        return ['0' => __('Times_limit_status 0'), '1' => __('Times_limit_status 1')];
    }

    public function getTimesLimitCooperationStatusList()
    {
        return ['0' => __('Times_limit_cooperation_status 0'), '1' => __('Times_limit_cooperation_status 1')];
    }

    public function getTemporaryLimitList()
    {
        return ['0' => __('Temporary_limit 0'), '1' => __('Temporary_limit 1')];
    }

    public function getProcessLimitList()
    {
        return ['0' => __('Process_limit 0'), '1' => __('Process_limit 1')];
    }

    public function getPassStatusList()
    {
        return ['0' => __('Pass_status 0'), '1' => __('Pass_status 1')];
    }

    public function getSpaceStateList()
    {
        return ['yes' => __('Space_state yes'), 'no' => __('Space_state no')];
    }

    public function getPickUpStatusList()
    {
        return ['0' => __('Pick_up_status 0'), '1' => __('Pick_up_status 1')];
    }


    public function getSpaceTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['space_type']) ? $data['space_type'] : '');
        $list = $this->getSpaceTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getSubjectTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['subject_type'] ?? '');
        $valueArr = explode(',', $value);
        $list = $this->getSubjectTypeList();
        return implode(',', array_intersect_key($list, array_flip($valueArr)));
    }


    public function getCarTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['car_type'] ?? '');
        $valueArr = explode(',', $value);
        $list = $this->getCarTypeList();
        return implode(',', array_intersect_key($list, array_flip($valueArr)));
    }


    public function getPayStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['pay_status']) ? $data['pay_status'] : '');
        $list = $this->getPayStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getTimesLimitStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['times_limit_status']) ? $data['times_limit_status'] : '');
        $list = $this->getTimesLimitStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getTimesLimitCooperationStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['times_limit_cooperation_status']) ? $data['times_limit_cooperation_status'] : '');
        $list = $this->getTimesLimitCooperationStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getTemporaryLimitTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['temporary_limit']) ? $data['temporary_limit'] : '');
        $list = $this->getTemporaryLimitList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getProcessLimitTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['process_limit']) ? $data['process_limit'] : '');
        $list = $this->getProcessLimitList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getPassStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['pass_status']) ? $data['pass_status'] : '');
        $list = $this->getPassStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getSpaceStateTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['space_state']) ? $data['space_state'] : '');
        $list = $this->getSpaceStateList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getPickUpStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['pick_up_status']) ? $data['pick_up_status'] : '');
        $list = $this->getPickUpStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setSubjectTypeAttr($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }

    protected function setCarTypeAttr($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }


    public function admin()
    {
        return $this->belongsTo('Admin', 'cooperation_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
