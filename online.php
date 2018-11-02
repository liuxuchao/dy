<?php

/**
 * ECSHOP 在线客服
 * ============================================================================
 * * 版权所有 2005-2016 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: index.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
require(ROOT_PATH . '/includes/lib_area.php');  //ecmoban模板堂 --zhuo

assign_template();

$user_id = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

/**
 * 在线客服
 */
if ($_REQUEST['act'] == 'service'){
   
    $IM_menu = $ecs->url() . '/online.php?act=service_menu';

    $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
    $seller_id = isset($_REQUEST['ru_id']) ? intval($_REQUEST['ru_id']) : -1;
    
    if ($GLOBALS['_CFG']['customer_service'] == 0) {
        $ru_id = 0;
    } else {
        
        if($seller_id > -1){
            $ru_id = $seller_id;
        }else{
            
            $goods = get_goods_info($goods_id, 0, 0, array('g.user_id'));
            $ru_id = $goods['user_id'];
        }
    }

    // 优先使用自有在线客服
    if (is_dir(ROOT_PATH . 'kefu')) {
        if (empty($user_id)) {
            exit("<script>window.location.href='user.php';</script>");
        }
        require(__DIR__ . '/includes/lib_code.php');
        $user_token = [
            'user_name' => $_SESSION['user_name'],
            'hash' => md5($_SESSION['user_name'] . date('YmdH') . $db->dbhash)
        ];
        $token = base64_encode(serialize($user_token));
        $Loaction = $ecs->url()."mobile/index.php?m=chat&token=". $token ."&ru_id=" .$ru_id. "&goods_id=" . $goods_id;
        ecs_header("Location: $Loaction\n");
        exit;
    }

    $sql="SELECT kf_appkey,kf_secretkey,kf_touid, kf_logo, kf_welcomeMsg FROM ".$ecs->table('seller_shopinfo')." WHERE ru_id = '$ru_id' LIMIT 1";
    $basic_info = $db->getRow($sql);

    IM($basic_info['kf_appkey'],$basic_info['kf_secretkey']);

    if(empty($basic_info['kf_logo']) || $basic_info['kf_logo'] == 'http://'){
        $basic_info['kf_logo']='http://dsc-kf.oss-cn-shanghai.aliyuncs.com/dsc_kf/p16812444.jpg';
    }

    //判断用户是否登入,登入了就登入登入用户,未登入就登入匿名用户;
    if ($user_id) {
        $user_info = user_info($user_id);
        $user_info['user_id'] = 'dsc' . $user_id;
        if (empty($user_info['user_picture'])) {
            $user_logo = 'http://dsc-kf.oss-cn-shanghai.aliyuncs.com/dsc_kf/dsc_kf_user_logo.jpg';
        } else {
            //OSS文件存储ecmoban模板堂 --zhuo start
            if ((strpos($user_info['user_picture'], 'http://') === false && strpos($user_info['user_picture'], 'https://') === false)) {
                if ($GLOBALS['_CFG']['open_oss'] == 1 && $user_info['user_picture']) {
                    $bucket_info = get_bucket_info();
                    $user_logo = $bucket_info['endpoint'] . $user_info['user_picture'];
                } else {
                    $user_logo = $user_info['user_picture'];
                }
            } else {
                $user_logo = $ecs->get_domain() . '/' .$user_info['user_picture'];
            }
            //OSS文件存储ecmoban模板堂 --zhuo end
        }
    } else {

        $user_info['user_id'] = $user_id;
        $user_logo = 'http://dsc-kf.oss-cn-shanghai.aliyuncs.com/dsc_kf/dsc_kf_user_logo.jpg';
    }

    $smarty->assign('user_id', $user_info['user_id']);
    $smarty->assign('user_logo', $user_logo);
    $smarty->assign('kf_appkey', $basic_info['kf_appkey']);
    $smarty->assign('kf_touid', $basic_info['kf_touid']);
    $smarty->assign('kf_logo', $basic_info['kf_logo']);
    $smarty->assign('kf_welcomeMsg', $basic_info['kf_welcomeMsg']);
    $smarty->assign('IM_menu', $IM_menu);
    $smarty->assign('goods_id', $goods_id);

    $smarty -> display('chats.dwt');
}

/**
 * 左侧菜单
 */
if ($_REQUEST['act'] == 'service_menu'){

    $smarty -> display('chats_menu.dwt');

}

/*
 * 右侧菜单
 */
if($_REQUEST['act'] == 'history'){

    $request = json_decode($_POST['q'],true);
    $itemId=$request['itemsId'][0];//商品ID;
    $url=$ecs->url();
    echo $current_url=$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];die;

    $goods=goods_info($itemId);

    echo <<<HTML
    {
    "code": "200",
    "desc": "powered by 大商创",
    "itemDetail": [
            {
                "userid": "{$request['userid']}",
                "itemid": "{$itemId}",
                "itemname": "{$goods['goods_name']}",
                "itempic": "{$url}{$goods['goods_thumb']}",
                "itemprice": "{$goods['shop_price']}",
                "itemurl": "{$current_url}",
                "extra": {}
            }
        ]
    }
HTML;

}


?>