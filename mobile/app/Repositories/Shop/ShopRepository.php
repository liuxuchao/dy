<?php

namespace App\Repositories\Shop;

use App\Models\TouchAd;
use App\Models\Goods;
use App\Models\ArticleXiao;
use App\Models\MerchantsShopInformation;
use App\Models\SellerShopinfo;
use App\Models\MerchantsCategory;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class ShopRepository
{

    /**
     * @param id
     * @return array
     */
    public function get($id)
    {
        return $this->findBY('id', $id);
    }

    /**
     * 根据其他值找店铺信息
     * @param $key
     * @param $val
     * @return array
     */
    public function findBY($key, $val)
    {
        $list = SellerShopinfo::select('ru_id', 'shop_name', 'shop_logo', 'shopname_audit')
            ->with(['MerchantsShopInformation' => function ($query) {
                $query->select('shoprz_brandName', 'user_id', 'shopNameSuffix', 'rz_shopName');
            }])
            ->where($key, $val)
            ->get()
            ->toArray();

        if (empty($list)) {
            $list = [];
            return $list;
        }

        //
        foreach ($list as $k => $v) {
            $list[$k]['brandName'] = $v['merchants_shop_information']['shoprz_brandName'];
            $list[$k]['shopNameSuffix'] = $v['merchants_shop_information']['shopNameSuffix'];
            $list[$k]['rz_shopName'] = $v['merchants_shop_information']['rz_shopName'];

            unset($list[$k]['merchants_shop_information']);
        }

        return $list;
    }

    /**
     * 获取轮播图
     * @param $tc_type
     * @param $num
     * @return array
     */
    public function getPositions($tc_type = 'weapp', $num = 3)
    {
        $time = local_gettime();

        $ads = TouchAd::select('ad_id', 'touch_ad.position_id', 'media_type', 'ad_link', 'ad_code', 'ad_name')
            ->with(['position'])
            ->join('touch_ad_position', 'touch_ad_position.position_id', '=', 'touch_ad.position_id')
            ->where("start_time", '<=', $time)
            ->where("touch_ad_position.tc_type", 'banner')
            ->where("touch_ad_position.ad_type", $tc_type)
            ->where("end_time", '>=', $time)
            ->where("enabled", 1);

        $res = $ads->orderby('ad_id', 'desc')
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

    //end

    /**
     * 改写  \App\Libraries\Shop 方法
     * 查询MYSQL拼接字符串数据
     * $select_array 查询的字段
     * $select_id 查询的ID值
     * $where 查询的条件 比如（AND goods_id = '$goods_id'）
     * $table 表名称
     * $id 被查询的字段
     * $is_db 查询返回数组方式
     */
    public function get_select_find_in_set($is_db = 0, $select_id, $select_array = array(), $where = '', $table = '', $id = '', $replace = '')
    {
        if ($replace) {
            $replace = "REPLACE ($id,'$replace',',')";
        } else {
            $replace = "$id";
        }

        if ($select_array && is_array($select_array)) {
            $select = implode(',', $select_array);
        } else {
            $select = '*';
        }
        $prefix = Config::get('database.connections.mysql.prefix');

        $sql = "SELECT {$select} FROM {$prefix}{$table} WHERE find_in_set('$select_id', $replace) $where";

        //
        if ($is_db == 1) {
            //多条数组数据
            return DB::select($sql);
        } elseif ($is_db == 2) {
            //一条数组数据
            $res = DB::select($sql);
            return isset($res[0]) ? json_decode(json_encode($res[0]), 1) : array();
        } else {
            //返回某个字段的值
            $sql = trim($sql . ' LIMIT 1');

            $res = DB::select($sql);
            if ($res !== false) {
                $row = isset($res[0]) ? json_decode(json_encode($res[0]), 1) : array();
                if ($row !== false) {
                    return reset($row);
                } else {
                    return '';
                }
            } else {
                return array();
            }
        }

    }

    /**
     * 获取广告
     * @param $tc_type
     * @param $num
     * @return array
     */
    public function getAd()
    {
        $time = local_gettime();
        $list = array();
        $position_id = [
            '0' => 256,
            '1' => 257,
            '2' => 258
        ];


        foreach($position_id as $key => $value){
            $res['ad'][$key] = TouchAd::select('ad_id', 'position_id', 'media_type', 'ad_link', 'ad_code', 'ad_name')
                ->where("position_id", $value)
                ->get()
                ->toArray();

                foreach($res['ad'][$key] as $k => $v){
                    if(strpos($v['ad_code'], 'http') === false){
                        $res['ad'][$key][$k]['ad_code'] = get_image_path($v['ad_code'], 'data/afficheimg');
                    }else{
                        $res['ad'][$key][$k]['ad_code'] = $v['ad_code'];
                    }
                }
        }
        return $res;
    }

    /**
     * 获取店铺
     * @param $tc_type
     * @param $num
     * @return array
     */
    public function getStore()
    {
        $store = MerchantsShopInformation::select('shop_id', 'user_id', 'rz_shopName', 'sort_order')
                ->with(['sellershopinfo'=>function ($query) {
                    $query->select('logo_thumb', 'ru_id', 'street_thumb');
                }])
                ->where('shop_close', 1)
                ->where('is_street', 1)
                ->limit(6)
                ->orderBy('sort_order', 'ASC')
                ->get()
                ->toArray();

        foreach ($store as $key => $val) {
            $store[$key]['sellershopinfo']['logo_thumb'] = get_image_path(str_replace('../', '', $val['sellershopinfo']['logo_thumb']));
            $store[$key]['sellershopinfo']['street_thumb'] = get_image_path($val['sellershopinfo']['street_thumb']);
            $store[$key]['goods'] = Goods::select('goods_id', 'goods_name', 'goods_thumb')
                ->where('user_id', $val['user_id'])
                ->where('is_on_sale', '1')
                ->where('is_alone_sale', '1')
                ->limit(3)
                ->orderBy('sort_order', 'ASC')
                ->get()
                ->toArray();
            foreach ($store[$key]['goods'] as $k => $v) {
                $store[$key]['goods'][$k]['goods_thumb'] = get_image_path($v['goods_thumb']);
            }
        }

        return $store;
    }

    /**
     * 获取新闻
     * @param $tc_type
     * @param $num
     * @return array
     */
    public function getArticle()
    {
        $res = ArticleXiao::select('title', 'content', 'article_id')
                ->where('is_open', 1)
                ->where('cat_id', '>', 0)
                ->limit(10)
                ->orderBy('add_time', 'desc')
                ->get()
                ->toArray();

        return $res;
    }

}
