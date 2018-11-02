<?php

/**
 * ECSHOP 第三方服务
 * ===========================================================
 * * 版权所有 2005-2016 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ==========================================================
 * $Author: liubo $
 * $Id: tp_api.php 17217 2011-01-19 06:29:08Z liubo $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

$adminru = get_admin_ru_id();
if(!$adminru){
    $adminru['ru_id'] = get_store_ru_id();
}

//store_priv('');

//默认
if (empty($_REQUEST['act']))
{
	die('Error');
}

/*------------------------------------------------------ */
//-- 电子面单列表页面
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'order_print_setting')
{
    store_priv('order_print_setting');

    $smarty->assign('ur_here', $_LANG['order_print_setting']);
    $smarty->assign('action_link', array('text' => $_LANG['order_print_setting_add'], 'href' => 'tp_api.php?act=order_print_setting_add'));
    $smarty->assign('full_page', 1);

    $print_setting = get_order_print_setting($adminru['ru_id']);

    $smarty->assign('print_setting', $print_setting['list']);
    $smarty->assign('filter', $print_setting['filter']);
    $smarty->assign('record_count', $print_setting['record_count']);
    $smarty->assign('page_count', $print_setting['page_count']);

    $sort_flag = sort_flag($print_setting['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    assign_query_info();
    $smarty->display('order_print_setting.dwt');
}

/*------------------------------------------------------ */
//-- 排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'order_print_setting_query')
{
    check_authz_json('order_print_setting');

    $print_setting = get_order_print_setting($adminru['ru_id']);

    $smarty->assign('print_setting', $print_setting['list']);
    $smarty->assign('filter', $print_setting['filter']);
    $smarty->assign('record_count', $print_setting['record_count']);
    $smarty->assign('page_count', $print_setting['page_count']);

    $sort_flag = sort_flag($print_setting['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('order_print_setting.dwt'), '', array('filter' => $print_setting['filter'], 'page_count' => $print_setting['page_count']));
}

/*------------------------------------------------------ */
//-- 删除
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'order_print_setting_remove')
{
    check_authz_json('order_print_setting');

    $id = intval($_GET['id']);

    $exc = new exchange($ecs->table("order_print_setting"), $db, 'id', 'ru_id');
    $exc->drop($id);
    //clear_cache_files();

    $url = 'tp_api.php?act=order_print_setting_query&' . str_replace('act=order_print_setting_remove', '', $_SERVER['QUERY_STRING']);
    ecs_header("Location: $url\n");
    exit;
}

/*------------------------------------------------------ */
//-- 编辑打印机
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_order_printer')
{
    check_authz_json('order_print_setting');

    $id = intval($_POST['id']);
    $val = trim($_POST['val']);

    $sql = " UPDATE " . $ecs->table('order_print_setting') . " SET printer = '$val' WHERE ru_id = '{$adminru['ru_id']}' AND id = '$id' ";
    $db->query($sql);

    //clear_cache_files();
    make_json_result($val);
}

/*------------------------------------------------------ */
//-- 编辑宽度
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_print_width')
{
    check_authz_json('order_print_setting');

    $id = intval($_POST['id']);
    $val = trim($_POST['val']);

    $sql = " UPDATE " . $ecs->table('order_print_setting') . " SET width = '$val' WHERE ru_id = '{$adminru['ru_id']}' AND id = '$id' ";
    $db->query($sql);

    //clear_cache_files();
    make_json_result($val);
}

/*------------------------------------------------------ */
//-- 编辑打印机排序
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_sort_order')
{
    check_authz_json('order_print_setting');

    $id = intval($_POST['id']);
    $val = trim($_POST['val']);

    $sql = " UPDATE " . $ecs->table('order_print_setting') . " SET sort_order = '$val' WHERE ru_id = '{$adminru['ru_id']}' AND id = '$id' ";
    $db->query($sql);

    //clear_cache_files();
    make_json_result($val);
}

/*------------------------------------------------------ */
//-- 切换默认
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'toggle_order_is_default')
{
    check_authz_json('order_print_setting');

    $id = intval($_POST['id']);
    $val = intval($_POST['val']);

    $sql = " UPDATE " . $ecs->table('order_print_setting') . " SET is_default = '$val' WHERE ru_id = '{$adminru['ru_id']}' AND id = '$id' ";
    $db->query($sql);

    if ($val) {
        $sql = " UPDATE " . $ecs->table('order_print_setting') . " SET is_default = '0' WHERE ru_id = '{$adminru['ru_id']}' AND id <> '$id' ";
        $db->query($sql);
    }

    //clear_cache_files();
    make_json_result($val);
}

/*------------------------------------------------------ */
//-- 添加、编辑电子面单
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'order_print_setting_add' || $_REQUEST['act'] == 'order_print_setting_edit')
{
    store_priv('order_print_setting');

    $id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    $print_size = get_table_date('order_print_size', "1", array('*'), 1);
    $smarty->assign('print_size', $print_size);
    if ($id > 0) {
        $print_setting = get_table_date('order_print_setting', "id='$id'", array('*'));
        $smarty->assign('print_setting', $print_setting);
        $smarty->assign('ur_here', $_LANG['order_print_setting_edit']);
        $smarty->assign('form_action', 'order_print_setting_update');
    } else {
        $smarty->assign('ur_here', $_LANG['order_print_setting_add']);
        $smarty->assign('form_action', 'order_print_setting_insert');
    }
    $smarty->assign('action_link', array('text' => $_LANG['order_print_setting'], 'href' => 'tp_api.php?act=order_print_setting'));

    assign_query_info();
    $smarty->display('order_print_setting_info.dwt');
}

/*------------------------------------------------------ */
//-- 添加、编辑电子面单
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'order_print_setting_insert' || $_REQUEST['act'] == 'order_print_setting_update')
{
    store_priv('order_print_setting');

    $data = array();
    $id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    $data['ru_id'] = $adminru['ru_id'];
    $data['is_default'] = !empty($_REQUEST['is_default']) ? intval($_REQUEST['is_default']) : 0;
    $data['specification'] = !empty($_REQUEST['specification']) ? trim($_REQUEST['specification']) : '';
    $data['printer'] = !empty($_REQUEST['printer']) ? trim($_REQUEST['printer']) : '';
    $data['width'] = !empty($_REQUEST['width']) ? intval($_REQUEST['width']) : 0;
    if (empty($data['width'])) {
        $print_size = get_table_date('order_print_size', "specification='{$data['specification']}'", array('height', 'width'));
        $data['width'] = $print_size['width'];
    }

    /* 检查是否重复 */
    $sql = " SELECT id FROM " . $ecs->table('order_print_setting') . " WHERE ru_id = '{$adminru['ru_id']}' AND specification = '{$data['specification']}' AND id <> '$id' LIMIT 1 ";
    $is_only = $db->getOne($sql);
    if (!empty($is_only)) {
        sys_msg($_LANG['specification_exist'], 1);
    }
    /* 插入、更新 */
    if ($id > 0) {
        $db->autoExecute($ecs->table('order_print_setting'), $data, 'UPDATE', "id = '$id'");
        $msg = $_LANG['edit_success'];
    } else {
        $db->autoExecute($ecs->table('order_print_setting'), $data, 'INSERT');
        $id = $db->insert_id();
        $msg = $_LANG['add_success'];
    }
    /* 默认设置 */
    if ($data['is_default']) {
        $db->autoExecute($ecs->table('order_print_setting'), array('is_default' => 0), 'UPDATE', "id <> '$id'");
    }

    $link[] = array('text' => $_LANG['back_list'], 'href' => 'tp_api.php?act=order_print_setting');
    sys_msg($msg, 0, $link);
}

