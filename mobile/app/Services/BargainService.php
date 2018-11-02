<?php

namespace App\Services;

use App\Extensions\Wxapp;
use Illuminate\Http\Request;
use App\Repositories\Shop\ShopRepository;
use App\Repositories\Bargain\BargainRepository;
use App\Repositories\Goods\GoodsRepository;
use App\Repositories\Goods\GoodsAttrRepository;
use App\Repositories\Cart\CartRepository;
use App\Repositories\Store\StoreRepository;
use App\Repositories\User\UserRepository;
use App\Repositories\Wechat\WxappConfigRepository;
use App\Services\AuthService;



/**
 * Class bargainRepository
 * @package App\Services
 */
class BargainService
{

    private $bargainRepository;
    private $shopRepository;
    private $goodsRepository;
    private $goodsAttrRepository;
    private $cartRepository;
    private $StoreRepository;
    private $userRepository;
    private $WxappConfigRepository;
    private $root_url;
    private $authService;

    /**
     * IndexService constructor.
     * @param BargainRepository $bargainRepository
     * @param ShopRepository $shopRepository
     * @param GoodsAttrRepository $goodsAttrRepository
     * @param CartRepository $cartRepository
     * @param AuthService $authService
     * @param Request $request
     */
    public function __construct(
        BargainRepository $bargainRepository,
        ShopRepository $shopRepository,
        GoodsRepository $goodsRepository,
        GoodsAttrRepository $goodsAttrRepository,
        CartRepository $cartRepository,
        StoreRepository $StoreRepository,
        UserRepository $userRepository,
        WxappConfigRepository $WxappConfigRepository,
        AuthService $authService,
        Request $request
    ){
        $this->bargainRepository = $bargainRepository;
        $this->shopRepository = $shopRepository;
        $this->goodsRepository = $goodsRepository;
        $this->goodsAttrRepository = $goodsAttrRepository;
        $this->cartRepository = $cartRepository;
        $this->StoreRepository = $StoreRepository;
        $this->userRepository = $userRepository;
        $this->WxappConfigRepository = $WxappConfigRepository;
        $this->authService = $authService;
        $this->root_url = dirname(dirname($request->root())) . '/';
    }

    /**
     * 微信小程序 砍价首页商品列表
     * @return array
     */
    public function bargainGoodsList($page = 1, $size = 10, $user_id = 0)
    {
        $page = empty($page) ? 1 : $page;

        $arr = [
            'id',             //砍价id
            'goods_id',       //商品id
            'goods_name',     //商品名
            'shop_price',     //商品价格
            'goods_thumb',    //商品图片
            'total_num',      //参与人数
            'target_price',   //砍价最底价
        ];
        $goodsList = $this->bargainRepository->findByType($user_id, '', $page, $size);  //砍价首页商品列表

        return $goodsList;
    }

    /**
     * 获取广告位
     * @return array
     */
    public function getAdsense($position_id = 0)
    {
        $shopconfig = app('App\Repositories\ShopConfig\ShopConfigRepository');
        $number = $shopconfig->getShopConfigByCode('wx_index_show_number');
        if (empty($number)) {
            $number = 10;
        }
        $adsense = $this->bargainRepository->bargainPositions($position_id, $number);  //获取广告位

        $ads = [];
        foreach ($adsense as $row) {
            if (!empty($row['position_id'])) {
                $src = (strpos($row['ad_code'], 'http://') === false && strpos($row['ad_code'], 'https://') === false) ?
                    "data/afficheimg/$row[ad_code]" : $row['ad_code'];
                $ads[] = [
                    'pic' => get_image_path($src),
                    'adsense_id' => $row['ad_id'],
                    'link' => $row['ad_link'],
                ];
            }
        }
        return $ads;
    }

