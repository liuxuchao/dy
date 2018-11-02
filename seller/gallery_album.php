<?php

/**
 * 商创 图片库管理
 * ===========================================================
 * * 版权所有 2005-2016 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ==========================================================
 * $Author: liubo $
 * $Id: affiliate.php 17217 2011-01-19 06:29:08Z liubo $
 */

define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
require(dirname(__FILE__) . '/includes/cls_FileUpload.php'); //ecmoban
require_once(ROOT_PATH . 'includes/cls_image.php');
/*交换对象*/
$exc   = new exchange($ecs->table("gallery_album"), $db, 'album_id', 'album_mame');

$adminru = get_admin_ru_id();

$smarty->assign("priv_ru",1);
$smarty->assign('menu_select', array('action' => '02_cat_and_goods', 'current' => 'gallery_album'));
/* 允许上传的文件类型 */
$allow_file_types = '|GIF|JPG|PNG|JPEG|';
if($_REQUEST['act'] == 'list'){
    $smarty->assign('primary_cat',     $_LANG['02_cat_and_goods']);
    $smarty->assign('ur_here',     $_LANG['gallery_album']);
    $smarty->assign('action_link', array('text' => $_LANG['add_album'], 'href' => 'gallery_album.php?act=add', 'class' => 'icon-plus'));
    
    $parent_id     =  empty($_REQUEST['parent_id'])  ? 0 : intval($_REQUEST['parent_id']);
    
    if($parent_id > 0){
        $sql = "SELECT parent_album_id FROM".$ecs->table("gallery_album")."WHERE album_id = '$parent_id'";
        $parent_album_id = $db->getOne($sql);
        $smarty->assign('action_link2', array('text' => $_LANG['return_to_superior'], 'href' => 'gallery_album.php?act=list&parent_id='.$parent_album_id));
    }
    
    $offline_store = get_pzd_list($adminru['ru_id']);
    
    $smarty->assign('gallery_album', $offline_store['pzd_list']);
    $smarty->assign('filter', $offline_store['filter']);
    $smarty->assign('record_count', $offline_store['record_count']);
    $smarty->assign('page_count', $offline_store['page_count']);
    $smarty->assign('full_page', 1);
	
	//分页
	$page_count_arr = seller_page($offline_store,$_REQUEST['page']);
    $smarty->assign('page_count_arr',$page_count_arr);	
    
    $smarty->display("gallery_album.dwt");
}
elseif($_REQUEST['act'] == 'query'){
    $offline_store = get_pzd_list($adminru['ru_id']);
    $smarty->assign('primary_cat',     $_LANG['02_cat_and_goods']);
    $smarty->assign('gallery_album', $offline_store['pzd_list']);
    $smarty->assign('filter', $offline_store['filter']);
    $smarty->assign('record_count', $offline_store['record_count']);
    $smarty->assign('page_count', $offline_store['page_count']);
    //分页
	$page_count_arr = seller_page($offline_store,$_REQUEST['page']);
    $smarty->assign('page_count_arr',$page_count_arr);	
//跳转页面  
    make_json_result($smarty->fetch('gallery_album.dwt'), '', array('filter' => $offline_store['filter'], 'page_count' => $offline_store['page_count']));
}
elseif($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit'){
    $smarty->assign('primary_cat',     $_LANG['02_cat_and_goods']);
    $smarty->assign('ur_here',     $_LANG['add_album']);
    $smarty->assign('action_link', array('text' => $_LANG['gallery_album'], 'href' => 'gallery_album.php?act=list', 'class' => 'icon-reply'));
    
    $parent_id = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : 0;
    $album_id = isset($_REQUEST['id'])  ? intval($_REQUEST['id']) : 0;
    $album_info = array();
    if($_REQUEST['act'] == 'add'){
            $cat_select = gallery_cat_list(0, 0, false, 0, true);   

            /* 简单处理缩进 */
            foreach ($cat_select as $k => $v) {
                    if ($v['level']) {
                            $level = str_repeat('&nbsp;', $v['level'] * 4);
                            $cat_select[$k]['name'] = $level . $v['name'];
                    }
            }
            $album_info['parent_album_id'] = $parent_id;
            $album_info['ru_id'] = $adminru['ru_id'];
            $smarty->assign('cat_select', $cat_select);
    }else{
            $cat_select = gallery_cat_list(0, $cat_info['parent_id'], false, 0, true);  	
            $cat_child = get_cat_child($album_id);
            /* 简单处理缩进 */
            foreach ($cat_select as $k => $v) {
                    if ($v['level']) {
                            $level = str_repeat('&nbsp;', $v['level'] * 4);
                            $cat_select[$k]['name'] = $level . $v['name'];
                    }
                    if(!empty($cat_child) && in_array($v['album_id'],$cat_child)){
                            unset($cat_select[$k]);
                        }
            }
            $smarty->assign('cat_select', $cat_select);
    }
    
    if($album_id > 0)
    {
        $album_info = get_goods_gallery_album(2, $album_id);
    }
    $smarty->assign("album_info",$album_info);
    $form_action = ($_REQUEST['act'] == 'add')  ?  "insert" : "update";
    $smarty->assign("form_action",$form_action);
    
    $smarty->display("gallery_album_info.dwt");
}elseif($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update'){
    $album_mame = isset($_REQUEST['album_mame'])  ?  addslashes($_REQUEST['album_mame']) : '';
    $album_desc = isset($_REQUEST['album_desc'])  ?  addslashes($_REQUEST['album_desc']) : '';
    $sort_order = isset($_REQUEST['sort_order'])  ? intval($_REQUEST['sort_order']) : 50;
    $parent_id = isset($_REQUEST['parent_id'])  ?  intval($_REQUEST['parent_id']) : 0;
    
    if($_REQUEST['act'] == 'insert'){
        /*检查是否重复*/
        $is_only = $exc->is_only('album_mame', $album_mame,0,"ru_id = ".$adminru['ru_id']);
        if (!$is_only)
        {
            sys_msg(sprintf($_LANG['title_exist'], stripslashes($album_mame)), 1);
        }
         /* 取得文件地址 */
        $file_url = '';
        if ((isset($_FILES['album_cover']['error']) && $_FILES['album_cover']['error'] == 0) || (!isset($_FILES['album_cover']['error']) && isset($_FILES['album_cover']['tmp_name']) && $_FILES['album_cover']['tmp_name'] != 'none'))
        {
            // 检查文件格式
            if (!check_file_type($_FILES['album_cover']['tmp_name'], $_FILES['album_cover']['name'], $allow_file_types))
            {
                sys_msg($_LANG['invalid_file']);
            }

            // 复制文件
            $res = upload_article_file($_FILES['album_cover']);
            if ($res != false)
            {
                $file_url = $res;
            }
        }
        if ($file_url == '')
        {
            $file_url = $_POST['file_url'];
        }
        $time = gmtime();
        $sql = "INSERT INTO".$ecs->table("gallery_album")."(`parent_album_id`,`album_mame`,`album_cover`,`album_desc`,`sort_order`,`add_time`,`ru_id`)"
                . " VALUES ('$parent_id','$album_mame','$file_url','$album_desc','$sort_order','$time','".$adminru['ru_id']."')";

        if($db->query($sql) == true)
        {
            $link[0]['text'] = $_LANG['continue_add_album'];
            $link[0]['href'] = 'gallery_album.php?act=add';

            $link[1]['text'] = $_LANG['bank_list'];
            $link[1]['href'] = 'gallery_album.php?act=list';

            sys_msg($_LANG['add_succeed'],0, $link);
        }
    }
    else
    {
        $album_id = isset($_REQUEST['id'])  ? intval($_REQUEST['id']) : 0;
        /*检查是否重复*/
        $is_only = $exc->is_only('album_mame', $album_mame,0,"ru_id = ".$adminru['ru_id']." AND album_id != '$album_id'");
        if (!$is_only)
        {
            sys_msg(sprintf($_LANG['title_exist'], stripslashes($album_mame)), 1);
        }
         /* 取得文件地址 */
        $file_url = '';
        if ((isset($_FILES['album_cover']['error']) && $_FILES['album_cover']['error'] == 0) || (!isset($_FILES['album_cover']['error']) && isset($_FILES['album_cover']['tmp_name']) && $_FILES['album_cover']['tmp_name'] != 'none'))
        {
            // 检查文件格式
            if (!check_file_type($_FILES['album_cover']['tmp_name'], $_FILES['album_cover']['name'], $allow_file_types))
            {
                sys_msg($_LANG['invalid_file']);
            }

            // 复制文件
            $res = upload_article_file($_FILES['album_cover']);
            if ($res != false)
            {
                $file_url = $res;
            }
        }
        if ($file_url == '')
        {
            $file_url = $_POST['file_url'];
        }
        /* 如果 file_url 跟以前不一样，且原来的文件是本地文件，删除原来的文件 */
         $old_url = get_goods_gallery_album(0, $album_id, array('album_cover'));
         if ($old_url != '' && $old_url != $file_url && strpos($old_url, 'http: ') === false && strpos($old_url, 'https: ') === false)
         {
             @unlink(ROOT_PATH . $old_url);
             $del_arr_img[] =  $old_url;

            get_oss_del_file($del_arr_img);
         }

         $sql = "UPDATE".$ecs->table('gallery_album')." SET album_mame='$album_mame',album_cover='$file_url'"
                . ",album_desc='$album_desc',sort_order='$sort_order',parent_album_id='$parent_id' WHERE album_id = '$album_id'";
         if($db->query($sql) == true)
         {
            $link[0]['text'] = $_LANG['bank_list'];
            $link[0]['href'] = 'gallery_album.php?act=list';

            sys_msg($_LANG['edit_succeed'],0, $link);    
         }
    }
    
}
/*查看图片*/
elseif($_REQUEST['act'] == 'view')
{
    $album_id = isset($_REQUEST['id'])  ? intval($_REQUEST['id']) : 0;
    $sql = "SELECT album_mame FROM ".$ecs->table("gallery_album")." WHERE album_id = '$album_id'";
    $album_mame = $db->getOne($sql);
    
    $smarty->assign('primary_cat',     $_LANG['02_cat_and_goods']);
    $smarty->assign('ur_here',     sprintf($_LANG['view_pic'], stripslashes($album_mame)));
    $smarty->assign('action_link', array('text' => $_LANG['upload_image'], 'spec' => "ectype='addpic_album'"));
    $smarty->assign('album_id',     $album_id);

    $cat_select = gallery_cat_list(0, 0, false, 0, true);
	
    /* 简单处理缩进 */
    foreach ($cat_select as $k => $v) {
            if ($v['level']) {
                    $level = str_repeat('&nbsp;', $v['level'] * 4);
                    $cat_select[$k]['name'] = $level . $v['name'];
            }
    }
    $smarty->assign('cat_select', $cat_select);
    
    $offline_store = get_pic_album($album_id);
    
    $smarty->assign('pic_album', $offline_store['pzd_list']);
    $smarty->assign('filter', $offline_store['filter']);
    $smarty->assign('record_count', $offline_store['record_count']);
    $smarty->assign('page_count', $offline_store['page_count']);
    $smarty->assign('full_page', 1);
	
	//分页
	$page_count_arr = seller_page($offline_store,$_REQUEST['page']);
    $smarty->assign('page_count_arr',$page_count_arr);	
    
    $smarty->display("pic_album.dwt");
}
elseif($_REQUEST['act'] == 'pic_query'){
    $album_id = isset($_REQUEST['album_id'])  ? intval($_REQUEST['album_id']) : 0;
    $offline_store = get_pic_album($album_id);
    $smarty->assign('pic_album', $offline_store['pzd_list']);
    $smarty->assign('filter', $offline_store['filter']);
    $smarty->assign('record_count', $offline_store['record_count']);
    $smarty->assign('page_count', $offline_store['page_count']);
    //分页
	$page_count_arr = seller_page($offline_store,$_REQUEST['page']);
    $smarty->assign('page_count_arr',$page_count_arr);
//跳转页面  
    make_json_result($smarty->fetch('pic_album.dwt'), '', array('filter' => $offline_store['filter'], 'page_count' => $offline_store['page_count']));
}
/*删除*/
elseif($_REQUEST['act'] == 'remove')
{
    
    require(ROOT_PATH . '/includes/lib_visual.php');
     $album_id = intval($_GET['id']);

    //获取下级相册数量
    $sql = "SELECT COUNT(*) FROM" . $ecs->table('gallery_album') . " WHERE parent_album_id = '$album_id'";
    $album_count = $db->getOne($sql);
    //存在下级相册 不让删除
    if ($album_count > 0) {
        make_json_error($_LANG['no_last_album_no_remove']);
    } else {

        /* 删除原来的文件 */
        $old_url = get_goods_gallery_album(0, $album_id, array('album_cover'));
        if ($old_url != '' && @strpos($old_url, 'http://') === false && @strpos($old_url, 'https://') === false) {
            @unlink(ROOT_PATH . $old_url);
        }
    //删除该相册目录下的所以图片
        $dir = ROOT_PATH . 'data/gallery_album/' . $album_id; //模板目录
        $rmdir = del_DirAndFile($dir);
    //删除图片数据库
        $sql = "DELETE FROM" . $ecs->table('pic_album') . "WHERE album_id = " . $album_id;
        $db->query($sql);
        $exc->drop($album_id);
        $url = 'gallery_album.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);
        ecs_header("Location: $url\n");
    }
    exit;
}
/*删除图片*/
elseif($_REQUEST['act'] == 'pic_remove'){
     require(ROOT_PATH . '/includes/cls_json.php');
    $json = new JSON;
    $result = array('error' => '','content' => '','url'=>'');
     $id = isset($_REQUEST['id'])  ?  intval($_REQUEST['id']) : 0;
    /* 删除原来的文件 */

    $pic_info = gallery_pic_album(2, $id, array('pic_file', 'pic_thumb', 'pic_image', 'album_id'));

    /* 删除原图 */
    if ($pic_info['pic_file'] != '' && @strpos($pic_info['pic_file'], 'http://') === false && @strpos($pic_info['pic_file'], 'https://') === false) {
        dsc_unlink(ROOT_PATH . $pic_info['pic_file']);
        $arr_img[] = $pic_info['pic_file'];
    }

    /* 删除缩略图 */
    if ($pic_info['pic_thumb'] != '' && @strpos($pic_info['pic_thumb'], 'http://') === false && @strpos($pic_info['pic_thumb'], 'https://') === false) {
        dsc_unlink(ROOT_PATH . $pic_info['pic_thumb']);
        $arr_img[] = $pic_info['pic_thumb'];
    }

    /* 删除图 */
    if ($pic_info['pic_image'] != '' && @strpos($pic_info['pic_image'], 'http://') === false && @strpos($pic_info['pic_image'], 'https://') === false) {
        dsc_unlink(ROOT_PATH . $pic_info['pic_image']);
        $arr_img[] = $pic_info['pic_image'];
    }

    get_oss_del_file($arr_img);
    
    $sql = "DELETE FROM" . $ecs->table('pic_album') ." WHERE pic_id = '$id' ";
    if($db->query($sql)){
        $result['error'] = 0;
        $result['id'] = $id;
    }else{
        $result['error'] = 1;
        $result['content'] = $_LANG['system_error_retry'];  
    }
    die(json_encode($result));
}

    /*------------------------------------------------------ */
