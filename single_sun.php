<?php

/**
 * ECSHOP 晒单页
 * ============================================================================
 *
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author:  $
 * $Id: single_sun.php 17067 2013-11-1 03:59:37Z  $
 */
define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

require(ROOT_PATH . '/includes/lib_area.php');  //ecmoban模板堂 --zhuo
require_once('includes/cls_newPage.php'); //ecmoban模板堂 --zhuo

require_once(ROOT_PATH . ADMIN_PATH . '/includes/lib_goods.php');
require_once(ROOT_PATH . 'languages/' . $_CFG['lang'] . '/user.php');
include_once(ROOT_PATH . '/includes/cls_image.php');
$image = new cls_image($_CFG['bgcolor']);

if ((DEBUG_MODE & 2) != 2) {
    $smarty->caching = true;
}

/* 初始化分页信息 */
$page = isset($_REQUEST['page']) && intval($_REQUEST['page']) > 0 ? intval($_REQUEST['page']) : 1;
$act = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : 'single_list';
$affiliate = unserialize($GLOBALS['_CFG']['affiliate']);
$smarty->assign('affiliate', $affiliate);

// $cache_id = $action . '-' . $page;

assign_template();

$did = empty($_REQUEST['did']) ? 0 : intval($_REQUEST['did']);
$dis_type = isset($_REQUEST['dis_type']) ? $_REQUEST['dis_type'] : 0;  //dis_type = 4 晒单

$smarty->assign('helps', get_shop_help());        // 网店帮助
$smarty->assign('data_dir', DATA_DIR);   // 数据目录
$smarty->assign('action', $action);
$smarty->assign('lang', $_LANG);

$warehouse_other = [
    'province_id' => $province_id,
    'city_id' => $city_id
];
$warehouse_area_info = get_warehouse_area_info($warehouse_other);

$region_id = $warehouse_area_info['region_id'];
$area_id = $warehouse_area_info['area_id'];
$area_city = $warehouse_area_info['city_id'];

$history_goods = get_history_goods($goods_id, $region_id, $area_id, $area_city);
$smarty->assign('history_goods', $history_goods);                                   // 商品浏览历史

if ($dis_type == 4) {
    $sql = "select comment_id, id_value, user_id, order_id, content, user_name, add_time from " . $ecs->table('comment') . " where comment_id = '$did' AND status = 1";
    $comment = $db->getRow($sql);

    $goods_id = $comment['id_value'];
} else {
    $sql = "select goods_id from " . $ecs->table('discuss_circle') . " where dis_id = '$did' AND review_status = 3 ";
    $goods_id = $db->getOne($sql);
}


$goodsInfo = get_goods_info($goods_id, $region_id, $area_id, $area_city);
$goodsInfo['goods_price'] = price_format($goodsInfo['goods_price']);
$smarty->assign('goodsInfo', $goodsInfo);

//评分 start
$mc_all = ments_count_all($goods_id);       //总条数
$mc_one = ments_count_rank_num($goods_id, 1);  //一颗星
$mc_two = ments_count_rank_num($goods_id, 2);     //两颗星
$mc_three = ments_count_rank_num($goods_id, 3);    //三颗星
$mc_four = ments_count_rank_num($goods_id, 4);  //四颗星
$mc_five = ments_count_rank_num($goods_id, 5);  //五颗星
$comment_all = get_conments_stars($mc_all, $mc_one, $mc_two, $mc_three, $mc_four, $mc_five);

$smarty->assign('comment_all', $comment_all);

/**
 * 评论
 */
