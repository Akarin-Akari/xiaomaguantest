<?php

namespace app\admin\model;

use think\Model;
use traits\model\SoftDelete;

class Fundsclass extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'funds_class';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'state_text'
    ];
    

    
    public function getStateList()
    {
        return ['income' => __('State income'), 'expenditure' => __('State expenditure')];
    }


    public function getStateTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['state']) ? $data['state'] : '');
        $list = $this->getStateList();
        return isset($list[$value]) ? $list[$value] : '';
    }




    public function admin()
    {
        return $this->belongsTo('Admin', 'cooperation_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
