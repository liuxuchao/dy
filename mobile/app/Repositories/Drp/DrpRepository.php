<?php

namespace App\Repositories\Drp;

use App\Models\Cart;
use App\Models\OrderGoods;
use App\Models\DrpConfig;
use App\Models\DrpLog;
use App\Models\DrpUserCredit;
use App\Models\ArticleXiao;
use App\Models\DrpType;
use App\Models\OrderInfo;
use App\Models\Payment;
use App\Models\Goods;
use App\Models\WechatUser;
use App\Models\DrpAffiliateLog;
use App\Models\ShippingArea;
use App\Services\AuthService;
use App\Models\DrpShop;
use App\Models\Users;
use App\Models\FavourableActivity;
use App\Repositories\Shop\ShopRepository;
use App\Repositories\Goods\GoodsRepository;
use App\Repositories\User\UserRankRepository;
use App\Repositories\ShopConfig\ShopConfigRepository;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * Class DrpRepository
 * @package App\Repositories\drp
 */
class DrpRepository
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

    /**
     * DrpRepository constructor.
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
        ShopRepository $shopRepository
    ) {
        $this->shopConfigRepository = $shopConfigRepository;
        $this->userRankRepository = $userRankRepository;
        $this->authService = $authService;
        $this->goodsRepository = $goodsRepository;
        $this->shopRepository = $shopRepository;
        $this->model = Cart::where('rec_id', '<>', 0);
    }

    /**
     * 判断是否已经开店
     * @param $columns
     * @return Cart
     */
    public function judgmentDrp($uid)
    {
        $res = DrpShop::select('id')
                ->where('user_id', $uid)
                ->first();
        $res = !empty($res) ? $res->toArray() : '';
        return $res;
    }

    /**
     * 判断店铺名
     * @param $columns
     * @return Cart
     */
    public function judgmentShop($shopname)
    {
        $res = DrpShop::select('id')
                ->where('shop_name', $shopname)
                ->first();
        $res = !empty($res) ? $res->toArray() : '';
        return $res;
    }

    /**
     * 判断号码
     * @param $columns
     * @return Cart
     */
    public function judgmentMobile($mobile)
    {
        $res = DrpShop::select('id')
                ->where('mobile', $mobile)
                ->first();
        $res = !empty($res) ? $res->toArray() : '';
        return $res;
    }

    /**
     * 添加店铺
     * @param $columns
     * @return Cart
     */
    public function creatDrp($uid, $result)
    {

        $res = new DrpShop();
        $res->user_id = $uid;
        $res->shop_name = $result['shopname'];
        $res->real_name = $result['realname'];
        $res->mobile = $result['mobile'];
        $res->qq = $result['qq'];
        $res->create_time = gmtime();

        $res->save();
    }

    /**
     * 插入分销表信息
     * @param $columns
     * @return Cart
     */
    public function insertDrplog($drp)
    {
        $res = new DrpLog();
        $res->order_id = $drp['order_id'];
        $res->time = $drp['time'];
        $res->user_id = $drp['user_id'];
        $res->user_name = $drp['user_name'];
        $res->money = $drp['money'];
        $res->point = $drp['point'];

        $res->save();
    }

    /**
     * 修改店铺信息
     * @param $columns
     * @return Cart
     */
    public function settings($uid, $result)
    {
        $res = DrpShop::where('user_id', $uid)
                    ->update($result);
    }

    /**
     * 店铺
     * @param $columns
     * @return Cart
     */
    public function userInfo($uid)
    {
        $res = DrpShop::where('user_id', $uid)
                    ->first();
        if ($res) {
            return $res->toArray();
        } else {
            return '';
        }
    }

    public function get_drp_money($type, $uid)
    {
        if ($type === 0) {

            $res = DrpAffiliateLog::where('separate_type', '!=', '-1')
                    ->where('user_id', $uid)
                    ->sum('money');
        } else {
            if ($type === 1) {

            $res = DrpAffiliateLog::where('separate_type', '!=', '-1')
                    ->where('time', '>=', mktime(0, 0, 0))
                    ->where('user_id', $uid)
                    ->sum('money');
            } else {
                $res = OrderGoods::from('order_goods as o')
                    ->leftjoin('drp_affiliate_log as a', 'o.order_id', '=', 'a.order_id')
                    ->where('a.separate_type', '!=', '-1')
                    ->where('a.user_id', $uid)
                    ->sum('goods_price');

            }
        }

        return $res;
    }

    public function shopMoney($uid) {

        $res = DrpShop::select('shop_money')
                    ->where('user_id', $uid)
                    ->first();

        return $res->toArray();
    }

    /**
     * 我的团队
     * @param $columns
     * @return parent
     */
    public function parentInfo($uid) {        

        $res = Users::from('users as u')
            ->select('u.user_id','u.user_name','u.nick_name', 'u.user_picture','u.reg_time','s.status', 's.audit')
            ->leftjoin('drp_shop as s', 'u.user_id', '=', 's.user_id')
            ->where('u.drp_parent_id', $uid)
            ->where('s.status', 1)
            ->where('s.audit', 1)
            ->orderby('u.reg_time', 'desc')
            ->get()
            ->toArray();

        foreach($res as $key => $val) {
            $res[$key]['money'] = DrpAffiliateLog::where('user_id', $val['user_id'])
                            ->sum('money');
        }

        return $res;
    }

    /**
     * 团队详情
     * @param $columns
     * @return parent
     */
    public function teamdetail($uid) {
        $prefix = Config::get('database.connections.mysql.prefix');
        $sql = "SELECT u.user_id,u.drp_parent_id, dp.create_time, IFNULL(w.nickname,u.user_name) as name, w.headimgurl, FROM_UNIXTIME(u.reg_time, '%Y-%m-%d') as time,
                IFNULL((select sum(sl.money) from ". $prefix."drp_affiliate_log as sl
                        left join ". $prefix."order_info as so on sl.order_id=so.order_id
                        where so.user_id='$uid' and sl.separate_type != -1 and sl.user_id=u.drp_parent_id),0) as sum_money,
                IFNULL((select sum(nl.money) from ". $prefix."drp_affiliate_log as nl
                        left join ". $prefix."order_info as no on nl.order_id=no.order_id
                        where  nl.time>'" . mktime(0, 0, 0) . "' and no.user_id='$uid' and nl.separate_type != -1 and nl.user_id=u.drp_parent_id),0) as now_money,
                       (select count(h.user_id) from ". $prefix."users as h LEFT JOIN ". $prefix."drp_shop as s on s.user_id=h.user_id where s.status=1 and s.audit=1 and drp_parent_id='$uid' ) as next_num
                FROM ". $prefix."users as u
                LEFT JOIN  ". $prefix."wechat_user as w ON u.user_id=w.ect_uid
                LEFT JOIN  ". $prefix."drp_shop as dp ON u.user_id=dp.user_id
                WHERE u.user_id='$uid'";
        $res = DB::select($sql);

        return $res;
    }

    /**
     * 下线会员
     * @param $columns
     * @return parent
     */
    public function OfflineUser($uid) {

        $res = Users::select('*')
                    ->where('parent_id', $uid)
                    ->orderby('reg_time', 'desc')
                    ->get()
                    ->toArray();  
        return $res;
    }


    /**
     * 分销商等级列表
     * @param $columns
     * @return parent
     */
    public function drpUserCredit()
    {
        $rank_info = DrpUserCredit::select('id', 'credit_name', 'min_money', 'max_money')
             ->get()
             ->toArray();
       
        return $rank_info;
    }


    /**
     * 分销商等级
     */
    public function drp_rank_info($user_id)
    {
        //检测分销商是否是特殊等级,直接获取等级信息
        $drp_info = DrpShop::select('credit_id')
                        ->where('user_id', $user_id)
                        ->first();
        if (!empty($drp_info)) {
            $drp_info = $drp_info->toArray();
            $rank_info = DrpUserCredit::select('id', 'credit_name', 'min_money', 'max_money')
                    ->where('id', 1)
                    ->first();
            $rank_info = $rank_info->toArray();
            if ($drp_info['credit_id'] > 0) {
                $rank_info = DrpUserCredit::select('id', 'credit_name', 'min_money', 'max_money')
                        ->where('id', $drp_info['credit_id'])
                        ->first();
                $rank_info = $rank_info->toArray();
            }
        } else {
        //统计分销商所属订单金额
            $totals = OrderGoods::from('order_goods as o')
                        ->leftjoin('drp_affiliate_log as a', 'o.order_id', '=', 'a.order_id')
                        ->where('a.separate_type', '!=', -1)
                        ->where('a.user_id', $user_id)
                        ->sum('money');
            $goods_price = $totals ? $totals : 0;

            $rank_info = DrpUserCredit::select('id', 'credit_name', 'min_money', 'max_money')
                             ->where('min_money', '<=', $goods_price)
                             ->where('max_money', '>', $goods_price)
                             ->first();
            $rank_info = $rank_info->toArray();
        }
        return $rank_info;
    }

    /**
     * 判断开店条件
     * @param $columns
     * @return parent
     */
    public function drpType($code) {

        $res = DrpConfig::select('value')
                    ->where('code', $code)
                    ->first();

        return $res->toArray();
    }

    /**
     * 支付信息
     * @param $columns
     * @return parent
     */
    public function payment($code) {

        $res = Payment::select('*')
                    ->where('pay_code', $code)
                    ->get()
                    ->toArray();

        return $res;
    }

    /**
     * 店铺订单
     * @param $columns
     * @return parent
     */
    public function order($uid, $corrent, $size, $status) {

        $res = DrpLog::from('drp_log as dl')
                ->select('dl.user_id', 'u.nick_name', 'u.user_name', 'dl.money','o.*','dl.point', 'dl.drp_level', 'dl.is_separate')
                ->leftjoin('order_info as o', 'o.order_id', '=', 'dl.order_id')
                ->leftjoin('wechat_user as w', 'w.ect_uid', '=', 'o.user_id')
                ->leftjoin('users as u', 'u.user_id', '=', 'o.user_id')
                ->offset($corrent)
                ->where('dl.user_id', $uid)
                ->where('o.pay_status', 2);

        if ($status == 2) {
            $res = $res;
        } else {
            $res = $res->where('o.drp_is_separate', $status);
        }

        $res = $res
            ->whereIn('dl.is_separate', [0, 1])
            ->orderby('order_id', 'desc')
            ->limit($size)
            ->get()
            ->toArray();

        return $res;
    }

    /**
     * 订单详情
     * @param $columns
     * @return parent
     */
    public function orderDetail($uid, $order_id) {

        $res = DrpLog::from('drp_log as dl')
            ->select('dl.user_id','u.nick_name','u.user_name','dl.money','o.*','dl.point', 'dl.drp_level')
            ->leftjoin('order_info as o', 'o.order_id', '=', 'dl.order_id')
            ->leftjoin('wechat_user as w', 'w.ect_uid', '=', 'o.user_id')
            ->leftjoin('users as u', 'u.user_id', '=', 'o.user_id')
            ->where('dl.user_id', $uid)
            ->where('o.order_id', $order_id)
            ->where('o.pay_status', 2)
            ->first();

        return $res->toArray();
    }

    /**
     * 订单商品
     * @param $columns
     * @return parent
     */
    public function ordergoods($order_id) {

        $res = OrderGoods::from('order_goods as og')
            ->select('og.rec_id','og.goods_id','og.goods_name','og.goods_attr','og.goods_number', 'og.goods_price', 'og.drp_money', 'g.goods_thumb')
            ->leftjoin('goods as g', 'og.goods_id', '=', 'g.goods_id')
            ->where('og.order_id', $order_id)
            ->where('og.is_distribution', 1)
            ->where('og.drp_money', '>', 0)
            ->get()
            ->toArray();

        return $res;
    }

    /**
     * 排行
     * @param $columns
     * @return parent
     */
    public function rankList($order_id) {

        $prefix = Config::get('database.connections.mysql.prefix');
        $sql = "SELECT d.user_id, w.nickname, w.headimgurl, u.user_name, u.user_picture,
                    IFNULL((select sum(money) from ".$prefix."drp_affiliate_log where  user_id=d.user_id and separate_type != -1),0) as money,
                    IFNULL((select count(user_id) from ".$prefix."users where drp_parent_id=d.user_id ),0) as user_num
                    FROM ".$prefix."drp_shop as d
                    LEFT JOIN ".$prefix."users as u ON d.user_id=u.user_id
                    LEFT JOIN ".$prefix."wechat_user as w ON d.user_id=w.ect_uid
                    LEFT JOIN ".$prefix."drp_affiliate_log as log ON log.user_id=d.user_id
                    where d.audit=1 and d.status=1
                    GROUP BY d.user_id
                    ORDER BY money desc,user_num desc";
        $res = DB::select($sql);

        return $res;
    }

    /**
     * 分类商品
     * @param $columns
     * @return parent
     */
    public function getCategoryGetGoods($uid, $cat_id) {

        $list = Goods::select('goods_id', 'cat_id', 'goods_name', 'shop_price', 'goods_thumb')
                    ->where('is_real', 1)
                    ->where('is_on_sale', 1)
                    ->where('is_alone_sale', 1)
                    ->where('review_status', '>', 2)
                    ->where('is_show', 1)
                    ->where('is_distribution', 1)
                    ->where('cat_id', $cat_id)
                    ->get()
                    ->toArray();
        foreach ($list as $k => $v) {
            $list[$k]['goods_thumb'] = get_image_path($v['goods_thumb']);
            $type = DrpType::select('id')
                    ->where('goods_id', $v['goods_id'])
                    ->where('user_id', $uid)
                    ->first();
            if (!empty($type)) {
                $list[$k]['drp_type'] = true;
            } else {
                $list[$k]['drp_type'] = false;
            }
        }

        return $list;
    }

    /**
     * 商品是否已有
     * @param $columns
     * @return parent
     */
    public function drpgoods($uid, $id) {

        $type = DrpType::select('id')
            ->where('goods_id', $id)
            ->where('user_id', $uid)
            ->first();
        if (!empty($type)) {
            $res = DrpType::where('goods_id', $id)
                ->where('user_id', $uid)
                ->delete();
        } else {
            $res = new DrpType();
            $res->user_id = $uid;
            $res->cat_id = 0;
            $res->goods_id = $id;
            $res->add_time = gmtime();
            $res->type = 2;
            $res->save();
        }
    }

    /**
     * 分类是否已有
     * @param $columns
     * @return parent
     */
    public function drpcat($uid, $id) {
        foreach ($id as $k => $val) {
            $type = DrpType::select('id')
                ->where('cat_id', $val)
                ->where('user_id', $uid)
                ->first();
            if (!empty($type)) {
            $res = DrpType::where('cat_id', $val)
                ->where('user_id', $uid)
                ->delete();
            } else {
                $res = new DrpType();
                $res->user_id = $uid;
                $res->cat_id = $val;
                $res->goods_id = 0;
                $res->add_time = gmtime();
                $res->type = 1;
                $res->save();
            }
        }
    }

    /**
     * 代言商品列表
     * @param $columns
     * @return parent
     */
    public function showgoods($uid, $corrent, $size, $type, $status = 0) {

        if ($type == 0) {
            $goods = Goods::select('goods_id', 'cat_id', 'goods_name', 'shop_price', 'goods_thumb');

            if ($status == 2) {
                $goods = $goods->where('is_new', 1);
            } elseif ($status == 3) {
                $time = gmtime();
                $goods = $goods->where('promote_price', '>', 0)->where('promote_start_date', '<=', $time)->where('promote_end_date', '>=', $time);
            }

            $res = $goods
                    ->where('is_real', 1)
                    ->where('is_on_sale', 1)
                    ->where('is_alone_sale', 1)
                    ->where('review_status', '>', 2)
                    ->where('is_show', 1)
                    ->where('is_distribution', 1)
                    ->get()
                    ->toArray();

            foreach ($res as $k => $v) {
                $res[$k]['goods_thumb'] = get_image_path($v['goods_thumb']);
            }
        } else {
            $list = DrpType::select('goods_id', 'cat_id')
                        ->offset($corrent)
                        ->where('user_id', $uid)
                        ->where('type', $type)
                        ->limit($size)
                        ->get()
                        ->toArray();
            $res = [];
            if (!empty($list)) {
                foreach ($list as $k => $v) {
                    if ($type == 2) {
                        $goods = Goods::select('goods_id', 'cat_id', 'goods_name', 'shop_price', 'goods_thumb');

                        if ($status == 2) {
                            $goods = $goods->where('is_new', 1);
                        } elseif ($status == 3) {
                            $time = gmtime();
                            $goods = $goods->where('promote_price', '>', 0)->where('promote_start_date', '<=', $time)->where('promote_end_date', '>=', $time);
                        }

                        $goods = $goods
                                ->where('is_real', 1)
                                ->where('is_on_sale', 1)
                                ->where('is_alone_sale', 1)
                                ->where('review_status', '>', 2)
                                ->where('is_show', 1)
                                ->where('is_distribution', 1)
                                ->where('goods_id', $v['goods_id'])
                                ->first();
                        if ($goods) {
                            $res[$k] = $goods->toArray();
                            $res[$k]['goods_thumb'] = get_image_path($res[$k]['goods_thumb']);
                        } else {
                            unset($res[$k]);
                        }
                    } else {
                        $goods = Goods::select('goods_id', 'cat_id', 'goods_name', 'shop_price', 'goods_thumb');

                        if ($status == 2) {
                            $goods = $goods->where('is_new', 1);
                        } elseif ($status == 3) {
                            $time = gmtime();
                            $goods = $goods->where('promote_price', '>', 0)->where('promote_start_date', '<=', $time)->where('promote_end_date', '>=', $time);
                        }

                        $res[$k] = $goods
                                ->where('is_real', 1)
                                ->where('is_on_sale', 1)
                                ->where('is_alone_sale', 1)
                                ->where('review_status', '>', 2)
                                ->where('is_show', 1)
                                ->where('is_distribution', 1)
                                ->where('cat_id', $v['cat_id'])
                                ->get()
                                ->toArray();
                        if (empty($res[$k])) {
                            unset($res[$k]);
                        }
                    }
                }
            }
            if ($type == 1) {
                $res = array_merge($res);
                $res = isset($res[0]) ? $res[0] : $res;
                foreach ($res as $k => $v) {
                    $res[$k]['goods_thumb'] = get_image_path($v['goods_thumb']);
                }
            }
        }

        return $res;
    }


    // 佣金明细
    public function Drplog($uid, $corrent, $size, $status)
    {

        $res = DrpLog::from('drp_log as a')
            ->select('o.order_sn','a.time','a.user_id','a.time','a.money', 'a.point', 'a.separate_type', 'w.nickname', 'u.user_name', 'a.is_separate')
            ->leftjoin('order_info as o', 'o.order_id', '=', 'a.order_id')
            ->leftjoin('wechat_user as w', 'w.ect_uid', '=', 'o.user_id')
            ->leftjoin('users as u', 'o.user_id', '=', 'u.user_id')
            ->offset($corrent);
        if ($status == 2) {
            //全部
            $res = $res;
        } else {
            //已分成 OR 等待处理
            $res = $res->where('a.is_separate', $status);
        }

        $res = $res
            ->where('a.user_id', $uid)
            ->where('o.order_status', 1)
            ->where('o.pay_status', 2)
            ->limit($size)
            ->get()
            ->toArray();

            foreach ($res as $k => $v) {
                $res[$k]['time'] = date('Y-m-d H:i', $v['time']);
                $res[$k]['is_separate'] = ($v['is_separate'] == '1') ? '已分成' : '等待处理';
                if($v['separate_type'] == -1){
                    $res[$k]['is_separate'] = '已撤销';
                }
            }
        return $res;
    }

    // 判断用户是否开店
    public function drpUserShop($uid)
    {
        $res = Users::from('users as u')
            ->select('u.drp_parent_id')
            ->leftjoin('drp_shop as ds', 'u.drp_parent_id', '=', 'ds.user_id')
            ->where('u.user_id', $uid)
            ->where('ds.audit', 1)
            ->where('ds.status', 1)
            ->first();

        if ($res) {
            return $res->toArray();
        } else {
            return '';
        }
    }

    // 文章
    public function News()
    {
        $res = ArticleXiao::select('title', 'content')
            ->where('is_open', 1)
            ->where('cat_id', 1000)
            ->orderby('add_time', 'desc')
            ->get()
            ->toArray();

        return $res;
    }

    // 获取订单商品分销金额总和
    public function Drpmoney($order_id)
    {
        $res = OrderGoods::where('order_id', $order_id)
            ->sum('drp_money');
        return $res;
    }
}
