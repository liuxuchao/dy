<?php

namespace App\Repositories\Wechat;

use App\Models\WxappConfig;
use App\Models\WxappTemplate;
use Illuminate\Support\Facades\Cache;

class WxappConfigRepository
{
    /**
     * 获取小程序配置信息
     * @param
     * @return
     */
    public function getWxappConfig()
    {
        $wxappConfig = Cache::get('wxapp_config');

        if (empty($wxappConfig)) {
            $wxappConfig = WxappConfig::get()
                ->toArray();

            Cache::put('wxapp_config', $wxappConfig, 60);
        }

        return $wxappConfig;
    }

    /**
     * 根据code获取配置
     * @param $code
     * @return mixed
     */
    public function getWxappConfigByCode($code)
    {
        $wxappConfig = $this->getWxappConfig();
        foreach ($wxappConfig as $v) {
            return $v[$code];
        }
    }


    /**
     * 根据code获取模板配置
     * @param $code
     * @return mixed
     */
    public function getTemplateInfo($code)
    {
         $wxapp_template =  WxappTemplate::select('*')
            ->where('wx_code', $code)
            ->first();

        if($wxapp_template === null) {
            return [];
        }

        return $wxapp_template->toArray();

    }


}