if ($_REQUEST['act'] == 'check_comm') {
    require_once (dirname(__FILE__) . '/includes/cls_json.php');
    $json = new JSON();

    $dis_id = empty($_REQUEST['dis_id']) ? 0 : intval($_REQUEST['dis_id']);
    $quote_id = empty($_REQUEST['quote_id']) ? 0 : intval($_REQUEST['quote_id']);
    $nick_user = empty($_REQUEST['nick_user']) ? 0 : intval($_REQUEST['nick_user']);
    $content = empty($_REQUEST['comment_content']) ? '' : htmlspecialchars($_REQUEST['comment_content']);
    $user_name = $_SESSION['user_name'];
    $user_id = $_SESSION['user_id'];
    $addtime = gmtime();
    $ip = real_ip();

    $res = array('error' => 0, 'err_msg' => '', 'dis_id' => $dis_id);

    if (empty($_SESSION['user_id'])) {
        $res['error'] = 2;
        die($json->encode($res));
    }

    if ($_SESSION['user_id'] == $nick_user) {
        $err_msg = $_LANG['comment_self'];

        $res['error'] = 1;
        $res['err_msg'] = $err_msg;
        die($json->encode($res));
    }

    $sql = "SELECT COUNT(*)  FROM " . $GLOBALS['ecs']->table('discuss_circle') . " WHERE dis_text = '$content'";
    if ($db->getOne($sql)) {
        $err_msg = $_LANG['repeat_comment'];

        $res['error'] = 1;
        $res['err_msg'] = $err_msg;
        die($json->encode($res));
    }

    $sql = "SELECT COUNT(*)  FROM " . $GLOBALS['ecs']->table('discuss_circle') . " WHERE parent_id = '$dis_id' AND user_id = '" . $_SESSION['user_id'] . "'";
    if ($db->getOne($sql) > 3) {
        $err_msg = $_LANG['More_comment'];

        $res['error'] = 1;
        $res['err_msg'] = $err_msg;
        die($json->encode($res));
    }

    $other = array(
        'goods_id' => 0,
        'parent_id' => $dis_id,
        'quote_id' => $quote_id,
        'user_id' => $user_id,
        'user_name' => $user_name,
        'dis_text' => $content,
        'add_time' => $addtime
    );

    $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('discuss_circle'), $other, 'INSERT');
    $dis_id = $db->insert_id();

    if ($dis_id) {
        $err_msg = $_LANG['comment_Success'];

        $res['error'] = 1;
        $res['err_msg'] = $err_msg;
        die($json->encode($res));
    } else {
        $err_msg = $_LANG['comment_fail'];

        $res['error'] = 1;
        $res['err_msg'] = $err_msg;
        die($json->encode($res));
    }
}