     /**
     * 商品详情
     * @param $id
     * @param $user_id
     * @return array
     */
    public function goodsDetail($id = 0, $user_id = 0,$bs_id = 0)
    {
        $result = [
            'error' => 0,
            'goods_img' => '',         //商品相册
            'goods_info' => '',        //商品信息
            'bargain_info' => '',      //砍价信息
            'bargain_list' => '',      //亲友帮
            'bargain_ranking' => '',   //排行榜
            'bargain_hot' => '',       //砍价爆款
            'goods_properties' => ''   // 商品属性 规格
        ];
        $rootPath = app('request')->root();
        $rootPath = dirname(dirname($rootPath)) . '/';
        $shopconfig = app('App\Repositories\ShopConfig\ShopConfigRepository');
        $timeFormat = $shopconfig->getShopConfigByCode('time_format');
        
        $time = gmtime();
        // 商品信息
        $goodsInfo = $this->bargainRepository->goodsInfo($id);

        //初始值
        $goodsInfo['bs_id'] = 0;         //创建砍价活动id
        $goodsInfo['add_bargain'] = 0;   //是否参与当前活动
        $goodsInfo['is_add_bargain'] = 0;//互砍时是否参与当前活动
        $goodsInfo['bargain_join'] = 0;  //已砍价
        $goodsInfo['bargain_bar'] = 0 ;  //进度条
        $goodsInfo['final_price'] = '';  //已砍到价格
        $goodsInfo['bargain_end'] = '';  //活动到期

        if ($goodsInfo['is_on_sale'] == 0) {
            return ['error' => 1, 'msg' => '商品已下架'];
        }
        if ($goodsInfo['status'] == 1 || $time > $goodsInfo['end_time']) {
            $goodsInfo['bargain_end'] = 1;
        }

        //是否帮助砍价
        if($bs_id){
            $goodsInfo['bs_id'] = empty($bs_id) ? 0 : $bs_id;
            $bs_id = $goodsInfo['bs_id'];
        }

        //验证是否参与当前活动标示
        $add_bargain = $this->bargainRepository->isAddBargain($id, $user_id, $bs_id);
        if($add_bargain){
            $goodsInfo['bs_id'] = empty($add_bargain['id']) ? 0 : $add_bargain['id'];
            $bs_id = $goodsInfo['bs_id'];
            $goodsInfo['add_bargain'] = 1;    //已参与
        }
        /* --验证是否砍价-- */
        if(!empty($bs_id)){
            
            //互砍模式中验证是否参与当前活动
            $add_bargain = $this->bargainRepository->isAddBargain($id, $user_id);
            if($add_bargain){                
                $goodsInfo['is_add_bargain'] = 1;    //已参与
            }

            // 验证已砍价信息
            $bargain_info = $this->bargainRepository->isBargainJoin($bs_id,$user_id);
            if ($bargain_info) {
                $goodsInfo['bargain_join'] = 1;    //已砍价标示
                //用户名、头像
                $user_info = $this->userRepository->userInfo($bargain_info['user_id']);
                $bargain_info['user_name'] = !empty($user_info['nick_name']) ? $user_info['nick_name'] : $user_info['user_name'];
                $bargain_info['user_picture'] = get_image_path($user_info['user_picture']);

                //排行榜
                $bargain_ranking= $this->bargainRepository->getBargainRanking($id);
                $bargain_info['ranking_num'] = count($bargain_ranking);  //几人参与活动
                $rank = $this->bargainRepository->copyArrayColumn($bargain_ranking, 'user_id');
                $rank = array_search($user_id, $rank);
                $bargain_info['rank'] = $rank+1 ;//排行名次

                $bargain_log = $this->bargainRepository->bargainLog($bs_id);//参与活动记录
                $bargain_info['final_price'] = $bargain_log['final_price'];//已砍到价格

                $result['bargain_info'] = $bargain_info;   //砍价记录
            }

            //亲友帮
            $bargain_list = $this->bargainRepository->getBargainStatistics($bs_id);
            $bargain_num = count($bargain_list);
            $goodsInfo['bargain_num'] = $bargain_num;//参与砍价人数
            $result['bargain_list'] = $bargain_list;

            //砍后价格,选择属性
            $bargain_log = $this->bargainRepository->bargainLog($bs_id);//参与活动记录
            $goodsInfo['final_price'] = $bargain_log['final_price'];//已砍到价格
            
            //获取选中活动属性原价，底价
            if($bargain_log['goods_attr_id']){
                $spec = explode(",", $bargain_log['goods_attr_id']);
                $goodsInfo['shop_price'] = $this->bargainRepository->getFinalPrice($goodsInfo['goods_id'], '', true, $spec, '', '');//原价
                $goodsInfo['target_price']  = $this->bargainRepository->bargainTargetPrice($id,$goodsInfo['goods_id'],$spec, 0, 0,$goodsInfo['model_attr']);//底价
                // 商品属性文字输出
                $attrName = $this->goodsAttrRepository->getAttrNameById($spec);
                $attrNameStr = '';
                foreach ($attrName as $v) {
                    $attrNameStr .= $v['attr_name'] . ':'. $v['attr_value'] . " \n";
                }
                $goodsInfo['attr_name'] = $attrNameStr;
            }
             //进度条
            $surplus = $goodsInfo['shop_price'] - $goodsInfo['target_price'];//差价
            //已砍价总额
            $subtract = $this->bargainRepository->subtractPriceSum($bs_id);
            $bargain_bar = round($subtract * 100 / $surplus, 0);//百分比
            $goodsInfo['bargain_bar'] = $bargain_bar ;//进度条

        }

        //排行榜
        $bargain_ranking = $this->bargainRepository->getBargainRanking($id);
        $goodsInfo['ranking_num'] = count($bargain_ranking);  //几人参与活动
        $rank = $this->bargainRepository->copyArrayColumn($bargain_ranking, 'user_id');
        $rank = array_search($user_id, $rank);
        $goodsInfo['rank'] = $rank+1 ;//排行名次

        $result['bargain_ranking'] = $bargain_ranking;//排行榜

        //砍价爆款
        $result['bargain_hot'] = $this->bargainRepository->findByType($user_id, 'is_hot');

        if (!empty($goodsInfo['desc_mobile'])) {
            $goodsInfo['desc_mobile'] = preg_replace("/height\=\"[0-9]+?\"/", "", $goodsInfo['desc_mobile']);
            $goodsInfo['desc_mobile'] = preg_replace("/width\=\"[0-9]+?\"/", "", $goodsInfo['desc_mobile']);
            $goodsInfo['desc_mobile'] = preg_replace("/style=.+?[*|\"]/i", "", $goodsInfo['desc_mobile']);
            $goodsInfo['goods_desc'] = preg_replace('/<div[^>]*(tools)[^>]*>(.*?)<\/div>(.*?)<\/div>/is', '', $goodsInfo['desc_mobile']);
        } elseif (!empty($goodsInfo['goods_desc'])) {
            $open_oss = $shopconfig->getShopConfigByCode('open_oss');
            if ($open_oss == 1) {
                $bucket_info = get_bucket_info();
                $bucket_info['endpoint'] = empty($bucket_info['endpoint']) ? $bucket_info['outside_site'] : $bucket_info['endpoint'];
                $goodsInfo['goods_desc'] = str_replace(['src="/images/upload', 'src="images/upload'], 'src="' . $bucket_info['endpoint'] . 'images/upload', $goodsInfo['goods_desc']);
            } else {
                $goodsInfo['goods_desc'] = str_replace(['src="/images/upload', 'src="images/upload'], 'src="' . $rootPath . '/images/upload', $goodsInfo['goods_desc']);
            }
        } else {
            $goodsInfo['goods_desc'] = 'xxx';
        }

        $goodsInfo['goods_thumb'] = get_image_path($goodsInfo['goods_thumb']);
        $goodsInfo['shop_price'] = price_format($goodsInfo['shop_price'], true);   //原价
        $goodsInfo['target_price'] = price_format($goodsInfo['target_price'], true);          //底价
        $goodsInfo['market_price_formated'] = price_format($goodsInfo['market_price'], true);
        $goodsInfo['end_time'] = $goodsInfo['end_time'] + (8 * 3600);
        $result['goods_info'] = $goodsInfo;

        // 商品相册
        $goodsGallery = $this->goodsRepository->goodsGallery($goodsInfo['goods_id']);

        foreach ($goodsGallery as $k => $v) {
            $goodsGallery[$k] = get_image_path($v['img_url']);
        }
        $result['goods_img'] = $goodsGallery;

        // 商品属性 规格
        $result['goods_properties'] = $this->goodsRepository->goodsProperties($goodsInfo['goods_id']);

        $result['root_path'] = $rootPath;

        return $result;
    }

