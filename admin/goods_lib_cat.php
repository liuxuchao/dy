<?php

/**
 * ECSHOP 商品分类管理程序
 * ============================================================================
 * * 版权所有 2005-2016 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: category.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
$exc = new exchange($ecs->table("goods_lib_cat"), $db, 'cat_id', 'cat_name');

/* act操作项的初始化 */
if (empty($_REQUEST['act']))
{
    $_REQUEST['act'] = 'list';
}
else
{
    $_REQUEST['act'] = trim($_REQUEST['act']);
}

$adminru = get_admin_ru_id();

$smarty->assign('menu_select',array('action' => '02_cat_and_goods', 'current' => '03_category_list'));
/*------------------------------------------------------ */
//-- 商品分类列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
	admin_priv('goods_lib_cat');
    $parent_id = !isset($_REQUEST['parent_id']) ? 0 : intval($_REQUEST['parent_id']);
    
    //返回上一页 start
    if(isset($_REQUEST['back_level']) && $_REQUEST['back_level'] > 0){
        $level = $_REQUEST['back_level'] - 1;
        $parent_id = $db->getOne("SELECT parent_id FROM " .$ecs->table('goods_lib_cat'). " WHERE cat_id = '$parent_id'", true);
    }else{
        $level = isset($_REQUEST['level']) ? $_REQUEST['level'] + 1 : 0;
    }
    //返回上一页 end
    
    $smarty->assign('level', $level);
    $smarty->assign('parent_id', $parent_id);
    
    /* 获取分类列表 */
    $cat_list = lib_get_cat_level($parent_id, $level);

    $smarty->assign('cat_info',     $cat_list);
    $smarty->assign('ru_id',     $adminru['ru_id']);

    if($adminru['ru_id'] == 0){
            $smarty->assign('action_link',  array('href' => 'goods_lib_cat.php?act=add', 'text' => $_LANG['04_category_add']));
    }
    
    /* 模板赋值 */
    $smarty->assign('ur_here',      $_LANG['21_goods_lib_cat']);
    $smarty->assign('full_page',    1);
    
    $cat_level = array($_LANG['num_1'],$_LANG['num_2'],$_LANG['num_3'],$_LANG['num_4'],$_LANG['num_5'],$_LANG['num_6'],$_LANG['num_7'],$_LANG['num_8'],$_LANG['num_9'],$_LANG['num_10']);
    $smarty->assign('cat_level', $cat_level[$level]);
	
    /* 列表页面 */
    assign_query_info();
    $smarty->display('goods_lib_cat_list.dwt');
}

/*------------------------------------------------------ */
//-- 排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    /* 获取分类列表 */
    $cat_list = lib_get_cat_level();
    $smarty->assign('cat_info',     $cat_list);
    $smarty->assign('ru_id',     $adminru['ru_id']);

    make_json_result($smarty->fetch('goods_lib_cat_list.dwt'));
}

/*------------------------------------------------------ */
//-- 添加商品分类
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'add') {
    /* 权限检查 */
    admin_priv('goods_lib_cat');

    $parent_id = empty($_REQUEST['parent_id']) ? 0 : intval($_REQUEST['parent_id']);
    if (!empty($parent_id)) {
        set_default_filter(0, $parent_id, 0, 0, 'goods_lib_cat'); //设置默认筛选
        $smarty->assign('parent_category', get_every_category($parent_id, 'goods_lib_cat')); //上级分类
        $smarty->assign('parent_id', $parent_id); //上级分类
    } else {
        set_default_filter(0, 0, 0, 0, 'goods_lib_cat'); //设置默认筛选
    }

    /* 模板赋值 */
    $smarty->assign('ur_here', $_LANG['04_category_add']);
    $smarty->assign('action_link', array('href' => 'goods_lib_cat.php?act=list', 'text' => $_LANG['03_category_list']));

    $smarty->assign('form_act', 'insert');
    $smarty->assign('cat_info', array('is_show' => 1));
    $smarty->assign('ru_id', $adminru['ru_id']);
	$smarty->assign('lib', 'lib');
    
    /* 显示页面 */
    assign_query_info();
    $smarty->display('goods_lib_cat_info.dwt');
}

