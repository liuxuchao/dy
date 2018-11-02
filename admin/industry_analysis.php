<?php

/**
 * dsc 销售统计管理
 * ============================================================================
 * * 版权所有 2005-2016 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: guest_stats.php 17217 2018-04-02 06:29:08Z liubo $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'includes/lib_statistical.php');
require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/' .ADMIN_PATH. '/statistic.php');

/* act操作项的初始化 */
if (empty($_REQUEST['act']))
{
    $_REQUEST['act'] = 'list';
}
else
{
    $_REQUEST['act'] = trim($_REQUEST['act']);
}

/* 时间参数 */
if (isset($_REQUEST['start_date']) && !empty($_REQUEST['end_date']))
{
    $start_date = local_strtotime($_REQUEST['start_date']);
    $end_date   = local_strtotime($_REQUEST['end_date']);
    if ($start_date == $end_date)
    {
        $end_date = $start_date + 86400;
    }
}
else
{
    $today      = local_strtotime(local_date('Y-m-d'));
    $start_date = $today - 86400 * 6;
    $end_date   = $today + 86400;
}

$smarty->assign('start_date',    local_date('Y-m-d H:i:s', $start_date));
$smarty->assign('end_date',      local_date('Y-m-d H:i:s', $end_date));
$smarty->assign('main_category', cat_list());

/*------------------------------------------------------ */
//-- 账单统计管理
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    $order_list = industry_analysis();
    
    $smarty->assign('full_page',    1);
    $smarty->assign('order_list',   $order_list['orders']);
    $smarty->assign('filter',       $order_list['filter']);
    $smarty->assign('record_count', $order_list['record_count']);
    $smarty->assign('page_count',   $order_list['page_count']);

    /* 显示模板 */
    assign_query_info();

    $smarty->assign('ur_here', $_LANG['04_industry_analysis']);
    $smarty->display('industry_analysis.dwt');
}

/*------------------------------------------------------ */
//-- 店铺销售查询
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'query')
{
    $order_list = industry_analysis();

    $smarty->assign('order_list',   $order_list['orders']);
    $smarty->assign('filter',       $order_list['filter']);
    $smarty->assign('record_count', $order_list['record_count']);
    $smarty->assign('page_count',   $order_list['page_count']);
    $sort_flag = sort_flag($order_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('industry_analysis.dwt'), '', array('filter' => $order_list['filter'], 'page_count' => $order_list['page_count']));
}

/*------------------------------------------------------ */
//-- 异步
/*------------------------------------------------------ */
else if($_REQUEST['act'] == 'get_chart_data')
{
    $search_data = array();
    $search_data['type'] = empty($_REQUEST['type'])? '' : trim($_REQUEST['type']);
    $chart_data = get_statistical_industry_analysis($search_data);
    make_json_result($chart_data);
}

/*------------------------------------------------------ */
//-- 导出
/*------------------------------------------------------ */
else if($_REQUEST['act'] == 'download')
{ 
    $_GET['uselastfilter'] = 1;
    $order_list = industry_analysis();
    $tdata = $order_list['orders'];
    $thead = array($_LANG['03_category_manage'], $_LANG['sale_money'], $_LANG['effective_sale_money'], $_LANG['total_quantity'], $_LANG['effective_quantity'], $_LANG['goods_total_num'], $_LANG['effective_goods_num'], $_LANG['not_sale_money_goods_num'], $_LANG['order_user_total']);
    $tbody = array('cat_name', 'goods_amount', 'valid_goods_amount', 'order_num', 'valid_num', 'goods_num', 'order_goods_num', 'no_order_goods_num', 'user_num');

    $config = array(
        'filename' => $_LANG['04_industry_analysis'],
        'thead' => $thead,
        'tbody' => $tbody,
        'tdata' => $tdata
    );
    list_download($config);
}
?>