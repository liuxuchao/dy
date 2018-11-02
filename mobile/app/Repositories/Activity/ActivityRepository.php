<?php

namespace App\Repositories\Activity;

use App\Models\FavourableActivity;
use App\Models\Goods;
use App\Models\OrderInfo;
use App\Models\Users;
use App\Models\Comment;
use App\Models\Products;
use App\Models\Attribute;
use App\Models\ActivityGoodsAttr;
use App\Models\GoodsAttr;
use App\Models\GoodsTransport;
use App\Models\ProductsArea;
use App\Services\AuthService;
use App\Models\ProductsWarehouse;
use App\Models\WarehouseAreaAttr;
use App\Models\WarehouseAreaGoods;
use App\Repositories\ShopConfig\ShopConfigRepository;
use App\Repositories\User\UserRankRepository;
use App\Repositories\Goods\GoodsRepository;
use App\Repositories\Goods\GoodsAttrRepository;
use App\Repositories\User\UserRepository;
use App\Repositories\Category\CategoryRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * Class BargainRepository
 * @package App\Repositories\bargain
 */
class ActivityRepository
{
    protected $goods;
    private $field;
    private $authService;
    private $goodsAttrRepository;
    private $shopConfigRepository;
    private $userRankRepository;
    private $goodsRepository;
    private $userRepository;
    private $categoryRepository;

    public function __construct(
        AuthService $authService,
        GoodsAttrRepository $goodsAttrRepository,
        ShopConfigRepository $shopConfigRepository,
        UserRankRepository $userRankRepository,
        GoodsRepository $goodsRepository,
        UserRepository $userRepository,
        CategoryRepository $categoryRepository

    )
    {        
        $this->authService = $authService;
        $this->goodsAttrRepository = $goodsAttrRepository;
        $this->shopConfigRepository = $shopConfigRepository;
        $this->userRankRepository = $userRankRepository;
        $this->goodsRepository = $goodsRepository;
        $this->userRepository = $userRepository;
        $this->categoryRepository = $categoryRepository;
    }

   
    

	 /**
     * 优惠活动 - 活动首页
     * @param $position_id
     * @param $num
     * @return array
     */
    public function activityList()
    {   
        $list = FavourableActivity::select('*')
            ->where("review_status", 3)
            ->orderby('sort_order', 'ASC')
            ->orderby('end_time', 'DESC')
            ->get()
            ->toArray();
        if ($list === null) {
            return [];
        }
       

        return $list;
    }


    /**
     * //查询符合条件的优惠活动
     * @param $position_id
     * @param $num
     * @return array
     */
    public function activityListAll($ru_id = 0)
    {   
        $gmtime = gmtime();
        $favourable_list = [];
        $shopconfig = app('App\Repositories\ShopConfig\ShopConfigRepository');
        $timeFormat = $shopconfig->getShopConfigByCode('time_format');

        $user_rank = $this->userRankRepository->getUserRankByUid();
        $user_rank = ',' . $user_rank['rank_id'] . ',';

        $activity = FavourableActivity::select('*')
            ->where("start_time", '<=', $gmtime)
            ->where("end_time", '>=', $gmtime);
        if($ru_id > 0){            
            $activity->Where('user_id', '=', $ru_id);
            $activity->orWhere('userFav_type', '=', 1);
        }else{
            $activity->where("user_id", $ru_id);
        }
        $list = $activity->where("review_status", 3)        
        ->whereraw("CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'")
        ->get()
        ->toArray();

        if ($list === null) {
            return [];
        } 

        return $list;
    }

    /**
     * //同一商家所有优惠活动包含的所有优惠范围
     * @param $position_id
     * @param $num
     * @return array
     */
    public function activityRangeExt($ru_id = 0,$act_range)
    {   
        $gmtime = gmtime();
        
        $user_rank = $this->userRankRepository->getUserRankByUid();
        $user_rank = ',' . $user_rank['rank_id'] . ',';
        $activity = FavourableActivity::select('*');
        if($ru_id > 0){            
            $activity->Where('user_id', '=', $ru_id);
            $activity->orWhere('userFav_type', '=', 1);
        }else{
            $activity->where("user_id", $ru_id);
        }
        $res = $activity->where("review_status", 3)
        ->where("start_time", '<=', $gmtime)
        ->where("end_time", '>=', $gmtime)
        ->where("act_range",  $act_range)
        ->whereraw("CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'")
        ->get()
        ->toArray();

        if ($res === null) {
            return [];
        }        
        $arr = array();
        foreach ($res as $key => $row)
        {  
            $id_list = explode(',', $row['act_range_ext']);
            $arr = array_merge($arr, $id_list);
        }

        return array_unique($arr);
       
    }

