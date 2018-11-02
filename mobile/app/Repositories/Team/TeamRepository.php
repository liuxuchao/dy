<?php

namespace App\Repositories\Team;

use App\Models\Goods;
use App\Models\OrderInfo;
use App\Models\Users;
use App\Models\Comment;
use App\Models\Products;
use App\Models\TeamCategory;
use App\Models\TeamGoods;
use App\Models\TeamLog;
use App\Models\Attribute;
use App\Models\ActivityGoodsAttr;
use App\Models\GoodsAttr;
use App\Models\GoodsTransport;
use App\Models\ProductsArea;
use App\Services\AuthService;
use App\Models\ProductsWarehouse;
use App\Models\WarehouseAreaAttr;
use App\Models\WarehouseAreaGoods;
use App\Models\StoreGoods;
use App\Models\StoreProducts;
use App\Models\TouchAd;
use App\Models\TouchAdPosition;
use App\Repositories\ShopConfig\ShopConfigRepository;
use App\Repositories\Goods\GoodsRepository;
use App\Repositories\Goods\GoodsAttrRepository;
use App\Repositories\User\UserRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * Class BargainRepository
 * @package App\Repositories\bargain
 */
class TeamRepository
{
    protected $goods;
    private $field;
    private $authService;
    private $goodsAttrRepository;
    private $shopConfigRepository;
    private $goodsRepository;
    private $userRepository;

