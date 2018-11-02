<?php

/**
 * ECSHOP 管理中心预售商品管理
 * ============================================================================
 * * 版权所有 2005-2016 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: li $
 * $Id: presale.php 17217 2015-11-8 li $
 */

define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . 'includes/lib_goods.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . '/' . ADMIN_PATH . '/includes/lib_goods.php');

/* 检查权限 */
admin_priv('presale');

//ecmoban模板堂 --zhuo start
$adminru = get_admin_ru_id();
if($adminru['ru_id'] == 0){
    $smarty->assign('priv_ru',   1);
}else{
    $smarty->assign('priv_ru',   0);
}
//ecmoban模板堂 --zhuo end

/* act操作项的初始化 */
if (empty($_REQUEST['act']))
{
    $_REQUEST['act'] = 'list';
}
else
{
    $_REQUEST['act'] = trim($_REQUEST['act']);
}

/*------------------------------------------------------ */
//-- 预售活动列表
/*------------------------------------------------------ */

if ($_REQUEST['act'] == 'list')
{
    /* 模板赋值 */
    $smarty->assign('full_page',    1);
    $smarty->assign('ur_here',      $_LANG['presale_list']);
    $smarty->assign('action_link',  array('href' => 'presale.php?act=add', 'text' => $_LANG['add_presale']));
    
    if($adminru['ru_id'] == 0){
            $smarty->assign('presale_cat_link',  array('href' => 'presale_cat.php?act=list', 'text' => $_LANG['presale_cat_list']));
    }
    
    $list = presale_list($adminru['ru_id']);
	
    $smarty->assign('presale_list',   $list['item']);
    $smarty->assign('filter',           $list['filter']);
    $smarty->assign('record_count',     $list['record_count']);
    $smarty->assign('page_count',       $list['page_count']);
    
    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);
    
    $store_list = get_common_store_list();
    $smarty->assign('store_list',        $store_list);

    //区分自营和店铺
    self_seller(BASENAME($_SERVER['PHP_SELF']));     
    
    /* 显示商品列表页面 */
    assign_query_info();
    $smarty->display('presale_list.dwt');
}

elseif ($_REQUEST['act'] == 'query')
{
    $list = presale_list($adminru['ru_id']);

    $smarty->assign('presale_list', $list['item']);
    $smarty->assign('filter',         $list['filter']);
    $smarty->assign('record_count',   $list['record_count']);
    $smarty->assign('page_count',     $list['page_count']);

    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('presale_list.dwt'), '',
        array('filter' => $list['filter'], 'page_count' => $list['page_count']));
}

/*------------------------------------------------------ */
//-- 添加/编辑预售活动
/*------------------------------------------------------ */
 elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit') {
    /* 初始化/取得预售活动信息 */
    if ($_REQUEST['act'] == 'add') {
        $presale = array(
            'cat_id' => 0,
			'act_desc' => '',
            'start_time' => date('Y-m-d H:i:s', time() + 86400),
            'end_time' => date('Y-m-d H:i:s', time() + 4 * 86400),
            'pay_start_time' => date('Y-m-d H:i:s', time() + 4 * 86400 + 1),
            'pay_end_time' => date('Y-m-d H:i:s', time() + 6 * 86400)
        );
        $smarty->assign('ur_here', $_LANG['add_presale']);
        $smarty->assign('form_action', "insert");
    } else {
        $presale_id = intval($_REQUEST['id']);
        if ($presale_id <= 0) {
            die('invalid param');
        }
        
        $presale = presale_info($presale_id, 0, 0, "seller");
        $smarty->assign('ur_here', $_LANG['edit_presale']);
        $smarty->assign('form_action', "update");
    }
    $smarty->assign('presale', $presale);
	
    /* 创建 html editor */
    create_html_editor2('act_desc', 'act_desc', $presale['act_desc']);	

    /* 模板赋值 */
    $smarty->assign('action_link', list_link($_REQUEST['act'] == 'add'));

    $cat_select = presale_cat_list(0, $presale['cat_id'], false, 0, true, '', 1);
	
    /* 缩进分类 */
    foreach ($cat_select as $k => $v) {
        if ($v['level']) {
            $level = '';
            for ($i = 0; $i < $v['level']; $i++) {
                $level .= '&nbsp;&nbsp;&nbsp;&nbsp;';
            }
            $cat_select[$k]['name'] = $level . $v['name'];
        }
    }
	
    $smarty->assign('cat_select', $cat_select);

    set_default_filter(); //设置默认筛选
    $smarty->assign('ru_id', $presale['ru_id']);

    /* 显示模板 */
    assign_query_info();
    $smarty->display('presale_info.dwt');
}

