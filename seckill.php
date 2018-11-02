<?php

/**
 * ECSHOP 秒杀商品前台文件
 * ============================================================================
 * * 版权所有 2005-2017 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liu $
 * $Id: group_buy.php 17217 2017-03-13 09:29:08Z liu $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

//ecmoban模板堂 --zhuo start
require(ROOT_PATH . 'includes/lib_area.php');  //ecmoban模板堂 --zhuo
$warehouse_other = [
    'province_id' => $province_id,
    'city_id' => $city_id
];
$warehouse_area_info = get_warehouse_area_info($warehouse_other);

$region_id = $warehouse_area_info['region_id'];
$area_id = $warehouse_area_info['area_id'];
$area_city = $warehouse_area_info['city_id'];

$keywords   = !empty($_REQUEST['keywords'])   ? htmlspecialchars(trim($_REQUEST['keywords'])):'';

if(isset($_REQUEST['keywords'])){
    clear_all_files();
}

if ((DEBUG_MODE & 2) != 2)
{
    $smarty->caching = true;
}

$user_id = isset($_SESSION['user_id'])? $_SESSION['user_id'] : 0;
$cat_id = isset($_REQUEST['cat_id']) && !empty($_REQUEST['cat_id']) ? intval($_REQUEST['cat_id']) : 0;

/*------------------------------------------------------ */
//-- act 操作项的初始化
/*------------------------------------------------------ */
$template = "seckill_list";
if (empty($_REQUEST['act']))
{
    if(defined('THEME_EXTENSION')){
        $template = "seckill";
    }
    $_REQUEST['act'] = 'list';
}

/*------------------------------------------------------ */
//-- 秒杀商品 --> 秒杀活动商品列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list') {
    
    assign_template();
    $position = assign_ur_here('seckill');
    $smarty->assign('page_title', $position['title']);    // 页面标题
    $smarty->assign('ur_here', $position['ur_here']);  // 当前位置
    $smarty->assign('helps', get_shop_help());       // 网店帮助	
    $categories_pro = get_top_category_tree();
    $smarty->assign('categories_pro', $categories_pro);

    $seckill_list = seckill_goods_list();
    foreach ($seckill_list as $k => $v) {
        $seckill_list[$k]['begin_time_formated'] = local_date('Y-m-d H:i:s', $v['begin_time']);
        $seckill_list[$k]['end_time_formated'] = local_date('Y-m-d H:i:s', $v['end_time']);
        if ($v['status'] == true) {
            $guess_goods = $v['goods'];
        }
        if ($v['soon'] == true) {
            $will_begin = $v['goods'];
        }
    }

    if ($guess_goods) {//更多好货根据商品销量排序
        foreach ($guess_goods AS $uniqid => $row) {
            foreach ($row AS $key => $value) {
                $arrSort[$key][$uniqid] = $value;
            }
        }
        array_multisort($arrSort['percent'], SORT_DESC, $guess_goods);
    }

    $smarty->assign('seckill_list', $seckill_list);

    if ($cat_id) {
        $cat_info = get_cat_info($cat_id, array('cat_alias_name'));
        $smarty->assign('cat_alias_name', $cat_info['cat_alias_name']);
        $smarty->assign('will_begin', $will_begin);
        $smarty->display('seckill_cat_list.dwt');
    } else {
        //广告
        for ($i = 1; $i <= $_CFG['auction_ad']; $i++) {
            $seckill_top_ad .= "'seckill_top_ad" . $i . ","; //秒杀列表页面广告
        }

        $smarty->assign('seckill_top_ad', $seckill_top_ad); //liu
        $smarty->assign('guess_goods', $guess_goods);
        $smarty->display('seckill_list.dwt');
    }
}