    public function __construct(
        AuthService $authService,
        GoodsAttrRepository $goodsAttrRepository,
        ShopConfigRepository $shopConfigRepository,
        GoodsRepository $goodsRepository,
        UserRepository $userRepository

    )
    {
        $this->setField();
        $this->authService = $authService;
        $this->goodsAttrRepository = $goodsAttrRepository;
        $this->shopConfigRepository = $shopConfigRepository;
        $this->goodsRepository = $goodsRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * 设置字段
     */
    public function setField()
    {
        $this->field = [
            'category' => 'cat_id'
        ];
    }

    /**
     * 获取字段
     * @param $field
     * @return mixed
     */
    public function getField($field)
    {
        return $this->field[$field];
    }

	 /**
     * 获取拼团首页轮播图
     * @param $position_id
     * @param $num
     * @return array
     */
    public function teamPositions($position_id = 0, $num = 3)
    {
        $time = gmtime();
        $res = TouchAd::select('ad_id', 'touch_ad.position_id', 'media_type', 'ad_link', 'ad_code', 'ad_name')
            ->with(['position'])
            ->join('touch_ad_position', 'touch_ad_position.position_id', '=', 'touch_ad.position_id')
            ->where("start_time", '<=', $time)
            ->where("end_time", '>=', $time)
            ->where("touch_ad_position.position_id", $position_id)
            ->where("enabled", 1)
            ->limit($num)
            ->get()
            ->toArray();

        $res = array_map(function ($v) {
            if (!empty($v['position'])) {
                $temp = array_merge($v, $v['position']);
                unset($temp['position']);
                return $temp;
            }
        }, $res);

        return $res;
    }

	 /**
     * 获取拼团频道轮播图
     * @param $tc_type
     * @param $num
     * @return array
     */
    public function categoriesAdsense($tc_id = 0, $type = 'banner', $num = 3)
    {
        $time = gmtime();
        $res = TouchAd::select('ad_id', 'touch_ad.position_id', 'media_type', 'ad_link', 'ad_code', 'ad_name')
            ->with(['position'])
            ->join('touch_ad_position', 'touch_ad_position.position_id', '=', 'touch_ad.position_id')
            ->where("start_time", '<=', $time)
            ->where("end_time", '>=', $time)
            ->where("touch_ad_position.tc_id", $tc_id)
			->where("touch_ad_position.tc_type", $type)
            ->where("touch_ad_position.ad_type", 'wxapp')
            ->where("enabled", 1)
            ->limit($num)
            ->get()
            ->toArray();

        $res = array_map(function ($v) {
            if (!empty($v['position'])) {
                $temp = array_merge($v, $v['position']);
                unset($temp['position']);
                return $temp;
            }
        }, $res);

        return $res;
    }


	/**
     * 获取拼团主频道
     * @param
     * @return mixed
     */
    public function teamCategoriesList()
    {
        return TeamCategory::select('*')
            ->where('parent_id', 0)
			->where('status', 1)
			->orderby('id', 'asc')
            ->get()
            ->toArray();
    }

	/**
     * 获取拼团子频道
     * @param $tc_id
     * @return mixed
     */
    public function teamCategoriesChild($tc_id = 0)
    {
        return TeamCategory::select('*')
            ->where('parent_id', $tc_id)
			->where('status', 1)
			->orderby('id', 'asc')
            ->get()
            ->toArray();
    }

	/**
     * 获取频道信息
     * @param $tc_id
     * @return mixed
     */
    public function teamCategoriesInfo($tc_id = 0)
    {
        return TeamCategory::select('*')
            ->where('id', $tc_id)
			->where('status', 1)
            ->first()
            ->toArray();
    }


	/**
     * 查询拼团商品列表
     * @param string $type
     * @param integer $size
     * @return mixed
     */
    public function teamGoodsList($page = 1, $size = 10 ,$type = [])
    {
        $goods = TeamGoods::from('team_goods as tg')
            ->select('g.goods_id', 'g.goods_name', 'g.shop_price','g.goods_number', 'g.sales_volume','g.goods_thumb', 'tg.id', 'tg.team_price', 'tg.team_num','tg.limit_num')
            ->leftjoin('goods as g', 'g.goods_id', '=', 'tg.goods_id');
        if (!empty($type)) {
            $goods->wherein('tg.tc_id', $type);
        }
        $begin = ($page - 1) * $size;
        $list = $goods->where('tg.is_team', 1)
            ->where('tg.is_audit', 2)
            ->where('g.is_on_sale', 1)
            ->where('g.is_alone_sale', 1)
			->where('g.is_delete', 0)
            ->where('g.review_status','>' ,2)
            ->offset($begin)
            ->orderby('tg.id', 'desc')
            ->limit($size)
            ->get()
            ->toArray();
        if ($list === null) {
            return [];
        }

        return $list;
    }

	/**
     * 获取随机用户
     * @param $user_id
     * @return mixed
     */
    public function randUserInfo($user_id = 0)
    {
		$prefix = Config::get('database.connections.mysql.prefix');
		$sql = "SELECT user_name,user_id FROM {$prefix}users WHERE user_id >= ((SELECT MAX(user_id) FROM {$prefix}users)-(SELECT MIN(user_id) FROM {$prefix}users)) * RAND() + (SELECT MIN(user_id) FROM {$prefix}users) and nick_name !='' LIMIT 30 ";
		$list = DB::select($sql);
		if ($list == null) {
            return [];
        }
		return $list;
    }


	/**
     * 拼团子频道商品列表
	 * @param int $tc_id
     * @param int $page
     * @param int $size
     * @param string $keywords
     * @param string $sortKey  排序 goods_id last_update  sales_volume  team_price
     * @param string $sortVal  ASC DESC
     * @return mixed
     */
    public function categoryGoodsList($tc_id = 0, $page = 1, $size = 10,$keywords='',$sortKey = 0, $sortVal = '')
    {
        $goods = TeamGoods::from('team_goods as tg')
            ->select('g.goods_id', 'g.goods_name', 'g.shop_price','g.goods_number','g.sales_volume','g.goods_thumb', 'tg.id', 'tg.team_price', 'tg.team_num','tg.limit_num')
            ->leftjoin('goods as g', 'g.goods_id', '=', 'tg.goods_id');

	    // 关键词
        if (!empty($keywords)) {
            $goods->where('goods_name', 'like', "%{$keywords}%");
        }
		// 排序
        $sort = ['ASC', 'DESC'];

		switch ($sortKey) {
			// 默认
			case '0':
				$goods->orderby('g.goods_id', 'ASC');
				break;
			// 新品
			case '1':
				$goods->orderby('g.last_update', in_array($sortVal, $sort) ? $sortVal : 'ASC');
				break;
			// 销量
			case '2':
				$goods->orderby('g.sales_volume', in_array($sortVal, $sort) ? $sortVal : 'ASC');
				break;
			// 价格
			case '3':
				$goods->orderby('tg.team_price', in_array($sortVal, $sort) ? $sortVal : 'ASC');
				break;
		}


        $begin = ($page - 1) * $size;
        $list = $goods->where('tg.tc_id', $tc_id)
			->where('tg.is_team', 1)
            ->where('tg.is_audit', 2)
            ->where('g.is_on_sale', 1)
            ->where('g.is_alone_sale', 1)
			->where('g.is_delete', 0)
            ->where('g.review_status', '>', 2)
            ->offset($begin)
            ->limit($size)
            ->get()
            ->toArray();

        if ($list === null) {
            return [];
        }

        return $list;
    }

	/**
     * 拼团排行商品列表
     * @param string $type
     * @param integer $size
     * @return mixed
     */
    public function teamRankingList($page = 1, $size = 10, $type = 0)
    {
        $goods = TeamGoods::from('team_goods as tg')
            ->select('g.goods_id', 'g.goods_name', 'g.shop_price','g.goods_number', 'g.sales_volume','g.goods_thumb', 'tg.id', 'tg.team_price', 'tg.team_num','tg.limit_num')
            ->leftjoin('goods as g', 'g.goods_id', '=', 'tg.goods_id');

		switch ($type) {
			// 热门
			case '0':
				$goods->orderby('tg.limit_num', 'DESC');
				break;
			// 新品
			case '1':
				$goods->orderby('g.add_time', 'DESC');
				break;
			// 优选
			case '2':
				$goods->where('g.is_hot', 1);
				break;
			case '3':
				$goods->where('g.is_best', 1);
				break;
		}

        $begin = ($page - 1) * $size;
        $list = $goods->where('tg.is_team', 1)
            ->where('tg.is_audit', 2)
            ->where('g.is_on_sale', 1)
            ->where('g.is_alone_sale', 1)
			->where('g.is_delete', 0)
            ->where('g.review_status', '>', 2)
            ->offset($begin)
            ->limit($size)
            ->get()
            ->toArray();

        if ($list === null) {
            return [];
        }

        return $list;
    }


	/**
     * 商品详情
     * @param $bargain_id
     * @return mixed
     */
    public function goodsInfo($goods_id =0)
    {
        $res = TeamGoods::from('team_goods as tg')
            ->select('tg.id','tg.goods_id','tg.team_price','tg.team_num','tg.limit_num','tg.astrict_num','tg.is_audit','tg.is_team','tg.team_desc','g.user_id','g.goods_sn', 'g.goods_name','g.is_real','g.is_shipping','g.is_on_sale', 'g.shop_price', 'g.market_price','g.goods_thumb', 'g.goods_img','g.goods_number','g.sales_volume', 'g.goods_desc', 'g.desc_mobile','g.goods_type','g.goods_brief','g.model_attr','g.review_status')
            ->leftjoin('goods as g', 'g.goods_id', '=', 'tg.goods_id')
            ->where('tg.goods_id', $goods_id)
			->where('tg.is_team', 1)
            ->first();
        if ($res === null) {
            return [];
        }
        return $res->toArray();
    }

	/**
     * 获取拼团新品
     * @param string $type
     * @param integer $size
     * @return mixed
     */
    public function teamNewGoods($type= 'is_new', $user_id = 0,$size = 10)
    {
        $goods = TeamGoods::from('team_goods as tg')
            ->select('g.goods_id', 'g.goods_name', 'g.shop_price','g.goods_number', 'g.sales_volume','g.goods_thumb', 'tg.id', 'tg.team_price', 'tg.team_num','tg.limit_num')
            ->leftjoin('goods as g', 'g.goods_id', '=', 'tg.goods_id');

        if ($type =='is_new') {
            $goods->where('g.is_new', 1);
        }
        $list = $goods->where('tg.is_team', 1)
            ->where('tg.is_audit', 2)
            ->where('g.is_on_sale', 1)
            ->where('g.is_alone_sale', 1)
			->where('g.is_delete', 0)
            ->where('g.review_status', '>', 2)
			->where('g.user_id', $user_id)
            ->orderby('tg.id', 'desc')
            ->limit($size)
            ->get()
            ->toArray();

        if ($list === null) {
            return [];
        }

        return $list;
    }

	/**
     * 验证参团活动信息
     * @param string $type
     * @param integer $size
     * @return mixed
     */
    public function teamIsFailure($team_id = 0)
    {
        $list = TeamLog::from('team_log as tl')
            ->select('tl.team_id','tl.goods_id','tl.start_time','tl.status','tg.id','tg.validity_time' ,'tg.team_price','tg.team_num','tg.limit_num','tg.astrict_num','tg.is_team','g.goods_name')
			->leftjoin('team_goods as tg', 'tl.t_id', '=', 'tg.id')
			->leftjoin('goods as g', 'tg.goods_id', '=', 'g.goods_id')
			->where('tl.team_id', $team_id)
            ->first();

        if ($list === null) {
            return [];
        }

        return $list->toArray();
    }

    /**
     * 获取参团有效订单信息
     * @param string $type
     * @param integer $size
     * @return mixed
     */
    public function teamOrderInfo($team_id = 0)
    {
        $list = OrderInfo::select('order_sn','user_id')
            ->where('team_id', $team_id)
            ->where('extension_code', 'team_buy')
            ->where('pay_status', 2)
            ->get()
            ->toArray();
        if ($list === null) {
            return [];
        }

        return $list;
    }

	/**
     * 获取该商品已成功开团信息
     * @param string $type
     * @param integer $size
     * @return mixed
     */
    public function teamGoodsLog($goods_id = 0, $size = 6)
    {
        $list = TeamLog::from('team_log as tl')
            ->select('tl.team_id','tl.goods_id','tl.start_time','o.team_parent_id','tg.validity_time' ,'tg.team_num')
            ->leftjoin('order_info as o', 'tl.team_id', '=', 'o.team_id')
			->leftjoin('team_goods as tg', 'tl.t_id', '=', 'tg.id')
			->where('tl.goods_id', $goods_id)
            ->where('tl.status', '<', 1)
            ->where('tl.is_show', 1)
			->where('o.extension_code', 'team_buy')
			->where('o.team_parent_id', '>', 0)
			->where('o.pay_status', 2)
			->where('tg.is_team', 1)
            ->orderby('o.add_time', 'desc')
            ->limit($size)
            ->get()
            ->toArray();

        if ($list === null) {
            return [];
        }

        return $list;
    }

	/**
     * 统计该拼团已参与人数
     * @param $bargain_id
     * @return mixed
     */
    public function surplusNum($team_id = 0)
    {
        return OrderInfo::select('*')
            ->where([['team_id', '=', $team_id],['extension_code', '=', 'team_buy']])
            ->orWhere([['pay_status', '=', 2],['order_status', '=', 4]])
            ->count();
    }

	/**
     * 取得商品最终使用价格
     *
     * @param   string  $goods_id      商品编号
     * @param   string  $goods_num     购买数量
     * @param   boolean $is_spec_price 是否加入规格价格
     * @param   mix     $property          规格ID的数组或者逗号分隔的字符串
     *
     * @return  商品最终购买价格
     */
    public function getFinalPrice($goods_id, $goods_num = '1', $is_spec_price = false, $property = [], $warehouse_id = 0, $area_id = 0)
    {
        $final_price   = 0; //商品最终购买价格
        $spec_price    = 0;

        //如果需要加入规格价格
        if ($is_spec_price) {
			if(!empty($property)){
				$spec_price = $this->goodsRepository->goodsPropertyPrice($goods_id, $property, $warehouse_id, $area_id);
			}
        }
		//商品信息
        $goods = $this -> goodsInfo($goods_id);

        //如果需要加入规格价格
        if ($is_spec_price) {
            if ($this->shopConfigRepository->getShopConfigByCode('add_shop_price') == 1) {
                $final_price = $goods['team_price'];
                $final_price+= $spec_price;
            }
        }

        if ($this->shopConfigRepository->getShopConfigByCode('add_shop_price') == 0) {
            //返回商品属性价
            $final_price = $goods['team_price'];
        }

        //返回商品最终购买价格
        return $final_price;

    }

	 /**
     * 获取拼团信息
     * @param string $type
     * @param integer $size
     * @return mixed
     */
    public function teamInfo($team_id = 0)
    {

		$info = TeamLog::from('team_log as tl')
            ->select('tl.team_id', 'tl.start_time','tl.status','o.user_id','o.team_parent_id','g.goods_id','g.goods_thumb','g.goods_img','g.goods_name','g.goods_brief','tg.validity_time' ,'tg.team_num' ,'tg.team_price','tg.is_team')
            ->leftjoin('order_info as o', 'tl.team_id', '=', 'o.team_id')
			->leftjoin('goods as g', 'tl.goods_id', '=', 'g.goods_id')
			->leftjoin('team_goods as tg', 'tl.t_id', '=', 'tg.id')
			->where('tl.team_id', $team_id)
            ->where('o.extension_code', 'team_buy')
			->where('o.team_parent_id', '>', 0)
            ->first()
            ->toArray();

        return $info;
    }

	 /**
     * 拼团订单状态
     * @param $team_id
     * @return mixed
     */
    public function orderInfo($team_id = 0, $user_id = 0)
    {
        return OrderInfo::select('order_id','order_status', 'pay_status')
            ->where('team_id', $team_id)
			->where('user_id', $user_id)
            ->first()
            ->toArray();
    }

	 /**
     * 获取拼团团员信息
     * @param string $type
     * @param integer $size
     * @return mixed
     */
    public function teamUserList($team_id = 0, $page = 1, $size = 5)
    {
		$begin = ($page - 1) * $size;

		$list = OrderInfo::from('order_info as o')
            ->select('o.add_time','o.team_id', 'o.user_id','o.team_parent_id','o.team_user_id')
            ->leftjoin('users as u', 'o.user_id', '=', 'u.user_id')
            ->where([['o.team_id', '=', $team_id],['o.extension_code', '=', 'team_buy']])
			->orWhere([['o.pay_status', '=', 2],['o.order_status', '=', 4]])
			->orderby('o.add_time', 'asc')
			->offset($begin)
            ->limit($size)
            ->get()
            ->toArray();

        return $list;
    }

	/**
     * 验证是否已经参团
     * @param $bargain_id
     * @return mixed
     */
    public function teamJoin($user_id, $team_id = 0)
    {
        return OrderInfo::select('*')
            ->where('team_id', $team_id)
			->where('user_id', $user_id)
            ->where('extension_code', 'team_buy')
            ->count();
    }



	/**
     * 我的拼团
     * @param string $type
     * @param integer $size
     * @return mixed
     */
	public function teamUserOrder($user_id, $type = 0, $page = 1, $size = 10)
    {

		$start = ($page - 1) * $size;
		switch ($type) {
			case '0':
				$where = " and t.status < 1 and '" . gmtime() . "'< (t.start_time+(tg.validity_time*3600)) and o.order_status != 2 and tg.is_team = 1 ";//拼团中
				break;
			case '1':
				$where = " and t.status = 1 ";//成功团
				break;
			case '2':
				$where = " and t.status < 1 and ('" . gmtime() . "' > (t.start_time+(tg.validity_time*3600)) || tg.is_team != 1)";//失败团
				break;
		}
		$prefix = Config::get('database.connections.mysql.prefix');

		$sql = "select o.order_id,o.user_id,o.order_status,o.pay_status,t.goods_id,t.team_id,t.start_time,t.status,g.goods_name,g.goods_thumb,g.shop_price,tg.validity_time,tg.id,tg.team_num,tg.team_price,tg.limit_num from " . $prefix . "order_info as o left join " . $prefix . "team_log as t on o.team_id = t.team_id left join " . $prefix . "team_goods as tg on t.t_id = tg.id left join " . $prefix . "goods as g on g.goods_id = tg.goods_id" . " where o.user_id = $user_id and o.extension_code ='team_buy'  and t.is_show = 1 $where  ORDER BY o.add_time DESC limit $start,$size";
		$list = DB::select($sql);

		return $list;
	}

    /**
     * 获取过期未退款的订单
     * @param string $type
     * @param integer $size
     * @return mixed
     */
    public function teamUserOrderRefund()
    {  
        $where = " and t.status < 1 and ('" . gmtime() . "' > (t.start_time+(tg.validity_time*3600)) || tg.is_team != 1)";//失败团
         
        $prefix = Config::get('database.connections.mysql.prefix');

        $sql = "select o.order_id,o.user_id,o.order_status,o.pay_status,o.goods_amount,o.shipping_fee,t.goods_id,t.team_id,t.start_time,t.status,g.goods_name,g.goods_thumb,g.shop_price,tg.validity_time,tg.id,tg.team_num,tg.team_price,tg.limit_num from " . $prefix . "order_info as o left join " . $prefix . "team_log as t on o.team_id = t.team_id left join " . $prefix . "team_goods as tg on t.t_id = tg.id left join " . $prefix . "goods as g on g.goods_id = tg.goods_id" . " where o.extension_code ='team_buy' and o.pay_status = 2  and t.is_show = 1 $where  ORDER BY o.add_time DESC";
        $list = DB::select($sql);

        return $list;
    }

	/**
     * 插入开团活动信息
     * @param $params
     * @return bool
     */
    public function addTeamLog($params)
    {
        $add = TeamLog::insertGetId(
            $params
        );
        if ($add) {
            return $add;
        }
    }

	/**
     * 更新活动参与人数
     * @param $params
     * @return bool
     */
    public function updateTeamLogStatua($team_id)
    {
        TeamLog::where('team_id', $team_id)
            ->update(['status' => 1]);

    }


	/**
     * 更改拼团参团数量
     * @param $params
     * @return bool
     */
    public function updateTeamLimitNum($id = 0,$goods_id = 0,$limit_num = 0)
    {
        TeamGoods::where('id', $id)
			->where('goods_id', $goods_id)
            ->update(['limit_num' => $limit_num]);

    }



}