/*------------------------------------------------------ */
//-- 添加/编辑预售活动的提交
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] =='insert_update')
{

    /* 取得预售活动id */
    $presale_id = intval($_POST['act_id']);
    if (isset($_POST['finish']) || isset($_POST['succeed']) || isset($_POST['fail']) || isset($_POST['mail']))
    {
        if ($presale_id <= 0)
        {
            sys_msg($_LANG['error_presale'], 1);
        }
        $presale = presale_info($presale_id, 0, 0, "seller");
        if (empty($presale))
        {
            sys_msg($_LANG['error_presale'], 1);
        }
    }

    if (isset($_POST['finish']))
    {
        /* 判断订单状态 */
        if ($presale['status'] != GBS_UNDER_WAY)
        {
            sys_msg($_LANG['error_status'], 1);
        }

        /* 结束预售活动，修改结束时间为当前时间 */
        $sql = "UPDATE " . $ecs->table('presale_activity') .
                " SET end_time = '" . gmtime() . "' " .
                "WHERE act_id = '$presale_id' LIMIT 1";
        $db->query($sql);

        /* 清除缓存 */
        clear_cache_files();

        /* 提示信息 */
        $links = array(
            array('href' => 'presale.php?act=list', 'text' => $_LANG['back_list'])
        );
        sys_msg($_LANG['edit_success'], 0, $links);
    }
    elseif (isset($_POST['succeed']))
    {
        /* 设置活动成功 */

        /* 判断订单状态 */
        if ($presale['status'] != GBS_FINISHED)
        {
            sys_msg($_LANG['error_status'], 1);
        }

        /* 如果有订单，更新订单信息 */
        if ($presale['total_order'] > 0)
        {
            /* 查找该预售活动的已确认或未确认订单（已取消的就不管了） */
            $sql = "SELECT order_id " .
                    "FROM " . $ecs->table('order_info') .
                    " WHERE extension_code = 'presale' " .
                    "AND extension_id = '$presale_id' " .
                    "AND (order_status = '" . OS_CONFIRMED . "' or order_status = '" . OS_UNCONFIRMED . "')";
            $order_id_list = $db->getCol($sql);

            /* 更新订单商品价 */
            $final_price = $presale['trans_price'];
            $sql = "UPDATE " . $ecs->table('order_goods') .
                    " SET goods_price = '$final_price' " .
                    "WHERE order_id " . db_create_in($order_id_list);
            $db->query($sql);

            /* 查询订单商品总额 */
            $sql = "SELECT order_id, SUM(goods_number * goods_price) AS goods_amount " .
                    "FROM " . $ecs->table('order_goods') .
                    " WHERE order_id " . db_create_in($order_id_list) .
                    " GROUP BY order_id";
            $res = $db->query($sql);
            while ($row = $db->fetchRow($res))
            {
                $order_id = $row['order_id'];
                $goods_amount = floatval($row['goods_amount']);

                /* 取得订单信息 */
                $order = order_info($order_id);

                /* 判断订单是否有效：余额支付金额 + 已付款金额 >= 保证金 */
                if ($order['surplus'] + $order['money_paid'] >= $presale['deposit'])
                {
                    /* 有效，设为已确认，更新订单 */

                    // 更新商品总额
                    $order['goods_amount'] = $goods_amount;

                    // 如果保价，重新计算保价费用
                    if ($order['insure_fee'] > 0)
                    {
                        $shipping = shipping_info($order['shipping_id']);
                        $order['insure_fee'] = shipping_insure_fee($shipping['shipping_code'], $goods_amount, $shipping['insure']);
                    }

                    // 重算支付费用
                    $order['order_amount'] = $order['goods_amount'] + $order['shipping_fee']
                        + $order['insure_fee'] + $order['pack_fee'] + $order['card_fee']
                        - $order['money_paid'] - $order['surplus'];
                    if ($order['order_amount'] > 0)
                    {
                        $order['pay_fee'] = pay_fee($order['pay_id'], $order['order_amount']);
                    }
                    else
                    {
                        $order['pay_fee'] = 0;
                    }

                    // 计算应付款金额
                    $order['order_amount'] += $order['pay_fee'];

                    // 计算付款状态
                    if ($order['order_amount'] > 0)
                    {
                        $order['pay_status'] = PS_UNPAYED;
                        $order['pay_time'] = 0;
                    }
                    else
                    {
                        $order['pay_status'] = PS_PAYED;
                        $order['pay_time'] = gmtime();
                    }

                    // 如果需要退款，退到帐户余额
                    if ($order['order_amount'] < 0)
                    {
                        // todo （现在手工退款）
                    }

                    // 订单状态
                    $order['order_status'] = OS_CONFIRMED;
                    $order['confirm_time'] = gmtime();

                    // 更新订单
                    $order = addslashes_deep($order);
                    update_order($order_id, $order);
                }
                else
                {
                    /* 无效，取消订单，退回已付款 */

                    // 修改订单状态为已取消，付款状态为未付款
                    $order['order_status'] = OS_CANCELED;
                    $order['to_buyer'] = $_LANG['cancel_order_reason'];
                    $order['pay_status'] = PS_UNPAYED;
                    $order['pay_time'] = 0;

                    /* 如果使用余额或有已付款金额，退回帐户余额 */
                    $money = $order['surplus'] + $order['money_paid'];
                    if ($money > 0)
                    {
                        $order['surplus'] = 0;
                        $order['money_paid'] = 0;
                        $order['order_amount'] = $money;

                        // 退款到帐户余额
                        order_refund($order, 1, $_LANG['cancel_order_reason'] . ':' . $order['order_sn']);
                    }

                    /* 更新订单 */
                    $order = addslashes_deep($order);
                    update_order($order['order_id'], $order);
                }
            }
        }

        /* 修改预售活动状态为成功 */
        $sql = "UPDATE " . $ecs->table('presale_activity') .
                " SET is_finished = '" . GBS_SUCCEED . "' " .
                "WHERE act_id = '$presale_id' LIMIT 1";
        $db->query($sql);

        /* 清除缓存 */
        clear_cache_files();

        /* 提示信息 */
        $links = array(
            array('href' => 'presale.php?act=list', 'text' => $_LANG['back_list'])
        );
        sys_msg($_LANG['edit_success'], 0, $links);
    }
    elseif (isset($_POST['fail']))
    {
        /* 设置活动失败 */

        /* 判断订单状态 */
        if ($presale['status'] != GBS_FINISHED)
        {
            sys_msg($_LANG['error_status'], 1);
        }

        /* 如果有有效订单，取消订单 */
        if ($presale['valid_order'] > 0)
        {
            /* 查找未确认或已确认的订单 */
            $sql = "SELECT * " .
                    "FROM " . $ecs->table('order_info') .
                    " WHERE extension_code = 'presale' " .
                    "AND extension_id = '$presale_id' " .
                    "AND (order_status = '" . OS_CONFIRMED . "' OR order_status = '" . OS_UNCONFIRMED . "') ";
            $res = $db->query($sql);
            while ($order = $db->fetchRow($res))
            {
                // 修改订单状态为已取消，付款状态为未付款
                $order['order_status'] = OS_CANCELED;
                $order['to_buyer'] = $_LANG['cancel_order_reason'];
                $order['pay_status'] = PS_UNPAYED;
                $order['pay_time'] = 0;

                /* 如果使用余额或有已付款金额，退回帐户余额 */
                $money = $order['surplus'] + $order['money_paid'];
                if ($money > 0)
                {
                    $order['surplus'] = 0;
                    $order['money_paid'] = 0;
                    $order['order_amount'] = $money;

                    // 退款到帐户余额
                    order_refund($order, 1, $_LANG['cancel_order_reason'] . ':' . $order['order_sn'], $money);
                }

                /* 更新订单 */
                $order = addslashes_deep($order);
                update_order($order['order_id'], $order);
            }
        }

        /* 修改预售活动状态为失败，记录失败原因（活动说明） */
        $sql = "UPDATE " . $ecs->table('presale_activity') .
                " SET is_finished = '" . GBS_FAIL . "', " .
                    "act_desc = '$_POST[act_desc]' " .
                "WHERE act_id = '$presale_id' LIMIT 1";
        $db->query($sql);

        /* 清除缓存 */
        clear_cache_files();

        /* 提示信息 */
        $links = array(
            array('href' => 'presale.php?act=list', 'text' => $_LANG['back_list'])
        );
        sys_msg($_LANG['edit_success'], 0, $links);
    }
    elseif (isset($_POST['mail']))
    {
        /* 发送通知邮件 */

        /* 判断订单状态 */
        if ($presale['status'] != GBS_SUCCEED)
        {
            sys_msg($_LANG['error_status'], 1);
        }

        /* 取得邮件模板 */
        $tpl = get_mail_template('presale');

        /* 初始化订单数和成功发送邮件数 */
        $count = 0;
        $send_count = 0;

        /* 取得有效订单 */
        $sql = "SELECT o.consignee, o.add_time, g.goods_number, o.order_sn, " .
                    "o.order_amount, o.order_id, o.email " .
                "FROM " . $ecs->table('order_info') . " AS o, " .
                    $ecs->table('order_goods') . " AS g " .
                "WHERE o.order_id = g.order_id " .
                "AND o.extension_code = 'presale' " .
                "AND o.extension_id = '$presale_id' " .
                "AND o.order_status = '" . OS_CONFIRMED . "'";
        $res = $db->query($sql);
        while ($order = $db->fetchRow($res))
        {
            /* 邮件模板赋值 */
            $smarty->assign('consignee',    $order['consignee']);
            $smarty->assign('add_time',     local_date($_CFG['time_format'], $order['add_time']));
            $smarty->assign('goods_name',   $presale['goods_name']);
            $smarty->assign('goods_number', $order['goods_number']);
            $smarty->assign('order_sn',     $order['order_sn']);
            $smarty->assign('order_amount', price_format($order['order_amount']));
            $smarty->assign('shop_url',     $ecs->url() . 'user.php?act=order_detail&order_id='.$order['order_id']);
            $smarty->assign('shop_name',    $_CFG['shop_name']);
            $smarty->assign('send_date',    local_date($GLOBALS['_CFG']['time_format'], gmtime()));

            /* 取得模板内容，发邮件 */
            $content = $smarty->fetch('str:' . $tpl['template_content']);
            if (send_mail($order['consignee'], $order['email'], $tpl['template_subject'], $content, $tpl['is_html']))
            {
                $send_count++;
            }
            $count++;
        }

        /* 提示信息 */
        sys_msg(sprintf($_LANG['mail_result'], $count, $send_count));
    }
    else
    {
        /* 保存预售信息 */
        $goods_id = intval($_POST['goods_id']);
        if ($goods_id <= 0)
        {
            sys_msg($_LANG['error_goods_null']);
        }
        
        $info = goods_presale($goods_id);
        if ($info && $info['act_id'] != $presale_id)
        {
            sys_msg($_LANG['error_goods_exist']);
        }

        $goods_name = $db->getOne("SELECT goods_name FROM " . $ecs->table('goods') . " WHERE goods_id = '$goods_id'");

        $act_name = empty($_POST['act_name']) ? $goods_name : sub_str($_POST['act_name'], 0, 255, false);

        $deposit = floatval($_POST['deposit']);
        
        if ($deposit < 0)
        {
            $deposit = 0;
        }

        /* 检查开始时间和结束时间是否合理 */
        $start_time = local_strtotime($_POST['start_time']);
        $end_time = local_strtotime($_POST['end_time']);
        $pay_start_time = local_strtotime($_POST['pay_start_time']);//liu
        $pay_end_time = local_strtotime($_POST['pay_end_time']);//liu
        if ($start_time >= $end_time || $pay_start_time >= $pay_end_time || $end_time > $pay_start_time)//change liu
        {
            sys_msg($_LANG['invalid_time']);
        }

        $presale = array(
            'act_name'   => $act_name,
            'act_desc'   => $_POST['act_desc'],
            'cat_id'   => intval($_POST['cat_id']),
            'goods_id'   => $goods_id,
            'goods_name' => $goods_name,
            'start_time'    => $start_time,
            'end_time'      => $end_time,
            'pay_start_time'    => $pay_start_time,//liu
            'pay_end_time'      => $pay_end_time,//liu
            'deposit'           => $deposit
        );
		
        /* 清除缓存 */
        clear_cache_files();

        /* 保存数据 */
        if ($presale_id > 0)
        {
            if (isset($_POST['review_status'])) {
                $review_status = !empty($_POST['review_status']) ? intval($_POST['review_status']) : 1;
                $review_content = !empty($_POST['review_content']) ? addslashes(trim($_POST['review_content'])) : '';

                $presale['review_status'] = $review_status;
                $presale['review_content'] = $review_content;
            }

            /* update */
            $db->autoExecute($ecs->table('presale_activity'), $presale, 'UPDATE', "act_id = '$presale_id'");

            /* log */
            admin_log(addslashes($goods_name) . '[' . $presale_id . ']', 'edit', 'presale');

            /* 提示信息 */
            $links = array(
                array('href' => 'presale.php?act=list&' . list_link_postfix(), 'text' => $_LANG['back_list'])
            );
            sys_msg($_LANG['edit_success'], 0, $links);
        }
        else
        {
            $presale['review_status'] = 3;
            $presale['user_id'] = $adminru['ru_id'];
            
            /* insert */
            $db->autoExecute($ecs->table('presale_activity'), $presale, 'INSERT');

            /* log */
            admin_log(addslashes($goods_name), 'add', 'presale');

            /* 提示信息 */
            $links = array(
                array('href' => 'presale.php?act=add', 'text' => $_LANG['continue_add']),
                array('href' => 'presale.php?act=list', 'text' => $_LANG['back_list'])
            );
            sys_msg($_LANG['add_success'], 0, $links);
        }
    }
}


