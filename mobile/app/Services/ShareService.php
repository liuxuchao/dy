<?php

namespace App\Services;

use App\Extensions\Wxapp;
use App\Repositories\User\UserRepository;
use App\Repositories\Share\ShareRepository;
use App\Repositories\Wechat\WxappConfigRepository;

class ShareService
{
    private $ShareRepository;
    private $userRepository;
    private $WxappConfigRepository;

    public function __construct(
        UserRepository $userRepository,
        ShareRepository $shareRepository,
        WxappConfigRepository $WxappConfigRepository
    )
    {
        $this->userRepository = $userRepository;
        $this->shareRepository = $shareRepository;
        $this->WxappConfigRepository = $WxappConfigRepository;
    }

    /**
     * 分享
     * @param int $uid
     * @return array
     *
     */
    public function Share($uid , $path = "/", $width = 430, $type = "index")
    {
        $app_name = $this->WxappConfigRepository->getWxappConfig();

        $shop_name = $app_name['0']['wx_appname'];
        $userInfo = $this->userRepository->userInfo($uid);// 分享人信息

        $result = $this->get_wxcode($path, $width);

        $rootPath = dirname(base_path());
        $imgDir = $rootPath. "/data/gallery_album/ewm/";
        if (!is_dir($imgDir)) {
            mkdir($imgDir);
        }
        $qrcode = $imgDir . $type . '_' . $uid .'.png';
        file_put_contents($qrcode, $result);

        $rootPath = app('request')->root();
        $rootPath = dirname(dirname($rootPath)) . '/';

        $image_name = $rootPath."data/gallery_album/ewm/" . basename($qrcode);

        $share = [
            'name' => $userInfo['nick_name'],  //分享人名字
            'id' => $userInfo['id'],  //分享人ID
            'pic' => get_image_path($userInfo['user_picture']),   //分享人头像
            'shop_name' => $shop_name,        //店铺名字
            'image_name' => get_image_path($image_name)
        ];

        return $share;
    }

    private function get_wxcode($path, $width)
    {
        $config = [
            'appid' => $this->WxappConfigRepository->getWxappConfigByCode('wx_appid'),
            'secret' => $this->WxappConfigRepository->getWxappConfigByCode('wx_appsecret'),
        ];

        $wxapp = new Wxapp($config);

        $result = $wxapp->getWxaCode($path, $width, true);
        if (empty($result)) {
            return false;
        }

        return $result;
    }


}
