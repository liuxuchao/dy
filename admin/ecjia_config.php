<?php

/**
 * ECSHOP 应用配置
 * ============================================================================
 * * 版权所有 2005-2016 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: article.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
include_once(ROOT_PATH . 'includes/cls_image.php');
$image = new cls_image($_CFG['bgcolor']);

/*------------------------------------------------------ */
//-- 配置列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    /* 取得过滤条件 */
    $filter = array();
    $smarty->assign('ur_here', $_LANG['12_ecjia_app_config']);
    
    
    $shop_app_icon = ecjia_config('shop_app_icon') ? ROOT_PATH . ecjia_config('shop_app_icon') : '';
    
    // 基本信息
    $smarty->assign('shop_app_icon', ecjia_config('shop_app_icon'));
    $smarty->assign('shop_app_description', ecjia_config('shop_app_description')); // 移动应用简介
    $smarty->assign('bonus_readme_url', ecjia_config('bonus_readme_url')); // 红包使用说明
    $smarty->assign('mobile_feedback_autoreply', ecjia_config('mobile_feedback_autoreply')); // 咨询默认回复设置
    $smarty->assign('mobile_shopkeeper_urlscheme', ecjia_config('mobile_shopkeeper_urlscheme')); // 掌柜UrlScheme设置
    $smarty->assign('shop_pc_url', ecjia_config('shop_pc_url')); // PC商城地址
    $smarty->assign('mobile_share_link', ecjia_config('mobile_share_link')); // 分享链接
    // 新人有礼红包
    $time = gmtime();
    $sql = "SELECT type_id, type_name FROM " . $GLOBALS['ecs']->table('bonus_type') . " WHERE use_start_date < '$time' AND '$time' < use_end_date ";
    $bonus_list = $db->getAll($sql);
    
    $bonus_select = '';
    foreach ($bonus_list as $key => $value)
    {
        $bonus_select .= '<li><a href="javascript:;" data-value="'.$value['type_id'].'" class="ftx-01">'.$value['type_name'].'</a></li>';
    }
    $smarty->assign('bonus_select', $bonus_select);
    
    $bonus_id = ecjia_config('mobile_signup_reward');
    $bonus_name = $db->getOne("SELECT type_name FROM " .$ecs->table('bonus_type'). " WHERE type_id = '$bonus_id' ");
    $smarty->assign('mobile_signup_reward', $bonus_id);// 新人有礼红包
    
    $smarty->assign('mobile_signup_reward_notice', ecjia_config('mobile_signup_reward_notice')); // 新人有礼说明
    
    // APP下载地址
    $smarty->assign('mobile_iphone_qr_code', ecjia_config('mobile_iphone_qr_code')); // iPhone下载二维码
    $smarty->assign('shop_iphone_download', ecjia_config('shop_iphone_download')); // iPhone下载地址
    $smarty->assign('mobile_android_qr_code', ecjia_config('mobile_android_qr_code')); // Android下载二维码
    $smarty->assign('shop_android_download', ecjia_config('shop_android_download')); // Android下载地址
    $smarty->assign('mobile_ipad_qr_code', ecjia_config('mobile_ipad_qr_code')); // iPad下载二维码
    $smarty->assign('shop_ipad_download', ecjia_config('shop_ipad_download')); // iPad下载地址
    
    // 移动广告位设置
    // 移动启动页广告图
    $sql = "SELECT ad_id, ad_name FROM " . $ecs->table('ad') . " WHERE start_time < '$time' AND '$time' < end_time";
    $ad_list = $db->getAll($sql);
    $smarty->assign('ad_list', $ad_list);
    
    $mobile_launch_select = '';
    foreach ($ad_list as $key => $value)
    {
        $mobile_launch_select .= '<li><a href="javascript:;" data-value="'.$value['ad_id'].'" class="ftx-01">'.$value['ad_name'].'</a></li>';
    }
    $smarty->assign('mobile_launch_select', $mobile_launch_select);
    
    $launch_ad_id = ecjia_config('mobile_launch_adsense');
    $launch_ad_name = $db->getOne("SELECT ad_name FROM " .$ecs->table('ad'). " WHERE ad_id = '$launch_ad_id' ");
    $smarty->assign('launch_ad_name', $launch_ad_name);
    $smarty->assign('launch_ad_id', $launch_ad_id); // 移动启动页广告图
    
    // 移动首页广告组
    $ads_id = ecjia_config('mobile_home_adsense_group');
    $where = '';
    if ($ads_id)
    {
        $where .= " AND ad_id IN ($ads_id)";
    }
    else
    {
        $where .= " AND ad_id = 0 ";
    }
    $sql = "SELECT ad_id, ad_name FROM " .$ecs->table('ad'). " WHERE 1 $where ";
    $mobile_home_adsense_group = $db->getAll($sql);
    $smarty->assign('mobile_home_adsense_group', $mobile_home_adsense_group); // 移动首页广告组
    
    // 已选择的热门城市
    $regions_id = ecjia_config('mobile_recommend_city');
    $where1 = '';
    if ($regions_id)
    {
        $where1 .= " AND region_id IN ($regions_id)";
    }
    else
    {
        $where1 .= " AND region_id = 0 ";
    }
    $sql = "SELECT region_id, region_name FROM " .$ecs->table('region'). " WHERE 1 $where1 ";
    $regions = $db->getAll($sql);
    $smarty->assign('regions', $regions); // 已选择的热门城市
    
    // 首页主题类设置
    $mobile_topic_select = '';
    foreach ($ad_list as $key => $value)
    {
        $mobile_topic_select .= '<li><a href="javascript:;" data-value="'.$value['ad_id'].'" class="ftx-01">'.$value['ad_name'].'</a></li>';
    }
    $smarty->assign('mobile_topic_select', $mobile_topic_select);
    
    $topic_ad_id = ecjia_config('mobile_topic_adsense');
    $topic_ad_name = $db->getOne("SELECT ad_name FROM " .$ecs->table('ad'). " WHERE ad_id = '$topic_ad_id' ");
    $smarty->assign('topic_ad_name', $topic_ad_name);
    $smarty->assign('topic_ad_id', $topic_ad_id); // 首页主题类设置

    
    // 登录页色值设置
    $smarty->assign('mobile_phone_login_fgcolor', ecjia_config('mobile_phone_login_fgcolor')); // 手机端登录页前景色
    $smarty->assign('mobile_phone_login_bgcolor', ecjia_config('mobile_phone_login_bgcolor')); // 手机端登录页背景色
    $smarty->assign('mobile_phone_login_bgimage', ecjia_config('mobile_phone_login_bgimage')); // 手机端登录页背景图片
    $smarty->assign('mobile_pad_login_fgcolor', ecjia_config('mobile_pad_login_fgcolor')); // Pad登录页前景色
    $smarty->assign('mobile_pad_login_bgcolor', ecjia_config('mobile_pad_login_bgcolor')); // Pad登录页背景色
    $smarty->assign('mobile_pad_login_bgimage', ecjia_config('mobile_pad_login_bgimage')); // Pad登录页背景图片
    
    // 热门城市设置
    $smarty->assign('mobile_recommend_city', ecjia_config('mobile_recommend_city')); // 已选择的热门城市 6
    
    $smarty->assign('form_action', 'update');
    assign_query_info();
    $smarty->display('ecjia_config.dwt');
}