/*------------------------------------------------------ */
//-- 电子面单 by wu
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'order_print')
{
    /* 检查权限 */
    store_priv('order_manage');

    /* 打印数据 */
    $print_specification = get_table_date("order_print_setting", "ru_id='{$adminru['ru_id']}' AND is_default='1'", array("specification"), 2);
    if (empty($print_specification)) {
        $print_specification = get_table_date("order_print_setting", "ru_id='{$adminru['ru_id']}' ORDER BY sort_order, id", array("specification"), 2);
    }

    $print_size_info = get_table_date("order_print_size", "specification='$print_specification'", array("*"));
    $print_size_list = get_table_date("order_print_setting", "ru_id='{$adminru['ru_id']}' ORDER BY sort_order, id", array("*"), 1);
    $print_spec_info = get_table_date("order_print_setting", "specification='$print_specification'", array("*"));

    if (empty($print_size_list)) {
        $link[] = array('text' => '返回订单列表', 'href' => 'order.php?act=list');
        sys_msg($_LANG['no_print_setting'], 1, $link);
    }

    $smarty->assign('print_specification', $print_specification);
    $smarty->assign('print_size_info', $print_size_info);
    $smarty->assign('print_size_list', $print_size_list);
    $smarty->assign('print_spec_info', $print_spec_info);

    /* 订单数据 */
    $order_id = empty($_REQUEST['order_id']) ? 0 : intval($_REQUEST['order_id']);
    $order_sn = empty($_REQUEST['order_sn']) ? '' : trim($_REQUEST['order_sn']);
	$order_type = empty($_REQUEST['order_type']) ? 'order' : trim($_REQUEST['order_type']);
    $action_id = get_table_date('admin_action', "action_code='supply_and_demand'", array('action_id'), 2); //判断是否安装供求模块
	if($order_type == 'order' || empty($action_id)){
		$table = $GLOBALS['ecs']->table('order_info');
	}else{
		$table = $GLOBALS['ecs']->table('wholesale_order_info');
	}
    $order_ids = array();
    if (!empty($order_id)) {
        $order_ids[] = $order_id;
    }
    if (!empty($order_sn)) {
        $sql = " SELECT order_id FROM " . $table . " WHERE order_sn " . db_create_in($order_sn);
        $ids = $GLOBALS['db']->getCol($sql);
        $order_ids = array_merge($order_ids, $ids);
    }

    $web_url = $ecs->stores_url() . STORES_PATH . "/";
    $smarty->assign('web_url', $web_url);
	
	$smarty->assign('order_type', $order_type);
	
	$smarty->assign('shop_url',     $ecs->stores_url());
	
	$sql = "SELECT value FROM " . $GLOBALS['ecs']->table('shop_config') . " WHERE code = 'order_print_logo'";
	$order_print_logo = strstr($GLOBALS['db']->getOne($sql),"images");
	$smarty->assign('order_print_logo', $ecs->stores_url() . ADMIN_PATH . '/' . $order_print_logo);

    $part_html = array();
    foreach ($order_ids as $order_id) {
        $order_info = get_print_order_info($order_id,$order_type);
        $smarty->assign('order_info', $order_info);
		$smarty->assign('order_sn', $order_info['order_sn']);
        $part_html[] = $smarty->fetch('library/order_print_part.lbi');
    }
    $smarty->assign('part_html', $part_html);

    /* 模板赋值 */
    //$smarty->assign('ur_here', $_LANG['order_print']);

    /* 显示模板 */
    assign_query_info();
    $smarty->display('order_print.dwt');
}

