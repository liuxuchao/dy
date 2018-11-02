<?php

/**
 * ECSHOP 超值礼包管理程序
 * ============================================================================
 * * 版权所有 2005-2016 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: package.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
$exc = new exchange($ecs->table("goods_activity"), $db, 'act_id', 'act_name');

include_once(ROOT_PATH . '/includes/cls_image.php'); 
$image = new cls_image($_CFG['bgcolor']);

//ecmoban模板堂 --zhuo start
$adminru = get_admin_ru_id();
if($adminru['ru_id'] == 0){
    $smarty->assign('priv_ru',   1);
}else{
    $smarty->assign('priv_ru',   0);
} 	
//ecmoban模板堂 --zhuo end

/*------------------------------------------------------ */
//-- 活动列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    $smarty->assign('ur_here',      $_LANG['14_package_list']);
    $smarty->assign('action_link',  array('text' => $_LANG['package_add'], 'href'=>'package.php?act=add'));

    $packages = get_packagelist($adminru['ru_id']);

    $smarty->assign('package_list', $packages['packages']);
    $smarty->assign('filter',       $packages['filter']);
    $smarty->assign('record_count', $packages['record_count']);
    $smarty->assign('page_count',   $packages['page_count']);

    $sort_flag  = sort_flag($packages['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    $store_list = get_common_store_list();
    $smarty->assign('store_list',        $store_list);
    $smarty->assign('full_page',    1);
    
    //区分自营和店铺
    self_seller(BASENAME($_SERVER['PHP_SELF']));     
    
    assign_query_info();
    $smarty->display('package_list.dwt');
}

/*------------------------------------------------------ */
//-- 查询、翻页、排序
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'query')
{
    $packages = get_packagelist($adminru['ru_id']);

    $smarty->assign('package_list', $packages['packages']);
    $smarty->assign('filter',       $packages['filter']);
    $smarty->assign('record_count', $packages['record_count']);
    $smarty->assign('page_count',   $packages['page_count']);

    $sort_flag  = sort_flag($packages['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    $store_list = get_common_store_list();
    $smarty->assign('store_list',        $store_list);

    make_json_result($smarty->fetch('package_list.dwt'), '',
        array('filter' => $packages['filter'], 'page_count' => $packages['page_count']));
}

/*------------------------------------------------------ */
//-- 添加活动
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'add') {
    /* 权限判断 */
    admin_priv('package_manage');

    /* 组合商品 */
    $group_goods_list = array();
    $sql = "DELETE FROM " . $ecs->table('package_goods') .
            " WHERE package_id = 0 AND admin_id = '$_SESSION[admin_id]'";

    $db->query($sql);

    /* 初始化信息 */
    $start_time = local_date('Y-m-d H:i:s');
    $end_time = local_date('Y-m-d H:i:s', strtotime('+1 month'));
    $package = array('package_price' => '', 'start_time' => $start_time, 'end_time' => $end_time,'ru_id'=>$adminru['ru_id']);

    $smarty->assign('package', $package);
    $smarty->assign('ur_here', $_LANG['package_add']);
    $smarty->assign('action_link', array('text' => $_LANG['14_package_list'], 'href' => 'package.php?act=list'));

    set_default_filter(); //设置默认筛选

    $smarty->assign('form_action', 'insert');
    $smarty->assign('ru_id', $adminru['ru_id']);

    assign_query_info();
    $smarty->display('package_info.dwt');
} 

/*------------------------------------------------------ */
//-- 插入活动数据
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] =='insert')
{
    /* 权限判断 */
    admin_priv('package_manage');

    $sql = "SELECT COUNT(*) ".
           " FROM " . $ecs->table('goods_activity').
           " WHERE act_type='" . GAT_PACKAGE . "' AND act_name='" . $_POST['package_name'] . "'" ;
    if ($db->getOne($sql))
    {
        sys_msg(sprintf($_LANG['package_exist'],  $_POST['package_name']) , 1);
    }


    /* 将时间转换成整数 */
    $_POST['start_time'] = local_strtotime($_POST['start_time']);
    $_POST['end_time']   = local_strtotime($_POST['end_time']);

    /* 处理提交数据 */
    if (empty($_POST['package_price']))
    {
        $_POST['package_price'] = 0;
    }

    $info = array('package_price'=>$_POST['package_price']);

    $activity_thumb = $image->upload_image($_FILES['activity_thumb'], 'activity_thumb');  //图片存放地址

    get_oss_add_file(array($activity_thumb));

    /* 插入数据 */
    $record = array(
        'act_name'          =>  $_POST['package_name'], 'act_desc'=>$_POST['desc'],
        'act_type'          =>  GAT_PACKAGE, 'start_time'=>$_POST['start_time'],
        'user_id'           =>  $adminru['ru_id'],
        'activity_thumb'    =>  $activity_thumb,  //ecmoban模板堂 --zhuo
        'end_time'          =>  $_POST['end_time'], 'is_finished'=>0, 
        'review_status'     =>  3,
        'ext_info'          =>  serialize($info)
    );

    $db->AutoExecute($ecs->table('goods_activity'),$record,'INSERT');

    /* 礼包编号 */
    $package_id = $db->insert_id();

    handle_packagep_goods($package_id);

    admin_log($_POST['package_name'],'add','package');
    $link[] = array('text' => $_LANG['back_list'], 'href'=>'package.php?act=list');
    $link[] = array('text' => $_LANG['continue_add'], 'href'=>'package.php?act=add');
    sys_msg($_LANG['add_succeed'],0,$link);
}

