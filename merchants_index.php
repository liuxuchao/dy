<?php

/**
 * DSC 购物流程
 * ============================================================================
 * 版权所有 2005-2016 上海商创网络科技有限公司，并保留所有权利。
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
require(ROOT_PATH . '/includes/lib_visual.php');

if ((DEBUG_MODE & 2) != 2)
{
    $smarty->caching = true;
}

require(ROOT_PATH . 'includes/lib_area.php');  //ecmoban模板堂 --zhuo

define('IN_ECS', true);

$seller_domain = get_seller_domain();

if($seller_domain){
    $merchant_id = $seller_domain['ru_id'];
    $smarty->assign('is_jsonp',    1);
}else{
    $merchant_id = isset($_REQUEST['merchant_id']) ? intval($_REQUEST['merchant_id']) : 0;
    $smarty->assign('is_jsonp',    0);
}

$ua = strtolower($_SERVER['HTTP_USER_AGENT']);

$uachar = "/(nokia|sony|ericsson|mot|samsung|sgh|lg|philips|panasonic|alcatel|lenovo|cldc|midp|mobile)/i";

if(($ua == '' || preg_match($uachar, $ua))&& !strpos(strtolower($_SERVER['REQUEST_URI']),'wap'))
{
    $Loaction = 'mobile/';

    if (!empty($Loaction))
    {
        ecs_header("Location: $Loaction\n");

        exit;
    }
}
/*记录访问者IP*/
$realip=real_ip();
$view_ip = modifyipcount($realip,$merchant_id);
 

/* 初始化分页信息 */
$page = isset($_REQUEST['page'])   && intval($_REQUEST['page'])  > 0 ? intval($_REQUEST['page'])  : 1;
$size = isset($_CFG['page_size'])  && intval($_CFG['page_size']) > 0 ? intval($_CFG['page_size']) : 10;
$brand = isset($_REQUEST['brand']) && intval($_REQUEST['brand']) > 0 ? intval($_REQUEST['brand']) : 0;
$price_max = isset($_REQUEST['price_max']) && intval($_REQUEST['price_max']) > 0 ? intval($_REQUEST['price_max']) : 0;
$price_min = isset($_REQUEST['price_min']) && intval($_REQUEST['price_min']) > 0 ? intval($_REQUEST['price_min']) : 0;
$filter_attr_str = isset($_REQUEST['filter_attr']) ? htmlspecialchars(trim($_REQUEST['filter_attr'])) : '0';

$filter_attr_str = trim(urldecode($filter_attr_str));
$filter_attr_str = preg_match('/^[\d\.]+$/',$filter_attr_str) ? $filter_attr_str : '';
$filter_attr = empty($filter_attr_str) ? '' : explode('.', $filter_attr_str);

/*模板名称*/
$tem = isset($_REQUEST['tem']) ? addslashes($_REQUEST['tem']) : '';

//正则去掉js代码
$preg = "/<script[\s\S]*?<\/script>/i";

/* 排序、显示方式以及类型 */
$default_display_type = $_CFG['show_order_type'] == '0' ? 'list' : ($_CFG['show_order_type'] == '1' ? 'grid' : 'text');
$default_sort_order_method = $_CFG['sort_order_method'] == '0' ? 'DESC' : 'ASC';
$default_sort_order_type   = $_CFG['sort_order_type'] == '0' ? 'goods_id' : ($_CFG['sort_order_type'] == '1' ? 'shop_price' : 'last_update');

$sort  = (isset($_REQUEST['sort'])  && in_array(trim(strtolower($_REQUEST['sort'])), array('goods_id', 'shop_price', 'last_update','sales_volume','comments_number'))) ? trim($_REQUEST['sort'])  : $default_sort_order_type;
$order = (isset($_REQUEST['order']) && in_array(trim(strtoupper($_REQUEST['order'])), array('ASC', 'DESC')))                              ? trim($_REQUEST['order']) : $default_sort_order_method;

