<?php

/**
 * ECSHOP 管理中心积分兑换商品程序文件
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

/*初始化数据交换对象 */
$exc   = new exchange($ecs->table("exchange_goods"), $db, 'goods_id', 'exchange_integral');
//$image = new cls_image();
$smarty->assign('menus',$_SESSION['menus']);
$smarty->assign('action_type',"bonus");
//ecmoban模板堂 --zhuo start
$adminru = get_admin_ru_id();
if($adminru['ru_id'] == 0){
    $smarty->assign('priv_ru',   1);
}else{
    $smarty->assign('priv_ru',   0);
} 	
//ecmoban模板堂 --zhuo end

$smarty->assign('controller', basename(PHP_SELF,'.php'));

/*------------------------------------------------------ */
//-- 商品列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    /* 权限判断 */
    admin_priv('exchange_goods');

    /* 取得过滤条件 */
    $filter = array();
    $smarty->assign('primary_cat',     $_LANG['02_promotion']);
    $smarty->assign('ur_here',      $_LANG['15_exchange_goods_list']);
    $smarty->assign('action_link',  array('text' => $_LANG['exchange_goods_add'], 'href' => 'exchange_goods.php?act=add', 'class' => 'icon-plus'));
    $smarty->assign('full_page',    1);
    $smarty->assign('filter',       $filter);

    $goods_list = get_exchange_goodslist($adminru['ru_id']);
	
	//分页
	$page_count_arr = seller_page($goods_list,$_REQUEST['page']);
    $smarty->assign('page_count_arr',$page_count_arr);	

    $smarty->assign('goods_list',    $goods_list['arr']);
    $smarty->assign('filter',        $goods_list['filter']);
    $smarty->assign('record_count',  $goods_list['record_count']);
    $smarty->assign('page_count',    $goods_list['page_count']);
    
    $store_list = get_common_store_list();
    $smarty->assign('store_list',        $store_list);

    $sort_flag  = sort_flag($goods_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    assign_query_info();
    $smarty->display('exchange_goods_list.dwt');
}

/*------------------------------------------------------ */
//-- 翻页，排序
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    check_authz_json('exchange_goods');

    $goods_list = get_exchange_goodslist($adminru['ru_id']);
	
	//分页
	$page_count_arr = seller_page($goods_list,$_REQUEST['page']);
    $smarty->assign('page_count_arr',$page_count_arr);	

    $smarty->assign('goods_list',    $goods_list['arr']);
    $smarty->assign('filter',        $goods_list['filter']);
    $smarty->assign('record_count',  $goods_list['record_count']);
    $smarty->assign('page_count',    $goods_list['page_count']);
    
    $sort_flag  = sort_flag($goods_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('exchange_goods_list.dwt'), '',
        array('filter' => $goods_list['filter'], 'page_count' => $goods_list['page_count']));
}

/*------------------------------------------------------ */
//-- 添加商品
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'add')
{
    /* 权限判断 */
    admin_priv('exchange_goods');
    $smarty->assign('primary_cat',     $_LANG['02_promotion']);
    $smarty->assign('menu_select', array('action'=>'02_promotion', 'current'=>'15_exchange_goods'));
    
    /*初始化*/
    $goods = array();
    $goods['is_exchange'] = 1;
    $goods['is_hot']      = 0;
    $goods['option']      = '<li><a href="javascript:;" data-value="0" class="ftx-01">'.$_LANG['make_option'].'</a></li>';

    $smarty->assign('goods',       $goods);
    $smarty->assign('ur_here',     $_LANG['exchange_goods_add']);
    $smarty->assign('action_link', array('text' => $_LANG['15_exchange_goods_list'], 'href' => 'exchange_goods.php?act=list', 'class' => 'icon-reply'));
    $smarty->assign('form_action', 'insert');
    $smarty->assign('ru_id',  $adminru['ru_id']);

    assign_query_info();
    $smarty->display('exchange_goods_info.dwt');
}

