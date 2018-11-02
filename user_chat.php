<?php

/**
 * DSC 会员中心
 * ============================================================================
 * * 版权所有 2005-2016 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: Zhuo $
 * $Id: common.php 2016-01-04 Zhuo $
*/
define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require(dirname(__FILE__) . '/includes/lib_code.php');

require(ROOT_PATH . '/includes/lib_area.php');  //ecmoban模板堂 --zhuo
/* 载入语言文件 */ //by guan 晒单评价
require_once(ROOT_PATH . '/' . ADMIN_PATH . '/includes/lib_goods.php');
require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/user.php');
include_once(ROOT_PATH . '/includes/cls_image.php');
$image = new cls_image($_CFG['bgcolor']);

/* 过滤 XSS 攻击和SQL注入 */
get_request_filter();

$user_id = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$action  = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : 'default';

$affiliate = unserialize($GLOBALS['_CFG']['affiliate']);
$smarty->assign('affiliate', $affiliate);
$back_act='';

$categories_pro = get_category_tree_leve_one();
$smarty->assign('categories_pro',  $categories_pro); // 分类树加强版

// 不需要登录的操作或自己验证是否登录（如ajax处理）的act
$not_login_arr =
array('login','act_login','register','act_register','act_edit_password','get_password','send_pwd_email','get_pwd_mobile','password', 'signin', 'add_tag', 'collect', 'return_to_cart', 'logout', 'email_list', 'validate_email', 'send_hash_mail', 'order_query', 'is_registered',
    'check_email','clear_history','qpassword_name', 'get_passwd_question', 'check_answer', 'oath' , 'oath_login', 'other_login', 'is_mobile_phone','check_phone','captchas','phone_captcha','code_notice','captchas_pass', 'oath_register', 'is_user', 'is_login_captcha', 'is_register_captcha',
    'is_mobile_code', 'oath_remove', 'oath_weixin_login', 'user_email_verify', 'user_email_send', 'email_send_succeed', 'pay_pwd', 'checkd_email_send_code');

/* 显示页面的action列表 */ //ecmoban模板堂 --zhuo 'order_delete_restore', 'order_recycle', 'order_to_query'
$ui_arr = array('chat_list');

/* 未登录处理 */
if (empty($_SESSION['user_id']))
{
    if (!in_array($action, $not_login_arr))
    {
        if (in_array($action, $ui_arr))
        {
            /* 如果需要登录,并是显示页面的操作，记录当前操作，用于登录后跳转到相应操作
            if ($action == 'login')
            {
                if (isset($_REQUEST['back_act']))
                {
                    $back_act = trim($_REQUEST['back_act']);
                }
            }
            else
            {}*/
            if (!empty($_SERVER['QUERY_STRING']))
            {
                $back_act = 'user_chat.php?' . strip_tags($_SERVER['QUERY_STRING']);
            }
            $action = 'login';
        }
        else
        {

            if($action != 'act_add_bonus'){
                //未登录提交数据。非正常途径提交数据！
                $str = "<script>window.opener.location.href='user.php';window.close();</script>";
                die($str);
            }
        }
    }
}


$sql ="SELECT user_id FROM " . $ecs->table('merchants_shop_information') .  " WHERE user_id = '$user_id' AND merchants_audit != 2";
$is_apply = $db->getOne($sql);
$smarty->assign('is_apply',       $is_apply);

/* 如果是显示页面，对页面进行相应赋值 */

if (in_array($action, $ui_arr))
{
    assign_template();
    $position = assign_ur_here(0, $_LANG['user_center']);
    $smarty->assign('page_title', $position['title']); // 页面标题
    $categories_pro = get_category_tree_leve_one();
    $smarty->assign('categories_pro',  $categories_pro); // 分类树加强版
    $smarty->assign('ur_here',    $position['ur_here']);
    $sql = "SELECT value FROM " . $ecs->table('shop_config') . " WHERE id = 419";
    $row = $db->getRow($sql);
    $car_off = $row['value'];
    $smarty->assign('car_off',       $car_off);
    /* 是否显示积分兑换 */
    if (!empty($_CFG['points_rule']) && unserialize($_CFG['points_rule']))
    {
        $smarty->assign('show_transform_points',     1);
    }
    $smarty->assign('helps',      get_shop_help());        // 网店帮助
    $smarty->assign('data_dir',   DATA_DIR);   // 数据目录
    $smarty->assign('action',     $action);
    $smarty->assign('lang',       $_LANG);
    
    $info = get_user_default($user_id);
   
    if ($user_id) {
        //验证邮箱
        if (!$info['is_validated'] && $_CFG['user_login_register'] == 1) {
            $Location = $ecs->url() . 'user.php?act=user_email_verify';
            header('location:' . $Location);
            exit;
        }
    }
    
    //ecmoban模板堂 --zhuo start
    $sql = "SELECT user_id FROM " . $ecs->table('admin_user') . " WHERE ru_id = '" . $_SESSION['user_id'] . "'";
    $is_merchants = 0;
    if ($db->getOne($sql, true)) {
        $is_merchants = 1;
    }
    
    $smarty->assign('is_merchants', $is_merchants);
    $smarty->assign('shop_reg_closed', $GLOBALS['_CFG']['shop_reg_closed']);
    //ecmoban模板堂 --zhuo end
}

