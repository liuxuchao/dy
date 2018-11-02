<?php

namespace App\Services;

use App\Extensions\Wxapp;
use Illuminate\Http\Request;
use App\Repositories\Account\AccountRepository;
use App\Repositories\User\UserRepository;




/**
 * Class teamRepository
 * @package App\Services
 */
class AccountService
{

    private $accountRepository;
    private $userRepository;
    private $root_url;

    /**
     * IndexService constructor.
     * @param AccountRepository $accountRepository
     * @param ShopRepository $shopRepository
     * @param GoodsAttrRepository $goodsAttrRepository
     * @param Request $request
     */
    public function __construct(
        AccountRepository $accountRepository,
        UserRepository $userRepository,
        Request $request
    ){
        $this->accountRepository = $accountRepository;
        $this->userRepository = $userRepository;
        $this->root_url = dirname(dirname($request->root())) . '/';
    }


	/**
     * 记录拼团退款资金变动
     * @param   int     $user_id        用户id
     * @param   float   $user_money     可用余额变动
     * @param   float   $frozen_money   冻结余额变动
     * @param   int     $rank_points    等级积分变动
     * @param   int     $pay_points     消费积分变动
     * @param   string  $change_desc    变动说明
     * @param   int     $change_type    变动类型：参见常量文件
     * @return  void
     */
    public function logAccountChange($user_id, $shop_money = 0, $frozen_money = 0, $rank_points = 0, $pay_points = 0, $change_desc = '', $change_type)
    {        
        if ($change_type == ACT_TRANSFERRED) {
            //插入帐户变动记录 
            $account_log = [
                'user_id'       => $user_id,
                'user_money'    => -$shop_money,
                'frozen_money'  => $frozen_money,
                'rank_points'   => -$rank_points,
                'pay_points'    => -$pay_points,
                'change_time'   => gmtime(),
                'change_desc'   => $change_desc,
                'change_type'   => $change_type
            ];
            $this->accountRepository->addAccountLog($account_log);
            
        }
        if ($change_type == ACT_TRANSFERRED) {
            // 获取用户信息
            $user_info = $this->userRepository->userInfo($user_id);
            
            // 更新用户信息
            $user_log = [
                'user_money'    => $user_info['user_money'] - ($shop_money),
                'frozen_money'  => $user_info['frozen_money'] - ($frozen_money),
                'rank_points'   => $user_info['rank_points'] - ($rank_points),                
                'pay_points'    => $user_info['pay_points'] - ($pay_points)
            ];
            $this->accountRepository->updateuser($user_id, $user_log);
        }

    }





}