/*------------------------------------------------------ */
//-- 切换电子面单 by wu
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'change_order_print')
{
    /* 检查权限 */
    store_priv('order_manage');

    /* 打印数据 */
    $print_specification = empty($_REQUEST['specification']) ? '' : trim($_REQUEST['specification']);

    $print_size_info = get_table_date("order_print_size", "specification='$print_specification'", array("*"));
    $print_size_list = get_table_date("order_print_setting", "ru_id='{$adminru['ru_id']}' ORDER BY sort_order, id", array("*"), 1);
    $print_spec_info = get_table_date("order_print_setting", "specification='$print_specification'", array("*"));

    $smarty->assign('print_specification', $print_specification);
    $smarty->assign('print_size_info', $print_size_info);
    $smarty->assign('print_size_list', $print_size_list);
    $smarty->assign('print_spec_info', $print_spec_info);

    /* 订单数据 */
    $order_id = empty($_REQUEST['order_id']) ? 0 : intval($_REQUEST['order_id']);
    $order_sn = empty($_REQUEST['order_sn']) ? '' : trim($_REQUEST['order_sn']);
	$order_type = empty($_REQUEST['order_type']) ? 'order' : trim($_REQUEST['order_type']);
    $action_id = get_table_date('admin_action', "action_code='supply_and_demand'", array('action_id'), 2); //判断是否安装供求模块
	if($order_type == 'order' || empty($action_id)){
		$table = $GLOBALS['ecs']->table('order_info');
	}else{
		$table = $GLOBALS['ecs']->table('wholesale_order_info');
	}
    $order_ids = array();
    if (!empty($order_id)) {
        $order_ids[] = $order_id;
    }
    if (!empty($order_sn)) {
        $sql = " SELECT order_id FROM " . $table . " WHERE order_sn " . db_create_in($order_sn);
        $ids = $GLOBALS['db']->getCol($sql);
        $order_ids = array_merge($order_ids, $ids);
    }

    $web_url = $ecs->stores_url() . STORES_PATH . "/";
    $smarty->assign('web_url', $web_url);
    
    $smarty->assign('order_type', $order_type);

	$smarty->assign('shop_url',     $ecs->stores_url());
	
	$sql = "SELECT value FROM " . $GLOBALS['ecs']->table('shop_config') . " WHERE code = 'order_print_logo'";
	$order_print_logo = strstr($GLOBALS['db']->getOne($sql),"images");
	$smarty->assign('order_print_logo', $ecs->stores_url() . ADMIN_PATH . '/' . $order_print_logo);

    $part_html = array();
    foreach ($order_ids as $order_id) {
        $order_info = get_print_order_info($order_id,$order_type);
        $smarty->assign('order_info', $order_info);
		$smarty->assign('order_sn', $order_info['order_sn']);
        $part_html[] = $smarty->fetch('library/order_print_part.lbi');
    }
    $smarty->assign('part_html', $part_html);

    /* 模板赋值 */
    //$smarty->assign('ur_here', $_LANG['order_print']);

    /* 显示模板 */
    $content = $smarty->fetch('library/order_print.lbi');
    make_json_result($content);
}