/*------------------------------------------------------ */
//-- 编辑活动
/*------------------------------------------------------ */
 elseif ($_REQUEST['act'] == 'edit') {
    /* 权限判断 */
    admin_priv('package_manage');
    
    $act_id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

    $package = get_package_info($act_id, "seller");
    $package_goods_list = get_package_goods($act_id,0,1); // 礼包商品

    $smarty->assign('package', $package);
    $smarty->assign('ur_here', $_LANG['package_edit']);
    $smarty->assign('action_link', array('text' => $_LANG['14_package_list'], 'href' => 'package.php?act=list&' . list_link_postfix()));

    set_default_filter(); //设置默认筛选	

    $smarty->assign('form_action', 'update');
    $smarty->assign('package_goods_list', $package_goods_list);
    $smarty->assign('ru_id', $package['ru_id']);

    assign_query_info();
    $smarty->display('package_info.dwt');
}

/*------------------------------------------------------ */
//-- 更新活动数据
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] =='update')
{
    /* 权限判断 */
    admin_priv('package_manage');
    
    $act_id = !empty($_POST['id']) ? intval($_POST['id']) : 0;
    
    /* 将时间转换成整数 */
    $_POST['start_time'] = local_strtotime($_POST['start_time']);
    $_POST['end_time']   = local_strtotime($_POST['end_time']);

    /* 处理提交数据 */
    if (empty($_POST['package_price']))
    {
        $_POST['package_price'] = 0;
    }

    /* 检查活动重名 */
    $sql = "SELECT COUNT(*) ".
           " FROM " . $ecs->table('goods_activity').
           " WHERE act_type='" . GAT_PACKAGE . "' AND act_name='" . $_POST['package_name'] . "' AND act_id <> '$act_id'" ;
    if ($db->getOne($sql))
    {
        sys_msg(sprintf($_LANG['package_exist'],  $_POST['package_name']) , 1);
    }
    
    $activity_thumb = $image->upload_image($_FILES['activity_thumb'], 'activity_thumb');  //图片存放地址
    get_oss_add_file(array($activity_thumb));
    
    $info = array('package_price'=>$_POST['package_price']);

    /* 更新数据 */
    $record = array(
        'act_name' => $_POST['package_name'], 
        'start_time' => $_POST['start_time'], 
        'end_time' => $_POST['end_time'],
        'act_desc' => $_POST['desc'], 
        'review_status' => 3,
        'ext_info'=>serialize($info)
    );
    
    if(!empty($activity_thumb)){
        $record['activity_thumb'] = $activity_thumb;
    }
    
    if (isset($_POST['review_status'])) {
        $review_status = !empty($_POST['review_status']) ? intval($_POST['review_status']) : 1;
        $review_content = !empty($_POST['review_content']) ? addslashes(trim($_POST['review_content'])) : '';

        $record['review_status'] = $review_status;
        $record['review_content'] = $review_content;
    }
    
    $db->autoExecute($ecs->table('goods_activity'), $record, 'UPDATE', "act_id = '$act_id' AND act_type = " . GAT_PACKAGE );

    admin_log($_POST['package_name'],'edit','package');
    $link[] = array('text' => $_LANG['back_list'], 'href'=>'package.php?act=list&' . list_link_postfix());
    sys_msg($_LANG['edit_succeed'],0,$link);
}

