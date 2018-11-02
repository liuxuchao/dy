<?php

namespace App\Services;

use App\Repositories\Wechat\WxappConfigRepository;

class WxappService
{
    private $WxappConfigRepository;

    /**
     * WxappService constructor.
     * @param WxappConfigRepository $WxappConfigRepository
     */
    public function __construct(WxappConfigRepository $WxappConfigRepository)
    {
        $this->WxappConfigRepository = $WxappConfigRepository;
    }

    /**
     * 小程序配置
     * @return array
     */
    public function getWxappConfig()
    {
        return $this->WxappConfigRepository->getWxappConfig();
    }

    /**
     * 根据code获取小程序配置
     * @return array
     */
    public function getWxappConfigByCode($code)
    {
        return $this->WxappConfigRepository->getWxappConfigByCode($code);
    }

}