/* 获取电子面单设置列表 */
function get_order_print_setting($ru_id)
{
    /* 过滤查询 */
    $filter = array();

    $filter['keyword'] = !empty($_REQUEST['keyword']) ? trim($_REQUEST['keyword']) : '';
    if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
        $filter['keyword'] = json_str_iconv($filter['keyword']);
    }

    $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'ops.sort_order' : trim($_REQUEST['sort_by']);
    $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'ASC' : trim($_REQUEST['sort_order']);

    $where = 'WHERE 1 ';

    /* 关键字 */
    if (!empty($filter['keyword'])) {
        $where .= " AND (ops.specification LIKE '%" . mysql_like_quote($filter['keyword']) . "%'" . " OR ops.printer LIKE '%" . mysql_like_quote($filter['keyword']) . "%'" . ")";
    }

    if (isset($ru_id)) {
        $where .= " AND ops.ru_id = '$ru_id' ";
    }

    /* 获得总记录数据 */
    $sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('order_print_setting') . ' AS ops ' . $where;
    $filter['record_count'] = $GLOBALS['db']->getOne($sql);

    $filter = page_and_size($filter);

    /* 获得数据 */
    $arr = array();
    $sql = 'SELECT ops.* FROM ' . $GLOBALS['ecs']->table('order_print_setting') . 'AS ops ' .
            $where . 'ORDER by ' . $filter['sort_by'] . ' ' . $filter['sort_order'];

    $res = $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);

    while ($rows = $GLOBALS['db']->fetchRow($res)) {
        $arr[] = $rows;
    }

    return array('list' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}

//快递鸟、电子面单：打印订单信息
function get_print_order_info($order_id = 0,$order_type='order') {
    require_once(ROOT_PATH . 'includes/lib_order.php');
    require_once(ROOT_PATH . 'includes/lib_goods.php');
    require_once(ROOT_PATH . 'includes/lib_wholesale.php');
    if($order_type == 'order'){
        //订单信息
        $order_info = order_info($order_id);
        //商品数据
        $goods_list = order_goods($order_id);
    }else{
        //订单信息
        $order_info = wholesale_order_info($order_id);
        //商品数据
        $goods_list = get_wholesale_order_goods_toInfo($order_id);
        //补全信息
        $order_info['total_fee'] = $order_info['order_amount'];
        $order_info['formated_total_fee'] = price_format($order_info['total_fee']);
        if($order_info['pay_status'] == 2){
            $order_info['money_paid'] = $order_info['order_amount'];
            $order_info['formated_money_paid'] = price_format($order_info['money_paid']);
            $order_info['formated_order_amount'] = price_format(0.00);
        }
    }
    

    if (isset($order_info['order_id'])) {
        /* 收货地址 */
        if($order_type == 'order'){
            $order_info['complete_address'] = get_goods_region_name($order_info['province']) . '-' .
            get_goods_region_name($order_info['city']) . '-' .
            get_goods_region_name($order_info['district']) . ' ' .
            $order_info['address'];
        }else{
            $order_info['complete_address'] = $order_info['address'];
        }
       
        
        $number_amount = 0;
        if ($goods_list) {
            foreach ($goods_list as $key => $val) {
                $goods_list[$key]['format_goods_price'] = price_format($val['goods_price']);
                $goods_list[$key]['format_goods_amount'] = price_format($val['goods_price'] * $val['goods_number']);            
                
                if($order_type == 'order'){
                    $products = get_warehouse_id_attr_number($val['goods_id'], $val['goods_attr_id'], $val['ru_id'], $val['warehouse_id'], $val['area_id'], $val['model_attr']);
                }
                
                if(isset($products) && isset($products['bar_code'])){
                    $bar_code = $products['bar_code'];
                }else{
                    $bar_code = get_table_date('goods', "goods_id = '" .$val['goods_id']. "'", array('bar_code'), 2);
                }
                
                $goods_list[$key]['bar_code'] = $bar_code;
                $number_amount += $val['goods_number'];
            }
        }
        $order_info['number_amount'] = $number_amount;
        $order_info['goods_list'] = $goods_list;
    }

    return $order_info;
}
?>
