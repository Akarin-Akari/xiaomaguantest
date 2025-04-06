<?php

namespace app\admin\model;

use think\Model;
use traits\model\SoftDelete;

class Installment extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'installment';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'audit_text',
        'pay_status_text',
        'pay_time_text'
    ];
    

    
    public function getAuditList()
    {
        return ['yes' => __('Yes'), 'no' => __('No')];
    }

    public function getPayStatusList()
    {
        return ['yes' => __('Pay_status yes'), 'no' => __('Pay_status no')];
    }


    public function getAuditTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['audit']) ? $data['audit'] : '');
        $list = $this->getAuditList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getPayStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['pay_status']) ? $data['pay_status'] : '');
        $list = $this->getPayStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getPayTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['pay_time']) ? $data['pay_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setPayTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


    public function admin()
    {
        return $this->belongsTo('Admin', 'cooperation_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function source()
    {
        return $this->belongsTo('app\admin\model\payment\Source', 'payment_source', 'id', [], 'LEFT')->setEagerlyType(0);
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
