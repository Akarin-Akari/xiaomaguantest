<?php

namespace app\admin\model;

use think\Model;


class Processcooperation extends Model
{

    

    

    // 表名
    protected $name = 'process_cooperation';
    
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
    
    public function process()
    {
        return $this->belongsTo('Process', 'process_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