//-- 上传图片
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'upload_pic')
{
    include_once(ROOT_PATH . '/includes/cls_image.php');
    $image = new cls_image($_CFG['bgcolor']);
    require_once(ROOT_PATH . '/' . ADMIN_PATH . '/includes/lib_goods.php');
    
    $result = array('error' => 0, 'pic' => '', 'name' => '');
    
    $album_id = isset($_REQUEST['album_id']) && !empty($_REQUEST['album_id']) ? intval($_REQUEST['album_id']) : 0;
    
    $bucket_info = get_bucket_info();
    
    if ($_FILES['file']['name']) {
        if ($realname) {
            $extname = strtolower(substr($_FILES['file']['name'], strrpos($_FILES['file']['name'], '.') + 1));
        } else {
            $extname = strtolower(substr($_FILES['file']['name'], strrpos($_FILES['file']['name'], '.') + 1));
        }
    }
    
    if ($extname == 'mp4') {
        
        $video_path = ROOT_PATH .DATA_DIR. '/uploads/pic_album/' . $album_id . "/";

        if (!file_exists($video_path)) {
            make_dir($video_path);
        }

        $videoOther = array(
            'isRandName' => true, 
            'allowType' => array('mp4'), 
            'FilePath' => $video_path, 
            'MAXSIZE' => 20000000
        );
        
        $upload = new FileUpload($videoOther);
        
        $pic_id = 0;
        if ($upload->uploadFile('file')) {
            
            $video = DATA_DIR . "/uploads/pic_album/" .$album_id. "/" . $upload->getNewFileName();
            
            $ru_id = get_goods_gallery_album(0, $album_id, array('ru_id'));
            
            $image_name = explode('.', $_FILES["file"]["name"]);
            $pic_name = $image_name['0']; //文件名称
            $pic_size = intval($_FILES['file']['size']); //视频大小
            $add_time = gmtime(); //上传时间
            
            //入库
            $sql = "INSERT INTO" . $ecs->table('pic_album') . "(`ru_id`,`album_id`,`pic_name`,`pic_file`,`pic_size`,`pic_spec`,`add_time`) VALUES('$ru_id','$album_id','$pic_name','$video','$pic_size','','$add_time')";
            $db->query($sql);
            $pic_id = $db->insert_id();
            
            $arr_img = array($video);
            get_oss_add_file($arr_img);
            
            $result['picid'] = $pic_id;
        } else {
            $result['error'] = 1;
            $result['massege'] = $upload->getErrorMsg();
        }
        
        if ($pic_id) {

            /* 删除本地视频 start */
            if ($GLOBALS['_CFG']['open_oss'] == 1 && $bucket_info['is_delimg'] == 1) {
                dsc_unlink($video);
            }
            /* 删除本地视频 start */
        }
    } else {
        
        $goods_img = '';  // 初始化商品图片
        $goods_thumb = '';  // 初始化商品缩略图
        $original_img = '';  // 初始化原始图片
        $old_original_img = '';  // 初始化原始图片旧图
        /* 取得文件地址 */
        $file_url = '';
        $pic_name = '';
        $pic_size = 0;
        $proc_thumb = (isset($GLOBALS['shop_id']) && $GLOBALS['shop_id'] > 0) ? false : true;
        if ((isset($_FILES['file']['error']) && $_FILES['file']['error'] == 0) || (!isset($_FILES['file']['error']) && isset($_FILES['file']['tmp_name']) && $_FILES['file']['tmp_name'] != 'none')) {

            // 检查文件格式
            if (!check_file_type($_FILES['file']['tmp_name'], $_FILES['file']['name'], $allow_file_types)) {
                sys_msg($_LANG['invalid_file']);
            }
            $image_name = explode('.', $_FILES["file"]["name"]);
            $pic_name = $image_name['0']; //文件名称
            $pic_size = intval($_FILES['file']['size']); //图片大小
            $dir = "gallery_album/" . $album_id . "/original_img";
            $original_img = $image->upload_image($_FILES['file'], $dir); // 原始图片
            $goods_img = $original_img;   // 商品图片

            $images = $original_img;   // 商品图片
            if ($proc_thumb && $image->gd_version() > 0 && $image->check_img_function($_FILES['file']['type'])) {
                if ($_CFG['thumb_width'] != 0 || $_CFG['thumb_height'] != 0) {
                    $goods_thumb = $image->make_thumb('../' . $original_img, $GLOBALS['_CFG']['thumb_width'], $GLOBALS['_CFG']['thumb_height'], "../data/gallery_album/" . $album_id . "/thumb_img/");
                    $goods_thumb = str_replace('../', " ", $goods_thumb, $i);
                    if ($goods_thumb === false) {
                        sys_msg($image->error_msg(), 1, array(), false);
                    }
                } else {
                    $goods_thumb = $original_img;
                }
                // 如果设置大小不为0，缩放图片
                if ($_CFG['image_width'] != 0 || $_CFG['image_height'] != 0) {
                    $images = $image->make_thumb('../' . $original_img, $GLOBALS['_CFG']['image_width'], $GLOBALS['_CFG']['image_height'], "../data/gallery_album/" . $album_id . "/images/");
                } else {
                    $images = $original_img;
                }
                if (intval($_CFG['watermark_place']) > 0 && !empty($GLOBALS['_CFG']['watermark'])) {
                    if ($image->add_watermark($images, '', $GLOBALS['_CFG']['watermark'], $GLOBALS['_CFG']['watermark_place'], $GLOBALS['_CFG']['watermark_alpha']) === false) {
                        sys_msg($image->error_msg(), 1, array(), false);
                    }
                }
            }
            if ($images) {
                $images = str_replace('../', "", $images, $i);
            }
            $result['data'] = array(
                'original_img' => $original_img,
                'goods_thumb' => $goods_thumb
            );
            $result['pic'] = get_image_path(0, $original_img);
            // 复制文件
            list($width, $height, $type, $attr) = getimagesize("../" . $original_img); //获取规格
            $pic_spec = $width . 'x' . $height; //图片规格
            $add_time = gmtime(); //上传时间

            $ru_id = get_goods_gallery_album(0, $album_id, array('ru_id'));

            $arr_img = array(
                $original_img,
                $goods_thumb,
                $images
            );

            get_oss_add_file($arr_img);

            //入库
            $sql = "INSERT INTO" . $ecs->table('pic_album') . "(`ru_id`,`album_id`,`pic_name`,`pic_file`,`pic_size`,`pic_spec`,`add_time`,`pic_thumb`,`pic_image`) VALUES('$ru_id','$album_id','$pic_name','$original_img','$pic_size','$pic_spec','$add_time','$goods_thumb','$images')";
            $db->query($sql);
            $pic_id = $db->insert_id();

            if ($pic_id) {

                /* 删除本地商品图片 start */
                if ($GLOBALS['_CFG']['open_oss'] == 1 && $bucket_info['is_delimg'] == 1) {
                    $album_images = array($original_img, $goods_thumb, $images);
                    dsc_unlink($album_images);
                }
                /* 删除本地商品图片 start */
            }
            $result['picid'] = $pic_id;
        } else {
            $result['error'] = 0;
            $result['massege'] = $_LANG['upload_error_check_server_config'];
        }
    }
    die(json_encode($result));
}

