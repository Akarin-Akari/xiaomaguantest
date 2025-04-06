<?php

namespace app\admin\model;

use think\Model;


class Recommender extends Model
{

    

    

    // 表名
    protected $name = 'recommender';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'status_text',
        'car_type_text',
        'sex_text',
        'sign_status_text',
        'message_status_text'
    ];
    

    
    public function getStatusList()
    {
        return ['yes' => __('Status yes'), 'no' => __('Status no')];
    }

    public function getCarTypeList()
    {
        return ['1' => __('Car_type 1'), '2' => __('Car_type 2')];
    }

    public function getSexList()
    {
        return ['male' => __('Sex male'), 'female' => __('Sex female')];
    }

    public function getSignStatusList()
    {
        return ['no' => __('Sign_status no'), 'yes' => __('Sign_status yes')];
    }

    public function getMessageStatusList()
    {
        return ['no' => __('Message_status no'), 'yes' => __('Message_status yes')];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getCarTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['car_type']) ? $data['car_type'] : '');
        $list = $this->getCarTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getSexTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['sex']) ? $data['sex'] : '');
        $list = $this->getSexList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getSignStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['sign_status']) ? $data['sign_status'] : '');
        $list = $this->getSignStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getMessageStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['message_status']) ? $data['message_status'] : '');
        $list = $this->getMessageStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }




    public function admin()
    {
        return $this->belongsTo('Admin', 'cooperation_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
