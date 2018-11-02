<?php

namespace App\Services;

use App\Models\OrderInfo;
use App\Models\CollectGoods;
use App\Http\Proxy\ShippingProxy;
use App\Repositories\User\UserRepository;
use App\Repositories\Goods\GoodsRepository;
use App\Repositories\Order\OrderRepository;
use App\Repositories\Drp\DrpRepository;
use App\Repositories\Payment\PayLogRepository;
use App\Repositories\Store\StoreRepository;
use App\Repositories\Region\RegionRepository;
use App\Repositories\Bonus\UserBonusRepository;
use App\Repositories\Comment\CommentRepository;
use App\Repositories\Order\OrderGoodsRepository;
use App\Repositories\Goods\CollectGoodsRepository;
use App\Repositories\Store\CollectStoreRepository;
use App\Repositories\Coupons\CouponsRepository;
use App\Repositories\Wechat\WxappConfigRepository;
use App\Repositories\Category\CategoryRepository;
use App\Extensions\Wxapp;
use App\Services\Wxpay\WxPay;

class DrpService
{
    private $orderRepository;
    private $goodsRepository;
    private $drpRepository;
    private $storeRepository;
    private $collectStoreRepository;
    private $userRepository;
    private $regionRepository;
    private $userBonusRepository;
    private $collectGoodsRepository;
    private $shopService;
    private $commentRepository;
    private $payLogRepository;
    private $orderGoodsRepository;
    private $couponsRepository;
    private $shippingProxy;
    private $WxappConfigRepository;
    private $categoryRepository;