/*------------------------------------------------------ */
//-- 删除活动图片
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'drop_thumb')
{
    /* 权限判断 */
    admin_priv('package_manage');
    $act_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $ru_id = isset($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;

    /* 取得logo名称 */
    $sql = "SELECT activity_thumb FROM " .$ecs->table('goods_activity'). " WHERE act_id = '$act_id'";
    $activity_thumb = $db->getOne($sql);

    if (!empty($activity_thumb))
    {
        get_del_batch('', $act_id, array('activity_thumb'), 'act_id', 'goods_activity', 1); //删除图片
        
        $sql = "UPDATE " .$ecs->table('goods_activity'). " SET activity_thumb = '' WHERE act_id = '$act_id'";
        $db->query($sql);
    }
    $link= array(array('text' => $_LANG['edit_package'], 'href' => 'package.php?act=edit&id=' . $act_id), array('text' => $_LANG['14_package_list'], 'href' => 'package.php?act=list'));
    sys_msg($_LANG['drop_package_thumb_success'], 0, $link);
}

/*------------------------------------------------------ */
//-- 删除指定的活动
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'remove')
{
    check_authz_json('package_manage');

    $id = intval($_GET['id']);
    get_del_batch('', $id, array('activity_thumb'), 'act_id', 'goods_activity', 1); //删除图片
    
    $exc->drop($id);

    $sql = "DELETE FROM " .$ecs->table('package_goods') .
            " WHERE package_id='$id'";
    $db->query($sql);

    $url = 'package.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}


/*------------------------------------------------------ */
//-- 批量操作
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'batch')
{
    /* 检查权限 */
    admin_priv('package_manage');
    
    if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes']))
    {
        sys_msg("没有选择任何数据", 1);
    }
    $ids = !empty($_POST['checkboxes']) ? $_POST['checkboxes'] : 0;
    
    if (isset($_POST['type']))
    {
        // 删除
        if ($_POST['type'] == 'batch_remove')
        {
            /* 删除记录 */
            $sql = "DELETE FROM " . $ecs->table('goods_activity') .
                    " WHERE act_id " . db_create_in($ids);
            $res = $db->query($sql);
			if($res){
				get_del_batch('', $ids, array('activity_thumb'), 'act_id', 'goods_activity', 1); //删除图片
				
				$sql = "DELETE FROM " .$ecs->table('package_goods') .
						" WHERE package_id " . db_create_in($ids);
				$db->query($sql);
			}

            /* 记日志 */
            admin_log('', 'batch_remove', 'package_manage');

            /* 清除缓存 */
            clear_cache_files();

            $links[] = array('text' => $_LANG['back_list'], 'href' => 'package.php?act=list&' . list_link_postfix());
            sys_msg($_LANG['batch_drop_ok'], 0, $links);
        }
        
        // 审核
        elseif ($_POST['type'] == 'review_to')
        {
            // review_status = 3审核通过 2审核未通过
            $review_status = $_POST['review_status'];           
            
            $sql = "UPDATE " . $ecs->table('goods_activity') ." SET review_status = '$review_status' "
                . " WHERE act_id " . db_create_in($ids);
            
            if($db->query($sql))
            {
                $lnk[] = array('text' => $_LANG['back_list'], 'href' => 'package.php?act=list&seller_list=1&' . list_link_postfix());
                sys_msg($_LANG['package_adopt_status_set_success'], 0, $lnk);
            }
        }
    }
}