/*------------------------------------------------------ */
//-- 商品分类添加时的处理
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'insert') {
    /* 权限检查 */
    admin_priv('goods_lib_cat');

    /* 初始化变量 */
    $cat['cat_id'] = !empty($_POST['cat_id']) ? intval($_POST['cat_id']) : 0;
    $cat['parent_id'] = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
    $cat['level'] = count(get_select_category($cat['parent_id'], 1, true)) - 2;

    if ($cat['level'] > 1 && $adminru['ru_id'] == 0) {
        $link[0]['text'] = $_LANG['go_back'];

        if ($cat['cat_id'] > 0) {
            $link[0]['href'] = 'goods_lib_cat.php?act=edit&cat_id=' . $cat['cat_id'];
        } else {
            $link[0]['href'] = 'goods_lib_cat.php?act=add&parent_id='.$cat['parent_id'];
        }

        sys_msg($_LANG['cat_prompt_notic_one'], 0, $link);
        exit;
    }

    $cat['sort_order'] = !empty($_POST['sort_order']) ? intval($_POST['sort_order']) : 0;
    $cat['cat_name'] = !empty($_POST['cat_name']) ? trim($_POST['cat_name']) : '';
    $cat['is_show'] = !empty($_POST['is_show']) ? intval($_POST['is_show']) : 0;

    if (cat_exists($cat['cat_name'], $cat['parent_id'])) {
        /* 同级别下不能有重复的分类名称 */
        $link[] = array('text' => $_LANG['go_back'], 'href' => 'javascript:history.back(-1)');
        sys_msg($_LANG['catname_exist'], 0, $link);
    }

    /* 入库的操作 */
	if ($db->autoExecute($ecs->table('goods_lib_cat'), $cat) !== false) {
		$cat_id = $db->insert_id();

		admin_log($_POST['cat_name'], 'add', 'goods_lib_cat');   // 记录管理员操作
		clear_cache_files();    // 清除缓存
		/* 添加链接 */
		$link[0]['text'] = $_LANG['continue_add'];
		$link[0]['href'] = 'goods_lib_cat.php?act=add&parent_id='.$cat['parent_id'];

		$link[1]['text'] = $_LANG['back_list'];
		$link[1]['href'] = 'goods_lib_cat.php?act=list&parent_id='.$cat['parent_id'].'&level='.$cat['level'];

		sys_msg($_LANG['catadd_succed'], 0, $link);
	}
}

/*------------------------------------------------------ */
//-- 编辑商品分类信息
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'edit') {
    admin_priv('goods_lib_cat');  // 权限检查
    $cat_id = intval($_REQUEST['cat_id']);
	
    $sql = "SELECT * FROM " .$GLOBALS['ecs']->table('goods_lib_cat'). " WHERE cat_id = '$cat_id' LIMIT 1";
    $cat_info = $GLOBALS['db']->getRow($sql);  // 查询分类信息数据

    //获取下拉列表 by wu start
    $smarty->assign('parent_id', $cat_info['parent_id']); //上级分类
    $smarty->assign('parent_category', get_every_category($cat_info['parent_id'], 'goods_lib_cat')); //上级分类导航
    set_default_filter(0, $cat_info['parent_id'], 0, 0, 'goods_lib_cat'); //设置默认筛选
    //获取下拉列表 by wu end	

    /* 模板赋值 */
    $smarty->assign('ur_here', $_LANG['category_edit']);
    $smarty->assign('action_link', array('text' => $_LANG['03_category_list'], 'href' => 'goods_lib_cat.php?act=list'));

    //ecmoban模板堂 --zhuo start
    $smarty->assign('cat_id', $cat_id);

    $smarty->assign('ru_id', $adminru['ru_id']);
    //ecmoban模板堂 --zhuo end

    $smarty->assign('cat_info', $cat_info);
    $smarty->assign('form_act', 'update');
	$smarty->assign('lib', 'lib');

    /* 显示页面 */
    assign_query_info();
    $smarty->display('goods_lib_cat_info.dwt');
}

