<?php

namespace App\Repositories\User;

use App\Models\Users as User;
use App\Models\CouponsUser;
use App\Models\CollectStore;
use App\Models\CollectGoods;
use App\Models\WechatUser;
use App\Models\ConnectUser;
use App\Models\UserBonus;

class UserRepository
{
    /**
     * 获取用户信息
     * @param $uid
     * @return array
     */
    public function userInfo($uid)
    {
        $user = User::where('user_id', $uid)
            ->select('user_id as id', 'user_name', 'nick_name', 'sex', 'birthday', 'user_money', 'frozen_money', 'pay_points', 'rank_points', 'address_id', 'qq', 'mobile_phone', 'user_picture')
            ->first();
        if ($user === null) {
            return [];
        }
        return $user->toArray();
    }

    /**
     * 获取用户资金信息
     * @param $uid
     * @return array
     */
    public function userFunds($uid)
    {
        $user = User::where('user_id', $uid)
            ->select('user_money', 'frozen_money', 'pay_points')
            ->first();
        if ($user === null) {
            return [];
        }
        $bonus_count = UserBonus::where('user_id', $uid)
            ->count();
        $coupons_count = CouponsUser::where('user_id', $uid)
            ->count();
        $store_count = CollectStore::where('user_id', $uid)
            ->count();
        $goods_count = CollectGoods::where('user_id', $uid)
            ->count();

        $result = [];
        $result['user_money'] = $user['user_money'];
        $result['pay_points'] = $user['pay_points'];
        $result['bonus_count'] = $bonus_count;
        $result['coupons_count'] = $coupons_count;
        $result['store_count'] = $store_count;
        $result['goods_count'] = $goods_count;

        return $result;
    }

    /**
     * 更新用户信息
     * @param  $res
     * @return
     */
    public function renewUser($res)
    {
        $model = new User();
        // 更新记录
        $model = User::where('user_id', $res['user_id'])->first();

        if ($model === null) {
            return [];
        }

        $model->fill($res);

        return $model->save();
    }

    /**
     * 查询绑定用户信息 getConnectUser
     * @param  $unionid
     * @return
     */
    public function getConnectUser($unionid)
    {
        $connectUser = User::select('users.user_id')
            ->leftjoin('connect_user', 'connect_user.user_id', '=', 'users.user_id')
            ->where('connect_user.open_id', $unionid)
            ->first();
        if ($connectUser === null) {
            return [];
        }
        return $connectUser->toArray();
    }

    /**
     * 新增社会化登录用户信息
     * @param $res
     * @return
     */
    public function addConnectUser($res)
    {
        $model = new ConnectUser();

        $model->fill($res);
        $model->save();

        return $model->id;
    }

    /**
     * 更新社会化登录用户信息
     * @param  $res
     * @return
     */
    public function updateConnnectUser($res)
    {
        $model = new ConnectUser();
        // 更新记录
        $model = ConnectUser::where('open_id', $res['open_id'])->first();

        if ($model === null) {
            return [];
        }

        $model->fill($res);

        return $model->save();
    }

    /**
     * @param $id
     * @param $uid
     * @return mixed
     */
    public function setDefaultAddress($id, $uid)
    {
        $model = User::where('user_id', $uid)
            ->first();
        if ($model == null) {
            return false;
        }

        $model->address_id = $id;
        return $model->save();
    }


    /**
     *获取用户Openid
     * @param $uid
     * @return mixed
     */
    public function getUserOpenid($uid)
    {
        $list = WechatUser::from('wechat_user as wu')
            ->select('wu.openid')
            ->leftjoin('connect_user as cu', 'cu.open_id', '=', 'wu.unionid')
            ->where('cu.user_id', $uid)
            ->first();

        if ($list === null) {
            return [];
        }

        return $list->toArray();
    }

}
