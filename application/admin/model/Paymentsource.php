<?php

namespace app\admin\model;

use think\Model;


class Paymentsource extends Model
{

    

    

    // 表名
    protected $name = 'payment_source';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    

    







    public function admin()
    {
        return $this->belongsTo('Admin', 'cooperation_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