    /**
     * 优惠活动
     * 查询全场通用优惠活动品牌值列表
     * --------暂时无用--------
     */
    public function returnActRangeExt($act_range_ext, $userFav_type, $act_range){

        if ($act_range_ext) {
            if ($userFav_type == 1 && $act_range == FAR_BRAND) {
                $id_list = explode(',', $act_range_ext);
                $brand_sql = "SELECT brand_id FROM " . $GLOBALS['ecs']->table('brand') . " WHERE brand_id " . db_create_in($id_list);
                $brand = $GLOBALS['db']->getCol($brand_sql);
                $id_list = !empty($brand) ? array_merge($id_list, $brand) : '';
                $id_list = array_unique($id_list);
                $act_range_ext = implode(",", $id_list);
            }
        }

        return $act_range_ext;
    }

     /**
     * 优惠活动 - 活动详情
     * @param $position_id
     * @param $num
     * @return array
     */
    public function detail($id = 0)
    {   
        $list = FavourableActivity::select('*')
            ->where("review_status", 3)
            ->where("act_id", $id)
            ->first()
            ->toArray();
        if ($list === null) {
            return [];
        }      

        return $list;
    }


     /**
     * 优惠活动 - 活动商品
     * @param $position_id
     * @param $num
     * @return array
     */
    public function activityGoods($filter = ['goods_ids' => '', 'cat_ids' => '', 'brand_ids' => '', 'user_id' => 0], $page, $size)
    {   
        $begin = ($page - 1) * $size;

        $goods = Goods::from('goods as g')
            ->select('*');
        // 分类
        if (!empty($filter['cat_ids'])) {            
            $cat_id = explode(',',$filter['cat_ids']);
            $goods->wherein('g.cat_id', $cat_id);
        }
        //品牌
        if (isset($filter['brand_ids']) && !empty($filter['brand_ids'])) {            
            $goods->leftjoin('brand as b', 'b.brand_id', '=', 'g.brand_id');
            $brand_id = explode(',',$filter['brand_ids']);
            $goods->wherein('g.brand_id', $brand_id);
        }
        // 商品
        if (isset($filter['goods_ids']) && !empty($filter['goods_ids'])) {
            $goods_id = explode(',',$filter['goods_ids']);
            $goods->wherein('g.goods_id', $goods_id);
        }

        //商家
        if (isset($filter['user_id'])) {
            $goods->where('g.user_id', $filter['user_id']);
        }
  
        $list = $goods->where('g.is_on_sale', 1)
            ->where('g.is_alone_sale', 1)
            ->where('g.is_delete', 0)            
            ->offset($begin)
            ->orderby('g.sort_order', 'ASC')
            ->limit($size)
            ->get()
            ->toArray();
        if ($list === null) {
            return [];
        }     
        
        return $list;
    }


    /**
     * 查询状态
     * @param  [int] $starttime 开始时间戳
     * @param  [int] $endtime   结束时间戳
     * @return [int] $result    0 未开始, 1 正在进行, 2 已结束
     */
    public function getStatus($starttime, $endtime)
    {
        $nowtime = gmtime();
        if (!empty($starttime) && !empty($endtime)){
            if ($starttime > $nowtime) {
                $result = 0; //未开始
            } elseif ($starttime < $nowtime && $endtime > $nowtime) {
                $result = 1; //进行中
            } elseif ($endtime < $nowtime) {
                $result = 2; //已结束
            }
            return $result;
        }

        return 0;
    }


