<?php

namespace App\Repositories\Account;

use App\Models\AccountLog;
use App\Models\Users;
use App\Repositories\ShopConfig\ShopConfigRepository;
use App\Repositories\Goods\GoodsRepository;
use App\Repositories\Goods\GoodsAttrRepository;
use App\Repositories\User\UserRepository;
use App\Services\AuthService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * Class BargainRepository
 * @package App\Repositories\bargain
 */
class AccountRepository
{
    protected $goods;
    private $field;
    private $authService;
    private $goodsAttrRepository;
    private $shopConfigRepository;
    private $goodsRepository;
    private $userRepository;

    public function __construct(
        AuthService $authService,
        GoodsAttrRepository $goodsAttrRepository,
        ShopConfigRepository $shopConfigRepository,
        GoodsRepository $goodsRepository,
        UserRepository $userRepository

    )
    {       
        $this->authService = $authService;
        $this->goodsAttrRepository = $goodsAttrRepository;
        $this->shopConfigRepository = $shopConfigRepository;
        $this->goodsRepository = $goodsRepository;
        $this->userRepository = $userRepository;
    }    


	/**
     * 插入帐户变动记录
     * @param $params
     * @return bool
     */
    public function addAccountLog($params)
    {
        $add = AccountLog::insertGetId(
            $params
        );
        if ($add) {
            return $add;
        }
    }

	/**
     * 更新用户信息
     * @param $params
     * @return bool
     */
    public function updateuser($user_id, $params)
    {
        Users::where('user_id', $user_id)
            ->update($params);

    }
	


}