/* ------------------------------------------------------ */
//-- 讨论圈详情页
/* ------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'discuss_show') {
    if (defined('THEME_EXTENSION')) {
        $smarty->assign('user_info', get_user_default($_SESSION['user_id']));
        $goods = $goodsInfo;
        //$smarty->assign('goods', $goods);
        if (defined('THEME_EXTENSION')) {
            //是否收藏店铺
            $sql = "SELECT rec_id FROM " . $ecs->table('collect_store') . " WHERE user_id = '" . $_SESSION['user_id'] . "' AND ru_id = '$goods[user_id]' "; //by kong
            $rec_id = $db->getOne($sql);
            if ($rec_id > 0) {
                $goodsInfo['error'] = '1';
            } else {
                $goodsInfo['error'] = '2';
            }
        }

        if ($goods['user_id'] > 0) {
            $merchants_goods_comment = get_merchants_goods_comment($goods['user_id']); //商家所有商品评分类型汇总
            $smarty->assign('merch_cmt', $merchants_goods_comment);
        }

        if ($GLOBALS['_CFG']['customer_service'] == 0) {
            $goods_user_id = 0;
        } else {
            $goods_user_id = $goods['user_id'];
        }

        $basic_info = get_shop_info_content($goods_user_id);

        /*  @author-bylu 判断当前商家是否允许"在线客服" start  */
        $shop_information = get_shop_name($goods_user_id);
        //判断当前商家是平台,还是入驻商家 bylu
        if ($goods_user_id == 0) {
            //判断平台是否开启了IM在线客服
            if ($db->getOne("SELECT kf_im_switch FROM " . $ecs->table('seller_shopinfo') . "WHERE ru_id = 0")) {
                $shop_information['is_dsc'] = true;
            } else {
                $shop_information['is_dsc'] = false;
            }
        } else {
            $shop_information['is_dsc'] = false;
        }
        $smarty->assign('shop_information', $shop_information);
        $smarty->assign('kf_appkey', $basic_info['kf_appkey']); //应用appkey;
        $smarty->assign('im_user_id', 'dsc' . $_SESSION['user_id']); //登入用户ID;
        /*  @author-bylu  end  */
    }

    if (defined('THEME_EXTENSION')) {
        $categories_pro = get_category_tree_leve_one();
        $smarty->assign('categories_pro', $categories_pro); // 分类树加强版
    }

    $size = 5;
    $cache_id = $did . '-' . $_SESSION['user_rank'] . '-' . $_CFG['lang'];
    $cache_id = sprintf('%X', crc32($cache_id));
    if (!$smarty->is_cached('goods_discuss_show.dwt', $cache_id)) {
        if (empty($did)) {
            ecs_header("Location: ./\n");
            exit;
        }

        if ($dis_type == 4) {
            $img_list = get_img_list($comment['id_value'], $comment['comment_id']);

            $sql = "SELECT user_picture from " . $ecs->table('users') . " WHERE user_id = '" . $comment['user_id'] . "'";
            $user_picture = $db->getOne($sql);

            $discuss['user_name'] = $comment['user_name'];
            $discuss['dis_title'] = $comment['content'];
            $discuss['dis_id'] = $comment['comment_id'];
            $discuss['user_id'] = $comment['user_id'];
            $discuss['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $comment['add_time']);
        } else {
            $sql = "SELECT * FROM " . $ecs->table('discuss_circle') . " WHERE dis_id='$did' AND parent_id = 0 ";
            $discuss = $db->getRow($sql);
            if (empty($discuss)) {
                ecs_header("location: ./\n");
                exit;
            }
            if (!empty($discuss) && $discuss['review_status'] != 3) {
                show_message($_LANG['sorry_single_sun'], $_LANG['back_page_up'], '', 'error');
                exit;
            }

            $discuss['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $discuss['add_time']);

            //上一条
            $prev = $db->getRow("SELECT dis_id, dis_title FROM " . $ecs->table('discuss_circle') . " WHERE dis_id < " . $discuss['dis_id'] . " AND parent_id = 0 AND review_status = 3 ORDER BY dis_id DESC");
            //下一条
            $next = $db->getRow("SELECT dis_id, dis_title FROM " . $ecs->table('discuss_circle') . " WHERE dis_id > " . $discuss['dis_id'] . " AND parent_id = 0 AND review_status = 3 ORDER BY dis_id DESC");

            $sql = "select user_picture from " . $ecs->table('discuss_circle') . " as d, " . $ecs->table('users') . " as u " . " where d.dis_id = '$did' AND d.parent_id = 0 AND d.user_id = u.user_id AND review_status = 3 ";
            $user_picture = $db->getOne($sql);

            //热门话题
            $discuss_hot = get_discuss_all_list($goodsInfo['goods_id'], 0, 1, 10, 0, 'dis_browse_num', $did);
            $smarty->assign('hot_list', $discuss_hot);
        }

        //会员昵称
        $sql = "SELECT user_name, nick_name FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '" . $discuss['user_id'] . "' LIMIT 1";
        $info = $GLOBALS['db']->getRow($sql);
        $discuss['nick_name'] = !empty($info['nick_name']) ? $info['nick_name'] : $info['username'];

        $smarty->assign('user_picture', $user_picture);

        $position = assign_ur_here($goodsInfo['cat_id'], $goodsInfo['goods_name'], array($discuss['dis_title']), $goodsInfo['goods_url']);
        $smarty->assign('ip', real_ip());
        $smarty->assign('goods', $goodsInfo);
        $smarty->assign('page_title', $position['title']); // 页面标题
        $smarty->assign('ur_here', $position['ur_here']);

        $reply_discuss = get_reply_discuss_circle($discuss['dis_id'], $size, $page);
        $smarty->assign('reply_discuss', $reply_discuss);

        $smarty->assign('num', count($img_list));
        $smarty->assign('img_list', $img_list);
        $smarty->assign('photo', $img_list[0]['thumb_url']);
        $smarty->assign('discuss', $discuss);
		$smarty->assign('act', $_REQUEST['act']);


        /* 更新点击次数 */
        $db->query('UPDATE ' . $ecs->table('discuss_circle') . " SET dis_browse_num = dis_browse_num + 1 WHERE dis_id = '$did' AND parent_id = 0 AND review_status = 3 ");

        $smarty->assign('now_time', gmtime());           // 当前系统时间
    }

    $smarty->display('goods_discuss_show.dwt');
}

/* 插入晒单提交信息 */
elseif ($_REQUEST['act'] == 'add_discuss') {
    include_once(ROOT_PATH . 'includes/lib_transaction.php');

    $goods_id = !empty($_POST['good_id']) ? $_POST['good_id'] : 0;
    if (empty($goods_id)) {
        ecs_header("Location: index.php\n");
        exit;
    }
    if (empty($_SESSION['user_id'])) {
        ecs_header("Location: user.php\n");
        exit;
    }
	if (isset($_POST['captcha'])) {
		/* 验证码检查 */
		if (empty($_POST['captcha']))
		{
			show_message($_LANG['invalid_captcha'], '', 'category_discuss.php?id='.$goods_id, 'error');
		}

		$captcha_str = isset($_POST['captcha']) ? trim($_POST['captcha']) : '';
		$verify = new Verify();
		$captcha_code = $verify->check($captcha_str, 'captcha_common');

		if(!$captcha_code){
			show_message($_LANG['invalid_captcha'], '', 'category_discuss.php?id='.$goods_id, 'error');
		}
	}

    if (empty($_POST['referenceType'])) {
        show_message($_LANG['discuss_type'], $_LANG['back_page_up'], "category_discuss.php?id=$goods_id", 'error');
    }
    if (empty($_POST['commentTitle'])) {
        show_message($_LANG['title_Remarks'], $_LANG['back_page_up'], "category_discuss.php?id=$goods_id", 'error');
    }
    if (empty($_POST['content'])) {
        show_message($_LANG['content_null'], $_LANG['back_page_up'], "category_discuss.php?id=$goods_id", 'error');
    }

    $commentTitle = !empty($_POST['commentTitle']) ? $_POST['commentTitle'] : '';
    $content = !empty($_POST['content']) ? $_POST['content'] : '';
    $referenceType = !empty($_POST['referenceType']) ? $_POST['referenceType'] : 1;
    $user_name = get_table_date('users', "user_id='$_SESSION[user_id]'", array('user_name'), 2);
    $time = gmtime();

    $sql = "INSERT INTO " . $ecs->table('discuss_circle') . "(goods_id, user_id, dis_type, dis_title, dis_text, add_time, user_name)VALUES(
	'$goods_id', '$_SESSION[user_id]', '$referenceType', '$commentTitle', '$content', '$time', '$user_name')";
    $db->query($sql);
    /* 商品编号 */
    $dis_id = $db->insert_id();

    /* 处理相册图片 */
    if (!empty($dis_id)) {
        handle_gallery_image(0, $_FILES['img_url'], $_POST['img_desc'], $_POST['img_file'], $dis_id, 1);
        show_message($_LANG['cmt_submit_wait'], $_LANG['back_page_up'], "category_discuss.php?id=$goods_id");
        // ecs_header("Location: single_sun.php?act=discuss_show&did=$dis_id."\n");
        exit;
    } else {
        show_message($_LANG['Submit_fail'], $_LANG['back_page_up'], "category_discuss.php?id=$goods_id", 'error');
        exit;
    }
}

