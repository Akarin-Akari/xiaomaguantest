<?php

namespace app\admin\model;

use think\Model;
use traits\model\SoftDelete;

class Terminal extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'terminal';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'state_text',
        'subject_type_text'
    ];
    

    
    public function getStateList()
    {
        return ['0' => __('State 0'), '1' => __('State 1')];
    }

    public function getSubjectTypeList()
    {
        return ['subject2' => __('Subject_type subject2'), 'subject3' => __('Subject_type subject3')];
    }


    public function getStateTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['state']) ? $data['state'] : '');
        $list = $this->getStateList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getSubjectTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['subject_type']) ? $data['subject_type'] : '');
        $list = $this->getSubjectTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }




    public function admin()
    {
        return $this->belongsTo('Admin', 'cooperation_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function space()
    {
        return $this->belongsTo('Space', 'space_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