/*------------------------------------------------------ */
//-- 秒杀商品 --> 商品详情
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'view')
{
    /* 取得参数：秒杀活动ID */
    $seckill_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    if ($seckill_id <= 0) {
        ecs_header("Location: ./\n");
        exit;
    }
    
    assign_template();

    /* 取得秒杀活动信息 */
    $seckill = seckill_info($seckill_id);
    $goods_id = $seckill['goods_id'];
    $goods_info = goods_info($goods_id); //通过商品ID获取到ru_id;
    
    //秒杀结束直接跳到普通商品购买
    if($seckill['is_end'] && !$seckill['status']){
        $Location = build_uri('goods', array('gid'=>$seckill['goods_id']), $seckill['goods_name']);
        ecs_header("Location: $Location\n");
    }

    if (!$seckill) {
        show_message($_LANG['now_not_snatch']);
    }
    
    $ua = strtolower($_SERVER['HTTP_USER_AGENT']);

    $uachar = "/(nokia|sony|ericsson|mot|samsung|sgh|lg|philips|panasonic|alcatel|lenovo|cldc|midp|mobile)/i";

    if (($ua == '' || preg_match($uachar, $ua)) && !strpos(strtolower($_SERVER['REQUEST_URI']), 'wap')) {
        $Loaction = 'mobile/index.php?m=seckill&a=detail&id=' .$seckill_id. '&tmr=0';

        if (!empty($Loaction)) {
            ecs_header("Location: $Loaction\n");

            exit;
        }
    }

    $first_month_day = local_mktime(0, 0, 0, date('m'), 1, date('Y')); //本月第一天
    $last_month_day = local_mktime(0, 0, 0, date('m'), date('t'), date('Y')) + 24 * 60 * 60 - 1; //本月最后一天

    $start_date = local_strtotime($seckill['begin_time']);
    $end_date = local_strtotime($seckill['end_time']);
    
    $position = assign_ur_here($seckill['cat_id'], $seckill['goods_name'], array(), '', $seckill['user_id']);
    $properties = get_goods_properties($goods_id, $region_id, $area_id, $area_city);  // 获得商品的规格和属性
    $comment_all = get_comments_percent($goods_id);
    $order_goods = get_for_purchasing_goods($start_date, $end_date, $goods_id, $_SESSION['user_id'], 'seckill');

    $smarty->assign('look_top', get_top_seckill_goods('click_count'));

    if ($area_id == NULL) {
        $area_id = 0;
    }

    $sql = "select province, city, kf_type, kf_ww, kf_qq, shop_name from " . $ecs->table('seller_shopinfo') . " where ru_id='" . $goods_info['user_id'] . "'";
    $basic_info = $db->getRow($sql);

    $basic_date = array('region_name');
    $basic_info['province'] = get_table_date('region', "region_id = '" . $basic_info['province'] . "'", $basic_date, 2);
    $basic_info['city'] = get_table_date('region', "region_id= '" . $basic_info['city'] . "'", $basic_date, 2) . "市";

    /* 处理客服旺旺数组 by kong */
    if ($basic_info['kf_ww']) {
        $kf_ww = array_filter(preg_split('/\s+/', $basic_info['kf_ww']));
        $kf_ww = explode("|", $kf_ww[0]);
        if (!empty($kf_ww[1])) {
            $basic_info['kf_ww'] = $kf_ww[1];
        } else {
            $basic_info['kf_ww'] = "";
        }
    } else {
        $basic_info['kf_ww'] = "";
    }
    /* 处理客服QQ数组 by kong */
    if ($basic_info['kf_qq']) {
        $kf_qq = array_filter(preg_split('/\s+/', $basic_info['kf_qq']));
        $kf_qq = explode("|", $kf_qq[0]);
        if (!empty($kf_qq[1])) {
            $basic_info['kf_qq'] = $kf_qq[1];
        } else {
            $basic_info['kf_qq'] = "";
        }
    } else {
        $basic_info['kf_qq'] = "";
    }

    $merchant_seckill = get_merchant_seckill_goods($seckill['id'], $goods_info['user_id']);
    $smarty->assign('merchant_seckill_goods', $merchant_seckill);

    /* 判断当前商家是否允许"在线客服" begin  */
    if ($GLOBALS['_CFG']['customer_service'] == 0) {
        $goods_info['user_id'] = 0;
    }
    $shop_information = get_shop_name($goods_info['user_id']); //通过ru_id获取到店铺信息;
    //判断当前商家是平台,还是入驻商家 
    if ($goods_info['user_id'] == 0) {
        //判断平台是否开启了IM在线客服
        if ($db->getOne("SELECT kf_im_switch FROM " . $ecs->table('seller_shopinfo') . "WHERE ru_id = 0")) {
            $shop_information['is_dsc'] = true;
        } else {
            $shop_information['is_dsc'] = false;
        }
    } else {
        $shop_information['is_dsc'] = false;
    }
    $shop_information['goods_id'] = $goods_id;
    $smarty->assign('shop_information', $shop_information);
    /* end */

    $area = array(
        'region_id' => $region_id, //仓库ID
        'province_id' => $province_id,
        'city_id' => $city_id,
        'district_id' => $district_id,
        'goods_id' => $goods_id,
        'user_id' => $user_id,
        'area_id' => $area_id,
		'area_city' => $area_city,
        'merchant_id' => $seckill['user_id'],
    );

    $properties = get_goods_properties($goods_id, $region_id, $area_id, $area_city);  // 获得商品的规格和属性  //ecmoban模板堂 --zhuo	
    $smarty->assign('cfg', $_CFG);                // 模板赋值
    $smarty->assign('properties', $properties['pro']);                              // 商品属性
    $smarty->assign('specification', $properties['spe']);                              // 商品规格

    //商品运费
    $region = array(1, $province_id, $city_id, $district_id);
    $shippingFee = goodsShippingFee($goods_id, $region_id, $area_id, $area_city, $region, $seckill['sec_price']);
    $smarty->assign('shippingFee', $shippingFee);

    $smarty->assign('ur_here', $position['ur_here']);  // 当前位置
    
    if (defined('THEME_EXTENSION')) {
        $categories_pro = get_category_tree_leve_one();
        $smarty->assign('categories_pro', $categories_pro); // 分类树加强版
    }
    
    /* 记录秒杀浏览历史 */
    if (!empty($_COOKIE['ECS']['sec_history']))
    {
        $sec_history = explode(',', $_COOKIE['ECS']['sec_history']);

        array_unshift($sec_history, $seckill_id);
        $sec_history = array_unique($sec_history);

        while (count($sec_history) > $_CFG['history_number'])
        {
            array_pop($sec_history);
        }

        setcookie('ECS[sec_history]', implode(',', $sec_history), gmtime() + 3600 * 24 * 30, $GLOBALS['cookie_path'], $GLOBALS['cookie_domain']);
    }
    else
    {
        setcookie('ECS[sec_history]', $seckill_id, gmtime() + 3600 * 24 * 30, $GLOBALS['cookie_path'], $GLOBALS['cookie_domain']);
    }

    //ecmoban模板堂 --zhuo start
    $shop_info = get_merchants_shop_info('merchants_steps_fields', $goods_info['user_id']);
    $adress = get_license_comp_adress($shop_info['license_comp_adress']);
    
    $smarty->assign('shop_info', $shop_info);
    $smarty->assign('adress', $adress);
    //ecmoban模板堂 --zhuo end
	
    $smarty->assign('id', $seckill['goods_id']);
    $smarty->assign('area', $area);
    $smarty->assign('orderG_number', $order_goods['goods_number']);
    $smarty->assign('comment_all', $comment_all);
    $smarty->assign('properties', $properties['pro']);                              // 商品属性	
    $smarty->assign('goods', $seckill);
    $smarty->assign('page_title', $position['title']);    // 页面标题
    $smarty->assign('pictures', get_goods_gallery($goods_id));  // 商品相册
    $smarty->assign('comment_percent', comment_percent($goods_id));
    $smarty->assign('basic_info', $basic_info);
    $smarty->assign('region_id', $region_id);
    $smarty->assign('area_id', $area_id);
    $smarty->assign('extend_info', get_goods_extend_info($goods_id)); //扩展信息 by wu
    $smarty->assign('helps', get_shop_help());       // 网店帮助
    $smarty->display('seckill_goods.dwt');
}