    /**
     * 商品属性价格与库存
     * @param int $bargain_id
     * @param int $attr_id
     * @param int $num
     * @param int $store_id 门店id
     * @return array
     *
     */
    public function goodsPropertiesPrice($bargain_id, $attr_id, $num = 1, $warehouse_id = 0, $area_id = 0, $store_id = 0)
    {
        $result = [
            'stock' => '',             //库存
            'market_price' => '',      //市场价
            'qty' => '',               //数量
            'spec_price' => '',        //属性价格
            'goods_price' => '',       //商品价格(最终使用价格)
            'target_price' => '',      //砍价底价
            'attr_img' => ''           //商品属性图片
        ];

        // 商品信息
        $goodsInfo = $this->bargainRepository->goodsInfo($bargain_id);

        $result['target_price'] = $goodsInfo['target_price'];
        $result['stock'] = $this->goodsRepository->goodsAttrNumber($goodsInfo['goods_id'], $attr_id, $warehouse_id, $area_id, $store_id);
        $result['market_price'] = $this->goodsRepository->goodsMarketPrice($goodsInfo['goods_id'], $attr_id, $warehouse_id, $area_id);
        $result['market_price_formated'] = price_format($result['market_price'], true);
        $result['qty'] = $num;
        $result['spec_price'] = $this->goodsRepository->goodsPropertyPrice($goodsInfo['goods_id'], $attr_id, $warehouse_id, $area_id);
        $result['spec_price_formated'] = price_format($result['spec_price'], true);
        $result['goods_price'] = $goodsInfo['goods_price'];

        if (!empty($attr_id)) {
            $result['target_price'] = $this->bargainRepository->bargainTargetPrice($bargain_id,$goodsInfo['goods_id'],$attr_id, $warehouse_id, $area_id,$goodsInfo['model_attr']);
            $result['goods_price'] = $this->bargainRepository->getFinalPrice($goodsInfo['goods_id'], $num, true, $attr_id, $warehouse_id, $area_id);
        }
        $result['goods_price_formated'] = price_format($result['goods_price'], true);//商品价格

        $attr_img = $this->goodsRepository->getAttrImgFlie($goodsInfo['goods_id'], $attr_id);
        if (!empty($attr_img)) {
            $result['attr_img'] = get_image_path($attr_img['attr_img_flie']);
        }

        return $result;
    }


