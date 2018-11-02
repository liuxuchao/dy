<?php

namespace App\Repositories\Cart;

use App\Models\Cart;
use App\Models\ShippingArea;
use App\Services\AuthService;
use App\Models\FavourableActivity;
use App\Repositories\Shop\ShopRepository;
use App\Repositories\Goods\GoodsRepository;
use App\Repositories\User\UserRankRepository;
use App\Repositories\ShopConfig\ShopConfigRepository;
use App\Repositories\Category\CategoryRepository;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * Class CartRepository
 * @package App\Repositories\cart
 */
class CartRepository
{
    const FAR_ALL = 0; // 全部商品
    const FAR_CATEGORY = 1; // 按分类选择
    const FAT_PRICE = 1; // 现金减免
    const FAT_DISCOUNT = 2; // 价格打折优惠
    const FAR_BRAND = 2; // 按品牌选择
    const FAR_GOODS = 3; // 按商品选择
    private $model;
    private $shopConfigRepository;
    private $userRankRepository;
    private $authService;
    private $goodsRepository;
    private $shopRepository;
    private $categoryRepository;

    /**
     * CartRepository constructor.
     * @param ShopConfigRepository $shopConfigRepository
     * @param UserRankRepository $userRankRepository
     * @param AuthService $authService
     * @param GoodsRepository $goodsRepository
     * @param ShopRepository $shopRepository
     */
    public function __construct(
        ShopConfigRepository $shopConfigRepository,
        UserRankRepository $userRankRepository,
        AuthService $authService,
        GoodsRepository $goodsRepository,
        ShopRepository $shopRepository,
        CategoryRepository $categoryRepository
    ) {
        $this->shopConfigRepository = $shopConfigRepository;
        $this->userRankRepository = $userRankRepository;
        $this->authService = $authService;
        $this->goodsRepository = $goodsRepository;
        $this->shopRepository = $shopRepository;
        $this->categoryRepository = $categoryRepository;
        $this->model = Cart::where('rec_id', '<>', 0);
    }


    /**
     * 添加字段
     * @param $columns
     * @return Cart
     */
    public function field($columns)
    {
        $this->model->select($columns);
        return $this;
    }

    /**
     * 根据ID返回购物商品
     * @param $rec_id
     * @return mixed
     */
    public function find($rec_id)
    {
        $cart = $this->model->where('rec_id', $rec_id)
            ->first();
        if ($cart === null) {
            return [];
        }

        return $cart->toArray();
    }

    /**
     * 商品添加到购物车
     * @param $params
     * @return bool
     */
    public function addGoodsToCart($params)
    {
        $model = new Cart();

        foreach ($params as $k => $v) {
            $model->{$k} = $v;
        }

        $res = $model->save();
        if ($res) {
            return $model->goods_number;
        }
        return false;
    }

    /**
     * 获取购物车所有商品
     * 暂时没有用
     * @return mixed
     */
    public function getAllCartGoods()
    {
        $cart = Cart::select('rec_id', 'user_id', 'goods_id', 'goods_name', 'market_price', 'goods_price', 'goods_number', 'goods_attr', 'ru_id')
            ->get()
            ->toArray();

        return $cart;
    }

    /**
     * 根据  商品ID 商品属性 用户ID 获取购物车记录
     * @param $uid
     * @param $goodsId
     * @param $goodsAttr
     * @return mixed
     */
    public function getCartByGoods($uid, $goodsId, $goodsAttr = '')
    {
        $cart = Cart::where('user_id', $uid)
            ->where('goods_id', $goodsId)
            ->where('goods_attr_id', $goodsAttr)
            ->where('rec_type', 0)
            ->first();

        if ($cart === null) {
            return [];
        }

        return $cart->toArray();
    }

    /**
     * 根据用户ID查询  购物车商品数量
     * @param $id  用户ID
     * @return mixed
     */
    public function goodsNumInCartByUser($id, $flow_type = 0)
    {
        $cart_list = Cart::where('user_id', $id)
            ->where('rec_type', $flow_type)//普通商品
            ->sum('goods_number');

        return $cart_list;
    }



