<?php

namespace App\Repositories\Coupons;

use App\Models\Users as User;
use App\Models\Goods;
use App\Models\Comment;
use App\Models\Coupons;
use App\Models\CouponsRegion;
use App\Models\CouponsUser;
use App\Repositories\ShopConfig\ShopConfigRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class CouponsRepository
{


    /**
     * 个人中心优惠券列表
     * @param type $num 每页显示记录条数
     * @param type $page 页数
     * @param type $status 状态
     * @return type
     */
    function getCouponsLists($status = 0, $uid)
    {
        $time = gmtime();//当前时间
        if ($status == 0) {
            //领取的优惠券未使用
            $where = "where cu.is_use = 0  and cu.user_id = '$uid' and c.cou_end_time>'$time' ";
        } elseif ($status == 1) {
            //已使用的
            $where = "where cu.is_use = 1  and cu.user_id = '$uid' ";
        } elseif ($status == 2) {
            //过期
            $where = "where  '$time' > c.cou_end_time and  cu.is_use = 0  and cu.user_id = '$uid'";
        }
        $prefix = Config::get('database.connections.mysql.prefix');
        $sql = "SELECT COUNT(*) FROM ". $prefix."coupons_user  AS cu LEFT JOIN  " . $prefix."coupons AS c ON c.cou_id = cu.cou_id  " . $where . "AND c.review_status = 3" ;
        //总条数
        $total = DB::select($sql);
        $total = get_object_vars($total[0]);

        $left_join = " LEFT JOIN " . $prefix."order_info AS o ON cu.order_id = o.order_id ";

        $sql = "SELECT c.*, cu.is_use, cu.is_use_time, cu.user_id, o.order_sn, o.add_time FROM " . $prefix. "coupons_user AS cu LEFT JOIN " . $prefix. "coupons AS c ON c.cou_id = cu.cou_id " .
            $left_join .
            $where . " AND c.review_status = 3 ";
        $tab = DB::select($sql);
        foreach ($tab as $k => $v) {
            $tab[$k] = get_object_vars($v);
            $tab[$k]['cou_start_time'] = date("Y-m-d", $tab[$k]['cou_start_time']);
            $tab[$k]['cou_end_time'] = date("Y-m-d", $tab[$k]['cou_end_time']);
            $tab[$k]['cou_add_time'] = date("Y-m-d H:i:s", $tab[$k]['cou_add_time']);
        }
        $result['tab'] = $tab;
        $result['status'] = $status;//优惠券状态
        $result['total'] = $total;//优惠券条数
        return $result;
    }

    /**
     * 详情优惠券
     * @param $id
     * @return mixed
     */
    public function goodsCoupont($id = 0, $ruId, $uid)
    {
        $time = gmtime();

        $prefix = Config::get('database.connections.mysql.prefix');
        $sql = "SELECT * FROM ".$prefix."coupons WHERE (`cou_type` = 3 OR `cou_type` = 4 ) AND `cou_end_time` >$time AND (( instr(`cou_goods`, $id) ) or (`cou_goods`=0)) AND  review_status = 3 and ru_id=" . $ruId;
        $total = DB::select($sql);
        foreach($total as $key => $val){
            $total[$key] = get_object_vars($val);
            $total[$key]['cou_start_time'] = date("Y-m-d", $total[$key]['cou_start_time']);
            $total[$key]['cou_end_time'] = date("Y-m-d", $total[$key]['cou_end_time']);
            $total[$key]['cou_add_time'] = date("Y-m-d H:i:s", $total[$key]['cou_add_time']);
            $pick = CouponsUser::select()
                        ->where('user_id', $uid)
                        ->where('cou_id', $total[$key]['cou_id'])
                        ->count();
            $cou_num = CouponsUser::select()
                        ->where('cou_id', $total[$key]['cou_id'])
                        ->count();
            if($total[$key]['cou_user_num'] > $pick && $total[$key]['cou_total'] > $cou_num){
                $total[$key]['pick'] = 1;//可领取
            }else{
                $total[$key]['pick'] = 2;//已领完
            }
        }

        return $total;
    }

    /**
     * 优惠券类型
     * @param $id
     * @return mixed
     */
    public function getCoutype($cou_id)
    {
        $res = Coupons::select('cou_type', 'cou_ok_user')
            ->where('cou_id' ,$cou_id )
            ->get()
            ->toArray();

        return $res[0];
    }

    /**
     *  领取优惠券
     * @param type $cou_id
     * @param type $uid
     */
    public function getCoups($cou_id, $uid, $ticket)
    {
        $time = gmtime();
        $prefix = Config::get('database.connections.mysql.prefix');
        $sql = "SELECT c.*,c.cou_total-COUNT(cu.cou_id) cou_surplus FROM ".$prefix."coupons c LEFT JOIN ".$prefix."coupons_user cu ON c.cou_id=cu.cou_id GROUP BY c.cou_id  HAVING cou_surplus>0 AND c.review_status = 3 AND c.cou_id='" . $cou_id . "' AND c.cou_end_time>$time limit 1";

        $total = DB::select($sql);
        $total = get_object_vars($total[0]);

        if (!empty($total)) {
            $num = CouponsUser::where('user_id', $uid)
                    ->where('cou_id', $cou_id)
                    ->count('cou_id');

            $res = Coupons::select('cou_user_num')
            ->where('cou_id', $cou_id)
            ->first();
            $res = $res['cou_user_num'];

            //判断是否已经领取了,并且还没有使用(根据创建优惠券时设定的每人可以领取的总张数为准,防止超额领取)
            if ($res > $num) {
                //领取优惠券
                $add = CouponsUser::insertGetId(['user_id' => $uid, 'cou_id' => $cou_id, 'uc_sn' => $time]);
                if ($add) {
                    $result['msg'] = '领取成功！感谢您的参与，祝您购物愉快';
                    $result['error'] = 2;
                    return $result;
                }
            } else {
                    $result['msg'] = '领取失败,您已经领取过该券了!每人限领取'. $res . '张';
                    $result['error'] = 3;
                    return $result;
            }
        } else {
            $result['msg'] = '优惠券已领完';
            $result['error'] = 4;
            return $result;
        }
    }

    /***
     * 获取用户拥有的优惠券 默认返回所有用户所拥有的优惠券; bylu
     * @param string $user_id 用户ID;
     * @param bool|false $is_use 找出当前用户可以使用的
     * @param bool|false $total 订单总价
     * @param bool|false $cart_goods 商品信息
     * @param bool|false $user 用于区分是否会员中心里取数据(会员中心里的优惠券不能分组)
     * @return mixed 优惠券数组
     */
    function UserCoupons($user_id = '', $is_use = false, $total = '', $cart_goods = false, $user = true, $cart_ru_id = -1, $act_type = 'user')
    {
        $time = gmtime();
        $cart_where = '';

        $prefix = Config::get('database.connections.mysql.prefix');
        //可使用的(平台用平台发的,商家用商家发的,当订单中混合了平台与商家的商品时,各自计算各自的商品总价是否达到各自发放的优惠券门槛,达到的话当前整个订单即可使用该优惠券)
        if ($is_use && isset($total) && $cart_goods) {
            $res = [];
            foreach ($cart_goods['shop_list'] as $k => $v) {
                $res[$v['ru_id']]['order_total'] = $v['goods_price'] * $v['goods_number'];
                $res[$v['ru_id']]['seller_id'] = $v['ru_id'];
                $res[$v['ru_id']]['goods_id'] = $v['goods_id'];
                $res[$v['ru_id']]['cat_id'] = $v['cat_id'];
                $res[$v['ru_id']]['goods'][$v['goods_id']] = $v;
            }

            $arr = [];
            $couarr = [];

            foreach ($res as $key => $row) {
                $row['goods_id'] = $this->get_del_str_comma($row['goods_id']);
                $row['cat_id'] = $this->get_del_str_comma($row['cat_id']);

                $cart_where .= " AND c.ru_id = '" . $row['seller_id'] . "'";

                $sql = "SELECT c.*, cu.uc_id FROM " . $prefix."coupons_user AS cu " .
                    " LEFT JOIN " . $prefix."coupons AS c ON cu.cou_id = c.cou_id " .
                    " WHERE c.review_status = 3 AND c.cou_end_time > $time AND $time > c.cou_start_time" .
                    " AND " . $row['order_total'] . " >= c.cou_man" .
                    " AND cu.order_id = 0 AND cu.is_use = 0 AND cu.user_id = '$user_id'" . $cart_where . " GROUP BY cu.uc_id";
                $couarr[$key] = DB::select($sql);
                foreach($couarr[$key] as $k => $val){
                    $arr[$k] = get_object_vars($val);
                    $arr[$k]['cou_start_time'] = date("Y-m-d", $arr[$k]['cou_start_time']);
                    $arr[$k]['cou_end_time'] = date("Y-m-d", $arr[$k]['cou_end_time']);
                    $arr[$k]['cou_add_time'] = date("Y-m-d H:i:s", $arr[$k]['cou_add_time']);
                }
            }
            return $arr;
        } else {

            if (!empty($user_id) && $user) {
                $where = " WHERE cu.user_id IN(" . $user_id . ") AND c.review_status = 3";
            } else if (!empty($user_id)) {
                $where = " WHERE cu.user_id IN(" . $user_id . ") AND c.review_status = 3";
            }

            $select = "";
            $leftjoin = "";
            if ($act_type == 'cart') {
                $where .= " AND c.cou_end_time > $time AND $time";
            } else {
                $select = ", o.order_sn, o.add_time";
                $leftjoin = " LEFT JOIN " . $prefix."order_info AS o ON cu.order_id = o.order_id ";
            }

            $sql = " SELECT c.*, cu.* $select FROM " .
                $prefix."coupons_user AS cu " .
                " LEFT JOIN " . $prefix."coupons AS c ON c.cou_id = cu.cou_id " .
                $leftjoin .
                $where . $cart_where . " AND cu.is_use = 0 GROUP BY cu.uc_id";

            $res = DB::select($sql);

            if ($res) {
                foreach ($res as $key => $row) {
                    $res[$key]['shop_name'] = get_shop_name($row['ru_id'], 1);
                }
            }
            return $res;
        }
    }

    /**
     * 通过 用户优惠券ID 获取该条优惠券详情
     * @param $uc_id 用户优惠券ID
     * @return mixed
     */
    function getcoupons($userId, $uc_id = 0, $select = array())
    {
        $time = gmtime();

        if ($select && is_array($select)) {
            $select = implode(",", $select);
        } else {
            $select = "c.*, cu.*";
        }
        $prefix = Config::get('database.connections.mysql.prefix');
        $sql = " SELECT $select FROM " . $prefix."coupons_user cu " .
            " LEFT JOIN " . $prefix."coupons c ON c.cou_id = cu.cou_id " .
            " WHERE cu.uc_id = '$uc_id' AND cu.user_id = '" . $userId . "' AND c.cou_end_time > $time  ";
        $total = DB::select($sql);
        $total = !empty($total) ? get_object_vars($total[0]) : '';
        return $total;
    }

    /**
     * 通过 用户优惠券ID 改变该条优惠券使用状态
     * @param $uc_id 用户优惠券ID
     * @return mixed
     */
    function getupcoutype($uc_id, $time)
    {
        if (!empty($uc_id)) {
            $array = [
                'is_use' => 1,
                'is_use_time' => $time
            ];
            $total = CouponsUser::where('uc_id', $uc_id)
                        ->update($array);
        }
        return $total;
    }

    /**
     * 获取当前优惠券的不包邮地区
     * @param $cou_id 优惠券ID
     * @return $cou_region 不包邮地区
     */
    function getcouponsregion($cou_id = 0)
    {
        $region = CouponsRegion::select('region_list')
                    ->where('cou_id', $cou_id)
                    ->get()
                    ->toArray();

        return $region;
    }

    /**
     * 去除字符串中首尾逗号
     * 去除字符串中出现两个连续逗号
     */
    function get_del_str_comma($str = '')
    {

        if ($str && is_array($str)) {
            return $str;
        } else {
            if ($str) {
                $str = str_replace(",,", ",", $str);

                $str1 = substr($str, 0, 1);
                $str2 = substr($str, str_len($str) - 1);

                if ($str1 === "," && $str2 !== ",") {
                    $str = substr($str, 1);
                } elseif ($str1 !== "," && $str2 === ",") {
                    $str = substr($str, 0, -1);
                } elseif ($str1 === "," && $str2 === ",") {
                    $str = substr($str, 1);
                    $str = substr($str, 0, -1);
                }
            }

            return $str;
        }
    }


}
