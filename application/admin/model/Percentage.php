<?php

namespace app\admin\model;

use think\Model;
use traits\model\SoftDelete;

class Percentage extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'student';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'sex_text',
        'car_type_text',
        'registtime_text',
        'study_sign_text',
        'subject_type_text',
        'pay_status_text',
        'statement_text',
        'payment_process_text',
        'audited_text'
    ];
    

    
    public function getSexList()
    {
        return ['male' => __('Sex male'), 'female' => __('Sex female')];
    }

    public function getCarTypeList()
    {
        return ['cartype1' => __('Car_type cartype1'), 'cartype2' => __('Car_type cartype2')];
    }

    public function getStudySignList()
    {
        return ['studying' => __('Study_sign studying'), 'graduation' => __('Study_sign graduation'), 'expired' => __('Study_sign expired'), 'drop_out' => __('Study_sign drop_out'), 'transfer' => __('Study_sign transfer')];
    }

    public function getSubjectTypeList()
    {
        return ['subject1' => __('Subject_type subject1'), 'subject2' => __('Subject_type subject2'), 'subject3' => __('Subject_type subject3'), 'subject4' => __('Subject_type subject4')];
    }

    
    public function getPayStatusList()
    {
        return ['no' => __('Pay_status no'), 'yes' => __('Pay_status yes')];
    }

    public function getStatementList()
    {
        return ['0' => __('Statement 0'), '1' => __('Statement 1')];
    }

    public function getPaymentProcessList()
    {
        return ['unpaid' => __('Payment_process unpaid'), 'paying' => __('Payment_process paying'), 'payed' => __('Payment_process payed')];
    }

    public function getAuditedList()
    {
        return ['0' => __('Audited 0'), '1' => __('Audited 1')];
    }


    public function getSexTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['sex']) ? $data['sex'] : '');
        $list = $this->getSexList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getCarTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['car_type']) ? $data['car_type'] : '');
        $valueArr = explode(',', $value);
        $list = $this->getCarTypeList();
        return implode(',', array_intersect_key($list, array_flip($valueArr)));
    }


    public function getRegisttimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['registtime']) ? $data['registtime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getStudySignTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['study_sign']) ? $data['study_sign'] : '');
        $list = $this->getStudySignList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getSubjectTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['subject_type']) ? $data['subject_type'] : '');
        $valueArr = explode(',', $value);
        $list = $this->getSubjectTypeList();
        return implode(',', array_intersect_key($list, array_flip($valueArr)));
    }


    public function getPayStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['pay_status']) ? $data['pay_status'] : '');
        $valueArr = explode(',', $value);
        $list = $this->getPayStatusList();
        return implode(',', array_intersect_key($list, array_flip($valueArr)));
    }

    public function getStatementTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['statement']) ? $data['statement'] : '');
        $valueArr = explode(',', $value);
        $list = $this->getStatementList();
        return implode(',', array_intersect_key($list, array_flip($valueArr)));
    }

    public function getPaymentProcessTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['payment_process']) ? $data['payment_process'] : '');
        $list = $this->getPaymentProcessList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getAuditedTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['audited']) ? $data['audited'] : '');
        $list = $this->getAuditedList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setCarTypeAttr($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }

    protected function setRegisttimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setSubjectTypeAttr($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }

    protected function setPayStatusAttr($value)
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


    public function recommender()
    {
        return $this->belongsTo('Recommender', 'follower', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