$display  = isset($_REQUEST['display']) ? strtolower($_REQUEST['display']) : '';
$display = !empty($display)?preg_replace($preg,"",stripslashes($display)):'';
$keyword = trim($_REQUEST['keyword']) ? trim($_REQUEST['keyword']) : '';
$keyword = htmlspecialchars($keyword);
$user_id = isset($_SESSION['user_id'])? $_SESSION['user_id'] : 0;

$cat_id = 0;
if(isset($_REQUEST['id']) && !empty($_REQUEST['id'])){
    $cat_id = intval($_REQUEST['id']);
}elseif(isset($_REQUEST['cat_id']) && !empty($_REQUEST['cat_id'])){
    $cat_id = intval($_REQUEST['cat_id']);
}

//商家不存则跳转回首页
$shop_date = array('shop_id');
$shop_where = "user_id = '$merchant_id'";
$shop_id = get_table_date('merchants_shop_information', $shop_where, $shop_date);

if($merchant_id == 0 || $shop_id < 1){
    header("Location: index.php\n");
    exit;
}

//ecmoban模板堂 --zhuo start 仓库
if (!empty($_REQUEST['act']) && $_REQUEST['act'] == 'in_warehouse') {

    include('includes/cls_json.php');

    $json = new JSON;
    $res = array('err_msg' => '', 'result' => '', 'qty' => 1);

    clear_cache_files();

    setcookie('region_id', $pid, gmtime() + 3600 * 24 * 30, $GLOBALS['cookie_path'], $GLOBALS['cookie_domain']);
    setcookie('regionId', $pid, gmtime() + 3600 * 24 * 30, $GLOBALS['cookie_path'], $GLOBALS['cookie_domain']);

    $area_region = 0;
    setcookie('area_region', $area_region, gmtime() + 3600 * 24 * 30, $GLOBALS['cookie_path'], $GLOBALS['cookie_domain']);

    $res['goods_id'] = $goods_id;

    $json = new JSON;
    die($json->encode($res));
} elseif (!empty($_REQUEST['act']) && $_REQUEST['act'] == 'in_stock') {

    include('includes/cls_json.php');

    $json = new JSON;
    $res = array('err_msg' => '', 'result' => '', 'qty' => 1);

    clear_cache_files();

    if (!isset($_COOKIE['province'])) {
        $area_array = get_ip_area_name();

        if ($area_array['county_level'] == 2) {
            $date = array('region_id', 'parent_id', 'region_name');
            $where = "region_name = '" . $area_array['area_name'] . "' AND region_type = 2";
            $city_info = get_table_date('region', $where, $date, 1);

            $date = array('region_id', 'region_name');
            $where = "region_id = '" . $city_info[0]['parent_id'] . "'";
            $province_info = get_table_date('region', $where, $date);

            $where = "parent_id = '" . $city_info[0]['region_id'] . "' order by region_id asc limit 0, 1";
            $district_info = get_table_date('region', $where, $date, 1);
        } elseif ($area_array['county_level'] == 1) {
            $area_name = $area_array['area_name'];

            $date = array('region_id', 'region_name');
            $where = "region_name = '$area_name'";
            $province_info = get_table_date('region', $where, $date);

            $where = "parent_id = '" . $province_info['region_id'] . "' order by region_id asc limit 0, 1";
            $city_info = get_table_date('region', $where, $date, 1);

            $where = "parent_id = '" . $city_info[0]['region_id'] . "' order by region_id asc limit 0, 1";
            $district_info = get_table_date('region', $where, $date, 1);
        }
    }

    $goods_id = empty($_GET['id']) ? 0 : $_GET['id'];
    $province = empty($_GET['province']) ? $province_info['region_id'] : $_GET['province'];
    $city = empty($_GET['city']) ? $city_info[0]['region_id'] : $_GET['city'];
    $district = empty($_GET['district']) ? $district_info[0]['region_id'] : $_GET['district'];
    $d_null = empty($_GET['d_null']) ? 0 : $_GET['d_null'];
    $user_id = empty($_GET['user_id']) ? 0 : $_GET['user_id'];

    $user_address = get_user_address_region($user_id);
    $user_address = explode(",", $user_address['region_address']);

    setcookie('province', $province, gmtime() + 3600 * 24 * 30, $GLOBALS['cookie_path'], $GLOBALS['cookie_domain']);
    setcookie('city', $city, gmtime() + 3600 * 24 * 30, $GLOBALS['cookie_path'], $GLOBALS['cookie_domain']);

    setcookie('district', $district, gmtime() + 3600 * 24 * 30, $GLOBALS['cookie_path'], $GLOBALS['cookie_domain']);

    $regionId = 0;
    setcookie('regionId', $regionId, gmtime() + 3600 * 24 * 30, $GLOBALS['cookie_path'], $GLOBALS['cookie_domain']);

    $res['d_null'] = $d_null;

    if ($d_null == 0) {
        if (in_array($district, $user_address)) {
            $res['isRegion'] = 1;
        } else {
            $res['message'] = $_LANG['region_message'];
            $res['isRegion'] = 88; //原为0
        }
    } else {
        setcookie('district', '', gmtime() + 3600 * 24 * 30, $GLOBALS['cookie_path'], $GLOBALS['cookie_domain']);
    }

    $res['goods_id'] = $goods_id;

    die($json->encode($res));
} elseif (!empty($_REQUEST['act']) && $_REQUEST['act'] == 'ajax_collect_store') { //Ajax取消/关注
    //修改 by tong
    include_once('includes/cls_json.php');

    $json = new JSON;
    $res = array('err_msg' => '', 'result' => '', 'error' => 0);

    $type = isset($_REQUEST['type']) ? intval($_REQUEST['type']) : 0;
    $merchant_id = isset($_REQUEST['merchant_id']) ? intval($_REQUEST['merchant_id']) : 0;
    $execute = isset($_REQUEST['execute']) ? intval($_REQUEST['execute']) : 0;

    if ((isset($_SESSION['user_id']) && $_SESSION['user_id'] < 1) || !isset($_SESSION['user_id'])) {
        $res['error'] = 2;
    } else {
        //判断是否已经关注
        $sql = "SELECT rec_id FROM " . $ecs->table('collect_store') . " WHERE user_id = '$user_id' AND ru_id = '$merchant_id' ";
        $rec_id = $db->getOne($sql);

        if ($execute == 1) {
            // 弹出提示
            if ($type == 0 || $type == 1) {
                $res['error'] = 3;
            } else if ($type == 2) {
                if ($rec_id < 1) {
                    $res['error'] = 3;
                } else {
                    $res['error'] = 1;
                }
            }
        } else {
            //取消关注
            if ($type == 0 || $type == 1) {
                if (!empty($merchant_id)) {
                    $sql = "DELETE FROM " . $ecs->table('collect_store') . " WHERE ru_id in($merchant_id)";
                    $db->query($sql);
                }
            }
            //添加关注
            if ($rec_id < 1) {
                $is_attention = 1;
                $sql = "INSERT INTO " . $ecs->table('collect_store') . "(`user_id`, `ru_id`, `add_time`, `is_attention`)VALUES('$user_id', '$merchant_id', '" . gmtime() . "', '$is_attention')";
                $db->query($sql);
            }
        }
    }

    $res['type'] = $type;
    $res['merchant_id'] = $merchant_id;

    die($json->encode($res));
}