/*------------------------------------------------------ */
//-- 上传视频
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'goods_video')
{
    $result = array('error' => 0, 'goods_id' => 0, 'massege'=> '', 'goods_video' => '', 'goods_video_path' => '');
    
    $goods_id = isset($_REQUEST['goods_id']) && !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
    
    $result['goods_id'] = $goods_id;
    
    $bucket_info = get_bucket_info();
    
    if ($_FILES['file']['name']) {
        if ($realname) {
            $extname = strtolower(substr($_FILES['file']['name'], strrpos($_FILES['file']['name'], '.') + 1));
        } else {
            $extname = strtolower(substr($_FILES['file']['name'], strrpos($_FILES['file']['name'], '.') + 1));
        }
    }
    
    $video_path = ROOT_PATH .DATA_DIR. '/uploads/goods/' . $goods_id . "/";

    if (!file_exists($video_path)) {
        make_dir($video_path);
    }

    if ($extname == 'mp4') {
        
        $videoOther = array(
            'isRandName' => true, 
            'allowType' => array('mp4'), 
            'FilePath' => $video_path, 
            'MAXSIZE' => 20000000
        );
  
        $upload = new FileUpload($videoOther);
        
        if ($upload->uploadFile('file')) {
            
            $goods_video = DATA_DIR . "/uploads/goods/" .$goods_id. "/" . $upload->getNewFileName();
            
            $goodsOther = array($goods_video);
            get_oss_add_file($goodsOther);
            
            if($goods_id){
                $sql = "SELECT goods_video FROM" .$GLOBALS['ecs']->table('goods'). " WHERE goods_id = '$goods_id'";
                $video_old = $GLOBALS['db']->getOne($sql, true);
                
                dsc_unlink($video_old);
                
                $arr[] =  $video_old;
                get_oss_del_file($arr);
                
                $sql = "UPDATE " .$GLOBALS['ecs']->table('goods'). " SET goods_video = '$goods_video' WHERE goods_id = '$goods_id'";
                $GLOBALS['db']->query($sql);
            }  else {             
                $admin_id = get_admin_id();
                $_SESSION['goods_video'][$admin_id][] = $goods_video;
            }

            $result['goods_video'] = $goods_video;
            $result['goods_video_path'] = !empty($goods_video) ? get_image_path($goods_id, $goods_video) : '';
        } else {
            $result['error'] = 1;
            $result['massege'] = $upload->getErrorMsg();
        }
        
        if ($goods_id) {
            
            sleep(3);
            
            /* 删除本地视频 start */
            if ($GLOBALS['_CFG']['open_oss'] == 1 && $bucket_info['is_delimg'] == 1) {
                dsc_unlink($goods_video);
            }
            /* 删除本地视频 start */
        }
    }else{
        $result['error'] = 2;
        $result['massege'] = $_LANG['upload_format_error'];
    }

    die(json_encode($result));
}