if ($_REQUEST['act'] =='update')
{
    /* 权限判断 */
    admin_priv('article_manage');
    /* 移动应用 Logo 图片 */
    if ((isset($_FILES['shop_app_icon']['error']) && $_FILES['shop_app_icon']['error'] == 0) || (!isset($_FILES['shop_app_icon']['error']) && isset($_FILES['shop_app_icon']['tmp_name']) && $_FILES['shop_app_icon']['tmp_name'] != 'none'))
    {
        $img_up_info = basename($image->upload_image($_FILES['shop_app_icon'], 'assets/ecmoban_sc'));
        
        $code = ecjia_config('shop_app_icon');
        
        if( $code && $code != DATA_DIR . '/assets/ecmoban_sc/'.$img_up_info)
        {
            @unlink('../' . $code);
        }
        
        get_oss_add_file(array(DATA_DIR . '/assets/ecmoban_sc/' . $img_up_info));
        
        $shop_app_icon_img_src = DATA_DIR . '/assets/ecmoban_sc/' . $img_up_info;
    }
    
    /* iPhone下载二维码 图片 */
    if ((isset($_FILES['mobile_iphone_qr_code']['error']) && $_FILES['mobile_iphone_qr_code']['error'] == 0) || (!isset($_FILES['mobile_iphone_qr_code']['error']) && isset($_FILES['mobile_iphone_qr_code']['tmp_name']) && $_FILES['mobile_iphone_qr_code']['tmp_name'] != 'none'))
    {
        $img_up_info = basename($image->upload_image($_FILES['mobile_iphone_qr_code'], 'assets'));
        
        $code = ecjia_config('mobile_iphone_qr_code');
        
        if( $code && $code != DATA_DIR . '/assets/'.$img_up_info)
        {
            @unlink('../' . $code);
        }
        
        get_oss_add_file(array(DATA_DIR . '/assets/' . $img_up_info));
        
        $mobile_iphone_qr_code_img_src = DATA_DIR . '/assets/' . $img_up_info;
    }
    /* Android下载二维码 图片 */
    if ((isset($_FILES['mobile_android_qr_code']['error']) && $_FILES['mobile_android_qr_code']['error'] == 0) || (!isset($_FILES['mobile_android_qr_code']['error']) && isset($_FILES['mobile_android_qr_code']['tmp_name']) && $_FILES['mobile_android_qr_code']['tmp_name'] != 'none'))
    {
        $img_up_info = basename($image->upload_image($_FILES['mobile_android_qr_code'], 'assets'));
        
        $code = ecjia_config('mobile_android_qr_code');
        
        if( $code && $code != DATA_DIR . '/assets/'.$img_up_info)
        {
            @unlink('../' . $code);
        }
        
        get_oss_add_file(array(DATA_DIR . '/assets/' . $img_up_info));
        
        $mobile_android_qr_code_img_src = DATA_DIR . '/assets/' . $img_up_info;
    }
    /* iPad下载二维码 图片 */
    if ((isset($_FILES['mobile_ipad_qr_code']['error']) && $_FILES['mobile_ipad_qr_code']['error'] == 0) || (!isset($_FILES['mobile_ipad_qr_code']['error']) && isset($_FILES['mobile_ipad_qr_code']['tmp_name']) && $_FILES['mobile_ipad_qr_code']['tmp_name'] != 'none'))
    {
        $img_up_info = basename($image->upload_image($_FILES['mobile_ipad_qr_code'], 'assets'));
        
        $code = ecjia_config('mobile_ipad_qr_code');
        
        if( $code && $code != DATA_DIR . '/assets/'.$img_up_info)
        {
            @unlink('../' . $code);
        }
        
        get_oss_add_file(array(DATA_DIR . '/assets/' . $img_up_info));
        
        $mobile_ipad_qr_code_img_src = DATA_DIR . '/assets/' . $img_up_info;
    }
    
    /* 手机端登录页背景图片 图片 */
    if ((isset($_FILES['mobile_phone_login_bgimage']['error']) && $_FILES['mobile_phone_login_bgimage']['error'] == 0) || (!isset($_FILES['mobile_phone_login_bgimage']['error']) && isset($_FILES['mobile_phone_login_bgimage']['tmp_name']) && $_FILES['mobile_phone_login_bgimage']['tmp_name'] != 'none'))
    {
        $img_up_info = basename($image->upload_image($_FILES['mobile_phone_login_bgimage'], 'assets'));
        
        $code = ecjia_config('mobile_phone_login_bgimage');
        
        if( $code && $code != DATA_DIR . '/assets/'.$img_up_info)
        {
            @unlink('../' . $code);
        }
        
        get_oss_add_file(array(DATA_DIR . '/assets/' . $img_up_info));
        
        $mobile_phone_login_bgimage_img_src = DATA_DIR . '/assets/' . $img_up_info;
    }
    
    /* Pad登录页背景图片 图片 */
    if ((isset($_FILES['mobile_pad_login_bgimage']['error']) && $_FILES['mobile_pad_login_bgimage']['error'] == 0) || (!isset($_FILES['mobile_pad_login_bgimage']['error']) && isset($_FILES['mobile_pad_login_bgimage']['tmp_name']) && $_FILES['mobile_pad_login_bgimage']['tmp_name'] != 'none'))
    {
        $img_up_info = basename($image->upload_image($_FILES['mobile_pad_login_bgimage'], 'assets'));
        
        $code = ecjia_config('mobile_pad_login_bgimage');
        
        if( $code && $code != DATA_DIR . '/assets/'.$img_up_info)
        {
            @unlink('../' . $code);
        }
        
        get_oss_add_file(array(DATA_DIR . '/assets/' . $img_up_info));
        
        $mobile_pad_login_bgimage_img_src = DATA_DIR . '/assets/' . $img_up_info;
    }
    
    $shop_app_icon = !empty($shop_app_icon_img_src) ? $shop_app_icon_img_src : trim($_POST['shop_app_icon_textfile']);
    $mobile_iphone_qr_code = !empty($mobile_iphone_qr_code_img_src) ? $mobile_iphone_qr_code_img_src : trim($_POST['mobile_iphone_qr_code_textfile']);
    $mobile_android_qr_code = !empty($mobile_android_qr_code_img_src) ? $mobile_android_qr_code_img_src : trim($_POST['mobile_android_qr_code_textfile']);
    $mobile_ipad_qr_code = !empty($mobile_ipad_qr_code_img_src) ? $mobile_ipad_qr_code_img_src : trim($_POST['mobile_ipad_qr_code_textfile']);
    $mobile_phone_login_bgimage = !empty($mobile_phone_login_bgimage_img_src) ? $mobile_phone_login_bgimage_img_src : trim($_POST['mobile_phone_login_bgimage_textfile']);
    $mobile_pad_login_bgimage = !empty($mobile_pad_login_bgimage_img_src) ? $mobile_pad_login_bgimage_img_src : trim($_POST['mobile_pad_login_bgimage_textfile']);
    
    
    $shop_app_description = !empty($_POST['shop_app_description']) ? trim($_POST['shop_app_description']) : '';
    $bonus_readme_url = !empty($_POST['bonus_readme_url']) ? trim($_POST['bonus_readme_url']) : '';
    $mobile_feedback_autoreply = !empty($_POST['mobile_feedback_autoreply']) ? trim($_POST['mobile_feedback_autoreply']) : '';
    $mobile_shopkeeper_urlscheme = !empty($_POST['mobile_shopkeeper_urlscheme']) ? trim($_POST['mobile_shopkeeper_urlscheme']) : '';
    $shop_pc_url = !empty($_POST['shop_pc_url']) ? trim($_POST['shop_pc_url']) : '';
    $mobile_share_link = !empty($_POST['mobile_share_link']) ? trim($_POST['mobile_share_link']) : '';
    $mobile_signup_reward = !empty($_POST['mobile_signup_reward']) ? trim($_POST['mobile_signup_reward']) : '';
    $mobile_signup_reward_notice = !empty($_POST['mobile_signup_reward_notice']) ? trim($_POST['mobile_signup_reward_notice']) : '';
    $shop_iphone_download = !empty($_POST['shop_iphone_download']) ? trim($_POST['shop_iphone_download']) : '';
    $shop_android_download = !empty($_POST['shop_android_download']) ? trim($_POST['shop_android_download']) : '';
    $shop_ipad_download = !empty($_POST['shop_ipad_download']) ? trim($_POST['shop_ipad_download']) : '';
    
    $mobile_launch_adsense = !empty($_POST['mobile_launch_adsense']) ? trim($_POST['mobile_launch_adsense']) : '';
    $mobile_home_adsense_group = !empty($_POST['mobile_home_adsense_group']) ? trim(implode(',', $_POST['mobile_home_adsense_group'])) : '';
    $mobile_topic_adsense = !empty($_POST['mobile_topic_adsense']) ? trim($_POST['mobile_topic_adsense']) : '';
    
    $mobile_phone_login_fgcolor = !empty($_POST['mobile_phone_login_fgcolor']) ? trim($_POST['mobile_phone_login_fgcolor']) : '';
    $mobile_phone_login_bgcolor = !empty($_POST['mobile_phone_login_bgcolor']) ? trim($_POST['mobile_phone_login_bgcolor']) : '';
    
    $mobile_pad_login_fgcolor = !empty($_POST['mobile_pad_login_fgcolor']) ? trim($_POST['mobile_pad_login_fgcolor']) : '';
    $mobile_pad_login_bgcolor = !empty($_POST['mobile_pad_login_bgcolor']) ? trim($_POST['mobile_pad_login_bgcolor']) : '';
    
    $mobile_recommend_city = !empty($_POST['regions']) ? trim(implode(',', $_POST['regions'])) : '';
    
    
    // 基本信息设置
    update_config('shop_app_icon', $shop_app_icon); // 移动应用 Logo
    update_config('shop_app_description', $shop_app_description); // 移动应用简介
    update_config('bonus_readme_url', '/index.php?m=article&c=mobile&a=info&id='.$bonus_readme_url); // 红包使用说明
    update_config('mobile_feedback_autoreply', $mobile_feedback_autoreply); // 咨询默认回复设置
    update_config('mobile_shopkeeper_urlscheme', $mobile_shopkeeper_urlscheme); // 掌柜UrlScheme设置
    update_config('shop_pc_url', $shop_pc_url); // PC商城地址
    update_config('mobile_share_link', $mobile_share_link); // 分享链接
    update_config('mobile_signup_reward', $mobile_signup_reward); // 新人有礼红包
    update_config('mobile_signup_reward_notice', $mobile_signup_reward_notice); // 新人有礼说明
    
    // 是否开启微商城（不知道code值）
    // 微商城 Logo（不知道code值）
    // 微商城地址 （不知道code值）
    
    // APP下载地址
    update_config('mobile_iphone_qr_code', $mobile_iphone_qr_code); // iPhone下载二维码
    update_config('shop_iphone_download', $shop_iphone_download); // iPhone下载地址
    update_config('mobile_android_qr_code', $mobile_android_qr_code); // Android下载二维码 
    update_config('shop_android_download', $shop_android_download); // Android下载地址 
    update_config('mobile_ipad_qr_code', $mobile_ipad_qr_code);// iPad下载二维码
    update_config('shop_ipad_download', $shop_ipad_download);// iPad下载地址
    
    // 移动广告位设置
    update_config('mobile_launch_adsense', $mobile_launch_adsense);// 移动启动页广告图
    update_config('mobile_home_adsense_group', $mobile_home_adsense_group);// 移动首页广告组
    update_config('mobile_topic_adsense', $mobile_topic_adsense);// 首页主题类设置
    
    // 登录页色值设置
    update_config('mobile_phone_login_fgcolor', $mobile_phone_login_fgcolor);// 手机端登录页前景色
    update_config('mobile_phone_login_bgcolor', $mobile_phone_login_bgcolor);// 手机端登录页背景色
    update_config('mobile_phone_login_bgimage', $mobile_phone_login_bgimage);// 手机端登录页背景图片
    update_config('mobile_pad_login_fgcolor', $mobile_pad_login_fgcolor);// Pad登录页前景色
    update_config('mobile_pad_login_bgcolor', $mobile_pad_login_bgcolor);// Pad登录页背景色
    update_config('mobile_pad_login_bgimage', $mobile_pad_login_bgimage);// Pad登录页背景图片
    
    // 热门城市设置
    update_config('mobile_recommend_city', $mobile_recommend_city);// 已选择的热门城市
    
    
    clear_cache_files(); // 清除缓存文件

    /* 提示信息 */

    $link[0]['text'] = $_LANG['back'];
    $link[0]['href'] = 'ecjia_config.php?act=list';
    sys_msg($_LANG['attradd_succed'],0, $link);
}