#需要查询的IP start

if (!isset($_COOKIE['province'])) {
    $area_array = get_ip_area_name();

    if ($area_array['county_level'] == 2) {
        $date = array('region_id', 'parent_id', 'region_name');
        $where = "region_name = '" . $area_array['area_name'] . "' AND region_type = 2";
        $city_info = get_table_date('region', $where, $date, 1);

        $date = array('region_id', 'region_name');
        $where = "region_id = '" . $city_info[0]['parent_id'] . "'";
        $province_info = get_table_date('region', $where, $date);

        $where = "parent_id = '" . $city_info[0]['region_id'] . "' order by region_id asc limit 0, 1";
        $district_info = get_table_date('region', $where, $date, 1);
    } elseif ($area_array['county_level'] == 1) {
        $area_name = $area_array['area_name'];

        $date = array('region_id', 'region_name');
        $where = "region_name = '$area_name'";
        $province_info = get_table_date('region', $where, $date);

        $where = "parent_id = '" . $province_info['region_id'] . "' order by region_id asc limit 0, 1";
        $city_info = get_table_date('region', $where, $date, 1);

        $where = "parent_id = '" . $city_info[0]['region_id'] . "' order by region_id asc limit 0, 1";
        $district_info = get_table_date('region', $where, $date, 1);
    }
}
#需要查询的IP end

