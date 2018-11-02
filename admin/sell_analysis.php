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

/*------------------------------------------------------ */
//-- 销售量
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'sales_volume')
{
    /* 主子订单处理 */
    $no_main_order = no_main_order();
    $sql = " SELECT " . statistical_field_order_num() . " AS total_num FROM " . $GLOBALS['ecs']->table('order_info') . " AS o WHERE 1 " . $no_main_order;
    $total_num = $GLOBALS['db']->getOne($sql);
    $smarty->assign('total_num', $total_num);

    $smarty->assign('ur_here', $_LANG['sales_volume']);
    $smarty->display('sales_volume_stats.dwt');
}

/*------------------------------------------------------ */
//-- 销售额
/*------------------------------------------------------ */
else if($_REQUEST['act'] == 'sales_money')
{
    /* 主子订单处理 */
    $no_main_order = no_main_order();
    $sql = " SELECT " . statistical_field_sale_money() . " AS total_fee FROM " . $GLOBALS['ecs']->table('order_info') . " AS o WHERE 1 " . $no_main_order;
    $total_fee = $GLOBALS['db']->getOne($sql);
    $smarty->assign('total_fee', $total_fee);

    $smarty->assign('ur_here', $_LANG['sales_money']);
    $smarty->display('sales_money_stats.dwt');
}

/*------------------------------------------------------ */
//-- 账单统计异步
/*------------------------------------------------------ */
else if($_REQUEST['act'] == 'get_chart_data')
{
    $search_data = array();
    $search_data['start_date'] = $start_date;
    $search_data['end_date'] = $end_date;
    $search_data['type'] = empty($_REQUEST['type'])? 'volume' : trim($_REQUEST['type']);
    $chart_data = get_statistical_sale($search_data);
    make_json_result($chart_data);
}

/*------------------------------------------------------ */
//-- 订单统计
/*------------------------------------------------------ */
else if($_REQUEST['act'] == 'order_stats')
{
    $smarty->assign('ur_here', $_LANG['order_stats']);
    $smarty->display('sales_order_stats.dwt');
}

?>