/*------------------------------------------------------ */
//-- 编辑商品分类信息
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'update')
{
    /* 权限检查 */
    admin_priv('goods_lib_cat');

    /* 初始化变量 */
    $cat_id = $cat['cat_id']       = !empty($_POST['cat_id'])       ? intval($_POST['cat_id'])     : 0;

    $cat['parent_id']    = isset($_POST['parent_id'])     ? intval($_POST['parent_id'])  : 0;
	$cat['level']        = count(get_select_category($cat['parent_id'],1,true)) - 2;
	$old_cat_name = isset($_REQUEST['old_cat_name'])  ?  $_REQUEST['old_cat_name'] : '';
	if($cat['level'] > 1 && $adminru['ru_id'] == 0)
	{
        $link[0]['text'] = $_LANG['go_back'];
        
        if($cat['cat_id'] > 0){
            $link[0]['href'] = 'goods_lib_cat.php?act=edit&cat_id=' . $cat['cat_id'];
        }else{
            $link[0]['href'] = 'goods_lib_cat.php?act=add&parent_id='.$cat['parent_id'];
        }

        sys_msg($_LANG['cat_prompt_notic_one'], 0, $link);
        exit;		
	}
	
    $cat['sort_order']   = !empty($_POST['sort_order'])   ? intval($_POST['sort_order']) : 0;
    $cat['cat_name']     = !empty($_POST['cat_name'])     ? trim($_POST['cat_name'])     : '';
	$cat['is_show']      = !empty($_POST['is_show'])      ? intval($_POST['is_show'])    : 0;
	
    /* 判断分类名是否重复 */
    if ($cat['cat_name'] != $old_cat_name)
    {
        if (cat_exists($cat['cat_name'],$cat['parent_id'], $cat_id))
        {
           $link[] = array('text' => $_LANG['go_back'], 'href' => 'javascript:history.back(-1)');
           sys_msg($_LANG['catname_exist'], 0, $link);
        }
    }
    
    /* 判断上级目录是否合法 */
    $children = get_array_keys_cat($cat_id);     // 获得当前分类的所有下级分类
    if (in_array($cat['parent_id'], $children))
    {
        /* 选定的父类是当前分类或当前分类的下级分类 */
       $link[] = array('text' => $_LANG['go_back'], 'href' => 'javascript:history.back(-1)');
       sys_msg($_LANG["is_leaf_error"], 0, $link);
    }

    if ($db->autoExecute($ecs->table('goods_lib_cat'), $cat, 'UPDATE', "cat_id='$cat_id'"))
    {
        clear_cache_files(); // 清除缓存
        admin_log($_POST['cat_name'], 'edit', 'goods_lib_cat'); // 记录管理员操作

        /* 提示信息 */
        $link[] = array('text' => $_LANG['back_list'], 'href' => 'goods_lib_cat.php?act=list&parent_id='.$cat['parent_id'].'&level='.$cat['level']);
        sys_msg($_LANG['catedit_succed'], 0, $link);
    }
}

/*------------------------------------------------------ */
//-- 编辑排序序号
/*------------------------------------------------------ */

if ($_REQUEST['act'] == 'edit_sort_order')
{
    check_authz_json('goods_lib_cat');

    $id = intval($_POST['id']);
    $val = intval($_POST['val']);

    if (cat_update($id, array('sort_order' => $val)))
    {
        clear_cache_files(); // 清除缓存
        make_json_result($val);
    }
    else
    {
        make_json_error($db->error());
    }
}

/*------------------------------------------------------ */
//-- 切换是否显示
/*------------------------------------------------------ */

if ($_REQUEST['act'] == 'toggle_is_show')
{
    check_authz_json('goods_lib_cat');

    $id = intval($_POST['id']);
    $val = intval($_POST['val']);

    if (cat_update($id, array('is_show' => $val)) != false)
    {
        clear_cache_files();
        make_json_result($val);
    }
    else
    {
        make_json_error($db->error());
    }
}

/*------------------------------------------------------ */
//-- 删除分类 ajax实现删除分类后页面不刷新 //ecmoban模板堂 --kong
/*------------------------------------------------------ */
 elseif ($_REQUEST['act'] == 'remove') 
{
    check_authz_json('goods_lib_cat');
    require(ROOT_PATH . '/includes/cls_json.php');
    $json = new JSON;
    $result = array('error' => 0, 'massege' => '', 'level' => '');
    /* 初始化分类ID并取得分类名称 */
    $result['level'] = $_REQUEST['level'];
    $cat_id = intval($_GET['cat_id']);
    $result['cat_id'] = $cat_id;
    $cat_name = $db->getOne('SELECT cat_name FROM ' . $ecs->table('goods_lib_cat') . " WHERE cat_id='$cat_id'");

    /* 当前分类下是否有子分类 */
    $cat_count = $db->getOne('SELECT COUNT(*) FROM ' . $ecs->table('goods_lib_cat') . " WHERE parent_id='$cat_id'");

    /* 当前分类下是否存在商品 */
    $goods_count = $db->getOne('SELECT COUNT(*) FROM ' . $ecs->table('goods_lib') . " WHERE cat_id='$cat_id'");
    /* 如果不存在下级子分类和商品，则删除之 */
    if ($cat_count == 0 && $goods_count == 0) {
        /* 删除分类 */
        $sql = 'DELETE FROM ' . $ecs->table('goods_lib_cat') . " WHERE cat_id = '$cat_id'";
        if ($db->query($sql)) {
            clear_cache_files();
            admin_log($cat_name, 'remove', 'goods_lib_cat');
            $result['error'] = 1;
        }
    } else {
        $result['error'] = 2;
        $result['massege'] = $cat_name . ' ' . $_LANG['cat_isleaf'];
    }
    die($json->encode($result));
}