for ($i = 1; $i <= $_CFG['auction_ad']; $i++) {
    $merchants_index .= "'merchants_index" . $i . ","; //轮播图
}

$smarty->assign('merchants_index', $merchants_index);

for ($i = 1; $i <= $_CFG['auction_ad']; $i++) {
    $merchants_index_flow .= "'merchants_index_flow" . $i . ","; //轮播图
}

$smarty->assign('merchants_index_flow', $merchants_index_flow);

$shop_name = get_shop_name($merchant_id, 1); //店铺名称	
$grade_info = get_seller_grade($merchant_id); //等级信息
$store_conut = get_merchants_store_info($merchant_id);
$store_info = get_merchants_store_info($merchant_id, 1);

$is_cache = 1;
$dwt = 'merchants_index.dwt';

$cache_id = sprintf('%X', crc32($cat_id . '-' . $merchant_id . '-' . $display . '-' . $sort  .'-' . $order  .'-' . $page . '-' . $size . '-' . $_SESSION['user_rank'] . '-' .
                $_CFG['lang'] .'-'. $brand. '-' . $price_max . '-' .$price_min . '-' . $filter_attr_str.'-'.$keyword));

if (!$smarty->is_cached($dwt, $cache_id))
{
    assign_template();
    
    $position = assign_ur_here(0, $shop_name);
    $smarty->assign('page_title',      $position['title']);    // 页面标题
    $smarty->assign('ur_here',         $position['ur_here']);  // 当前位置
    $smarty->assign('helps',           get_shop_help());       // 网店帮助
	
    if (defined('THEME_EXTENSION')) {
        $categories_pro = get_category_tree_leve_one();
        $smarty->assign('categories_pro', $categories_pro); // 分类树加强版
    }

    $get_cat_goods = get_cat_goods($cat_id, $merchant_id, 8);
    $smarty->assign('get_cat_goods', $get_cat_goods); //分类下商品

    $promotion_goods = get_promote_goods('', $region_id, $area_id);
    $smarty->assign('promotion_goods', $promotion_goods); //促销商品



    assign_pager('merchants_index',            $cat_id, $count, $size, $sort, $order, $page, '', $brand, $price_min, $price_max, $display, $filter_attr_str, '', '', $merchant_id, $keyword); // 分页
	
    /* 页面中的动态内容 */
    assign_dynamic('merchants_index');
}

if($merchant_id > 0){
	$merchants_goods_comment = get_merchants_goods_comment($merchant_id); //商家所有商品评分类型汇总
}

$smarty->assign('merch_cmt',  $merchants_goods_comment); 

$store_category = get_user_store_category($merchant_id); //店铺导航栏
$smarty->assign('store_category',         $store_category);

//商家二维码 by wu start
$sql="SELECT ss.*,sq.* FROM ".$ecs->table('seller_shopinfo')." AS ss ".
	" LEFT JOIN ".$ecs->table('seller_qrcode')." AS sq ON sq.ru_id = ss.ru_id ".
	" WHERE ss.ru_id='$merchant_id' LIMIT 1";
$basic_info = $db->getRow($sql);

$logo = str_replace('../', '',$basic_info['qrcode_thumb']);
$size = '155x155';
$url = $ecs->url();
$data = $url."mobile/index.php?r=store/index/shop_info&id=".$merchant_id;
$errorCorrectionLevel = 'Q'; // 纠错级别：L、M、Q、H
$matrixPointSize = 4; // 点的大小：1到10
$filename = "seller_imgs/seller_qrcode/seller_qrcode_" . $merchant_id . ".png";

