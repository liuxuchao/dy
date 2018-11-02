<?php

/**
 * ECSHOP 购物流程
 * ============================================================================
 * 版权所有 2005-2016 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: zblikai $
 * $Id: flow.php 15632 2009-02-20 03:58:31Z zblikai $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

require(ROOT_PATH . '/includes/lib_area.php');  //ecmoban模板堂 --zhuo

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
//正则去掉js代码
$preg = "/<script[\s\S]*?<\/script>/i";
$id  = isset($_REQUEST['id']) ? strtolower($_REQUEST['id']) : 0;
$id  =!empty($id) ? preg_replace($preg,"",stripslashes($id)): 0;

if (empty($id)) {
    /* 如果ID为0，则返回首页 */
    ecs_header("Location: ./\n");
    exit;
}

$goods_id = intval($id);
$cache_id = sprintf('%X', crc32($goods_id . '-' . $_SESSION['user_rank'] . '-' . $_CFG['lang']));

$warehouse_other = [
    'province_id' => $province_id,
    'city_id' => $city_id
];
$warehouse_area_info = get_warehouse_area_info($warehouse_other);

$region_id = $warehouse_area_info['region_id'];
$area_id = $warehouse_area_info['area_id'];
$area_city = $warehouse_area_info['city_id'];

$history_goods = get_history_goods($goods_id, $region_id, $area_id, $area_city);
$smarty->assign('history_goods',       $history_goods);                                   // 商品浏览历史

$goodsInfo = get_goods_info($goods_id, $region_id, $area_id, $area_city);
$goodsInfo['goods_price'] = price_format($goodsInfo['goods_price']);

//预售商品
$sql = "SELECT * FROM " . $GLOBALS['ecs']->table('presale_activity') . " WHERE goods_id = '$goods_id'";
$presale = $GLOBALS['db']->getAll($sql);
if ($presale) {
    foreach ($presale AS $row) {
        $goodsInfo['goods_url'] = build_uri('presale', array('act' => 'view', 'presaleid' => $row['act_id']));
    }
    $smarty->assign('is_presale', $presale);
}
$smarty->assign('goodsInfo',  $goodsInfo);

//评分 start
$mc_all = ments_count_all($goods_id);       //总条数
$mc_one = ments_count_rank_num($goods_id,1);		//一颗星
$mc_two = ments_count_rank_num($goods_id,2);	    //两颗星	
$mc_three = ments_count_rank_num($goods_id,3);   	//三颗星
$mc_four = ments_count_rank_num($goods_id,4);		//四颗星
$mc_five = ments_count_rank_num($goods_id,5);		//五颗星
$comment_all = get_conments_stars($mc_all,$mc_one,$mc_two,$mc_three,$mc_four,$mc_five);

$smarty->assign('comment_all',  $comment_all); 

if (!$smarty->is_cached('category_discuss.dwt', $cache_id))
{
    if (defined('THEME_EXTENSION')) {
        $smarty->assign('user_info', get_user_default($_SESSION['user_id']));
        $goods = get_goods_info($goods_id);

        if (defined('THEME_EXTENSION')) {
            //是否收藏店铺
            $sql = "SELECT rec_id FROM " . $ecs->table('collect_store') . " WHERE user_id = '" . $_SESSION['user_id'] . "' AND ru_id = '$goods[user_id]' "; //by kong 
            $rec_id = $db->getOne($sql);
            if ($rec_id > 0) {
                $goods['error'] = '1';
            } else {
                $goods['error'] = '2';
            }
        }

        $smarty->assign('goods', $goods);

        if ($goods['user_id'] > 0) {
            $merchants_goods_comment = get_merchants_goods_comment($goods['user_id']); //商家所有商品评分类型汇总
            $smarty->assign('merch_cmt', $merchants_goods_comment);
        }

        if ($GLOBALS['_CFG']['customer_service'] == 0) {
            $goods_user_id = 0;
        } else {
            $goods_user_id = $goods['user_id'];
        }

        $basic_info = get_shop_info_content($goods_user_id);

        /*  @author-bylu 判断当前商家是否允许"在线客服" start  */
        $shop_information = get_shop_name($goods_user_id);
        //判断当前商家是平台,还是入驻商家 bylu
        if ($goods_user_id == 0) {
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
        $smarty->assign('im_user_id', 'dsc' . $_SESSION['user_id']); //登入用户ID;
        /*  @author-bylu  end  */
    }
    if ($db->getOne(" SELECT rec_id FROM " . $ecs->table('collect_store') . " WHERE user_id = 'user_id' AND ru_id = 'goods_user_id' ")) {
        $smarty->assign('is_collected', true);
    }

    $smarty->assign('goods_id', $goods_id);

    assign_template();
    $position = assign_ur_here($goodsInfo['cat_id'], $goodsInfo['goods_name'], array(), '', $goodsInfo['user_id']);
    $smarty->assign('page_title', $position['title']);    // 页面标题
    $smarty->assign('ur_here', $position['ur_here']);  // 当前位置

    if (!defined('THEME_EXTENSION')) {
        $categories_pro = get_category_tree_leve_one();
        $smarty->assign('categories_pro', $categories_pro); // 分类树加强版
    }

    /* meta information */
    $smarty->assign('keywords', htmlspecialchars($_CFG['shop_keywords']));
    $smarty->assign('description', htmlspecialchars($_CFG['shop_desc']));
    $smarty->assign('flash_theme', $_CFG['flash_theme']);  // Flash轮播图片模板

    $smarty->assign('feed_url', ($_CFG['rewrite'] == 1) ? 'feed.xml' : 'feed.php'); // RSS URL

    $smarty->assign('helps', get_shop_help());       // 网店帮助

    /* 验证码相关设置 */
    if ((intval($_CFG['captcha']) & CAPTCHA_COMMENT) && gd_version() > 0) {
        $smarty->assign('enabled_captcha', 1);
        $smarty->assign('rand', mt_rand());
    }

    $smarty->assign('shop_notice', $_CFG['shop_notice']);       // 商店公告
}

$discuss_list = get_discuss_all_list($goods_id);
$smarty->assign('discuss_list',       $discuss_list);           

$all_count = get_discuss_type_count($goods_id); //帖子总数
$t_count = get_discuss_type_count($goods_id, 1); //讨论帖总数
$w_count = get_discuss_type_count($goods_id, 2); //问答帖总数
$q_count = get_discuss_type_count($goods_id, 3); //圈子帖总数
$s_count = get_commentImg_count($goods_id); //晒单帖总数
$all_count = $t_count + $w_count + $q_count + $s_count; //帖子总数

$smarty->assign('all_count',       $all_count);   
$smarty->assign('t_count',       $t_count);    
$smarty->assign('w_count',       $w_count);    
$smarty->assign('q_count',       $q_count);    
$smarty->assign('s_count',       $s_count);  

//热门话题
$discuss_hot = get_discuss_all_list($goods_id, 0, 1, 10, 0, 'dis_browse_num');
$smarty->assign('hot_list',       $discuss_hot);    

$smarty->assign('user_id',       $user_id);  
$smarty->display('category_discuss.dwt', $cache_id);

/*------------------------------------------------------ */
//-- PRIVATE FUNCTIONS
/*------------------------------------------------------ */

?>