/*------------------------------------------------------ */
//-- 批量操作
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'batch')
{
    /* 检查权限 */
    check_authz_json('presale');
    
    if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes']))
    {
        sys_msg($_LANG['not_select_data'], 1);
    }
    $ids = !empty($_POST['checkboxes']) ? $_POST['checkboxes'] : 0;
    
    if (isset($_POST['type']))
    {
        // 删除
        if ($_POST['type'] == 'batch_remove')
        {
            $del_count = 0; //初始化删除数量
			foreach ($ids AS $key => $id)
			{
				/* 取得预售活动信息 */
				$presale = presale_info($id, 0, 0, "seller");

				/* 如果预售活动已经有订单，不能删除 */
				if ($presale['valid_order'] <= 0)
				{
					/* 删除预售活动 */
					$sql = "DELETE FROM " . $GLOBALS['ecs']->table('presale_activity') .
							" WHERE act_id = '$id' LIMIT 1";
					$GLOBALS['db']->query($sql, 'SILENT');

					admin_log(addslashes($presale['goods_name']) . '[' . $id . ']', 'remove', 'presale');
					$del_count++;
				}
			}

			/* 如果删除了预售活动，清除缓存 */
			if ($del_count > 0)
			{
				clear_cache_files();
			}

			$links[] = array('text' => $_LANG['back_list'], 'href'=>'presale.php?act=list');
			sys_msg(sprintf($_LANG['batch_drop_success'], $del_count), 0, $links);
        }
        
        // 审核
        elseif ($_POST['type'] == 'review_to')
        {
            // review_status = 3审核通过 2审核未通过
            $review_status = $_POST['review_status'];
            
            $sql = "UPDATE " . $ecs->table('presale_activity') ." SET review_status = '$review_status' "
                . " WHERE act_id " . db_create_in($ids);
            
            if($db->query($sql))
            {
                $lnk[] = array('text' => $_LANG['back_list'], 'href' => 'presale.php?act=list&seller_list=1&' . list_link_postfix());
                sys_msg($_LANG['integral_goods_adopt_status_success'], 0, $lnk);
            }
        }
    }
}