/*------------------------------------------------------ */
//-- 编辑活动名称
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'edit_package_name')
{
    check_authz_json('package_manage');

    $id = intval($_POST['id']);
    $val = json_str_iconv(trim($_POST['val']));

    /* 检查活动重名 */
    $sql = "SELECT COUNT(*) ".
           " FROM " . $ecs->table('goods_activity').
           " WHERE act_type='" . GAT_PACKAGE . "' AND act_name='$val' AND act_id <> '$id'" ;
    if ($db->getOne($sql))
    {
        make_json_error(sprintf($_LANG['package_exist'],  $val));
    }

    $exc->edit("act_name='$val'", $id);
    make_json_result(stripslashes($val));
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

    $opt = array();
    foreach ($arr AS $key => $val)
    {
        $opt[$key] = array('value' => $val['goods_id'],
                        'text' => $val['goods_name'],
                        'data' => $val['shop_price']);

        $opt[$key]['products'] = get_good_products($val['goods_id']);
    }
    
    make_json_result($opt);
}

/*------------------------------------------------------ */
//-- 增加一个商品
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'add_package_goods')
{
	
    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON;
    $result = array();
    check_authz_json('package_manage');

    $type = isset($_REQUEST['type']) ? intval($_REQUEST['type']) : 0; // 0为多商品，1为单商品
    
    $goods_ids = !empty($_REQUEST['goods_ids']) ? trim($_REQUEST['goods_ids']) : '';
    $package_id = isset($_REQUEST['pid']) ? intval($_REQUEST['pid']) : 0;
    $number     = isset($_REQUEST['num']) ? intval($_REQUEST['num']) : 1;
    $pbtype = !empty($_REQUEST['pbtype'])  ?  trim($_REQUEST['pbtype'])  :  '';
    if($goods_ids){
        $goods_ids_arr = explode(',', $goods_ids);
        if(!empty($goods_ids_arr)){
            foreach($goods_ids_arr as $v){
                $sql = "SELECT COUNT(*) FROM".$ecs->table('package_goods')."WHERE goods_id = '" .$v. "' LIMIT 1";
                $goods_count = $db->getOne($sql);
                if($goods_count == 0 || $type == 1){
                    $sql = "INSERT INTO " . $ecs->table('package_goods') . " (package_id, goods_id, goods_number, admin_id) " .
                    "VALUES ('$package_id', '" . $v . "', '$number', '$_SESSION[admin_id]')";
                    $db->query($sql, 'SILENT');
                }
            }
        }
    }
    if($type != 1) {
        //处理超值礼包商品
        $sql = "DELETE FROM" . $ecs->table('package_goods') . " WHERE package_id = '$package_id' AND goods_id NOT IN ($goods_ids)";
        $db->query($sql);
    }
    
    $arr = get_package_goods($package_id,0,1);
    $smarty->assign('pbtype',$pbtype);
    $smarty->assign('package_goods_list',$arr);
    $result['content'] = $GLOBALS['smarty']->fetch('library/getsearchgoodsDiv.lbi');
    clear_cache_files();
    die(json_encode($result));
}