/* 插入晒单提交信息 */
elseif ($_REQUEST['act'] == 'ajax_verify') {
    require_once('includes/cls_json.php');
    $json = new JSON;
    $error = true;

    $captcha_str = isset($_GET['captcha']) ? trim($_GET['captcha']) : '';

    /* 验证码检查 */
    if ((intval($_CFG['captcha']) & CAPTCHA_COMMENT) && gd_version() > 0) {
        $verify = new Verify();
        $captcha_code = $verify->check($captcha_str, 'captcha_discuss', $rec_id);

        if (!$captcha_code) {
            $error = false;
        }
    }
    die($json->encode($error));
}

function get_reply_discuss_circle($dis_id, $size = 5, $reply_page = 1) {

    require_once('includes/cls_pager.php');
    $record_count = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('discuss_circle') . "WHERE parent_id = '$dis_id' AND review_status = 3 ");
    $reply_discuss = new Pager($record_count, $size, '', $dis_id, 0, $reply_page, 'reply_discuss_gotoPage');
    $limit = $reply_discuss->limit;
    $pager = $reply_discuss->fpage(array(0, 4, 5, 6, 9));

    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('discuss_circle') . " WHERE parent_id = '$dis_id' AND review_status = 3 ORDER BY add_time DESC " . $limit;
    $res = $GLOBALS['db']->getAll($sql);

    foreach ($res as $key => $row) {

        $sql = "select user_picture from " . $GLOBALS['ecs']->table('users') . " where user_id = '" . $row['user_id'] . "'";
        $user_picture = $GLOBALS['db']->getOne($sql);

        $res[$key]['user_picture'] = $user_picture;
        $res[$key]['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['add_time']);
        $res[$key]['quote'] = get_quote_reply($row['quote_id']);
        $res[$key]['dis_text'] = nl2br($row['dis_text']);
        $sql = "SELECT user_name, nick_name FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '" . $row['user_id'] . "' LIMIT 1";
        $info = $GLOBALS['db']->getRow($sql);
        $res[$key]['nick_name'] = !empty($info['nick_name']) ? $info['nick_name'] : $info['username'];
    }

    return array('list' => $res, 'pager' => $pager, 'record_count' => $record_count, 'size' => $size);
}

function get_quote_reply($quote_id)
{
    $sql = "SELECT user_name, dis_text, user_id FROM " . $GLOBALS['ecs']->table('discuss_circle') . " WHERE dis_id = '$quote_id' AND review_status = 3 ";
    $row = $GLOBALS['db']->getRow($sql);

    if ($row) {
        $sql = "SELECT user_name, nick_name FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '" . $row['user_id'] . "' LIMIT 1";
        $info = $GLOBALS['db']->getRow($sql);
        $row['nick_name'] = !empty($info['nick_name']) ? $info['nick_name'] : $info['username'];
    }

    return $row;
}

?>
