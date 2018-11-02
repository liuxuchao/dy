<?php

namespace App\Repositories\Bonus;

class BonusTypeRepository
{

    /**
     * 取得红包信息
     * @param   int     $bonus_id   红包id
     * @param   string  $bonus_sn   红包序列号
     * @param   array   红包信息
     */
    public function bonusInfo($bonus_id, $bonus_sn = '')
    {
        return self::join('user_bonus', 'bonus_type.type_id', '=', 'user_bonus.bonus_type_id')
            ->where('bonus_id', $bonus_id)
            ->first();
    }
}