if (!file_exists(ROOT_PATH . $filename)) {
    
    if (!file_exists(ROOT_PATH . "seller_imgs/seller_qrcode")) {
        make_dir(ROOT_PATH . "seller_imgs/seller_qrcode");
    }
    
    require(ROOT_PATH . '/includes/phpqrcode/phpqrcode.php'); //by wu
    
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

$smarty->assign('seller_qrcode_img', $filename);
$smarty->assign('seller_qrcode_text', $basic_info['shop_name']);
//商家二维码 by wu end
$basic_info['shop_logo'] = str_replace('../', '', $basic_info['shop_logo']);

//二维码 by yan xin end
$basic_info['qrcode_thumb'] = str_replace('../', '', $basic_info['qrcode_thumb']);//二维码

//OSS文件存储ecmoban模板堂 --zhuo start
if($GLOBALS['_CFG']['open_oss'] == 1 && $basic_info['shop_logo']){
    $bucket_info = get_bucket_info();
    $basic_info['shop_logo'] = $bucket_info['endpoint'] . $basic_info['shop_logo'];
}else{
    $basic_info['shop_logo'] = $_CFG['site_domain'] . $basic_info['shop_logo'];
}
//OSS文件存储ecmoban模板堂 --zhuo end    

/*处理客服QQ数组 by kong*/
if($basic_info['kf_qq']){
    $kf_qq=array_filter(preg_split('/\s+/', $basic_info['kf_qq']));
    $kf_qq=explode("|",$kf_qq[0]);
    if(!empty($kf_qq[1])){
        $basic_info['kf_qq'] = $kf_qq[1];
    }else{
       $basic_info['kf_qq'] = ""; 
    }
    
}else{
    $basic_info['kf_qq'] = "";
}
/*处理客服旺旺数组 by kong*/
if($basic_info['kf_ww']){
    $kf_ww=array_filter(preg_split('/\s+/', $basic_info['kf_ww']));
    $kf_ww=explode("|",$kf_ww[0]);
    if(!empty($kf_ww[1])){
        $basic_info['kf_ww'] = $kf_ww[1];
    }else{
        $basic_info['kf_ww'] ="";
    }
    
}else{
    $basic_info['kf_ww'] ="";
}
$smarty->assign('basic_info',         $basic_info);  //店铺详细信息

$sql = "select parent_id from " .$ecs->table('category'). " where cat_id = '$cat_id'";
$parent_id = $db->getOne($sql);

if($parent_id == 0){
	$cat_id = 0;
}

$banner_list = get_store_banner_list($merchant_id, $store_info['seller_theme']); //店铺首页轮播图
$smarty->assign('banner_list',          $banner_list); 

/*  @author-bylu 判断当前商家是否允许"在线客服" start  */
$shop_information = get_shop_name($merchant_id);
$smarty->assign('shop_information',$shop_information);
/*  @author-bylu  end  */

$cat_list = cat_list($cat_id, 1, 0, 'merchants_category', array(), 0, $merchant_id);
$smarty->assign('cat_store_list',  $cat_list);
if (defined('THEME_EXTENSION')){
     $collect_store = 0;
    if($_SESSION['user_id'] > 0){
        $sql = "SELECT rec_id FROM " . $GLOBALS['ecs']->table('collect_store') . " WHERE user_id = '" . $_SESSION['user_id'] . "' AND ru_id = '" .$merchant_id . "' ";
        $collect_store = $GLOBALS['db']->getOne($sql);
    }

    $smarty->assign('collect_store',  $collect_store);
    $smarty->assign('merchant_id',  $merchant_id);
}

$smarty->assign('shop_name', $shop_name);
$smarty->display($dwt, $cache_id, $not);

/*------------------------------------------------------ */
//-- PRIVATE FUNCTION
/*------------------------------------------------------ */

//查询店铺基本信息以及店铺信息是否存在
function get_merchants_store_info($merchant_id, $type = 0){
    
    if($type == 0){
       $select = "count(*)"; 
    }elseif($type == 1){
       $select  = "seller_theme, shop_keyword, notice"; 
    }
    
    $sql = "select " .$select. " from " .$GLOBALS['ecs']->table('seller_shopinfo'). " where ru_id = '$merchant_id'";
    
    if($type == 0){
       $res = $GLOBALS['db']->getOne($sql);
    }elseif($type == 1){
       $res = $GLOBALS['db']->getRow($sql);
    }
    
    return $res;
}


/**
 * 店铺首页分类下商品
 * int $num 调用数量
 * string $id 分类ID
 * return $arr
 * by yan xin
 */
function get_cat_goods($id, $ru_id, $num = 8) {
    $sql = 'SELECT cat_id,cat_name FROM ' . $GLOBALS['ecs']->table('merchants_category') . ' WHERE parent_id =' . $id . ' AND user_id =' . $ru_id;
    $res = $GLOBALS['db']->getAll($sql);

    foreach ($res as $idx => $row) {
        $arr[$idx]['cat_url'] = build_uri('merchants_category', array('cid' => $row['cat_id']), $row['cat_name']);
        $arr[$idx]['cat_name'] = $row['cat_name'];
        $arr[$idx]['cat_id'] = $row['cat_id'];

        $sql = 'SELECT cat_id,cat_name FROM ' . $GLOBALS['ecs']->table('merchants_category') . " WHERE parent_id = '$row[cat_id]' ORDER BY sort_order LIMIT 9 ";
        $child_cat_res = $GLOBALS['db']->getAll($sql);
        $child_cat = array();
        //$top_goods = array();
        foreach ($child_cat_res as $key => $value) {
            $child_cat[$key]['cat_id'] = $value['cat_id'];
            $child_cat[$key]['cat_name'] = $value['cat_name'];
            $child_cat[$key]['url'] = build_uri('merchants_category', array('cid' => $value['cat_id']), $value['cat_name']);
        }

        $children = get_children($row['cat_id'], 0, 0, 'merchants_category', "g.user_cat");

        $sql = 'SELECT g.goods_id, g.goods_name, g.market_price, g.shop_price AS org_price, ' .
                "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, " .
                'g.promote_price, promote_start_date, promote_end_date, g.goods_brief, g.goods_thumb, g.goods_img ' .
                "FROM " . $GLOBALS['ecs']->table('goods') . ' AS g ' .
                "LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp " .
                "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " .
                'WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND ' .
                'g.is_delete = 0 AND (' . $children . ') ';

        $order_rule = 'ORDER BY g.sort_order, g.goods_id DESC';
        $sql .= $order_rule;
        if ($num > 0) {
            $sql .= ' LIMIT ' . $num;
        }
        $goods_res = $GLOBALS['db']->getAll($sql);

        $goods = array();
        foreach ($goods_res AS $goods_idx => $goods_row) {
            if ($goods_row['promote_price'] > 0) {
                $promote_price = bargain_price($goods_row['promote_price'], $goods_row['promote_start_date'], $goods_row['promote_end_date']);
                $goods[$goods_idx]['promote_price'] = $promote_price > 0 ? price_format($promote_price) : '';
            } else {
                $goods[$goods_idx]['promote_price'] = '';
            }

            $goods[$goods_idx]['id'] = $goods_row['goods_id'];
            $goods[$goods_idx]['name'] = $goods_row['goods_name'];
            $goods[$goods_idx]['brief'] = $goods_row['goods_brief'];
            $goods[$goods_idx]['market_price'] = price_format($goods_row['market_price']);
            $goods[$goods_idx]['short_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
                    sub_str($goods_row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $goods_row['goods_name'];
            $goods[$goods_idx]['shop_price'] = price_format($goods_row['shop_price']);
            $goods[$goods_idx]['thumb'] = get_image_path($goods_row['goods_id'], $goods_row['goods_thumb'], true);
            $goods[$goods_idx]['goods_img'] = get_image_path($goods_row['goods_id'], $goods_row['goods_img']);
            $goods[$goods_idx]['url'] = build_uri('goods', array('gid' => $goods_row['goods_id']), $goods_row['goods_name']);
        }

        $arr[$idx]['goods'] = $goods;
        $arr[$idx]['child_cat'] = $child_cat;
    }
    return $arr;
}

?>