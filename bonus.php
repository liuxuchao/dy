<?php

/**
 * DSC 红包前台文件
 * ============================================================================
 * 版权所有 2005-2016 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: Zhuo $
 * $Id: bonus.php 2016-01-04 Zhuo $
 */
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
require(ROOT_PATH . '/includes/lib_area.php');  //ecmoban模板堂 --zhuo
require(ROOT_PATH . 'includes/lib_publicfunc.php'); //ecmoban模板堂 --zhuo

/* ------------------------------------------------------ */
//-- act 操作项的初始化
/* ------------------------------------------------------ */
if (empty($_REQUEST['act'])) {
    get_go_index(1);
}

/* ------------------------------------------------------ */
//-- 红包领取页
/* ------------------------------------------------------ */
if ($_REQUEST['act'] == 'bonus_info') {
    /* 模板赋值 */
    assign_template();
    $position = assign_ur_here();
    $smarty->assign('page_title', $position['title']);    // 页面标题
    $smarty->assign('ur_here', $position['ur_here']);  // 当前位置

    $smarty->assign('feed_url',        ($_CFG['rewrite'] == 1) ? 'feed.xml' : 'feed.php'); // RSS URL
    $smarty->assign('helps',           get_shop_help());       // 网店帮助     

    /* 获取数据 */
    $type_id = !empty($_GET['id']) ? intval($_GET['id']) : 0;
    $bonus_info = $db->getRow("SELECT * FROM " .$ecs->table('bonus_type'). " WHERE type_id = '$type_id'");
    $bonus_info['send_start_date']   = local_date('Y-m-d H:i:s', $bonus_info['send_start_date']);
    $bonus_info['send_end_date']     = local_date('Y-m-d H:i:s', $bonus_info['send_end_date']);
    $bonus_info['use_start_date']    = local_date('Y-m-d H:i:s', $bonus_info['use_start_date']);
    $bonus_info['use_end_date']      = local_date('Y-m-d H:i:s', $bonus_info['use_end_date']);
    $bonus_info['type_money_formatted']      = price_format($bonus_info['type_money']);
    $bonus_info['min_goods_amount_formatted']      = price_format($bonus_info['min_goods_amount']);
    $bonus_info['shop_name'] = get_shop_name($bonus_info['user_id'], 1); //店铺名称
    $smarty->assign('bonus_info', $bonus_info);

    /* 是否领过 */
    if($_SESSION['user_id']){
        $sql = " SELECT bonus_id FROM ".$GLOBALS['ecs']->table('user_bonus')." WHERE bonus_type_id = '$type_id' AND user_id = '$_SESSION[user_id]' LIMIT 1 ";
        $exist = $GLOBALS['db']->getOne($sql);    
        $smarty->assign('exist', $exist);        
    }

    /* 剩余数量 */
    $sql = " SELECT COUNT(bonus_id) FROM ".$GLOBALS['ecs']->table('user_bonus')." WHERE bonus_type_id = '$type_id' AND user_id = '0' LIMIT 1 ";
    $left = $GLOBALS['db']->getOne($sql);
    $smarty->assign('left', $left); 

    /* 显示模板 */
    $smarty->display('bonus.dwt');
}

/* ------------------------------------------------------ */
//-- 领取红包
/* ------------------------------------------------------ */
if ($_REQUEST['act'] == 'get_bonus') {
    include_once('includes/cls_json.php');
    $json  = new JSON;
    $result = array('error' => 0, 'message' => '', 'content' => '');

    $type_id = empty($_REQUEST['type_id'])? 0:intval($_REQUEST['type_id']);
    if(empty($_SESSION['user_id'])){
        $result['error'] = 1;
        $result['message'] = $_LANG['please_login'];
    }else{
        $sql = " SELECT bonus_id FROM ".$GLOBALS['ecs']->table('user_bonus')." WHERE bonus_type_id = '$type_id' AND user_id = '$_SESSION[user_id]' LIMIT 1 ";
        $exist = $GLOBALS['db']->getOne($sql);
        if(!empty($exist)){
            $result['error'] = 1;
            $result['message'] = $_LANG['already_got'];
        }else{
            $sql = " SELECT bonus_id FROM ".$GLOBALS['ecs']->table('user_bonus')." WHERE bonus_type_id = '$type_id' AND user_id = 0 LIMIT 1 ";
            $bonus_id = $GLOBALS['db']->getOne($sql);
            if(empty($bonus_id)){
                $result['error'] = 1;
                $result['message'] = $_LANG['no_bonus'];            
            }else{
                $data = array(
                    'user_id'=>$_SESSION['user_id'],
                    'bind_time'=>gmtime()
                );
                $db->autoExecute($ecs->table('user_bonus'), $data, 'UPDATE', "bonus_id   = '$bonus_id'");
                $result['message'] = $_LANG['get_success'];  
            }           
        }
    }
    die($json->encode($result));
}

?>
