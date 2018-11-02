<?php

/**
 * 商创网络 商家模板
 * ============================================================================
 * * 版权所有 2005-2016 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: visualhome.php 17217 2011-01-19 06:29:08Z liubo $
*/
define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require(ROOT_PATH . '/includes/lib_visual.php');

get_invalid_apply(1);//未支付模板订单失效处理

//商家模板列表
if($_REQUEST['act'] == 'list'){
    admin_priv('10_visual_editing');

    //页面赋值
    $smarty->assign('menu_select', array('action' => '17_merchants', 'current' => 'template_mall'));
    $smarty->assign("ur_here",$_LANG['template_mall']);
    $template_mall_list = template_mall_list();
    $smarty->assign('available_templates',  $template_mall_list['list']);
    $smarty->assign('filter',       $template_mall_list['filter']);
    $smarty->assign('record_count', $template_mall_list['record_count']);
    $smarty->assign('page_count',   $template_mall_list['page_count']);    
    
    $smarty->assign('template_type', 'seller');
    $smarty->assign('full_page', 1);
    $smarty->assign("act_type",$_REQUEST['act']);
    
    assign_query_info();
    $smarty->display("visualhome_list.dwt");
}
/*------------------------------------------------------ */
//-- 排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    $template_mall_list = template_mall_list();
    $smarty->assign('available_templates',  $template_mall_list['list']);
    $smarty->assign('filter',       $template_mall_list['filter']);
    $smarty->assign('record_count', $template_mall_list['record_count']);
    $smarty->assign('page_count',   $template_mall_list['page_count']);    
    $smarty->assign('template_type', 'seller');

    make_json_result($smarty->fetch('visualhome_list.dwt'), '',
        array('filter' => $template_mall_list['filter'], 'page_count' => $template_mall_list['page_count']));
}
//模板支付使用记录
if ($_REQUEST['act'] == 'template_apply_list') {
    //页面赋值
    $smarty->assign('menu_select', array('action' => '17_merchants', 'current' => 'template_apply_list'));
    $smarty->assign("ur_here",$_LANG['template_apply_list']);
    
    //获取商家列表
    $store_list = get_common_store_list();
    $smarty->assign('store_list',        $store_list);
    
    //获取数据
    $template_mall_list = get_template_apply_list();
    $smarty->assign('available_templates', $template_mall_list['list']);
    $smarty->assign('filter', $template_mall_list['filter']);
    $smarty->assign('record_count', $template_mall_list['record_count']);
    $smarty->assign('page_count', $template_mall_list['page_count']);

    $smarty->assign('full_page', 1);
    $smarty->assign("act_type", $_REQUEST['act']);

    assign_query_info();
    $smarty->display("template_apply_list.dwt");
}
/* ------------------------------------------------------ */
//-- 排序、分页、查询
/* ------------------------------------------------------ */ 
elseif ($_REQUEST['act'] == 'apply_query') {
    $template_mall_list = get_template_apply_list();
    $smarty->assign('available_templates', $template_mall_list['list']);
    $smarty->assign('filter', $template_mall_list['filter']);
    $smarty->assign('record_count', $template_mall_list['record_count']);
    $smarty->assign('page_count', $template_mall_list['page_count']);

    make_json_result($smarty->fetch('template_apply_list.dwt'), '', array('filter' => $template_mall_list['filter'], 'page_count' => $template_mall_list['page_count']));
}
/* ------------------------------------------------------ */
//-- 确认付款操作
/* ------------------------------------------------------ */ 
elseif($_REQUEST['act'] == 'confirm_operation'){
    $apply_id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    
    //获取订单信息
    $sql = "SELECT ru_id,temp_id,temp_code FROM".$GLOBALS['ecs']->table("seller_template_apply")."WHERE apply_id = '$apply_id'";
    $seller_template_apply = $GLOBALS['db']->getRow($sql);

    //导入已付款的模板
    $new_suffix = get_new_dirName($seller_template_apply['ru_id']);//获取新的模板
    Import_temp($seller_template_apply['temp_code'],$new_suffix,$seller_template_apply['ru_id']);

    //更新模板使用数量
    $sql = "UPDATE".$GLOBALS['ecs']->table('template_mall')."SET sales_volume = sales_volume+1 WHERE temp_id = '".$seller_template_apply['temp_id']."'";
    $GLOBALS['db']->query($sql);

     /*修改申请的支付状态 */
    $sql=" UPDATE ".$GLOBALS['ecs']->table('seller_template_apply')." SET pay_status = 1 ,pay_time = '".gmtime()."' , apply_status = 1 WHERE apply_id= '$apply_id'";
    $GLOBALS['db']->query($sql);
    
    /* 修改此次支付操作的状态为已付款 */
    $sql = "UPDATE " . $ecs->table('pay_log') . "SET is_paid = 1 WHERE order_id = '" . $apply_id . "' AND order_type = '" . PAY_APPLYTEMP . "'";
    $db->query($sql);
    $url = 'template_mall.php?act=apply_query&' . str_replace('act=confirm_operation', '', $_SERVER['QUERY_STRING']);
    ecs_header("Location: $url\n");
    exit;
}
//删除模板订单
elseif($_REQUEST['act'] == 'remove'){
    $apply_id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    $sql = "DELETE FROM".$ecs->table('seller_template_apply')."WHERE apply_id = '$apply_id' AND pay_status = 0";
    $db->query($sql);
    $url = 'template_mall.php?act=apply_query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);
    ecs_header("Location: $url\n");
    exit;
}