//用户中心欢迎页
if ($action == 'default')
{

}
/** 显示聊天列表 */
elseif ($action == 'chat_list')
{
    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

    $chat_list = chat_message_list();

    $smarty->assign('chat_list',   json_encode($chat_list));

    $smarty->display('user_chat_list.dwt');
}

/** 获取聊天数据 */
elseif ($action == 'chat_list_data')
{
    include_once(ROOT_PATH .'includes/cls_json.php');
    $json = new JSON();

    $input = file_get_contents('php://input');
    $input = json_decode($input, 1);
    $serviceId = isset($input['service_id']) ? intval($input['service_id']) : 1;
    $list = getChatListById($serviceId);
    die($json->encode($list));
}
/** 聊天页面 */
elseif ($action == 'service') {
    $page = isset($_REQUEST['goods']) ? intval($_REQUEST['page']) : 1;
    $config = require_once ROOT_PATH .'/kefu/config/config.php';

    $config_prot = $config['prot'];
    if(empty($config_prot)){
        show_message('socket端口号未配置', $_LANG['back_up_page'], 'user.php', 'error');
    }
    $listen_route = $config['listen_route'];
    if(empty($listen_route)){
        $listen_route = $_SERVER['SERVER_ADDR'];
    }
    $smarty->assign('listen_route', $listen_route); //;
    $smarty->assign('prot', $config_prot); //;

    //店铺
    $goodsId   = !empty($_GET['goods_id'])     ? intval($_GET['goods_id'])     : 0;
    $ruId   = (!empty($_GET['ru_id']) || $_GET['ru_id'] == 0)    ? intval($_GET['ru_id'])     : 0;

    $smarty->assign('goods_id', $goodsId); //;
    $smarty->assign('ru_id', $ruId); //;
    $serviceId = getServiceByRuId($ruId);
    $shopInfo = shopInfo($ruId);
    $smarty->assign('shop_name', $shopInfo['shop_name']); //;

    $smarty->assign('service_id', $serviceId); //;

    //客户信息
    $user_info = get_user_default($_SESSION['user_id']);
//    $user_pic = empty($user_info['user_picture']) ? 'no_picture.jpg' : $user_info['user_picture'];
//    $user_info['user_picture'] = '/data/images_user/'.basename($user_pic);
    $user_info['user_picture'] = formatUserPic($user_info['user_picture']);
    $smarty->assign('user_info', $user_info); //;

    $smarty->display('user_chat.dwt');
}
/** 获取聊天列表 */
function chat_message_list(){
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT `id`, `customer_id`, `services_id`, `origin`, `goods_id`, `store_id`, `status` FROM " . $GLOBALS['ecs']->table('im_dialog')
        . " WHERE customer_id='$user_id'";
    $dialog = $GLOBALS['db']->getAll($sql);

    $temp = [];
    foreach ($dialog as $k => $v) {
        if(in_array($v['services_id'], $temp)){
            unset($dialog[$k]);
            continue;
        }
        $temp[] = $v['services_id'];
    }
    $dialog = array_values($dialog);
    $messageList = [];
    foreach($dialog as $k => $v){
        $sql = "SELECT count(id) FROM " . $GLOBALS['ecs']->table('im_message')
            . " WHERE dialog_id='{$v['id']}' AND status = 1";
        $messageList[$k]['count'] = $GLOBALS['db']->getOne($sql);

        //消息属性
        $sql = "SELECT `message`, `add_time`, `user_type`, `status` FROM " . $GLOBALS['ecs']->table('im_message')
            . " WHERE dialog_id='{$v['id']}' ORDER BY add_time DESC";

        $res = $GLOBALS['db']->getRow($sql);

        $messageList[$k]['message'] = htmlspecialchars_decode($res['message']);
        $messageList[$k]['add_time'] = date('Y-m-d H:i:s', $res['add_time']);
        $messageList[$k]['origin'] = ($v['origin'] == 1) ? 'PC' : 'Phone';
        $messageList[$k]['user_type'] = $res['user_type'];
        $messageList[$k]['status'] = ($v['status'] == 1) ? '未结束' : '结束';

        //用户属性
        $service_info = serviceInfo($v['services_id']);
        $messageList[$k]['services_id'] = $v['services_id'];
        $messageList[$k]['user_name'] = $service_info['user_name'];
        $messageList[$k]['user_picture'] = get_image_path($service_info['user_picture']);

        if(empty($service_info['user_name'])){
            unset($messageList[$k]);
        }
    }
    return $messageList;
}
/** 客服信息 */
function serviceInfo ($id){
    $sql = 'SELECT user_name FROM' . $GLOBALS['ecs']->table('im_service') . ' WHERE id='.$id;
    $service['user_name'] = $GLOBALS['db']->getOne($sql);  //
    $service['user_picture'] = getShopLogoByService($id);;  //

    return $service;
}

