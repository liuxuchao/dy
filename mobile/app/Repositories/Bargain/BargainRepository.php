<?php

namespace App\Repositories\Bargain;

use App\Models\Goods;
use App\Models\Comment;
use App\Models\Products;
use App\Models\BargainGoods;
use App\Models\Attribute;
use App\Models\ActivityGoodsAttr;
use App\Models\BargainStatistics;
use App\Models\BargainStatisticsLog;
use App\Models\GoodsAttr;
use App\Models\GoodsTransport;
use App\Models\ProductsArea;
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
class BargainRepository
{
    protected $goods;
    private $field;
    private $goodsAttrRepository;
    private $shopConfigRepository;
    private $goodsRepository;
    private $userRepository;

    public function __construct(
        GoodsAttrRepository $goodsAttrRepository,
        ShopConfigRepository $shopConfigRepository,
        GoodsRepository $goodsRepository,
        UserRepository $userRepository

    )
    {
        $this->setField();
        $this->goodsAttrRepository = $goodsAttrRepository;
        $this->shopConfigRepository = $shopConfigRepository;
        $this->goodsRepository = $goodsRepository;
        $this->userRepository = $userRepository;
    }
    /**
     * 新增单个商品
     * @param array $data
     * @return mixed
     */
    public function create(array $data)
    {
    }

    /**
     * 获取单个商品信息
     * @param $id
     * @return mixed
     */
    public function get($id)
    {
    }

    /**
     * 更新商品信息
     * @param array $data
     * @return mixed
     */
    public function update(array $data)
    {
    }

    /**
     * 根据商品Id删除商品
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
    }

    /**
     * 商品搜索
     * @param array $data
     * @return mixed
     */
    public function search(array $data)
    {
    }

    /**
     * 获取商品SKU列表
     * @param $id
     * @return mixed
     */
    public function sku($id)
    {
    }