/*------------------------------------------------------ */
//-- 价格
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'price')
{
    include('includes/cls_json.php');

    $json   = new JSON;
    $res    = array('err_msg' => '', 'err_no' => 0, 'result' => '', 'qty' => 1);

    $goods_id     = (isset($_REQUEST['id'])) ? intval($_REQUEST['id']) : 0; //仓库管理的地区ID
    $attr_id    = isset($_REQUEST['attr']) ? explode(',', $_REQUEST['attr']) : array();
    $number     = (isset($_REQUEST['number'])) ? intval($_REQUEST['number']) : 1;
    $warehouse_id     = (isset($_REQUEST['warehouse_id'])) ? intval($_REQUEST['warehouse_id']) : 0;
    $area_id     = (isset($_REQUEST['area_id'])) ? intval($_REQUEST['area_id']) : 0; //仓库管理的地区ID
    $area_city     = (isset($_REQUEST['area_city'])) ? intval($_REQUEST['area_city']) : 0;
	
    $onload = (isset($_REQUEST['onload'])) ? trim($_REQUEST['onload']) : ''; //仓库管理的地区ID
    $goods = seckill_info($goods_id, 0, '', $warehouse_id, $area_id, $area_city);
    
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
        $products = get_warehouse_id_attr_number($goods['goods_id'], $_REQUEST['attr'], $goods['user_id'], $warehouse_id, $area_id, $area_city);
        $attr_number = isset($products['product_number']) ? $products['product_number'] : 0;


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

        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table($table_products) . " WHERE goods_id = '" . $goods['goods_id'] . "'" . $type_files . " LIMIT 1";
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

        $attr_number = !empty($attr_number) ? $attr_number : 0;

        /* 库存 */
        if ($goods['sec_num'] <= $attr_number) {
            $res['attr_number'] = $goods['sec_num'];
        } else {
            $res['attr_number'] = $attr_number;
        }
    }

    $res['onload'] = $onload;

    die($json->encode($res));
}

