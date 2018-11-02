<?php

namespace App\Repositories\Bonus;

use App\Models\UserBonus;

class UserBonusRepository
{

    /**
     * 返回用户红包数量
     * @param $userId
     * @return mixed
     */
    public function getUserBonusCount($userId)
    {
        return UserBonus::where('user_id', $userId)
            ->count();
    }
}