    /**
     * 根据用户ID查询  购物车商品信息
     * @param $id  用户ID
     * @return mixed
     */
    public function getTeamGoodsInCart($id, $flow_type = 0)
    {
        $cart = Cart::select('*')
            ->where('user_id', $id)
            ->where('rec_type', $flow_type)
            ->first();

        if ($cart === null) {
            return [];
        }

         return $cart->toArray();
    }

    /**
     * 根据用户ID获取购物车商品列表
     * @param $id
     * @return mixed
     */
    public function getGoodsInCartByUser($id, $flow_type = 0)
    {
        $cart_list = Cart::select('cart.*')
            ->with(['goods' => function ($query) {
                $query->select('goods_id', 'cat_id', 'brand_id', 'goods_name', 'goods_thumb', 'freight', 'tid', 'goods_weight', 'shipping_fee', 'cloud_id', 'cloud_goodsname', 'dis_commission', 'is_distribution');
            }])
            ->where('user_id', $id)
            ->where('rec_type', $flow_type)//普通商品
            ->orderby('rec_id', 'desc')
            ->get()
            ->toArray();
        $cart = array();
        foreach ($cart_list as $key => $value) {
            if(isset($value['goods']['goods_id'])){
                $cart[$key] = $value;
            }
        }

        $total = ['goods_price' => 0, 'market_price' => 0, 'goods_number' => 0];
        $goods_list = [];
        /* 用于统计购物车中实体商品和虚拟商品的个数 */
        $virtual_goods_count = 0;
        $real_goods_count = 0;

        foreach ($cart as $v) {

            // 计算总价
            $total['goods_price'] += $v["goods_price"] * $v['goods_number'];
            $total['market_price'] += $v["market_price"] * $v['goods_number'];
            $total['goods_number'] += $v["goods_number"];

            $v['subtotal'] = price_format($v['goods_price'] * $v['goods_number'], false);
            $v['goods_price_format'] = price_format($v['goods_price'], false);
            $v['market_price_format'] = price_format($v['market_price'], false);

            /* 统计实体商品和虚拟商品的个数 */

            if ($v['is_real']) {
                $real_goods_count++;
            } else {
                $virtual_goods_count++;
            }

            //店铺名称
            $shopInfo = $this->shopRepository->findBY('ru_id', $v['ru_id']);

            if (count($shopInfo) > 0) {
                $shopInfo = $shopInfo[0];
                if ($shopInfo['shopname_audit'] == 1) {
                    if ($v['ru_id'] > 0) {
                        $v['shop_name'] = $shopInfo['brandName'] . $shopInfo['shopNameSuffix'];
                    } else {
                        $v['shop_name'] = $shopInfo['shop_name'];
                    }
                } else {
                    $v['shop_name'] = $shopInfo['rz_shopName'];
                }
            } else {
                $v['shop_name'] = "";
            }

            $goods_list[] = $v;
        }

        $tmpArray = [];
        $goodslist = [];
        foreach ($goods_list as $key => $row) {
            $row['goods']['rec_id'] = $row['rec_id'];
            $row['goods']['user_id'] = $row['user_id'];
            $row['goods']['market_price'] = $row['market_price'];
            $row['goods']['goods_price'] = $row['goods_price'];
            $row['goods']['goods_number'] = $row['goods_number'];
            $row['goods']['goods_attr'] = $row['goods_attr'];
            $row['goods']['is_real'] = $row['is_real'];
            $row['goods']['goods_attr_id'] = $row['goods_attr_id'];
            $row['goods']['is_shipping'] = $row['is_shipping'];
            $row['goods']['ru_id'] = $row['ru_id'];
            $row['goods']['warehouse_id'] = $row['warehouse_id'];
            $row['goods']['stages_qishu'] = $row['stages_qishu'];
            $row['goods']['add_time'] = $row['add_time'];
            $row['goods']['goods_sn'] = $row['goods_sn'];
            $row['goods']['product_id'] = $row['product_id'];
            $row['goods']['extension_code'] = $row['extension_code'];
            $row['goods']['parent_id'] = $row['parent_id'];
            $row['goods']['is_gift'] = $row['is_gift'];
            $row['goods']['model_attr'] = $row['model_attr'];
            $row['goods']['area_id'] = $row['area_id'];

            $a = $row['ru_id'];
            $tmpArray[$a]['shop_name'] = $row['shop_name'];
            $tmpArray[$a]['user_id'] = $row['user_id'];
            $tmpArray[$a]['ru_id'] = $row['ru_id'];

            $tmpArray[$a]['goods'][] = $row['goods'];
            $goodslist[$key]['goods'] = $row['goods'];
        }

        foreach ($tmpArray as $key => $value) {
            //商家配送方式
            $shipping = ShippingArea::select('shipping_area.*')
                ->with(['shipping' => function ($query) {
                    $query->select('shipping_id', 'shipping_name', 'insure');
                }])
                ->where('ru_id', $value['ru_id'])
                ->get()
                ->toArray();

            $ship = [];
            foreach ($shipping as $k => $val) {
                if ($val['ru_id'] == $value['ru_id']) {
                    $val['shipping']['ru_id'] = $val['ru_id'];
                    $val['shipping']['configure'] = $val['configure'];
                    $ship[] = $val['shipping'];
                }
            }
            $tmpArray[$key]['shop_info'] = $ship;
        }
        /* 计算折扣 */
        $total['discount'] = $this->computeDiscountCheck($tmpArray);

        $total['goods_price'] = $total['goods_price'] - $total['discount'];
        $total['discount'] = $total['discount'];
        $total['saving'] = $total['market_price'] - $total['goods_price'];
        $total['saving_formated'] = price_format($total['market_price'] - $total['goods_price'], false);
        if ($total['market_price'] > 0) {
            $total['save_rate'] = $total['market_price'] ? round(($total['market_price'] - $total['goods_price']) *
                    100 / $total['market_price']) . '%' : 0;
        }
        $total['discount_formated'] = price_format($total['discount'], false);
        $total['goods_price_formated'] = price_format($total['goods_price'], false);
        $total['market_price_formated'] = price_format($total['market_price'], false);
        $total['real_goods_count'] = $real_goods_count;
        $total['virtual_goods_count'] = $virtual_goods_count;

        return ['goods_list' => $tmpArray, 'total' => $total, 'product' => $goodslist];
    }

