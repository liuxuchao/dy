<?php

namespace App\Services;

use App\Models\OrderInfo;
use App\Repositories\Cart\CartRepository;
use App\Repositories\Coupons\CouponsRepository;
use App\Repositories\Goods\GoodsRepository;
use App\Repositories\Order\OrderRepository;
use App\Repositories\User\AccountRepository;
use App\Repositories\User\AddressRepository;
use App\Repositories\User\InvoiceRepository;
use App\Repositories\Region\RegionRepository;
use App\Repositories\Payment\PayLogRepository;
use App\Repositories\Payment\PaymentRepository;
use App\Repositories\Product\ProductRepository;
use App\Repositories\Order\OrderGoodsRepository;
use App\Repositories\Shipping\ShippingRepository;
use App\Repositories\Order\OrderInvoiceRepository;
use App\Repositories\Shop\ShopRepository;
use App\Repositories\ShopConfig\ShopConfigRepository;
use App\Repositories\Bargain\BargainRepository;
use App\Repositories\Team\TeamRepository;
use App\Repositories\Drp\DrpRepository;
use App\Repositories\User\UserRepository;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class FlowService
{

    ///
    private $cartRepository;
    private $CouponsRepository;
    private $addressRepository;
    private $invoiceRepository;
    private $paymentRepository;
    private $shippingRepository;
    private $shopConfigRepository;
    private $goodsRepository;
    private $productRepository;
    private $orderRepository;
    private $orderGoodsRepository;
    private $orderInvoiceRepository;
    private $accountRepository;
    private $payLogRepository;
    private $regionRepository;
    private $shopRepository;
    private $bargainRepository;
    private $teamRepository;
    private $userRepository;
    private $userId;
    private $defaultAddress;
    private $drpRepository;

    public function __construct(
        CartRepository $cartRepository,
        CouponsRepository $couponsRepository,
        AddressRepository $addressRepository,
        InvoiceRepository $invoiceRepository,
        PaymentRepository $paymentRepository,
        ShippingRepository $shippingRepository,
        DrpRepository $drpRepository,
        UserRepository $userRepository,
        ShopConfigRepository $shopConfigRepository,
        GoodsRepository $goodsRepository,
        OrderInvoiceRepository $orderInvoiceRepository,
        ProductRepository $productRepository,
        OrderRepository $orderRepository,
        OrderGoodsRepository $orderGoodsRepository,
        AccountRepository $accountRepository,
        PayLogRepository $payLogRepository,
        RegionRepository $regionRepository,
        ShopRepository $shopRepository,
        BargainRepository $bargainRepository,
        TeamRepository $teamRepository
    )
    {
        $this->cartRepository = $cartRepository;
        $this->couponsRepository = $couponsRepository;
        $this->addressRepository = $addressRepository;
        $this->invoiceRepository = $invoiceRepository;
        $this->paymentRepository = $paymentRepository;
        $this->shippingRepository = $shippingRepository;
        $this->drpRepository = $drpRepository;
        $this->shopConfigRepository = $shopConfigRepository;
        $this->goodsRepository = $goodsRepository;
        $this->productRepository = $productRepository;
        $this->orderRepository = $orderRepository;
        $this->orderGoodsRepository = $orderGoodsRepository;
        $this->orderInvoiceRepository = $orderInvoiceRepository;
        $this->accountRepository = $accountRepository;
        $this->payLogRepository = $payLogRepository;
        $this->regionRepository = $regionRepository;
        $this->shopRepository = $shopRepository;
        $this->bargainRepository = $bargainRepository;
        $this->teamRepository = $teamRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * 订单确认信息
     * @param $userId
     * @return array
     */
    public function flowInfo($userId, $flow_type = 0, $bs_id = 0, $t_id = 0, $team_id = 0)
    {
        $result = [];
        $arrru = [];
        $cou = [];
        $this->userId = $userId;
        //商品类型
        $flow_type = isset($flow_type) ? intval($flow_type) : CART_GENERAL_GOODS;

        $this->defaultAddress = $defaultAddress = $this->addressRepository->getDefaultByUserId($userId);// 收货地址
        $result['cart_goods_list'] = $this->arrangeCartGoods($userId, $flow_type);  //购物车商品

        foreach ($result['cart_goods_list']['list'] as $key => $val) {
            $cou[$key] = $this->couponsRepository->UserCoupons($userId, true, $result['cart_goods_list']['order_total'], $val, true);  //用户优惠券
        }
        //优惠活动
        $result['discount'] = $result['cart_goods_list']['discount'];

        $cou_num = count($cou);
        if ($cou_num > 0) {
            for ($i = 0; $i < $cou_num; $i++) {
                $arrru = array_merge($arrru, $cou[$i]);
            }
            $result['coupons_list'] = $arrru;
        }

        $result['flow_type'] = $flow_type; //购物车商品类型
        //砍价返回标识
        if ($bs_id) {
            $result['bs_id'] = $bs_id; //砍价参与id
        }

        //拼团返回标识
        if ($t_id) {
            $result['t_id'] = $t_id;       //拼团活动id
            $result['team_id'] = $team_id; //拼团开团id
        }

        // 发票
        if ($this->shopConfigRepository->getShopConfigByCode('can_invoice') == '1') {
            $result['invoice_content'] = explode("\n", str_replace("\r", '', $this->shopConfigRepository->getShopConfigByCode('invoice_content')));
            if (empty($this->invoiceRepository->find($userId))) {
                $result['vat_invoice'] = '';
            } else {
                $result['vat_invoice'] = $this->invoiceRepository->find($userId);//增值发票
            }
            $result['can_invoice'] = 1;
        } else {
            $result['can_invoice'] = 0;
        }
        // 收货地址
        if (empty($defaultAddress['province']) || empty($defaultAddress['city'])) {
            $result['default_address'] = '';
        } else {
            $result['default_address'] = [
                'country' => $this->regionRepository->getRegionName($defaultAddress['country']),
                'province' => $this->regionRepository->getRegionName($defaultAddress['province']),
                'city' => $this->regionRepository->getRegionName($defaultAddress['city']),
                'district' => $this->regionRepository->getRegionName($defaultAddress['district']),
                'address' => $defaultAddress['address'],
                'address_id' => $defaultAddress['address_id'],
                'consignee' => $defaultAddress['consignee'],
                'mobile' => $defaultAddress['mobile'],
                'user_id' => $defaultAddress['user_id'],
            ];
        }
        // 收货地址end

//        $result['payment_list'] = $this->paymentRepository->paymentList();   //支付方式列表

        return $result;
    }

    /**
     * 使用优惠券
     * @param $userId
     * @param $cou_id
     * @return array
     */
    public function changeCou($uc_id, $userId, $flow_type = 0)
    {

        /* 获得收货人信息 */
        $this->defaultAddress = $defaultAddress = $this->addressRepository->getDefaultByUserId($userId);
        /* 对商品信息赋值 */
        $cart_goods_list = $this->arrangeCartGoods($userId, $flow_type);  //购物车商品

        if (empty($cart_goods_list)) {
            $result['error'] = "购物车中无商品";
        } else {
            /* 获取优惠券信息 */
            $coupons_info = $this->couponsRepository->getcoupons($userId, $uc_id, ['c.cou_id', 'c.cou_man', 'c.cou_type', 'c.ru_id', 'c.cou_money', 'cu.uc_id', 'cu.user_id']);

            $consignee['province_name'] = $this->regionRepository->getRegionName($this->defaultAddress['province']);
            $consignee['city_name'] = $this->regionRepository->getRegionName($this->defaultAddress['city']);
            $consignee['district_name'] = $this->regionRepository->getRegionName($this->defaultAddress['district']);
            $consignee['street'] = $this->regionRepository->getRegionName($this->defaultAddress['street']);//街道
            $consignee['consignee_address'] = $consignee['province_name'] . $consignee['city_name'] . $consignee['district_name'] . $this->defaultAddress['address'] . $this->defaultAddress['street'];

            /* 优惠券 免邮 start */
            $not_freightfree = 0;
            if (!empty($coupons_info) && $cart_goods_list) {
                if ($coupons_info['cou_type'] == 5) {

                    $region = $this->couponsRepository->getcouponsregion($coupons_info['cou_id']);
                    $cou_region = $region[0];
                    $cou_region = !empty($cou_region) ? explode(',', $cou_region['region_list']) : [];

                    /* 是否含有不支持免邮的地区 */
                    if ($cou_region && in_array($this->defaultAddress['province'], $cou_region)) {
                        $not_freightfree = 1;
                    }
                } else {
                    if ($cart_goods_list['order_total'] > $coupons_info['cou_money']) {
                        $result['cou_money'] = $coupons_info['cou_money'];
                        $result['order_total'] = $cart_goods_list['order_total'] - $coupons_info['cou_money'];
                        $result['order_total_formated'] = price_format($result['order_total']);
                    }
                }
            }
            $result['cou_type'] = $coupons_info['cou_type'];
            $result['not_freightfree'] = $not_freightfree;
            /* 优惠券 免邮 end */

            $result['cou_id'] = $uc_id;
        }

        return $result;
    }

    /**
     * 整理购物车商品数据
     * @param $userId
     * @return array
     */
    private function arrangeCartGoods($userId, $flow_type)
    {
        $cartGoodsList = $this->cartRepository->getGoodsInCartByUser($userId, $flow_type);  //购物车商品
        $list = [];
        $totalAmount = $cartGoodsList['total']['goods_price'];   //订单总价
        $discount = price_format($cartGoodsList['total']['discount'], false);   //订单总价

        foreach ($cartGoodsList['goods_list'] as $k => $v) {
            if (!isset($total[$v['ru_id']])) {
                $total[$v['ru_id']] = 0;
            }

            $totalPrice = empty($total[$v['ru_id']]['price']) ? 0 : $total[$v['ru_id']]['price'];
            $totalNumber = empty($total[$v['ru_id']]['number']) ? 0 : $total[$v['ru_id']]['number'];
            $cart_value = '';
            foreach ($v['goods'] as $key => $value) {
                $totalPrice += $value["goods_price"] * $value['goods_number'];
                $totalNumber += $value["goods_number"];

                $cart_value = $cart_value . ',' . $value['rec_id'];
                $list[$v['ru_id']]['shop_list'][$key] = [
                    'rec_id' => $value['rec_id'],
                    'user_id' => $v['user_id'],
                    'cat_id' => $value['cat_id'],
                    'goods_id' => $value['goods_id'],
                    'goods_name' => $value['goods_name'],
                    'ru_id' => $v['ru_id'],
                    'shop_name' => $v['shop_name'],
                    'market_price' => strip_tags($value['market_price']),
                    'market_price_formated' => price_format($value['market_price'], false),
                    'goods_price' => strip_tags($value['goods_price']),
                    'goods_price_formated' => price_format($value['goods_price'], false),
                    'goods_number' => $value['goods_number'],
                    'goods_thumb' => get_image_path($value['goods_thumb']),
                    'goods_attr' => $value['goods_attr']
                ];
            }
            $cart_value = substr($cart_value, 1);
            // 商家配送方式
            $shippingList = $this->getRuShippngInfo($v['goods'], $cart_value, $v['ru_id']);
            $list[$v['ru_id']]['shop_info'] = [];
            foreach ($shippingList['shipping_list'] as $key => $value) {
                $list[$v['ru_id']]['shop_info'][] = [
                    'shipping_id' => $value['shipping_id'],
                    'shipping_name' => $value['shipping_name'],
                    'ru_id' => $v['ru_id'],
                ];
            }

//            $list[$v['ru_id']]['is_freight'] = $shippingList['is_freight'];
            //

            $list[$v['ru_id']]['total'] = [
                'price' => $totalPrice,
                'price_formated' => price_format($totalPrice, false),
                'number' => $totalNumber
            ];
        }
        unset($cartGoodsList);
        $totalAmount = strip_tags(preg_replace('/([\x80-\xff]*|[a-zA-Z])/i', '', $totalAmount));  //格式化总价
        //
        sort($list);

        return ['list' => $list, 'order_total' => round($totalAmount,2), 'order_total_formated' => price_format($totalAmount, false),'discount' =>$discount];
    }

    /**
     * 提交订单
     * @param $args
     * @return array
     */
    public function submitOrder($args)
    {
        // 检查登录状态
        $userId = $args['uid'];
        app('config')->set('uid', $userId);
        $time = gmtime();
        //商品类型
        $flow_type = isset($args['flow_type']) ? intval($args['flow_type']) : CART_GENERAL_GOODS;

        // 检查购物车商品
        $goodsNum = $this->cartRepository->goodsNumInCartByUser($userId, $flow_type);

        if (empty($goodsNum)) {
            return ['error' => 1, 'msg' => '购物车没有商品'];
        }

        /**
         * 检查商品库存
         * 如果使用库存，且下订单时减库存，则减少库存
         */
        if ($this->shopConfigRepository->getShopConfigByCode('use_storage') == 1 && $this->shopConfigRepository->getShopConfigByCode('stock_dec_time') == 1) {
            $cart_goods = $this->cartRepository->getGoodsInCartByUser($userId, $flow_type);
            $_cart_goods_stock = [];
            foreach ($cart_goods['goods_list'] as $value) {
                foreach ($value['goods'] as $goodsValue) {
                    $_cart_goods_stock[$goodsValue['rec_id']] = $goodsValue['goods_number'];
                }
            }

            // 检查库存
            if (!$this->flow_cart_stock($_cart_goods_stock)) {
                return ['error' => 1, 'msg' => '库存不足'];
            }
            unset($cart_goods_stock, $_cart_goods_stock);
        }
        // 查询收货人信息
        $consignee = $args['consignee'];

        $consignee_info = $this->addressRepository->find($consignee);
        if (empty($consignee_info)) {
            return ['error' => 1, 'msg' => 'not find consignee'];
        }

        // 配送方式
        $shipping = $this->generateShipping($args['shipping']);
        // *****
        // 预订单
        $order = [
            'shipping_id' => empty($shipping['shipping_id']) ? 0 : $shipping['shipping_id'],
            'pay_id' => intval(0),
            'surplus' => isset($args['surplus']) ? floatval($args['surplus']) : 0.00,
            'integral' => isset($score) ? intval($score) : 0,//使用的积分的数量,取用户使用积分,商品可用积分,用户拥有积分中最小者
            'tax_id' => empty($args['postdata']['tax_id']) ? 0 : $args['postdata']['tax_id'], //纳税人识别码
            'inv_payee' => trim($args['postdata']['inv_payee']),   //个人还是公司名称 ，增值发票时此值为空
            'inv_content' => empty(trim($args['postdata']['inv_content'])) ? 0 : trim($args['postdata']['inv_content']),//发票明细
            'vat_id' => empty($args['postdata']['vat_id']) ? 0 : $args['postdata']['vat_id'],//增值发票对应的id
            'invoice_type' => empty($args['postdata']['invoice_type']) ? 0 : $args['postdata']['invoice_type'],// 0普通发票，1增值发票
            'froms' => '小程序',
            'referer' => 'wxapp',
            'postscript' => @trim($args['postscript']),
            'how_oos' => '',//缺货处理
            'user_id' => $userId,
            'add_time' => $time,
            'order_status' => OS_UNCONFIRMED,
            'shipping_status' => SS_UNSHIPPED,
            'pay_status' => PS_UNPAYED,
            'agency_id' => 0,//办事处的id
        ];

        /** 扩展信息 */
        $order['extension_code'] = '';
        $order['extension_id'] = 0;
        if ($flow_type == CART_BARGAIN_GOODS) {
            $order['extension_code'] = 'bargain_buy';
        }
        if ($flow_type == CART_TEAM_GOODS) {
            $order['extension_code'] = 'team_buy';
        }

        /** 订单中的商品 */
        if (!isset($cart_goods)) {
            $cart_goods = $this->cartRepository->getGoodsInCartByUser($userId, $flow_type);
        }
        $cartGoods = $cart_goods['goods_list'];   //购物车列表

        $cart_good_ids = [];   //购物车ID集合
        foreach ($cartGoods as $k => $v) {
            foreach ($v['goods'] as $goodsValue) {
                array_push($cart_good_ids, $goodsValue['rec_id']);
            }
        }

        if (empty($cart_goods)) {
            return ['error' => 1, 'msg' => '购物车没有商品'];
        }
        /** 检查积分余额是否合法 */
        /** 检查红包是否存在 */
        /** 收货人信息 */
        $order['consignee'] = $consignee_info->consignee;
        $order['country'] = $consignee_info->country;
        $order['province'] = $consignee_info->province;
        $order['city'] = $consignee_info->city;
        $order['mobile'] = $consignee_info->mobile;
        $order['tel'] = $consignee_info->tel;
        $order['zipcode'] = $consignee_info->zipcode;
        $order['district'] = $consignee_info->district;
        $order['address'] = $consignee_info->address;

        /** 判断是不是实体商品 */
        foreach ($cartGoods as $val) {
            foreach ($val['goods'] as $v) {
                /* 统计实体商品的个数 */
                if ($v['is_real']) {
                    $is_real_good = 1;
                }
            }
        }
        //        if(isset($is_real_good))
        //        {
        //            $shipping_is_real = $this->shippingRepository->find($order['shipping_id']);
        //            if(!$shipping_is_real)
        //            {
        //                return ['error' => 1, 'msg' => '�        �送方式不正确'];
        //            }
        //        }

        /** 订单中的总额 */
        $total = $this->orderRepository->order_fee($order, $cart_goods['goods_list'], $consignee_info, $cart_good_ids, $order['shipping_id'], $consignee);

        /** 获取该订单中使用的优惠券 */
        if ($args['uc_id'] > 0) {
            $coupons = $this->couponsRepository->getcoupons($userId, $args['uc_id'], ['c.cou_id', 'c.cou_man', 'c.cou_type', 'c.ru_id', 'c.cou_money', 'cu.uc_id', 'cu.user_id']);
            $total['amount'] = $total['amount'] - $coupons['cou_money'];
            $total['goods_price'] = $total['goods_price'] - $coupons['cou_money'];
            if ($coupons['cou_type'] == 5) {
                $total['amount'] = $total['amount'] - $total['shipping_fee'];
                $total['goods_price'] = $total['goods_price'] - $total['shipping_fee'];
                $total['shipping_fee'] = 0;
            }
        }
        $order['bonus'] = isset($bonus) ? $bonus['type_money'] : '';
        $order['coupons'] = isset($coupons) ? $coupons['cou_money'] : '';
        $order['goods_amount'] = $total['goods_price'];
        $order['discount'] = $total['discount'];
        $order['surplus'] = $total['surplus'];
        $order['tax'] = $total['tax'];

        /** 配送方式 */
        if (!empty($order['shipping_id'])) {
            $order['shipping_name'] = addslashes($shipping['shipping_name']);
        }
        $order['shipping_fee'] = $total['shipping_fee'];
        $order['insure_fee'] = 0;

        /** 支付方式 */
        if ($order['pay_id'] > 0) {
            $order['pay_name'] = '微信支付';
        }
        $order['pay_name'] = '微信支付';
        $order['pay_fee'] = $total['pay_fee'];
        $order['cod_fee'] = $total['cod_fee'];

        /** 如果全部使用余额支付，检查余额是否足够 没有余额支付*/
        $order['order_amount'] = number_format($total['amount'], 2, '.', '');

        /** 如果订单金额为0（使用余额或积分或红包支付），修改订单状态为已确认、已付款 */
        if ($order['order_amount'] <= 0) {
            $order['order_status'] = OS_CONFIRMED;
            $order['confirm_time'] = $time;
            $order['pay_status'] = PS_PAYED;
            $order['pay_time'] = $time;
            $order['order_amount'] = 0;
        }

        $order['integral_money'] = $total['integral_money'];
        $order['integral'] = $total['integral'];

        $order['parent_id'] = 0;
        $order['order_sn'] = $this->getOrderSn(); //获取新订单号

        //推送贡云订单
        $car_goods = [];  //购物车商品
        foreach ($cartGoods as $goods) {
            foreach ($goods['goods'] as $k => $list) {
                $car_goods[] = $list;
            }
        }
        $requ = array();
        $cloud_order_list = array();
        $parentordersn = '';
        $requ = $this->sendCloudOrderGoods($car_goods, $order); //推送贡云订单
        if (!empty($requ)) {
            if ($requ['code'] == '10000') {
                $parentordersn = $requ['data']['result'];
                $cloud_order_list = $requ['data']['orderDetailList'];
            } else {
                //show_message($requ['message'], '', '', 'info', true);
                return ['error' => 1, 'msg' => $requ['message']];
            }
        }

        //拼团默认值
        $order['team_id'] = 0;
        $order['team_parent_id'] = 0;
        $order['team_user_id'] = 0;

        /* 插入拼团信息记录 sty */
        if ($flow_type == CART_TEAM_GOODS) {
            if ($args['team_id'] > 0) {  //参团
                $team_info = $this->teamRepository->teamIsFailure($args['team_id']);
                if ($team_info['status'] > 0) {//参与拼团人数溢出时，开启新的团
                    $team_cart_goods = $this->cartRepository->getTeamGoodsInCart($userId, $flow_type);//购物车拼团商品信息
                    // 添加参数
                    $arguments = [
                        't_id' => $args['t_id'],
                        'goods_id' => $team_cart_goods['goods_id'],//拼团商品id
                        'start_time' => gmtime(),
                        'status' => 0,
                    ];
                    //插入开团活动信息
                    $team_log_id = $this->teamRepository->addTeamLog($arguments);
                    $order['team_id'] = $team_log_id;
                    $order['team_parent_id'] = $userId;

                } else { //参团
                    $order ['team_id'] = $args['team_id'];
                    $order ['team_user_id'] = $userId;

                }
            } else { //开团
                $team_cart_goods = $this->cartRepository->getTeamGoodsInCart($userId, $flow_type);//购物车拼团商品信息
                // 添加参数
                $arguments = [
                    't_id' => $args['t_id'],
                    'goods_id' => $team_cart_goods['goods_id'],//拼团商品id
                    'start_time' => gmtime(),
                    'status' => 0,
                ];
                //插入开团活动信息
                $team_log_id = $this->teamRepository->addTeamLog($arguments);
                $order['team_id'] = $team_log_id;
                $order['team_parent_id'] = $userId;
            }

        }
        /* 插入拼团信息记录 end */

        /** 插入订单表 */
        unset($order['timestamps']);
        unset($order['perPage']);
        unset($order['incrementing']);
        unset($order['dateFormat']);
        unset($order['morphClass']);
        unset($order['exists']);
        unset($order['wasRecentlyCreated']);
        unset($order['cod_fee']);
        $order['bonus'] = !empty($order['bonus']) ? $order['bonus'] : (!empty($order['bonus_id']) ? $order['bonus_id'] : 0);

        $new_order_id = $this->orderRepository->insertGetId($order);
        $order['order_id'] = $new_order_id;   //订单ID

        $code = $this->drpRepository->drpType('drp_affiliate');
        $drp_affiliate = json_decode($code['value']);

        $parent_id = $this->drpRepository->drpUserShop($userId);//判断是否有上级并开店
        if ($parent_id) {
            $is_distribution = 1;
        } else {
            $is_distribution = 0;
        }


        /** 插入订单商品 */
        $newGoodsList = [];
        foreach ($cartGoods as $v) {
            foreach ($v['goods'] as $gv) {
                $gv['ru_id'] = $v['ru_id'];
                $gv['user_id'] = $v['user_id'];
                $gv['shop_name'] = $v['shop_name'];
                $gv['dis_commission'] = $gv['dis_commission'];
                $gv['is_distribution'] = isset($is_distribution) ? $is_distribution : 0;
                $newGoodsList[] = $gv;
            }
        }
        $this->orderGoodsRepository->insertOrderGoods($newGoodsList, $order['order_id']);

        /** 插入分销信息表drp_log */
        if (isset($is_distribution) && $is_distribution == 1) {
            $drp = [];
            $drp['order_id'] = $order['order_id'];
            $drp['time'] = gmtime();
            $drp['user_id'] = $userId;
            $user_name = $this->userRepository->$userInfo($userId);
            $drp['user_name'] = $user_name['nick_name'];
            $money = $this->drpRepository->Drpmoney($order['order_id']);

            $level = $this->drpRepository->drpType('drp_affiliate');
            $drp_level_per = $level['item'][$result['drp_level']];
            foreach ($drp_level_per as $ke => $vo) {
                $coco[$p++] = $vo;
            }
            $per = $coco;//分销商等级分成比例
            $drp_level_per = $per[0];
            $drp['money'] = round($money * $drp_level_per / 100, 2);
            $point = $this->orderRepository->integral_to_give($order['order_id']);
            $drp['point'] = round($point * $drp_level_per / 100, 2);

            $this->drpRepository->insertDrplog($drp);
        }

        //录入贡云订单信息
        if (!empty($cloud_order_list)) {
            foreach ($cloud_order_list as $k => $v) {
                $cloud_order = array();
                $cloud_order['apiordersn'] = trim($v['apiOrderSn']); //订单编号 对应贡云子订单号
                $cloud_order['parentordersn'] = trim($requ['data']['result']); //主订单号
                $cloud_order['goods_id'] = intval($v['goodId']); //商品id 对应的是贡云的商品id
                $cloud_order['user_id'] = $order['user_id']; //下单会员id
                $cloud_order['cloud_orderid'] = $v['orderId']; //贡云字订单id
                $cloud_order['cloud_detailed_id'] = $v['id']; //贡云订单明细id
                //处理价格
                $totalprice = !empty($v['totalPrice']) ? trim($v['totalPrice']) : 0;
                //分转换为元
                if ($totalprice > 0) {
                    $totalprice = $totalprice / 100;
                }
                $totalprice = floatval($totalprice);
                $cloud_order['totalprice'] = $totalprice; //总价 对应贡云价格

                $recid = $this->orderGoodsRepository->orderGoodsRecId($order['order_id'],$v['goodId']);
                $cloud_order['rec_id'] = $recid['rec_id'];

                $this->orderGoodsRepository->insertOrderCloud($cloud_order);
            }
        }



        /** 处理余额、积分、红包 */
        if ($order['user_id'] > 0 && $order['integral'] > 0) {
            $this->accountRepository->logAccountChange(0, 0, 0, $order['integral'] * (-1), trans('message.score.pay'), $order['order_sn'], $userId);
        }

        /*处理优惠券*/
        if ($args['uc_id']) {
            $coutype = $this->couponsRepository->getupcoutype($args['uc_id'], $time);
        }

        /* 修改砍价活动状态 */
        if ($order['extension_code'] == 'bargain_buy') {
            $this->bargainRepository->updateStatus($args['bs_id']);
        }

        /** 如果使用库存，且下订单时减库存，则减少库存 */
        if ($this->shopConfigRepository->getShopConfigByCode('use_storage') == '1' && $this->shopConfigRepository->getShopConfigByCode('stock_dec_time') == SDT_PLACE) {
            $this->orderRepository->changeOrderGoodsStorage($order['order_id'], true, SDT_PLACE);
        }

        /** 清空购物车 */
        $this->clear_cart_ids($cart_good_ids, $flow_type);

        /** 清除缓存，否则买了商品，但是前台页面读取缓存，商品数量不减少 */
        // clear_all_files();
        /** 插入支付日志 */

        $order['log_id'] = $this->payLogRepository->insert_pay_log($new_order_id, $order['order_amount'], 0);   //订单支付
        /** 当前用户是否已经填写过发票信息 */
        $user_invoice = $this->orderInvoiceRepository->find($userId);
        $invoice_info = [
            'tax_id' => $order['tax_id'],    // 纳税人识别码
            'inv_payee' => $order['inv_payee'],   // 公司名称
            'user_id' => $userId,
        ];
        if (!empty($user_invoice)) {
            $this->orderInvoiceRepository->updateInvoice($user_invoice['invoice_id'], $invoice_info);
        } else {
            $this->orderInvoiceRepository->addInvoice($invoice_info);
        }

        /** 主订单ID */
        $order_id = $order['order_id'];

        /** 生成子订单 */
        $shipping = [
            'shipping' => $args['shipping'],    // 配送ID 列表
            'shipping_fee_list' => isset($total['shipping_fee_list']) ? $total['shipping_fee_list'] : '',   // 配送费用列表
        ];

        if ($flow_type != CART_BARGAIN_GOODS && $flow_type != CART_TEAM_GOODS) {
            $this->childOrder($cart_goods, $order, $consignee_info, $shipping);
        }


        return $order_id;
    }

    /**
     * 取得贡云商品并推送
     */
    private function sendCloudOrderGoods($cart_goods = array(), $order = array())
    {
        //判断是否填写回调接口appkey，如果没有返回失败
        if (!$this->shopConfigRepository->getShopConfigByCode('cloud_dsc_appkey')) {
            return $requ = array();
        }

        //商品信息
        $order_request = array();
        $order_detaillist = array();
        foreach ($cart_goods as $cart_goods_key => $cart_goods_val) {
            if ($cart_goods_val['cloud_id'] > 0) {
                $arr = array();
                $arr['goodName'] = $cart_goods_val['cloud_goodsname'];//商品名称
                $arr['goodId'] = $cart_goods_val['cloud_id'];//商品id
                //获取货品id，库存id
                if ($cart_goods_val['goods_attr_id']) {
                    $goods_attr_id = explode(',', $cart_goods_val['goods_attr_id']);
                    //获取货品信息
                    $goods = $this->goodsRepository->goodsInfo($cart_goods_val['goods_id']);//商品详情
                    $products_info = $this->goodsRepository->getProductsAttrNumber($cart_goods_val['goods_id'],$goods_attr_id,0,0,$goods['model_attr']);

                    $arr['inventoryId'] = $products_info['inventoryid'];//库存id
                    $arr['productId'] = $products_info['cloud_product_id'];//货品id
                }
                $arr['quantity'] = $cart_goods_val['goods_number'];//购买数量
                $arr['deliveryWay'] = '3';//快递方式 3为快递送  上门自提不支持
                $order_detaillist[] = $arr;
            }
        }

        //初始化数据
        $requ = array();
        if (!empty($order_detaillist)) {
            $order_request['orderDetailList'] = $order_detaillist;
            $order_request['address'] = $order['address'];//地址
            $order_request['area'] = $this->regionRepository->getRegionName($order['district']);//地区
            $order_request['city'] = $this->regionRepository->getRegionName($order['city']);//城市
            $order_request['province'] = $this->regionRepository->getRegionName($order['province']);//城市
            $order_request['remark'] = $order['postscript'];//备注
            $order_request['mobile'] = intval($order['mobile']);//电话
            $order_request['payType'] = 99;//支付方式 统一用99
            $order_request['linkMan'] = $order['consignee'];//收件人
            $order_request['billType'] = !empty($order['invoice_type']) ? 2 : 1;//发票类型 2:公司，1、个人
            $order_request['billHeader'] = $order['inv_payee'];//发票抬头
            //$order_request['isBill'] = $order['need_inv'];//是否开发票
            $order_request['isBill'] = 0;//是否开发票
            $order_request['taxNumber'] = '';//税号

            if ($order_request['billType'] == 2) {
                $invoices_info = $this->invoiceRepository->find($order['user_id']);
                $order_request['billHeader'] = $invoices_info->company_name;
                $order_request['taxNumber'] = $invoices_info->tax_id;
            }

            $cloud = new \App\Services\Erp\JigonService();
            $requ = $cloud->push($order_request, $order);
            $requ = json_decode($requ, true);
        }

        return $requ;
    }

    /**
     * 确认订单 推送给贡云
     */
    public function cloudConfirmOrder($order_id)
    {
        if ($order_id > 0) {
            //获取贡云服订单号  和上次订单总额
            $cloud_order = $this->orderGoodsRepository->orderCloudInfo($order_id);
            $cloud_orders = array();
            if ($cloud_order) {
                $cloud_orders['orderSn'] = $cloud_order['parentordersn'];
                $cloud_orders['paymentFee'] = floatval($cloud_order['goods_number'] * $cloud_order['goods_price'] * 100);
                //获取支付流水号
                $loginfo = $this->payLogRepository->pay_log_info($order_id, PAY_ORDER);   //订单支付
                $cloud_orders['payId'] = $loginfo['log_id'];
                $cloud_orders['payType'] = 99;//支付方式  默认99
                $rootPath = app('request')->root();
                $rootPath = dirname(dirname($rootPath)) . '/';
                $cloud_orders['notifyUrl'] = $rootPath . "api.php?app_key=" . $this->shopConfigRepository->getShopConfigByCode('cloud_dsc_appkey') . "&method=dsc.order.confirmorder.post&format=json&interface_type=1";

                $cloud = new \App\Services\Erp\JigonService();
                $cloud->confirm($cloud_orders);
            }
        }
    }

    /**
     * 组装配送方式
     * @param $arr
     * @return array
     */
    private function generateShipping($arr)
    {
        $return = [];
        $str = [];
        foreach ($arr as $k => $v) {
            $return[] = implode('|', array_values($v));

            $shippingId = $v['shipping_id'];
            $shipping = $this->shippingRepository->find($shippingId);

            $str[] = implode('|', [$v['ru_id'], $shipping['shipping_name']]);
        }
        return ['shipping_id' => implode(',', $return), 'shipping_name' => implode(',', $str)];
    }

    /**
     * 查询商家默认配送方式
     * lib_order.php get_ru_shippng_info
     * $cart_goods   购物车商品
     * $cart_value   购物车ID  10,8
     * $ru_id   商家id 0
     * $consignee   收货人信息
     */
    public function getRuShippngInfo($cart_goods, $cart_value, $ru_id, $userId = 0)
    {

        //分离商家信息by wu start
        $cart_value_arr = array();
        $cart_freight = array();
        $freight = '';
        foreach ($cart_goods as $cgk => $cgv) {
            if ($cgv['ru_id'] != $ru_id) {
                unset($cart_goods[$cgk]);
            } else {
                $cart_value_list = explode(',', $cart_value);
                if (in_array($cgv['rec_id'], $cart_value_list)) {
                    $cart_value_arr[] = $cgv['rec_id'];

                    if ($cgv['freight'] == 2) {
                        @$cart_freight[$cgv['rec_id']][$cgv['freight']] = $cgv['tid'];
                    }

                    $freight .= $cgv['freight'] . ",";
                }
            }
        }
        if ($freight) {
            $freight = get_del_str_comma($freight);
        }
        $is_freight = 0;
        if ($freight) {
            $freight = explode(",", $freight);
            $freight = array_unique($freight);

            /**
             * 判断是否有《地区运费》
             */
            if (in_array(2, $freight)) {
                $is_freight = 1;
            }
        }

        $cart_value = implode(',', $cart_value_arr);
        //分离商家信息by wu end

        $sess_id = " user_id = '" . (empty($this->userId) ? app('config')->get('uid') : $this->userId) . "' ";
        /* 取得购物类型 */
        $order['shipping_id'] = 0;   // 初始化配送ID
        $seller_shipping = $this->shippingRepository->getSellerShippingType($ru_id);
        $shipping_id = isset($seller_shipping['shipping_id']) ? $seller_shipping['shipping_id'] : 0;

        if (empty($this->defaultAddress)) {
            $uid = app('config')->get('uid');
            $this->defaultAddress = $this->addressRepository->getDefaultByUserId($uid);// 收货地址
        }


        $consignee = $this->defaultAddress;

        $consignee['street'] = isset($consignee['street']) ? $consignee['street'] : 0;
        $region = array($consignee['country'], $consignee['province'], $consignee['city'], $consignee['district'], $consignee['street']);

        $insure_disabled = true;
        $cod_disabled = true;

        $where = '';
        if ($cart_value) {
            $where .= " AND rec_id IN($cart_value)";
        }

        // 查看购物车中是否全为免运费商品，若是则把运费赋为零
        $shipping_count = $this->cartRepository->fee_goods($sess_id, $ru_id, $where);
        $shipping_list = array();
        $shipping_list1 = array();
        $shipping_list2 = array();
        $configure_value = 0;
        $configure_type = 0;
        $prefix = Config::get('database.connections.mysql.prefix');

        if ($is_freight) {
            if ($cart_freight) {
                $list1 = array();
                $list2 = array();
                foreach ($cart_freight as $key => $row) {

                    if (isset($row[2]) && $row[2]) {
                        $transport_list = $this->goodsRepository->getTransport($row[2]);

                        foreach ($transport_list as $tkey => $trow) {
                            if ($trow['freight_type'] == 1) {
                                $sql = "SELECT s.shipping_id, s.shipping_code, s.shipping_name, shipping_order FROM {$prefix}shipping AS s " .
                                    " LEFT JOIN {$prefix}goods_transport_tpl AS gtt ON s.shipping_id = gtt.shipping_id" .
                                    " WHERE gtt.user_id = '$ru_id' AND s.enabled = 1 AND gtt.tid = '" . $trow['tid'] . "'" .
                                    " AND (FIND_IN_SET('" . $region[1] . "', gtt.region_id) OR FIND_IN_SET('" . $region[2] . "', gtt.region_id) OR FIND_IN_SET('" . $region[3] . "', gtt.region_id) OR FIND_IN_SET('" . $region[4] . "', gtt.region_id))" .
                                    " GROUP BY s.shipping_id";
                                $shipping_list1 = DB::select($sql);
                                $list1[] = $shipping_list1;
                            } else {

                                $sql = "SELECT s.shipping_id, s.shipping_code, s.shipping_name, shipping_order FROM {$prefix}shipping AS s " .
                                    " LEFT JOIN {$prefix}goods_transport_extend AS gted ON gted.tid = '" . $trow['tid'] . "' AND gted.ru_id = '$ru_id'" .
                                    " LEFT JOIN {$prefix}goods_transport_express AS gte ON gted.tid = gte.tid AND gte.ru_id = '$ru_id'" .
                                    " WHERE FIND_IN_SET(s.shipping_id, gte.shipping_id) " .
                                    " AND ((FIND_IN_SET('" . $region[1] . "', gted.top_area_id)) OR (FIND_IN_SET('" . $region[2] . "', gted.area_id) OR FIND_IN_SET('" . $region[3] . "', gted.area_id) OR FIND_IN_SET('" . $region[4] . "', gted.area_id)))" .
                                    " GROUP BY s.shipping_id";
                                $shipping_list2 = DB::select($sql);
                                $list2[] = $shipping_list2;

                            }
                        }
                    }
                }

                $shipping_list1 = get_three_to_two_array($list1);
                $shipping_list2 = get_three_to_two_array($list2);

                if ($shipping_list1 && $shipping_list2) {
                    $shipping_list = array_merge($shipping_list1, $shipping_list2);
                } elseif ($shipping_list1) {
                    $shipping_list = $shipping_list1;
                } elseif ($shipping_list2) {
                    $shipping_list = $shipping_list2;
                }
                foreach ($shipping_list as $k => $v) {
                    $shipping_list[$k] = json_decode(json_encode($v), 1);
                }

                if ($shipping_list) {
                    //去掉重复配送方式 start
                    $new_shipping = array();
                    foreach ($shipping_list as $key => $val) {
                        @$new_shipping[$val['shipping_code']][] = $key;
                    }

                    foreach ($new_shipping as $key => $val) {
                        if (count($val) > 1) {
                            for ($i = 1; $i < count($val); $i++) {
                                unset($shipping_list[$val[$i]]);
                            }
                        }
                    }
                    //去掉重复配送方式 end

                    $shipping_list = get_array_sort($shipping_list, 'shipping_order');
                }
            }

            if ($shipping_list) {
                foreach ($shipping_list as $key => $val) {
                    if (substr($val['shipping_code'], 0, 5) != 'ship_') {
                        $freightModel = $this->shopConfigRepository->getShopConfigByCode('freight_model');
                        if ($freightModel == 0) {

                            /* 商品单独设置运费价格 start */
                            if ($cart_goods) {
                                if (count($cart_goods) == 1) {

                                    $cart_goods = array_values($cart_goods);

                                    if (!empty($cart_goods[0]['freight']) && $cart_goods[0]['is_shipping'] == 0) {

                                        if ($cart_goods[0]['freight'] == 1) {
                                            $configure_value = $cart_goods[0]['shipping_fee'] * $cart_goods[0]['goods_number'];
                                        } else {

                                            $trow = $this->goodsRepository->getGoodsTransport($cart_goods[0]['tid']);

                                            if ($trow['freight_type']) {

                                                $cart_goods[0]['user_id'] = $cart_goods[0]['ru_id'];
                                                $transport_tpl = $this->get_goods_transport_tpl($cart_goods[0], $region, $val, $cart_goods[0]['goods_number']);

                                                $configure_value = isset($transport_tpl['shippingFee']) ? $transport_tpl['shippingFee'] : 0;
                                            } else {
                                                $transport = array('top_area_id', 'area_id', 'tid', 'ru_id', 'sprice');
                                                $transport_where = " AND ru_id = '" . $cart_goods[0]['ru_id'] . "' AND tid = '" . $cart_goods[0]['tid'] . "'";

                                                $goods_transport = $this->shopRepository->get_select_find_in_set(2, $consignee['city'], $transport, $transport_where, 'goods_transport_extend', 'area_id');


                                                $ship_transport = array('tid', 'ru_id', 'shipping_fee');
                                                $ship_transport_where = " AND ru_id = '" . $cart_goods[0]['ru_id'] . "' AND tid = '" . $cart_goods[0]['tid'] . "'";
                                                $goods_ship_transport = $this->shopRepository->get_select_find_in_set(2, $val['shipping_id'], $ship_transport, $ship_transport_where, 'goods_transport_express', 'shipping_id');

                                                $goods_transport['sprice'] = isset($goods_transport['sprice']) ? $goods_transport['sprice'] : 0;
                                                $goods_ship_transport['shipping_fee'] = isset($goods_ship_transport['shipping_fee']) ? $goods_ship_transport['shipping_fee'] : 0;

                                                if ($trow['type'] == 1) {
                                                    $configure_value = $goods_transport['sprice'] * $cart_goods[0]['goods_number'] + $goods_ship_transport['shipping_fee'] * $cart_goods[0]['goods_number'];
                                                } else {
                                                    $configure_value = $goods_transport['sprice'] + $goods_ship_transport['shipping_fee'];
                                                }
                                            }
                                        }
                                    } else {
                                        /* 有配送按配送区域计算运费 */
                                        $configure_type = 1;
                                    }
                                } else {
                                    $order_transpor = get_order_transport($cart_goods, $consignee, $val['shipping_id'], $val['shipping_code']);

                                    if ($order_transpor['freight']) {
                                        /* 有配送按配送区域计算运费 */
                                        $configure_type = 1;
                                    }

                                    $configure_value = isset($order_transpor['sprice']) ? $order_transpor['sprice'] : 0;
                                }
                            }
                            /* 商品单独设置运费价格 end */

                            $shipping_fee = $shipping_count == 0 ? 0 : $configure_value;
                            $shipping_list[$key]['free_money'] = price_format(0, false);
                        }

                        $shipping_list[$key]['shipping_id'] = $val['shipping_id'];
                        $shipping_list[$key]['shipping_name'] = $val['shipping_name'];
                        $shipping_list[$key]['shipping_code'] = $val['shipping_code'];
                        $shipping_list[$key]['format_shipping_fee'] = price_format($shipping_fee, false);
                        $shipping_list[$key]['shipping_fee'] = $shipping_fee;

                        if (isset($val['insure']) && $val['insure']) {
                            $shipping_list[$key]['insure_formated'] = strpos($val['insure'], '%') === false ? price_format($val['insure'], false) : $val['insure'];
                        }

                        /* 当前的配送方式是否支持保价 */
                        if ($val['shipping_id'] == $order['shipping_id']) {

                            if (isset($val['insure']) && $val['insure']) {
                                $insure_disabled = ($val['insure'] == 0);
                            }
                            if (isset($val['support_cod']) && $val['support_cod']) {
                                $cod_disabled = ($val['support_cod'] == 0);
                            }
                        }

                        //默认配送方式
                        $shipping_list[$key]['default'] = 0;
                        if ($shipping_id == $val['shipping_id']) {
                            $shipping_list[$key]['default'] = 1;
                        }

                        $shipping_list[$key]['insure_disabled'] = $insure_disabled;
                        $shipping_list[$key]['cod_disabled'] = $cod_disabled;
                    }

                    // 兼容过滤ecjia配送方式
                    if (substr($val['shipping_code'], 0, 5) == 'ship_') {
                        unset($shipping_list[$key]);
                    }
                }

                //去掉重复配送方式 by wu start
                $shipping_type = array();
                foreach ($shipping_list as $key => $val) {
                    @$shipping_type[$val['shipping_code']][] = $key;
                }

                foreach ($shipping_type as $key => $val) {
                    if (count($val) > 1) {
                        for ($i = 1; $i < count($val); $i++) {
                            unset($shipping_list[$val[$i]]);
                        }
                    }
                }
                //去掉重复配送方式 by wu end
            }
        } else {

            /* 商品单独设置运费价格 start */
            if ($cart_goods) {
                if (count($cart_goods) == 1) {

                    $cart_goods = array_values($cart_goods);

                    if (!empty($cart_goods[0]['freight']) && $cart_goods[0]['is_shipping'] == 0) {

                        $configure_value = $cart_goods[0]['shipping_fee'] * $cart_goods[0]['goods_number'];
                    } else {
                        /* 有配送按配送区域计算运费 */
                        $configure_type = 1;
                    }
                } else {

                    $sprice = 0;
                    foreach ($cart_goods as $key => $row) {
                        if ($row['is_shipping'] == 0) {
                            $sprice += $row['shipping_fee'] * $row['goods_number'];
                        }
                    }

                    $configure_value = $sprice;
                }
            }
            /* 商品单独设置运费价格 end */

            $shipping_fee = $shipping_count == 0 ? 0 : $configure_value;
            $shipping_list[0]['free_money'] = price_format(0, false);
            $shipping_list[0]['format_shipping_fee'] = price_format($shipping_fee, false);
            $shipping_list[0]['shipping_fee'] = $shipping_fee;
            $shipping_list[0]['shipping_id'] = isset($seller_shipping['shipping_id']) && !empty($seller_shipping['shipping_id']) ? $seller_shipping['shipping_id'] : 0;
            $shipping_list[0]['shipping_name'] = isset($seller_shipping['shipping_name']) && !empty($seller_shipping['shipping_name']) ? $seller_shipping['shipping_name'] : '';
            $shipping_list[0]['shipping_code'] = isset($seller_shipping['shipping_code']) && !empty($seller_shipping['shipping_code']) ? $seller_shipping['shipping_code'] : '';
            $shipping_list[0]['default'] = 1;
        }

        $arr = array('is_freight' => $is_freight, 'shipping_list' => $shipping_list);
        return $arr;
    }

    /**
     * 商品地区运费模板
     */
    private function get_goods_transport_tpl($goodsInfo = array(), $region = array(), $shippingInfo = array(), $goods_number = 1)
    {

        $goodsInfo['goods_weight'] = isset($goodsInfo['goods_weight']) ? $goodsInfo['goods_weight'] : $goodsInfo['goodsweight'];
        $goodsInfo['shop_price'] = isset($goodsInfo['shop_price']) ? $goodsInfo['shop_price'] : $goodsInfo['goods_price'];
        $prefix = Config::get('database.connections.mysql.prefix');

        if (empty($shippingInfo)) {

            $is_goods = 1;

            /**
             * 商品详情显示
             */
            //查询商家设置送方式
            $shippingInfo = get_seller_shipping_type($goodsInfo['user_id']);
            if (!$shippingInfo) {
                $tpl_shipping = $this->get_goods_transport_tpl_shipping($goodsInfo['tid'], 0, $region);
                if ($tpl_shipping) {
                    $shippingInfo = $tpl_shipping[0];
                }
            } else {
                $shippingInfo = $this->get_goods_transport_tpl_shipping($goodsInfo['tid'], $shippingInfo['shipping_id'], $region);
            }
        } else {

            $is_goods = 0;

            /**
             * 购物车显示/订单分单
             */
            $shippingInfo = $this->get_goods_transport_tpl_shipping($goodsInfo['tid'], $shippingInfo['shipping_id'], $region);
        }

        $where = '';
        if ($shippingInfo && $shippingInfo['shipping_id']) {
            $where .= " AND s.shipping_id = '" . $shippingInfo['shipping_id'] . "'";
        } else {
            $shippingInfo = $this->get_goods_transport_tpl_shipping($goodsInfo['tid'], 0, $region, $is_goods);

            if ($shippingInfo) {
                $shippingInfo = isset($shippingInfo[0]) ? $shippingInfo[0] : array();
            }
        }

        //获取配送区域
        $sql = "SELECT gtt.*, s.shipping_id, s.shipping_code, s.shipping_name, " .
            "s.shipping_desc, s.insure, s.support_cod, gtt.configure FROM {$prefix}shipping AS s, " .
            "{$prefix}goods_transport_tpl AS gtt " .
            " WHERE gtt.shipping_id = s.shipping_id " . $where .
            " AND s.enabled = 1 AND gtt.user_id = '" . $goodsInfo['user_id'] . "' AND gtt.tid = '" . $goodsInfo['tid'] . "'" .
            " AND (FIND_IN_SET('" . $region[1] . "', gtt.region_id) OR FIND_IN_SET('" . $region[2] . "', gtt.region_id) OR FIND_IN_SET('" . $region[3] . "', gtt.region_id) OR FIND_IN_SET('" . $region[4] . "', gtt.region_id))" .
            " LIMIT 1";

        $val = DB::select($sql);

        if (count($val) > 0) {
            $val = $val[0];
        } else {
            $val = [];
        }
        $val = get_object_vars($val);


        //是否支持配送
        $is_shipping = 0;
        if ($val) {
            $is_shipping = 1;
        }

        if (!$shippingInfo) {
            $shippingInfo = array(
                'shipping_id' => 0,
                'shipping_code' => '',
                'configure' => '',
            );
        }

        $shippingFee = 0;
        if ($is_shipping) {
            $goods_weight = $goodsInfo['goods_weight'] * $goods_number;
            $shop_price = $goodsInfo['shop_price'] * $goods_number;
            $shippingFee = shipping_fee($shippingInfo['shipping_code'], $shippingInfo['configure'], $goods_weight, $shop_price, $goods_number);
            $shippingCfg = unserialize_config($shippingInfo['configure']);
            $free_money = price_format($shippingCfg['free_money'], false);
        }

        $arr = array(
            'shippingFee' => $shippingFee,
            'shipping_fee_formated' => price_format($shippingFee, false),
            'is_shipping' => $is_shipping,
            'shipping_id' => $shippingInfo['shipping_id']  //购物流程需要
        );

        return $arr;
    }

    /**
     * 获取商品运费模板的运费方式
     */
    private function get_goods_transport_tpl_shipping($tid = 0, $shipping_id = 0, $region = array(), $type = 0, $limit = 0)
    {

        $where = "";
        if ($shipping_id) {
            $where .= " AND gtt.shipping_id = '$shipping_id'";
        }

        if ($limit) {
            $where .= " LIMIT " . $limit;
        }

        $prefix = Config::get('database.connections.mysql.prefix');

        $sql = "SELECT gtt.*, s.shipping_name, s.shipping_code FROM {$prefix}goods_transport_tpl AS gtt" .
            " LEFT JOIN {$prefix}shipping AS s ON gtt.shipping_id = s.shipping_id" .
            " WHERE gtt.tid = '$tid' $where";

        $arr = array();
        if ($type == 1) {
//            $res = $GLOBALS['db']->getAll($sql);
            $res = DB::select($sql);

            foreach ($res as $key => $row) {
                $row = get_object_vars($row);
                $region_id = !empty($row['region_id']) ? explode(",", $row['region_id']) : array();

                if ($region) {
                    foreach ($region as $rk => $rrow) {
                        if ($region_id && in_array($rrow, $region_id)) {
                            $arr[] = $row;
                        } else {
                            continue;
                        }
                    }
                }
            }
        } else {
//            $res = $GLOBALS['db']->getRow($sql);
            $res = DB::select($sql);

            foreach ($res as $key => $row) {
                $res = get_object_vars($row);
                $region_id = !empty($res['region_id']) ? explode(",", $res['region_id']) : array();

                foreach ($region as $rk => $rrow) {
                    if ($region_id && in_array($rrow, $region_id)) {
                        return $res;
                    }
                }
            }
        }

        return $arr;
    }

    /**
     * 生成子订单
     * @param $cartGoods
     * @param $order
     * @param $consigneeInfo
     * @param $shipping
     */
    private function childOrder($cartGoods, $order, $consigneeInfo, $shipping)
    {
        $goodsList = $cartGoods['goods_list'];   //商品列表
        $total = $cartGoods['total'];  //商品总价信息
        $orderGoods = [];   //添加子订单商品
        $ruIds = $this->getRuIds($goodsList);

        if (count($ruIds) <= 0) {
            return;
        }

        // 商品配送方式
        $newShippingArr = [];
        foreach ($shipping['shipping'] as $v) {
            $newShippingArr[$v['ru_id']] = $v['shipping_id'];
        }

        // 商品配送费用
        $newShippingFeeArr = [];
        if (isset($shipping['shipping_fee_list']) && !empty($shipping['shipping_fee_list'])) {
            foreach ($shipping['shipping_fee_list'] as $k => $v) {
                $newShippingFeeArr[$k] = $v;
            }
        }

        // 配送方式名称
        $newShippingName = explode(',', $order['shipping_name']);
        $newShippingNameArr = [];
        foreach ($newShippingName as $v) {
            $temp = explode('|', $v);
            $newShippingNameArr[$temp[0]] = $temp[1];
        }

        //
        foreach ($goodsList as $key => $value) {
            $userId = 0;
            $goodsAmount = 0;
            $orderAmount = 0;
            $newOrder = [];
            $orderGoods = [];

            $shipping_fee = (empty($newShippingFeeArr[$value['ru_id']]) || !isset($newShippingFeeArr[$value['ru_id']])) ? 0 : $newShippingFeeArr[$value['ru_id']];
            //计算折扣
            $discount = $this->cartRepository->childOrderDiscount($value['goods']);

            foreach ($value['goods'] as $v) {
                if ($v['ru_id'] != $value['ru_id']) {
                    continue;
                }
                $userId = $value['user_id'];
                $goodsAmount += $v['goods_number'] * $v['goods_price'];
            }
            $orderAmount = $goodsAmount + $shipping_fee - $order['coupons'] - $discount;
            $newOrder = [
                'main_order_id' => $order['order_id'],
                'order_sn' => $this->getOrderSn(), //获取新订单号
                'user_id' => $userId,
                'shipping_id' => $newShippingArr[$value['ru_id']],
                'shipping_name' => $newShippingNameArr[$value['ru_id']],
                'shipping_fee' => $shipping_fee,
                'pay_id' => $order['pay_id'],
                'pay_name' => '微信支付',
                'goods_amount' => $goodsAmount,
                'order_amount' => $orderAmount,
                'add_time' => gmtime(),
                'order_status' => $order['order_status'],
                'shipping_status' => $order['shipping_status'],
                'pay_status' => $order['pay_status'],
                'tax_id' => $order['tax_id'], //纳税人识别码
                'inv_payee' => $order['inv_payee'],   //个人还是公司名称 ，增值发票时此值为空
                'inv_content' => $order['inv_content'],//发票明细
                'vat_id' => $order['vat_id'],//增值发票对应的id
                'invoice_type' => $order['invoice_type'],// 0普通发票，1增值发票
                'froms' => '微信小程序',
                'referer' => 'wxapp',
                'coupons' => $order['coupons'],
                'discount' => $discount,
                'consignee' => $consigneeInfo->consignee,
                'country' => $consigneeInfo->country,
                'province' => $consigneeInfo->province,
                'city' => $consigneeInfo->city,
                'mobile' => $consigneeInfo->mobile,
                'tel' => $consigneeInfo->tel,
                'zipcode' => $consigneeInfo->zipcode,
                'district' => $consigneeInfo->district,
                'address' => $consigneeInfo->address,
                'extension_code' => $order['extension_code'],
                'team_id' => $order['team_id'],
                'team_parent_id' => $order['team_parent_id'],
                'team_user_id' => $order['team_user_id'],
            ];

            $new_order_id = $this->orderRepository->insertGetId($newOrder);   //插入订单
            foreach ($value['goods'] as $v) {
                if ($v['ru_id'] != $value['ru_id']) {
                    continue;
                }
                // 订单商品
                $orderGoods[] = [
                    'order_id' => $new_order_id,
                    'goods_id' => $v['goods_id'],
                    'goods_name' => $v['goods_name'],
                    'goods_sn' => $v['goods_sn'],
                    'product_id' => $v['product_id'],
                    'goods_number' => $v['goods_number'],
                    'market_price' => $v['market_price'],
                    'goods_price' => $v['goods_price'],
                    'goods_attr' => $v['goods_attr'],
                    'is_real' => $v['is_real'],
                    'extension_code' => $v['extension_code'],
                    'parent_id' => $v['parent_id'],
                    'is_gift' => $v['is_gift'],
                    'model_attr' => $v['model_attr'],
                    'goods_attr_id' => $v['goods_attr_id'],
                    'ru_id' => $v['ru_id'],
                    'shipping_fee' => $v['shipping_fee'],
                    'warehouse_id' => $v['warehouse_id'],
                    'area_id' => $v['area_id'],
                ];
            }
            $this->orderGoodsRepository->insertOrderGoods($orderGoods);    //添加子订单商品
        }
    }

    /**
     * 获取商品中商家ID
     * @param $cartGoods
     * @return array
     */
    private function getRuIds($cartGoods)
    {
        $arr = [];
        foreach ($cartGoods as $v) {
            if (in_array($v['ru_id'], $arr)) {
                continue;
            }
            $arr[] = $v['ru_id'];
        }

        return $arr;
    }

    /**
     * 检查订单中商品库存
     *
     * @access  public
     * @param   array $arr
     *
     * @return  void
     */
    public function flow_cart_stock($arr)
    {
        foreach ($arr as $key => $val) {
            $val = intval(make_semiangle($val));
            if ($val <= 0 || !is_numeric($key)) {
                continue;
            }

            // 根据购物车ID 找到商品
            $goods = $this->cartRepository->field(['goods_id', 'goods_attr_id', 'extension_code'])->find($key);   //

            // 商品 、 货品 信息
            $row = $this->goodsRepository->cartGoods($key);

            //系统启用了库存，检查输入的商品数量是否有效
            $goodsExtendsionCode = (empty($goods['extension_code'])) ? "" : $goods['extension_code'];
            if (intval($this->shopConfigRepository->getShopConfigByCode('use_storage')) > 0 && $goodsExtendsionCode != 'package_buy') {
                if ($row['goods_number'] < $val) {
                    return false;
                }

                /* 是货品 */
                $row['product_id'] = trim($row['product_id']);
                if (!empty($row['product_id'])) {
                    @$product_number = $this->productRepository
                        ->findBy(['goods_id' => $goods['goods_id'], 'product_id' => $row['product_id']])
                        ->column('product_number');

                    if ($product_number < $val) {
                        return false;
                    }
                }
            }
        }
        return true;
    }


    /**
     * 得到新订单号
     * @return  string
     */
    public function getOrderSn()
    {
        $time = explode(" ", microtime());
        $time = $time[1] . ($time[0] * 1000);
        $time = explode(".", $time);
        $time = isset($time[1]) ? $time[1] : 0;
        $time = date('YmdHis') + $time;

        /* 选择一个随机的方案 */
        mt_srand((double)microtime() * 1000000);
        return $time . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    /**
     * 清空指定购物车
     * @param   arr $arr 购物车id
     * @param   int $type 类型：默认普通商品
     */
    private function clear_cart_ids($arr, $type = CART_GENERAL_GOODS)
    {
        $uid = app('config')->get('uid');

        $this->cartRepository->deleteAll([
            ['in', 'rec_id', $arr],
            ['rec_type', $type],
            ['user_id', $uid],
        ]);
    }

    /**
     * 运费计算
     * @param $args
     * @return int
     */
    public function shippingFee($args)
    {
        $result = ['error' => 0, 'message' => ''];
        /* 配送方式 */
        $shippingId = isset($args['id']) ? intval($args['id']) : 0;
        $ruId = isset($args['ru_id']) ? intval($args['ru_id']) : 0;
        $address = isset($args['address']) ? intval($args['address']) : 0;
        $uc_id = isset($args['uc_id']) ? intval($args['uc_id']) : 0;
        $this->userId = $args['uid'];

        $coupons_info = $this->couponsRepository->getcoupons($this->userId, $uc_id, ['c.cou_id', 'c.cou_man', 'c.cou_type', 'c.ru_id', 'c.cou_money', 'cu.uc_id', 'cu.user_id']);

        /* 对商品信息赋值 */
        $cart_goods = $this->cartRepository->getGoodsInCartByUser($args['uid'], $args['flow_type']);

        // 切换配送方式
        $cart_goods_list = $cart_goods['product'];
        if (empty($cart_goods_list)) {
            $result['error'] = 1;
            $result['message'] = '购物车没有商品';
            return $result;
        }

        foreach ($cart_goods_list as $key => $val) {
            if ($shippingId > 0 && $val['goods']['ru_id'] == $ruId) {
                $cart_goods_list[$key]['goods']['tmp_shipping_id'] = $shippingId;
            }
        }
        /* 计算订单的费用 */
        // 收货地址、商品列表、配送方式ID
        $this->defaultAddress = $this->addressRepository->getDefaultByUserId($args['uid']);// 收货地址

        $cart_value = '';
        foreach ($cart_goods_list as $k => $v) {
            $cart_goods_list[$k] = $v['goods'];

            if ($v['goods']['ru_id'] == $ruId) {
                $cart_value = $cart_value . ',' . $v['goods']['rec_id'];
            }
        }
        $cart_value = substr($cart_value, 1);

        $shipFee = $this->getRuShippngInfo($cart_goods_list, $cart_value, $ruId);
        $shipList = $shipFee['shipping_list'];

        foreach ($shipList as $k => $v) {
            if ($v['shipping_id'] == $shippingId) {
                $shipFee = $v['shipping_fee'];
            }
        }

        if ($shipFee !== '0' || $shipFee !== 0) {
            $newShipFee = strip_tags(preg_replace('/([\x80-\xff]*|[a-zA-Z])/i', '', $shipFee));
            $result['fee'] = "0";
            if (floatval($newShipFee) > 0) {
                $result['fee'] = $newShipFee;
            }
        } else {
            $result['error'] = 1;
            $result['message'] = '该地区不支持配送';
        }
        if ($uc_id > 0) {
            $coupons_info = $this->couponsRepository->getcoupons($this->userId, $uc_id, ['c.cou_id', 'c.cou_man', 'c.cou_type', 'c.ru_id', 'c.cou_money', 'cu.uc_id', 'cu.user_id']);
            $result['cou_money'] = $coupons_info['cou_money'];

            $result['cou_type'] = $coupons_info['cou_type'];
        }

        $result['fee_formated'] = $shipFee;
        return $result;
    }

    /**
     * 提交订单返回数据
     * @param $args
     * @return mixed
     */
    public function orderDetail($args)
    {
        $main_order = $this->orderRepository->orderDetail($args['uid'], $args['main_order_id']);

        if (empty($main_order)) {
            return [];
        }

        $son_order = $this->orderRepository->orderMainDetail($args['uid'], $args['main_order_id']);

        $list = [
            'order_amount' => price_format($main_order['order_amount'], false),
            'order_amount_formated' => price_format($main_order['order_amount'], false),
            'order_sn' => $son_order,
        ];

        return $list;
    }
}
