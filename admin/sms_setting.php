<?php

/**
 * ECSHOP 会员管理程序
 * ============================================================================
 * * 版权所有 2005-2016 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: users.php 17217 2011-01-19 06:29:08Z liubo $
 */
define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

/* 检查权限 */
admin_priv('sms_setting');

/* ------------------------------------------------------ */
//-- 店铺设置
/* ------------------------------------------------------ */ 
if ($_REQUEST['act'] == 'step_up') {

    require_once(ROOT_PATH . 'languages/' .$_CFG['lang'] .'/' .ADMIN_PATH. '/shop_config.php');
    
    $smarty->assign('ur_here', $_LANG['01_sms_setting']);
    
    $smarty->assign('menu_select', array('action' => '24_sms', 'current' => '01_sms_setting'));
    
    $group_list = get_up_settings('sms');
    $smarty->assign('group_list',   $group_list);
    
    assign_query_info();
    $smarty->display('sms_step_up.dwt');
}

?>