/*------------------------------------------------------ */
//-- 删除一个商品
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'drop_package_goods')
{
    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON;
    $result = array();

    check_authz_json('package_manage');

    $package_id = isset($_REQUEST['pid']) ? intval($_REQUEST['pid']) : 0;
    $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
    $product_id = isset($_REQUEST['product_id']) ? intval($_REQUEST['product_id']) : 0;
    
    $sql = "DELETE FROM " .$ecs->table('package_goods') .
                " WHERE package_id='$package_id' AND goods_id = '$goods_id' AND product_id = '$product_id'" ;
    if ($package_id == 0)
    {
        $sql .= " AND admin_id = '$_SESSION[admin_id]'";
    }
    $db->query($sql);
    
    die(json_encode($result));
}
/*------------------------------------------------------ */
//-- 编辑一个商品数量
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'edit_package_nuber')
{
   include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON;
    $result = array('error'=>0);

    check_authz_json('package_manage');

    $package_id = isset($_REQUEST['pid']) ? intval($_REQUEST['pid']) : 0;
    $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
    $product_id = isset($_REQUEST['product_id']) ? intval($_REQUEST['product_id']) : 0;
    $num = isset($_REQUEST['num']) ? intval($_REQUEST['num']) : 0;
    $where  = " WHERE package_id='$package_id' AND goods_id = '$goods_id' AND product_id = '$product_id'";
    if($num > 0){
        $sql = "UPDATE" .$ecs->table('package_goods') .
                " SET goods_number = '$num' ".$where ;
        
        $db->query($sql);
    }else{
        $sql = "SELECT goods_number FROM".$ecs->table('package_goods').$where ;
        $goods_number = $db->getOne($sql);
        if($goods_number > 0){
            $result['goods_number'] = $goods_number;
        }else{
            $result['goods_number'] = 1;
        }
        
        $result['error'] = 1;
        $result['msg'] = $_LANG['goods_number_notic_one'];
    }
    
    die(json_encode($result));
}
/*------------------------------------------------------ */
//-- 编辑一个商品属性
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'edit_package_product')
{
   include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON;
    $result = array('error'=>0);

    check_authz_json('package_manage');

    $package_id = isset($_REQUEST['pid']) ? intval($_REQUEST['pid']) : 0;
    $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
    $product_id = isset($_REQUEST['product_id']) ? intval($_REQUEST['product_id']) : 0;
    $old_product_id = isset($_REQUEST['old_product_id']) ? intval($_REQUEST['old_product_id']) : 0;
    $where  = " WHERE package_id='$package_id' AND goods_id = '$goods_id'";
    if($product_id > 0){
        //判断改属性商品是否存在
        $sql = "SELECT COUNT(*) FROM".$ecs->table('package_goods').$where." AND product_id = '$product_id'";
        $product_count = $db->getOne($sql);
        if($product_count > 0){
            $result['error'] = 1;
            $result['msg'] = $_LANG['goods_number_notic_two'];
        }else{
             $sql = "UPDATE" .$ecs->table('package_goods') .
                " SET product_id = '$product_id' ".$where."AND product_id = '$old_product_id'" ;
            $db->query($sql);
        }
    }else{
        $result['error'] = 1;
        $result['msg'] = $_LANG['system_error'];
    }
    
    die(json_encode($result));
}

/**
 * 获取活动列表
 *
 * @access  public
 *
 * @return void
 */
