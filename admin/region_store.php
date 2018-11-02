<?php

/**
 * ECSHOP 卖场
 * ===========================================================
 * * 版权所有 2005-2016 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ==========================================================
 * $Author: liubo $
 * $Id: region_store.php 17217 2011-01-19 06:29:08Z liubo $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

$adminru = get_admin_ru_id();

//admin_priv('');

//默认
if (empty($_REQUEST['act']))
{
	die('Error');
}

/*------------------------------------------------------ */
//-- 列表页面
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
	admin_priv('region_store_manage');
	
    $smarty->assign('ur_here',     $_LANG['01_region_store_manage']);
   	$smarty->assign('action_link', array('text' => $_LANG['region_store_add'], 'href' => 'region_store.php?act=add'));
    $smarty->assign('full_page',  1);
	
    $region_store = region_store_list($adminru['ru_id']);

    $smarty->assign('list',          $region_store['list']);
    $smarty->assign('filter',        $region_store['filter']);
    $smarty->assign('record_count',  $region_store['record_count']);
    $smarty->assign('page_count',    $region_store['page_count']);

    $sort_flag  = sort_flag($region_store['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    assign_query_info();
    $smarty->display('region_store_list.dwt');
}

/*------------------------------------------------------ */
//-- 排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
	check_authz_json('region_store_manage');
	
    $region_store = region_store_list($adminru['ru_id']);

    $smarty->assign('list',          $region_store['list']);
    $smarty->assign('filter',        $region_store['filter']);
    $smarty->assign('record_count',  $region_store['record_count']);
    $smarty->assign('page_count',    $region_store['page_count']);

    $sort_flag  = sort_flag($region_store['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('region_store_list.dwt'), '',
        array('filter' => $region_store['filter'], 'page_count' => $region_store['page_count']));
}

/*------------------------------------------------------ */
//-- 删除
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    check_authz_json('region_store_manage');

    $id = intval($_GET['id']);

	$exc = new exchange($ecs->table("region_store"), $db, 'rs_id');	
	$exc->drop($id);
    //删除关联地区和管理员
    $db->query("DELETE FROM".$ecs->table("rs_region")." WHERE rs_id='$id'");
    $db->autoExecute($ecs->table('admin_user'), array('rs_id'=>0), 'UPDATE', "rs_id = '$id'");
    
	//clear_cache_files();
    $url = 'region_store.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);
    ecs_header("Location: $url\n");
    exit;
}

/*------------------------------------------------------ */
//-- 编辑
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_rs_name')
{
    check_authz_json('region_store_manage');

    $id = intval($_POST['id']);
    $val = trim($_POST['val']);
	
	$sql = " UPDATE ".$ecs->table('region_store')." SET rs_name = '$val' WHERE rs_id = '$id' ";
	$db->query($sql);

	//clear_cache_files();
	make_json_result($val);
}

/*------------------------------------------------------ */
//-- 添加、编辑
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit')
{
    admin_priv('region_store_manage');
	
    $rs_id = !empty($_REQUEST['rs_id']) ? intval($_REQUEST['rs_id']) : 0;
    if($rs_id > 0){
		$region_store = get_region_store_info($rs_id);
		if($region_store['region_id']){
			$region_level = get_region_level($region_store['region_id']);
			$region_store['region_level'] = $region_level;
		}		
		$smarty->assign('region_store', $region_store);
		$smarty->assign('ur_here', $_LANG['edit']);
		$smarty->assign('form_action', 'update');
    }else{
		$smarty->assign('ur_here', $_LANG['add']);
		$smarty->assign('form_action', 'insert');
	}
	$smarty->assign('action_link', array('text' => $_LANG['01_region_store_manage'], 'href' => 'region_store.php?act=list'));
    
    //区域
    $smarty->assign('country_all',  get_regions());
    $smarty->assign('province_all', get_regions(1, 1));
	
	//管理员
	$smarty->assign('region_admin',  get_region_admin());

    assign_query_info();
    $smarty->display('region_store_info.dwt');
}

