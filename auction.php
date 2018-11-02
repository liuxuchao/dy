<?php

/**
 * ECSHOP 拍卖前台文件
 * ============================================================================
 * * 版权所有 2005-2016 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: auction.php 17217 2011-01-19 06:29:08Z liubo $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

require(ROOT_PATH . '/includes/lib_area.php');  //ecmoban模板堂 --zhuo
require(ROOT_PATH . 'includes/lib_order.php');

//ecmoban模板堂 --zhuo start
$warehouse_other = [
    'province_id' => $province_id,
    'city_id' => $city_id
];
$warehouse_area_info = get_warehouse_area_info($warehouse_other);

$region_id = $warehouse_area_info['region_id'];
$area_id = $warehouse_area_info['area_id'];
$area_city = $warehouse_area_info['city_id'];

if(isset($_COOKIE['region_id']) && !empty($_COOKIE['region_id'])){
    $region_id = $_COOKIE['region_id'];
}
//ecmoban模板堂 --zhuo end

/*------------------------------------------------------ */
//-- act 操作项的初始化
/*------------------------------------------------------ */
if (empty($_REQUEST['act']))
{
    $_REQUEST['act'] = 'list';
}

$smarty->assign('now_time',  gmtime());           // 当前系统时间

/*------------------------------------------------------ */
//-- 拍卖活动列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    //瀑布流 by wu start
    $smarty->assign('category_load_type', $_CFG['category_load_type']);
    $smarty->assign('query_string', preg_replace('/act=\w+&?/', '', $_SERVER['QUERY_STRING']));
    //瀑布流 by wu end	

    /* 初始化分页信息 */
    $page         = isset($_REQUEST['page'])   && intval($_REQUEST['page'])  > 0 ? intval($_REQUEST['page'])  : 1;  // 取得当前页
    $size         = isset($_CFG['page_size'])  && intval($_CFG['page_size']) > 0 ? intval($_CFG['page_size']) : 10; // 取得每页记录数
    $size = 15;
    $cat_id       = isset($_REQUEST['cat_id']) && intval($_REQUEST['cat_id']) > 0 ? intval($_REQUEST['cat_id']) : 0;
    $integral_max = isset($_REQUEST['integral_max']) && intval($_REQUEST['integral_max']) > 0 ? intval($_REQUEST['integral_max']) : 0;
    $integral_min = isset($_REQUEST['integral_min']) && intval($_REQUEST['integral_min']) > 0 ? intval($_REQUEST['integral_min']) : 0;
    $keywords   = !empty($_REQUEST['keywords'])   ? htmlspecialchars(trim($_REQUEST['keywords'])):'';
    
    $cat_top_id = isset($_REQUEST['cat_top_id']) ? intval($_REQUEST['cat_top_id']) : 0;
    $default_sort_order_method = $_CFG['sort_order_method'] == '0' ? 'DESC' : 'ASC';
    $default_sort_order_type   = $_CFG['sort_order_type'] == '0' ? 'act_id' : ($_CFG['sort_order_type'] == '1' ? 'start_time' : 'end_time');

    $sort  = (isset($_REQUEST['sort'])  && in_array(trim(strtolower($_REQUEST['sort'])), array('act_id', 'start_time', 'end_time'))) ? trim($_REQUEST['sort'])  : $default_sort_order_type;
    $order = (isset($_REQUEST['order']) && in_array(trim(strtoupper($_REQUEST['order'])), array('ASC', 'DESC'))) ? trim($_REQUEST['order']) : $default_sort_order_method;
    
    $children = get_children($cat_id);
    $top_children = array();
    if($cat_top_id > 0){
        $top_children = get_children($cat_top_id);
    }
    /* 取得拍卖活动总数 */
    $count = auction_count($keywords,$top_children);
    if ($count > 0)
    {
        /* 计算总页数 */
        $page_count = ceil($count / $size);

        /* 取得当前页 */
        $page = $page > $page_count ? $page_count : $page;

        /* 缓存id：语言 - 每页记录数 - 当前页 */
        $cache_id = $_CFG['lang'] . '-' . $size . '-' . $page;
        $cache_id = sprintf('%X', crc32($cache_id));
    }
    else
    {
        /* 缓存id：语言 */
        $cache_id = $_CFG['lang'];
        $cache_id = sprintf('%X', crc32($cache_id));
    }

    /* 如果没有缓存，生成缓存 */
    if (!$smarty->is_cached('auction_list.dwt', $cache_id))
    {
        if ($count > 0) {
            /* 取得当前页的拍卖活动 */
            $auction_list = auction_list($keywords, $sort, $order, $size, $page, $top_children);
            $smarty->assign('auction_list', $auction_list);
            if (defined('THEME_EXTENSION')) {
                $smarty->assign('cat_top_list', get_top_cat());
            }
            //瀑布流 by wu start
            if (!$_CFG['category_load_type']) {
                /* 设置分页链接 */
                $pager = get_pager('auction.php', array('act' => 'list', 'keywords' => $keywords, 'sort' => $sort, 'order' => $order), $count, $page, $size);
                $smarty->assign('pager', $pager);
            }
            //瀑布流 by wu end			
        }

        /* 模板赋值 */
        $smarty->assign('cfg', $_CFG);
        assign_template();
        $position = assign_ur_here();
        $smarty->assign('page_title', $position['title']);    // 页面标题
        $smarty->assign('ur_here',    $position['ur_here']);  // 当前位置
        $smarty->assign('helps',      get_shop_help());       // 网店帮助
        
        if (!defined('THEME_EXTENSION')) {
            $categories_pro = get_category_tree_leve_one();
            $smarty->assign('categories_pro', $categories_pro); // 分类树加强版
        }

        $smarty->assign('feed_url',         ($_CFG['rewrite'] == 1) ? "feed-typeauction.xml" : 'feed.php?type=auction'); // RSS URL
        
        $smarty->assign('hot_goods',       get_exchange_recommend_goods('hot',  $children, $integral_min, $integral_max));  //热门
        
        $smarty->assign('category',        9999999999999999999);
	if (defined('THEME_EXTENSION')){
		 $smarty->assign('cat_top_id',$cat_top_id);
	        /*广告位*/
	        for($i=1;$i<=$_CFG['auction_ad'];$i++){
	            $activity_top_banner   .= "'activity_top_ad_auction".$i.","; //轮播图
	        }
	        $smarty->assign('activity_top_banner',$activity_top_banner);
	}
        assign_dynamic('auction_list');
    }

    /* 显示模板 */
    $smarty->display('auction_list.dwt', $cache_id);
}