/*------------------------------------------------------ */
//-- 秒杀商品 --> 购买
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'buy')
{
    /* 查询：判断是否登录 */
    if ($user_id <= 0)
    {
        show_message($_LANG['gb_error_login'], '', '', 'error');
    }
    
    $sql = "DELETE FROM" . $GLOBALS['ecs']->table('solve_dealconcurrent') . " WHERE user_id = '$user_id' AND flow_type = '" .CART_SECKILL_GOODS. "'";
    $db->query($sql);
    
    $goods_attr = isset($_POST['goods_attr_id']) && !empty($_POST['goods_attr_id']) ? dsc_addslashes($_POST['goods_attr_id'], 0) : '';
    
    /* 查询：取得参数：秒杀活动id */
    $sec_goods_id = isset($_POST['sec_goods_id']) ? intval($_POST['sec_goods_id']) : 0;
    if ($sec_goods_id <= 0)
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 查询：取得数量 */
    $number = isset($_POST['number']) ? intval($_POST['number']) : 1;
    $number = $number < 1 ? 1 : $number;

    /* 查询：取得秒杀活动信息 */
    $seckill = seckill_info($sec_goods_id, $number);
    if (empty($seckill))
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 查询：检查秒杀活动是否是进行中 */
    if (isset($seckill['status']) && $seckill['status'] === false)
    {
        show_message($_LANG['gb_error_status'], '', '', 'error');
    }
    
    $prod = array();
    $products = [];
    if ($goods_attr) {
        if ($seckill['model_attr'] == 1) {
            $table_products = "products_warehouse";
            $type_files = " AND warehouse_id = '$region_id'";
        } elseif ($seckill['model_attr'] == 2) {
            $table_products = "products_area";
            $type_files = " AND area_id = '$area_id'";
            if ($GLOBALS['_CFG']['area_pricetype'] == 1) {
                $type_files .= " AND city_id = '$area_city'";
            }
        } else {
            $table_products = "products";
            $type_files = "";
        }
        
        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table($table_products) . " WHERE goods_id = '" .$seckill['goods_id']. "'" . $type_files . " LIMIT 1";
        $prod = $GLOBALS['db']->getRow($sql);
        
        $products = get_warehouse_id_attr_number($seckill['goods_id'], $goods_attr, $seckill['user_id'], $region_id, $area_id, $area_city);
        $product_number = isset($products['product_number']) ? $products['product_number'] : 0;
    }
    
    if ($prod) {
        $goods_number = $product_number;
    } else {
        $goods_number = $seckill['goods_number'];
    }
    
    /* 库存 */
    if ($goods['sec_num'] <= $goods_number) {
        $goods_number = $seckill['sec_num'];
    }

    if($goods_attr && $seckill['cloud_id']){
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
                                $goods_number = $v['taxNum'];
                            } else {
                                $goods_number = $v['noTaxNum'];
                            }

                            break;
                        }
                    }
                }
            }
        }
    }
    
    $url = build_uri('seckill', array('act' => "view", 'secid' => $sec_goods_id));
    
    if($goods_number <= 0){
        show_message($GLOBALS['_LANG']['buy_error'], $GLOBALS['_LANG']['go_back'], $url);
        exit;
    }
    
    /* 查询：处理规格属性 */
    if ($goods_attr)
    {
        $goods_attr_id = $goods_attr;
        
        $attr_list = array();
        $sql = "SELECT a.attr_name, g.attr_value " .
                "FROM " . $ecs->table('goods_attr') . " AS g, " .
                    $ecs->table('attribute') . " AS a " .
                "WHERE g.attr_id = a.attr_id " .
                "AND g.goods_attr_id " . db_create_in($goods_attr_id) ." ORDER BY a.sort_order, a.attr_id, g.goods_attr_id";
        $res = $db->query($sql);
        while ($row = $db->fetchRow($res))
        {
            $attr_list[] = $row['attr_name'] . ': ' . $row['attr_value'];
        }
        $goods_attr = join(chr(13) . chr(10), $attr_list);
    } else {
        $goods_attr = '';
        $goods_attr_id = '';
    }
	
    $start_date = $seckill['begin_date'];
    $end_date = $seckill['end_date'];
    $order_goods = get_for_purchasing_goods($start_date, $end_date, $seckill['goods_id'], $_SESSION['user_id']);

    $restrict_amount = $number + $order_goods['goods_number'];

	
    /* 更新：清空购物车中所有团购商品 */
    include_once(ROOT_PATH . 'includes/lib_order.php');
	
    if (!empty($_SESSION['user_id'])) {
        $sess = "";
    } else {
        $sess = real_cart_mac_ip();
    }

    /* 更新：清空购物车中所有秒杀商品 */
    include_once(ROOT_PATH . 'includes/lib_order.php');
    clear_cart(CART_SECKILL_GOODS);	
    /* 更新：加入购物车 */
    $goods_price = isset($seckill['sec_price']) > 0 ? $seckill['sec_price'] : $seckill['shop_price'];
    $cart = array(
        'user_id'        => $_SESSION['user_id'],
        'session_id'     => $sess,
        'goods_id'       => $seckill['goods_id'],
        'product_id'     => $products['product_id'],
        'goods_sn'       => addslashes($seckill['goods_sn']),
        'goods_name'     => addslashes($seckill['goods_name']),
        'market_price'   => $seckill['market_price'],
        'goods_price'    => $goods_price,
        'goods_number'   => $number,
        'goods_attr'     => addslashes($goods_attr),
        'goods_attr_id' => $goods_attr_id,
        //ecmoban模板堂 --zhuo start
        'ru_id' => $seckill['user_id'],
        'warehouse_id' => $region_id,
        'area_id' => $area_id,
        //ecmoban模板堂 --zhuo end
        'is_real' => $seckill['is_real'],
        'extension_code' => 'seckill'.$sec_goods_id,
        'parent_id'      => 0,
        'rec_type'       => CART_SECKILL_GOODS,
        'is_shipping'   => $seckill['is_shipping'],
        'is_gift'        => 0
    );

    $db->autoExecute($ecs->table('cart'), $cart, 'INSERT');

    /* 更新：记录购物流程类型：团购 */
    $_SESSION['flow_type'] = CART_SECKILL_GOODS;
    $_SESSION['extension_code'] = 'seckill';
    $_SESSION['extension_id'] = $sec_goods_id;

    /* 进入收货人页面 */
    $_SESSION['browse_trace'] = "seckill";
    ecs_header("Location: ./flow.php?step=checkout\n");
    exit;
}