/*------------------------------------------------------ */
//-- 删除视频
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'del_video')
{
    $result = array('error' => 0, 'goods_id' => 0, 'massege'=> '', 'goods_video' => '');
    
    $goods_id = isset($_REQUEST['goods_id']) && !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
    
    if ($goods_id) {
        $sql = "SELECT goods_video FROM" . $GLOBALS['ecs']->table('goods') . " WHERE goods_id = '$goods_id'";
        $video_old = $GLOBALS['db']->getOne($sql, true);

        dsc_unlink($video_old);
        $arr[] = $video_old;
        get_oss_del_file($arr);

        $sql = "UPDATE " . $GLOBALS['ecs']->table('goods') . " SET goods_video = '' WHERE goods_id = '$goods_id'";
        $GLOBALS['db']->query($sql);
    }else{
        get_del_goods_video();
    }

    die(json_encode($result));
}

/*图片批量操作*/
elseif($_REQUEST['act'] == 'batch')
 {
    $checkboxes = !empty($_REQUEST['checkboxes']) ? $_REQUEST['checkboxes'] : array();
    $old_album_id = isset($_REQUEST['old_album_id']) ? intval($_REQUEST['old_album_id']) : 0;
    $album_id = isset($_REQUEST['album_id']) ? intval($_REQUEST['album_id']) : 0;
    $type = isset($_REQUEST['type']) ? addslashes($_REQUEST['type']) : '';

    if (!empty($checkboxes)) {
        if ($type == 'remove') {
            /* 获取所以图片 */
            $sql = "SELECT pic_file,pic_thumb, pic_image FROM" . $ecs->table('pic_album') . " WHERE ru_id='" . $adminru['ru_id'] . "' AND pic_id" . db_create_in($checkboxes);
            $pic_info = $db->getAll($sql);
            /* 存在图片  删除 */
            if (!empty($pic_info)) {
                foreach ($pic_info as $v) {
                    if ($v['pic_file'] != '' && @strpos($v['pic_file'], 'http://') === false && @strpos($v['pic_file'], 'https://') === false) {
                        dsc_unlink(ROOT_PATH . $v['pic_file']);
                        $arr_img[] =  $v['pic_file'];
                    }
                    
                    /* 删除缩略图 */
                    if ($v['pic_thumb'] != '' && @strpos($v['pic_thumb'], 'http://') === false && @strpos($v['pic_thumb'], 'https://') === false) {
                        dsc_unlink(ROOT_PATH . $v['pic_thumb']);
                        $arr_img[] =  $v['pic_thumb'];
                    }
                    
                    /* 删除缩略图 */
                    if ($v['pic_image'] != '' && @strpos($v['pic_image'], 'http://') === false && @strpos($v['pic_image'], 'https://') === false) {
                        dsc_unlink(ROOT_PATH . $v['pic_image']);
                        $arr_img[] =  $v['pic_image'];
                    }
                    
                    get_oss_del_file($arr_img);
                }
            }
            /* 删除活动 */
            $sql = 'DELETE FROM' . $ecs->table('pic_album') . " WHERE ru_id='" . $adminru['ru_id'] . "' AND  pic_id" . db_create_in($checkboxes);

            if ($db->query($sql) == true) {
                $link[] = array('text' => $_LANG['bank_list'], 'href' => 'gallery_album.php?act=view&id=' . $old_album_id);
                sys_msg($_LANG['delete_succeed'], 0, $link);
            }
        } else {
            /* 转移相册 */
            if ($album_id > 0) {
                $sql = "UPDATE" . $ecs->table('pic_album') . " SET album_id = '" . $album_id . "' WHERE ru_id='" . $adminru['ru_id'] . "' AND pic_id" . db_create_in($checkboxes);
                if ($db->query($sql) == true) {
                    $link[] = array('text' => $_LANG['bank_list'], 'href' => 'gallery_album.php?act=view&id=' . $old_album_id);
                    sys_msg($_LANG['remove_succeed'], 0, $link);
                }
            } else {
                $link[] = array('text' => $_LANG['bank_list'], 'href' => 'gallery_album.php?act=view&id=' . $old_album_id);
                sys_msg($_LANG['album_fail'], 1, $link);
            }
        }
    } else {
        $link[] = array('text' => $_LANG['bank_list'], 'href' => 'gallery_album.php?act=view&id=' . $old_album_id);
        sys_msg($_LANG['handle_fail'], 1, $link);
    }
}
//转移相册弹窗
elseif($_REQUEST['act'] == 'move_pic'){
    
    $album_id = !empty($_REQUEST['album_id']) ? intval($_REQUEST['album_id']) : 0;
    $inherit = !empty($_REQUEST['inherit']) ? intval($_REQUEST['inherit']) : 0;
    $cat_select = gallery_cat_list(0, 0, false, 0, true);   

    /* 简单处理缩进 */
    foreach ($cat_select as $k => $v) {
            if ($v['level']) {
                    $level = str_repeat('&nbsp;', $v['level'] * 4);
                    $cat_select[$k]['name'] = $level . $v['name'];
            }
    }
    $smarty->assign('cat_select', $cat_select);

    $smarty->assign('form_act', 'submit_pic');
    $smarty->assign('action_type', 'move_pic');
    $smarty->assign('album_id', $album_id);
    $smarty->assign('inherit', $inherit);
    $html = $smarty->fetch("category_move.dwt");

    clear_cache_files();
    make_json_result($html);
}
//转移相册操作
elseif($_REQUEST['act'] == 'submit_pic'){
    $album_id = !empty($_REQUEST['album_id']) ? intval($_REQUEST['album_id']) : 0;//操作相册
    $inherit = !empty($_REQUEST['inherit']) ? intval($_REQUEST['inherit']) : 0;//子相册是否继承
    $target_album_id = !empty($_REQUEST['target_album_id']) ? intval($_REQUEST['target_album_id']) : 0;//目标相册
    $cat_select = $album_id;
    if($inherit == 1){
        $cat_select = getgallery_child($album_id,1);
    }
    
    $sql = "UPDATE" . $ecs->table('pic_album') . " SET album_id = '" . $target_album_id . "' WHERE album_id" . db_create_in($cat_select)." AND ru_id = '".$adminru['ru_id']."'";
    $db->query($sql);
    
    $sql = "SELECT  parent_album_id FROM ".$GLOBALS['ecs']->table('gallery_album').
            "WHERE album_id = '$album_id' AND ru_id = '".$adminru['ru_id']."'";
    $parent_album_id = $db->getOne($sql);
    $link[] = array('text' => $_LANG['bank_list'], 'href' => 'gallery_album.php?act=list&parent_id=' . $parent_album_id);
    sys_msg($_LANG['attradd_succed'], 0, $link);
}
function getgallery_child($album_id = 0,$type = 0){
    $adminru = get_admin_ru_id();
    $child_arr = '';
    if($album_id > 0){
        if($type == 1){
            $child_arr = $album_id;
        }
         $sql = "SELECT  album_id FROM ".$GLOBALS['ecs']->table('gallery_album').
            "WHERE parent_album_id = '$album_id' AND ru_id = '".$adminru['ru_id']."'";
        $child_list = $GLOBALS['db']->getAll($sql);
        if(!empty($child_list)){
            foreach($child_list as $k=>$v){
                 $child_arr .=  ",".$v['album_id'];
                $child_tree = getgallery_child($v['album_id']);
                if($child_tree){
                    $child_arr .=  ",".$child_tree;
                }
           }
        }
    }
    $child_arr = get_del_str_comma($child_arr);
   return $child_arr;
   
}
/*获取相册列表*/
function get_pzd_list($ru_id) {
    $result = get_filter();
    if ($result === false) {
        /* 筛选信息 */
        $filter['album_mame'] = empty($_REQUEST['album_mame']) ? '' : trim($_REQUEST['album_mame']);
        $filter['parent_id']     =  empty($_REQUEST['parent_id'])  ? 0 : intval($_REQUEST['parent_id']);
        /* 拼装筛选 */
        $where = ' WHERE 1 ';
        $where .= " AND ga.parent_album_id = '".$filter['parent_id']."' ";
        if ($filter['album_mame']) {
            $where .= " AND ga.album_mame LIKE '%" . mysql_like_quote($filter['album_mame']) . "%'";
        }
        if ($ru_id > 0) {
            $where .= " AND ga.ru_id = '$ru_id'";
        }

        //管理员查询的权限 -- 店铺查询 end
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('gallery_album') . " AS ga" . $where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);
        $filter = page_and_size($filter);
        /* 获活动数据 */
        $sql = "SELECT album_id,ru_id,album_mame,album_cover,album_desc,sort_order FROM" . $GLOBALS['ecs']->table('gallery_album') . " AS ga" . "  $where ORDER BY sort_order ASC LIMIT " . $filter['start'] . "," . $filter['page_size'];
        $filter['keywords'] = stripslashes($filter['keywords']);
        set_filter($filter, $sql);
    } else {
        $sql = $result['sql'];
        $filter = $result['filter'];
    }
    $row = $GLOBALS['db']->getAll($sql);
    foreach ($row as $k => $v) {
        if ($v['ru_id'] > 0) {
            $row[$k]['shop_name'] = get_shop_name($v['ru_id'], 1);
        } else {
            $row[$k]['shop_name'] = $GLOBALS['_LANG']['self'];
        }
        $row[$k]['gallery_count'] = $GLOBALS['db']->getOne("SELECT COUNT('pic_id') FROM" . $GLOBALS['ecs']->table('pic_album') . " WHERE album_id = '" . $v['album_id'] . "'");
    }
    $arr = array('pzd_list' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
    return $arr;
}

