<?php

/**
 * DSC 商品详情
 * ============================================================================
 * * 版权所有 2005-2016 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: Zhuo $
 * $Id: common.php 2016-01-04 Zhuo $
 */
define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require(ROOT_PATH . '/includes/lib_area.php');  //ecmoban模板堂 --zhuo

$warehouse_other = [
    'province_id' => $province_id,
    'city_id' => $city_id
];
$warehouse_area_info = get_warehouse_area_info($warehouse_other);

$region_id = $warehouse_area_info['region_id'];
$area_id = $warehouse_area_info['area_id'];
$area_city = $warehouse_area_info['city_id'];

if ((DEBUG_MODE & 2) != 2) {
    $smarty->caching = true;
}

$affiliate = unserialize($GLOBALS['_CFG']['affiliate']);
$smarty->assign('affiliate', $affiliate);
$factor = intval($_CFG['comment_factor']);
$smarty->assign('factor', $factor);
/*------------------------------------------------------ */
//-- INPUT
/*------------------------------------------------------ */

/* 过滤 XSS 攻击和SQL注入 */
get_request_filter();

$goods_id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
$pid = isset($_REQUEST['pid']) && !empty($_REQUEST['pid']) ? intval($_REQUEST['pid']) : 0;
$user_id = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

$ua = strtolower($_SERVER['HTTP_USER_AGENT']);

$uachar = "/(nokia|sony|ericsson|mot|samsung|sgh|lg|philips|panasonic|alcatel|lenovo|cldc|midp|mobile)/i";

if (($ua == '' || preg_match($uachar, $ua)) && !strpos(strtolower($_SERVER['REQUEST_URI']), 'wap')) {
    $Loaction = 'mobile/index.php?r=goods&id=' . $goods_id;

    if (!empty($Loaction)) {
        ecs_header("Location: $Loaction\n");

        exit;
    }
}

$smarty->assign('category', $goods_id);

//参数不存在则跳转回首页
if (!isset($_REQUEST['id'])) {
    header("Location: index.php\n");
    exit;
}

/* 清空配件购物车 */
if (!empty($user_id)) {
    $sess_id = " user_id = '$user_id' ";
} else {
    $sess_id = " session_id = '" . real_cart_mac_ip() . "' ";
}

/*------------------------------------------------------ */
//-- 改变属性、数量时重新计算商品价格
/*------------------------------------------------------ */