	 /**
     * 同一商家所有优惠活动包含的所有优惠范围
     * @param $position_id
     * @param $num
     * @return array
     */
    public function getActivitytype($act_id = 0)
    {   

        $gmtime = gmtime();
        $user_rank = $this->userRankRepository->getUserRankByUid();
        $user_rank = ',' . $user_rank['rank_id'] . ',';
        $activity = FavourableActivity::select('*')
            ->where("review_status", 3)
            ->where("act_id", $act_id)
            ->where("start_time", '<=', $gmtime)
            ->where("end_time", '>=', $gmtime)
            ->whereraw("CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'")
            ->first()
            ->toArray();       

        if ($activity === null) {
            return [];
        }       
        $row = [];
        switch ($activity['act_type']) {
            case 0:                
                $row['act_type'] = '满赠';
                $row['act_name'] = " 满 " . $activity['min_amount'] . " 元可换购赠品";
                break;
            case 1:                
                $row['act_type'] = '满减';
                $row['act_name'] = " 满 " . $activity['min_amount'] . " 元可享受减免 ".$activity['act_type_ext'] . " 元 ";
                break;
            case 2:               
                $row['act_type'] = '折扣';
                $row['act_name'] = " 满 " . $activity['min_amount'] . " 元可享受折扣 ";
                break;
            default:
                break;
        }

        return $row;

    }   
    	


     /**
     * 查询活动中 已加入购物车的商品
     * @param $position_id
     * @param $num
     * @return array
     */
    public function cartFavourableGoods($user_id = 0, $act_id = 0)
    {   

        $gmtime = gmtime();
        $user_rank = $this->userRankRepository->getUserRankByUid();
        $user_rank = ',' . $user_rank['rank_id'] . ',';
        $favourable = FavourableActivity::select('*')
            ->where("review_status", 3)
            ->where("act_id", $act_id)
            ->where("start_time", '<=', $gmtime)
            ->where("end_time", '>=', $gmtime)
            ->whereraw("CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'")
            ->first()
            ->toArray(); 

        $prefix = Config::get('database.connections.mysql.prefix');

         /* 查询优惠范围内商品总额的sql */
        $sql = "SELECT c.rec_id, c.goods_number, g.goods_id, g.goods_thumb, g.goods_name, c.goods_price AS shop_price " .
            " FROM " . $prefix . "cart AS c, " . $prefix . "goods AS g " .
            " WHERE c.goods_id = g.goods_id " .
            " AND c.user_id = $user_id AND c.rec_type = '" . CART_GENERAL_GOODS . "' " .
            " AND c.is_gift = 0 " .
            " AND c.goods_id > 0 " ; //ecmoban模板堂 --zhuo

        $id_list = [];
        $list = [];        
        if ($favourable) {
            /* 根据优惠范围修正sql */
            if ($favourable['act_range'] == FAR_ALL) {
                // sql do not change
            } elseif ($favourable['act_range'] == FAR_CATEGORY) {
                /* 取得优惠范围分类的所有下级分类 */
                $cat_list = explode(',', $favourable['act_range_ext']);
                foreach ($cat_list as $id) {
                    $cat_list = $this->categoryRepository->arr_foreach($this->categoryRepository->catList($id));            
                    $id_list = array_merge($id_list, $cat_list);
                    array_unshift($id_list, $id);
                }
                $id_list= join(',', array_unique($id_list));
                $sql .= "AND g.cat_id in ($id_list)";

            } elseif ($favourable['act_range'] == FAR_BRAND) {
                $id_list = $favourable['act_range_ext'];
                $sql .= "AND g.brand_id in ($id_list)";

            } elseif ($favourable['act_range'] == FAR_GOODS) {
                $id_list = $favourable['act_range_ext'];
                $sql .= "AND g.goods_id in ($id_list)" ;
            }

            $res = DB::select($sql);

            foreach ($res as $key => $row) {
                $list[$key] = get_object_vars($row);

                $list[$key]['rec_id'] = $list[$key]['rec_id'];
                $list[$key]['goods_id'] = $list[$key]['goods_id'];
                $list[$key]['goods_name'] = $list[$key]['goods_name'];
                $list[$key]['goods_thumb'] = get_image_path($list[$key]['goods_thumb']);
                $list[$key]['shop_price'] = number_format($list[$key]['shop_price'], 2, '.', '');
                $list[$key]['goods_number'] = $list[$key]['goods_number'];
            }

        }

        return $list;
    }
	

	

	

	
	


}
