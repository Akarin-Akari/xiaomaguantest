<?php

namespace app\admin\model;

use think\Model;


class Card extends Model
{

    

    

    // 表名
    protected $name = 'ticket';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'verifytime_text'
    ];
    

    



    public function getVerifytimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['verifytime']) ? $data['verifytime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setVerifytimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


    public function admin()
    {
        return $this->belongsTo('Admin', 'cooperation_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function student()
    {
        return $this->belongsTo('Student', 'stu_id', 'stu_id', [], 'LEFT')->setEagerlyType(0);
    }


    public function coach()
    {
        return $this->belongsTo('Coach', 'coach_id', 'coach_id', [], 'LEFT')->setEagerlyType(0);
    }


    
    public function recommender()
    {
        return $this->belongsTo('Recommender', 'verify_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

}
