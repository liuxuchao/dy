<?php

namespace App\Services;

use App\Extensions\Wxapp;
use Illuminate\Http\Request;
use App\Repositories\Shop\ShopRepository;
use App\Repositories\Team\TeamRepository;
use App\Repositories\Goods\GoodsRepository;
use App\Repositories\Goods\GoodsAttrRepository;
use App\Repositories\Cart\CartRepository;
use App\Repositories\Store\StoreRepository;
use App\Repositories\User\UserRepository;
use App\Repositories\Wechat\WxappConfigRepository;
use App\Repositories\Goods\CollectGoodsRepository;
use App\Repositories\ShopConfig\ShopConfigRepository;
use App\Services\AccountService;
use App\Services\UserService;
use App\Repositories\Order\OrderRepository;


/**
 * Class teamRepository
 * @package App\Services
 */
class TeamService
{

    private $teamRepository;
    private $shopRepository;
    private $goodsRepository;
    private $goodsAttrRepository;
    private $cartRepository;
    private $StoreRepository;
    private $userRepository;
    private $WxappConfigRepository;
	private $collectGoodsRepository;
	private $shopConfigRepository;
	private $shopService;
    private $accountService;
    private $userService;
    private $orderRepository;
    private $root_url;

    /**
     * IndexService constructor.
     * @param TeamRepository $teamRepository
     * @param ShopRepository $shopRepository
     * @param GoodsAttrRepository $goodsAttrRepository
     * @param CartRepository $cartRepository
     * @param Request $request
     */
    public function __construct(
        TeamRepository $teamRepository,
        ShopRepository $shopRepository,
        GoodsRepository $goodsRepository,
        GoodsAttrRepository $goodsAttrRepository,
        CartRepository $cartRepository,
        StoreRepository $StoreRepository,
        UserRepository $userRepository,
        WxappConfigRepository $WxappConfigRepository,
		CollectGoodsRepository $collectGoodsRepository,
		ShopConfigRepository $shopConfigRepository,
		ShopService $shopService,
        AccountService $accountService,
        UserService $userService,
        OrderRepository $orderRepository,
        Request $request
    ){
        $this->teamRepository = $teamRepository;
        $this->shopRepository = $shopRepository;
        $this->goodsRepository = $goodsRepository;
        $this->goodsAttrRepository = $goodsAttrRepository;
        $this->cartRepository = $cartRepository;
        $this->StoreRepository = $StoreRepository;
        $this->userRepository = $userRepository;
        $this->WxappConfigRepository = $WxappConfigRepository;
		$this->collectGoodsRepository = $collectGoodsRepository;
		$this->shopConfigRepository = $shopConfigRepository;
		$this->shopService = $shopService;
        $this->accountService = $accountService;
        $this->userService = $userService;
        $this->orderRepository = $orderRepository;
        $this->root_url = dirname(dirname($request->root())) . '/';
    }