    /**
     * 我要参与
     * @param int $bargain_id
     * @param int $attr_id
     * @return array
     *
     */
    public function addBargain($bargain_id, $attr_id, $user_id= 0, $warehouse_id = 0, $area_id = 0)
    {
        $goodsInfo = $this->bargainRepository->goodsInfo($bargain_id);// 商品信息
        $attr_id = empty($attr_id) ? '' : json_decode($attr_id, 1);
        $goodsAttrId = implode(',', $attr_id);
        if(!empty($attr_id)){
            $final_price = $this->bargainRepository->getFinalPrice($goodsInfo['goods_id'], '', true, $attr_id, $warehouse_id, $area_id); //原价
        }else{
            $final_price = $goodsInfo['shop_price'];
        }

        // 添加参数
        $arguments = [
            'bargain_id'     => $bargain_id,
            'goods_attr_id'  => $goodsAttrId,
            'user_id'        => $user_id,
            'final_price'    => $final_price ,
            'add_time'       => gmtime(),
        ];

        //插入参与活动记录表
        $result = $this->bargainRepository->addBargain($arguments);

        // 商品属性文字输出
        $attrName = $this->goodsAttrRepository->getAttrNameById($attr_id);
        $attrNameStr = '';
        foreach ($attrName as $v) {
            $attrNameStr .= $v['attr_name'] . ':'. $v['attr_value'] . " \n";
        }
        $result['attr_name'] = $attrNameStr;
        $result['num'] = 1;
        $result['add_bargain'] = 1;


        //更新活动参与人数
        $this->bargainRepository->updateBargain($bargain_id,$goodsInfo['total_num']);

        return $result;

    }

