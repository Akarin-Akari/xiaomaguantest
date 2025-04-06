<?php

namespace app\admin\model;

use think\Model;
use traits\model\SoftDelete;

class Kesanhegeai extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'student_warn_ai';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'keer_hege_status_text',
        'kesan_hege_status_text',
        'keer_leiji_status_text',
        'kesan_leiji_status_text'
    ];
    

    
    public function getKeerHegeStatusList()
    {
        return ['0' => __('Keer_hege_status 0'), '1' => __('Keer_hege_status 1')];
    }

    public function getKesanHegeStatusList()
    {
        return ['0' => __('Kesan_hege_status 0'), '1' => __('Kesan_hege_status 1')];
    }

    public function getKeerLeijiStatusList()
    {
        return ['0' => __('Keer_leiji_status 0'), '1' => __('Keer_leiji_status 1')];
    }

    public function getKesanLeijiStatusList()
    {
        return ['0' => __('Kesan_leiji_status 0'), '1' => __('Kesan_leiji_status 1')];
    }


    public function getKeerHegeStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['keer_hege_status']) ? $data['keer_hege_status'] : '');
        $list = $this->getKeerHegeStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getKesanHegeStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['kesan_hege_status']) ? $data['kesan_hege_status'] : '');
        $list = $this->getKesanHegeStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getKeerLeijiStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['keer_leiji_status']) ? $data['keer_leiji_status'] : '');
        $list = $this->getKeerLeijiStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getKesanLeijiStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['kesan_leiji_status']) ? $data['kesan_leiji_status'] : '');
        $list = $this->getKesanLeijiStatusList();
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


    public function student()
    {
        return $this->belongsTo('Student', 'stu_id', 'stu_id', [], 'LEFT')->setEagerlyType(0);
    }
    
    public function warnexamai()
    {
        return $this->belongsTo('warnexamai', 'cooperation_id', 'cooperation_id', [], 'LEFT')->setEagerlyType(0);
    }
}