/* 上传文件 */
function upload_article_file($upload,$file = '')
{
  
    if (!make_dir("../" . DATA_DIR . "/gallery_album"))
    {
        /* 创建目录失败 */
        return false;
    }
    $filename = cls_image::random_filename() . substr($upload['name'], strpos($upload['name'], '.'));
    $path     = ROOT_PATH. DATA_DIR . "/gallery_album/". $filename;
    if (move_upload_file($upload['tmp_name'], $path))
    {
        return DATA_DIR . "/gallery_album/" . $filename;
    }
    else
    {
        return false;
    }
}
/*获取相册图片*/
function get_pic_album($album_id = 0) {
    $result = get_filter();
    if ($result === false) {
        $filter['album_id'] = $album_id;
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('pic_album') . " WHERE album_id = '" . $filter['album_id'] . "'";
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);
        $filter = page_and_size($filter);
        /* 获活动数据 */
        $sql = "SELECT pic_id,ru_id,album_id,pic_name,pic_file,pic_size,pic_spec,pic_thumb,pic_image FROM" . $GLOBALS['ecs']->table('pic_album') . " WHERE album_id = '" . $filter['album_id'] . "' ORDER BY pic_id ASC LIMIT " . $filter['start'] . ",".$filter['page_size']."";
        $filter['keywords'] = stripslashes($filter['keywords']);
        set_filter($filter, $sql);
    } else {
        $sql = $result['sql'];
        $filter = $result['filter'];
    }
    $row = $GLOBALS['db']->getAll($sql);
    foreach ($row as $k => $v) {
        $row[$k]['verific_pic'] = 0;//图片是否引用
        if(verific_pic($v['pic_file']) || verific_pic($v['pic_thumb']) || verific_pic($v['pic_image'])){
            $row[$k]['verific_pic'] = 1;
        }
        if(isset($v['pic_file']) && $v['pic_file']){
            $row[$k]['pic_file'] = get_image_path($v['pic_id'], $v['pic_file']);
        }
        
        if (!empty($row[$k]['pic_file']) && (strpos($row[$k]['pic_file'], 'http://') === false && strpos($row[$k]['pic_file'], 'https://') === false)) {
            $row[$k]['pic_file'] = $GLOBALS['ecs']->seller_url() . $row[$k]['pic_file'];
        }
        
        if ($v['pic_size'] > 0) {
            $row[$k]['pic_size'] = number_format($v['pic_size'] / 1024, 2) . 'k';
        }
    }
    
    $arr = array('pzd_list' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
    return $arr;
}