/*------------------------------------------------------ */
//-- 瀑布流 by wu
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'load_more_goods')
{
    
    /* 初始化分页信息 */
    $page         = isset($_REQUEST['page'])   && intval($_REQUEST['page'])  > 0 ? intval($_REQUEST['page'])  : 1;  // 取得当前页
    $size         = isset($_CFG['page_size'])  && intval($_CFG['page_size']) > 0 ? intval($_CFG['page_size']) : 10; // 取得每页记录数
    $size = 15;
    $cat_id       = isset($_REQUEST['cat_id']) && intval($_REQUEST['cat_id']) > 0 ? intval($_REQUEST['cat_id']) : 0;
    $integral_max = isset($_REQUEST['integral_max']) && intval($_REQUEST['integral_max']) > 0 ? intval($_REQUEST['integral_max']) : 0;
    $integral_min = isset($_REQUEST['integral_min']) && intval($_REQUEST['integral_min']) > 0 ? intval($_REQUEST['integral_min']) : 0;
    $keywords   = !empty($_REQUEST['keywords'])   ? htmlspecialchars(trim($_REQUEST['keywords'])):'';
    $cat_top_id = isset($_REQUEST['cat_top_id']) ? intval($_REQUEST['cat_top_id']) : 0;
    $default_sort_order_method = $_CFG['sort_order_method'] == '0' ? 'DESC' : 'ASC';
    $default_sort_order_type   = $_CFG['sort_order_type'] == '0' ? 'act_id' : ($_CFG['sort_order_type'] == '1' ? 'start_time' : 'end_time');

    $sort  = (isset($_REQUEST['sort'])  && in_array(trim(strtolower($_REQUEST['sort'])), array('act_id', 'start_time', 'end_time'))) ? trim($_REQUEST['sort'])  : $default_sort_order_type;
    $order = (isset($_REQUEST['order']) && in_array(trim(strtoupper($_REQUEST['order'])), array('ASC', 'DESC'))) ? trim($_REQUEST['order']) : $default_sort_order_method;
    
    $children = get_children($cat_id);
    if($cat_top_id > 0){
        $top_children = get_children($cat_top_id);
    }
	/* 取得当前页的拍卖活动 */
	$auction_list = auction_list($keywords, $sort, $order, $size, $page,$top_children);
	$smarty->assign('auction_list',  $auction_list);
	
	$smarty->assign('type',  'auction'); 
	if (defined('THEME_EXTENSION')){
		$smarty->assign('cat_top_list',  get_top_cat());
	        $smarty->assign('cat_top_id',$cat_top_id);
	}
	$result = array('error' => 0,'message' => '','cat_goods'=>'','best_goods'=>'');
	$result['cat_goods'] = html_entity_decode($smarty->fetch('library/more_goods_page.lbi'));
	die(json_encode($result));	
}

