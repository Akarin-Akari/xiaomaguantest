<?php

namespace app\admin\model;

use think\Model;
use traits\model\SoftDelete;

class Intentstudent extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'intent_student';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'sex_text',
        'regis_status_text',
        'subject_type_text',
        'car_type_text',
        'intention_text',
        'registtime_text'
    ];
    

    
    public function getSexList()
    {
        return ['male' => __('Sex male'), 'female' => __('Sex female')];
    }

    public function getRegisStatusList()
    {
        return ['1' => __('Regis_status 1'), '2' => __('Regis_status 2')];
    }

    public function getSubjectTypeList()
    {
        return ['subject1' => __('Subject_type subject1'), 'subject2' => __('Subject_type subject2'), 'subject3' => __('Subject_type subject3'), 'subject4' => __('Subject_type subject4')];
    }

    public function getCarTypeList()
    {
        return ['cartype1' => __('Car_type cartype1'), 'cartype2' => __('Car_type cartype2'), 'cartype3' => __('Car_type cartype3'), 'cartype4' => __('Car_type cartype4'), 'cartype5' => __('Car_type cartype5'), 'cartype6' => __('Car_type cartype6'), 'cartype7' => __('Car_type cartype7')];
    }

    public function getIntentionList()
    {
        return ['1' => __('Intention 1'), '2' => __('Intention 2'), '3' => __('Intention 3'), '4' => __('Intention 4'), '5' => __('Intention 5')];
    }


    public function getSexTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['sex']) ? $data['sex'] : '');
        $list = $this->getSexList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getRegisStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['regis_status']) ? $data['regis_status'] : '');
        $list = $this->getRegisStatusList();
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
        $value = $value ? $value : (isset($data['car_type']) ? $data['car_type'] : '');
        $list = $this->getCarTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getIntentionTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['intention']) ? $data['intention'] : '');
        $list = $this->getIntentionList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getRegisttimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['registtime']) ? $data['registtime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setSubjectTypeAttr($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }

    protected function setRegisttimeAttr($value)
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

        
    public function recommender()
    {
        return $this->belongsTo('Recommender', 'follower', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    
    public function signupsource()
    {
        return $this->belongsTo('signupsource', 'sign_up_source', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