// 红包使用说明 文章搜索
elseif ($_REQUEST['act'] == 'search_article')
{
    $result = array('error'=>0, 'msg'=>'', 'content'=>'');
    
    $title = !empty($_REQUEST['article_keywords']) ? trim($_REQUEST['article_keywords']) : '';
    
    $where = '';
    if ($title)
    {
        $where .= " AND title LIKE '%$title%' ";
    }
    $sql = "SELECT article_id, title FROM " . $GLOBALS['ecs']->table('article') . " WHERE 1 $where";
    $res = $GLOBALS['db']->getAll($sql);
    
    $article_str = '<div class="cite">' . $_LANG['please_select'] . '</div>
            <ul class="ps-container" style="display: none;">';
    
    foreach ($res as $key => $value)
    {
        $article_str .= '<li><a href="javascript:;" data-value="'.$value['article_id'].'" class="ftx-01">'.$value['title'].'</a></li>';
    }
    
    $article_str .= '</ul>
            <input name="bonus_readme_url" type="hidden" value="'.  ecjia_config('$bonus_readme_url').'" id="bonus_readme_url">';
    
    $result['content'] = $article_str;
    die(json_encode($result));
}

// ecjia config 
function ecjia_config($code)
{
    $sql = "SELECT value FROM " . $GLOBALS['ecs']->table('shop_config') . " WHERE code = '$code' ";
    return $GLOBALS['db']->getOne($sql);
}

// ecjia config 
function update_config($code, $value)
{
    $sql = "UPDATE " . $GLOBALS['ecs']->table('shop_config') . " SET value = '$value' WHERE code = '$code' ";
    $GLOBALS['db']->query($sql);
}

?>