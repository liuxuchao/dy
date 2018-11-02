<?php

/**
 * DSC 会员中心异步操作
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
require(ROOT_PATH . 'includes/cls_json.php');
include_once(ROOT_PATH . 'includes/lib_clips.php');

/* 载入语言文件 */
require(ROOT_PATH . 'languages/' . $GLOBALS['_CFG']['lang'] . '/user.php');
$smarty->assign('lang', $_LANG);

$user_id = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

$json   = new JSON;
$result = array('error' => 0, 'message' => '', 'content' => '');

$is_jsonp = isset($_REQUEST['is_jsonp']) && !empty($_REQUEST['is_jsonp']) ? intval($_REQUEST['is_jsonp']) : 0; //jquery Ajax跨域

/*------------------------------------------------------ */
//-- 评论/晒单
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'comments_form') {
    
    //剔除未保存晒单图 start
    $sql = "SELECT id, comment_img, img_thumb FROM " .$ecs->table('comment_img'). " WHERE user_id = '$user_id' AND comment_id = 0";
    $img_list = $db->getAll($sql);
    /* 验证码相关设置 */
    if ((intval($_CFG['captcha']) & CAPTCHA_COMMENT) && gd_version() > 0)
    {
        $smarty->assign('enabled_captcha', 1);
        $smarty->assign('rand',            mt_rand());
    }
    foreach($img_list as $key=>$val){
        get_oss_del_file(array($val['comment_img'], $val['img_thumb']));
        @unlink(ROOT_PATH . $val['comment_img']);
        @unlink(ROOT_PATH . $val['img_thumb']);
    }
    
    $sql = "DELETE FROM ". $ecs->table('comment_img') ." WHERE user_id = '$user_id' AND comment_id = 0" ;
    $db->query($sql);
    //剔除未保存晒单图 end
    
    $rec_id = isset($_REQUEST['rec_id']) && !empty($_REQUEST['rec_id']) ? intval($_REQUEST['rec_id']) : 0;
    $sign = isset($_REQUEST['sign']) && !empty($_REQUEST['sign']) ? intval($_REQUEST['sign']) : 0;
    
    $comment = get_ajax_user_order_comment_list($user_id, 0, $sign, $rec_id);
    $smarty->assign('item', $comment);
    $smarty->assign('user_id', $user_id);
    $smarty->assign('sessid',    SESS_ID);
    $smarty->assign('sign',    $sign);
    
    $result['content'] = $smarty->fetch('library/comments_form.lbi');
} elseif($_REQUEST['act'] == 'upload_user_picture'){
    $filename = !empty($_REQUEST['image'])  ?  $_REQUEST['image']  : '';
    $filename_arr = array();
    if($filename && isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0){
        
        include_once(ROOT_PATH . '/includes/cls_image.php');
        $image = new cls_image($_CFG['bgcolor']);
        $filename_cropper = "data/images_user/cropper/" . $_SESSION['user_id'] . "_cropper.jpg"; //剪裁后未压缩的图片
        $route = "data/images_user/" ; 
        $filename_arr = explode(',', $filename);
        if(!empty($filename_arr)){
            
            if (!is_dir("data/images_user/cropper/")) {
                mkdir("data/images_user/cropper/",0777,true);
            }
            
            $somecontent1 = base64_decode($filename_arr[1]);  
            if ($handle = fopen($filename_cropper, "w+")) {   
                    if (!fwrite($handle, $somecontent1) == FALSE) {   
                     fclose($handle);  
                    }  
            }  
            $filename_120 = $image->make_thumb($filename_cropper, 120, 120,$route,'',$_SESSION['user_id'] . "_120.jpg");//48*48头像小图
            $filename_48 = $image->make_thumb($filename_cropper, 48, 48,$route,'',$_SESSION['user_id'] . "_48.jpg");//48*48头像小图
            $filename_24 = $image->make_thumb($filename_cropper, 24, 24,$route,'',$_SESSION['user_id'] . "_24.jpg");//24*24头像小图
            
            get_oss_add_file(array($filename_120, $filename_48, $filename_24));
	
            $parent['user_picture'] = $filename_120;
            $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('users'), $parent, 'UPDATE', "user_id = '" .$_SESSION['user_id']. "'");
            
            $result['file'] = $filename_120;
            $result['result'] = $_LANG['upload_success'];
            $result['error'] = 'ok';
             //记录会员操作日志
            users_log_change($_SESSION['user_id'],USER_PICT);
        }else{
            $result['result'] = $_LANG['unknown_error'];
        }
        
    }else{
        $result['result'] = $_LANG['overdue_login'];
    }
}
//切换举报主题
elseif($_REQUEST['act'] == 'checked_report_title')
{
    $type_id = !empty($_REQUEST['type_id'])  ?  intval($_REQUEST['type_id'])  : 0;
    $report_title = get_goods_report_title($type_id);
    $result = '<li><a href="javascript:void(0);" data-value="">' . $_LANG['Please_select'] . '</a></li>';
    if($report_title)
    {
        foreach($report_title as $k=>$v)
        {
            $result .= '<li><a href="javascript:void(0);" data-value="' . $v['title_id'] . '">' . $v['title_name'] . '</a></li>';
        }
    }
}
//上传举报证据
elseif($_REQUEST['act'] == 'ajax_report_img'){
    $goods_id = !empty($_REQUEST['goods_id'])  ?  intval($_REQUEST['goods_id'])  : 0;
    $sessid = isset($_GET['sessid']) ? trim($_GET['sessid']) : '';
    $img_file = isset($_FILES['file']) ? $_FILES['file'] : array();
    $sql = "SELECT count(*) FROM ". $ecs->table('sessions') ." WHERE userid = '" . $user_id . "' AND sesskey='$sessid'";
    if($user_id > 0  && $db->getOne($sql) > 0){
        include_once(ROOT_PATH . '/includes/cls_image.php');
        $image = new cls_image($_CFG['bgcolor']);

        $img_file = $image->upload_image($img_file, 'report_img/'.date('Ym')); //原图
        if($img_file === false){
            $result['error'] = 1;
            $result['message'] = $image->error_msg();
            die($json->encode($result));
        }

//        $img_thumb = $image->make_thumb($img_file, $GLOBALS['_CFG']['single_thumb_width'], $GLOBALS['_CFG']['single_thumb_height'], DATA_DIR . '/report_img/'.date('Ym').'/thumb/'); //缩略图
        
        get_oss_add_file(array($img_file));
        
        $report = array(
            'goods_id' => $goods_id,
            'user_id' => $user_id,
            'img_file' => $img_file
        );

        $sql = "SELECT count(*) FROM " . $ecs->table('goods_report_img') . " WHERE user_id = '$user_id' AND goods_id = '$goods_id'";
        $img_count = $db->getOne($sql);

        if ($img_count < 5 && $img_file) {
            $db->autoExecute($ecs->table('goods_report_img'), $report, 'INSERT');
        } else {
            $result['error'] = 1;
            $result['message'] = $_LANG['report_img_number'];
            die($json->encode($result));
        }
    }else{
        $result['error'] = 1;
        $result['message'] = $_LANG['overdue_login'];
    }
    $sql = "SELECT img_id as id , goods_id, report_id,user_id,img_file as comment_img FROM " . $ecs->table('goods_report_img') . " WHERE user_id = '$user_id' AND goods_id = '$goods_id' AND report_id = 0 ORDER BY  id DESC";
    $img_list = $db->getAll($sql);
    $smarty->assign('img_list', $img_list);
    $smarty->assign('report', 1);
    $result['content'] = $smarty->fetch("library/comment_image.lbi");
}
//删除图片 
elseif($_REQUEST['act'] == 'del_reportpic'){
    $img_id = isset($_REQUEST['re_imgId']) ? intval($_REQUEST['re_imgId']) : 0;
    $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
    $order_id = isset($_REQUEST['order_id']) ? intval($_REQUEST['order_id']) : 0;
    $complaint = isset($_REQUEST['complaint']) ? intval($_REQUEST['complaint']) : 0;
    $report = 0;
    if($user_id > 0 || $img_id > 0)
    {
        $img_list = array();
        //订单投诉
        if($complaint > 0){
            $report = 2;
            $ty_table = "complaint_img";
            //获取投诉相册
            $sql = "SELECT img_id as id , order_id, complaint_id,user_id,img_file as comment_img FROM " . $ecs->table('complaint_img') . " WHERE user_id = '$user_id' AND order_id = '$order_id'  ORDER BY  id DESC";
            $img_list = $db->getAll($sql);
        }else{
            $report = 1;
            $ty_table = "goods_report_img";
            //商品举报
            $sql = "SELECT img_id as id , goods_id, report_id,user_id,img_file as comment_img FROM " . $ecs->table('goods_report_img') . " WHERE user_id = '$user_id' AND goods_id = '$goods_id'";
            $img_list = $db->getAll($sql);
            
        }
        if(!empty($img_list)){
            foreach($img_list as $key=>$val){
                if($img_id == $val['id']){
                    $sql = "DELETE FROM " .$ecs->table($ty_table). " WHERE img_id = '$img_id'";
                    $db->query($sql);
                    unset($img_list[$key]);

                    get_oss_del_file(array($val['comment_img']));

                    @unlink(ROOT_PATH . $val['comment_img']);
                }
            }
        }
        $smarty->assign('img_list',        $img_list);
        $smarty->assign('report', $report);
        $result['content'] = $smarty->fetch("library/comment_image.lbi");
    }
    else
    {
        $result['error'] = 1;
        $result['message'] = $_LANG['overdue_login'];
    }
}
//切换举报状态
elseif($_REQUEST['act'] == 'check_report_state'){
    $report_id = !empty($_REQUEST['report_id'])  ?  intval($_REQUEST['report_id']) : 0;
    $state = !empty($_REQUEST['state'])  ?  intval($_REQUEST['state']) : 0;
    if($user_id > 0){
        $sql = "UPDATE".$ecs->table('goods_report')."SET report_state = '$state'  WHERE report_id = '$report_id'";
        $db->query($sql);
    }else{
        $result['error'] = 1;
        $result['message'] = $_LANG['overdue_login'];
    }
}
//获取举报类型描述
elseif($_REQUEST['act'] == 'complaint_title_desc'){
    $title_id = !empty($_REQUEST['title_id'])  ?  intval($_REQUEST['title_id']) : 0;
    if($user_id > 0){
        $sql = "SELECT title_desc FROM".$ecs->table('complain_title')." WHERE title_id = '$title_id'";
        $result['content'] = $db->getOne($sql);
    }else{
        $result['error'] = 1;
        $result['message'] = $_LANG['overdue_login'];
    }
}
//上传交易纠纷图片
elseif($_REQUEST['act'] == 'complaint_img'){
    $order_id = !empty($_REQUEST['order_id'])  ?  intval($_REQUEST['order_id'])  : 0;
    $sessid = isset($_GET['sessid']) ? trim($_GET['sessid']) : '';
    $img_file = isset($_FILES['file']) ? $_FILES['file'] : array();
    $sql = "SELECT count(*) FROM ". $ecs->table('sessions') ." WHERE userid = '" . $user_id . "' AND sesskey='$sessid'";
    if($user_id > 0  && $db->getOne($sql) > 0){
        include_once(ROOT_PATH . '/includes/cls_image.php');
        $image = new cls_image($_CFG['bgcolor']);

        $img_file = $image->upload_image($img_file, 'complaint_img/'.date('Ym')); //原图
        if($img_file === false){
            $result['error'] = 1;
            $result['message'] = $image->error_msg();
            die($json->encode($result));
        }

//        $img_thumb = $image->make_thumb($img_file, $GLOBALS['_CFG']['single_thumb_width'], $GLOBALS['_CFG']['single_thumb_height'], DATA_DIR . '/report_img/'.date('Ym').'/thumb/'); //缩略图
        
        get_oss_add_file(array($img_file));
        
        $report = array(
            'order_id' => $order_id,
            'user_id' => $user_id,
            'img_file' => $img_file
        );

        $sql = "SELECT count(*) FROM " . $ecs->table('complaint_img') . " WHERE user_id = '$user_id' AND order_id = '$order_id'";
        $img_count = $db->getOne($sql);

        if ($img_count < 5 && $img_file) {
            $db->autoExecute($ecs->table('complaint_img'), $report, 'INSERT');
        } else {
            $result['error'] = 1;
            $result['message'] = $_LANG['report_img_number'];
            die($json->encode($result));
        }
    }else{
        $result['error'] = 1;
        $result['message'] = $_LANG['overdue_login'];
    }
    $sql = "SELECT img_id as id , order_id, complaint_id,user_id,img_file as comment_img FROM " . $ecs->table('complaint_img') . " WHERE user_id = '$user_id' AND order_id = '$order_id' AND complaint_id = 0 ORDER BY  id DESC";
    $img_list = $db->getAll($sql);
    $smarty->assign('img_list', $img_list);
    $smarty->assign('report', 2);
    $result['content'] = $smarty->fetch("library/comment_image.lbi");
}
/*------------------------------------------------------ */
//-- 发布聊天
/*------------------------------------------------------ */
elseif($_REQUEST['act'] == 'talk_release')
{
    $talk_id = !empty($_REQUEST['talk_id'])  ?  intval($_REQUEST['talk_id']) : 0;
    $complaint_id = !empty($_REQUEST['complaint_id'])  ?  intval($_REQUEST['complaint_id']) : 0;
    $talk_content = !empty($_REQUEST['talk_content'])  ?  trim($_REQUEST['talk_content']) : '';
    $type = !empty($_REQUEST['type'])  ?  intval($_REQUEST['type']) : 0;
    
    //执行操作类型  1、刷新，0入库
    if($type == 0){
         $complaint_talk = array(
            'complaint_id' => $complaint_id,
            'talk_member_id' => $user_id,
            'talk_member_name' => $_SESSION['user_name'],
            'talk_member_type' => 1,
            'talk_content' => $talk_content,
            'talk_time' => gmtime(),
            'view_state' => 'user'
        );
         $db->autoExecute($ecs->table('complaint_talk'), $complaint_talk, 'INSERT');
    }
      $talk_list = checkTalkView($complaint_id,'user');
     $smarty->assign('talk_list',$talk_list);
     $result['content'] = $smarty->fetch("library/talk_list.lbi");
     die($json->encode($result));
}
//删除订单投诉
elseif($_REQUEST['act'] == 'del_compalint'){
     $complaint_id = !empty($_REQUEST['compalint_id'])  ?  intval($_REQUEST['compalint_id']) : 0;
    if($user_id > 0){
        //删除相关图片
        del_complaint_img($complaint_id);
        del_complaint_img($complaint_id,'appeal_img');
        //删除相关聊天
        del_complaint_talk($complaint_id);
        $sql = "DELETE FROM".$ecs->table('complaint')."WHERE complaint_id = '$complaint_id'";
        $db->query($sql);
    }else{
        $result['error'] = 1;
        $result['message'] = $_LANG['overdue_login'];
    }
}
if ($is_jsonp) {
    echo $_GET['jsoncallback'] . "(" . $json->encode($result) . ")";
} else {
    echo $json->encode($result);
}