    /**
     * 去砍价
     * @param int $bargain_id
     * @param int $attr_id
     * @return array
     *
     */
    public function goBargain($bargain_id = 0, $bs_id = 0, $user_id= 0, $form_id = '')
    {
        $result = [
            'error' => '',
            'message' => ''
        ];

        $bargain = $this->bargainRepository->goodsInfo($bargain_id);// 砍价商品信息
        $bs_log = $this->bargainRepository->bargainLog($bs_id);//参与活动记录
        //获取选中活动属性底价
        if($bs_log['goods_attr_id']){
            $spec = explode(",", $bs_log['goods_attr_id']);
            $bargain['target_price'] = $this->bargainRepository->bargainTargetPrice($bargain_id,$bargain['goods_id'],$spec, 0, 0,$bargain['model_attr']);//底价
        }

        //验证是否重复参与砍价
        $number = $this->bargainRepository->bargainLogNumber($bs_id, $user_id);//参与活动记录
        if($number > 0){
            $result = [
                'error' => 1,
                'message' => '您已参与砍价！'
            ];
           return $result;
        }

        //砍价规则

        if($bargain['target_price'] == $bs_log['final_price']){
            $result = [
                'error' => 1,
                'message' => '已砍至最低价格！'
            ];
            return $result;
        }else{
            $subtract_price = rand($bargain['min_price'], $bargain['max_price']);//砍掉价格区间
            $subtract = $bs_log['final_price'] - $subtract_price;//已砍价到

            if($subtract < $bargain['target_price']){
                $subtract_price = $bs_log['final_price'] - $bargain['target_price'];
            }
        }

        // 添加参数
        $arguments = [
            'bs_id'           => $bs_id,
            'user_id'         => $user_id,
            'subtract_price'  => $subtract_price,
            'add_time'        => gmtime(),
        ];
        //插入参与砍价记录表
        $add = $this->bargainRepository->addBargainStatistics($arguments);
        if($add){
            //更新参与砍价人数 和砍后最终购买价
            $count_num = $bs_log['count_num']+1;
            $final_price = $bs_log['final_price'] - $subtract_price; //砍后价格
            $this->bargainRepository->updateBargainStatistics($bs_id, $count_num, $final_price);

            //排行
            $bargain_ranking = $this->bargainRepository->getBargainRanking($bargain_id);
            $rank = $this->bargainRepository->copyArrayColumn($bargain_ranking, 'user_id');
            $rank = array_search($bs_log['user_id'], $rank);
            $rank = $rank+1 ;//排行名次

            //会员信息
            $user_info = $this->userRepository->userInfo($user_id);
            $user_name = !empty($user_info['nick_name']) ? $user_info['nick_name'] : $user_info['user_name'];
            $user_picture = get_image_path($user_info['user_picture']);

            //验证是否参与当前活动标示
            $add_bargain= 0;
            $add_bargain_info = $this->bargainRepository->isAddBargain($bargain_id, $user_id);
            if($add_bargain_info){
                $add_bargain= 1;    //已参与
            }

            $result = [
                'error' => 2,
                'subtract_price' => $subtract_price, //砍掉价格
                'final_price' => $final_price,       //砍后价格
                'rank' => $rank,       //等级
                'user_name' => $user_name,
                'user_picture' => $user_picture,
                'add_bargain' => $add_bargain,
                'bs_id' => $bs_id,
                'bargain_join' => 1,
                'message' => '砍价成功'
            ];

            //推送模消息
            /**/
            $pushData = [
                'keyword1' => ['value' => $bargain['goods_name'], 'color' => '#000000'],     //商品名称
                'keyword2' => ['value' => price_format($bargain['target_price'], true), 'color' => '#000000'],  //底价
                'keyword3' => ['value' => price_format($subtract_price, true), 'color' => '#000000']  //砍掉价格
            ];
            $url = 'pages/bargain/goods?objectId='. $bargain_id . '&bs_id='. $bs_id;
            $this->authService->wxappPushTemplate('AT1173', $pushData, $url, $user_id,$form_id);

        }else{
            $result = [
                'error' => 1,
                'message' => '砍价失败'
            ];
        }

        return $result;

    }