/*------------------------------------------------------ */
//-- 添加商品
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'insert')
{
    /* 权限判断 */
    admin_priv('exchange_goods');

    /*检查是否重复*/
    $is_only = $exc->is_only('goods_id', $_POST['goods_id'],0, " goods_id ='$_POST[goods_id]'");

    if (!$is_only)
    {
        sys_msg($_LANG['goods_exist'], 1);
    }

    /*插入数据*/
    $add_time = gmtime();
    if (empty($_POST['goods_id']))
    {
        $_POST['goods_id'] = 0;
    }
	
    /* 插入数据 */
    $record = array(
        'goods_id' => intval($_POST['goods_id']),
        'exchange_integral' => intval($_POST['exchange_integral']),
        'market_integral' => intval($_POST['market_integral']),
        'is_exchange' => intval($_POST['is_exchange']),
        'is_hot' => intval($_POST['is_hot']),
        'is_best' => intval($_POST['is_best']),
        'user_id' => $adminru['ru_id'],
        'add_time' => gmtime()
    );

    $db->AutoExecute($ecs->table('exchange_goods'), $record, 'INSERT');

    $link[0]['text'] = $_LANG['continue_add'];
    $link[0]['href'] = 'exchange_goods.php?act=add';

    $link[1]['text'] = $_LANG['back_list'];
    $link[1]['href'] = 'exchange_goods.php?act=list';

    admin_log($_POST['goods_id'],'add','exchange_goods');

    clear_cache_files(); // 清除相关的缓存文件

    sys_msg($_LANG['articleadd_succeed'],0, $link);
}

/*------------------------------------------------------ */
//-- 编辑
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'edit')
{
    /* 权限判断 */
    admin_priv('exchange_goods');
    $smarty->assign('primary_cat',     $_LANG['02_promotion']);
    $smarty->assign('menu_select', array('action'=>'02_promotion', 'current'=>'15_exchange_goods'));
    
    $goods_id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

    /* 取商品数据 */
    $sql = "SELECT eg.goods_id, eg.exchange_integral, market_integral, eg.user_id, eg.is_exchange, eg.is_hot, eg.is_best, eg.review_status, eg.review_content, g.goods_name ".
           " FROM " . $ecs->table('exchange_goods') . " AS eg ".
           "  LEFT JOIN " . $ecs->table('goods') . " AS g ON g.goods_id = eg.goods_id ".
           " WHERE eg.goods_id='$goods_id'";
    $goods = $db->GetRow($sql);
    
    if ($goods['user_id'] != $adminru['ru_id']) {
        $Loaction = "exchange_goods.php?act=list";
        ecs_header("Location: $Loaction\n");
        exit;
    }
    
    $goods['option']  = '<option value="'.$goods['goods_id'].'">'.$goods['goods_name'].'</option>';
    $goods['option']  = '<li><a href="javascript:;" data-value="'.$goods['goods_id'].'" class="ftx-01">'.$goods['goods_name'].'</a></li>';
    $smarty->assign('goods',       $goods);
    $smarty->assign('ur_here',     $_LANG['exchange_goods_add']);
    $smarty->assign('action_link', array('text' => $_LANG['15_exchange_goods_list'], 'href' => 'exchange_goods.php?act=list&' . list_link_postfix(), 'class' => 'icon-reply'));
    $smarty->assign('form_action', 'update');
    $smarty->assign('ru_id',  $adminru['ru_id']);

    assign_query_info();
    $smarty->display('exchange_goods_info.dwt');
}