/*------------------------------------------------------ */
//-- 拍卖商品 --> 商品详情
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'view')
{
    $cat_id       = isset($_REQUEST['cat_id']) && intval($_REQUEST['cat_id']) > 0 ? intval($_REQUEST['cat_id']) : 0;
    $integral_max = isset($_REQUEST['integral_max']) && intval($_REQUEST['integral_max']) > 0 ? intval($_REQUEST['integral_max']) : 0;
    $integral_min = isset($_REQUEST['integral_min']) && intval($_REQUEST['integral_min']) > 0 ? intval($_REQUEST['integral_min']) : 0;
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    $children = get_children($cat_id);
    
    $smarty->assign('hot_goods',       get_exchange_recommend_goods('hot',  $children, $integral_min, $integral_max));  //热门
    $smarty->assign('user_id', $user_id);
    /* 取得参数：拍卖活动id */
    $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    if ($id <= 0)
    {
        ecs_header("Location: ./\n");
        exit;
    }
    /*获取用户信息*/
    $user = user_info($user_id);
    $smarty->assign("user",$user);
    /* 取得拍卖活动信息 */
    $auction = auction_info($id);
    
    if(!$auction){
        show_message($_LANG['now_not_snatch'],$_LANG['back_auction_home'], 'auction.php');
    }

    $auction['is_winner'] = 0;
    /* 缓存id：语言，拍卖活动id，状态，如果是进行中，还要最后出价的时间（如果有的话） */
    $cache_id = $_CFG['lang'] . '-' . $id . '-' . $auction['status_no'];
    if ($auction['status_no'] == UNDER_WAY)
    {
        if (isset($auction['last_bid']))
        {
            $cache_id = $cache_id . '-' . $auction['last_bid']['bid_time'];
        }
    }
    elseif ($auction['last_bid'])
    {
        if($auction['status_no'] == FINISHED && $auction['last_bid']['bid_user'] == $_SESSION['user_id'] && $auction['order_count'] == 0){
            $auction['is_winner'] = 1;
        }
        $cache_id = $cache_id . '-' . $auction['last_bid']['bid_time'] . '-1';
    }
    
    $cache_id = sprintf('%X', crc32($cache_id));

    /* 如果没有缓存，生成缓存 */
    if (!$smarty->is_cached('auction.dwt', $cache_id))
    {
        //取货品信息
        if ($auction['product_id'] > 0)
        {
            $goods_specifications = get_specifications_list($auction['goods_id']);

            $good_products = get_good_products($auction['goods_id'], 'AND product_id = ' . $auction['product_id']);

            $_good_products = explode('|', $good_products[0]['goods_attr']);
            $products_info = '';
            foreach ($_good_products as $value)
            {
                $products_info .= ' ' . $goods_specifications[$value]['attr_name'] . '：' . $goods_specifications[$value]['attr_value'];
            }
            $smarty->assign('products_info',     $products_info);
            unset($goods_specifications, $good_products, $_good_products,  $products_info);
        }

        $auction['gmt_end_time'] = local_strtotime($auction['end_time']);
        
        $smarty->assign('auction', $auction);

        /* 取得拍卖商品信息 */
        $goods_id = $auction['goods_id'];
        $goods = goods_info($goods_id,'','','','',1);
        if (empty($goods))
        {
            ecs_header("Location: ./\n");
            exit;
        }
        $goods['url'] = build_uri('goods', array('gid' => $goods_id), $goods['goods_name']);
        $smarty->assign('auction_goods', $goods);
        $smarty->assign('goods', $goods);
       
        /* 出价记录 */
        $smarty->assign('auction_log', auction_log($id));
        $smarty->assign('auction_count', auction_log($id, 1));

        //模板赋值
        $smarty->assign('cfg', $_CFG);
        assign_template();

        $position = assign_ur_here(0, $goods['goods_name']);
        $smarty->assign('page_title', $position['title']);    // 页面标题
        $smarty->assign('ur_here',    $position['ur_here']);  // 当前位置
        
        if (!defined('THEME_EXTENSION')) {
            $categories_pro = get_category_tree_leve_one();
            $smarty->assign('categories_pro', $categories_pro); // 分类树加强版
        }

        $smarty->assign('helps',      get_shop_help());       // 网店帮助
        $smarty->assign('pictures',            get_goods_gallery($goods_id));                    // 商品相册
        
        //评分 start
        $mc_all = ments_count_all($goods_id);       //总条数
        $mc_one = ments_count_rank_num($goods_id, 1);  //一颗星
        $mc_two = ments_count_rank_num($goods_id, 2);     //两颗星	
        $mc_three = ments_count_rank_num($goods_id, 3);    //三颗星
        $mc_four = ments_count_rank_num($goods_id, 4);  //四颗星
        $mc_five = ments_count_rank_num($goods_id, 5);  //五颗星
        $comment_all = get_conments_stars($mc_all, $mc_one, $mc_two, $mc_three, $mc_four, $mc_five);

        if ($goods['user_id'] > 0) {
            $merchants_goods_comment = get_merchants_goods_comment($goods['user_id']); //商家所有商品评分类型汇总
        }
        //评分 end 

        $smarty->assign('comment_all', $comment_all);
        $smarty->assign('merch_cmt', $merchants_goods_comment);
        
        $properties = get_goods_properties($goods_id, $region_id, $area_id, $area_city);  // 获得商品的规格和属性  //ecmoban模板堂 --zhuo
        $smarty->assign('properties',          $properties['pro']);                              // 商品规格	
        $smarty->assign('specification',       $properties['spe']);                              // 商品属性

        assign_dynamic('auction');
    }
    
    $smarty->assign('goods_id', $goods_id);                              // 商品ID
    $smarty->assign('region_id', $region_id);                              // 商品ID
    $smarty->assign('area_id', $area_id);                              // 商品ID

    $basic_date = array('region_name');
    $basic_info['province'] = get_table_date('region', "region_id = '" . $goods['province'] . "'", $basic_date, 2);
    $basic_info['city'] = get_table_date('region', "region_id= '" . $goods['city'] . "'", $basic_date, 2) . "市";
    $basic_info['kf_type'] = $goods['kf_type'];
    $basic_info['[kf_ww'] = $goods['kf_ww'];
    $basic_info['kf_qq'] = $goods['kf_qq'];
    $basic_info['shop_name'] = $goods['shop_name'];

    /*  @author-bylu 判断当前商家是否允许"在线客服" start  */
    $shop_information = get_shop_name($goods['user_id']);//通过ru_id获取到店铺信息;
    //判断当前商家是平台,还是入驻商家 bylu
    if($goods['user_id'] == 0){
        //判断平台是否开启了IM在线客服
        if($db->getOne("SELECT kf_im_switch FROM ".$ecs->table('seller_shopinfo')."WHERE ru_id = 0")){
            $shop_information['is_dsc'] = true;
        }else{
            $shop_information['is_dsc'] = false;
        }
    }else{
        $shop_information['is_dsc'] = false;
    }
    $smarty->assign('shop_information',$shop_information);
    /*  @author-bylu  end  */

    $smarty->assign('basic_info',  $basic_info);
    
    $smarty->assign('category',        9999999999999999999);

    //更新商品点击次数
    $sql = 'UPDATE ' . $ecs->table('goods') . ' SET click_count = click_count + 1 '.
           "WHERE goods_id = '" . $auction['goods_id'] . "'";
    $db->query($sql);
    
    $smarty->display('auction.dwt', $cache_id);
}