/*------------------------------------------------------ */
//-- PRIVATE FUNCTIONS
/*------------------------------------------------------ */

/**
 * 添加商品分类
 *
 * @param   integer $cat_id
 * @param   array   $args
 *
 * @return  mix
 */
function cat_update($cat_id, $args)
{
    if (empty($args) || empty($cat_id))
    {
        return false;
    }

    return $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('goods_lib_cat'), $args, 'update', "cat_id='$cat_id'");
}

function lib_get_cat_level($parent_id = 0, $level = 0) 
{

    $sql = "SELECT glc.cat_id, glc.cat_name,glc.is_show ,glc.sort_order , glc.parent_id " .
            " FROM " . $GLOBALS['ecs']->table('goods_lib_cat') . " AS glc WHERE glc.parent_id = '$parent_id' " .
            " order by glc.sort_order, glc.cat_id";
    $res = $GLOBALS['db']->getAll($sql);

    foreach ($res as $k => $row) {
		//ecmoban模板堂 --zhuo 查询服分类下子分类下的商品数量 start
		$cat_id_str = lib_get_class_nav($res[$k]['cat_id'], 'goods_lib_cat');
		$res[$k]['cat_child'] = substr($cat_id_str['catId'], 0, -1);
		if (empty($cat_id_str['catId'])) {
			$res[$k]['cat_child'] = substr($res[$k]['cat_id'], 0, -1);
		}

		$res[$k]['cat_child'] = isset($res[$k]['cat_child']) && !empty($res[$k]['cat_child']) ? get_del_str_comma($res[$k]['cat_child']) : '';

		if ($res[$k]['cat_child']) {
			$cat_in = " AND g.lib_cat_id in(" . $res[$k]['cat_child'] . ")";
		} else {
			$cat_in = "";
		}

		$goodsNums = $GLOBALS['db']->getAll("SELECT g.goods_id FROM " . $GLOBALS['ecs']->table('goods_lib') . " AS g " . " WHERE 1 " . $cat_in . $ruCat);

		$goods_ids = array();
		foreach ($goodsNums as $num_key => $num_val) {
			$goods_ids[] = $num_val['goods_id'];
		}

		//$goodsCat = get_goodsCat_num($res[$k]['cat_child'], $goods_ids, $ruCat);

		$res[$k]['goods_num'] = count($goodsNums);// + $goodsCat;

		//$res[$k]['goodsCat'] = $goodsCat; //扩展商品数量
		$res[$k]['goodsNum'] = $goodsNum; //本身以及子分类的商品数量
		//ecmoban模板堂 --zhuo 查询服分类下子分类下的商品数量 end
        
        $res[$k]['level'] = $level;
    }

    return $res;
}

function lib_get_class_nav($cat_id, $table = 'goods_lib_cat'){

	$sql = "select cat_id,cat_name,parent_id from " . $GLOBALS['ecs']->table($table) ." where cat_id = '$cat_id'";
	$res = $GLOBALS['db']->getAll($sql);

	foreach($res as $key => $row){
		$arr[$key]['cat_id'] 	= $row['cat_id'];
		$arr[$key]['cat_name'] 	= $row['cat_name'];
		$arr[$key]['parent_id'] = $row['parent_id'];
		
		$arr['catId'] .= $row['cat_id'] . ",";
		$arr[$key]['child'] = lib_get_parent_child($row['cat_id'], $table);

		if(empty($arr[$key]['child']['catId'])){
			$arr['catId'] = $arr['catId'];
		}else{
			$arr['catId'] .= $arr[$key]['child']['catId'];
		}
	}

	return $arr;
}

function lib_get_parent_child($parent_id = 0, $table = 'goods_lib_cat'){
	$sql = "select cat_id,cat_name,parent_id from " . $GLOBALS['ecs']->table($table) ." where parent_id = '$parent_id'";
	$res = $GLOBALS['db']->getAll($sql);

	foreach($res as $key => $row){
		$arr[$key]['cat_id'] 	= $row['cat_id'];
		$arr[$key]['cat_name'] 	= $row['cat_name'];
		$arr[$key]['parent_id'] = $row['parent_id'];

		$arr['catId'] .= $row['cat_id'] . ",";
		$arr[$key]['child'] = lib_get_parent_child($row['cat_id']);

		$arr['catId'] .= $arr[$key]['child']['catId'];
	}

	return $arr;
}

?>