    /**
     * 更新购物车
     * @param $uid
     * @param $id 购物车ID
     * @param $num
     * @param array $attr
     * @return boolean
     */
    public function update($uid, $id, $num, $attr = [])
    {
        $cart = Cart::where('user_id', $uid)
            ->where('rec_id', $id)
            ->first();
        if ($cart === null) {
            return false;
        }

        $cart->goods_number = $num;
        return $cart->save();
    }

    /**
     * 删除购物车商品
     * @param $id
     * @param $uid
     */
    public function deleteOne($id, $uid)
    {
        return Cart::where('rec_id', $id)
            ->where('user_id', $uid)
            ->delete();
    }

    /**
     * 删除全部
     * @param $arr
     */
    public function deleteAll($arr)
    {
        $cartModel = new Cart();

        foreach ($arr as $k => $v) {
            if (count($v) == 3 && $v[0] == 'in') {
                $cartModel = $cartModel->whereIn($v[1], $v[2]);
            } elseif (count($v) == 2) {
                $cartModel = $cartModel->where($v[0], $v[1]);
            }
        }

        $cartModel->delete();
    }

    /**
     * 根据类型删除购物车商品
     * @param $type
     * @param $uid
     */
    public function clearCart($type, $uid)
    {
        return Cart::where('rec_type', $type)
            ->where('user_id', $uid)
            ->delete();
    }