	/**
     * 获取拼团首页广告位
     * @return array
     */
    public function getAdsense($position_id = 0)
    {
        $shopconfig = app('App\Repositories\ShopConfig\ShopConfigRepository');
        $number = $shopconfig->getShopConfigByCode('wx_index_show_number');
        if (empty($number)) {
            $number = 10;
        }
        $adsense = $this->teamRepository->teamPositions($position_id, $number);  //获取广告位

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
     * 获取频道广告位
     * @return array
     */
    public function categoriesAdsense($tc_id = 0, $type ='banner')
    {
        $shopconfig = app('App\Repositories\ShopConfig\ShopConfigRepository');
        $number = $shopconfig->getShopConfigByCode('wx_index_show_number');
        if (empty($number)) {
            $number = 10;
        }
        $adsense = $this->teamRepository->categoriesAdsense($tc_id, $type, $number);  //获取广告位
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
     * 获取拼团主频道
     * @return array
     */
    public function teamCategories()
    {
        $arr = [
            'tc_id',       //频道id
            'name',        //频道名称
        ];

        $team_categories_list = $this->teamRepository->teamCategoriesList();  //获取拼团主频道
		$list = [];
		foreach ($team_categories_list as $key => $val) {
			$list[$key]['tc_id'] = $val['id'];
			$list[$key]['name'] = $val['name'];
		}

        return $list;
    }


	/**
     * 获取拼团子频道
     * @return array
     */
    public function teamCategoriesChild($tc_id = 0)
    {
        $arr = [
            'tc_id',       //频道id
            'name',        //频道名称
			'tc_img',      //频道图片
        ];

        $team_categories_child = $this->teamRepository->teamCategoriesChild($tc_id);  //获取拼团主频道
		$list = [];
		foreach ($team_categories_child as $key => $val) {
			$list[$key]['tc_id'] = $val['id'];
			$list[$key]['name'] = $val['name'];
			$list[$key]['tc_img'] = get_image_path($val['tc_img']);
		}
		$team_categories_info = $this->teamRepository->teamCategoriesInfo($tc_id);  //获取频道信息
		$data['list'] = $list;
		$data['title'] = $team_categories_info['name'];
        return $data;
    }

    /**
     *  拼团首页商品列表
     * @return array
     */
    public function teamGoodsList($page = 1, $size = 10, $tc_id = 0)
    {
        $page = empty($page) ? 1 : $page;

        $arr = [
            'id',             //拼团活动id
            'goods_id',       //商品id
            'goods_name',     //商品名
            'shop_price',     //商品价格
            'goods_thumb',    //商品图片
            'team_price',     //拼团价格
            'team_num',       //几人团
			'limit_num'      //已参团人数
        ];
		$type = [];
		if($tc_id > 0){
			$team_categories_child = $this->teamRepository->teamCategoriesChild($tc_id);  //获取拼团主频道
			if(!empty($team_categories_child)){
				foreach ($team_categories_child as $key) {
					$one_id[] = $key['id'];
				}
				$type = $one_id;
			}
            $type[] = $tc_id; 
		}
        
        $goodsList = $this->teamRepository->teamGoodsList($page, $size, $type);
		$list = [];
		foreach ($goodsList as $key => $val) {
			$list[$key]['id'] = $val['id'];
			$list[$key]['goods_id'] = $val['goods_id'];
			$list[$key]['goods_name'] = $val['goods_name'];
			$list[$key]['shop_price'] = price_format($val['shop_price']);
			$list[$key]['goods_thumb'] = get_image_path($val['goods_thumb']);
			$list[$key]['team_price'] = price_format($val['team_price']);
			$list[$key]['team_num'] = $val['team_num'];
			$list[$key]['limit_num'] = $val['limit_num'];
		}

        return $list;
    }

    /**
     * 首页下单提示轮播
     * @return array
     */
    public function virtualOrder($user_id = 0)
    {
        $arr = [
			'error',
            'user_name',
            'user_picture',
			'seconds',
        ];
		if ($this->shopConfigRepository->getShopConfigByCode('virtual_order') == 1) {
			$user = $this->teamRepository->randUserInfo($user_id);  //获取随机用户
			if($user){
				$list = [];
				foreach ($user as $key => $val) {
					$list[$key] = get_object_vars($val);
					//用户名、头像
					$user_info = $this->userRepository->userInfo($list[$key]['user_id']);
					$list[$key]['user_name'] = !empty($user_info['nick_name']) ? $user_info['nick_name'] : $user_info['user_name'];
					$list[$key]['user_picture'] = get_image_path($user_info['user_picture']);
					//随机秒数
					$list[$key]['seconds'] = rand(1, 8) . "秒前";
				}
			}else{
				$list['error'] = 1;
			}
		}else{
			$list['error'] = 1;
		}

        return $list;
    }



	 /**
     *  拼团子频道商品列表
     * @return array
     */
    public function categoryGoodsList($tc_id = 0, $page = 1, $size = 10,$keyword = '',$sortKey = 0, $sortVal = '')
    {
        $page = empty($page) ? 1 : $page;

        $arr = [
            'id',             //拼团活动id
            'goods_id',       //商品id
            'goods_name',     //商品名
            'shop_price',     //商品价格
            'goods_thumb',    //商品图片
            'team_price',     //拼团价格
            'team_num',       //几人团
			'limit_num',      //已参团人数
			'goods_number',   //库存
			'sales_volume'    //销量
        ];

        $goodsList = $this->teamRepository->categoryGoodsList($tc_id, $page, $size,$keyword,$sortKey, $sortVal);
		$list = [];
		foreach ($goodsList as $key => $val) {
			$list[$key]['id'] = $val['id'];
			$list[$key]['goods_id'] = $val['goods_id'];
			$list[$key]['goods_name'] = $val['goods_name'];
			$list[$key]['shop_price'] = price_format($val['shop_price']);
			$list[$key]['goods_thumb'] = get_image_path($val['goods_thumb']);
			$list[$key]['team_price'] = price_format($val['team_price']);
			$list[$key]['team_num'] = $val['team_num'];
			$list[$key]['limit_num'] = $val['limit_num'];
			$list[$key]['goods_number'] = $val['goods_number'];
			$list[$key]['sales_volume'] = $val['sales_volume'];
		}

        return $list;
    }

	 /**
     *  拼团排行商品列表
     * @return array
     */
    public function teamRankingList($page = 1, $size = 10, $type = 0)
    {
        $page = empty($page) ? 1 : $page;
        $arr = [
            'id',             //拼团活动id
            'goods_id',       //商品id
            'goods_name',     //商品名
            'shop_price',     //商品价格
            'goods_thumb',    //商品图片
            'team_price',     //拼团价格
            'team_num',       //几人团
			'limit_num'      //已参团人数
        ];
        $goodsList = $this->teamRepository->teamRankingList($page, $size, $type);
		$list = [];
		foreach ($goodsList as $key => $val) {
			$list[$key]['key'] = $key + 1;
			$list[$key]['id'] = $val['id'];
			$list[$key]['goods_id'] = $val['goods_id'];
			$list[$key]['goods_name'] = $val['goods_name'];
			$list[$key]['shop_price'] = price_format($val['shop_price']);
			$list[$key]['goods_thumb'] = get_image_path($val['goods_thumb']);
			$list[$key]['team_price'] = price_format($val['team_price']);
			$list[$key]['team_num'] = $val['team_num'];
			$list[$key]['limit_num'] = $val['limit_num'];
			$list[$key]['type'] = $type;
		}

        return $list;
    }


	 /**
     * 商品详情
     * @param $goods_id
     * @return array
     */
    public function goodsDetail($goods_id = 0, $uid, $team_id = 0)
    {
		$result = [
            'error' => 0,
            'user_id' => 0,
            'goods_img' => '',         //商品相册
            'goods_info' => '',        //商品信息
            'team_log' => '',          //已成功开团信息
            'new_goods' => '',         //拼团新品
            'goods_properties' => ''   // 商品属性 规格
        ];        
        $result['user_id'] = $uid;

		$time = local_gettime();
		$rootPath = app('request')->root();
        $rootPath = dirname(dirname($rootPath)) . '/';
		$shopconfig = app('App\Repositories\ShopConfig\ShopConfigRepository');
        $timeFormat = $shopconfig->getShopConfigByCode('time_format');
		//是否收藏
		$collect = $this->collectGoodsRepository->findOne($goods_id, $uid);

		// 商品信息
        $goodsInfo = $this->teamRepository->goodsInfo($goods_id);

		//初始值
		$goodsInfo['team_id'] = 0;

		//验证参团活动是否结束
        if ($team_id) {
            $team_info = $this->teamRepository->teamIsFailure($team_id);
            if ($team_info['is_team'] != 1 || $team_info['status'] == 1) {
                return ['error' => 1, 'msg' => '该拼团活动已结束，去查看新的活动吧'];
            }
			$goodsInfo['team_id'] = $team_id;
        }

		if ($goodsInfo['is_on_sale'] == 0) {
            return ['error' => 1, 'msg' => '商品已下架'];
        }
		if(empty($goodsInfo)){
			return ['error' => 1, 'msg' => '该拼团活动已结束，去查看新的活动吧'];
		}

		$goodsInfo['goods_thumb'] = get_image_path($goodsInfo['goods_thumb']);
		$goodsInfo['team_price'] = price_format($goodsInfo['team_price'], true);  //拼团价格
		$goodsInfo['shop_price'] = price_format($goodsInfo['shop_price'], true);
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
        }
		$goodsInfo['is_collect'] = empty($collect) ? 0 : 1;

		$result['goods_info'] = $goodsInfo;  //商品信息

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


		//获取该商品已成功开团信息
        $team_log = $this->teamRepository->teamGoodsLog($goods_id);
		if($team_log){
			foreach ($team_log as $key => $val) {
				$validity_time = $val['start_time'] + ($val['validity_time'] * 3600)+ (8*3600);
				$team_log[$key]['end_time'] = $validity_time; //剩余时间
				//统计该拼团已参与人数
				$team_num = $this->teamRepository->surplusNum($val['team_id']);
				$team_log[$key]['surplus'] = $val['team_num'] - $team_num;//还差几人
				//用户名、头像
				$user_info = $this->userRepository->userInfo($val['team_parent_id']);
				$team_log[$key]['user_name'] = !empty($user_info['nick_name']) ? $user_info['nick_name'] : $user_info['user_name'];
				$team_log[$key]['user_picture'] = get_image_path($user_info['user_picture']);

                //验证是否参团
                $team_log[$key]['is_team'] = 0;
                $team_join = $this->teamRepository->teamJoin($uid, $val['team_id']);
                if($team_join > 0){
                    $team_log[$key]['is_team'] = 1;
                }
				//过滤到期的拼团
				if ($validity_time <= $time) {
					unset($team_log[$key]);
				}
			}
			$result['team_log'] = $team_log;
		}


		//获取拼团新品
        $new_goods = $this->teamRepository->teamNewGoods('is_new', $goodsInfo['user_id']);
		foreach ($new_goods as $key => $val) {
            $new_goods[$key]['goods_thumb'] = get_image_path($val['goods_thumb']);
			$new_goods[$key]['shop_price'] = price_format($val['shop_price'], true);
			$new_goods[$key]['team_price'] = price_format($val['team_price'], true);
        }
        $result['new_goods'] = $new_goods;

		// 商品相册
        $goodsGallery = $this->goodsRepository->goodsGallery($goods_id);
        foreach ($goodsGallery as $k => $v) {
            $goodsGallery[$k] = get_image_path($v['img_url']);
        }
        $result['goods_img'] = $goodsGallery;

		// 商品属性 规格
        $result['goods_properties'] = $this->goodsRepository->goodsProperties($goods_id);

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
    public function goodsPropertiesPrice($goods_id, $attr_id, $num = 1, $warehouse_id = 0, $area_id = 0, $store_id = 0)
    {
        $result = [
            'stock' => '',             //库存
            'market_price' => '',      //市场价
            'qty' => '',               //数量
            'spec_price' => '',        //属性价格
            'goods_price' => '',       //商品价格(最终使用价格)
            'attr_img' => ''           //商品属性图片
        ];

        // 商品信息
        $goodsInfo = $this->teamRepository->goodsInfo($goods_id);
        $result['stock'] = $this->goodsRepository->goodsAttrNumber($goodsInfo['goods_id'], $attr_id, $warehouse_id, $area_id, $store_id);
        $result['market_price'] = $this->goodsRepository->goodsMarketPrice($goodsInfo['goods_id'], $attr_id, $warehouse_id, $area_id);
        $result['market_price_formated'] = price_format($result['market_price'], true);
		$result['qty'] = $num;
        $result['spec_price'] = $this->goodsRepository->goodsPropertyPrice($goodsInfo['goods_id'], $attr_id, $warehouse_id, $area_id);
        $result['spec_price_formated'] = price_format($result['spec_price'], true);
        $result['goods_price'] = $this->teamRepository->getFinalPrice($goodsInfo['goods_id'], $num, true, $attr_id, $warehouse_id, $area_id);
        $result['goods_price_formated'] = price_format($result['goods_price'], true);
        $attr_img = $this->goodsRepository->getAttrImgFlie($goodsInfo['goods_id'], $attr_id);
        if (!empty($attr_img['attr_img_flie'])) {
            $result['attr_img'] = get_image_path($attr_img['attr_img_flie']);
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
            'flow_type' => 0,           //购物车类型
            't_id' => 0,                //拼团活动id
			'team_id' => 0,             //拼团开团id
        ];
		//dump($params);
		$goods = $this->teamRepository->goodsInfo($params['goods_id']);   //拼团商品信息
        if ($goods['is_on_sale'] != 1) {
            return '商品已下架';
        }

        // 货品
		$goodsAttr = empty($params['attr_id']) ? '' : json_decode($params['attr_id'], 1);
		$goodsAttrId = implode(',', $goodsAttr);
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

		// 计算商品价格
        $goodsPrice = $this->teamRepository->getFinalPrice($params['goods_id'], $params['num'], true, $goodsAttr);

        //库存
        $attr_number = $this->goodsRepository->goodsAttrNumber($params['goods_id'], $goodsAttr);

        if ($params['num'] > $attr_number) {
             return '当前库存不足';
        }
		//验证拼团限购数量
		if ($params['num'] > $goods['astrict_num']) {
             return '已超过拼团限购数量';
        }

        /* 更新：清空当前会员购物车中砍价商品 */
        $this->cartRepository->clearCart(CART_TEAM_GOODS,$params['uid']);

        // 添加参数
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
            'rec_type' => CART_TEAM_GOODS,  // 购物车商品类型
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
            $result['flow_type'] = CART_TEAM_GOODS;
            $result['t_id'] = $params['t_id'];
			if ($params['team_id'] > 0) {
				$result['team_id'] = $params['team_id'];
			}
        }

        return $result;
    }



     /**
     * 等待成团页面
     * @param int $uid      会员id
     * @param int $team_id  开团id
     * @param int $user_id  开团会员id
     * @return mixed
     */
    public function teamWait($uid = 0, $team_id = 0, $user_id)
    {
        $result = [
            'error' => 0,
            'team_info' => '',         //拼团信息
            'teamUser' => '',          //已成功开团信息
        ];
		//订单状态
		//$order_info = $this->teamRepository->orderInfo($team_id, $user_id);

		//获取拼团信息
		$team_info = $this->teamRepository->teamInfo($team_id);
		$user_info = $this->userRepository->userInfo($team_info['team_parent_id']);
		$team_info['user_name'] = !empty($user_info['nick_name']) ? $user_info['nick_name'] : $user_info['user_name'];
		$team_info['user_picture'] = get_image_path($user_info['user_picture']);
		$team_info['goods_thumb'] = get_image_path($team_info['goods_thumb']);
		$team_info['team_price'] = price_format($team_info['team_price']);

		$end_time = $team_info['start_time'] + ($team_info['validity_time'] * 3600);//剩余时间
		$team_info['end_time'] =$end_time + (8 * 3600);
		$team_num = $this->teamRepository->surplusNum($team_info['team_id']);  //统计几人参团
		$team_info['surplus'] = $team_info['team_num'] - $team_num;//还差几人
        $team_info['bar'] = round($team_num * 100 / $team_info['team_num'], 0);//百分比

        if ($team_info['status'] != 1 && gmtime() < $end_time && $team_info['is_team'] == 1) {//进行中
            $team_info['status'] = 0;
        } elseif (($team_info['status'] != 1 && gmtime() > $end_time) || $team_info['is_team'] != 1) {//失败
            $team_info['status'] = 2;
        } elseif ($team_info['status'] = 1) {//成功
            $team_info['status'] = 1;
        }

		//验证是否已经参团
		$team_join = $this->teamRepository->teamJoin($uid, $team_id);

        if ($team_join > 0) {
			$team_info['team_join'] = 1;
        }

		$result['team_info'] = $team_info;

		//获取拼团团员信息
		$teamUser = $this->teamRepository->teamUserList($team_id, 1, 5);
		foreach ($teamUser as $key => $val) {
            $user_info = $this->userRepository->userInfo($val['user_id']);
            $teamUser[$key]['user_name'] = !empty($user_info['nick_name']) ? $user_info['nick_name'] : $user_info['user_name'];
            $teamUser[$key]['user_picture'] = get_image_path($user_info['user_picture']);
        }
		$result['teamUser'] = $teamUser;

        return $result;
    }


	/**
     * 拼团成员页面
     * @param int $team_id
     * @param int $page
     * @param int $size
     * @return mixed
     */
    public function teamUser($team_id = 0, $page = 1, $size = 10)
    {
        $page = empty($page) ? 1 : $page;

		//获取拼团团员信息
		$teamUser = $this->teamRepository->teamUserList($team_id, $page, $size);
		$shopconfig = app('App\Repositories\ShopConfig\ShopConfigRepository');
        $timeFormat = $shopconfig->getShopConfigByCode('time_format');
		foreach ($teamUser as $key => $val) {
			$teamUser[$key]['add_time'] = local_date($timeFormat, $val['add_time']); // 时间
            $user_info = $this->userRepository->userInfo($val['user_id']);
            $teamUser[$key]['user_name'] = !empty($user_info['nick_name']) ? $user_info['nick_name'] : $user_info['user_name'];
            $teamUser[$key]['user_picture'] = get_image_path($user_info['user_picture']);
        }
        return $teamUser;
    }


	 /**
     *  我的拼团
     * @return array
     */
    public function teamUserOrder($user_id, $type = 0, $page = 1, $size = 10)
    {
        $this->checkRefund();
        $page = empty($page) ? 1 : $page;
        $arr = [
            'id',             //拼团活动id
            'goods_id',       //商品id
            'goods_name',     //商品名
            'shop_price',     //商品价格
            'goods_thumb',    //商品图片
            'team_price',     //拼团价格
            'team_num',       //几人团
			'limit_num'      //已参团人数
        ];
        $team_order = $this->teamRepository->teamUserOrder($user_id, $type, $page, $size);
		$list = [];
		foreach ($team_order as $key => $val) {
			$list[$key] = get_object_vars($val);
			$list[$key]['id'] = $list[$key]['id'];
			$list[$key]['team_id'] = $list[$key]['team_id'];
			$list[$key]['goods_id'] = $list[$key]['goods_id'];
			$list[$key]['order_id'] = $list[$key]['order_id'];
			$list[$key]['team_id'] = $list[$key]['team_id'];
			$list[$key]['user_id'] = $list[$key]['user_id'];
			$list[$key]['goods_name'] = $list[$key]['goods_name'];
			$list[$key]['shop_price'] = price_format($list[$key]['shop_price']);
			$list[$key]['goods_thumb'] = get_image_path($list[$key]['goods_thumb']);
			$list[$key]['team_price'] = price_format($list[$key]['team_price']);
			$list[$key]['team_num'] = $list[$key]['team_num'];
			$team_num = $this->teamRepository->surplusNum($list[$key]['team_id']);  //统计几人参团
			$list[$key]['limit_num'] = $team_num;
			$list[$key]['type'] = $type;
		}

        return $list;
    }



    /**
     * 检测拼团失败，退款到余额中
     */
    public function checkRefund(){

        //失败拼团订单
        $goods_list = $this->teamRepository->teamUserOrderRefund();
  
        foreach ($goods_list as $key => $val) {
            $list[$key] = get_object_vars($val);
            $list[$key]['id'] = $list[$key]['id'];

            $amount = $list[$key]['goods_amount']+$list[$key]['shipping_fee'];  //退款金额
            //记录会员账目明细
            $info ='拼团订单自动退款到余额，金钱：'. $amount;
            $this->accountService->logAccountChange($list[$key]['user_id'], -$amount, 0, 0, 0, $info, ACT_TRANSFERRED); 

            //记录订单操作记录
            $order_status = 2;
            $action_note = '拼团订单自动退款';
            $this->userService->orderActionChange($list[$key]['order_id'],'admin',$order_status,0,0,$action_note);

            //检查商品库存 
            //--库存管理use_storage 1为开启 0为未启用-------  SDT_PLACE：0为发货时 1为下单时 2为付款时  
            if ($this->shopConfigRepository->getShopConfigByCode('use_storage') == '1' && ($this->shopConfigRepository->getShopConfigByCode('stock_dec_time') == SDT_PLACE || $this->shopConfigRepository->getShopConfigByCode('stock_dec_time') == SDT_PAID)) {
                $this->orderRepository->changeOrderGoodsStorage($list[$key]['order_id'], false, SDT_PLACE);
            }
            
        }
    }





















}