/*------------------------------------------------------ */
//-- 搜索商品
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'search_goods')
{
    check_authz_json('presale');

    include_once(ROOT_PATH . 'includes/cls_json.php');

    $json   = new JSON;
    $filter = $json->decode($_GET['JSON']);
    $arr    = get_goods_list($filter);

    make_json_result($arr);
}

/*------------------------------------------------------ */
//-- 获取本店价
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'get_price')
{
    check_authz_json('presale');

    include_once(ROOT_PATH . 'includes/cls_json.php');

    $json   = new JSON;
    $goods_id = $json->decode($_GET['goods_id']);
    $shop_price    = get_shop_price($goods_id);
    make_json_result($shop_price);
}

/*------------------------------------------------------ */
//-- 编辑保证金
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'edit_deposit')
{
    check_authz_json('presale');

    $id = intval($_POST['id']);
    $val = floatval($_POST['val']);

    $sql = "UPDATE " . $ecs->table('presale_activity') .
            " SET deposit = '" . $val . "'" .
            " WHERE act_id = '$id'";
    $db->query($sql);

    clear_cache_files();

    make_json_result(number_format($val, 2));
}

/*------------------------------------------------------ */
//-- 删除预售活动
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'remove')
{
    check_authz_json('presale');

    $id = intval($_GET['id']);

    /* 取得预售活动信息 */
    $presale = presale_info($id, 0, 0, "seller");

    /* 如果预售活动已经有订单，不能删除 */
    if ($presale['valid_order'] > 0)
    {
        make_json_error($_LANG['error_exist_order']);
    }

    /* 删除预售活动 */
    $sql = "DELETE FROM " . $ecs->table('presale_activity') . " WHERE act_id = '$id' LIMIT 1";
    $db->query($sql);

    admin_log(addslashes($presale['goods_name']) . '[' . $id . ']', 'remove', 'presale');

    clear_cache_files();

    $url = 'presale.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}

/*
 * 取得预售活动列表
 * @return   array
 */
function presale_list($ru_id)
{
    $result = get_filter();
    $where = "";
    if ($result === false)
    {
        /* 过滤条件 */
        $filter['keyword']      = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
        {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }
        $filter['sort_by']      = empty($_REQUEST['sort_by']) ? 'ga.act_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order']   = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        $filter['seller_list'] = isset($_REQUEST['seller_list']) && !empty($_REQUEST['seller_list']) ? 1 : 0;  //商家和自营订单标识
        $filter['review_status']    = empty($_REQUEST['review_status']) ? 0 : intval($_REQUEST['review_status']);
        
        //卖场 start
        $filter['rs_id'] = empty($_REQUEST['rs_id']) ? 0 : intval($_REQUEST['rs_id']);
        $adminru = get_admin_ru_id();
        if($adminru['rs_id'] > 0){
            $filter['rs_id'] = $adminru['rs_id'];
        }
        //卖场 end
		
        $where = (!empty($filter['keyword'])) ? " AND (ga.goods_name LIKE '%" . mysql_like_quote($filter['keyword']) . "%')" : '';
        
        if( $filter['review_status']){
            $where .= " AND ga.review_status = '" .$filter['review_status']. "' ";
        }
        
        //卖场
        $where .= get_rs_null_where('ga.user_id', $filter['rs_id']);
        
        //管理员查询的权限 -- 店铺查询 start
        $filter['store_search'] = !isset($_REQUEST['store_search']) ? -1 : intval($_REQUEST['store_search']);
        $filter['merchant_id'] = isset($_REQUEST['merchant_id']) ? intval($_REQUEST['merchant_id']) : 0;
        $filter['store_keyword'] = isset($_REQUEST['store_keyword']) ? trim($_REQUEST['store_keyword']) : '';
        
        //ecmoban模板堂 --zhuo start
        if($ru_id > 0){
            $where .= " and ga.user_id = '$ru_id'";
        }
        //ecmoban模板堂 --zhuo end
        
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
                    $where .= " AND ga.user_id = 0";
                }    
           }
        }
        //管理员查询的权限 -- 店铺查询 end
        $where .= !empty($filter['seller_list']) ? " AND ga.user_id > 0 " : " AND ga.user_id = 0 "; //区分商家和自营

        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('presale_activity') ." AS ga ".
                " WHERE 1 $where";
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        /* 分页大小 */
        $filter = page_and_size($filter);

        /* 查询 */
        $sql = "SELECT ga.* ".
                "FROM " . $GLOBALS['ecs']->table('presale_activity') ." AS ga ".
                " WHERE 1 $where ".
                " ORDER BY $filter[sort_by] $filter[sort_order] ".
                " LIMIT ". $filter['start'] .", $filter[page_size]";
	
        $filter['keyword'] = stripslashes($filter['keyword']);
        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }
    $res = $GLOBALS['db']->query($sql);

    $list = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $stat = presale_stat($row['act_id'], $row['deposit']);
        $arr = array_merge($row, $stat);

        $status = presale_status($arr);

        $arr['start_time']  = local_date($GLOBALS['_CFG']['date_format'], $arr['start_time']);
        $arr['end_time']    = local_date($GLOBALS['_CFG']['date_format'], $arr['end_time']);
        $arr['pay_start_time']  = local_date($GLOBALS['_CFG']['date_format'], $arr['pay_start_time']);
        $arr['pay_end_time']    = local_date($GLOBALS['_CFG']['date_format'], $arr['pay_end_time']);
        $arr['cur_status']  = $GLOBALS['_LANG']['gbs'][$status];
        
        $arr['act_name'] = !empty($arr['act_name']) ? $arr['act_name'] : $arr['goods_name'];
        $arr['shop_name'] = get_shop_name($row['user_id'], 1);
        
        $list[] = $arr;
    }
    $arr = array('item' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}

/**
 * 取得某商品的预售活动
 * @param   int     $goods_id   商品id
 * @return  array
 */
function goods_presale($goods_id)
{
    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('presale_activity') .
            " WHERE goods_id = '$goods_id' " .
            " AND start_time <= " . gmtime() .
            " AND end_time >= " . gmtime() . " LIMIT 1";

    return $GLOBALS['db']->getRow($sql);
}

/**
 * 列表链接
 * @param   bool    $is_add         是否添加（插入）
 * @return  array('href' => $href, 'text' => $text)
 */
function list_link($is_add = true)
{
    $href = 'presale.php?act=list';
    if (!$is_add)
    {
        $href .= '&' . list_link_postfix();
    }

    return array('href' => $href, 'text' => $GLOBALS['_LANG']['presale_list']);
}

/*
* 获取商品的本店售价，供参考预售商品定金参考比对
*/
function get_shop_price($goods_id){
	$sql = " SELECT shop_price FROM ".$GLOBALS['ecs']->table('goods')." WHERE goods_id = '$goods_id' ";
	return $GLOBALS['db']->getOne($sql);
}

?>