function get_packagelist($ru_id)
{
    $result = get_filter();
    if ($result === false)
    {
        /* 查询条件 */
        $filter['keywords']   = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
        {
            $filter['keywords'] = json_str_iconv($filter['keywords']);
        }
        $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'ga.act_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        $filter['seller_list'] = isset($_REQUEST['seller_list']) && !empty($_REQUEST['seller_list']) ? 1 : 0;  //商家和自营订单标识
        $filter['review_status']    = empty($_REQUEST['review_status']) ? 0 : intval($_REQUEST['review_status']);
        
        //卖场 start
        $filter['rs_id'] = empty($_REQUEST['rs_id']) ? 0 : intval($_REQUEST['rs_id']);
        $adminru = get_admin_ru_id();
        if($adminru['rs_id'] > 0){
            $filter['rs_id'] = $adminru['rs_id'];
        }
        //卖场 end
        
        $where = (!empty($filter['keywords'])) ? " AND ga.act_name like '%". mysql_like_quote($filter['keywords']) ."%'" : '';
		
	//ecmoban模板堂 --zhuo start
        if($ru_id > 0){
            $where .= " and ga.user_id = '$ru_id'";
        }
        //ecmoban模板堂 --zhuo end
        
        if( $filter['review_status']){
            $where .= " AND ga.review_status = '" .$filter['review_status']. "' ";
        }
        
        //卖场
        $where .= get_rs_null_where('ga.user_id', $filter['rs_id']);
        
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
                        $where .= " AND ga.user_id = '" .$filter['merchant_id']. "' ";
                    }elseif($filter['store_search'] == 2){
                        $store_where .= " AND msi.rz_shopName LIKE '%" . mysql_like_quote($filter['store_keyword']) . "%'";
                    }elseif($filter['store_search'] == 3){
                        $store_where .= " AND msi.shoprz_brandName LIKE '%" . mysql_like_quote($filter['store_keyword']) . "%' " . $store_search_where;
                    }

                    if($filter['store_search'] > 1){
                        $where .= " AND (SELECT msi.user_id FROM " . $GLOBALS['ecs']->table('merchants_shop_information') .' as msi ' .  
                                  " WHERE msi.user_id = ga.user_id $store_where) > 0 ";
                    }
                }else{
                    $where .= ' AND ga.user_id = 0';
                }    
           }
        }
        //管理员查询的权限 -- 店铺查询 end
        $where .= !empty($filter['seller_list']) ? " AND ga.user_id > 0 " : " AND ga.user_id = 0 "; //区分商家和自营

        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('goods_activity') ." AS ga ".
               " WHERE ga.act_type =" . GAT_PACKAGE . $where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $filter = page_and_size($filter);

        /* 获活动数据 */
        $sql = "SELECT ga.act_id, ga.act_name AS package_name, ga.start_time, ga.end_time, ga.is_finished, ga.ext_info, ga.user_id, ga.goods_id, ga.review_status, ga.review_content ".
               " FROM " . $GLOBALS['ecs']->table('goods_activity') ." AS ga ".
               " WHERE ga.act_type = " . GAT_PACKAGE . $where .
               " ORDER by $filter[sort_by] $filter[sort_order] LIMIT ". $filter['start'] .", " . $filter['page_size'];

        $filter['keywords'] = stripslashes($filter['keywords']);
        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }

    $row = $GLOBALS['db']->getAll($sql);

    foreach ($row AS $key => $val)
    {
        $row[$key]['start_time'] = local_date($GLOBALS['_CFG']['time_format'], $val['start_time']);
        $row[$key]['end_time']   = local_date($GLOBALS['_CFG']['time_format'], $val['end_time']);
        $info = unserialize($row[$key]['ext_info']);
        unset($row[$key]['ext_info']);
        if ($info)
        {
            foreach ($info as $info_key => $info_val)
            {
                $row[$key][$info_key] = $info_val;
            }
        }
        
        $row[$key]['ru_name'] = get_shop_name($val['user_id'], 1); //ecmoban模板堂 --zhuo
    }

    $arr = array('packages' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}

/**
 * 保存某礼包的商品
 * @param   int     $package_id
 * @return  void
 */
function handle_packagep_goods($package_id)
{
    $sql = "UPDATE " . $GLOBALS['ecs']->table('package_goods') . " SET " .
            " package_id = '$package_id' " .
            " WHERE package_id = '0'" .
            " AND admin_id = '$_SESSION[admin_id]'";
    $GLOBALS['db']->query($sql);
}

?>