    /**
     * 添加商品到购物车
     * @param $params
     * @return bool
     */
    public function addGoodsToCart($params)
    {
        $result = [
            'code' => 0,
            'flow_type' => 0,
            'bs_id' => 0,
        ];

        $goods = $this->goodsRepository->find($params['goods_id']);   //查找商品
        if ($goods['is_on_sale'] != 1) {
            return '商品已下架';
        }

        // 货品
        //参与活动记录
        $bs_log = $this->bargainRepository->bargainLog($params['bs_id']);//参与活动记录
        $goodsAttrId = $bs_log['goods_attr_id'];  //属性
        $goodsAttr = explode(',', $goodsAttrId);
        $product = $this->goodsRepository->getProductByGoods($params['goods_id'], implode('|', $goodsAttr));
        if (empty($product)) {
            $product['id'] = 0;
        }
        // 商品属性文字输出
        $attrName = $this->goodsAttrRepository->getAttrNameById($goodsAttr);

        $attrNameStr = '';
        foreach ($attrName as $v) {
            $attrNameStr .= $v['attr_name'] . ':'. $v['attr_value'] . " \n";
        }

        //库存
        $attr_number = $this->goodsRepository->goodsAttrNumber($params['goods_id'], $goodsAttr);
        if ($params['num'] > $attr_number) {
             return '当前库存不足';
        }
        /* 更新：清空当前会员购物车中砍价商品 */
        $this->cartRepository->clearCart(CART_BARGAIN_GOODS,$params['uid']);
        // 计算商品价格
        $goodsPrice = $bs_log['final_price'];

        // 添加参数  CART_BARGAIN_GOODS
        $arguments = [
            'goods_id' => $goods['goods_id'],
            'user_id' => $params['uid'],
            'goods_sn' => $goods['goods_sn'],
            'product_id' => empty($product['id']) ? '' : $product['id'],
            'group_id' => '',
            'goods_name' => $goods['goods_name'],
            'market_price' => $goods['market_price'],
            'goods_price' => $goodsPrice,
            'goods_number' => $params['num'],
            'goods_attr' => $attrNameStr,
            'is_real' => $goods['is_real'],
            'extension_code' => empty($params['extension_code']) ? '' : $params['extension_code'],
            'parent_id' => 0,
            'rec_type' => CART_BARGAIN_GOODS,  // 购物车商品类型
            'is_gift' => 0,
            'is_shipping' => $goods['is_shipping'],
            'can_handsel' => '',
            'model_attr' => $goods['model_attr'],
            'goods_attr_id' => $goodsAttrId,
            'ru_id' => $goods['user_id'],
            'shopping_fee' => '',
            'warehouse_id' => '',
            'area_id' => '',
            'add_time' => gmtime(),
            'stages_qishu' => '',
            'store_id' => '',
            'freight' => '',
            'tid' => '',
            'shipping_fee' => '',
            'store_mobile' => '',
            'take_time' => '',
            'is_checked' => '1',
        ];

        $goodsNumber = $this->cartRepository->addGoodsToCart($arguments);
        if($goodsNumber){
            $result['flow_type'] = CART_BARGAIN_GOODS;
            $result['bs_id'] = $params['bs_id'];
        }



        return $result;
    }


     /**
     * 我de砍价
     * @param int $user_id
     * @param int $page
     * @param int $size
     * @return mixed
     */
    public function myBargain($user_id = 0, $page = 1, $size = 10)
    {
        $page = empty($page) ? 1 : $page;

        $field = [
            "id",           //砍价活动id
            "goods_id",     //商品id
            "goods_name",   //商品名称
            "shop_price",   //商品价格
            "goods_thumb",  //商品图片
            "target_price" //底价
        ];

        $list = $this->bargainRepository->myBargain($user_id, $page, $size);
        foreach ($list as $key => $v) {
            $list[$key]['goods_thumb'] = get_image_path($v['goods_thumb']);
            $list[$key]['shop_price'] = price_format($v['shop_price'], false);
            $list[$key]['target_price'] = price_format($v['target_price'], false);
            $target_price = $this->bargainRepository->getBargainTargetPrice($v['id']);//获取砍价商品属性最低价格
            if($target_price){
                $list[$key]['target_price'] =  price_format($target_price, false);
            }
        }
        return $list;
    }



}