    /**
     * DrpService constructor.
     * @param OrderRepository $orderRepository
     * @param GoodsRepository $goodsRepository
     * @param UserRepository $userRepository
     * @param DrpRepository $drpRepository
     * @param RegionRepository $regionRepository
     * @param UserBonusRepository $userBonusRepository
     * @param CollectGoodsRepository $collectGoodsRepository
     * @param ShopService $shopService
     * @param CommentRepository $commentRepository
     * @param OrderGoodsRepository $orderGoodsRepository
     */
    public function __construct(
        OrderRepository $orderRepository,
        GoodsRepository $goodsRepository,
        UserRepository $userRepository,
        DrpRepository $drpRepository,
        StoreRepository $storeRepository,
        CollectStoreRepository $collectStoreRepository,
        RegionRepository $regionRepository,
        UserBonusRepository $userBonusRepository,
        CollectGoodsRepository $collectGoodsRepository,
        ShopService $shopService,
        CommentRepository $commentRepository,
        PayLogRepository $payLogRepository,
        OrderGoodsRepository $orderGoodsRepository,
        CouponsRepository $couponsRepository,
        CategoryRepository $categoryRepository,
        ShippingProxy $shippingProxy,
        WxappConfigRepository $WxappConfigRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->goodsRepository = $goodsRepository;
        $this->userRepository = $userRepository;
        $this->drpRepository = $drpRepository;
        $this->regionRepository = $regionRepository;
        $this->userBonusRepository = $userBonusRepository;
        $this->collectGoodsRepository = $collectGoodsRepository;
        $this->storeRepository = $storeRepository;
        $this->collectStoreRepository = $collectStoreRepository;
        $this->shopService = $shopService;
        $this->commentRepository = $commentRepository;
        $this->orderGoodsRepository = $orderGoodsRepository;
        $this->couponsRepository = $couponsRepository;
        $this->payLogRepository = $payLogRepository;
        $this->shippingProxy = $shippingProxy;
        $this->WxappConfigRepository = $WxappConfigRepository;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * 查看店铺
     */
    public function shop($uid)
    {
        $userinfo = $this->drpRepository->userInfo($uid);
        $res = [];
        $res['shopInfo'] = $userinfo;
        $res['userInfo'] = $this->userRepository->userInfo($uid);
        $res['userInfo']['user_picture'] = get_image_path($res['userInfo']['user_picture']);

        return $res;
    }

    /**
     * 购买店铺分销模式
     */
    public function con($uid)
    {
        $res = [];

        $isdrp = $this->drpRepository->drpType('isdrp');

        if ($isdrp == 0) {
            $res['type'] = 0;
        }

        $isbuy = $this->drpRepository->drpType('isbuy');

        if ($isbuy == 1) {//需要购买成为分销商
            $res['type'] = 1;
            return $res;
        }

        $buy_money = $this->drpRepository->drpType('buy_money');
        if ($buy_money > 0) {//需要购买金额
            $res['type'] = 2;
            $res['buy_money'] = 100;
            return $res;
        }
        $res['type'] = 3;
        return $res;
    }

    /**
     * 店铺商品展示
     */
    public function shopgoods($uid, $id, $page = 1, $size = 10, $status, $type)
    {
        $res = [];
        $corrent = ($page - 1) * $size;
        $res = $this->drpRepository->showgoods($uid, $corrent, $size, $type, $status);

        return $res;
    }

    /**
     * 店铺商品展示
     */
    public function settings($args)
    {
        $res = [];
        if (isset($args['shop_name'])) {
            if ($args['shop_portrait']) {
                $res['shop_portrait'] = $args['shop_portrait'];
            }
            $res['shop_name'] = $args['shop_name'];
            $res['real_name'] = $args['real_name'];
            $res['mobile'] = $args['mobile'];
            if ($args['qq']) {
                $res['qq'] = $args['qq'];
            }
            $res['type'] = $args['type'];
            if ($args['shop_img']) {
                $res['shop_img'] = $args['shop_img'];
            }
            $result = $this->drpRepository->settings($args['uid'], $res);
            return $result;
        } else {
            $res = $this->drpRepository->userInfo($args['uid']);
            return $res;
        }
    }

    /**
     * 购买成为分销商
     */
    public function purchase($uid)
    {
        $res = [];
        $code = $this->drpRepository->drpType('isbuy');
        if ($code != 1) {
            ecs_header("Location: " . url('drp/index/index'));
        }

        $price = $this->drpRepository->drpType('buy_money');

        $res['price'] = price_format($price);

        $novice = $this->drpRepository->drpType('novice');
        $novice = $this->htmlOut($novice);
        $res['novice'] = nl2br($novice);
        return $res;
    }

    /**
     * html代码输出
     */
    private function htmlOut($str)
    {
        if (function_exists('htmlspecialchars_decode')) {
            $str = htmlspecialchars_decode($str);
        } else {
            $str = html_entity_decode($str);
        }
        $str = stripslashes($str);
        return $str;
    }

    /**
     * 生成伪订单号
     */
    public function PurchasePay($uid)
    {
        $price = $this->drpRepository->drpType('buy_money');
        $userInfo = $this->userRepository->userInfo($args['uid']);

        $payment = $this->drpRepository->payment('wxpay');
        $order = [];
        $order['order_sn'] = $userInfo['user_id'];
        $order['user_name'] = $userInfo['user_name'];
        $order['order_amount'] = $price['value'] + $payment['pay_fee']; //计算此次预付款需要支付的总金额
        $order['log_id'] = $this->payLogRepository->insert_pay_log($order['order_sn'], $order['order_amount'], $type = PAY_REGISTERED, 0); //记录支付log
        $order['pay_code'] = $payment['pay_code'];
        // if ($order['order_amount'] > 0) {
        //     include_once(ADDONS_PATH . 'payment/' . $payment['pay_code'] . '.php');
        //     $pay_obj = new $payment['pay_code'];
        //     $pay_online = $pay_obj->get_code($order, unserialize_config($payment['pay_config']));
        //     die($pay_online);
        // }
        $new_order_id = $this->orderRepository->insertGetId($order);
        $order['order_id'] = $new_order_id;   //订单ID
        $new = [];
        $new = [
            'open_id' => $order['order_id'],
            'body' => $ruName . '订单编号' . $order['order_sn'],
            'out_trade_no' => $order['order_sn'],
            'total_fee' => $order['order_amount'], // 分
        ];
        return $this->WxPay($new);
    }

    /**
     * 微信小程序支付接口
     * @param $args
     * @return mixed
     */
    private function WxPay($args)
    {
        $wxpay = new WxPay();

        $code = 'wxpay';

        $config = [
            'app_id' => $this->WxappConfigRepository->getWxappConfigByCode('wx_appid'),
            'app_secret' => $this->WxappConfigRepository->getWxappConfigByCode('wx_appsecret'),
            'mch_key' => $this->WxappConfigRepository->getWxappConfigByCode('wx_mch_key'),
            'mch_id' => $this->WxappConfigRepository->getWxappConfigByCode('wx_mch_id'),
        ];
        $wxpay->init($config['app_id'], $config['app_secret'], $config['mch_key']);
        $nonce_str = 'ibuaiVcKdpRxkhJA';
        $time_stamp = (string)gmtime();

        $inputParams = [

            //公众账号ID
            'appid' => $config['app_id'],

            //商户号
            'mch_id' => $config['mch_id'],

            //openid
            'openid' => $args['open_id'],

            'device_info' => '1000',

            //随机字符串
            'nonce_str' => $nonce_str,

            //商品描述
            'body' => $args['body'],

            'attach' => $args['body'],

            //商户订单号
            'out_trade_no' => $args['out_trade_no'],

            //总金额
            'total_fee' => $args['total_fee'],

            //终端IP
            'spbill_create_ip' => app('request')->getClientIp(),

            //接受微信支付异步通知回调地址
            'notify_url' => Url::to('api/wx/payment/notify', ['code', $code]),

            //交易类型:JSAPI,NATIVE,APP
            'trade_type' => 'JSAPI'
        ];

        $inputParams['sign'] = $wxpay->createMd5Sign($inputParams);

        //获取prepayid
        $prepayid = $wxpay->sendPrepay($inputParams);

        $pack = 'prepay_id=' . $prepayid;

        $prePayParams = [
            'appId' => $config['app_id'],
            'timeStamp' => $time_stamp,
            'package' => $pack,
            'nonceStr' => $nonce_str,
            'signType' => 'MD5'
        ];

        //生成签名
        $sign = $wxpay->createMd5Sign($prePayParams);

        $body = [
            'appid' => $config['app_id'],
            'mch_id' => $config['mch_id'],
            'prepay_id' => $prepayid,
            'nonce_str' => $nonce_str,
            'timestamp' => $time_stamp,
            'packages' => $pack,
            'sign' => $sign,
        ];
        return ['wxpay' => $body];
    }

    /**
     * 申请开店
     * @param array $args
     * @return mixed
     */
    public function drpRegister($uid, $shopname, $realname, $mobile, $qq)
    {
        $result = [];
        //判断后台申请开店条件
        $buy_money = $this->drpRepository->drpType('buy_money');
        if ($buy_money['value'] == 1) {
            $buy = $this->drpRepository->drpType('buy');//获取设置金额
            //获取订单金额

            $money = $this->orderRepository->orderMoney($uid);

            if ($buy['value'] > $money) {
                $result['error'] = 1;
                $result['msg'] = '您的累计消费金额未达到'.$buy['value'] .'元，暂时无法开店，再接再厉';
                return $result;
            }
        }

        $judgmentdrp = $this->drpRepository->judgmentDrp($uid); //判断是否已经开店
        if ($judgmentdrp) {
            $result['error'] = 1;
            $result['msg'] = '店铺已有';
            return $result;
        }
        $judgmentshop = $this->drpRepository->judgmentShop($shopname); //判断店铺名称
        if ($judgmentshop) {
            $result['error'] = 1;
            $result['msg'] = '店铺名称已存在，请输入新的店铺名称';
            return $result;
        } else {
           $result['shopname'] = $shopname;//店铺姓名
        }

        $result['realname'] = $realname;//真实名称

        $judgmentmobile = $this->drpRepository->judgmentMobile($mobile);//判断手机号
        if ($judgmentshop) {
            $result['error'] = 1;
            $result['msg'] = '号码已存在，请输入新的号码';
            return $result;
        } else {
           $result['mobile'] = $mobile;//手机号
        }

        $result['qq'] = ($qq == 0) ? '' : $qq;

        $res = $this->drpRepository->creatDrp($uid, $result);

        if ($res) {
            $result['error'] = 0;
        } else {
            $result['error'] = 0;
        }
        return $result;
    }

    /**
     * 开店完成
     * @param array $args
     * @return mixed
     */
    public function regEnd($uid)
    {
        $result = [];

        $userinfo = $this->drpRepository->userInfo($uid);

        return $userinfo;
    }

    /**
     * 信息页面
     * @param array $args
     * @return mixed
     */
    public function index($uid)
    {
        $result = [];

        $userinfo = $this->drpRepository->userInfo($uid);
        if (!empty($userinfo)) {
            $surplus_amount = $this->drpRepository->shopMoney($uid);
            $totals = $this->drpRepository->get_drp_money(0, $uid);              //累计佣金
            $today_total = $this->drpRepository->get_drp_money(1, $uid);         //今日收入
            $total_amount = $this->drpRepository->get_drp_money(2, $uid);        //总销售额

            $buy_money = $this->drpRepository->drpType('drp_affiliate');         //分销提现比例
            $buy_money = json_decode($buy_money['value']);
            $userrank = $this->drpRepository->drp_rank_info($uid);
            $result['money_time'] = $buy_money;
            $userinfo['create_time'] = date('Y-m-d', ($userinfo['create_time'] + 28800));

            $result['userinfo'] = $userinfo;
            $result['userrank'] = $userrank;
            $result['totals'] = $totals;
            $result['surplus_amount'] = $surplus_amount['shop_money'];
            $result['today_total'] = $today_total;
            $result['total_amount'] = $total_amount;
            $result['error'] = 0;
            return $result;
        } else {
            $result['error'] = 1;
            return $result;
        }
    }

    /**
     * 分享二维码
     * @param array $args
     * @return mixed
     */
    public function userCard($uid, $path = "", $width = 430, $type = 'drp')
    {
        $shopinfo = $this->drpRepository->userInfo($uid);

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
            'name' => $shopinfo['real_name'],  //分享人名字
            'id' => $shopinfo['user_id'],  //分享人ID
            'shop_name' => $shopinfo['shop_name'],        //店铺名字
            'image_name' => $image_name
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

        $result = $wxapp->getWaCode($path, $width, false);
        if (empty($result)) {
            return false;
        }
        return $result;
    }

    /**
     * 我的团队
     * @param array $args
     * @return mixed
     */
    public function team($uid)
    {
        $parentuser = $this->drpRepository->parentInfo($uid);
        $user_info = [];
        foreach ($parentuser as $key => $v) {
            $user_info[$key]['user_id'] = $v['user_id'];
            $user_info[$key]['user_name'] = $v['nick_name'] ? $v['nick_name'] : $v['user_name'];
            $user_info[$key]['user_picture'] = get_image_path($v['user_picture']);
            $user_info[$key]['reg_time'] = date('Y-m-d ', ($v['reg_time'] + 28800));
            $user_info[$key]['money'] = $v['money'];
        }
        return $user_info;
    }

    /**
     * 团队详情
     * @param array $args
     * @return mixed
     */
    public function teamdetail($uid)
    {
        $team = $this->drpRepository->teamdetail($uid);
        $info = get_object_vars($team[0]);
        $user_nick = $this->userRepository->userInfo($info['user_id']);
        $info['headimgurl'] = get_image_path($user_nick['user_picture']);
        $info['user_name'] = $user_nick['nick_name'] ? $user_nick['nick_name'] : $user_nick['user_name'];
        $info['time'] = date('Y-m-d ', ($info['create_time'] + 28800));
        return $info;
    }

    /**
     * 下线会员
     * @param array $args
     * @return mixed
     */
    public function OfflineUser($uid)
    {
        $parentuser = $this->drpRepository->OfflineUser($uid);
        $user_info = [];
        foreach ($parentuser as $key => $v) {
            $user_info[$key]['user_id'] = $v['user_id'];
            $user_info[$key]['user_name'] = $v['nick_name'] ? $v['nick_name'] : $v['user_name'];
            $user_info[$key]['user_picture'] = get_image_path($v['user_picture']);
            $user_info[$key]['reg_time'] = date('Y-m-d', ($v['reg_time'] + 28800));
        }
        return $user_info;
    }

    /**
     * 店铺订单
     * @param array $args
     * @return mixed
     */
    public function order($uid, $page = 1, $size = 10, $status)
    {
        $res = [];

        $corrent = ($page - 1) * $size;

        $result = $this->drpRepository->order($uid, $corrent, $size, $status);
        //dump($result);exit;
        
        if ($result) {
            $level = $this->drpRepository->drpType('drp_affiliate');
            $level = unserialize($level['value']);
            
            //分销商等级列表
            $drp_user_credit = $this->drpRepository->drpUserCredit();
            //分销商等级
            $rank_info = $this->drpRepository->drp_rank_info($uid);

            foreach ($result as $key => $value) {
                $goodslist = $this->orderRepository->getOrderGoods($value['order_id']);
                $res[$key]['nickname'] = $value['nick_name'] ? $value['nick_name'] : $value['user_name'];
                $res[$key]['order_sn'] = $value['order_sn'];
                $res[$key]['order_id'] = $value['order_id'];
                $res[$key]['status'] = $value['is_separate'];
                $res[$key]['point'] = $value['point'];
                $res[$key]['money'] = $value['money'];
                $res[$key]['add_time'] = date('Y-m-d', ($value['add_time'] + 28800));

                //获取分销商所在等级
                foreach ($drp_user_credit as $key1 => $vo1) {
                    if ($vo1['id'] == $rank_info['id']) {
                        $rank = $key1;
                    }
                }
               
                $drp_level_per = $level['item'][$value['drp_level']];                
                $p = 0;
                foreach ($drp_level_per as $ke => $vo) {
                    $coco[$p++] = $vo;
                }
                $per = $coco;//分销商等级分成比例                
                $drp_level_per = ($per[$rank]);                
                foreach ($goodslist as $k => $v) {
                    $level_per = ((float)$drp_level_per) * ($v['drp_money'] / $v['goods_number'] / $v['goods_price']);
                    $goodslist[$k]['goods_thumb'] = get_image_path($v['goods_thumb']);
                }
                $res[$key]['level_per'] = round($level_per, 2) . "%";
                $res[$key]['goodslist'] = $goodslist;
                $res[$key]['goodscount'] = count($goodslist);
            }
        }
        return $res;
    }

    /**
     * 订单详情
     * @param array $args
     * @return mixed
     */
    public function orderdetail($uid, $order_id)
    {
        $res = [];
        $result = $this->drpRepository->orderDetail($uid, $order_id);
        $p = 0;
        if ($result) {
            $level = $this->drpRepository->drpType('drp_affiliate');
            $level = unserialize($level['value']);
            //分销商等级列表
            $drp_user_credit = $this->drpRepository->drpUserCredit();
            //分销商等级
            $rank_info = $this->drpRepository->drp_rank_info($uid);
            $goodslist = $this->orderRepository->getOrderGoods($result['order_id']);
            $res['nickname'] = $result['nick_name'] ? $result['nick_name'] : $result['user_name'];
            $res['order_sn'] = $result['order_sn'];
            $res['order_id'] = $result['order_id'];
            $res['status'] = $result['is_separate'];
            $res['point'] = $result['point'];
            $res['money'] = $result['money'];
            $res['add_time'] = date('Y-m-d', ($result['add_time'] + 28800));            
            //获取分销商所在等级
            foreach ($drp_user_credit as $key1 => $vo1) {
                if ($vo1['id'] == $rank_info['id']) {
                    $rank = $key1;
                }
            }
            $drp_level_per = $level['item'][$result['drp_level']];
            foreach ($drp_level_per as $ke => $vo) {
                $coco[$p++] = $vo;
            }
            $per = $coco;//分销商等级分成比例
            $drp_level_per = ($per[$rank]);
            foreach ($goodslist as $k => $v) {
                $level_per = ((float)$drp_level_per) * ($v['drp_money'] / $v['goods_number'] / $v['goods_price']);
                $goodslist[$k]['goods_thumb'] = get_image_path($v['goods_thumb']);
            }
            $res['level_per'] = round($level_per, 2) . "%";
            $res['get_per'] = round($result['goods_amount'] * $res['level_per'] / 100, 2);
            $res['goodslist'] = $goodslist;

        }
        return $res;
    }

    /**
     * 下线排行
     * @param array $args
     * @return mixed
     */
    public function ranklist($uid)
    {
        $rank = [];

        $res = $this->drpRepository->rankList($uid);

        foreach ($res as $key => $v) {
            $result[$key] = get_object_vars($v);
            $result[$key]['rank'] = $key + 1;
            $result[$key]['headimgurl'] = (!empty($result[$key]['headimgurl'])) ? $result[$key]['headimgurl'] : get_image_path($result[$key]['user_picture']);
            if ($result[$key]['user_id'] == $uid) {
                $stay = $result[$key]['rank'];
            }
        }
        $rank['result'] = isset($result) ? $result : '';
        $rank['stay'] = isset($stay) ? $stay : 1;

        return $rank;
    }

    /**
     * 分类显示
     * @param array $args
     * @return mixed
     */
    public function category($args)
    {
        if ($args['id'] > 0) {
            $list = $this->drpRepository->getCategoryGetGoods($args['uid'], $args['id']);
        } else {

            $list = $this->categoryRepository->getDrpCategorys($args['uid']);
        }
        return $list;
    }

    /**
     * 添加代言
     * @param array $args
     * @return mixed
     */
    public function add($uid, $id, $type)
    {
        $time = gmtime();
        if ($type == 2) {
            $res = $this->drpRepository->drpgoods($uid, $id);

        } elseif ($type == 1) {
            //$id = explode(',', $id);
            $res = $this->drpRepository->drpcat($uid, $id);
        }
        return $res;
    }

    /**
     * 我的代言
     * @param array $args
     * @return mixed
     */
    public function showgoods($uid, $page = 1, $size = 10, $type)
    {
        if ($type == 2 || $type == 0) {
            $corrent = ($page - 1) * $size;
            $res = $this->drpRepository->showgoods($uid, $corrent, $size, $type);
        } else {
            $res = $this->categoryRepository->getDrpCategorys($uid);
        }
        return $res;
    }

    /**
     * 佣金明细
     * @param array $args
     * @return mixed
     */
    public function drplog($uid, $page = 1, $size = 10, $status)
    {
        $corrent = ($page - 1) * $size;
        $res = $this->drpRepository->Drplog($uid, $corrent, $size, $status);

        return $res;
    }

    /**
     * 文章
     * @param array $args
     * @return mixed
     */
    public function news($uid)
    {
        $res = $this->drpRepository->News();

        $rootPath = app('request')->root();
        $rootPath = dirname(dirname($rootPath)) . '/';

        foreach ($res as $k => $v) {
            $res[$k]['content'] = str_replace(['src="/images/upload', 'src="images/upload'], 'src="' . $rootPath . '/images/upload', $res[$k]['content']);
        }

        return $res;
    }
}