/*------------------------------------------------------ */
//-- 编辑
/*------------------------------------------------------ */
if ($_REQUEST['act'] =='update')
{
    /* 权限判断 */
    admin_priv('exchange_goods');

    $goods_id = !empty($_POST['goods_id']) ? intval($_POST['goods_id']) : 0;
    $exchange_integral = !empty($_POST['exchange_integral']) ? intval($_POST['exchange_integral']) : 0;
    $market_integral = !empty($_POST['market_integral']) ? intval($_POST['market_integral']) : 0;
    $is_exchange = !empty($_POST['is_exchange']) ? intval($_POST['is_exchange']) : 0;
    $is_hot = !empty($_POST['is_hot']) ? intval($_POST['is_hot']) : 0;
    $is_best = !empty($_POST['is_best']) ? intval($_POST['is_best']) : 0;
    
    /* 更新数据 */
    $record = array(
        'goods_id' => $goods_id,
        'exchange_integral' => $exchange_integral,
        'market_integral' => $market_integral,
        'is_exchange' => $is_exchange,
        'is_hot' => $is_hot,
        'is_best' => $is_best
    );
    
    $record['review_status'] = 1;

    $db->autoExecute($ecs->table('exchange_goods'), $record, 'UPDATE', "goods_id = '$goods_id'");

    $link[0]['text'] = $_LANG['back_list'];
    $link[0]['href'] = 'exchange_goods.php?act=list&' . list_link_postfix();

    admin_log($goods_id, 'edit', 'exchange_goods');

    clear_cache_files();
    sys_msg($_LANG['articleedit_succeed'], 0, $link);
}

/*------------------------------------------------------ */
//-- 编辑使用积分值
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_exchange_integral')
{
    check_authz_json('exchange_goods');

    $id                = intval($_POST['id']);
    $exchange_integral = floatval($_POST['val']);

    /* 检查文章标题是否重复 */
    if ($exchange_integral < 0 || $exchange_integral == 0 && $_POST['val'] != "$goods_price")
    {
        make_json_error($_LANG['exchange_integral_invalid']);
    }
    else
    {
        if ($exc->edit("exchange_integral = '$exchange_integral'", $id))
        {
            clear_cache_files();
            admin_log($id, 'edit', 'exchange_goods');
            make_json_result(stripslashes($exchange_integral));
        }
        else
        {
            make_json_error($db->error());
        }
    }
}

/*------------------------------------------------------ */
//-- 切换是否兑换
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'toggle_exchange')
{
    check_authz_json('exchange_goods');

    $id     = intval($_POST['id']);
    $val    = intval($_POST['val']);

    $exc->edit("is_exchange = '$val'", $id);
    clear_cache_files();

    make_json_result($val);
}

/*------------------------------------------------------ */
//-- 切换是否热销
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'toggle_hot')
{
    check_authz_json('exchange_goods');

    $id     = intval($_POST['id']);
    $val    = intval($_POST['val']);

    $exc->edit("is_hot = '$val'", $id);
    clear_cache_files();

    make_json_result($val);
}

/*------------------------------------------------------ */
//-- 切换是否精品
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'toggle_best')
{
    check_authz_json('exchange_goods');

    $id     = intval($_POST['id']);
    $val    = intval($_POST['val']);

    $exc->edit("is_best = '$val'", $id);
    clear_cache_files();

    make_json_result($val);
}

/*------------------------------------------------------ */
//-- 批量删除商品
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'batch_remove')
{
    admin_priv('exchange_goods');

    if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes']))
    {
        sys_msg($_LANG['no_select_goods'], 1);
    }

    $count = 0;
    foreach ($_POST['checkboxes'] AS $key => $id)
    {
        if ($exc->drop($id))
        {
            admin_log($id,'remove','exchange_goods');
            $count++;
        }
    }

    $lnk[] = array('text' => $_LANG['back_list'], 'href' => 'exchange_goods.php?act=list');
    sys_msg(sprintf($_LANG['batch_remove_succeed'], $count), 0, $lnk);
}

/*------------------------------------------------------ */
//-- 删除商品
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    check_authz_json('exchange_goods');

    $id = intval($_GET['id']);
    
    /* 取商品数据 */
    $sql = "SELECT eg.user_id ".
           " FROM " . $ecs->table('exchange_goods') . " AS eg ".
           " WHERE eg.goods_id='$id'";
    $goods = $db->GetRow($sql);
    
    if ($goods['user_id'] != $adminru['ru_id']) {
        $url = 'exchange_goods.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);
        ecs_header("Location: $url\n");
        exit;
    }
    
    if ($exc->drop($id))
    {
        admin_log($id,'remove','exchange_goods');
        clear_cache_files();
    }

    $url = 'exchange_goods.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}