/**
 * 评论晒单
 * @param type $user_id
 * @param type $type count,list标识
 * @param type $sign 0：带评论 1：追加图片 2:已评论
 * @param type $size
 * @param type $start
 * @return type
 */
function get_ajax_user_order_comment_list($user_id, $type = 0, $sign = 0, $rec_id) {
    
    $where = " AND og.rec_id = $rec_id ";

    if ($sign == 0) {
        $where .= " AND (SELECT count(*) FROM " . $GLOBALS['ecs']->table('comment') . " AS c WHERE c.comment_type = 0 AND c.id_value = g.goods_id AND c.rec_id = og.rec_id AND c.parent_id = 0 AND c.user_id = '$user_id') = 0 ";
    } elseif ($sign == 1) {
        $where .= " AND (SELECT count(*) FROM " . $GLOBALS['ecs']->table('comment') . " AS c WHERE c.comment_type = 0 AND c.id_value = g.goods_id AND c.rec_id = og.rec_id AND c.parent_id = 0 AND c.user_id = '$user_id') > 0 ";
        $where .= " AND (SELECT count(*) FROM " . $GLOBALS['ecs']->table('comment_img') . " AS ci, " . $GLOBALS['ecs']->table('comment') . " AS c" . " WHERE c.comment_type = 0 AND c.id_value = g.goods_id AND ci.rec_id = og.rec_id AND c.parent_id = 0 AND c.user_id = '$user_id' AND ci.comment_id = c.comment_id ) = 0 ";
    } elseif ($sign == 2) {
        $where .= " AND (SELECT count(*) FROM " . $GLOBALS['ecs']->table('comment') . " AS c WHERE c.comment_type = 0 AND c.id_value = g.goods_id AND c.rec_id = og.rec_id AND c.parent_id = 0 AND c.user_id = '$user_id') > 0 ";
        $where .= " AND (SELECT count(*) FROM " . $GLOBALS['ecs']->table('comment_img') . " AS ci, " . $GLOBALS['ecs']->table('comment') . " AS c" . " WHERE c.comment_type = 0 AND c.id_value = g.goods_id AND ci.rec_id = og.rec_id AND c.parent_id = 0 AND c.user_id = '$user_id' AND ci.comment_id = c.comment_id ) > 0 ";
    }

    $sql = "SELECT og.rec_id, og.order_id, og.goods_id, og.goods_name, oi.add_time, g.goods_thumb, g.goods_product_tag, og.ru_id,oi.order_sn,og.goods_number,og.goods_price FROM " .
            $GLOBALS['ecs']->table('order_goods') . " AS og " .
            "LEFT JOIN " . $GLOBALS['ecs']->table('order_info') . " AS oi ON og.order_id = oi.order_id " .
            "LEFT JOIN " . $GLOBALS['ecs']->table('goods') . " AS g ON og.goods_id = g.goods_id " .
            "WHERE og.goods_id = g.goods_id AND oi.user_id = '$user_id' $where";

    $row = $GLOBALS['db']->getRow($sql);
   
    if ($row) {
        $row['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['add_time']);
        $row['goods_thumb'] = get_image_path($goods_id, $row['goods_thumb'], true);
        $row['impression_list'] = !empty($row['goods_product_tag']) ? explode(',', $row['goods_product_tag']) : array();
        $row['goods_url'] = build_uri('goods', array('gid' => $row['goods_id']), $row['goods_name']);
        $row['goods_price'] = price_format($row['goods_price']);

        //订单商品评论信息
        $row['comment'] = get_order_goods_comment($row['goods_id'], $row['rec_id'], $user_id);
    }

    return $row;
}

?>