/*------------------------------------------------------ */
//-- 设置提醒秒杀商品
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'collect') {
    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON();
    $result = array('error' => 0, 'message' => '', 'url' => '');

    $sid = !empty($_REQUEST['sid']) ? intval($_REQUEST['sid']) : 0;
    $user_id = !empty($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;

    if ($user_id) {
        /* 检查是否已经存在于用户提醒表 */
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('seckill_goods_remind') .
                " WHERE user_id = '$user_id' AND sec_goods_id = '$sid'";       
        if ($GLOBALS['db']->GetOne($sql) > 0) {
            $result['error'] = 1;
            $result['message'] = $GLOBALS['_LANG']['remind_goods_existed'];
            die($json->encode($result));
        } else {
            $time = gmtime();
            $sql = "INSERT INTO " . $GLOBALS['ecs']->table('seckill_goods_remind') . " (user_id, sec_goods_id, add_time)" .
                    "VALUES ('$user_id', '$sid', '$time')";

            if ($GLOBALS['db']->query($sql) === false) {
                $result['error'] = 1;
                $result['message'] = $GLOBALS['db']->errorMsg();
                die($json->encode($result));
            } else {
                $result['error'] = 0;
                $result['message'] = $GLOBALS['_LANG']['remind_goods_success'];
                die($json->encode($result));
            }
        }
    } else {
        $result['error'] = 2;
        $result['message'] = $_LANG['login_please'];
        die($json->encode($result));
    }
}

/*------------------------------------------------------ */
//-- 取消提醒秒杀商品
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'cancel') {
    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON();
    $result = array('error' => 0, 'message' => '', 'url' => '');
    $user_id = !empty($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
    $sid = !empty($_REQUEST['sid']) ? intval($_REQUEST['sid']) : 0;

    $sql = "DELETE FROM " . $GLOBALS['ecs']->table('seckill_goods_remind') . " WHERE sec_goods_id = '$sid' AND user_id = '$user_id' ";
    if ($GLOBALS['db']->query($sql) === false) {
        $result['error'] = 1;
        $result['message'] = $GLOBALS['db']->errorMsg();
        die($json->encode($result));
    } else {
        $result['error'] = 0;
        $result['message'] = $GLOBALS['_LANG']['cancel_remind_success'];
        die($json->encode($result));
    }
}

function get_top_seckill_goods()
{
    $date_begin = local_strtotime(local_date('Ymd'));
    $sql = "SELECT sg.*,g.goods_name, g.shop_price, g.sales_volume, g.goods_thumb, g.goods_id FROM " . $GLOBALS['ecs']->table('seckill_goods') . " sg "
            . " LEFT JOIN " . $GLOBALS['ecs']->table('seckill') . " AS s ON s.sec_id = sg.sec_id "
            . " LEFT JOIN " . $GLOBALS['ecs']->table('seckill_time_bucket') . " AS stb ON sg.tb_id = stb.id "
            . " LEFT JOIN " . $GLOBALS['ecs']->table('goods') . " g ON sg.goods_id = g.goods_id WHERE acti_time >= $date_begin AND g.goods_id <> '' LIMIT 5 ";
    $look_top_list = $GLOBALS['db']->getAll($sql);
    foreach ($look_top_list as $key => $look_top) {
        $look_top['goods_thumb'] = get_image_path($look_top['goods_id'], $look_top['goods_thumb'], true);
        $look_top['url'] = build_uri('seckill', array('act' => "view", 'secid' => $look_top['id']), $look_top['goods_name']);
        $look_top_list_1[] = $look_top;
    }

    return $look_top_list_1;
}

function get_merchant_seckill_goods($sec_goods_id, $ru_id){
    
    $date_begin = local_strtotime(local_date('Ymd'));
    $sql = "SELECT sg.id, sg.sec_price, g.goods_name, g.goods_thumb, g.sales_volume FROM " . $GLOBALS['ecs']->table('seckill_goods') . " sg "
            . " LEFT JOIN " . $GLOBALS['ecs']->table('seckill') . " AS s ON sg.sec_id = s.sec_id "
            . " LEFT JOIN " . $GLOBALS['ecs']->table('goods') . " g ON sg.goods_id = g.goods_id "
            . " LEFT JOIN " . $GLOBALS['ecs']->table('seckill_time_bucket') . " AS stb ON sg.tb_id = stb.id "
            . " WHERE g.user_id = '$ru_id' AND acti_time >= $date_begin AND g.goods_id <> '' LIMIT 4 ";
    $merchant_seckill = $GLOBALS['db']->getAll($sql);

    foreach ($merchant_seckill as $key => $row) {
        $merchant_seckill[$key]['shop_price'] = price_format($row['sec_price'], false);
        $merchant_seckill[$key]['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $merchant_seckill[$key]['url'] = build_uri('seckill', array('act' => "view", 'secid' => $row['id']), $row['goods_name']);
    }

    return $merchant_seckill;
}

?>