/*------------------------------------------------------ */
//-- 添加、编辑
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update')
{
    admin_priv('region_store_manage');
	
	$store_data = array();
	$region_data = array();
	$admin_data = array();
    $rs_id = !empty($_REQUEST['rs_id']) ? intval($_REQUEST['rs_id']) : 0;
    $store_data['rs_name'] = !empty($_REQUEST['rs_name']) ? trim($_REQUEST['rs_name']) : '';
	$region_data['region_id'] = !empty($_REQUEST['city']) ? intval($_REQUEST['city']) : 0;
	$admin_data['user_id'] = !empty($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
	$region_data['rs_id'] = $rs_id;
	$admin_data['rs_id'] = $rs_id;
	
    /*检查卖场是否重复*/
	$sql = " SELECT rs_id FROM ".$ecs->table('region_store')." WHERE rs_name = '{$store_data['rs_name']}' AND rs_id <> '$rs_id' LIMIT 1 ";
    $is_only = $db->getOne($sql);
    if (!empty($is_only))
    {
        sys_msg($_LANG['region_store_exist'], 1);
    }
	/*插入、更新*/
    if($rs_id > 0){
		$db->autoExecute($ecs->table('region_store'), $store_data, 'UPDATE', "rs_id = '$rs_id'");
		$msg = $_LANG['edit_success'];
    }else{
		$db->autoExecute($ecs->table('region_store'), $store_data, 'INSERT');
		$msg = $_LANG['add_success'];
		$rs_id = $db->insert_id();
		$region_data['rs_id'] = $rs_id;
		$admin_data['rs_id'] = $rs_id;
	}

    /*检查城市是否重复*/
	$sql = " SELECT id FROM ".$ecs->table('rs_region')." WHERE region_id = '{$region_data['region_id']}' AND rs_id <> '$rs_id' LIMIT 1 ";
    $is_only = $db->getOne($sql);
    if (!empty($is_only))
    {
        sys_msg($_LANG['rs_region_exist'], 1);
    }	
    /*检查地区是否设置*/
	$sql = " SELECT id FROM ".$ecs->table('rs_region')." WHERE rs_id = '$rs_id' LIMIT 1 ";
    $is_exist = $db->getOne($sql);
	/*插入、更新*/
    if($is_exist){
		$db->autoExecute($ecs->table('rs_region'), $region_data, 'UPDATE', "rs_id = '$rs_id'");
    }else{
		$db->autoExecute($ecs->table('rs_region'), $region_data, 'INSERT');
	}

    /*检查管理员是否重复*/
	$sql = " SELECT rs_id FROM ".$ecs->table('admin_user')." WHERE rs_id <> '$rs_id' AND user_id = '{$admin_data['user_id']}' LIMIT 1 ";
    $is_only = $db->getOne($sql);
    if (!empty($is_only))
    {
        sys_msg($_LANG['rs_admin_exist'], 1);
    }
	//解除绑定
	$db->autoExecute($ecs->table('admin_user'), array('rs_id'=>0), 'UPDATE', "rs_id = '$rs_id'");	
	//绑定新值
	$db->autoExecute($ecs->table('admin_user'), $admin_data, 'UPDATE', "user_id = '{$admin_data['user_id']}'");	
	
	$link[] = array('text' => $_LANG['back_list'], 'href' => 'region_store.php?act=list');
    sys_msg($msg, 0, $link);
}

/*------------------------------------------------------ */
//-- 刷新管理员
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'admin_update')
{
    check_authz_json('region_store_manage');

	$smarty->assign('region_admin',  get_region_admin());
	$content = $smarty->fetch('library/region_admin.lbi');

	//clear_cache_files();
	make_json_result($content);
}

/* 获取卖场列表 */
function region_store_list()
{	
    /* 过滤查询 */
    $filter = array();
	
    $filter['keyword'] = !empty($_REQUEST['keyword']) ? trim($_REQUEST['keyword']) : '';
    if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
    {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
    }
    
    $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'rs.rs_id' : trim($_REQUEST['sort_by']);
    $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

    $where = 'WHERE 1 ';
    
    /* 关键字 */
    if (!empty($filter['keyword']))
    {
        $where .= " AND (rs.rs_name LIKE '%" . mysql_like_quote($filter['keyword']) . "%') ";  
    }

    /* 获得总记录数据 */
    $sql = 'SELECT COUNT(*) FROM ' .$GLOBALS['ecs']->table('region_store'). ' AS rs ' . $where;
    $filter['record_count'] = $GLOBALS['db']->getOne($sql);

    $filter = page_and_size($filter);

    /* 获得数据 */
    $arr = array();
    $sql = 'SELECT rs.* FROM '.$GLOBALS['ecs']->table('region_store'). ' AS rs ' .
		$where . 'ORDER BY '.$filter['sort_by'].' '.$filter['sort_order'];

    $res = $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);

    while ($rows = $GLOBALS['db']->fetchRow($res))
    {
		//地区
		$region_id = get_table_date('rs_region', "rs_id='$rows[rs_id]'", array('region_id'), 2);
		if($region_id){
			$rows['region_name'] = get_table_date('region', "region_id='$region_id'", array('region_name'), 2);
		}
		
		//管理员
		$rows['user_name'] = get_table_date('admin_user', "rs_id='$rows[rs_id]'", array('user_name'), 2);
		
        $arr[] = $rows;
    }

    return array('list' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}

/* 获取卖场信息 */
function get_region_store_info($rs_id = 0){
    $region_store = get_table_date('region_store', "rs_id='$rs_id'", array('*'));
    if($region_store){
        //区域
        $sql = " SELECT region_id FROM ".$GLOBALS['ecs']->table('rs_region')." WHERE rs_id = '$rs_id' ";
        $region_id = $GLOBALS['db']->getOne($sql);
		
        //管理员
        $sql = " SELECT user_id FROM ".$GLOBALS['ecs']->table('admin_user')." WHERE rs_id = '$rs_id' ";
        $user_id = $GLOBALS['db']->getOne($sql);
        
        //整合数据
        $region_store['region_id'] = $region_id;
        $region_store['user_id'] = $user_id;
    }

    return $region_store;
}

/* 获取管理员列表 */
function get_region_admin(){
    $super_admin_id = get_table_date('admin_user', "action_list='all'", array('user_id'), 2);
    $sql = " SELECT user_id, user_name FROM ".$GLOBALS['ecs']->table('admin_user')." WHERE action_list != 'all' AND ru_id = 0 AND parent_id = $super_admin_id ORDER BY user_id DESC";
    $region_admin = $GLOBALS['db']->getAll($sql);
    return $region_admin;
}

?>
