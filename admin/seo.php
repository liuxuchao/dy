<?php

/**
 * ECSHOP 管理SEO程序文件
 * ============================================================================
 * * 版权所有 2005-2016 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author $
 * $Id $
 */
define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

/* ------------------------------------------------------ */
//-- 首页
/* ------------------------------------------------------ */
if ($_REQUEST['act'] == 'index') {
    admin_priv('seo');
    $is_index = "index";
    $get_seo = get_seo();
    $smarty->assign('seo', $get_seo);
    $smarty->assign('full_page', 1);
    $smarty->assign('is_index', $is_index);
    $smarty->assign('menu_select', array('action' => '06_seo', 'current' => 'index'));
    $smarty->display('seo.dwt');
} 

/* ------------------------------------------------------ */
//-- 团购
/* ------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'group') 
{
    admin_priv('seo');
    $is_group = "group";
    $get_seo = get_seo();
    $smarty->assign('seo', $get_seo);
    $smarty->assign('full_page', 1);
    $smarty->assign('is_group', $is_group);
    $smarty->assign('menu_select', array('action' => '06_seo', 'current' => 'group'));
    $smarty->display('seo.dwt');
} 

/* ------------------------------------------------------ */
//-- 品牌
/* ------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'brand') 
{
    admin_priv('seo');
    $is_brand = "brand";
    $smarty->assign('full_page', 1);
    $get_seo = get_seo();
    $smarty->assign('seo', $get_seo);
    $smarty->assign('is_brand', $is_brand);
    $smarty->assign('menu_select', array('action' => '06_seo', 'current' => 'brand'));
    $smarty->display('seo.dwt');
} 

/* ------------------------------------------------------ */
//-- 积分商城
/* ------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'exchage') 
{
    admin_priv('seo');
    $is_exchage = "exchage";
    $smarty->assign('full_page', 1);
    $get_seo = get_seo();
    $smarty->assign('seo', $get_seo);
    $smarty->assign('is_exchage', $is_exchage);
    $smarty->assign('menu_select', array('action' => '06_seo', 'current' => 'exchage'));
    $smarty->display('seo.dwt');
} 

/* ------------------------------------------------------ */
//-- 文章
/* ------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'article') 
{
    admin_priv('seo');
    $is_article = "article";
    $smarty->assign('full_page', 1);
    $get_seo = get_seo();
    $smarty->assign('seo', $get_seo);
    $smarty->assign('is_article', $is_article);
    $smarty->assign('menu_select', array('action' => '06_seo', 'current' => 'article'));
    $smarty->display('seo.dwt');
} 

/* ------------------------------------------------------ */
//-- 店铺
/* ------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'store') 
{
    admin_priv('seo');
    $is_store = "store";
    $smarty->assign('full_page', 1);
    $get_seo = get_seo();
    $smarty->assign('seo', $get_seo);
    $smarty->assign('is_store', $is_store);
    $smarty->assign('menu_select', array('action' => '06_seo', 'current' => 'store'));
    $smarty->display('seo.dwt');
} 

/* ------------------------------------------------------ */
//-- 商品
/* ------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'goods') 
{
    admin_priv('seo');
    $is_goods = "goods";
    $smarty->assign('full_page', 1);
    $get_seo = get_seo();
    $smarty->assign('seo', $get_seo);
    $smarty->assign('is_goods', $is_goods);
    $smarty->assign('menu_select', array('action' => '06_seo', 'current' => 'goods'));
    $smarty->display('seo.dwt');
} 

/* ------------------------------------------------------ */
//-- 商品分类
/* ------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'goods_cat') 
{
    admin_priv('seo');
    $smarty->assign('filter_category_list', get_category_list());
    $is_goods_cat = "goods_cat";
    $smarty->assign('full_page', 1);
    $smarty->assign('seo', $get_seo);
    $smarty->assign('is_goods_cat', $is_goods_cat);
    $smarty->assign('menu_select', array('action' => '06_seo', 'current' => 'goods_cat'));
    $smarty->display('seo.dwt');
} 

/* ------------------------------------------------------ */
//-- 信息提交
/* ------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'setting') 
{
    $seo = empty($_POST['seo']) ? '' : $_POST['seo'];
    if (is_array($seo)) {
        foreach ($seo as $key => $value) {
            $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('seo'), $value, 'UPDATE', "type='$key'");
            if ($key == 'group_content') {
                $key = 'group';
            } elseif ($key == 'brand_list') {
                $key = 'brand';
            } elseif ($key == 'change_content' || $key == 'change') {
                $key = 'exchage';
            } elseif ($key == 'article_content') {
                $key = 'article';
            } elseif ($key == 'shop') {
                $key = 'store';
            }
            $url = '?act=' . $key;
        }
    }

    $links = array(
        array('text' => $_LANG['back_list'], 'href' => $url),
    );
	
    clear_cache_files();
    sys_msg($_LANG['update_Success'], 0, $links);
} 

/* ------------------------------------------------------ */
//-- 分类信息提交
/* ------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'cate_setting') 
{

    $categroy = array();
    $categroy['category_id'] = empty($_POST['category_id']) ? 0 : intval($_POST['category_id']);
    $categroy['cate_title'] = empty($_POST['cate_title']) ? '' : $_POST['cate_title'];
    $categroy['cate_keywords'] = empty($_POST['cate_keywords']) ? '' : $_POST['cate_keywords'];
    $categroy['cate_description'] = empty($_POST['cate_description']) ? '' : $_POST['cate_description'];
    $result = $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('category'), $categroy, 'UPDATE', "cat_id=" . $categroy['category_id']);
    $links = array(
        array('text' => $_LANG['back_list'], 'href' => '?act=goods_cat'),
    );
	
    clear_cache_files();
    
    if ($result) {
        sys_msg($_LANG['update_Success'], 0, $links);
    } else {
        sys_msg($_LANG['Submit_fail'], 0, $links);
    }
}

function get_seo() {
    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('seo') . " WHERE 1";
    $res = $GLOBALS['db']->getAll($sql);
    if (is_array($res)) {
        foreach ($res as $value) {
            $seo[$value['type']] = $value;
        }
    }
    return $seo;
}

?>