if (!empty($_REQUEST['act']) && $_REQUEST['act'] == 'price') {
    include('includes/cls_json.php');

    $json = new JSON;
    $res = array('err_msg' => '', 'err_no' => 0, 'result' => '', 'qty' => 1);

    $attr_id = isset($_REQUEST['attr']) && !empty($_REQUEST['attr']) ? explode(',', $_REQUEST['attr']) : array();
    $number = (isset($_REQUEST['number'])) ? intval($_REQUEST['number']) : 1;
    $warehouse_id = (isset($_REQUEST['warehouse_id'])) ? intval($_REQUEST['warehouse_id']) : 0;
    $area_id = (isset($_REQUEST['area_id'])) ? intval($_REQUEST['area_id']) : 0; //仓库管理的地区ID

    $onload = (isset($_REQUEST['onload'])) ? trim($_REQUEST['onload']) : ''; //仓库管理的地区ID

    $goods_attr = isset($_REQUEST['goods_attr']) && !empty($_REQUEST['goods_attr']) ? explode(',', $_REQUEST['goods_attr']) : array();
    $attr_ajax = get_goods_attr_ajax($goods_id, $goods_attr, $attr_id);

    $goods = get_goods_info($goods_id, $warehouse_id, $area_id, $area_city);

    if ($goods_id == 0) {
        $res['err_msg'] = $_LANG['err_change_attr'];
        $res['err_no'] = 1;
    } else {
        if ($number == 0) {
            $res['qty'] = $number = 1;
        } else {
            $res['qty'] = $number;
        }

        //ecmoban模板堂 --zhuo start
        $products = get_warehouse_id_attr_number($goods_id, $_REQUEST['attr'], $goods['user_id'], $warehouse_id, $area_id, $area_city);
        $attr_number = isset($products['product_number']) ? $products['product_number'] : 0;
        $product_promote_price = isset($products['product_promote_price']) ? $products['product_promote_price'] : 0;

        if ($goods['model_attr'] == 1) {
            $table_products = "products_warehouse";
            $type_files = " and warehouse_id = '$warehouse_id'";
        } elseif ($goods['model_attr'] == 2) {
            $table_products = "products_area";
            $type_files = " and area_id = '$area_id'";
            if ($GLOBALS['_CFG']['area_pricetype'] == 1) {
                $type_files .= " AND city_id = '$area_city'";
            }
        } else {
            $table_products = "products";
            $type_files = "";
        }

        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table($table_products) . " WHERE goods_id = '$goods_id'" . $type_files . " LIMIT 1";
        $prod = $GLOBALS['db']->getRow($sql);

        //贡云商品 获取库存
        if ($goods['cloud_id'] > 0) {
            $attr_number = 0;
            if (!empty($attr_id)) {
                $plugin_file = ROOT_PATH . '/plugins/cloudApi/cloudApi.php';
                $sql = "SELECT cloud_product_id FROM" . $ecs->table('products') . "WHERE product_id = '" . $products['product_id'] . "'";
                $productIds = $db->getCol($sql);
                if (file_exists($plugin_file)) {
                    include_once($plugin_file);
                    $cloud = new cloud();
                    $cloud_prod = $cloud->queryInventoryNum($productIds);

                    $cloud_prod = json_decode($cloud_prod, true);

                    if ($cloud_prod['code'] == 10000) {
                        $cloud_product = $cloud_prod['data'];
                        if ($cloud_product) {

                            foreach ($cloud_product as $k => $v) {
                                if (in_array($v['productId'], $productIds)) {
                                    if ($v['hasTax'] == 1) {
                                        $attr_number = $v['taxNum'];
                                    } else {
                                        $attr_number = $v['noTaxNum'];
                                    }

                                    break;
                                }
                            }
                        }
                    }
                }
            }
        } else {

            if ($goods['goods_type'] == 0) {
                $attr_number = $goods['goods_number'];
            } else {
                if (empty($prod)) { //当商品没有属性库存时
                    $attr_number = $goods['goods_number'];
                }
            }
        }

        if (empty($prod)) { //当商品没有属性库存时
            $res['bar_code'] = $goods['bar_code'];
        } else {
            $res['bar_code'] = $products['bar_code'];
        }

        $attr_number = !empty($attr_number) ? $attr_number : 0;

        $res['attr_number'] = $attr_number;
        //ecmoban模板堂 --zhuo end

        $res['show_goods'] = 0;
        if ($goods_attr && $GLOBALS['_CFG']['add_shop_price'] == 0) {
            if (count($goods_attr) == count($attr_ajax['attr_id'])) {
                $res['show_goods'] = 1;
            }
        }

        $shop_price = get_final_price($goods_id, $number, true, $attr_id, $warehouse_id, $area_id, $area_city);
        $res['shop_price'] = price_format($shop_price);

        //如果有属性，属性价格 
        if ($attr_id) {
            $spec_price = get_final_price($goods_id, $number, true, $attr_id, $warehouse_id, $area_id, $area_city, 1, 0, 0, $res['show_goods'], $product_promote_price);
        }

        if ($GLOBALS['_CFG']['add_shop_price'] == 0 && empty($spec_price) && empty($prod)) {
            $spec_price = $shop_price;
        }
        $res['goods_rank_prices'] = '';
        if ($GLOBALS['_CFG']['add_shop_price'] == 0) {
            $res['result'] = price_format($spec_price);

            if ($products['product_price'] > 0) {
                $rank_prices = get_user_rank_prices($goods_id, $products['product_price']);

                if (!empty($rank_prices)) {
                    $smarty->assign('act', 'goods_rank_prices');
                    $smarty->assign('rank_prices', $rank_prices);
                    $res['goods_rank_prices'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
                }
            }

        } else {
            $res['result'] = price_format($shop_price);
        }

        $res['spec_price'] = price_format($spec_price);
        $res['original_shop_price'] = $shop_price;
        $res['original_spec_price'] = $spec_price;
        $res['marketPrice_amount'] = price_format($goods['marketPrice'] + $spec_price);


        if ($GLOBALS['_CFG']['add_shop_price'] == 0) {
            $goods['marketPrice'] = isset($products['product_market_price']) && !empty($products['product_market_price']) ? $products['product_market_price'] : $goods['marketPrice'];
            $res['result_market'] = price_format($goods['marketPrice']); // * $number
        } else {
            $res['result_market'] = price_format($goods['marketPrice'] + $spec_price); // * $number
        }

        //@author-bylu 当点击了数量加减后 重新计算白条分期 每期的价格 start
        if ($goods['stages']) {
            if (!is_array($goods['stages'])) {
                $stages = unserialize($goods['stages']);
            } else {
                $stages = $goods['stages'];
            }
            $total = floatval(strip_tags(str_replace('¥', '', $res['result']))); //总价+运费*数量;
            foreach ($stages as $K => $v) {
                $res['stages'][$v] = round($total * ($goods['stages_rate'] / 100) + $total / $v, 2);
            }
        }
        //@author-bylu 当点击了数量加减后 重新计算白条分期 每期的价格 end

        $fittings_list = get_goods_fittings(array($goods_id), $warehouse_id, $area_id, $area_city);

        if ($fittings_list) {
            $fittings_attr = $attr_id;
            $goods_fittings = get_goods_fittings_info($goods_id, $warehouse_id, $area_id, $area_city, '', 1, '', $fittings_attr);

            if (is_array($fittings_list)) {
                foreach ($fittings_list as $vo) {
                    $fittings_index[$vo['group_id']] = $vo['group_id']; //关联数组
                }
            }
            ksort($fittings_index); //重新排序

            $merge_fittings = get_merge_fittings_array($fittings_index, $fittings_list); //配件商品重新分组
            $fitts = get_fittings_array_list($merge_fittings, $goods_fittings);

            for ($i = 0; $i < count($fitts); $i++) {
                $fittings_interval = $fitts[$i]['fittings_interval'];

                $res['fittings_interval'][$i]['fittings_minMax'] = price_format($fittings_interval['fittings_min']) . "-" . number_format($fittings_interval['fittings_max'], 2, '.', '');
                $res['fittings_interval'][$i]['market_minMax'] = price_format($fittings_interval['market_min']) . "-" . number_format($fittings_interval['market_max'], 2, '.', '');

                if ($fittings_interval['save_minPrice'] == $fittings_interval['save_maxPrice']) {
                    $res['fittings_interval'][$i]['save_minMaxPrice'] = price_format($fittings_interval['save_minPrice']);
                } else {
                    $res['fittings_interval'][$i]['save_minMaxPrice'] = price_format($fittings_interval['save_minPrice']) . "-" . number_format($fittings_interval['save_maxPrice'], 2, '.', '');
                }

                $res['fittings_interval'][$i]['groupId'] = $fittings_interval['groupId'];
            }
        }


        if ($GLOBALS['_CFG']['open_area_goods'] == 1) {

            $area_list = get_goods_link_area_list($goods_id, $goods['user_id']);
            if ($area_list['goods_area']) {
                if (!in_array($area_id, $area_list['goods_area'])) {
                    $res['err_no'] = 2;
                }
            } else {
                $res['err_no'] = 2;
            }
        }
        //更新商品购买数量
        $start_date = $goods['xiangou_start_date'];
        $end_date = $goods['xiangou_end_date'];
        $order_goods = get_for_purchasing_goods($start_date, $end_date, $goods_id, $user_id, '', $_REQUEST['attr']);
        $res['orderG_number'] = $order_goods['goods_number'];
        $res['onload'] = $onload;
        $limit = 1;
        $area_position_list = get_goods_user_area_position($goods['user_id'], $city_id, $_REQUEST['attr'], $goods_id, 0, 0, 1, 0, $limit);

        if (count($area_position_list) > 0) {
            $res['store_type'] = 1;
        } else {
            $res['store_type'] = 0;
        }
    }

    die($json->encode($res));
}

if (!empty($_REQUEST['act']) && $_REQUEST['act'] == 'in_stock') {

    include('includes/cls_json.php');

    $json = new JSON;

    $res = array('err_msg' => '', 'result' => '', 'qty' => 1);

    clear_cache_files();

    $goods_id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
    $province = empty($_REQUEST['province']) ? 1 : intval($_REQUEST['province']);
    $city = empty($_REQUEST['city']) ? 52 : intval($_REQUEST['city']);
    $district = empty($_REQUEST['district']) ? 500 : intval($_REQUEST['district']);
    $d_null = empty($_REQUEST['d_null']) ? 0 : intval($_REQUEST['d_null']);
    $user_id = empty($_REQUEST['user_id']) ? 0 : intval($_REQUEST['user_id']);

    $user_address = get_user_address_region($user_id);
    $user_address = explode(",", $user_address['region_address']);

    setcookie('province', $province, gmtime() + 3600 * 24 * 30, $GLOBALS['cookie_path'], $GLOBALS['cookie_domain']);
    setcookie('city', $city, gmtime() + 3600 * 24 * 30, $GLOBALS['cookie_path'], $GLOBALS['cookie_domain']);

    setcookie('district', $district, gmtime() + 3600 * 24 * 30, $GLOBALS['cookie_path'], $GLOBALS['cookie_domain']);

    $sql = "SELECT region_id FROM " . $GLOBALS['ecs']->table('region') . " WHERE parent_id = '$district'";
    $street_info = $GLOBALS['db']->getCol($sql);

    $street_list = 0;
    $street_id = 0;

    if ($street_info) {
        $street_id = $street_info[0];
        $street_list = implode(",", $street_info);
    }

    setcookie('street', $street_id, gmtime() + 3600 * 24 * 30, $GLOBALS['cookie_path'], $GLOBALS['cookie_domain']);
    setcookie('street_area', $street_list, gmtime() + 3600 * 24 * 30, $GLOBALS['cookie_path'], $GLOBALS['cookie_domain']);

    $regionId = 0;
    setcookie('regionId', $regionId, gmtime() + 3600 * 24 * 30, $GLOBALS['cookie_path'], $GLOBALS['cookie_domain']);

    //清空
    setcookie('type_province', 0, gmtime() + 3600 * 24 * 30, $GLOBALS['cookie_path'], $GLOBALS['cookie_domain']);
    setcookie('type_city', 0, gmtime() + 3600 * 24 * 30, $GLOBALS['cookie_path'], $GLOBALS['cookie_domain']);
    setcookie('type_district', 0, gmtime() + 3600 * 24 * 30, $GLOBALS['cookie_path'], $GLOBALS['cookie_domain']);

    $res['d_null'] = $d_null;

    if ($d_null == 0) {
        if (in_array($district, $user_address)) {
            $res['isRegion'] = 1;
        } else {
            $res['message'] = $_LANG['Distribution_message'];
            $res['isRegion'] = 88; //原为0
        }
    } else {
        setcookie('district', '', gmtime() + 3600 * 24 * 30, $GLOBALS['cookie_path'], $GLOBALS['cookie_domain']);
    }

    $res['goods_id'] = $goods_id;

    $flow_warehouse = get_warehouse_goods_region($province);
    setcookie('flow_region', $flow_warehouse['region_id'], gmtime() + 3600 * 24 * 30, $GLOBALS['cookie_path'], $GLOBALS['cookie_domain']);

    die($json->encode($res));

}

//ecmoban模板堂 --zhuo start 仓库
if (!empty($_REQUEST['act']) && $_REQUEST['act'] == 'in_warehouse') {

    include('includes/cls_json.php');

    $json = new JSON;
    $res = array('err_msg' => '', 'result' => '', 'qty' => 1);
    $res['warehouse_type'] = !empty($_REQUEST['warehouse_type']) ? $_REQUEST['warehouse_type'] : '';
    clear_cache_files();

    setcookie('region_id', $pid, gmtime() + 3600 * 24 * 30, $GLOBALS['cookie_path'], $GLOBALS['cookie_domain']);
    setcookie('regionId', $pid, gmtime() + 3600 * 24 * 30, $GLOBALS['cookie_path'], $GLOBALS['cookie_domain']);

    $area_region = 0;
    setcookie('area_region', $area_region, gmtime() + 3600 * 24 * 30, $GLOBALS['cookie_path'], $GLOBALS['cookie_domain']);

    $res['goods_id'] = $goods_id;

    $json = new JSON;
    die($json->encode($res));

}

/*------------------------------------------------------ */
//-- 商品购买记录ajax处理
/*------------------------------------------------------ */

if (!empty($_REQUEST['act']) && $_REQUEST['act'] == 'gotopage') {
    include('includes/cls_json.php');

    $json = new JSON;
    $res = array('err_msg' => '', 'result' => '');

    $goods_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    $page = (isset($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;

    if (!empty($goods_id)) {
        $need_cache = $GLOBALS['smarty']->caching;
        $need_compile = $GLOBALS['smarty']->force_compile;

        $GLOBALS['smarty']->caching = false;
        $GLOBALS['smarty']->force_compile = true;

        /* 商品购买记录 */
        $sql = 'SELECT u.user_name, og.goods_number, oi.add_time, IF(oi.order_status IN (2, 3, 4), 0, 1) AS order_status ' .
            'FROM ' . $ecs->table('order_info') . ' AS oi LEFT JOIN ' . $ecs->table('users') . ' AS u ON oi.user_id = u.user_id, ' . $ecs->table('order_goods') . ' AS og ' .
            'WHERE oi.order_id = og.order_id AND ' . gmtime() . ' - oi.add_time < 2592000 AND og.goods_id = ' . $goods_id . ' ORDER BY oi.add_time DESC LIMIT ' . (($page > 1) ? ($page - 1) : 0) * 5 . ',5';
        $bought_notes = $db->getAll($sql);

        foreach ($bought_notes as $key => $val) {
            $bought_notes[$key]['add_time'] = local_date("Y-m-d G:i:s", $val['add_time']);
        }

        $sql = 'SELECT count(*) ' .
            'FROM ' . $ecs->table('order_info') . ' AS oi LEFT JOIN ' . $ecs->table('users') . ' AS u ON oi.user_id = u.user_id, ' . $ecs->table('order_goods') . ' AS og ' .
            'WHERE oi.order_id = og.order_id AND ' . gmtime() . ' - oi.add_time < 2592000 AND og.goods_id = ' . $goods_id;
        $count = $db->getOne($sql);


        /* 商品购买记录分页样式 */
        $pager = array();
        $pager['page'] = $page;
        $pager['size'] = $size = 5;
        $pager['record_count'] = $count;
        $pager['page_count'] = $page_count = ($count > 0) ? intval(ceil($count / $size)) : 1;;
        $pager['page_first'] = "javascript:gotoBuyPage(1,$goods_id)";
        $pager['page_prev'] = $page > 1 ? "javascript:gotoBuyPage(" . ($page - 1) . ",$goods_id)" : 'javascript:;';
        $pager['page_next'] = $page < $page_count ? 'javascript:gotoBuyPage(' . ($page + 1) . ",$goods_id)" : 'javascript:;';
        $pager['page_last'] = $page < $page_count ? 'javascript:gotoBuyPage(' . $page_count . ",$goods_id)" : 'javascript:;';

        $smarty->assign('notes', $bought_notes);
        $smarty->assign('pager', $pager);


        $res['result'] = $GLOBALS['smarty']->fetch('library/bought_notes.lbi');

        $GLOBALS['smarty']->caching = $need_cache;
        $GLOBALS['smarty']->force_compile = $need_compile;
    }

    die($json->encode($res));
}
//@author guan start

/*------------------------------------------------------ */
//-- PROCESSOR
/*------------------------------------------------------ */
$cache_id = $goods_id . '-' . $_SESSION['user_rank'] . '-' . $_CFG['lang'];
$cache_id = sprintf('%X', crc32($cache_id));

//ecmoban模板堂 --zhuo start
if (isset($_COOKIE['region_id']) && !empty($_COOKIE['region_id'])) {
    $region_id = $_COOKIE['region_id'];
}
//ecmoban模板堂 --zhuo end

$goods = get_goods_info($goods_id, $region_id, $area_id, $area_city);

$area = array(
    'region_id' => $region_id, //仓库ID
    'province_id' => $province_id,
    'city_id' => $city_id,
    'district_id' => $district_id,
    'street_id' => $street_id,
    'street_list' => $street_list,
    'goods_id' => $goods_id,
    'user_id' => $user_id,
    'area_id' => $area_id,
    'area_city' => $area_city,
    'merchant_id' => $goods['user_id'],
);

$smarty->assign('area', $area);

if (!$smarty->is_cached('goods.dwt', $cache_id)) {
    if ($goods === false || $goods['is_show'] == 0) {
        /* 如果没有找到任何记录则跳回到首页 */
        ecs_header("Location: ./\n");
        exit;
    } else {
        assign_template('c');

        /* 查看是否秒杀商品 */
        $sec_goods_id = get_is_seckill($goods_id);

        if ($sec_goods_id) {
            $seckill_url = build_uri('seckill', array('act' => "view", 'secid' => $sec_goods_id));
            ecs_header("Location: $seckill_url\n");
            exit;
        }

        /* meta */
        $smarty->assign('keywords', !empty($goods['keywords']) ? htmlspecialchars($goods['keywords']) : htmlspecialchars($_CFG['shop_keywords']));
        $smarty->assign('description', !empty($goods['goods_brief']) ? htmlspecialchars($goods['goods_brief']) : htmlspecialchars($_CFG['shop_desc']));

        $position = assign_ur_here($goods['cat_id'], $goods['goods_name'], array(), '', $goods['user_id']);

        /* current position */
        $smarty->assign('ur_here', $position['ur_here']);                  // 当前位置

        if (defined('THEME_EXTENSION') && $goods['user_id'] == 0) {
            $smarty->assign('see_more_goods', 1);
        } else {
            $smarty->assign('see_more_goods', 0);
        }

        $smarty->assign('image_width', $_CFG['image_width']);
        $smarty->assign('image_height', $_CFG['image_height']);
        $smarty->assign('helps', get_shop_help()); // 网店帮助
        $smarty->assign('id', $goods_id);
        $smarty->assign('type', 0);
        $smarty->assign('cfg', $_CFG);

        $promotion = get_promotion_info($goods_id, $goods['user_id'], $goods);
        $smarty->assign('promotion', $promotion); //促销信息

        $consumption_count = 0;
        if ($goods['consumption']) {
            $consumption_count = 1;
        }

        $promo_count = count($promotion) + $consumption_count;
        $smarty->assign('promo_count', $promo_count); //促销数量

        //ecmoban模板堂 --zhuo start 限购
        $start_date = $goods['xiangou_start_date'];
        $end_date = $goods['xiangou_end_date'];

        $nowTime = gmtime();
        if ($nowTime > $start_date && $nowTime < $end_date) {
            $xiangou = 1;
        } else {
            $xiangou = 0;
        }

        $order_goods = get_for_purchasing_goods($start_date, $end_date, $goods_id, $user_id);
        $smarty->assign('xiangou', $xiangou);
        $smarty->assign('orderG_number', $order_goods['goods_number']);
        //ecmoban模板堂 --zhuo end 限购

        //ecmoban模板堂 --zhuo start
        $shop_info = get_merchants_shop_info('merchants_steps_fields', $goods['user_id']);
        $adress = get_license_comp_adress($shop_info['license_comp_adress']);

        $smarty->assign('shop_info', $shop_info);
        $smarty->assign('adress', $adress);
        //ecmoban模板堂 --zhuo end

        //by wang 获得商品扩展信息
        $goods['goods_extends'] = get_goods_extends($goods_id);

        //判断是否支持退货服务
        $is_return_service = 0;
        if ($goods['return_type']) {
            $fruit1 = array(1, 2, 3); //退货，换货，仅退款
            $intersection = array_intersect($fruit1, $goods['return_type']); //判断商品是否设置退货相关
            if (!empty($intersection)) {
                $is_return_service = 1;
            }
        }
        //判断是否设置包退服务  如果设置了退换货标识，没有设置包退服务  那么修正包退服务为已选择
        if ($is_return_service == 1 && !$goods['goods_extends']['is_return']) {
            $goods['goods_extends']['is_return'] = 1;
        }

        $shop_price = $goods['shop_price'];
        $linked_goods = get_linked_goods($goods_id, $region_id, $area_id, $area_city);

        $goods['goods_style_name'] = add_style($goods['goods_name'], $goods['goods_name_style']);

        //商品标签 liu
        if ($goods['goods_tag']) {
            $goods['goods_tag'] = explode(',', $goods['goods_tag']);
        }

        //@author guan start
        if ($_CFG['two_code']) {

            $goods_weixin_path = ROOT_PATH . IMAGE_DIR . "/weixin_img/";

            if (!file_exists($goods_weixin_path)) {
                make_dir($goods_weixin_path);
            }

            $logo = empty($_CFG['two_code_logo']) ? $goods['goods_img'] : str_replace('../', '', $_CFG['two_code_logo']);

            $size = '200x200';
            $url = $ecs->url();
            $two_code_links = trim($_CFG['two_code_links']);
            $two_code_links = empty($two_code_links) ? $url : $two_code_links;
            $data = $two_code_links . 'goods.php?id=' . $goods['goods_id'];
            $errorCorrectionLevel = 'H'; // 纠错级别：L、M、Q、H
            $matrixPointSize = 4; // 点的大小：1到10
            $filename = IMAGE_DIR . "/weixin_img/weixin_code_" . $goods['goods_id'] . ".png";

            if (!file_exists(ROOT_PATH . $filename)) {

                require(ROOT_PATH . 'includes/phpqrcode/phpqrcode.php'); //by wu

                QRcode::png($data, $filename, $errorCorrectionLevel, $matrixPointSize);

                $QR = imagecreatefrompng($filename);
                if ($logo !== FALSE) {
                    $logo = imagecreatefromstring(file_get_contents($logo));

                    $QR_width = imagesx($QR);
                    $QR_height = imagesy($QR);

                    $logo_width = imagesx($logo);
                    $logo_height = imagesy($logo);

                    // Scale logo to fit in the QR Code
                    $logo_qr_width = $QR_width / 5;
                    $scale = $logo_width / $logo_qr_width;
                    $logo_qr_height = $logo_height / $scale;
                    $from_width = ($QR_width - $logo_qr_width) / 2;
                    //echo $from_width;exit;
                    imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height);
                }

                imagepng($QR, $filename);
                imagedestroy($QR);
            }

            $smarty->assign('weixin_img_url', $filename);
            $smarty->assign('weixin_img_text', trim($_CFG['two_code_mouse']));
            $smarty->assign('two_code', trim($_CFG['two_code']));
        }
        //@author guan end

        /*获取可用门店数量 by kong 20160721*/
        $goods['store_count'] = 0;
        $sql = "SELECT COUNT(*) FROM" . $ecs->table('offline_store') . " AS o LEFT JOIN" . $ecs->table('store_goods') . " AS s ON o.id = s.store_id WHERE s.goods_id = '$goods_id' AND o.is_confirm = 1";
        $store_goods = $db->getOne($sql);

        $sql = "SELECT COUNT(*) FROM" . $ecs->table('offline_store') . " AS o LEFT JOIN" . $ecs->table('store_products') . " AS s ON o.id = s.store_id WHERE s.goods_id = '$goods_id' AND o.is_confirm = 1";
        $store_products = $db->getOne($sql);
        if ($store_goods > 0 || $store_products > 0) {
            $goods['store_count'] = 1;
        }

        $area_position_list = get_goods_user_area_position($goods['user_id'], $city_id);
        $smarty->assign('area_position_list', $area_position_list);

        $smarty->assign('goods', $goods);

        $smarty->assign('goods_name', $goods['goods_name']);
        $smarty->assign('goods_id', $goods['goods_id']);
        $smarty->assign('promote_end_time', $goods['gmt_end_time']);

        if (!defined('THEME_EXTENSION')) {
            $categories_pro = get_category_tree_leve_one();
            $smarty->assign('categories_pro', $categories_pro); // 分类树加强版
        }

        $properties = get_goods_properties($goods_id, $region_id, $area_id, $area_city);  // 获得商品的规格和属性  //ecmoban模板堂 --zhuo

        $smarty->assign('best_goods', get_recommend_goods('best', '', $region_id, $area_id, $area_city, $goods['user_id'], 1));    // 推荐商品
        $smarty->assign('new_goods', get_recommend_goods('new', '', $region_id, $area_id, $area_city, $goods['user_id'], 1));     // 最新商品
        $smarty->assign('hot_goods', get_recommend_goods('hot', '', $region_id, $area_id, $area_city, $goods['user_id'], 1));     // 最新商品

        $smarty->assign('properties', $properties['pro']);                              // 商品规格
        $smarty->assign('specification', $properties['spe']);                              // 商品属性
        $smarty->assign('related_goods', $linked_goods);                                   // 关联商品
        $smarty->assign('goods_article_list', get_linked_articles($goods_id));                  // 关联文章

        $rank_prices = get_user_rank_prices($goods_id, $goods['price']);
        $smarty->assign('rank_prices', $rank_prices);    // 会员等级价格

        $smarty->assign('pictures', get_goods_gallery($goods_id));                    // 商品相册
        $smarty->assign('bought_goods', get_also_bought($goods_id));                      // 购买了该商品的用户还购买了哪些商品
        $smarty->assign('goods_rank', get_goods_rank($goods_id));                       // 商品的销售排名

        /**
         * 店铺分类
         */
        if ($goods['user_id']) {
            $goods_store_cat = get_child_tree_pro(0, 0, 'merchants_category', 0, $goods['user_id']);

            if ($goods_store_cat) {
                $goods_store_cat = array_values($goods_store_cat);
            }

            $smarty->assign('goods_store_cat', $goods_store_cat);
        }

        $group_count = get_group_goods_count($goods_id);
        if ($group_count) {
            //by mike start
            //组合套餐名
            $comboTabIndex = get_cfg_group_goods();
            $smarty->assign('comboTab', $comboTabIndex);
            //组合套餐组
            $fittings_list = get_goods_fittings(array($goods_id), $region_id, $area_id, $area_city);
            if (is_array($fittings_list)) {
                foreach ($fittings_list as $vo) {
                    $fittings_index[$vo['group_id']] = $vo['group_id']; //关联数组
                }
            }
            ksort($fittings_index); //重新排序
            $smarty->assign('fittings_tab_index', $fittings_index); //套餐数量
            //by mike end

            $smarty->assign('fittings', $fittings_list);                   // 配件
        }

        //获取tag
        $tag_array = get_tags($goods_id);
        $smarty->assign('tags', $tag_array);                                       // 商品的标记

        //获取关联礼包
        $package_goods_list = get_package_goods_list($goods['goods_id']);
        $smarty->assign('package_goods_list', $package_goods_list);    // 获取关联礼包

        assign_dynamic('goods');
        $volume_price_list = get_volume_price_list($goods['goods_id'], '1');
        $smarty->assign('volume_price_list', $volume_price_list);    // 商品优惠价格区间

        $discuss_list = get_discuss_all_list($goods_id, 0, 1, 10);
        $smarty->assign('discuss_list', $discuss_list);
        $smarty->assign('all_count', $discuss_list['record_count']);

        //同类其他品牌
        $goods_brand = get_goods_similar_brand($goods['cat_id']);
        $smarty->assign('goods_brand', $goods_brand);

        //相关分类
        $goods_related_cat = get_goods_related_cat($goods['cat_id']);
        $smarty->assign('goods_related_cat', $goods_related_cat);

        //评分 start
        $comment_all = get_comments_percent($goods_id);
        if ($goods['user_id'] > 0) {
            $merchants_goods_comment = get_merchants_goods_comment($goods['user_id']); //商家所有商品评分类型汇总
            $smarty->assign('merch_cmt', $merchants_goods_comment);
        }
        //评分 end 

        $smarty->assign('comment_all', $comment_all);

        $goods_area = 1;
        if ($GLOBALS['_CFG']['open_area_goods'] == 1) {

            $area_list = get_goods_link_area_list($goods_id, $goods['user_id']);
            if ($area_list['goods_area']) {
                if (in_array($area_id, $area_list['goods_area'])) {
                    $goods_area = 1;
                } else {
                    $goods_area = 0;
                }
            } else {
                $goods_area = 0;
            }
        }

        $smarty->assign('goods_area', $goods_area);

        if ($GLOBALS['_CFG']['customer_service'] == 0) {
            $shop_information = get_shop_name($goods['user_id']);
            $smarty->assign('shop_close', $shop_information['shop_close']);
            $goods['user_id'] = 0;
        }

        $basic_info = get_shop_info_content($goods['user_id']);
        /*  @author-bylu 判断当前商家是否允许"在线客服" start */
        $shop_information = get_shop_name($goods['user_id']);
        $shop_information['kf_tel'] = $db->getOne("SELECT kf_tel FROM " . $ecs->table('seller_shopinfo') . "WHERE ru_id = '" . $goods['user_id'] . "'");

        //判断当前商家是平台,还是入驻商家 bylu
        if ($goods['user_id'] == 0) {
            //判断平台是否开启了IM在线客服
            if ($db->getOne("SELECT kf_im_switch FROM " . $ecs->table('seller_shopinfo') . "WHERE ru_id = 0")) {
                $shop_information['is_dsc'] = true;
            } else {
                $shop_information['is_dsc'] = false;
            }
        } else {
            $shop_information['is_dsc'] = false;
        }
        $smarty->assign('shop_information', $shop_information);
        $smarty->assign('kf_appkey', $basic_info['kf_appkey']); //应用appkey;
        $smarty->assign('im_user_id', 'dsc' . $user_id); //登入用户ID;
        /*  @author-bylu  end */

        if ($GLOBALS['_CFG']['customer_service'] == 1) {
            $smarty->assign('shop_close', $shop_information['shop_close']);
        }


        $basic_date = array('region_name');
        $basic_info['province'] = get_table_date('region', "region_id = '" . $basic_info['province'] . "'", $basic_date, 2);
        $basic_info['city'] = get_table_date('region', "region_id= '" . $basic_info['city'] . "'", $basic_date, 2) . "市";

        $smarty->assign('basic_info', $basic_info);

        if ($rank = get_rank_info()) {
            $smarty->assign('rank_name', $rank['rank_name']);
        }

        $smarty->assign('info', get_user_default($user_id));

        //@author-bylu 获取当前商品白条分期数据 start
        if ($goods['stages']) {
            //计算每期价格[默认,当js失效商品详情页才会显示这里的结果](每期价格=((总价+运费)*费率)+((总价+运费)/期数));
            foreach ($goods['stages'] as $k => $v) {
                $stages_arr[$v]['stages_one_price'] = round(($goods['shop_price'] + $shippingFee['shipping_fee']) * ($goods['stages_rate'] / 100) + ($goods['shop_price'] + $shippingFee['shipping_fee']) / $v, 2);
            }
            $smarty->assign('stages', $stages_arr);
        }
        //@author-bylu  end

        //@author-bylu 获取当前商品可使用的优惠券信息 start
        $goods_coupons = get_goods_coupons_list($goods_id);
        $smarty->assign('goods_coupons', $goods_coupons);
        //@author-bylu  end

        $smarty->assign('extend_info', get_goods_extend_info($goods_id)); //扩展信息 by wu

        if (!defined('THEME_EXTENSION')) {
            //商品运费
            $region = array(1, $province_id, $city_id, $district_id, $street_id, $street_list);
            $shippingFee = goodsShippingFee($goods_id, $region_id, $area_id, $area_city, $region);
            $smarty->assign('shippingFee', $shippingFee);
        }

        $smarty->assign('goods_id', $goods_id); //商品ID
        $smarty->assign('region_id', $region_id); //商品仓库region_id 
        $smarty->assign('user_id', $user_id);
        $smarty->assign('area_id', $area_id); //地区ID 

        $site_http = $ecs->http();
        if ($site_http == 'http://') {
            $is_http = 1;
        } elseif ($site_http == 'https://') {
            $is_http = 2;
        } else {
            $is_http = 0;
        }

        $smarty->assign('url', $ecs->url());
        $smarty->assign('is_http', $is_http);
    }
}

/* 记录浏览历史 */
if (!empty($_COOKIE['ECS']['history'])) {
    $history = explode(',', $_COOKIE['ECS']['history']);

    array_unshift($history, $goods_id);
    $history = array_unique($history);

    while (count($history) > $_CFG['history_number']) {
        array_pop($history);
    }

    setcookie('ECS[history]', implode(',', $history), gmtime() + 3600 * 24 * 30, $GLOBALS['cookie_path'], $GLOBALS['cookie_domain']);
} else {
    setcookie('ECS[history]', $goods_id, gmtime() + 3600 * 24 * 30, $GLOBALS['cookie_path'], $GLOBALS['cookie_domain']);
}

/* 更新点击次数 */
$db->query('UPDATE ' . $ecs->table('goods') . " SET click_count = click_count + 1 WHERE goods_id = '$goods_id'");

/* 记录浏览历史 ecmoban模板堂 --zhuo start 浏览列表插件*/
if (!empty($_COOKIE['ECS']['list_history'])) {
    $list_history = explode(',', $_COOKIE['ECS']['list_history']);

    array_unshift($list_history, $goods_id);
    $list_history = array_unique($list_history);

    while (count($list_history) > 100000) {
        array_pop($list_history);
    }

    setcookie('ECS[list_history]', implode(',', $list_history), gmtime() + 3600 * 24 * 30, $GLOBALS['cookie_path'], $GLOBALS['cookie_domain']);
} else {
    setcookie('ECS[list_history]', $goods_id, gmtime() + 3600 * 24 * 30, $GLOBALS['cookie_path'], $GLOBALS['cookie_domain']);
}
/* 记录浏览历史 ecmoban模板堂 --zhuo end 浏览列表插件*/

//ecmoban模板堂 --zhuo 仓库 start
$smarty->assign('area_htmlType', 'goods');

$_SESSION['goods_equal'] = '';
$db->query('DELETE FROM ' . $ecs->table('cart_combo') . " WHERE (parent_id = 0 and goods_id = '$goods_id' or parent_id = '$goods_id') AND " . $sess_id);
//ecmoban模板堂 --zhuo 仓库 end

$smarty->assign('freight_model', $GLOBALS['_CFG']['freight_model']); //ecmoban模板堂 --zhuo 运费模式
$smarty->assign('one_step_buy', $GLOBALS['_CFG']['one_step_buy']); //ecmoban模板堂 --zhuo 一步购物
$smarty->assign('now_time', gmtime());           // 当前系统时间


//获取seo start
$seo = get_seo_words('goods');

foreach ($seo as $key => $value) {
    $seo[$key] = str_replace(array('{sitename}', '{key}', '{name}', '{description}'), array($_CFG['shop_name'], $goods['keywords'], $goods['goods_name'], $goods['goods_brief']), $value);
}


if (!empty($seo['keywords'])) {
    $smarty->assign('keywords', htmlspecialchars($seo['keywords']));
} else {
    $smarty->assign('keywords', htmlspecialchars($_CFG['shop_keywords']));
}

if (!empty($seo['description'])) {
    $smarty->assign('description', htmlspecialchars($seo['description']));
} else {
    $smarty->assign('description', htmlspecialchars($_CFG['shop_desc']));
}

if (!empty($seo['title'])) {
    $smarty->assign('page_title', htmlspecialchars($seo['title']));
} else {
    $smarty->assign('page_title', $position['title']);
}
//获取seo end

$smarty->display('goods.dwt', $cache_id);

/*------------------------------------------------------ */
//-- PRIVATE FUNCTION
/*------------------------------------------------------ */

/**
 * 获得指定商品的关联商品
 *
 * @access  public
 * @param   integer $goods_id
 * @return  array
 */
function get_linked_goods($goods_id, $warehouse_id = 0, $area_id = 0, $area_city = 0)
{
    //ecmoban模板堂 --zhuo start
    $where = '';
    $leftJoin = '';
    $where_area = '';
    if ($GLOBALS['_CFG']['area_pricetype'] == 1) {
        $where_area = " AND wag.city_id = '$area_city'";
    }

    $shop_price = "wg.warehouse_price, wg.warehouse_promote_price, wag.region_price, wag.region_promote_price, g.model_price, g.model_attr, ";
    $leftJoin .= " left join " . $GLOBALS['ecs']->table('warehouse_goods') . " as wg on g.goods_id = wg.goods_id and wg.region_id = '$warehouse_id' ";
    $leftJoin .= " left join " . $GLOBALS['ecs']->table('warehouse_area_goods') . " as wag on g.goods_id = wag.goods_id and wag.region_id = '$area_id' $where_area ";

    if ($GLOBALS['_CFG']['open_area_goods'] == 1) {
        $leftJoin .= " left join " . $GLOBALS['ecs']->table('link_area_goods') . " as lag on g.goods_id = lag.goods_id ";
        $where .= " and lag.region_id = '$area_id' ";
    }
    //ecmoban模板堂 --zhuo end	

    $sql = 'SELECT g.goods_id, g.goods_name, g.goods_thumb, g.goods_img, IF(g.model_price < 1, g.shop_price, IF(g.model_price < 2, wg.warehouse_price, wag.region_price)) AS org_price, ' .
        "IFNULL(IFNULL(mp.user_price, IF(g.model_price < 1, g.shop_price, IF(g.model_price < 2, wg.warehouse_price, wag.region_price)) * '$_SESSION[discount]'), g.shop_price * '$_SESSION[discount]')  AS shop_price, " .
        'IFNULL(IF(g.model_price < 1, g.promote_price, IF(g.model_price < 2, wg.warehouse_promote_price, wag.region_promote_price)), g.promote_price) AS promote_price, ' .
        'g.market_price, g.sales_volume, g.model_attr, g.promote_start_date, g.promote_end_date, g.product_price, g.product_promote_price ' .
        'FROM ' . $GLOBALS['ecs']->table('link_goods') . ' lg ' .
        'LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . ' AS g ON g.goods_id = lg.link_goods_id ' .
        "LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp " .
        "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " .
        $leftJoin .
        "WHERE lg.goods_id = '$goods_id' AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 " .
        $where .
        "LIMIT " . $GLOBALS['_CFG']['related_goods_number'];
    $res = $GLOBALS['db']->query($sql);

    $arr = array();
    while ($row = $GLOBALS['db']->fetchRow($res)) {
        if ($row['promote_price'] > 0) {
            $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
        } else {
            $promote_price = 0;
        }

        $arr[$row['goods_id']]['goods_id'] = $row['goods_id'];
        $arr[$row['goods_id']]['goods_name'] = $row['goods_name'];
        $arr[$row['goods_id']]['short_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
            sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
        $arr[$row['goods_id']]['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $arr[$row['goods_id']]['goods_img'] = get_image_path($row['goods_id'], $row['goods_img']);
        $arr[$row['goods_id']]['market_price'] = price_format($row['market_price']);
        $arr[$row['goods_id']]['shop_price'] = price_format($row['shop_price']);
        $arr[$row['goods_id']]['promote_price'] = ($promote_price > 0) ? price_format($promote_price) : '';
        $arr[$row['goods_id']]['url'] = build_uri('goods', array('gid' => $row['goods_id']), $row['goods_name']);
        $arr[$row['goods_id']]['sales_volume'] = $row['sales_volume'];
    }

    return $arr;
}

/**
 * 获得指定商品的关联文章
 *
 * @access  public
 * @param   integer $goods_id
 * @return  void
 */
function get_linked_articles($goods_id)
{
    $sql = 'SELECT a.article_id, a.title, a.file_url, a.open_type, a.add_time ' .
        'FROM ' . $GLOBALS['ecs']->table('goods_article') . ' AS g, ' .
        $GLOBALS['ecs']->table('article') . ' AS a ' .
        "WHERE g.article_id = a.article_id AND g.goods_id = '$goods_id' AND a.is_open = 1 " .
        'ORDER BY a.add_time DESC';
    $res = $GLOBALS['db']->query($sql);

    $arr = array();
    while ($row = $GLOBALS['db']->fetchRow($res)) {
        $row['url'] = $row['open_type'] != 1 ?
            build_uri('article', array('aid' => $row['article_id']), $row['title']) : trim($row['file_url']);
        $row['add_time'] = local_date($GLOBALS['_CFG']['date_format'], $row['add_time']);
        $row['short_title'] = $GLOBALS['_CFG']['article_title_length'] > 0 ?
            sub_str($row['title'], $GLOBALS['_CFG']['article_title_length']) : $row['title'];

        $arr[] = $row;
    }

    return $arr;
}

/**
 * 获得指定商品的各会员等级对应的价格
 *
 * @access  public
 * @param   integer $goods_id
 * @return  array
 */
function get_user_rank_prices($goods_id, $shop_price)
{
    if (empty($shop_price)) {
        $shop_price = 0;
    }
    $sql = "SELECT rank_id, IFNULL(mp.user_price, r.discount * $shop_price / 100) AS price, r.rank_name, r.discount " .
        'FROM ' . $GLOBALS['ecs']->table('user_rank') . ' AS r ' .
        'LEFT JOIN ' . $GLOBALS['ecs']->table('member_price') . " AS mp " .
        "ON mp.goods_id = '$goods_id' AND mp.user_rank = r.rank_id " .
        "WHERE r.show_price = 1 OR r.rank_id = '$_SESSION[user_rank]'";
    $res = $GLOBALS['db']->query($sql);

    $arr = array();
    while ($row = $GLOBALS['db']->fetchRow($res)) {

        $arr[$row['rank_id']] = array(
            'rank_name' => htmlspecialchars($row['rank_name']),
            'price' => price_format($row['price']));
    }

    return $arr;
}

/**
 * 获得购买过该商品的人还买过的商品
 *
 * @access  public
 * @param   integer $goods_id
 * @return  array
 */
function get_also_bought($goods_id)
{
    $sql = 'SELECT COUNT(b.goods_id ) AS num, g.goods_id, g.goods_name, g.goods_thumb, g.goods_img, g.shop_price, g.promote_price, g.promote_start_date, g.promote_end_date ' .
        'FROM ' . $GLOBALS['ecs']->table('order_goods') . ' AS a ' .
        'LEFT JOIN ' . $GLOBALS['ecs']->table('order_goods') . ' AS b ON b.order_id = a.order_id ' .
        'LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . ' AS g ON g.goods_id = b.goods_id ' .
        "WHERE a.goods_id = '$goods_id' AND b.goods_id <> '$goods_id' AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 " .
        'GROUP BY b.goods_id ' .
        'ORDER BY num DESC ' .
        'LIMIT ' . $GLOBALS['_CFG']['bought_goods'];
    $res = $GLOBALS['db']->query($sql);

    $key = 0;
    $arr = array();
    while ($row = $GLOBALS['db']->fetchRow($res)) {
        $arr[$key]['goods_id'] = $row['goods_id'];
        $arr[$key]['goods_name'] = $row['goods_name'];
        $arr[$key]['short_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
            sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
        $arr[$key]['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $arr[$key]['goods_img'] = get_image_path($row['goods_id'], $row['goods_img']);
        $arr[$key]['shop_price'] = price_format($row['shop_price']);
        $arr[$key]['url'] = build_uri('goods', array('gid' => $row['goods_id']), $row['goods_name']);

        if ($row['promote_price'] > 0) {
            $arr[$key]['promote_price'] = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            $arr[$key]['formated_promote_price'] = price_format($arr[$key]['promote_price']);
        } else {
            $arr[$key]['promote_price'] = 0;
        }

        $key++;
    }

    return $arr;
}

/**
 * 获得指定商品的销售排名
 *
 * @access  public
 * @param   integer $goods_id
 * @return  integer
 */
function get_goods_rank($goods_id)
{
    /* 统计时间段 */
    $period = intval($GLOBALS['_CFG']['top10_time']);
    if ($period == 1) // 一年
    {
        $ext = " AND o.add_time > '" . local_strtotime('-1 years') . "'";
    } elseif ($period == 2) // 半年
    {
        $ext = " AND o.add_time > '" . local_strtotime('-6 months') . "'";
    } elseif ($period == 3) // 三个月
    {
        $ext = " AND o.add_time > '" . local_strtotime('-3 months') . "'";
    } elseif ($period == 4) // 一个月
    {
        $ext = " AND o.add_time > '" . local_strtotime('-1 months') . "'";
    } else {
        $ext = '';
    }

    /* 查询该商品销量 */
    $sql = 'SELECT IFNULL(SUM(g.goods_number), 0) ' .
        'FROM ' . $GLOBALS['ecs']->table('order_info') . ' AS o, ' .
        $GLOBALS['ecs']->table('order_goods') . ' AS g ' .
        "WHERE o.order_id = g.order_id " .
        "AND o.order_status = '" . OS_CONFIRMED . "' " .
        "AND o.shipping_status " . db_create_in(array(SS_SHIPPED, SS_RECEIVED)) .
        " AND o.pay_status " . db_create_in(array(PS_PAYED, PS_PAYING)) .
        " AND g.goods_id = '$goods_id'" . $ext;
    $sales_count = $GLOBALS['db']->getOne($sql);

    if ($sales_count > 0) {
        /* 只有在商品销售量大于0时才去计算该商品的排行 */
        $sql = 'SELECT DISTINCT SUM(goods_number) AS num ' .
            'FROM ' . $GLOBALS['ecs']->table('order_info') . ' AS o, ' .
            $GLOBALS['ecs']->table('order_goods') . ' AS g ' .
            "WHERE o.order_id = g.order_id " .
            "AND o.order_status = '" . OS_CONFIRMED . "' " .
            "AND o.shipping_status " . db_create_in(array(SS_SHIPPED, SS_RECEIVED)) .
            " AND o.pay_status " . db_create_in(array(PS_PAYED, PS_PAYING)) . $ext .
            " GROUP BY g.goods_id HAVING num > $sales_count";
        $res = $GLOBALS['db']->query($sql);

        $rank = $GLOBALS['db']->num_rows($res) + 1;

        if ($rank > 10) {
            $rank = 0;
        }
    } else {
        $rank = 0;
    }

    return $rank;
}

/**
 * 获得商品选定的属性的附加总价格
 *
 * @param   integer $goods_id
 * @param   array $attr
 *
 * @return  void
 */
function get_attr_amount($goods_id, $attr)
{
    $sql = "SELECT SUM(attr_price) FROM " . $GLOBALS['ecs']->table('goods_attr') .
        " WHERE goods_id='$goods_id' AND " . db_create_in($attr, 'goods_attr_id');

    return $GLOBALS['db']->getOne($sql);
}

/**
 * 取得跟商品关联的礼包列表
 *
 * @param   string $goods_id 商品编号
 *
 * @return  礼包列表
 */
function get_package_goods_list($goods_id)
{
    $now = gmtime();
    $sql = "SELECT pg.goods_id, ga.act_id, ga.act_name, ga.act_desc, ga.goods_name, ga.start_time,
                   ga.end_time, ga.is_finished, ga.ext_info
            FROM " . $GLOBALS['ecs']->table('goods_activity') . " AS ga, " . $GLOBALS['ecs']->table('package_goods') . " AS pg
            WHERE pg.package_id = ga.act_id AND ga.review_status = 3
            AND ga.start_time <= '" . $now . "'
            AND ga.end_time >= '" . $now . "'
            AND pg.goods_id = '" . $goods_id . "'" . "
            GROUP BY ga.act_id
            ORDER BY ga.act_id ";
    $res = $GLOBALS['db']->getAll($sql);

    foreach ($res as $tempkey => $value) {
        $subtotal = 0;
        $row = unserialize($value['ext_info']);
        unset($value['ext_info']);
        if ($row) {
            foreach ($row as $key => $val) {
                $res[$tempkey][$key] = $val;
            }
        }

        $sql = "SELECT pg.package_id, pg.goods_id, pg.goods_number, pg.admin_id, p.goods_attr, g.goods_sn, g.goods_name, g.market_price, g.goods_thumb, IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS rank_price
                FROM " . $GLOBALS['ecs']->table('package_goods') . " AS pg
                    LEFT JOIN " . $GLOBALS['ecs']->table('goods') . " AS g
                        ON g.goods_id = pg.goods_id
                    LEFT JOIN " . $GLOBALS['ecs']->table('products') . " AS p
                        ON p.product_id = pg.product_id
                    LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp
                        ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]'
                WHERE pg.package_id = " . $value['act_id'] . "
                ORDER BY pg.package_id, pg.goods_id";

        $goods_res = $GLOBALS['db']->getAll($sql);

        foreach ($goods_res as $key => $val) {
            $goods_id_array[] = $val['goods_id'];
            $goods_res[$key]['goods_thumb'] = get_image_path($val['goods_id'], $val['goods_thumb'], true);
            $goods_res[$key]['market_price'] = price_format($val['market_price']);
            $goods_res[$key]['rank_price'] = price_format($val['rank_price']);
            $subtotal += $val['rank_price'] * $val['goods_number'];
        }

        /* 取商品属性 */
        $sql = "SELECT ga.goods_attr_id, ga.attr_value
                FROM " . $GLOBALS['ecs']->table('goods_attr') . " AS ga, " . $GLOBALS['ecs']->table('attribute') . " AS a
                WHERE a.attr_id = ga.attr_id
                AND a.attr_type = 1
                AND " . db_create_in($goods_id_array, 'goods_id') . " ORDER BY a.sort_order, a.attr_id, ga.goods_attr_id";
        $result_goods_attr = $GLOBALS['db']->getAll($sql);

        $_goods_attr = array();
        foreach ($result_goods_attr as $value) {
            $_goods_attr[$value['goods_attr_id']] = $value['attr_value'];
        }

        /* 处理货品 */
        $format = '[%s]';
        foreach ($goods_res as $key => $val) {
            if ($val['goods_attr'] != '') {
                $goods_attr_array = explode('|', $val['goods_attr']);

                $goods_attr = array();
                foreach ($goods_attr_array as $_attr) {
                    $goods_attr[] = $_goods_attr[$_attr];
                }

                $goods_res[$key]['goods_attr_str'] = sprintf($format, implode('，', $goods_attr));
            }
        }

        $res[$tempkey]['goods_list'] = $goods_res;
        $res[$tempkey]['subtotal'] = price_format($subtotal);
        $res[$tempkey]['saving'] = price_format(($subtotal - $res[$tempkey]['package_price']));
        $res[$tempkey]['package_price'] = price_format($res[$tempkey]['package_price']);
    }

    return $res;
}

/*
 * 相关分类
 */
function get_goods_related_cat($cat_id)
{
    $sql = "SELECT parent_id FROM " . $GLOBALS['ecs']->table('category') . " WHERE cat_id = '$cat_id'";
    $res = $GLOBALS['db']->getOne($sql, true);

    $sql = "SELECT cat_id, cat_name FROM " . $GLOBALS['ecs']->table('category') . " WHERE parent_id = '" . $res . "'";
    $res = $GLOBALS['db']->getAll($sql);

    foreach ($res as $key => $row) {
        $res[$key]['cat_id'] = $row['cat_id'];
        $res[$key]['cat_name'] = $row['cat_name'];
        $res[$key]['url'] = build_uri('category', array('cid' => $row['cat_id']), $row['cat_name']);
    }

    return $res;
}

/*
 * 同类其他品牌
 */
function get_goods_similar_brand($cat_id = 0)
{

    $sql = "SELECT GROUP_CONCAT(brand_id) AS brand_id FROM " . $GLOBALS['ecs']->table('goods') . " WHERE cat_id = '$cat_id'";
    $brand_id = $GLOBALS['db']->getOne($sql, true);

    if ($brand_id) {
        $brand_id = explode(',', $brand_id);
        $brand_id = array_unique($brand_id);
        $brand_id = implode(',', $brand_id);

        $sql = "SELECT brand_id, brand_name FROM " . $GLOBALS['ecs']->table('brand') . " WHERE brand_id " . db_create_in($brand_id) . " AND is_show = 1";
        $brand = $GLOBALS['db']->getAll($sql);

        foreach ($brand as $key => $row) {
            $brand[$key]['url'] = build_uri('brand', array('bid' => $row['brand_id']), $row['brand_name']);
        }
    }

    return $brand;
}

/**
 * 获取商品ajax属性是否都选中
 */
function get_goods_attr_ajax($goods_id, $goods_attr, $goods_attr_id)
{

    $arr = array();
    $arr['attr_id'] = '';
    $where = "";
    if ($goods_attr) {

        $goods_attr = implode(",", $goods_attr);
        $where .= " AND ga.attr_id IN($goods_attr)";

        if ($goods_attr_id) {
            $goods_attr_id = implode(",", $goods_attr_id);
            $where .= " AND ga.goods_attr_id IN($goods_attr_id)";
        }

        $sql = "SELECT ga.goods_attr_id, ga.attr_id, ga.attr_value  FROM " . $GLOBALS['ecs']->table('goods_attr') . " AS ga" .
            " LEFT JOIN " . $GLOBALS['ecs']->table('attribute') . " AS a ON ga.attr_id = a.attr_id " .
            " WHERE  ga.goods_id = '$goods_id' $where AND a.attr_type > 0 ORDER BY a.sort_order, a.attr_id, ga.goods_attr_id";
        $res = $GLOBALS['db']->getAll($sql);

        foreach ($res as $key => $row) {
            $arr[$row['attr_id']][$row['goods_attr_id']] = $row;

            $arr['attr_id'] .= $row['attr_id'] . ",";
        }

        if ($arr['attr_id']) {
            $arr['attr_id'] = substr($arr['attr_id'], 0, -1);
            $arr['attr_id'] = explode(",", $arr['attr_id']);
        } else {
            $arr['attr_id'] = array();
        }
    }

    return $arr;
}

/**
 * 查看是否秒杀
 */
function get_is_seckill($goods_id = 0)
{
    $date_begin = local_strtotime(local_date('Ymd'));
    $stb_time = local_date('H:i:s');

    $sql = "SELECT sg.id AS sec_goods_id FROM " . $GLOBALS['ecs']->table('seckill_goods') . ' AS sg ' .
        "LEFT JOIN " . $GLOBALS['ecs']->table('seckill_time_bucket') . ' AS stb ON sg.tb_id = stb.id ' .
        "LEFT JOIN " . $GLOBALS['ecs']->table('seckill') . ' AS s ON s.sec_id = sg.sec_id ' .
        " WHERE goods_id = '$goods_id' AND s.begin_time <= '$date_begin' AND s.acti_time > '$date_begin' AND s.is_putaway = 1 " .
        " AND s.review_status = 3 AND stb.begin_time <= '$stb_time' AND stb.end_time > '$stb_time' ORDER BY stb.begin_time ASC";
    $sec_goods_id = $GLOBALS['db']->getOne($sql, true);

    return $sec_goods_id;
}

?>