/*------------------------------------------------------ */
//-- 搜索商品
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'search_goods')
{
    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON;

    $filters = $json->decode($_GET['JSON']);

    $arr = get_goods_list($filters);

    make_json_result($arr);
}

/* 获得商品列表 */
function get_exchange_goodslist($ru_id)
{
    $result = get_filter();
    if ($result === false)
    {
        $filter = array();
        $filter['keyword']    = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
        {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }
        $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'eg.goods_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        
        $filter['review_status']    = empty($_REQUEST['review_status']) ? 0 : intval($_REQUEST['review_status']);

        $where = 1;
        if (!empty($filter['keyword']))
        {
            $where .= " AND g.goods_name LIKE '%" . mysql_like_quote($filter['keyword']) . "%'";
        }
        
        if( $filter['review_status']){
            $where .= " AND eg.review_status = '" .$filter['review_status']. "' ";
        }
	
        //ecmoban模板堂 --zhuo start
	if($ru_id > 0){
            $where .= " and eg.user_id = '$ru_id'";
	}
	//ecmoban模板堂 --zhuo end
        
        //管理员查询的权限 -- 店铺查询 start
        $filter['store_search'] = !isset($_REQUEST['store_search']) ? -1 : intval($_REQUEST['store_search']);
        $filter['merchant_id'] = isset($_REQUEST['merchant_id']) ? intval($_REQUEST['merchant_id']) : 0;
        $filter['store_keyword'] = isset($_REQUEST['store_keyword']) ? trim($_REQUEST['store_keyword']) : '';
        
        $store_where = '';
        $store_search_where = '';
        if($filter['store_search'] > -1){
           if($ru_id == 0){ 
                if($filter['store_search'] > 0){
                    if($_REQUEST['store_type']){
                        $store_search_where = "AND msi.shopNameSuffix = '" .$_REQUEST['store_type']. "'";
                    }

                    if($filter['store_search'] == 1){
                        $where .= " AND eg.user_id = '" .$filter['merchant_id']. "' ";
                    }elseif($filter['store_search'] == 2){
                        $store_where .= " AND msi.rz_shopName LIKE '%" . mysql_like_quote($filter['store_keyword']) . "%'";
                    }elseif($filter['store_search'] == 3){
                        $store_where .= " AND msi.shoprz_brandName LIKE '%" . mysql_like_quote($filter['store_keyword']) . "%' " . $store_search_where;
                    }

                    if($filter['store_search'] > 1){
                        $where .= " AND (SELECT msi.user_id FROM " . $GLOBALS['ecs']->table('merchants_shop_information') .' as msi ' .  
                                  " WHERE msi.user_id = eg.user_id $store_where) > 0 ";
                    }
                }else{
                    $where .= " AND eg.user_id = 0";
                }    
           }
        }
        //管理员查询的权限 -- 店铺查询 end

        /* 文章总数 */
        $sql = 'SELECT COUNT(*) FROM ' .$GLOBALS['ecs']->table('exchange_goods'). ' AS eg '.
               'LEFT JOIN ' .$GLOBALS['ecs']->table('goods'). ' AS g ON g.goods_id = eg.goods_id '.
               'WHERE ' .$where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $filter = page_and_size($filter);

        /* 获取文章数据 */
        $sql = 'SELECT eg.* , g.goods_name '.
               'FROM ' .$GLOBALS['ecs']->table('exchange_goods'). ' AS eg '.
               'LEFT JOIN ' .$GLOBALS['ecs']->table('goods'). ' AS g ON g.goods_id = eg.goods_id '.
               'WHERE ' .$where. ' ORDER BY '.$filter['sort_by'].' '.$filter['sort_order'];

        $filter['keyword'] = stripslashes($filter['keyword']);
        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }
    $arr = array();
    $res = $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);

    while ($rows = $GLOBALS['db']->fetchRow($res))
    {
        $rows['user_name'] = get_shop_name($rows['user_id'], 1); //ecmoban模板堂 --zhuo
        $arr[] = $rows;
    }
    return array('arr' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}
?>