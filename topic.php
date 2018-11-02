<?php

/**
 * ECSHOP 专题前台
 * ============================================================================
 * * 版权所有 2005-2016 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * @author:     webboy <laupeng@163.com>
 * @version:    v2.1
 * ---------------------------------------------
 */
define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require(ROOT_PATH . '/includes/lib_visual.php');
if ((DEBUG_MODE & 2) != 2) {
    $smarty->caching = true;
}

require(ROOT_PATH . '/includes/lib_area.php');  //ecmoban模板堂 --zhuo

$warehouse_other = [
    'province_id' => $province_id,
    'city_id' => $city_id
];
$warehouse_area_info = get_warehouse_area_info($warehouse_other);

$region_id = $warehouse_area_info['region_id'];
$area_id = $warehouse_area_info['area_id'];
$area_city = $warehouse_area_info['city_id'];

$topic_id = empty($_REQUEST['topic_id']) ? 0 : intval($_REQUEST['topic_id']);
$preview = !empty($_REQUEST['preview'])  ? $_REQUEST['preview'] : 0;
$where = '';
if($preview != 1){
    $where = "AND  " . gmtime() . " >= start_time AND " . gmtime() . "<= end_time AND review_status = 3";
}
$sql = "SELECT topic_id, user_id FROM " . $ecs->table('topic') .
        "WHERE topic_id = '$topic_id'".$where ;

$topic = $db->getRow($sql);
if (empty($topic)) {
    /* 如果没有找到任何记录则跳回到首页 */
    ecs_header("Location: ./\n");
    exit;
}

/**
 * 专题可视化
 * 下载OSS模板文件
 */
get_down_topictemplates($topic['topic_id'], $topic['user_id']);

//获取页面内容
$pc_page['tem'] = "topic_" . $topic_id;
$filename = ROOT_PATH . 'data/topic' . '/topic_' . $topic['user_id'] . "/" . $pc_page['tem'] ;
if($preview == 1){
    $preview_dir = ROOT_PATH . 'data/topic' . '/topic_' . $topic['user_id'] . "/" . $pc_page['tem'] . "/temp" ;
    if(is_dir($preview_dir)){
        $filename = $preview_dir;
    }
}
$pc_page['out'] = get_html_file($filename."/pc_html.php");
$nav_page = get_html_file($filename."/nav_html.php");
 /*重写图片链接*/
$pc_page['out'] = str_replace('../data/gallery_album/',"data/gallery_album/",$pc_page['out'],$i);
$pc_page['out'] = str_replace('../data/seller_templates/',"data/seller_templates/",$pc_page['out'],$i);
$pc_page['out'] = str_replace('../data/topic/',"data/topic/",$pc_page['out'],$i);

//OSS文件存储ecmoban模板堂 --zhuo start
if ($GLOBALS['_CFG']['open_oss'] == 1) {
    $bucket_info = get_bucket_info();
    $endpoint = $bucket_info['endpoint'];
} else {
    $endpoint = !empty($GLOBALS['_CFG']['site_domain']) ? $GLOBALS['_CFG']['site_domain'] : '';
}

if ($pc_page['out'] && $endpoint) {
    $desc_preg = get_goods_desc_images_preg($endpoint, $pc_page['out']);
    $pc_page['out'] = $desc_preg['goods_desc'];
}
//OSS文件存储ecmoban模板堂 --zhuo end

$sql = "SELECT * FROM " . $ecs->table('topic') . " WHERE topic_id = '$topic_id'";

$topic = $db->getRow($sql);

/* 模板赋值 */
assign_template();
$position = assign_ur_here(0, $topic['title']);
$smarty->assign('page_title', $position['title']);       // 页面标题
//$smarty->assign('ur_here',          $position['ur_here'] . '> ' . $topic['title']);     // 当前位置 remove by wu
$smarty->assign('ur_here', $position['ur_here']);     // 当前位置
$smarty->assign('helps', get_shop_help());              // 网店帮助

$smarty->assign('show_marketprice', $_CFG['show_marketprice']);
$smarty->assign('sort_goods_arr', $sort_goods_arr);          // 商品列表
$smarty->assign('topic', $topic);                   // 专题信息
$smarty->assign('keywords', $topic['keywords']);       // 专题信息
$smarty->assign('description', $topic['description']);    // 专题信息
$smarty->assign('site_domain', $_CFG['site_domain']);  //网站域名


$categories_pro = get_category_tree_leve_one();
$smarty->assign('categories_pro', $categories_pro); // 分类树加强版

$smarty->assign("pc_page", $pc_page);

$smarty->assign('warehouse_id',       $region_id);
$smarty->assign('area_id',       $area_id);
$smarty->assign('nav_page',       $nav_page);
/* 显示模板 */
$smarty->display("topic.dwt");
?>