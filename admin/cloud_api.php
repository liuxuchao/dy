<?php

/**
 * 大商创 第三方服务 - 贡云
 * ===========================================================
 * * 版权所有 2005-2016 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ==========================================================
 * $Author: liubo $
 * $Id: cloud_api.php 17217 2011-01-19 06:29:08Z liubo $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

//默认
if (empty($_REQUEST['act']))
{
    die('Error');
}

//贡云信息
elseif ($_REQUEST['act'] == 'config')
{
    admin_priv('shop_config');

    $smarty->assign('ur_here', $_LANG['cloud_api']);
    $smarty->assign('form_act', 'cloud_update');

    $api_config = array();
    $api_config['client_id'] = get_table_date('shop_config', "code='cloud_client_id'", array('value'), 2);
    $api_config['appkey'] = get_table_date('shop_config', "code='cloud_appkey'", array('value'), 2);
	$api_config['cloud_dsc_appkey'] = get_table_date('shop_config', "code='cloud_dsc_appkey'", array('value'), 2);
    $smarty->assign('api_config', $api_config);

    assign_query_info();
    $smarty->display('cloud_api.dwt');
}

//贡云更新
elseif ($_REQUEST['act'] == 'cloud_update')
{
    admin_priv('shop_config');

    $client_id = empty($_REQUEST['client_id']) ? '' : trim($_REQUEST['client_id']);
    $appkey = empty($_REQUEST['appkey']) ? '' : trim($_REQUEST['appkey']);
    $cloud_dsc_appkey = empty($_REQUEST['cloud_dsc_appkey']) ? '' : trim($_REQUEST['cloud_dsc_appkey']);
    $sql = " UPDATE " . $GLOBALS['ecs']->table('shop_config') . " SET value = '$client_id' WHERE code = 'cloud_client_id' ";
    $GLOBALS['db']->query($sql);
    $sql = " UPDATE " . $GLOBALS['ecs']->table('shop_config') . " SET value = '$appkey' WHERE code = 'cloud_appkey' ";
    $GLOBALS['db']->query($sql);
    $sql = " UPDATE " . $GLOBALS['ecs']->table('shop_config') . " SET value = '$cloud_dsc_appkey' WHERE code = 'cloud_dsc_appkey' ";
    $GLOBALS['db']->query($sql);
    $link[] = array('text' => $_LANG['go_back'], 'href' => 'cloud_api.php?act=config');
    sys_msg($_LANG['save_success'], 0, $link);
}