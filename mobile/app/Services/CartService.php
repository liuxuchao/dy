<?php

namespace App\Services;

use App\Repositories\Cart\CartRepository;
use App\Repositories\Goods\GoodsRepository;
use App\Repositories\Goods\GoodsAttrRepository;
use App\Repositories\Activity\ActivityRepository;
use App\Repositories\User\UserRankRepository;
use App\Repositories\Category\CategoryRepository;

/**
 * Class CartService
 * @package App\Services
 */
class CartService
{
    private $cartRepository;
    private $goodsRepository;
    private $authService;
    private $goodsAttrRepository;
    private $activityRepository;
    private $userRankRepository;
    private $categoryRepository;

    /**
     * CartService constructor.
     * @param CartRepository $cartRepository
     * @param GoodsRepository $goodsRepository
     * @param AuthService $authService
     * @param GoodsAttrRepository $goodsAttrRepository
     */
    public function __construct(
        CartRepository $cartRepository,
        GoodsRepository $goodsRepository,
        AuthService $authService,
        GoodsAttrRepository $goodsAttrRepository,
        ActivityRepository $activityRepository,
        UserRankRepository $userRankRepository,
        CategoryRepository $categoryRepository
    ) {
        $this->cartRepository = $cartRepository;
        $this->goodsRepository = $goodsRepository;
        $this->authService = $authService;
        $this->goodsAttrRepository = $goodsAttrRepository;
        $this->activityRepository = $activityRepository;
        $this->userRankRepository = $userRankRepository;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * 购物车页面数据
     * @return mixed
     */
    public function getCart()
    {
        //购物车商品列表
        $cart = $this->getCartGoods();        
        //对同一商家商品按照活动分组
        $merchant_goods_list = $this->cartByFavourable($cart['goods_list']);
        $result = [];
        $result['cart_list'] = $merchant_goods_list;        
        $result['total'] = array_map('strip_tags', $cart['total']);
        $result['best_goods'] = $this->getBestGoods();

        return $result;
    }


     /**
     * 购物车商品列表
     * @return mixed
     */
    private function getCartGoods()
    {

        // 用户ID
        $userId = $this->authService->authorization();

        $list = $this->cartRepository->getGoodsInCartByUser($userId);

        return $list;
    }

    /**
     * 对同一商家商品按照活动分组
     * @return mixed
     */
    private function cartByFavourable($merchant_goods)
    {
        //dump($merchant_goods);exit;
        $id_list = array();
        $list_array = array();
        foreach ($merchant_goods as $key => $row) { // 第一层 遍历商家
            
            $user_cart_goods = isset($row['goods']) && !empty($row['goods']) ? $row['goods'] : array(); 
            // 商家发布的优惠活动
            $favourable_list = $this->favourable_list($row['user_id'], $row['ru_id']);
                          
            // 对优惠活动进行归类
            $sort_favourable = $this->sort_favourable($favourable_list);
            //dump($sort_favourable);
            if ($user_cart_goods) {
                foreach ($user_cart_goods as $key1 => $row1) {
                    $row1['market_price_formated'] = price_format($row1['market_price'], false);
                    $row1['goods_price_formated'] = price_format($row1['goods_price'], false);
                    $row1['goods_thumb'] = get_image_path($row1['goods_thumb']);
                    // 第二层 遍历购物车中商家的商品
                    $row1['original_price'] = $row1['goods_price'] * $row1['goods_number'];
                    // 活动-全部商品
                    if (isset($sort_favourable['by_all']) && $row1['extension_code'] != 'package_buy' && substr($row1['extension_code'], 0, 7) != 'seckill') {
                        foreach ($sort_favourable['by_all'] as $key2 => $row2) {
                            $mer_ids = true;                            
                            if ($row2['userFav_type'] == 1 || $mer_ids) {
                                if ($row1['is_gift'] == 0) {
                                    // 活动商品
                                    if (isset($row1) && $row1) {
                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['act_id'] = $row2['act_id'];
                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['act_name'] = $row2['act_name'];
                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['act_type'] = $row2['act_type'];
                                        // 活动类型
                                        switch ($row2['act_type']) {
                                            case 0:
                                                $merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_txt'] = '满赠';
                                                $merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_ext_format'] = intval($row2['act_type_ext']); // 可领取总件数
                                                break;
                                            case 1:
                                                $merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_txt'] = '满减';
                                                $merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_ext_format'] = number_format($row2['act_type_ext'], 2); // 满减金额
                                                break;
                                            case 2:
                                                $merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_txt'] = '折扣';
                                                $merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_ext_format'] = floatval($row2['act_type_ext'] / 10); // 折扣百分比
                                                break;

                                            default:
                                                break;
                                        }
                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['min_amount'] = $row2['min_amount'];
                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_ext'] = intval($row2['act_type_ext']); // 可领取总件数
                                        @$merchant_goods[$key]['new_list'][$row2['act_id']]['cart_fav_amount'] += $row1['subtotal'];
                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['available'] = $this->favourableAvailable($row['user_id'],$row2, array(), $row1['ru_id']); // 购物车满足活动最低金额
                                        // 购物车中已选活动赠品数量                                        
                                        $cart_favourable = $this->cartRepository->cartFavourable($row['user_id'], $row1['ru_id']);
                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['cart_favourable_gift_num'] = empty($cart_favourable[$row2['act_id']]) ? 0 : intval($cart_favourable[$row2['act_id']]);
                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['favourable_used'] = $this->favourableUsed($row2, $cart_favourable);
                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['left_gift_num'] = intval($row2['act_type_ext']) - (empty($cart_favourable[$row2['act_id']]) ? 0 : intval($cart_favourable[$row2['act_id']]));

                                        /* 检查购物车中是否已有该优惠 */

                                        // 活动赠品
                                        if ($row2['gift']) {
                                            $merchant_goods[$key]['new_list'][$row2['act_id']]['act_gift_list'] = $row2['gift'];
                                        }

                                        // new_list->活动id->act_goods_list
                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['act_goods_list'][$row1['rec_id']] = $row1;
                                        unset($row1);

                                    }
                                } else { // 赠品
                                    $merchant_goods[$key]['new_list'][$row2['act_id']]['act_cart_gift'][$row1['rec_id']] = $row1;
                                }
                            } else {
                                if($GLOBALS['_CFG']['region_store_enabled']){
                                    // new_list->活动id->act_goods_list | 活动id的数组位置为0，表示次数组下面为没有参加活动的商品
                                    $merchant_goods[$key]['new_list'][0]['act_goods_list'][$row1['rec_id']] = $row1;
                                }
                            }
                            break; // 如果有多个优惠活动包含全部商品，只取一个
                        }
                        continue; // 如果活动包含全部商品，跳出循环体
                    }

                    // 活动-分类
                    if (isset($sort_favourable['by_category']) && $row1['extension_code'] != 'package_buy' && substr($row1['extension_code'], 0, 7) != 'seckill') {
                        //优惠活动关联分类集合
                        $get_act_range_ext = $this->activityRepository->activityRangeExt($row['ru_id'], 1); // 1表示优惠范围 按分类


                        $str_cat = '';
                        foreach ($get_act_range_ext as $id) {
                            /**
                             * 当前分类下的所有子分类
                             * 返回一维数组
                             */
                            $cat_keys = $this->categoryRepository->arr_foreach($this->categoryRepository->catList(intval($id)));
                            if ($cat_keys) {
                                $str_cat .= implode(",", $cat_keys);
                            }
                        }
                        if ($str_cat) {
                            $list_array = explode(",", $str_cat);
                        }

                        $list_array = !empty($list_array) ? array_merge($get_act_range_ext, $list_array) : $get_act_range_ext;
                        $id_list = $this->categoryRepository->arr_foreach($list_array);
                        $id_list = array_unique($id_list);
                        $cat_id = $row1['cat_id']; //购物车商品所属分类ID
                        // 优惠活动ID集合
                        $favourable_id_list = $this->getFavourableId($sort_favourable['by_category']);
                        // 判断商品或赠品 是否属于本优惠活动
                        if ((in_array($cat_id, $id_list) && $row1['is_gift'] == 0) || in_array($row1['is_gift'], $favourable_id_list)) {
                            foreach ($sort_favourable['by_category'] as $key2 => $row2) {
                                if (isset($row1) && $row1) {
                                    //优惠活动关联分类集合
                                    $fav_act_range_ext = !empty($row2['act_range_ext']) ? explode(',', $row2['act_range_ext']) : array();
                                    foreach ($fav_act_range_ext as $id) {
                                        /**
                                         * 当前分类下的所有子分类
                                         * 返回一维数组
                                         */
                                        $cat_keys = $this->categoryRepository->arr_foreach($this->categoryRepository->catList(intval($id)));
                                        $fav_act_range_ext = array_merge($fav_act_range_ext, $cat_keys);
                                    }

                                    if ($row1['is_gift'] == 0 && in_array($cat_id, $fav_act_range_ext)) { // 活动商品
                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['act_id'] = $row2['act_id'];
                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['act_name'] = $row2['act_name'];
                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['act_type'] = $row2['act_type'];
                                        // 活动类型
                                        switch ($row2['act_type']) {
                                            case 0:
                                                $merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_txt'] = '满赠';
                                                $merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_ext_format'] = intval($row2['act_type_ext']); // 可领取总件数
                                                break;
                                            case 1:
                                                $merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_txt'] = '满减';
                                                $merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_ext_format'] = number_format($row2['act_type_ext'], 2); // 满减金额
                                                break;
                                            case 2:
                                                $merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_txt'] = '折扣';
                                                $merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_ext_format'] = floatval($row2['act_type_ext'] / 10); // 折扣百分比
                                                break;

                                            default:
                                                break;
                                        }

                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['min_amount'] = $row2['min_amount'];
                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_ext'] = intval($row2['act_type_ext']); // 可领取总件数
                                        @$merchant_goods[$key]['new_list'][$row2['act_id']]['cart_fav_amount'] += $row1['subtotal'];
                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['available'] = $this->favourableAvailable($row['user_id'], $row2, array(), $row1['ru_id']); // 购物车满足活动最低金额
                                        // 购物车中已选活动赠品数量                                  
                                        $cart_favourable = $this->cartRepository->cartFavourable($row['user_id'], $row1['ru_id']);
                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['cart_favourable_gift_num'] = empty($cart_favourable[$row2['act_id']]) ? 0 : intval($cart_favourable[$row2['act_id']]);
                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['favourable_used'] =  $this->favourableUsed($row2, $cart_favourable);
                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['left_gift_num'] = intval($row2['act_type_ext']) - (empty($cart_favourable[$row2['act_id']]) ? 0 : intval($cart_favourable[$row2['act_id']]));

                                        /* 检查购物车中是否已有该优惠 */

                                        // 活动赠品
                                        if ($row2['gift']) {
                                            $merchant_goods[$key]['new_list'][$row2['act_id']]['act_gift_list'] = $row2['gift'];
                                        }

                                        // new_list->活动id->act_goods_list
                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['act_goods_list'][$row1['rec_id']] = $row1;

                                        unset($row1);
                                    }

                                    if (isset($row1) && $row1 && $row1['is_gift'] == $row2['act_id']) { // 赠品
                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['act_cart_gift'][$row1['rec_id']] = $row1;
                                    }
                                }
                            }
                            continue;
                        }
                    }

                    // 活动-品牌
                    if (isset($sort_favourable['by_brand']) && $row1['extension_code'] != 'package_buy' && substr($row1['extension_code'], 0, 7) != 'seckill') {
                        // 优惠活动 品牌集合
                        $get_act_range_ext = $this->activityRepository->activityRangeExt($row['ru_id'], 2); // 2表示优惠范围 按品牌
                        $brand_id = $row1['brand_id'];

                        // 优惠活动ID集合
                        $favourable_id_list = $this->getFavourableId($sort_favourable['by_brand']);

                        // 是品牌活动的商品或者赠品
                        if ((in_array(trim($brand_id), $get_act_range_ext) && $row1['is_gift'] == 0) || in_array($row1['is_gift'], $favourable_id_list)) {
                            foreach ($sort_favourable['by_brand'] as $key2 => $row2) {
                                $act_range_ext_str = ',' . $row2['act_range_ext'] . ',';
                                $brand_id_str = ',' . $brand_id . ',';

                                if (isset($row1) && $row1) {
                                    if ($row1['is_gift'] == 0 && strstr($act_range_ext_str, trim($brand_id_str))) { // 活动商品
                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['act_id'] = $row2['act_id'];
                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['act_name'] = $row2['act_name'];
                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['act_type'] = $row2['act_type'];
                                        // 活动类型
                                        switch ($row2['act_type']) {
                                            case 0:
                                                $merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_txt'] = '满赠';
                                                $merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_ext_format'] = intval($row2['act_type_ext']); // 可领取总件数
                                                break;
                                            case 1:
                                                $merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_txt'] = '满减';
                                                $merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_ext_format'] = number_format($row2['act_type_ext'], 2); // 满减金额
                                                break;
                                            case 2:
                                                $merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_txt'] = '折扣';
                                                $merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_ext_format'] = floatval($row2['act_type_ext'] / 10); // 折扣百分比
                                                break;

                                            default:
                                                break;
                                        }

                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['min_amount'] = $row2['min_amount'];
                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_ext'] = intval($row2['act_type_ext']); // 可领取总件数
                                        @$merchant_goods[$key]['new_list'][$row2['act_id']]['cart_fav_amount'] += $row1['subtotal'];
                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['available'] = $this->favourableAvailable($row['user_id'],$row2); // 购物车满足活动最低金额
                                        // 购物车中已选活动赠品数量
                                        
                                        $cart_favourable = $this->cartRepository->cartFavourable($row['user_id'], $row1['ru_id']);
                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['cart_favourable_gift_num'] = empty($cart_favourable[$row2['act_id']]) ? 0 : intval($cart_favourable[$row2['act_id']]);
                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['favourable_used'] =  $this->favourableUsed($row2, $cart_favourable);
                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['left_gift_num'] = intval($row2['act_type_ext']) - (empty($cart_favourable[$row2['act_id']]) ? 0 : intval($cart_favourable[$row2['act_id']]));

                                        /* 检查购物车中是否已有该优惠 */

                                        // 活动赠品
                                        if ($row2['gift']) {
                                            $merchant_goods[$key]['new_list'][$row2['act_id']]['act_gift_list'] = $row2['gift'];
                                        }

                                        // new_list->活动id->act_goods_list
                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['act_goods_list'][$row1['rec_id']] = $row1;

                                        unset($row1);
                                    }

                                    if (isset($row1) && $row1 && $row1['is_gift'] == $row2['act_id']) { // 赠品
                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['act_cart_gift'][$row1['rec_id']] = $row1;
                                    }
                                }
                            }
                            continue;
                        }
                    }

                    // 活动-部分商品
                    if (isset($sort_favourable['by_goods']) && $row1['extension_code'] != 'package_buy' && substr($row1['extension_code'], 0, 7) != 'seckill') {
                        
                        $get_act_range_ext = $this->activityRepository->activityRangeExt($row['ru_id'], 3);// 3表示优惠范围 按商品
                        // 优惠活动ID集合
                        $favourable_id_list = $this->getFavourableId($sort_favourable['by_goods']);

                        // 判断购物商品是否参加了活动  或者  该商品是赠品
                        if (in_array($row1['goods_id'], $get_act_range_ext) || in_array($row1['is_gift'], $favourable_id_list)) {                            
                            foreach ($sort_favourable['by_goods'] as $key2 => $row2) { // 第三层 遍历活动
                                $act_range_ext_str = ',' . $row2['act_range_ext'] . ','; // 优惠活动中的优惠商品
                                $goods_id_str = ',' . $row1['goods_id'] . ',';
                                // 如果是活动商品
                                if (isset($row1) && $row1) {
                                    if (strstr($act_range_ext_str, $goods_id_str) && ($row1['is_gift'] == 0)) {

                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['act_id'] = $row2['act_id'];
                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['act_name'] = $row2['act_name'];
                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['act_type'] = $row2['act_type'];
                                        // 活动类型
                                        switch ($row2['act_type']) {
                                            case 0:
                                                $merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_txt'] = '满赠';
                                                $merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_ext_format'] = intval($row2['act_type_ext']); // 可领取总件数
                                                break;
                                            case 1:
                                                $merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_txt'] = '满减';
                                                $merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_ext_format'] = number_format($row2['act_type_ext'], 2); // 满减金额
                                                break;
                                            case 2:
                                                $merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_txt'] = '折扣';
                                                $merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_ext_format'] = floatval($row2['act_type_ext'] / 10); // 折扣百分比
                                                break;

                                            default:
                                                break;
                                        }
                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['min_amount'] = $row2['min_amount']; //金额下线
                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['act_type_ext'] = intval($row2['act_type_ext']); // 可领取总件数
                                        @$merchant_goods[$key]['new_list'][$row2['act_id']]['cart_fav_amount'] += $row1['subtotal'];
                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['available'] = $this->favourableAvailable($row['user_id'],$row2); // 购物车满足活动最低金额

                                        // 购物车中已选活动赠品数量                                        
                                        $cart_favourable = $this->cartRepository->cartFavourable($row['user_id'], $row1['ru_id']);
                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['cart_favourable_gift_num'] = empty($cart_favourable[$row2['act_id']]) ? 0 : intval($cart_favourable[$row2['act_id']]);
                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['favourable_used'] = $this->favourableUsed($row2, $cart_favourable);
                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['left_gift_num'] = intval($row2['act_type_ext']) - (empty($cart_favourable[$row2['act_id']]) ? 0 : intval($cart_favourable[$row2['act_id']]));

                                        /* 检查购物车中是否已有该优惠 */

                                        // 活动赠品
                                        if ($row2['gift']) {
                                            /*$gift_list =[
                                                'ru_id' => $row2['user_id'],
                                                'act_id' => $row2['act_id'],                                                
                                                'list'   => $row2['gift'],
                                                'select_num'   =>  empty($cart_favourable[$row2['act_id']]) ? 0 : intval($cart_favourable[$row2['act_id']]),
                                                'total_num'   => intval($row2['act_type_ext'])
                                            ];
                                            $merchant_goods[$key]['new_list'][$row2['act_id']]['act_gift_list'] = $gift_list;*/
                                            $merchant_goods[$key]['new_list'][$row2['act_id']]['act_gift_list'] = $row2['gift'];
                                        }

                                        // new_list->活动id->act_goods_list  $key1
                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['act_goods_list'][$row1['rec_id']] = $row1;
                                        //$merchant_goods[$key]['new_list'][$key1]['act_goods_list'][$row1['rec_id']] = $row1;

                                        break;

                                        unset($row1);
                                    }

                                    // 如果是赠品
                                    if (isset($row1) && $row1 && $row1['is_gift'] == $row2['act_id']) {
                                        $merchant_goods[$key]['new_list'][$row2['act_id']]['act_cart_gift'][$row1['rec_id']] = $row1;
                                    }
                                }
                            }
                        } else {
                            // new_list->活动id->act_goods_list | 活动id的数组位置为0，表示次数组下面为没有参加活动的商品
                            $merchant_goods[$key]['new_list'][0]['act_goods_list'][$row1['rec_id']] = $row1;
                        }
                    } else {
                        // new_list->活动id->act_goods_list | 活动id的数组位置为0，表示次数组下面为没有参加活动的商品
                        $merchant_goods[$key]['new_list'][0]['act_goods_list'][$row1['rec_id']] = $row1;
                    }
                }
            }
        }

        return $merchant_goods;
    
        
    }

    /**
     * 根据购物车判断是否可以享受某优惠活动
     * @param   array $favourable 优惠活动信息
     * @return  bool
     */
    public function favourableAvailable($user_id,$favourable, $act_sel_id = array(), $ru_id = -1)
    {
        /* 会员等级是否符合 */
        $user_rank = $this->userRankRepository->getUserRankByUid();
        //$user_rank['rank_id'] = '6';
        if (strpos(',' . $favourable['user_rank'] . ',', ',' . $user_rank['rank_id'] . ',') === false) {
            return false;
        }
        //dump($user_id);exit;
        /* 优惠范围内的商品总额 */
        $amount = $this->cartRepository->cartFavourableAmount($user_id,$favourable, $act_sel_id, $ru_id);

        /* 金额上限为0表示没有上限 */
        return $amount >= $favourable['min_amount'] && ($amount <= $favourable['max_amount'] || $favourable['max_amount'] == 0);
    }

    /**
     * 购物车中是否已经有某优惠
     * @param   array $favourable 优惠活动
     * @param   array $cart_favourable购物车中已有的优惠活动及数量
     */
    public function favourableUsed($favourable, $cart_favourable)
    {
        if ($favourable['act_type'] == FAT_GOODS) {
            return isset($cart_favourable[$favourable['act_id']]) && $cart_favourable[$favourable['act_id']] >= $favourable['act_type_ext'] && $favourable['act_type_ext'] > 0;
        } else {
            return isset($cart_favourable[$favourable['act_id']]);
        }
    }

    /**
     * 取得某用户等级当前时间可以享受的优惠活动
     * @param int $user_id 会员id
     * @param int $ru_id 商家id
     * @param int $fav_id 优惠活动ID
     * @param int $ru_id 传参 显示赠品商品
     * @return  array
     */
    public function favourable_list($user_id = 0,$ru_id = 0,$act_sel_id = [])
    {
        $shopconfig = app('App\Repositories\ShopConfig\ShopConfigRepository');
        $timeFormat = $shopconfig->getShopConfigByCode('time_format');

        $list = $this->activityRepository->activityListAll($ru_id);
        /* 购物车中已有的优惠活动及数量 */
        $used_list = $this->cartRepository->cartFavourable($user_id, $ru_id);
        
        $favourable_list = [];
        if ($list) {
            foreach ($list as $favourable) {
                $favourable['start_time'] = local_date($timeFormat, $favourable['start_time']);
                $favourable['end_time'] = local_date($timeFormat, $favourable['end_time']);
                $favourable['formated_min_amount'] = price_format($favourable['min_amount'], false);
                $favourable['formated_max_amount'] = price_format($favourable['max_amount'], false);
                $favourable['gift'] = unserialize($favourable['gift']);
                foreach ($favourable['gift'] as $key => $value) {                    
                    $goods = $this->goodsRepository->find($value['id']);   //查找商品
                    $cart_gift_num = $this->cartRepository->goodsNumInCartGift($user_id, $value['id']);//赠品在购物车数量
                    if (!empty($goods)) {
                        $favourable['gift'][$key]['ru_id'] = $favourable['user_id'];
                        $favourable['gift'][$key]['act_id'] = $favourable['act_id'];
                        $favourable['gift'][$key]['formated_price'] = price_format($value['price'], false);                        
                        // 赠品缩略图
                        $favourable['gift'][$key]['thumb_img'] = get_image_path($goods['goods_thumb']);
                        $favourable['gift'][$key]['is_checked'] = $cart_gift_num ? true : false;
                    } else {
                        unset($favourable['gift'][$key]);
                    }
                }
                
                //$favourable['act_range_desc'] = act_range_desc($favourable);
                //$favourable['act_type_desc'] = sprintf($fat_ext_lang[$favourable['act_type']], $favourable['act_type_ext']);

                //是否能享受 
                $favourable['available'] = $this->favourableAvailable($user_id,$favourable, $act_sel_id);
                
                if ($favourable['available']) {
                    //是否尚未享受
                    $favourable['available'] = !$this->favourableUsed($favourable, $used_list);
                }

               /* $favourable['act_range_ext'] = return_act_range_ext($favourable['act_range_ext'], $favourable['userFav_type'], $favourable['act_range']);*/

                $favourable_list[] = $favourable;
            }
        }
        return $favourable_list;

    }


    // 对优惠商品进行归类
    public function sort_favourable($favourable_list)
    {
        $arr = array();
        foreach ($favourable_list as $key => $value)
        {
            switch ($value['act_range'])
            {
                case FAR_ALL:
                    $arr['by_all'][$key] = $value;
                    break;
                case FAR_CATEGORY:
                    $arr['by_category'][$key] = $value;
                    break;
                case FAR_BRAND:
                    $arr['by_brand'][$key] = $value;
                    break;
                case FAR_GOODS:
                    $arr['by_goods'][$key] = $value;
                    break;
                default:
                    break;
            }
        }
        return $arr;
    }

    // 获取活动id数组
    public function getFavourableId($favourable)
    {
        $arr = array();
        foreach ($favourable as $key => $value)
        {
            $arr[$key] = $value['act_id'];
        }

        return $arr;
    }

    /**
     * 推荐商品
     * @return mixed
     */
    private function getBestGoods()
    {
        $list =$this->goodsRepository->findByType('best');

        $bestGoods = array_map(function ($v) {
            return [
                'goods_id' => $v['goods_id'],
                'goods_name' => $v['goods_name'],
                'market_price' => $v['market_price'],
                'market_price_formated' => price_format($v['market_price'], false),
                'shop_price' => $v['shop_price'],
                'shop_price_formated' => price_format($v['shop_price'], false),
                'goods_thumb' => get_image_path($v['goods_thumb']),
            ];
        }, $list);

        return $bestGoods;
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
            'goods_number' => 0,
            'total_number' => 0,
        ];

        $goods = $this->goodsRepository->find($params['id']);   //查找商品

        if ($goods['is_on_sale'] != 1) {
            return '商品已下架';
        }

        // 货品
        $goodsAttr = empty($params['attr_id']) ? '' : json_decode($params['attr_id'], 1);
        $goodsAttrId = implode(',', $goodsAttr);
        $product = $this->goodsRepository->getProductByGoods($params['id'], implode('|', $goodsAttr));
        if (empty($product)) {
            $product['id'] = 0;
        }
        // 商品属性文字输出
        $attrName = $this->goodsAttrRepository->getAttrNameById($goodsAttr);

        $attrNameStr = '';
        foreach ($attrName as $v) {
            $attrNameStr .= $v['attr_name'] . ':'. $v['attr_value'] . " \n";
        }

        // 计算商品价格
        $goodsPrice = $this->goodsRepository->getFinalPrice($params['id'], $params['num'], 1, $goodsAttr);

        // 判断购物车是否已经添加
        $cart = $this->cartRepository->getCartByGoods($params['uid'], $params['id'], $goodsAttrId);
        $cart_num = isset($cart['goods_number']) ? $cart['goods_number'] : 0;
        if(($params['num']+$cart_num) > $goods['goods_number']){
            return '库存不足';
        }
        if (!empty($cart)) {
            // 已有商品  则更新商品数量
            $goodsNumber = $params['num']+$cart['goods_number'];
            $res = $this->cartRepository->update($params['uid'], $cart['rec_id'], $goodsNumber);
            if ($res) {
                $number = $this->cartRepository->goodsNumInCartByUser($params['uid']);
                $result['goods_number'] = $goodsNumber;
                $result['total_number'] = $number;
            }
        } else {
            // 添加参数
            $arguments = [
                'goods_id' => $params['id'],
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
                'rec_type' => 0,  // 普通商品
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
                'is_checked' => '',
            ];

            $goodsNumber = $this->cartRepository->addGoodsToCart($arguments);
            $number = $this->cartRepository->goodsNumInCartByUser($params['uid']);

            $result['goods_number'] = $goodsNumber;
            $result['total_number'] = $number;
        }

        return $result;
    }


