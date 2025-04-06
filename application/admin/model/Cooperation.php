<?php

namespace app\admin\model;

use think\Model;
use traits\model\SoftDelete;

class Cooperation extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'cooperation';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'ai_agree_sc_text',
        'ai_pass_coach_text',
        'keer_pass_sc_text',
        'keer_pass_time_text',
        'kesan_pass_sc_text',
        'kesan_pass_time_text',
        'distribute_state_text',
        'forbidden_pay_state_text',
        'forbidden_tmp_stu_text',
        'forbidden_not_reserve_text',
        'forbidden_tmp_stu_ai_text',
        'forbidden_not_reserve_ai_text',
        'promote_day_state_text',
        'reserve_warn_text',
        'warn_date_status_text',
        'warn_continue_text'
    ];
    

    
    public function getAiAgreeScList()
    {
        return ['0' => __('Ai_agree_sc 0'), '1' => __('Ai_agree_sc 1')];
    }

    public function getAiPassCoachList()
    {
        return ['0' => __('Ai_pass_coach 0'), '1' => __('Ai_pass_coach 1')];
    }

    public function getKeerPassScList()
    {
        return ['0' => __('Keer_pass_sc 0'), '1' => __('Keer_pass_sc 1')];
    }

    public function getKesanPassScList()
    {
        return ['0' => __('Kesan_pass_sc 0'), '1' => __('Kesan_pass_sc 1')];
    }

    public function getDistributeStateList()
    {
        return ['0' => __('Distribute_state 0'), '1' => __('Distribute_state 1')];
    }

    public function getForbiddenPayStateList()
    {
        return ['0' => __('Forbidden_pay_state 0'), '1' => __('Forbidden_pay_state 1')];
    }

    public function getForbiddenTmpStuList()
    {
        return ['0' => __('Forbidden_tmp_stu 0'), '1' => __('Forbidden_tmp_stu 1')];
    }

    public function getForbiddenNotReserveList()
    {
        return ['0' => __('Forbidden_not_reserve 0'), '1' => __('Forbidden_not_reserve 1')];
    }

    public function getForbiddenTmpStuAiList()
    {
        return ['0' => __('Forbidden_tmp_stu_ai 0'), '1' => __('Forbidden_tmp_stu_ai 1')];
    }

    public function getForbiddenNotReserveAiList()
    {
        return ['0' => __('Forbidden_not_reserve_ai 0'), '1' => __('Forbidden_not_reserve_ai 1')];
    }

    public function getPromoteDayStateList()
    {
        return ['0' => __('Promote_day_state 0'), '1' => __('Promote_day_state 1')];
    }

    public function getReserveWarnList()
    {
        return ['status0' => __('reserve_warn status0'), 'status1' => __('reserve_warn status1'), 'status2' => __('reserve_warn status2')];
    }

    public function getWarnDateStatusList()
    {
        return ['0' => __('Warn_date_status 0'), '1' => __('Warn_date_status 1')];
    }

    public function getWarnContinueList()
    {
        return ['0' => __('Warn_continue 0'), '1' => __('Warn_continue 1')];
    }
    public function getPayCooperationStateList()
    {
        return ['0' => __('pay_cooperation 0'), '1' => __('pay_cooperation 1')];
    }
    public function getPromopteDayStateList()
    {
        return ['0' => __('Promote_day_state 0'), '1' => __('Promote_day_state 1')];
    }

    
    public function getAiAgreeScTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['ai_agree_sc']) ? $data['ai_agree_sc'] : '');
        $list = $this->getAiAgreeScList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getAiPassCoachTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['ai_pass_coach']) ? $data['ai_pass_coach'] : '');
        $list = $this->getAiPassCoachList();
        return isset($list[$value]) ? $list[$value] : '';
    }
    public function getPayCooperationTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['pay_cooperation']) ? $data['pay_cooperation'] : '');
        $list = $this->getPayCooperationStateList();
        return isset($list[$value]) ? $list[$value] : '';
    }
    

    public function getKeerPassScTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['keer_pass_sc']) ? $data['keer_pass_sc'] : '');
        $list = $this->getKeerPassScList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getKeerPassTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['keer_pass_time']) ? $data['keer_pass_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getKesanPassScTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['kesan_pass_sc']) ? $data['kesan_pass_sc'] : '');
        $list = $this->getKesanPassScList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getKesanPassTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['kesan_pass_time']) ? $data['kesan_pass_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getDistributeStateTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['distribute_state']) ? $data['distribute_state'] : '');
        $list = $this->getDistributeStateList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getForbiddenPayStateTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['forbidden_pay_state']) ? $data['forbidden_pay_state'] : '');
        $list = $this->getForbiddenPayStateList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getForbiddenTmpStuTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['forbidden_tmp_stu']) ? $data['forbidden_tmp_stu'] : '');
        $list = $this->getForbiddenTmpStuList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getForbiddenNotReserveTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['forbidden_not_reserve']) ? $data['forbidden_not_reserve'] : '');
        $list = $this->getForbiddenNotReserveList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getForbiddenTmpStuAiTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['forbidden_tmp_stu_ai']) ? $data['forbidden_tmp_stu_ai'] : '');
        $list = $this->getForbiddenTmpStuAiList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getForbiddenNotReserveAiTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['forbidden_not_reserve_ai']) ? $data['forbidden_not_reserve_ai'] : '');
        $list = $this->getForbiddenNotReserveAiList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getReserveWarnTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['reserve_warn']) ? $data['reserve_warn'] : '');
        $list = $this->getReserveWarnList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getWarnDateStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['warn_date_status']) ? $data['warn_date_status'] : '');
        $list = $this->getWarnDateStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getWarnContinueTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['warn_continue']) ? $data['warn_continue'] : '');
        $list = $this->getWarnContinueList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setKeerPassTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setKesanPassTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setReserveWarnAttr($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }
    public function getPromoteDayStateTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['promote_day_state']) ? $data['promote_day_state'] : '');
        $list = $this->getDistributeStateList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function admin()
    {
        return $this->belongsTo('Admin', 'cooperation_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
