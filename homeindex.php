<?php

/**
 * 商创 可视化首页文件
 * ============================================================================
 * * 版权所有 2005-2016 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: index.php 17217 2011-01-19 06:29:08Z liubo $
*/

if (!$smarty->is_cached('homeindex.dwt', $cache_id) || $preview == 1)
{
    assign_template();

    $position = assign_ur_here();
    $smarty->assign('ur_here', $position['ur_here']);  // 当前位置
    
    //获取seo start
    $seo = get_seo_words('index');
    foreach ($seo as $key => $value) {
        $seo[$key] = str_replace(array('{sitename}', '{key}', '{description}'), array($position['title'], $_CFG['shop_keywords'], $_CFG['shop_desc']), $value);
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

    $smarty->assign('flash_theme',     $_CFG['flash_theme']);  // Flash轮播图片模板

    $smarty->assign('feed_url',        ($_CFG['rewrite'] == 1) ? 'feed.xml' : 'feed.php'); // RSS URL

    $smarty->assign('warehouse_id',       $region_id);
    $smarty->assign('area_id',       $area_id);
    $smarty->assign('area_city',       $area_city);

    $smarty->assign('helps',           get_shop_help());       // 网店帮助

    /* 页面中的动态内容 */
    assign_dynamic('homeindex', $region_id, $area_id, $area_city);

    /* 重写图片链接 */
    $replace_data = array(
        'http://localhost/ecmoban_dsc2.0.5_20170518/',
        'http://localhost/ecmoban_dsc2.2.6_20170727/',
        'http://localhost/ecmoban_dsc2.3/',
        'http://localhost/dsc30/',
        'themes/ecmoban_dsc2017/'
    );

    //获取首页可视化模板
    $page = get_html_file($dir . "/pc_html.php");
    $nav_page = get_html_file($dir . '/nav_html.php');
    $topBanner = get_html_file($dir . '/topBanner.php');

    $topBanner = str_replace($replace_data, $ecs->url(), $topBanner);
    $page = str_replace($replace_data, $ecs->url(), $page);

    //OSS文件存储ecmoban模板堂 --zhuo start
    if ($GLOBALS['_CFG']['open_oss'] == 1) {
        $bucket_info = get_bucket_info();
        $endpoint = $bucket_info['endpoint'];
    } else {
        $endpoint = !empty($GLOBALS['_CFG']['site_domain']) ? $GLOBALS['_CFG']['site_domain'] : '';
    }

    if ($page && $endpoint) {
        $desc_preg = get_goods_desc_images_preg($endpoint, $page);
        $page = $desc_preg['goods_desc'];
    }
    if ($topBanner && $endpoint) {
        $desc_preg = get_goods_desc_images_preg($endpoint, $topBanner);
        $topBanner = $desc_preg['goods_desc'];
    }
    //OSS文件存储ecmoban模板堂 --zhuo end
    $user_id = !empty($_SESSION['user_id'])  ?  $_SESSION['user_id'] : 0;

    if (!defined('THEME_EXTENSION')) {
        $categories_pro = get_category_tree_leve_one();
        $smarty->assign('categories_pro', $categories_pro); // 分类树加强版
    }

    $pc_page['tem'] = $suffix;
    $smarty->assign('pc_page',       $pc_page);
    $smarty->assign('nav_page',       $nav_page);
    $smarty->assign('page',       $page);
    $smarty->assign('topBanner',       $topBanner);
    $smarty->assign('user_id',       $user_id);

    $smarty->assign('site_domain', $_CFG['site_domain']);

    $bg_image = getleft_attr("content", 0, $pc_page['tem'], $GLOBALS['_CFG']['template']);
    $smarty->assign('bg_image', $bg_image);
}

if ($preview == 1) {
    $smarty->display('homeindex.dwt');
} else {
    $smarty->display('homeindex.dwt', $cache_id);
}