    /**
     * 计算购物车中的商品能享受红包支付的总额
     * @param $order_products
     * @return  float   享受红包支付的总额
     */
    public function computeDiscountCheck($order_products)
    {
        /* 查询优惠活动 */
        $now = local_gettime();
        $user_rank = $this->userRankRepository->getUserRankByUid();
        $user_rank = ',' . $user_rank['rank_id'] . ',';

        $favourable_list = FavourableActivity::where('start_time', '<=', $now)
            ->where('end_time', '>=', $now)
            ->whereraw("CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'")
            ->wherein('act_type', [self::FAT_DISCOUNT, self::FAT_PRICE])
            ->get()
            ->toArray();

        if (!$favourable_list) {
            return 0;
        }
        $goods_list = $order_products;
        foreach ($goods_list as $key => $good) {
            foreach ($good['goods'] as $k => $v) {
                $good_property = [];
                if ($v['goods_attr_id']) {
                    $good_property = explode(',', $v['goods_attr_id']);
                }
                $goods_list[$key]['price'] = $this->goodsRepository->getFinalPrice($v['goods_id'], $v['goods_number'], true, $good_property);
                $goods_list[$key]['amount'] = $v['goods_number'];
            }
        }
        if (!$goods_list) {
            return 0;
        }

        /* 初始化折扣 */
        $discount = 0;
        $favourable_name = [];

        /* 循环计算每个优惠活动的折扣 */
        foreach ($favourable_list as $favourable) {
            $total_amount = 0;
            if ($favourable['act_range'] == self::FAR_ALL) {
                foreach ($goods_list as $goods) {
                    foreach ($goods['goods'] as $v) {
                        $total_amount += $v['goods_price'] * $v['goods_number'];
                    }
                }
            } elseif ($favourable['act_range'] == self::FAR_CATEGORY) {
                // /* 找出分类id的子分类id */
                $id_list = [];
                $raw_id_list = explode(',', $favourable['act_range_ext']);
                foreach ($raw_id_list as $id)
                {
                    $cat_list = $this->categoryRepository->arr_foreach($this->categoryRepository->catList($id));
                    $id_list = array_merge($id_list, $cat_list);
                    array_unshift($id_list, $id);
                }
                $ids = join(',', array_unique($id_list));
                foreach ($goods_list as $goods)
                {
                    foreach ($goods['goods'] as $v) {
                        if (strpos(',' . $ids . ',', ',' . $v['cat_id'] . ',') !== false)
                        {
                            $total_amount += $v['goods_price'] * $v['goods_price'];
                        }
                    }
                }
            } elseif ($favourable['act_range'] == self::FAR_BRAND) {
                foreach ($goods_list as $goods) {
                    foreach ($goods['goods'] as $v) {
                        if (strpos(',' . $favourable['act_range_ext'] . ',', ',' . $v['brand_id'] . ',') !== false) {
                            $total_amount += $v['goods_price'] * $v['goods_number'];
                        }
                    }

                }
            } elseif ($favourable['act_range'] == self::FAR_GOODS) {
                foreach ($goods_list as $goods) {
                    foreach ($goods['goods'] as $v) {
                        if (strpos(',' . $favourable['act_range_ext'] . ',', ',' . $v['goods_id'] . ',') !== false) {
                            $total_amount += $v['goods_price'] * $v['goods_number'];
                        }
                    }
                }
            } else {
                continue;
            }
            if ($total_amount > 0 && $total_amount >= $favourable['min_amount'] && ($total_amount <= $favourable['max_amount'] || $favourable['max_amount'] == 0)) {
                if ($favourable['act_type'] == self::FAT_DISCOUNT) {
                    $discount += $total_amount * (1 - $favourable['act_type_ext'] / 100);
                } elseif ($favourable['act_type'] == self::FAT_PRICE) {
                    $discount += $favourable['act_type_ext'];
                }
            }
        }
        return $discount;
    }