    /**
     * @return mixed
     */
    public function skuAdd()
    {
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
     * 获取单个商砍价信息
     * @param $bargain_id
     * @return mixed
     */
    public function find($bargain_id)
    {
        return BargainGoods::select('*')
            ->where('bargain_id', $bargain_id)
            ->first()
            ->toArray();
    }

    /**
     * 参与活动记录
     * @param $bargain_id
     * @return mixed
     */
    public function bargainLog($bs_id = 0)
    {
        return BargainStatisticsLog::select('*')
            ->where('id', $bs_id)
            ->first()
            ->toArray();
    }

    /**
     * 验证是否参与当前活动
     * @param $bargain_id
     * @param $user_id
     * @return mixed
     */
    public function isAddBargain($bargain_id = 0,$user_id, $bs_id = 0)
    {
        $info = BargainStatisticsLog::select('*');
        if($bs_id > 0){
            $info->where('id', $bs_id);
        }
        $tatistics_og = $info->where('bargain_id', $bargain_id)
            ->where('user_id', $user_id)
            ->where('status', 0)
            ->first();

        if ($tatistics_og === null) {
            return [];
        }

        return $tatistics_og->toArray();
    }

    /**
     * 验证已砍价信息
     * @param $bargain_id
     * @param $user_id
     * @return mixed
     */
    public function isBargainJoin($bs_id = 0,$user_id)
    {
        $bargain_info = BargainStatistics::select('*')
            ->where('bs_id', $bs_id)
            ->where('user_id', $user_id)
            ->first();
        if ($bargain_info === null) {
            return [];
        }

        return $bargain_info->toArray();;

    }


    /**
     * 验证参与砍价次数
     * @param $bargain_id
     * @return mixed
     */
    public function bargainLogNumber($bs_id = 0, $user_id = 0)
    {
        return BargainStatistics::select('*')
            ->where('bs_id', $bs_id)
            ->where('user_id', $user_id)
            ->count();
    }


     /**
     * 获取轮播图
     * @param $tc_type
     * @param $num
     * @return array
     */
    public function bargainPositions($tc_type = 'weapp', $num = 3)
    {
        $time = gmtime();
        $res = TouchAd::select('ad_id', 'touch_ad.position_id', 'media_type', 'ad_link', 'ad_code', 'ad_name')
            ->with(['position'])
            ->join('touch_ad_position', 'touch_ad_position.position_id', '=', 'touch_ad.position_id')
            ->where("start_time", '<=', $time)
            ->where("end_time", '>=', $time)
            ->where("touch_ad_position.position_id", $tc_type)
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
     * 查询砍价商品列表
     * @param string $type
     * @param integer $size
     * @return mixed
     */
    public function findByType($user_id = 0,$type = '',$page = 1, $size = 10 )
    {
        $time = gmtime();

        $goods = BargainGoods::from('bargain_goods as bg')
            ->select('bg.id','bg.start_time','bg.goods_price','bg.end_time','bg.target_price','bg.total_num','g.goods_id', 'g.goods_name', 'g.shop_price', 'g.market_price', 'g.goods_thumb' , 'g.goods_img')
            ->leftjoin('goods as g', 'g.goods_id', '=', 'bg.goods_id');

        // 类型
        if ($type == 'is_hot') {
            $goods->where('bg.is_hot', 1);
        }
        $begin = ($page - 1) * $size;
        $list = $goods->where('bg.status', 0)
            ->where('bg.is_audit', 2)
            ->where('bg.is_delete', 0)
            ->where("bg.start_time", '<=', $time)
            ->where("bg.end_time", '>=', $time)
            ->where('g.is_on_sale', 1)
            ->where('g.is_alone_sale', 1)
            ->where('g.is_delete', 0)
            ->where('g.review_status','>' ,2)
            ->offset($begin)
            ->orderby('bg.id', 'desc')
            ->limit($size)
            ->get()
            ->toArray();

        if ($list === null) {
            return [];
        }

        foreach ($list as $key => $val) {
            $list[$key]['goods_thumb']  = get_image_path($val['goods_thumb']);
            $list[$key]['shop_price']   = price_format($val['shop_price'], false);
            $list[$key]['target_price'] = price_format($val['target_price'], false);
            $target_price = $this->getBargainTargetPrice($val['id']);//获取砍价商品属性最低价格
            if($target_price){
                $list[$key]['target_price'] = price_format($target_price, false);
            }
            $add_bargain = $this->isAddBargain($val['id'], $user_id);  //参与活动id
            $list[$key]['bs_id'] = empty($add_bargain['id'])? 0 : $add_bargain['id'];
        }

        return $list;
    }

    /**
     * 商品详情
     * @param $bargain_id
     * @return mixed
     */
    public function goodsInfo($bargain_id =0)
    {
        $res = BargainGoods::from('bargain_goods as bg')
            ->select('bg.id','bg.goods_id','bg.goods_price','bg.start_time','bg.end_time','bg.target_price','bg.min_price','bg.max_price','bg.total_num','bg.status','bg.bargain_desc','g.user_id','g.goods_sn', 'g.goods_name','g.is_real','g.is_shipping','g.is_on_sale', 'g.shop_price', 'g.market_price','g.goods_thumb', 'g.goods_img','g.goods_number', 'g.goods_desc', 'g.desc_mobile','g.goods_type','g.goods_brief','g.model_attr','g.review_status')
            ->leftjoin('goods as g', 'g.goods_id', '=', 'bg.goods_id')
            ->where('bg.id', $bargain_id)
            ->first();
        if ($res === null) {
            return [];
        }
        return $res->toArray();
    }

    /**
     * 亲友帮
     * @param string $type
     * @param integer $size
     * @return mixed
     */
    public function getBargainStatistics($bs_id = 0)
    {

        $list = BargainStatistics::select('user_id' ,'add_time','subtract_price')
            ->where('bs_id', $bs_id)
            ->orderby('add_time', 'desc')
            ->get();

        if ($list === null) {
            return [];
        }
        $list = $list->toArray();

        $timeFormat = $this->shopConfigRepository->getShopConfigByCode('time_format');
        foreach ($list as $key => $val) {
            $list[$key]['subtract_price']   = price_format($val['subtract_price'], false);
            $list[$key]['add_time'] = local_date($timeFormat, $val['add_time']);
            //用户名、头像
            $user_info = $this->userRepository->userInfo($val['user_id']);
            $list[$key]['user_name'] = !empty($user_info['nick_name']) ? $user_info['nick_name'] : $user_info['user_name'];
            $list[$key]['user_picture'] = get_image_path($user_info['user_picture']);

        }

        return $list;
    }

    /**
     * 排行榜
     * @param string $type
     * @param integer $size
     * @return mixed
     */
    public function getBargainRanking($bargain_id = 0)
    {
        $prefix = Config::get('database.connections.mysql.prefix');
        $sql ="SELECT bsl.user_id , IFNULL((select sum(subtract_price) from {$prefix}bargain_statistics where bs_id = bsl.id),0) as money from {$prefix}bargain_statistics_log bsl ";
        $sql .= " left join {$prefix}bargain_statistics bs on bsl.id = bs.bs_id ";
        $sql .= " where bsl.bargain_id={$bargain_id} GROUP BY bsl.id order by money desc ";
        $list = DB::select($sql);
        if(empty($list)){
            return [];
        }
        foreach ($list as $key => $val) {
            $total[$key] = get_object_vars($val);
            $total[$key]['rank'] = $key+1;
            $total[$key]['money'] = price_format($total[$key]['money'], false);
            //用户名、头像
            $user_info = $this->userRepository->userInfo($total[$key]['user_id']);
            $total[$key]['user_name'] = !empty($user_info['nick_name']) ? $user_info['nick_name'] : $user_info['user_name'];
            $total[$key]['user_picture'] = get_image_path($user_info['user_picture']);
        }
        return $total;
    }


    /**
     * 获取砍价商品属性最低价格
     * @param $bargain_id
     */
    public function getBargainTargetPrice($bargain_id = 0)
    {
        $target_price = ActivityGoodsAttr::where('bargain_id', $bargain_id)
        ->min('target_price');
        if ($target_price === null) {
            return [];
        }
        return $target_price;
    }

    /**
     * 已砍价总额
     * @param $bs_id
     */
    public function subtractPriceSum($bs_id = 0)
    {
        $subtract_price = BargainStatistics::where('bs_id', $bs_id)
        ->sum('subtract_price');
        if ($subtract_price === null) {
            return 0;
        }

        return $subtract_price;
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

        $goods = Goods::from('goods as g')
            ->select('g.shop_price','g.promote_price', 'g.promote_start_date', 'g.promote_end_date', 'mp.user_price')
            ->leftjoin('member_price as mp', 'mp.goods_id', '=', 'g.goods_id')
            ->where('g.goods_id', $goods_id)
            ->where('g.is_delete', 0)
            ->first()
            ->toArray();


        //如果需要加入规格价格
        if ($is_spec_price) {
            if ($this->shopConfigRepository->getShopConfigByCode('add_shop_price') == 1) {
                $final_price = $goods['shop_price'];
                $final_price+= $spec_price;
            }
        }

        if ($this->shopConfigRepository->getShopConfigByCode('add_shop_price') == 0) {
            //返回商品属性价
            $final_price = $spec_price;
        }

        //返回商品最终购买价格
        return $final_price;

    }


    /**
     * 获得指定商品属性活动最低价
     *
     * @param   string  $bargain_id   砍价活动id
     * @param   string  $goods_id     商品编号
     * @param   boolean spec          规格ID的数组或者逗号分隔的字符串
     */
    function bargainTargetPrice($bargain_id = 0,$goods_id = 0,$attr_id = [], $warehouse_id = 0, $area_id = 0,$model_attr = 0)
    {
        if (empty($attr_id)) {
            $attr_id = 0;
        } else {
            //去掉复选属性start
            if (is_string($attr_id)) {
                $attr_arr = explode(',', $attr_id);
            } else {
                $attr_arr = $attr_id;
            }
            foreach ($attr_arr as $key => $val) {
                //$attr_type = $this->getGoodsAttrId($val);
                $attr_type = $this->goodsRepository->getGoodsAttrId($val);
                if (($attr_type == 0 || $attr_type == 2) && $attr_arr[$key]) {
                    unset($attr_arr[$key]);
                }
            }
            $attr_id = implode('|', $attr_arr);
            //去掉复选属性 end
        }
        //商品属性价格模式,货品模式
        if ($this->shopConfigRepository->getShopConfigByCode('goods_attr_price') == 1) {
            if ($model_attr == 1) {
                $product_price = ProductsWarehouse::select('product_id','product_price', 'product_promote_price', 'product_market_price')
                ->where('goods_id', $goods_id)
                ->where('goods_attr', $attr_id)
                ->where('warehouse_id', $warehouse_id)
                ->first()
                ->toArray();
            } elseif ($model_attr == 2) {
                $product_price = ProductsArea::select('product_id','product_price', 'product_promote_price', 'product_market_price')
                ->where('goods_id', $goods_id)
                ->where('goods_attr', $attr_id)
                ->where('area_id', $area_id)
                ->first()
                ->toArray();
            } else {
                $product_price = Products::select('product_id','product_price', 'product_promote_price', 'product_market_price')
                ->where('goods_id', $goods_id)
                ->where('goods_attr', $attr_id)
                ->first()
                ->toArray();
            }
            if($product_price['product_id']){
                $res = ActivityGoodsAttr::select('target_price')
                ->where('bargain_id', $bargain_id)
                ->where('goods_id', $goods_id)
                ->where('product_id', $product_price['product_id'])
                ->first()
                ->toArray();
            }

            if ($res === null) {
                return [];
            }

            return $res['target_price'];

        }
    }

    /**
     * 我要参与
     * @param $params
     * @return bool
     */
    public function addBargain($params)
    {
        $add = BargainStatisticsLog::insertGetId(
            $params
        );

        if ($add) {
            $result['msg'] = '参与成功！感谢您的参与，祝您购物愉快';
            $result['bs_id'] = $add;
            $result['error'] = 2;
            return $result;
        }

    }

    /**
     * 更新活动参与人数
     * @param $params
     * @return bool
     */
    public function updateBargain($bargain_id = 0,$total_num = 0)
    {
        $total_num = $total_num +1;
        BargainGoods::where('id', $bargain_id)
            ->update(['total_num' => $total_num]);

    }

    /**
     * 去砍价
     * @param $params
     * @return bool
     */
    public function addBargainStatistics($params)
    {
        return $add = BargainStatistics::insertGetId(
        $params
        );
    }

     /**
     * 更新参与砍价人数 和砍后最终购买价
     * @param $params
     * @return bool
     */
    public function updateBargainStatistics($bs_id = 0,$count_num = 0, $final_price = 0)
    {
        BargainStatisticsLog::where('id', $bs_id)
            ->update(['count_num' => $count_num, 'final_price' => $final_price]);

    }

    /**
     * 修改砍价活动状态
     * @param $params
     * @return bool
     */
    public function updateStatus($bs_id = 0)
    {
        BargainStatisticsLog::where('id', $bs_id)
            ->update(['status' => 1]);

    }

    /**
     * 我de砍价列表
     * @param $user_id
     * @param int $page
     * @param int $size
     * @return mixed
     */
    public function myBargain($user_id = 0, $page = 1, $size = 10)
    {
        $begin = ($page - 1) * $size;

        $goods = BargainStatisticsLog::from('bargain_statistics_log as bsl')
            ->select('bg.id','bg.target_price','bg.total_num','g.goods_id','g.goods_name','g.shop_price','g.goods_thumb','g.goods_img')
            ->leftjoin('bargain_goods as bg', 'bsl.bargain_id', '=', 'bg.id')
            ->leftjoin('goods as g', 'bg.goods_id', '=', 'g.goods_id')
            ->offset($begin)
            ->where('bsl.user_id', $user_id)
            ->orderby('bsl.add_time', 'desc')
            ->limit($size)
            ->get()
            ->toArray();
        return  $goods;
    }


    /*二维数组转一维数组*/
    function copyArrayColumn($input, $columnKey, $indexKey = null)
    {
        $columnKeyIsNumber = (is_numeric($columnKey)) ? true : false;
        $indexKeyIsNull = (is_null($indexKey)) ? true : false;
        $indexKeyIsNumber = (is_numeric($indexKey)) ? true : false;
        $result = [];

        foreach ((array)$input as $key => $row) {
            if ($columnKeyIsNumber) {
                $tmp = array_slice($row, $columnKey, 1);
                $tmp = (is_array($tmp) && !empty($tmp)) ? current($tmp) : null;
            } else {
                $tmp = isset($row[$columnKey]) ? $row[$columnKey] : null;
            }
            if (! $indexKeyIsNull) {
                if ($indexKeyIsNumber) {
                    $key = array_slice($row, $indexKey, 1);
                    $key = (is_array($key) && ! empty($key)) ? current($key) : null;
                    $key = is_null($key) ? 0 : $key;
                } else {
                    $key = isset($row[$indexKey]) ? $row[$indexKey] : 0;
                }
            }

            $result[$key] = $tmp;
        }

        return $result;
    }


}
