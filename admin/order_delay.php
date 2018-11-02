<?php

/**
 * ECSHOP 延迟收货
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: users.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

/*------------------------------------------------------ */
//-- 延迟收货申请列表
/*------------------------------------------------------ */
$exc   = new exchange($ecs->table("order_delayed"), $db, 'delayed_id', 'apply_day','order_id');//
if ($_REQUEST['act'] == 'list')
{
    /* 检查权限 */
    admin_priv('order_delayed');
    
    $order_delay_list = get_order_delayed_list();
    $smarty->assign('ur_here',$_LANG['order_delay_apply']);
    $smarty->assign('order_delay_list',    $order_delay_list['order_delay_list']);
    $smarty->assign('filter',       $order_delay_list['filter']);
    $smarty->assign('record_count', $order_delay_list['record_count']);
    $smarty->assign('page_count',   $order_delay_list['page_count']);
    $smarty->assign('full_page',    1);

    assign_query_info();
    $smarty->display('order_delayed_list.dwt');
}

/*------------------------------------------------------ */
//-- ajax
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    //检查权限
    check_authz_json('order_delayed');
	
    $order_delay_list = get_order_delayed_list();

//    print_arr($order_delay_list);
    $smarty->assign('order_delay_list', $order_delay_list['order_delay_list']);
    $smarty->assign('filter',       $order_delay_list['filter']);
    $smarty->assign('record_count', $order_delay_list['record_count']);
    $smarty->assign('page_count',   $order_delay_list['page_count']);

    $sort_flag  = sort_flag($order_delay_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('order_delayed_list.dwt'), '', array('filter' => $order_delay_list['filter'], 'page_count' => $order_delay_list['page_count']));
}
/*------------------------------------------------------ */
//-- 批量操作 延迟收货
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'batch')
{
    /* 检查权限 */
    admin_priv('order_delayed');
    
    
    if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes']))
    {
        sys_msg($_LANG['not_select_data'], 1);
    }
    $delay_id_arr = !empty($_POST['checkboxes']) ? $_POST['checkboxes'] : array();
    $review_status = !empty($_POST['review_status']) ? intval($_POST['review_status']) : 0;
    if (isset($_POST['type']))
    {
        // 删除
        if ($_POST['type'] == 'batch_remove')
        {
            $sql = "DELETE FROM " . $ecs->table('order_delayed') .
            " WHERE delayed_id " . db_create_in($delay_id_arr);
    
            if($db->query($sql))
            {
                $lnk[] = array('text' => $_LANG['back_list'], 'href' => 'order_delay.php?act=list');
                sys_msg($_LANG['remove_delay_info_success'], 0, $lnk);
            }
            /* 记录日志 */
            admin_log('', 'batch_trash', 'users_real');
        }
        
        // 审核
        elseif ($_POST['type'] == 'review_to')
        {
            // review_status = 0未审核 1审核通过 2审核未通过
            
            // 查询是否有已审核的订单
            $sql = "SELECT od.review_status,od.apply_day,o.order_sn ,od.delayed_id FROM " . $GLOBALS['ecs']->table('order_delayed') . "AS od"
                    . " LEFT JOIN " .$ecs->table('order_info'). "AS o ON od.order_id = o.order_id WHERE od.delayed_id " . db_create_in($delay_id_arr);
            $ald_review = $GLOBALS['db']->getAll($sql);
            $msj_order = '';
            foreach ($ald_review as $key => $value)
            {
                //判断是否审核通过
                if ($value['review_status'] > 0)
                {
                    $id_key = array_search($value['delayed_id'], $delay_id_arr);
                    unset($delay_id_arr[$id_key]);
                }
                //判断是否设置天数
                if($value['apply_day'] == 0 && $review_status == 1){
                    if($msj_order){
                        $msj_order .= "," . $value['order_sn'];
                    }else{
                        $msj_order = $value['order_sn'];
                    }
                    $id_key = array_search($value['delayed_id'], $delay_id_arr);
                    unset($delay_id_arr[$id_key]);
                }
            }
            
            $time = gmtime();
            
            $sql = "UPDATE " . $ecs->table('order_delayed') ." SET review_status = '$review_status', review_time = '$time', review_admin = '$_SESSION[admin_id]' "
                    . " WHERE delayed_id " . db_create_in($delay_id_arr);
            
            if($db->query($sql))
            {
                // 更新订单表的确认收货天数
                $sql = "SELECT order_id, apply_day FROM " . $GLOBALS['ecs']->table('order_delayed') ." WHERE delayed_id " . db_create_in($delay_id_arr);
                $order_id_list = $GLOBALS['db']->getAll($sql);
                foreach ($order_id_list as $key => $value)
                {
                    $sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') . "SET auto_delivery_time=auto_delivery_time+'$value[apply_day]' WHERE order_id='$value[order_id]'";
                    $GLOBALS['db']->query($sql);
                }
                
                $lnk[] = array('text' => $_LANG['back'], 'href' => 'order_delay.php?act=list');
                $message = $_LANG['order_delay_set_success'];
                if($msj_order){
                    $message = $message . $_LANG['order_set_info_one'] . $msj_order . $_LANG['order_set_info_two'];
                }
                sys_msg($message, 0, $lnk);
            }
        }
    }
}
/*------------------------------------------------------ */
//-- 修改申请天数
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_apply_day')
{
    check_authz_json('order_delayed');

    $id   = intval($_POST['id']);
    $val = json_str_iconv(trim($_POST['val']));

    if ($exc->edit("apply_day = '$val'", $id))
    {
        clear_cache_files();
        make_json_result(stripslashes($val));
    }
}
/*------------------------------------------------------ */
//-- 投诉设置
/*------------------------------------------------------ */
elseif($_REQUEST['act'] =='complaint_conf')
{
    //卖场 start
    if($adminru['rs_id'] > 0){
        $url = "order_delay.php?act=list";
        ecs_header("Location: $url\n");
    }
    //卖场 end
    
    admin_priv('order_delayed');
    require_once(ROOT_PATH . 'languages/' .$_CFG['lang'] .'/' .ADMIN_PATH. '/shop_config.php');
    $smarty->assign("ur_here",$_LANG['order_delay_conf']);
    $smarty->assign('action_link',  array('text' => $_LANG['order_delay_apply'], 'href' => 'order_delay.php?act=list'));
    $smarty->assign('action_link2',  array('text' => $_LANG['order_delay_conf'], 'href' => 'order_delay.php?act=complaint_conf'));
    
    $order_delay = get_up_settings('order_delay');
    $smarty->assign('report_conf',   $order_delay);
    
    $smarty->assign("act_type",$_REQUEST['act']);
    $smarty->assign('conf_type','order_delay');
    assign_query_info();
    $smarty->display('goods_report_conf.dwt');
}

?>