    /**
     *  分单计算折扣：根据购物车和优惠活动
     * @param $order_products
     * @return  float
     */
    public function childOrderDiscount($order_products)
    {
        /* 查询优惠活动 */
        $now = local_gettime();
        $user_rank = $this->userRankRepository->getUserRankByUid();
        $user_rank = ',' . $user_rank['rank_id'] . ',';

        $favourable_list = FavourableActivity::where('start_time', '<=', $now)
            ->where('end_time', '>=', $now)
            ->whereraw("CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'")
            ->wherein('act_type', [self::FAT_DISCOUNT, self::FAT_PRICE])
            ->get()
            ->toArray();

        if (!$favourable_list) {
            return 0;
        }
        $goods_list = $order_products;
        if (!$goods_list) {
            return 0;
        }

        /* 初始化折扣 */
        $discount = 0;
        $favourable_name = [];

        /* 循环计算每个优惠活动的折扣 */
        foreach ($favourable_list as $favourable) {
            $total_amount = 0;
            if ($favourable['act_range'] == self::FAR_ALL) {
                foreach ($goods_list as $goods) {
                    $total_amount += $goods['goods_price'] * $goods['goods_number'];
                }
            } elseif ($favourable['act_range'] == self::FAR_CATEGORY) {
                // /* 找出分类id的子分类id */
                $id_list = [];
                $raw_id_list = explode(',', $favourable['act_range_ext']);
                foreach ($raw_id_list as $id)
                {
                    $cat_list = $this->categoryRepository->arr_foreach($this->categoryRepository->catList($id));
                    $id_list = array_merge($id_list, $cat_list);
                    array_unshift($id_list, $id);
                }
                $ids = join(',', array_unique($id_list));
                foreach ($goods_list as $goods)
                {
                    if (strpos(',' . $ids . ',', ',' . $goods['cat_id'] . ',') !== false)
                    {
                        $total_amount += $goods['goods_price'] * $goods['goods_price'];
                    }

                }
            } elseif ($favourable['act_range'] == self::FAR_BRAND) {
                foreach ($goods_list as $goods) {
                    if (strpos(',' . $favourable['act_range_ext'] . ',', ',' . $goods['brand_id'] . ',') !== false) {
                        $total_amount += $goods['goods_price'] * $goods['goods_number'];
                    }
                }
            } elseif ($favourable['act_range'] == self::FAR_GOODS) {
                foreach ($goods_list as $goods) {
                    if (strpos(',' . $favourable['act_range_ext'] . ',', ',' . $goods['goods_id'] . ',') !== false) {
                        $total_amount += $goods['goods_price'] * $goods['goods_number'];
                    }
                }
            } else {
                continue;
            }
            if ($total_amount > 0 && $total_amount >= $favourable['min_amount'] && ($total_amount <= $favourable['max_amount'] || $favourable['max_amount'] == 0)) {
                if ($favourable['act_type'] == self::FAT_DISCOUNT) {
                    $discount += $total_amount * (1 - $favourable['act_type_ext'] / 100);
                } elseif ($favourable['act_type'] == self::FAT_PRICE) {
                    $discount += $favourable['act_type_ext'];
                }
            }
        }
        return $discount;
    }

    /**
     * 取得购物车该赠送的积分数
     * @return  int     积分数
     */
    public function getGiveIntegral()
    {
        $uid = $this->authService->authorization();

        $allIntegral = Cart::from("cart as c")
            ->select(['c.*', 'g.give_integral as give_integral'])
            ->leftjoin('goods as g', 'c.goods_id', '=', 'g.goods_id')
            ->where('c.goods_id', '>', 0)
            ->where('c.parent_id', 0)
            ->where('c.rec_type', 0)
            ->where('c.is_gift', 0)
            ->where('c.user_id', $uid)
            ->get()
            ->toArray();

        $sum = 0;
        foreach ($allIntegral as $key => $value) {
            $giveIntegral = empty($value['give_integral']) ? 0 : $value['give_integral'];
            if ($giveIntegral > -1) {
                $sum += $giveIntegral * $value['goods_number'];
            } else {
                $sum += $value['goods_price'] * $value['goods_number'];
            }
        }

        return $sum;
    }

    /**
     * 查看购物车中是否全为免运费商品，若是则把运费赋为零
     */
    public function fee_goods ($sess_id, $ru_id, $where) {
        $prefix = Config::get('database.connections.mysql.prefix');

        $sql = 'SELECT count(*) FROM ' . $prefix . "cart WHERE " . $sess_id . " AND `extension_code` != 'package_buy' AND `is_shipping` = 0 AND ru_id = '" . $ru_id . "'" . $where;

        $shipping_count = DB::select($sql);
        $shipping_count = get_object_vars($shipping_count[0]);

        return $shipping_count['count(*)'];
    }