/*------------------------------------------------------ */
//-- 拍卖商品 --> 出价
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'bid')
{
    include_once(ROOT_PATH . 'includes/lib_order.php');

    $_POST['price'] = isset($_POST['price']) ? intval($_POST['price']) : 0;
    
    $warehouse_id = isset($_POST['region_id']) ? intval($_POST['region_id']) : 0;
    $area_id = isset($_POST['area_id']) ? intval($_POST['area_id']) : 0;
    $goods_attr = isset($_REQUEST['goods_attr_id']) && !empty($_REQUEST['goods_attr_id']) ? dsc_addslashes($_REQUEST['goods_attr_id'], 0) : '';
    
    /* 取得参数：拍卖活动id */
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if ($id <= 0)
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 取得拍卖活动信息 */
    $auction = auction_info($id);
    if (empty($auction))
    {
        ecs_header("Location: ./\n");
        exit;
    }
    
    $goods = get_goods_info($auction['goods_id'], $warehouse_id, $area_id, $area_city);
    
    $prod = array();
    if ($goods_attr) {
        if ($goods['model_attr'] == 1) {
            $table_products = "products_warehouse";
            $type_files = " AND warehouse_id = '$warehouse_id'";
        } elseif ($goods['model_attr'] == 2) {
            $table_products = "products_area";
            $type_files = " AND area_id = '$area_id'";
        } else {
            $table_products = "products";
            $type_files = "";
        }
        
        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table($table_products) . " WHERE goods_id = '" .$goods['goods_id']. "'" . $type_files . " LIMIT 1";
        $prod = $GLOBALS['db']->getRow($sql);
        
        $products = get_warehouse_id_attr_number($goods['goods_id'], $goods_attr, $goods['user_id'], $warehouse_id, $area_id, $area_city);
        $product_number = isset($products['product_number']) ? $products['product_number'] : 0;
    }
    
    if ($prod) {
        $goods_number = $product_number;
    } else {
        $goods_number = $goods['goods_number'];
    }
    
    if($goods_attr && $goods['cloud_id']){
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
    
    $url = build_uri('auction', array('auid'=>$auction['act_id']));
    
    if($goods_number <= 0){
        show_message($GLOBALS['_LANG']['buy_error'], $GLOBALS['_LANG']['go_back'], $url);
        exit;
    }

    /* 活动是否正在进行 */
    if ($auction['status_no'] != UNDER_WAY)
    {
        show_message($_LANG['au_not_under_way'], '', '', 'error');
    }

    /* 是否登录 */
    $user_id = $_SESSION['user_id'];
    if ($user_id <= 0)
    {
        show_message($_LANG['au_bid_after_login']);
    }
    $user = user_info($user_id);

    /* 取得出价 */
    $bid_price = isset($_POST['buy-price']) ? round(floatval($_POST['buy-price']), 2) : 0;
    if ($bid_price <= 0)
    {
        show_message($_LANG['au_bid_price_error'], '', '', 'error');
    }

    /* 如果有一口价且出价大于等于一口价，则按一口价算 */
    $is_ok = false; // 出价是否ok
    if ($auction['end_price'] > 0)
    {
        if ($bid_price >= $auction['end_price'])
        {
            $bid_price = $auction['end_price'];
            $is_ok = true;
        }
    }

    /* 出价是否有效：区分第一次和非第一次 */
    if (!$is_ok)
    {
        if ($auction['bid_user_count'] == 0)
        {
            /* 第一次要大于等于起拍价 */
            $min_price = $auction['start_price'];
        }
        else
        {
            /* 非第一次出价要大于等于最高价加上加价幅度，但不能超过一口价 */
            $min_price = $auction['last_bid']['bid_price'] + $auction['amplitude'];
            if ($auction['end_price'] > 0)
            {
                $min_price = min($min_price, $auction['end_price']);
            }
        }

        if ($bid_price < $min_price)
        {
            show_message(sprintf($_LANG['au_your_lowest_price'], price_format($min_price, false)), '', '', 'error');
        }
    }

    /* 检查联系两次拍卖人是否相同 */
    if ($auction['last_bid']['bid_user'] == $user_id && $bid_price != $auction['end_price'])
    {
        show_message($_LANG['au_bid_repeat_user'], '', '', 'error');
    }

    /* 是否需要保证金 */
    if ($auction['deposit'] > 0)
    {
        /* 可用资金够吗 */
        if ($user['user_money'] < $auction['deposit'])
        {
            show_message($_LANG['au_user_money_short'], '', '', 'error');
        }

        /* 如果不是第一个出价，解冻上一个用户的保证金 */
        if ($auction['bid_user_count'] > 0)
        {
            log_account_change($auction['last_bid']['bid_user'], $auction['deposit'], (-1) * $auction['deposit'],
                0, 0, sprintf($_LANG['au_unfreeze_deposit'], $auction['act_name']));
        }

        /* 冻结当前用户的保证金 */
        log_account_change($user_id, (-1) * $auction['deposit'], $auction['deposit'],
            0, 0, sprintf($_LANG['au_freeze_deposit'], $auction['act_name']));
    }

    /* 插入出价记录 */
    $auction_log = array(
        'act_id'    => $id,
        'bid_user'  => $user_id,
        'bid_price' => $bid_price,
        'bid_time'  => gmtime()
    );
    $db->autoExecute($ecs->table('auction_log'), $auction_log, 'INSERT');

    /* 出价是否等于一口价 */
    if ($bid_price == $auction['end_price'])
    {
        /* 结束拍卖活动 */
        $sql = "UPDATE " . $ecs->table('goods_activity') . " SET is_finished = 1 WHERE act_id = '$id' LIMIT 1";
        $db->query($sql);
    }

    /* 跳转到活动详情页 */
    ecs_header("Location: auction.php?act=view&id=$id\n");
    exit;
}

/*------------------------------------------------------ */
//-- 拍卖商品 --> 购买
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'buy')
{
    /* 查询：取得参数：拍卖活动id */
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    $goods_attr = isset($_POST['goods_attr_id']) && !empty($_POST['goods_attr_id']) ? dsc_addslashes($_POST['goods_attr_id'], 0) : '';
    
    if ($id <= 0)
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 查询：取得拍卖活动信息 */
    $auction = auction_info($id);
    if (empty($auction))
    {
        ecs_header("Location: ./\n");
        exit;
    }
    
    /* 查询：取得商品信息 */
    $goods = get_goods_info($auction['goods_id'], $region_id, $area_id, $area_city);
    
    $prod = array();
    $products = [];
    if ($goods_attr) {
        if ($goods['model_attr'] == 1) {
            $table_products = "products_warehouse";
            $type_files = " AND warehouse_id = '$region_id'";
        } elseif ($goods['model_attr'] == 2) {
            $table_products = "products_area";
            $type_files = " AND area_id = '$area_id'";
            if ($GLOBALS['_CFG']['area_pricetype'] == 1) {
                $type_files .= " AND city_id = '$area_city'";
            }
        } else {
            $table_products = "products";
            $type_files = "";
        }
        
        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table($table_products) . " WHERE goods_id = '" .$goods['goods_id']. "'" . $type_files . " LIMIT 1";
        $prod = $GLOBALS['db']->getRow($sql);
        
        $products = get_warehouse_id_attr_number($goods['goods_id'], $goods_attr, $goods['user_id'], $region_id, $area_id, $area_city);
        $product_number = isset($products['product_number']) ? $products['product_number'] : 0;
    }
    
    if ($prod) {
        $goods_number = $product_number;
    } else {
        $goods_number = $goods['goods_number'];
    }
    
    if($goods_attr && $goods['cloud_id']){
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
    
    $url = build_uri('auction', array('auid'=>$auction['act_id']));
    
    if($goods_number <= 0){
        show_message($GLOBALS['_LANG']['buy_error'], $GLOBALS['_LANG']['go_back'], $url);
        exit;
    }

    /* 查询：活动是否已结束 */
    if ($auction['status_no'] != FINISHED)
    {
        show_message($_LANG['au_not_finished'], '', '', 'error');
    }

    /* 查询：有人出价吗 */
    if ($auction['bid_user_count'] <= 0)
    {
        show_message($_LANG['au_no_bid'], '', '', 'error');
    }

    /* 查询：是否已经有订单 */
    if ($auction['order_count'] > 0)
    {
        show_message($_LANG['au_order_placed']);
    }

    /* 查询：是否登录 */
    $user_id = $_SESSION['user_id'];
    if ($user_id <= 0)
    {
        show_message($_LANG['au_buy_after_login']);
    }

    /* 查询：最后出价的是该用户吗 */
    if ($auction['last_bid']['bid_user'] != $user_id)
    {
        show_message($_LANG['au_final_bid_not_you'], '', '', 'error');
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

    //ecmoban模板堂 --zhuo start
    if (!empty($_SESSION['user_id'])) {
        $sess = "";
    } else {
        $sess = real_cart_mac_ip();
    }
    //ecmoban模板堂 --zhuo end

    /* 清空购物车中所有拍卖商品 */
    include_once(ROOT_PATH . 'includes/lib_order.php');
    clear_cart(CART_AUCTION_GOODS);

    /* 加入购物车 */
    $cart = array(
        'user_id'        => $user_id,
        'session_id'     => $sess,
        'goods_id'       => $auction['goods_id'],
        'product_id'     => isset($products['product_id']) ? $products['product_id'] : 0,
        'goods_sn'       => addslashes($goods['goods_sn']),
        'goods_name'     => addslashes($goods['goods_name']),
        'market_price'   => $goods['market_price'],
        'goods_price'    => $auction['last_bid']['bid_price'],
        'goods_number'   => 1,
        'goods_attr'     => addslashes($goods_attr),
        'goods_attr_id'  => $goods_attr_id,
        'warehouse_id'   => $region_id, //ecmoban模板堂 --zhuo 仓库
        'area_id'        => $area_id, //ecmoban模板堂 --zhuo 仓库地区
        'area_city'      => $area_city,
        'is_real'        => $goods['is_real'],
        'ru_id'          => $goods['user_id'],
        'extension_code' => addslashes($goods['extension_code']),
        'parent_id'      => 0,
        'rec_type'       => CART_AUCTION_GOODS,
        'is_gift'        => 0
    );
    $db->autoExecute($ecs->table('cart'), $cart, 'INSERT');

    /* 记录购物流程类型：团购 */
    $_SESSION['flow_type'] = CART_AUCTION_GOODS;
    $_SESSION['extension_code'] = 'auction';
    $_SESSION['extension_id'] = $id;
    $_SESSION['direct_shopping'] = 2;

    /* 进入收货人页面 */
    ecs_header("Location: ./flow.php?step=checkout&direct_shopping=2\n");
    exit;
}

/**
 * 取得拍卖活动数量
 * @return  int
 */
function auction_count($keywords,$top_children=array())
{
    $now = gmtime();
    $where =  '';
    if ($keywords)
    {
        $where = "AND (a.act_name LIKE '%$keywords%' OR g.goods_name LIKE '%$keywords%') ";
    }
    if(!empty($top_children)){
        $where .= "AND ".$top_children."";
    }
    $sql = "SELECT COUNT(*) " .
            "FROM " . $GLOBALS['ecs']->table('goods_activity') ." AS a ".
            "LEFT JOIN " . $GLOBALS['ecs']->table('goods') . " AS g ON a.goods_id = g.goods_id " .
            " WHERE a.act_type = '" . GAT_AUCTION . "' " .
            "AND a.start_time <= '$now' AND a.end_time >= '$now' AND a.is_finished < 2 AND a.review_status = 3 " . $where;
    return $GLOBALS['db']->getOne($sql);
}
/**
 * 取得拍卖活动所有商品分类的顶级分类
 * @return  int
 */
function get_top_cat()
{
    $now = gmtime();
    $cat_top_list = array();
    $sql = "SELECT g.cat_id " .
            "FROM " . $GLOBALS['ecs']->table('goods_activity') ." AS a ".
            "LEFT JOIN " . $GLOBALS['ecs']->table('goods') . " AS g ON a.goods_id = g.goods_id " .
            " WHERE a.act_type = '" . GAT_AUCTION . "' " .
            "AND a.start_time <= '$now' AND a.end_time >= '$now' AND a.is_finished < 2  AND a.review_status = 3 " ;
   $cat_list = $GLOBALS['db']->getAll($sql);
   
   foreach($cat_list as $k=>$v){
        $cat_info = get_topparent_cat($v['cat_id']);
        $cat_top_list[$cat_info['cat_id']] = $cat_info;
   }
    return $cat_top_list;
}
/**
 * 取得某页的拍卖活动
 * @param   int     $size   每页记录数
 * @param   int     $page   当前页
 * @return  array
 */
function auction_list($keywords, $sort, $order, $size, $page,$top_children=array())
{
    $auction_list = array();
    $auction_list['finished'] = $auction_list['finished'] = array();
    $where = "";
    if ($keywords)
    {
        $where = "AND (a.act_name LIKE '%$keywords%' OR g.goods_name LIKE '%$keywords%') ";
    }
    if ($sort)
    {
        $by_sort = " a.$sort";
    }
    if(!empty($top_children)){
        $where .= "AND ".$top_children."";
    }
    $now = gmtime();
    $sql = "SELECT a.*, IFNULL(g.goods_thumb, '') AS goods_thumb " .
            "FROM " . $GLOBALS['ecs']->table('goods_activity') . " AS a " .
                "LEFT JOIN " . $GLOBALS['ecs']->table('goods') . " AS g ON a.goods_id = g.goods_id " .
            "WHERE a.act_type = '" . GAT_AUCTION . "' " .$where.
            "AND a.start_time <= '$now' AND a.end_time >= '$now' AND a.is_finished < 2 AND a.review_status = 3 ORDER BY $by_sort $order";

	//瀑布流 by wu start
	if(isset($_REQUEST['act']) && $_REQUEST['act'] == 'load_more_goods')
	{
		$start = intval($_REQUEST['goods_num']);
	}
	else
	{
		$start = ($page - 1) * $size;
	}
	$res = $GLOBALS['db']->selectLimit($sql, $size, $start);
	//瀑布流 by wu end			
    //$res = $GLOBALS['db']->selectLimit($sql, $size, ($page - 1) * $size);
    while($row = $GLOBALS['db']->fetchRow($res))
    {
        $ext_info = unserialize($row['ext_info']);
        $auction = array_merge($row, $ext_info);
        $auction['status_no'] = auction_status($auction);

        $auction['start_time'] = local_date($GLOBALS['_CFG']['time_format'], $auction['start_time']);
        $auction['end_time']   = local_date($GLOBALS['_CFG']['time_format'], $auction['end_time']);
        $auction['formated_start_price'] = price_format($auction['start_price']);
        $auction['formated_end_price'] = price_format($auction['end_price']);
        $auction['formated_deposit'] = price_format($auction['deposit']);
        $auction['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $auction['url'] = build_uri('auction', array('auid'=>$auction['act_id']));
        $auction['count'] = auction_log($auction['act_id'], 1);
        $auction['current_time'] = local_date('Y-m-d H:i:s', gmtime());
        $auction['rz_shopName'] = get_shop_name($row['user_id'], 1); //店铺名称  
//        print_arr($auction);
        /* 查询已确认订单数 */
        if ($auction['status_no'] > 1)
        {
            $sql = "SELECT COUNT(*)" .
                    " FROM " . $GLOBALS['ecs']->table('order_info') .
                    " WHERE extension_code = 'auction'" .
                    " AND extension_id = '$auction[act_id]'" .
                    " AND order_status " . db_create_in(array(OS_CONFIRMED, OS_UNCONFIRMED));
            $auction['order_count'] = $GLOBALS['db']->getOne($sql);
        }
        else
        {
            $auction['order_count'] = 0;
        }

        /* 查询出价用户数和最后出价  qin */
        $sql = "SELECT COUNT(DISTINCT bid_user) FROM " . $GLOBALS['ecs']->table('auction_log') .
                " WHERE act_id = '$auction[act_id]'";
        $auction['bid_user_count'] = $GLOBALS['db']->getOne($sql);
        
        if ($auction['bid_user_count'] > 0)
        {
            $sql = "SELECT a.*, u.user_name " .
                    "FROM " . $GLOBALS['ecs']->table('auction_log') . " AS a, " .
                            $GLOBALS['ecs']->table('users') . " AS u " .
                    "WHERE a.bid_user = u.user_id " .
                    "AND act_id = '$auction[act_id]' " .
                    "ORDER BY a.log_id DESC";
            $row = $GLOBALS['db']->getRow($sql);
            $row['formated_bid_price'] = price_format($row['bid_price'], false);
            $row['bid_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['bid_time']);
            $auction['last_bid'] = $row;
        }
        
        $auction['is_winner'] = 0;
        if($auction['last_bid']['bid_user']){
            if ($auction['status_no'] == FINISHED && $auction['last_bid']['bid_user'] == $_SESSION['user_id'] && $auction['order_count'] == 0)
            {
                $auction['is_winner'] = 1;
            }
        }

        $auction['s_user_id'] = $_SESSION['user_id'];
        
        if($auction['status_no'] < 2)
        {
            $auction_list['under_way'][] = $auction;
        }
        else
        {
            $auction_list['finished'][] = $auction;
        }
    }
    
    if($auction_list['under_way']){
        $auction_list = @array_merge($auction_list['under_way'], $auction_list['finished']);
    }else{
        $auction_list = $auction_list['finished'];
    }
    
    //get_print_r($auction_list);
    return $auction_list;
}

/**
 * 获得指定分类下的推荐商品
 *
 * @access  public
 * @param   string      $type       推荐类型，可以是 best, new, hot, promote
 * @param   string      $cats       分类的ID
 * @param   integer     $min        商品积分下限
 * @param   integer     $max        商品积分上限
 * @param   string      $ext        商品扩展查询
 * @return  array
 */
function get_exchange_recommend_goods($type = '', $cats = '', $min =0,  $max = 0, $ext)
{
    $price_where = ($min > 0) ? " AND g.shop_price >= $min " : '';
    $price_where .= ($max > 0) ? " AND g.shop_price <= $max " : '';

    $now = gmtime();
    $sql =  'SELECT g.goods_id, g.goods_name, g.goods_name_style, ' .
            'g.goods_brief, g.goods_thumb, goods_img, b.brand_name, ' .
            
            'ga.act_name, ga.act_id, ga.ext_info, ga.start_time, ga.start_time, ga.end_time '.
            
            'FROM ' . $GLOBALS['ecs']->table('goods_activity') . ' AS ga ' .
            'LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . ' AS g ON g.goods_id = ga.goods_id ' .
            'LEFT JOIN ' . $GLOBALS['ecs']->table('brand') . ' AS b ON b.brand_id = g.brand_id ' .
            "WHERE ga.act_type = '" . GAT_AUCTION . "' " .
            "AND ga.start_time <= '$now' AND ga.review_status = 3 AND ga.end_time >= '$now' AND ga.is_finished < 2 " . $price_where . $ext;
   
    $num = 0;
    $type2lib = array('best'=>'auction_best', 'new'=>'auction_new', 'hot'=>'auction_hot');
    $num = get_library_number($type2lib[$type], 'auction_list');
    
    switch ($type)
    {
        case 'best':
            $sql .= ' AND ga.is_best = 1';
            break;
        case 'new':
            $sql .= ' AND ga.is_new = 1';
            break;
        case 'hot':
            $sql .= ' AND ga.is_hot = 1';
            break;
    }

    if (!empty($cats))
    {
        //$sql .= " AND (" . $cats . " OR " . get_extension_goods($cats) .")";
    }
    $order_type = $GLOBALS['_CFG']['recommend_order'];
    $sql .= ($order_type == 0) ? ' ORDER BY g.sort_order, g.last_update DESC' : ' ORDER BY RAND()';
    
    $res = $GLOBALS['db']->selectLimit($sql, $num);

    $idx = 0;
    $auction = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $auction[$idx]['id']                = $row['goods_id'];
        $auction[$idx]['name']              = $row['goods_name'];
        $auction[$idx]['brief']             = $row['goods_brief'];
        $auction[$idx]['brand_name']        = $row['brand_name'];
        $auction[$idx]['short_name']        = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
                                                sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
        $auction[$idx]['exchange_integral'] = $row['exchange_integral'];
        $auction[$idx]['thumb']             = get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $auction[$idx]['goods_img']         = get_image_path($row['goods_id'], $row['goods_img']);
        $auction[$idx]['url']               = build_uri('auction', array('auid'=>$row['act_id'], $row['act_name']));
        
        $auction[$idx]['format_start_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['start_time']);
        $auction[$idx]['format_end_time']   = local_date($GLOBALS['_CFG']['time_format'], $row['end_time']);
        
        $ext_info = unserialize($row['ext_info']);
        $auction_info = array_merge($row, $ext_info);
        $auction[$idx]['auction'] = $auction_info;
        $auction[$idx]['status_no'] = auction_status($auction_info);      
        $auction[$idx]['start_price'] = price_format($auction_info['start_price']);      
        $auction[$idx]['count'] = auction_log($row['act_id'], 1);

        $auction[$idx]['short_style_name']  = add_style($auction[$idx]['short_name'], $row['goods_name_style']);
        $idx++;
    }
    
    //get_print_r($auction);
    return $auction;
}

?>