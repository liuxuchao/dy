<?php

namespace App\Services;

use App\Extensions\Wxapp;
use App\Repositories\Cart\CartRepository;
use App\Repositories\Goods\GoodsRepository;
use App\Repositories\Store\StoreRepository;
use App\Repositories\Coupons\CouponsRepository;
use App\Repositories\Goods\GoodsAttrRepository;
use App\Repositories\Goods\CollectGoodsRepository;
use App\Repositories\User\UserRepository;
use App\Repositories\Wechat\WxappConfigRepository;
use App\Repositories\Activity\ActivityRepository;
use App\Repositories\Category\CategoryRepository;

class GoodsService
{
    private $goodsRepository;
    private $goodsAttrRepository;
    private $collectGoodsRepository;
    private $CouponsRepository;
    private $shopService;
    private $cartRepository;
    private $StoreRepository;
    private $userRepository;
    private $WxappConfigRepository;
    private $activityRepository;
    private $categoryRepository;

    public function __construct(
        GoodsRepository $goodsRepository,
        GoodsAttrRepository $goodsAttrRepository,
        CollectGoodsRepository $collectGoodsRepository,
        CouponsRepository $couponsRepository,
        ShopService $shopService,
        CartRepository $cartRepository,
        UserRepository $userRepository,
        StoreRepository $StoreRepository,
        ActivityRepository $activityRepository,
        WxappConfigRepository $WxappConfigRepository,
        CategoryRepository $categoryRepository
    )
    {
        $this->goodsRepository = $goodsRepository;
        $this->goodsAttrRepository = $goodsAttrRepository;
        $this->collectGoodsRepository = $collectGoodsRepository;
        $this->couponsRepository = $couponsRepository;
        $this->shopService = $shopService;
        $this->cartRepository = $cartRepository;
        $this->StoreRepository = $StoreRepository;
        $this->userRepository = $userRepository;
        $this->activityRepository = $activityRepository;
        $this->WxappConfigRepository = $WxappConfigRepository;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * 商品列表
     * @param int $categoryId
     * @param $keywords
     * @param int $page
     * @param int $size
     * @param $sortKey
     * @param $sortVal
     * @return mixed
     */
    public function getGoodsList($categoryId = 0, $keywords = '', $page = 1, $size = 10, $sortKey = '', $sortVal = '', $warehouse_id = 0, $area_id = 0, $proprietary = 2, $price_min = 0, $price_max = 0, $brand = '', $province_id = 0, $city_id = 0, $county_id = 0, $fil_key)
    {
        $page = empty($page) ? 1 : $page;
        $cat = '';
        $field = [
            "goods_id",    //商品id
            "goods_name", //商品名称
            "shop_price",  //商品价格
            "goods_thumb", //商品图片
            "goods_number",   //商品销量
            "market_price",   //商品原价格
            "sales_volume"  //商品库存
        ];
        if ($categoryId) {
            $cat = $this->goodsRepository->allcat($categoryId);
        }
        if($cat){
            foreach($cat as $k => $val){
                $res[$k] = isset($val['cat_id']) ? $val['cat_id'] : $val;
            }
            array_unshift($res, $categoryId);
            $categoryId = $res;
        }
        $list = $this->goodsRepository->findBy('category', $categoryId, $page, $size, $field, $keywords, $sortKey, $sortVal, $proprietary, $price_min, $price_max, $brand, $province_id, $city_id, $county_id, $fil_key);

        foreach ($list as $k => $v) {
            $list[$k]['goods_thumb'] = get_image_path($v['goods_thumb']);
            $list[$k]['brand_name'] = $this->goodsRepository->getBrandNameByGoodsId($v['goods_id']);
            $list[$k]['market_price_formated'] = price_format($v['market_price'], false);
            $list[$k]['shop_price'] = $this->goodsRepository->getGoodsOneAttrPrice($v['goods_id']);
            $list[$k]['shop_price_formated'] = price_format($v['shop_price'], false);
        }

        return $list;
    }

    /**
     * 商品列表筛选
     * @param int $categoryId
     * @param $keywords
     * @param int $page
     * @param int $size
     * @param $sortKey
     * @param $sortVal
     * @return mixed
     */
    public function getGoodsFilter($id)
    {

        return ;
    }

    /**
     * 商品筛选条件
     * @param int $categoryId
     * @param $keywords
     * @param int $page
     * @param int $size
     * @param $sortKey
     * @param $sortVal
     * @return mixed
     */
    public function getGoodsFilterCondition($cat_id = 0)
    {

        $list = $this->goodsRepository->FilterCondition($cat_id);

        return $list;
    }

    /**
     * 商品详情
     * @param $id
     * @param $uid
     * @return array
     */
    public function goodsDetail($id, $uid)
    {
        $result = [
            'error' => 0,
            'goods_img' => '',
            'goods_info' => '',
            'goods_comment' => '',
            'goods_properties' => ''
        ];
        $rootPath = app('request')->root();
        $rootPath = dirname(dirname($rootPath)) . '/';
        $shopconfig = app('App\Repositories\ShopConfig\ShopConfigRepository');
        $timeFormat = $shopconfig->getShopConfigByCode('time_format');

        //
        $collect = $this->collectGoodsRepository->findOne($id, $uid);
        $goodsComment = $this->goodsRepository->goodsComment($id);

        foreach ($goodsComment as $k => $v) {
            $goodsComment[$k]['add_time'] = local_date('Y-m-d', $v['add_time']);
            $goodsComment[$k]['user_name'] = $this->goodsRepository->getGoodsCommentUser($v['user_id']);
        }

        $result['goods_comment'] = $goodsComment;
        $result['total_comment_number'] = count($result['goods_comment']);
        // 商品信息
        $goodsInfo = $this->goodsRepository->goodsInfo($id);
        if ($goodsInfo['is_on_sale'] == 0) {
            return ['error' => 1, 'msg' => '商品已下架'];
        }
        $goodsInfo['goods_thumb'] = get_image_path($goodsInfo['goods_thumb']);
        $goodsInfo['goods_video'] = $goodsInfo['goods_video'] ? get_image_path($goodsInfo['goods_video']) : '';
        $goodsInfo['goods_price_formated'] = price_format($goodsInfo['goods_price'], true);
        $goodsInfo['market_price_formated'] = price_format($goodsInfo['market_price'], true);

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
            // 查询关联商品描述
//        $sql = "SELECT ld.goods_desc FROM {pre}link_desc_goodsid AS dg, {pre}link_goods_desc AS ld WHERE dg.goods_id = {$this->goods_id}  AND dg.d_id = ld.id AND ld.review_status > 2";
//        $link_desc = Db::Query($sql);
//        if (!empty($info['desc_mobile'])) {
//            // 处理手机端商品详情 图片（手机相册图） data/gallery_album/
//            if (C('shop.open_oss') == 1) {
//                $bucket_info = get_bucket_info();
//                $bucket_info['endpoint'] = empty($bucket_info['endpoint']) ? $bucket_info['outside_site'] : $bucket_info['endpoint'];
//                $desc_preg = get_goods_desc_images_preg($bucket_info['endpoint'], $info['desc_mobile'], 'desc_mobile');
//                $goods_desc = preg_replace('/<div[^>]*(tools)[^>]*>(.*?)<\/div>(.*?)<\/div>/is', '', $desc_preg['desc_mobile']);
//            } else {
//                $goods_desc = preg_replace('/<div[^>]*(tools)[^>]*>(.*?)<\/div>(.*?)<\/div>/is', '', $info['desc_mobile']);
//            }
//        }
//        if (empty($goodsInfo['desc_mobile']) && empty($goodsInfo['goods_desc'])) {
//            $goods_desc = $link_desc;
//        }
        }

        $result['goods_info'] = array_merge($goodsInfo, ['is_collect' => (empty($collect)) ? 0 : 1]);

        // 商家信息
        $ruId = $goodsInfo['user_id'];
        unset($result['goods_info']['user_id']);
        if ($ruId > 0) {
            $result['shop_name'] = $this->shopService->getShopName($ruId);

            $result['coll_num'] = $this->StoreRepository->collnum($ruId);

            $detail = $this->StoreRepository->detail($ruId);
            $result['detail'] = $detail['0'];
            $result['detail']['sellershopinfo']['logo_thumb'] = get_image_path(str_replace('../', '', $detail['0']['sellershopinfo']['logo_thumb']));
        }

        $coupont = $this->couponsRepository->goodsCoupont($id, $goodsInfo['user_id'], $uid);
        // foreach ($coupont as $key => $value) {
        //         $coupont[$key]['cou_end_time'] = local_date('Y.m.d', $value['cou_end_time']);
        //         $coupont[$key]['cou_start_time'] = local_date('Y.m.d', $value['cou_start_time']);

        // }
        $result['coupont'] = $coupont;

        // 商品相册
        $goodsGallery = $this->goodsRepository->goodsGallery($id);
        foreach ($goodsGallery as $k => $v) {
            $goodsGallery[$k] = get_image_path($v['img_url']);
        }
        $result['goods_img'] = $goodsGallery;

        // 商品属性 规格
        $result['goods_properties'] = $this->goodsRepository->goodsProperties($id);

        // 推荐商品
        $result['recommend'] = $this->goodsRepository->findByType('best');
        foreach ($result['recommend'] as $key => $value) {
            $result['recommend'][$key]['goods_thumb'] = get_image_path($value['goods_thumb']);
        }

        // 促销信息
        $result['goods_promotion'] = $this->getPromotionInfo($id, $goodsInfo['user_id']);

        // 购物车商品数量
        $result['cart_number'] = $this->cartRepository->goodsNumInCartByUser($uid);
        $result['root_path'] = $rootPath;

        return $result;
    }