    /**
     * 添加优惠活动（赠品）到购物车
     * @param $params
     * @return bool
     */
    public function addGiftCart($params)
    {
        $result = [
            'error' => 0,
            'message' => '',
        ];

        //$select_gift = explode(',', $params['select_gift']);//选中赠品id 
        $select_gift = $params['select_gift'];  //选中赠品id 

        /** 取得优惠活动信息 */
        $favourable = $this->activityRepository->detail($params['act_id']); 
       
        if (!empty($favourable)) {
            $favourable['gift'] = unserialize($favourable['gift']);
            if ($favourable['act_type'] == FAT_GOODS) {
                $favourable['act_type_ext'] = round($favourable['act_type_ext']);
            }
        }else{
            $result['error'] = 1;
            $result['message'] = '您要加入购物车的优惠活动不存在';
            return $result;
        }

        /** 判断用户能否享受该优惠 */ 
        if (!$this->favourableAvailable($params['uid'],$favourable)) {
            $result['error'] = 1;
            $result['message'] = '您不能享受该优惠';
            return $result;
        }   

        /** 检查购物车中是否已有该优惠 */        
        $cart_favourable = $this->cartRepository->cartFavourable($params['uid'], $params['ru_id']);
        if ($this->favourableUsed($favourable, $cart_favourable)) {
            $result['error'] = 1;
            $result['message'] = '该优惠活动已加入购物车了';
            return $result;
        }        

        /* 赠品（特惠品）优惠 */
        if ($favourable['act_type'] == FAT_GOODS) {
            /* 检查是否选择了赠品 */
            if (empty($params['select_gift'])) {
                $result['error'] = 1;
                $result['message'] = '请选择赠品（特惠品）';
                return $result;
            }

            /* 检查是否已在购物车 */
            $gift_name = [];
            $goodsname = $this->cartRepository->getGiftCart($params['uid'], $select_gift, $params['act_id']);
            foreach ($goodsname as $key => $value) {
                $gift_name[$key] = $value['goods_name'];
            }
            
            if (!empty($gift_name)) {
                $result['error'] = 1;
                $result['message'] = sprintf('您选择的赠品（特惠品）已经在购物车中了：%s', join(',', $gift_name));
                 return $result;

            }

            /* 检查数量是否超过上限 */
            $count = isset($cart_favourable[$params['act_id']]) ? $cart_favourable[$params['act_id']] : 0;
            if ($favourable['act_type_ext'] > 0 && $count + count($select_gift) > $favourable['act_type_ext']) {
                $result['error'] = 1;
                $result['message'] = '您选择的赠品（特惠品）数量超过上限了';
                return $result;
            }

            $success = false;
            //dump($favourable['gift']);exit;
            /* 添加赠品到购物车 */
            foreach ($favourable['gift'] as $gift)
            {
                if (in_array($gift['id'], $select_gift)) {

                    $goods = $this->goodsRepository->find($gift['id']);   //查找商品
                    // 添加参数
                    $arguments = [
                        'goods_id' => $gift['id'],
                        'user_id' => $params['uid'],
                        'goods_sn' => $goods['goods_sn'],
                        'product_id' => empty($product['id']) ? '' : $product['id'],
                        'group_id' => '',
                        'goods_name' => $goods['goods_name'],
                        'market_price' => $goods['market_price'],
                        'goods_price' => $gift['price'],
                        'goods_number' => 1,
                        'goods_attr' => '',
                        'is_real' => $goods['is_real'],
                        'extension_code' => CART_GENERAL_GOODS,
                        'parent_id' => 0,
                        'rec_type' => 0,  // 普通商品
                        'is_gift' => 1,
                        'is_shipping' => $goods['is_shipping'],
                        'can_handsel' => '',
                        'model_attr' => $goods['model_attr'],
                        'goods_attr_id' => '',
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
                        'is_checked' => '',
                    ];

                    $goodsNumber = $this->cartRepository->addGoodsToCart($arguments);                    
                    $success = true;
                }
            }

            if ($success == true) {
                $result['act_id'] = $params['act_id'];
                $result['ru_id'] = $params['ru_id'];

                $result['error'] = 0;
                $result['message'] = '已加入购物车';
                //dump($result);exit;
                return $result;

            } else {
                $result['error'] = 1;
                $result['message'] = '加入失败';
                 return $result;

            }
        }

        $result['error'] = 1;
        $result['message'] = '加入失败';

        return $result;



        
    }





    /**
     * 更新购物车商品
     * @param $args
     * @return array
     */
    public function updateCartGoods($args)
    {
        $cart = $this->cartRepository->find($args['id']);   //查找商品
        $goods = $this->goodsRepository->find($cart['goods_id']);   //查找商品
        if ($args['amount'] > $goods['goods_number']) {
            return ['code' => 1, 'msg' => '库存不足'];
        }
        $res = $this->cartRepository->update($args['uid'], $args['id'], $args['amount']);
        if ($res) {
            //成功
            return ['code' => 0, 'msg' => '添加成功'];
        }
        return ['code' => 1, 'msg' => '添加失败'];
    }

    /**
     * 删除购物车商品
     * @param $args
     * @return array
     */
    public function deleteCartGoods($args)
    {
        $res = $this->cartRepository->deleteOne($args['id'], $args['uid']);

        $result = [];
        switch ($res) {
            case 0:
                $result['code'] = 1;
                $result['msg'] = '购物车中没有该商品';
                break;
            case 1:
                $result['code'] = 0;
                $result['msg'] = '删除一个商品';
                break;
            default:
                $result['code'] = 1;
                $result['msg'] = '删除失败';
                break;
        }

        return $result;
    }
}