/** 客服头像 */
function getShopLogoByService ($id){
    $sql = 'SELECT shop_logo FROM' . $GLOBALS['ecs']->table('im_service') . ' AS s';
    $sql .= ' LEFT JOIN ' . $GLOBALS['ecs']->table('admin_user') . ' AS a on a.user_id = s.user_id';
    $sql .= ' LEFT JOIN ' . $GLOBALS['ecs']->table('seller_shopinfo') . ' AS si on a.ru_id = si.ru_id';
    $sql .= ' WHERE s.id=' .$id;

    return $GLOBALS['db']->getOne($sql);
}

/** 店铺信息 */
function shopInfo ($id){
    $id = empty($id) ? 0 : $id;
    $sql = 'SELECT * FROM' . $GLOBALS['ecs']->table('seller_shopinfo') . ' WHERE ru_id='.$id;
    return $GLOBALS['db']->getRow($sql);
}

/** 根据ID获取最近聊天记录 */
function getChatListById ($id){
    $user_id = $_SESSION['user_id'];

    $sql = 'SELECT message, from_unixtime(add_time) as add_time, from_user_id, user_type  FROM' . $GLOBALS['ecs']->table('im_message') . ' WHERE (from_user_id=' . $id . ' AND to_user_id = ' . $user_id . ') OR (from_user_id = ' . $user_id . ' AND to_user_id = ' . $id . ') ORDER BY add_time DESC limit 3';
    $list = $GLOBALS['db']->getAll($sql);

    foreach($list as $k => $v){
        $list[$k]['message'] = htmlspecialchars_decode($v['message']);
        if($v['user_type'] == 1){
            $sql = "SELECT nick_name FROM " . $GLOBALS['ecs']->table('im_service') . " WHERE id = " . $v['from_user_id'] . " LIMIT 1";
            $list[$k]['user_name'] = $GLOBALS['db']->getOne($sql);
            $list[$k]['warp_chat'] = 'warp-chat-left';
        }elseif($v['user_type'] == 2){
            $sql = "SELECT user_name FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = " . $v['from_user_id'] . " LIMIT 1";
            $list[$k]['user_name'] = $GLOBALS['db']->getOne($sql);
            $list[$k]['warp_chat'] = 'warp-chat-right';
        }
        $list[$k]['message'] = htmlspecialchars_decode($v['message']);
    }

    return $list;
}

/** 根据店铺查找客服 */
function getServiceByRuId($ruId) {
    $user_id = $_SESSION['user_id'];
    $ruId = empty($ruId) ? 0 : $ruId;

    $sql = 'SELECT services_id  FROM' . $GLOBALS['ecs']->table('im_dialog') . ' WHERE customer_id = ' . $user_id . ' AND status = 1 AND store_id = ' . $ruId . ' ORDER BY start_time DESC limit 1';
    return $GLOBALS['db']->getOne($sql);
}


/** 格式化用户头像 */
function formatUserPic($pic){
    $rootPath = dirname($_SERVER['PHP_SELF']);
    $pic = empty($pic) ? 'no_picture.jpg' : $pic;
    if(basename($pic) == 'no_picture.jpg'){
        $user_pic = $rootPath.'/kefu/public/assets/images/'.basename($pic);
    }else{
        $user_pic = $rootPath.'/data/images_user/'.basename($pic);
    }
    //当前域名协议

    return $user_pic;

}