    /**
     *  所有的促销活动信息
     *
     * @access  public
     * @return  array
     */
    public function getPromotionInfo($goods_id = '', $ru_id = 0)
    {
        $snatch = [];
        $group = [];
        $auction = [];
        $package = [];
        $favourable = [];
        
        //查询符合条件的优惠活动
        $res = $this->activityRepository->activityListAll($ru_id);
        if (empty($goods_id)) {
            foreach ($res as $rows) {
                $favourable[$rows['act_id']]['act_name'] = $rows['act_name'];
                $favourable[$rows['act_id']]['url'] = url('activity/index/detail', ['id' => $rows['act_id']]);
                $favourable[$rows['act_id']]['time'] = sprintf(L('promotion_time'), local_date('Y-m-d', $rows['start_time']), local_date('Y-m-d', $rows['end_time']));
                $favourable[$rows['act_id']]['sort'] = $rows['start_time'];
                $favourable[$rows['act_id']]['type'] = 'favourable';
                $favourable[$rows['act_id']]['act_type'] = $rows['act_type'];
            }
        } else {           

             // 商品信息
            $row = $this->goodsRepository->goodsInfo($goods_id);
            $category_id = $row['cat_id'];
            $brand_id = $row['brand_id'];

            foreach ($res as $rows) {
                if ($rows['act_range'] == FAR_ALL) {
                    $favourable[$rows['act_id']]['act_id'] = $rows['act_id'];
                    $favourable[$rows['act_id']]['act_name'] = $rows['act_name'];
                    $favourable[$rows['act_id']]['sort'] = $rows['start_time'];
                    $favourable[$rows['act_id']]['type'] = 'favourable';
                    $favourable[$rows['act_id']]['act_type'] = $rows['act_type'];
                } elseif ($rows['act_range'] == FAR_CATEGORY) {
                    /* 找出分类id的子分类id */
                    $id_list = [];
                    $raw_id_list = explode(',', $rows['act_range_ext']);

                    foreach ($raw_id_list as $id) { 
                        /**
                         * 当前分类下的所有子分类
                         * 返回一维数组
                         */
                        $cat_list = $this->categoryRepository->arr_foreach($this->categoryRepository->catList($id));  
                        $id_list = array_merge($id_list, $cat_list);
                        array_unshift($id_list, $id);

                    }
                    $ids = join(',', array_unique($id_list));
                    if (strpos(',' . $ids . ',', ',' . $category_id . ',') !== false) {
                        $favourable[$rows['act_id']]['act_id'] = $rows['act_id'];
                        $favourable[$rows['act_id']]['act_name'] = $rows['act_name'];
                        $favourable[$rows['act_id']]['sort'] = $rows['start_time'];
                        $favourable[$rows['act_id']]['type'] = 'favourable';
                        $favourable[$rows['act_id']]['act_type'] = $rows['act_type'];
                    }
                } elseif ($rows['act_range'] == FAR_BRAND) {                   
                    //$rows['act_range_ext'] = $this->activityRepository->returnActRangeExt($rows['act_range_ext'], $rows['userFav_type'], $rows['act_range']);
                    if (strpos(',' . $rows['act_range_ext'] . ',', ',' . $brand_id . ',') !== false) {
                        $favourable[$rows['act_id']]['act_id'] = $rows['act_id'];
                        $favourable[$rows['act_id']]['act_name'] = $rows['act_name'];
                        $favourable[$rows['act_id']]['sort'] = $rows['start_time'];
                        $favourable[$rows['act_id']]['type'] = 'favourable';
                        $favourable[$rows['act_id']]['act_type'] = $rows['act_type'];
                    }
                } elseif ($rows['act_range'] == FAR_GOODS) {
                    if (strpos(',' . $rows['act_range_ext'] . ',', ',' . $goods_id . ',') !== false) {
                        $favourable[$rows['act_id']]['act_id'] = $rows['act_id'];
                        $favourable[$rows['act_id']]['act_name'] = $rows['act_name'];
                        $favourable[$rows['act_id']]['sort'] = $rows['start_time'];
                        $favourable[$rows['act_id']]['type'] = 'favourable';
                        $favourable[$rows['act_id']]['act_type'] = $rows['act_type'];
                    }
                }
            }
        }

        $sort_time = [];
        $arr = array_merge($snatch, $group, $auction, $package, $favourable);
        foreach ($arr as $key => $value) {
            $sort_time[] = $value['sort'];
        }
        array_multisort($sort_time, SORT_NUMERIC, SORT_DESC, $arr);

        return $arr;


    }
    