    /**
     * 优惠范围内的商品总额
     */
    public function cartFavourableAmount ($user_id, $favourable, $act_sel_id = array('act_sel_id' => '','act_pro_sel_id' => '', 'act_sel' => ''), $ru_id = -1){

        $prefix = Config::get('database.connections.mysql.prefix');

        $fav_where = "";
        if ($favourable['userFav_type'] == 0) {
            $fav_where = " AND g.user_id = '" . $favourable['user_id'] . "' ";
        } else {
            if ($ru_id > -1) {
                $fav_where = " AND g.user_id = '$ru_id' ";
            }
        }
        if (!empty($act_sel_id['act_sel']) && ($act_sel_id['act_sel'] == 'cart_sel_flag')) {
            $sel_id_list = explode(',', $act_sel_id['act_sel_id']);
            $fav_where .= "AND c.rec_id " . db_create_in($sel_id_list);
        }
        //ecmoban模板堂 --zhuo end

        /* 查询优惠范围内商品总额的sql */
        $sql = "SELECT SUM(c.goods_price * c.goods_number) as goods_price " .
            " FROM " . $prefix . "cart AS c, " . $prefix . "goods AS g " .
            " WHERE c.goods_id = g.goods_id " .
            " AND c.user_id = $user_id AND c.rec_type = '" . CART_GENERAL_GOODS . "' " .
            " AND c.is_gift = 0 " .
            " AND c.goods_id > 0 " . $fav_where; //ecmoban模板堂 --zhuo

        $id_list = [];
        $list_array = [];

        $amount = 0;
        if ($favourable) {
            /* 根据优惠范围修正sql */
            if ($favourable['act_range'] == FAR_ALL) {
                // sql do not change
            } elseif ($favourable['act_range'] == FAR_CATEGORY) {
                /* 取得优惠范围分类的所有下级分类 */
                $cat_list = explode(',', $favourable['act_range_ext']);
                foreach ($cat_list as $id) {
                    $id_list = $this->categoryRepository->arr_foreach($this->categoryRepository->catList($id));
                }
                $id_list = implode(',', $id_list);
                $sql .= "AND g.cat_id in ($id_list)";

            } elseif ($favourable['act_range'] == FAR_BRAND) {
                $id_list = $favourable['act_range_ext'];
                $sql .= "AND g.brand_id in ($id_list)";

            } elseif ($favourable['act_range'] == FAR_GOODS) {
                $id_list = $favourable['act_range_ext'];
                $sql .= "AND g.goods_id in ($id_list)" ;

            }

            /* 优惠范围内的商品总额 */
            $amount = DB::select($sql);

            $amount = get_object_vars($amount[0]);
        }

        return $amount['goods_price'];


    }



    /**
     * 取得购物车中已有的优惠活动及数量
     *
     * @return array
     */
    public function cartFavourable($user_id, $ru_id = -1)
    {
        $prefix = Config::get('database.connections.mysql.prefix');
        $where = '';
        if($ru_id > -1){
            $where .= " AND ru_id = '$ru_id'";
        }

        $sql = "SELECT is_gift, COUNT(*) AS num " . "FROM {$prefix}cart  WHERE user_id = $user_id  AND rec_type = '" . CART_GENERAL_GOODS . "'" . " AND is_gift > 0 " . $where.  " GROUP BY is_gift";
        $res = DB::select($sql);

        $list = [];
        if ($res) {
            foreach ($res as $row) {
                $row = get_object_vars($row);
                $list [$row ['is_gift']] = $row ['num'];
            }
        }
        return $list;
    }


    /**
     * 检查是否已在购物车
     * @return mixed
     */
    public function getGiftCart($user_id = 0, $is_gift_cart = [], $act_id = 0)
    {
        $cart = Cart::select('goods_name')
            ->where('user_id', $user_id)
            ->wherein('goods_id', $is_gift_cart)
            ->where('is_gift', $act_id)
            ->where('rec_type', CART_GENERAL_GOODS)
            ->get()
            ->toArray();

        return $cart;
    }

    /**
     * 查询  购物车商赠品数量
     * @param $id  用户ID
     * @param $goods_id  用户ID
     * @return mixed
     */
    public function goodsNumInCartGift($id, $goods_id = 0)
    {
        $cart_list = Cart::where('user_id', $id)
            ->where('goods_id', $goods_id)
            ->where('is_gift', 1)
            ->sum('goods_number');

        return $cart_list;
    }



}