    /**
     * 商品属性价格与库存
     * @param int $goodsId
     * @param int $attr_id
     * @param int $num
     * @param int $store_id 门店id
     * @return array
     *
     */
    public function goodsPropertiesPrice($goods_id, $attr_id, $num = 1, $warehouse_id = 0, $area_id = 0, $store_id = 0)
    {
        $result = [
            'stock' => '',       //库存
            'market_price' => '',      //市场价
            'qty' => '',               //数量
            'spec_price' => '',        //属性价格
            'goods_price' => '',           //商品价格(最终使用价格)
            'attr_img' => ''           //商品属性图片
        ];
        // $goods = $this->goodsRepository->goodsInfo($goods_id);//商品详情

        $result['stock'] = $this->goodsRepository->goodsAttrNumber($goods_id, $attr_id, $warehouse_id, $area_id, $store_id);
        $result['market_price'] = $this->goodsRepository->goodsMarketPrice($goods_id, $attr_id, $warehouse_id, $area_id);
        $result['market_price_formated'] = price_format($result['market_price'], true);
        $result['qty'] = $num;
        $result['spec_price'] = $this->goodsRepository->goodsPropertyPrice($goods_id, $attr_id, $warehouse_id, $area_id);
        $result['spec_price_formated'] = price_format($result['spec_price'], true);
        $result['goods_price'] = $this->goodsRepository->getFinalPrice($goods_id, $num, true, $attr_id, $warehouse_id, $area_id);
        $result['goods_price_formated'] = price_format($result['goods_price'], true);
        $attr_img = $this->goodsRepository->getAttrImgFlie($goods_id, $attr_id);
        if (!empty($attr_img)) {
            $result['attr_img'] = get_image_path($attr_img['attr_img_flie']);
        }

        return $result;
    }

    /**
     * 商品分享
     * @param int $goodsId
     * @param int $attr_id
     * @param int $num
     * @param int $store_id 门店id
     * @return array
     *
     */
    public function goodsShare($id, $uid, $path = "", $width = 430, $type = "goods")
    {
        $goodsInfo = $this->goodsRepository->goodsInfo($id);// 商品信息

        $ruId = $goodsInfo['user_id'];

        $detail = $this->StoreRepository->detail($ruId);// 商家信息
        $app_name = $this->WxappConfigRepository->getWxappConfig();
        $shop_name = empty($detail) ? $app_name['0']['wx_appname'] : $detail['0']['rz_shopName'] ;

        $result = $this->get_wxcode($path, $width);

        $rootPath = dirname(base_path());

        $imgDir = $rootPath. "/data/gallery_album/ewm/";
        if (!is_dir($imgDir)) {
            mkdir($imgDir);
        }
        $qrcode = $imgDir . $type . '_' . $uid . '_' . $id .'.png';
        file_put_contents($qrcode, $result);

        $rootPath = app('request')->root();
        $rootPath = dirname(dirname($rootPath)) . '/';

        $image_name = $rootPath."data/gallery_album/ewm/" . basename($qrcode);

        $userInfo = $this->userRepository->userInfo($uid);// 分享人信息

        $user = [
            'name' => (isset($userInfo['nick_name'])) ? $userInfo['nick_name'] : $userInfo['user_name'],  //分享人名字
            'id' => $userInfo['id'],  //分享人ID
            'pic' => get_image_path($userInfo['user_picture']),   //分享人头像
            'shop_name' => $shop_name,        //店铺名字
            'image_name' => $image_name
        ];
        $goods_cont = [
            'id' => $goodsInfo['goods_id'],  //推荐商品ID
            'name' => $goodsInfo['goods_name'],  //商品名称
            'pic' => get_image_path($goodsInfo['goods_thumb'])   //推荐商品图
        ];

        $share['user'] = $user;
        $share['goods_cont'] = $goods_cont;

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
     * 领取优惠券， (coupons_user,coupons)
     * @param int $cou_id 优惠券id
     * //1.根据优惠券id查询，是否还有剩余优惠券，查coupon_user表默认只能领取一次，
     * //2.领取优惠券，（优惠券数量减少，coupons_user 添加一条数据记录用户获取优惠券，）
     */
    public function getCoupon($cou_id, $uid)
    {
        $ticket = 1;      // 默认每次领取一张优惠券
        $time = gmtime();

        $result = $this->couponsRepository->getCoutype($cou_id);

        $type = $result['cou_type'];      //优惠券类型
        $cou_rank = $result['cou_ok_user'];  //可以使用优惠券的rank
        $ranks = explode(",", $cou_rank);

        $result = $this->couponsRepository->getCoups($cou_id, $uid, $ticket);

        return $result;
     }

    /**
     * 历史商品
     * @param $args
     */
    public function history($goods_list, $page = 1, $size = 10)
    {
        if(empty($goods_list)){
            return;
        }
        $goods_list =explode(',', $goods_list);

        $list = $this->goodsRepository->goodsHistory($goods_list, $page, $size);

        return $list;
    }

    /**
     * 保存记录
     * @param $args
     */
    public function goodsSave($list, $goods_id)
    {
        $goods_list =explode(',', $list);
        array_unshift($goods_list, $goods_id);
        $goods_list = array_unique($goods_list);
        $goods_list = implode(',', $goods_list);
        return $goods_list;
    }

}
