<?php

/**
 * DSC 管理中心品牌管理
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
require_once(ROOT_PATH . '/' . ADMIN_PATH . '/includes/lib_goods.php');
include_once(ROOT_PATH . '/includes/cls_image.php');
$image = new cls_image($_CFG['bgcolor']);
require(ROOT_PATH . '/includes/cls_json.php');
require(ROOT_PATH . '/includes/lib_visual.php');

$admin_id = get_admin_id();
$adminru = get_admin_ru_id();

/*------------------------------------------------------ */
//-- 仓库弹窗
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'dialog_warehouse') {
    $json = new JSON;
    $result = array('content' => '', 'sgs' => '');
    $temp = !empty($_REQUEST['temp']) ? $_REQUEST['temp'] : '';
    $user_id = !empty($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
    $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
    $smarty->assign("temp", $temp);
    $result['sgs'] = $temp;

    $grade_rank = get_seller_grade_rank($user_id);
    $smarty->assign('grade_rank', $grade_rank);
    $smarty->assign('integral_scale', $_CFG['integral_scale']);

    $warehouse_list = get_warehouse_list();
    $smarty->assign('warehouse_list', $warehouse_list);

    $smarty->assign('user_id', $user_id);
    $smarty->assign('goods_id', $goods_id);

    $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
    die($json->encode($result));
}

//扩展分类
else if ($_REQUEST['act'] == 'extension_category') {
    $json = new JSON;
    $result = array('content' => '', 'sgs' => '');
    $temp = !empty($_REQUEST['temp']) ? $_REQUEST['temp'] : '';
    $smarty->assign("temp", $temp);
    $other_catids = !empty($_REQUEST['other_catids']) ? $_REQUEST['other_catids'] : '';
    $result['sgs'] = $temp;

    $goods_id = empty($_REQUEST['goods_id']) ? 0 : intval($_REQUEST['goods_id']);
    
    $goods = get_admin_goods_info($goods_id, array('user_id'));
    
    /* 商家入驻分类 */
    if($goods['user_id']){
        $seller_shop_cat = seller_shop_cat($goods['user_id']);
    }
    
    /* 取得分类 */
    $level_limit = 3;
    $category_level = array();
    for ($i = 1; $i <= $level_limit; $i++) {
        $category_list = array();
        if ($i == 1) {
            if($goods['user_id']){
                $category_list = get_category_list(0, 0, $seller_shop_cat, $goods['user_id'], $i);
            }else{
                $category_list = get_category_list();
            }
        }
        $smarty->assign('cat_level', $i);
        $smarty->assign('category_list', $category_list);
        $category_level[$i] = $smarty->fetch('templates/library/get_select_category.lbi');
    }
    $smarty->assign('category_level', $category_level);

    /* 取得已存在的扩展分类 */
    if($goods_id > 0 || $other_catids){
        $where = '';
        if($other_catids && $goods_id == 0){
            $where = " ga.cat_id in (".$other_catids.") AND ga.goods_id = 0";
        }elseif($goods_id > 0){
            $where = " ga.goods_id = '$goods_id'";
        }
        if($where){
             $other_cat_list1 = array();
            $sql = "SELECT ga.cat_id FROM " . $ecs->table('goods_cat') . " as ga " .
                    " WHERE" .$where;
            $other_cat1 = $db->getCol($sql);

            $other_category = array();
            foreach ($other_cat1 as $key => $val) {
                $other_category[$key]['cat_id'] = $val;
                $other_category[$key]['cat_name'] = get_every_category($val);
            }
            $smarty->assign('other_category', $other_category);
        }
       
    }
    
    $smarty->assign('goods_id', $goods_id);
    $result['content'] = $GLOBALS['smarty']->fetch('library/extension_category.lbi');
    die($json->encode($result));
}

/*------------------------------------------------------ */
//-- 添加属性图片 //ecmoban模板堂 --zhuo
/*------------------------------------------------------ */
 elseif ($_REQUEST['act'] == 'add_attr_img') {
    $json = new JSON;
    $result = array('error' => 0, 'message' => '','content' => '');

    $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
	$goods_name = !empty($_REQUEST['goods_name']) ? trim($_REQUEST['goods_name']) : '';
    $attr_id = !empty($_REQUEST['attr_id']) ? intval($_REQUEST['attr_id']) : 0;
    $goods_attr_id = !empty($_REQUEST['goods_attr_id']) ? intval($_REQUEST['goods_attr_id']) : 0;
    $goods_attr_name = !empty($_REQUEST['goods_attr_name']) ? trim($_REQUEST['goods_attr_name']) : '';

    $goods_date = array('goods_name');
    $goods_info = get_table_date('goods', "goods_id = '$goods_id'", $goods_date);
    if (!isset($goods_info['goods_name'])) {
        $goods_info['goods_name'] = $goods_name;
    }

    $goods_attr_date = array('attr_img_flie, attr_img_site, attr_checked, attr_gallery_flie');
    $goods_attr_info = get_table_date('goods_attr', "goods_id = '$goods_id' and attr_id = '$attr_id' and goods_attr_id = '$goods_attr_id'", $goods_attr_date);
    
    if ($goods_attr_info) {
        if ($goods_attr_info['attr_img_flie']) {
            $goods_attr_info['attr_img_flie'] = get_image_path($goods_attr_id, $goods_attr_info['attr_img_flie'], true);
        }

        if ($goods_attr_info['attr_img_site']) {
            $goods_attr_info['attr_img_site'] = get_image_path($goods_attr_id, $goods_attr_info['attr_img_site'], true);
        }

        if ($goods_attr_info['attr_gallery_flie']) {
            $goods_attr_info['attr_gallery_flie'] = get_image_path($goods_attr_id, $goods_attr_info['attr_gallery_flie'], true);
        }
    }

    $attr_date = array('attr_name');
    $attr_info = get_table_date('attribute', "attr_id = '$attr_id'", $attr_date);

    $smarty->assign('goods_info', $goods_info);
    $smarty->assign('attr_info', $attr_info);
    $smarty->assign('goods_attr_info', $goods_attr_info);
    $smarty->assign('goods_attr_name', $goods_attr_name);
    $smarty->assign('goods_id', $goods_id);
    $smarty->assign('attr_id', $attr_id);
    $smarty->assign('goods_attr_id', $goods_attr_id);
    $smarty->assign('form_action', 'insert_attr_img');

    $result['content'] = $GLOBALS['smarty']->fetch('library/goods_attr_img_info.lbi');
    die($json->encode($result));
}

/*------------------------------------------------------ */
//-- 添加属性图片插入数据 //ecmoban模板堂 --zhuo
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'insert_attr_img')
{
    include_once(ROOT_PATH . '/includes/lib_goods.php');
    
    $json = new JSON;
    $result = array('error' => 0, 'message' => '','content' => '', 'is_checked' => 0);
    
    $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
    $goods_attr_id = !empty($_REQUEST['goods_attr_id']) ? intval($_REQUEST['goods_attr_id']) : 0;
    $attr_id = !empty($_REQUEST['attr_id']) ? intval($_REQUEST['attr_id']) : 0;
    $goods_attr_name = !empty($_REQUEST['goods_attr_name']) ? $_REQUEST['goods_attr_name'] : '';
    $img_url = !empty($_REQUEST['img_url']) ? $_REQUEST['img_url'] : '';
    
    /* 允许上传的文件类型 */
    $allow_file_types = '|GIF|JPG|JPEG|PNG|';
    
    if(!empty($_FILES['attr_img_flie'])){
        $other['attr_img_flie'] = get_upload_pic('attr_img_flie');
        get_oss_add_file(array($other['attr_img_flie']));
    }else{
        $other['attr_img_flie'] = '';
    }
    
    $goods_attr_date = array('attr_img_flie, attr_img_site');
    $goods_attr_info = get_table_date('goods_attr', "goods_id = '$goods_id' and attr_id = '$attr_id' and goods_attr_id = '$goods_attr_id'", $goods_attr_date);

    if(empty($other['attr_img_flie'])){
            $other['attr_img_flie'] = $goods_attr_info['attr_img_flie'];
    }else{
        @unlink(ROOT_PATH  . $goods_attr_info['attr_img_flie']);
    }

    $other['attr_img_site'] = !empty($_REQUEST['attr_img_site']) ? $_REQUEST['attr_img_site'] : '';
    $other['attr_checked'] = !empty($_REQUEST['attr_checked']) ? intval($_REQUEST['attr_checked']) : 0;
    $other['attr_gallery_flie'] = $img_url;
    
    if($other['attr_checked'] == 1){
        $db->autoExecute($ecs->table('goods_attr'), array('attr_checked' => 0), 'UPDATE', 'attr_id = ' . $attr_id . ' and goods_id = ' . $goods_id);
        $result['is_checked'] = 1;
    }
    
    $db->autoExecute($ecs->table('goods_attr'), $other, 'UPDATE', 'goods_attr_id = ' . $goods_attr_id . ' and attr_id = ' . $attr_id . ' and goods_id = ' . $goods_id);
    
    $result['goods_attr_id'] = $goods_attr_id;
    
    /* 同步前台商品详情价格与商品列表价格一致 start */
    if ($other['attr_checked'] == 1) {
        $goods = get_admin_goods_info($goods_id, array('promote_price', 'promote_start_date', 'promote_end_date', 'user_id', 'model_attr'));
        if ($GLOBALS['_CFG']['add_shop_price'] == 0 && $goods['model_attr'] == 0) {
            $properties = get_goods_properties($goods_id, 0, 0, 0, '', 0, $goods['model_attr'], 0);  // 获得商品的规格和属性  
            $spe = !empty($properties['spe']) ? array_values($properties['spe']) : $properties['spe'];

            $arr = array();
            $goodsAttrId = '';
            if ($spe) {
                foreach ($spe as $key => $val) {
                    if ($val['values']) {
                        if ($val['is_checked']) {
                            $arr[$key]['values'] = get_goods_checked_attr($val['values']);
                        } else {
                            $arr[$key]['values'] = $val['values'][0];
                        }
                    }

                    if ($arr[$key]['values']['id']) {
                        $goodsAttrId .= $arr[$key]['values']['id'] . ",";
                    }
                }

                $goodsAttrId = get_del_str_comma($goodsAttrId);
            }

            $time = gmtime();
            if (!empty($goodsAttrId)) {
                $products = get_warehouse_id_attr_number($goods_id, $goodsAttrId, $goods['user_id'], 0, 0, $goods['model_attr']);

                if ($products) {

                    $products['product_market_price'] = isset($products['product_market_price']) ? $products['product_market_price'] : 0;
                    $products['product_price'] = isset($products['product_price']) ? $products['product_price'] : 0;
                    $products['product_promote_price'] = isset($products['product_promote_price']) ? $products['product_promote_price'] : 0;

                    $promote_price = 0;
                    if ($time >= $goods['promote_start_date'] && $time <= $goods['promote_end_date']) {
                        $promote_price = $goods['promote_price'];
                    }

                    if ($row['promote_price'] > 0) {
                        $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                    } else {
                        $promote_price = 0;
                    }

                    if ($time >= $goods['promote_start_date'] && $time <= $goods['promote_end_date']) {
                        $promote_price = $products['product_promote_price'];
                    }

                    $other = array(
                        'product_table' => $products['product_table'],
                        'product_id' => $products['product_id'],
                        'product_price' => $products['product_price'],
                        'product_promote_price' => $promote_price
                    );

                    $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('goods'), $other, 'UPDATE', "goods_id = '$goods_id'");
                }
            }
        }
    } else {
        if ($goods['model_attr'] > 0) {
            $goods_other = array(
                'product_table' => '',
                'product_id' => 0,
                'product_price' => 0,
                'product_promote_price' => 0
            );
            $db->autoExecute($ecs->table('goods'), $goods_other, 'UPDATE', "goods_id = '$goods_id'");
        }
    }
    /* 同步前台商品详情价格与商品列表价格一致 end */

    clear_cache_files();
    die($json->encode($result));
}

/*------------------------------------------------------ */
//-- 删除属性图片 //ecmoban模板堂 --zhuo
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'drop_attr_img')
{
    $json = new JSON;
    $result = array('error' => 0, 'message' => '','content' => '');
    
    $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
    $goods_attr_id = isset($_REQUEST['goods_attr_id']) ? intval($_REQUEST['goods_attr_id']) : 0;
    $attr_id = isset($_REQUEST['attr_id']) ? intval($_REQUEST['attr_id']) : 0;
    $goods_attr_name = isset($_REQUEST['goods_attr_name']) ? trim($_REQUEST['goods_attr_name']) : '';
    
    $sql = "select attr_img_flie from " .$ecs->table('goods_attr'). " where goods_attr_id = '$goods_attr_id'";
    $attr_img_flie = $db->getOne($sql);
    
    get_oss_del_file(array($attr_img_flie));
    
    @unlink(ROOT_PATH  . $attr_img_flie);
    $other['attr_img_flie'] = '';
    $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('goods_attr'), $other, "UPDATE", "goods_attr_id = '$goods_attr_id'");
    
    $result['goods_attr_id'] = $goods_attr_id;
    
    die($json->encode($result));
}

/*------------------------------------------------------ */
//-- 选择属性图片 --zhuo
/*------------------------------------------------------ */
 elseif ($_REQUEST['act'] == 'choose_attrImg') {
    $json = new JSON;
    $result = array('error' => 0, 'message' => '', 'content' => '');
    
    $admin_id = get_admin_id();

    $goods_id = empty($_REQUEST['goods_id']) ? 0 : intval($_REQUEST['goods_id']);
    $goods_attr_id = empty($_REQUEST['goods_attr_id']) ? 0 : intval($_REQUEST['goods_attr_id']);
    $on_img_id = isset($_REQUEST['img_id']) ? intval($_REQUEST['img_id']) : 0;

    $sql = "SELECT attr_gallery_flie FROM " . $GLOBALS['ecs']->table('goods_attr') . " WHERE goods_attr_id = '$goods_attr_id' AND goods_id = '$goods_id'";
    $attr_gallery_flie = $GLOBALS['db']->getOne($sql);
    
    $thumb_img_id = $_SESSION['thumb_img_id' . $admin_id]; //处理添加商品时相册图片串图问题   by kong
    if (empty($goods_id) && $thumb_img_id) {
        $where = " goods_id = 0 AND img_id " . db_create_in($thumb_img_id) ;
    }else{
        $where = " goods_id = '$goods_id'";
    }

    /* 删除数据 */
    $sql = "SELECT img_id, thumb_url, img_url FROM " . $GLOBALS['ecs']->table('goods_gallery') . " WHERE $where";
    $img_list = $GLOBALS['db']->getAll($sql);

    $str = "<ul>";
    foreach ($img_list as $idx => $row) {
        $row['thumb_url'] = get_image_path(0, $row['thumb_url']); //处理图片地址
        if ($attr_gallery_flie == $row['img_url']) {
            $str .= '<li id="gallery_' . $row['img_id'] . '" onClick="gallery_on(this,' . $row['img_id'] . ',' . $goods_id . ',' . $goods_attr_id . ')" class="on"><img src="' . $row['thumb_url'] . '" width="87" /><i><img src="images/yes.png" width="14" height="12"></i></li>';
        } else {
            $str .= '<li id="gallery_' . $row['img_id'] . '" onClick="gallery_on(this,' . $row['img_id'] . ',' . $goods_id . ',' . $goods_attr_id . ')"><img src="' . $row['thumb_url'] . '" width="87" /><i><img src="images/yes.png" width="14" height="12"></i></li>';
        }
    }
    $str .= "</ul>";

    $result['content'] = $str;

    die($json->encode($result));
}

/*------------------------------------------------------ */
//-- 选择属性图片 --zhuo
/*------------------------------------------------------ */
 elseif ($_REQUEST['act'] == 'insert_gallery_attr') {
    $json = new JSON;
    $result = array('error' => 0, 'message' => '', 'content' => '');

    $goods_id = intval($_REQUEST['goods_id']);
    $goods_attr_id = intval($_REQUEST['goods_attr_id']);
    $gallery_id = intval($_REQUEST['gallery_id']);

    if (!empty($gallery_id)) {
        $sql = "SELECT img_id, img_url FROM " . $ecs->table('goods_gallery') . "WHERE img_id='$gallery_id'";
        $img = $db->getRow($sql);
        $result['img_id'] = $img['img_id'];
        $result['img_url'] = $img['img_url'];

        $sql = "UPDATE " . $ecs->table('goods_attr') . " SET attr_gallery_flie = '" . $img['img_url'] . "' WHERE goods_attr_id = '$goods_attr_id' AND goods_id = '$goods_id'";
        $db->query($sql);
    } else {
        $result['error'] = 1;
    }
    
    $result['goods_attr_id'] = $goods_attr_id;

    die($json->encode($result));
}


/* ------------------------------------------------------ */
//-- 智能权重
/* ------------------------------------------------------ */ 
elseif ($_REQUEST['act'] == 'manual_intervention') {
    $json = new JSON;
    $result = array('error' => 0, 'message' => '', 'content' => '');

    $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
    $goods = get_admin_goods_info($goods_id);
    $smarty->assign('goods', $goods);
    $manual_intervention = get_manual_intervention($goods_id);
    $smarty->assign('manual_intervention', $manual_intervention);

    $result['content'] = $GLOBALS['smarty']->fetch('library/manual_intervention.lbi');

    die($json->encode($result));
}

/* ------------------------------------------------------ */
//-- 添加仓库价格 //ecmoban模板堂 --zhuo
/* ------------------------------------------------------ */ 
elseif ($_REQUEST['act'] == 'add_goods_model_price') {
    $json = new JSON;
    $result = array('error' => 0, 'message' => '','content' => '');
    
    $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
    $warehouse_id = 0;
    $area_id = 0;
    
    $goods = get_goods_model($goods_id);
    $smarty->assign('goods', $goods);
    
    $warehouse_list = get_warehouse_list();
    if($warehouse_list){
        $warehouse_id = $warehouse_list[0]['region_id'];
        $sql = "SELECT region_id FROM " .$ecs->table('region_warehouse'). " WHERE parent_id = '" .$warehouse_list[0]['region_id']. "'";
        $area_id = $db->getOne($sql, true);
    }
    
    $smarty->assign('warehouse_list', $warehouse_list);
    $smarty->assign('warehouse_id', $warehouse_id);
    $smarty->assign('area_id', $area_id);

    $list = get_goods_warehouse_area_list($goods_id, $goods['model_attr'], $warehouse_id);  
   
    $smarty->assign('warehouse_area_list',     $list['list']);
    $smarty->assign('warehouse_area_filter',       $list['filter']);
    $smarty->assign('warehouse_area_record_count', $list['record_count']);
    $smarty->assign('warehouse_area_page_count',   $list['page_count']);
    $smarty->assign('query',            $list['query']);
    $smarty->assign('full_page',    1);

    $result['content'] = $GLOBALS['smarty']->fetch('library/goods_price_list.lbi');
    
    die($json->encode($result));
}

/* ------------------------------------------------------ */
//-- 添加仓库价格
/* ------------------------------------------------------ */ 
elseif ($_REQUEST['act'] == 'goods_wa_query') {
    $json = new JSON;
    $result = array('error' => 0, 'message' => '', 'content' => '');

    $list = get_goods_warehouse_area_list();
    
    $smarty->assign('warehouse_area_list',     $list['list']);
    $smarty->assign('warehouse_area_filter',       $list['filter']);
    $smarty->assign('warehouse_area_record_count', $list['record_count']);
    $smarty->assign('warehouse_area_page_count',   $list['page_count']);
    $smarty->assign('query',            $list['query']);
    
    $goods = get_goods_model($list['filter']['goods_id']);
    $smarty->assign('goods', $goods);
    
    make_json_result($smarty->fetch('goods_price_list.lbi'), '', array('pb_filter' => $list['filter'], 'pb_page_count' => $list['page_count'], 'class' => "goodslistDiv"));
}

/* ------------------------------------------------------ */
//-- 添加仓库属性价格 //ecmoban模板堂 --zhuo
/* ------------------------------------------------------ */ 
elseif ($_REQUEST['act'] == 'add_warehouse_price') {
    $json = new JSON;
    $result = array('error' => 0, 'message' => '','content' => '');
    
    $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
    $attr_id = isset($_REQUEST['attr_id']) && !empty($_REQUEST['attr_id']) ? intval($_REQUEST['attr_id']) : 0;
    $goods_attr_id = isset($_REQUEST['goods_attr_id']) && !empty($_REQUEST['goods_attr_id']) ? intval($_REQUEST['goods_attr_id']) : 0;
    $goods_attr_name = !empty($_REQUEST['goods_attr_name']) ? trim($_REQUEST['goods_attr_name']) : '';

    $action_link = array('href' => 'goods.php?act=edit&goods_id=' . $goods_id . '&extension_code=', 'text' => $_LANG['goods_info']);
    
    if(empty($goods_attr_id)){
        $goods_attr_id = get_goods_attr_nameId($goods_id, $attr_id, $goods_attr_name); //获取商品的属性ID
    }
    
    if(empty($attr_id)){
        $attr_id = get_goods_attr_nameId($goods_id, $goods_attr_id, $goods_attr_name, 'attr_id', 1);
    }
    
    $goods_date = array('goods_name');
    $goods_info = get_table_date('goods', "goods_id = '$goods_id'", $goods_date);

    $attr_date = array('attr_name');
    $attr_info = get_table_date('attribute', "attr_id = '$attr_id'", $attr_date);

    $warehouse_area_list = get_fine_warehouse_all(0, $goods_id, $goods_attr_id);
    
    $smarty->assign('goods_info', $goods_info);
    $smarty->assign('attr_info', $attr_info);
    $smarty->assign('goods_attr_name', $goods_attr_name);
    $smarty->assign('warehouse_area_list', $warehouse_area_list);
    $smarty->assign('goods_id', $goods_id);
    $smarty->assign('attr_id', $attr_id);
    $smarty->assign('goods_attr_id', $goods_attr_id);
    $smarty->assign('form_action', 'insert_warehouse_price');
    $smarty->assign('action_link', $action_link);

    $result['content'] = $GLOBALS['smarty']->fetch('library/goods_warehouse_price_info.lbi');
    
    die($json->encode($result));
}

/* ------------------------------------------------------ */
//-- 添加仓库属性价格 //ecmoban模板堂 --zhuo
/* ------------------------------------------------------ */ 
elseif ($_REQUEST['act'] == 'insert_warehouse_price') {
    $json = new JSON;
    $result = array('error' => 0, 'message' => '', 'content' => '');
    
    $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
    
    if(isset($_REQUEST['goods_attr_id']) && is_array($_REQUEST['goods_attr_id'])){
        $goods_attr_id = !empty($_REQUEST['goods_attr_id']) ? $_REQUEST['goods_attr_id'] : array();
    }else{
        $goods_attr_id = !empty($_REQUEST['goods_attr_id']) ? intval($_REQUEST['goods_attr_id']) : 0;
    }
    
    if(isset($_REQUEST['attr_id']) && is_array($_REQUEST['attr_id'])){
        $attr_id = !empty($_REQUEST['attr_id']) ? $_REQUEST['attr_id'] : array();
    }else{
        $attr_id = !empty($_REQUEST['attr_id']) ? intval($_REQUEST['attr_id']) : 0;
    }
    
    if(isset($_REQUEST['warehouse_name']) && is_array($_REQUEST['warehouse_name'])){
        $warehouse_name = !empty($_REQUEST['warehouse_name']) ? $_REQUEST['warehouse_name'] : array();
    }else{
        $warehouse_name = !empty($_REQUEST['warehouse_name']) ? intval($_REQUEST['warehouse_name']) : 0;
    }
    
    $goods_attr_name = !empty($_REQUEST['goods_attr_name']) ? $_REQUEST['goods_attr_name'] : '';
    
    get_warehouse_area_attr_price_insert($warehouse_name, $goods_id, $goods_attr_id, 'warehouse_attr');
    
    $result['goods_attr_id'] = $goods_attr_id;

    die($json->encode($result));
}    

/*------------------------------------------------------ */
//-- 删除商品勾选属性 //ecmoban模板堂 --zhuo
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'del_goods_attr') {
    $json = new JSON;
    $result = array('error' => 0, 'message' => '', 'content' => '');
    $goods_id = isset($_REQUEST['goods_id']) && !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
    $attr_id = isset($_REQUEST['attr_id']) && !empty($_REQUEST['attr_id']) ? intval($_REQUEST['attr_id']) : 0;
    $goods_attr_id = isset($_REQUEST['goods_attr_id']) && !empty($_REQUEST['goods_attr_id']) ? intval($_REQUEST['goods_attr_id']) : 0;
    $attr_value = isset($_REQUEST['attr_value']) && !empty($_REQUEST['attr_value']) ? addslashes($_REQUEST['attr_value']) : '';
    $goods_model = isset($_REQUEST['model']) && !empty($_REQUEST['model']) ? intval($_REQUEST['model']) : 0;//商品模式
    $region_id = empty($_REQUEST['region_id']) ? 0 : intval($_REQUEST['region_id']); //地区id
    
    if($goods_attr_id){
        $where = "goods_attr_id = '$goods_attr_id'";
    }else{
        $where = "goods_id = '$goods_id' AND attr_value = '$attr_value' AND attr_id = '$attr_id' AND admin_id = '$admin_id'";
    }
    $attr_where = '';
    //判断商品类型
    if ($goods_model == 1) {
        $table = "products_warehouse";
        $attr_where .= " AND warehouse_id = '$region_id' ";
    } elseif ($goods_model == 2) {
        $table = "products_area";
        $attr_where .= " AND area_id = '$region_id' ";
    } else {
        $table = "products";
    }
    
    //删除相关货品
    $sql = "SELECT product_id,goods_attr FROM".$ecs->table($table)."WHERE goods_id = '$goods_id' $attr_where";
    $products = $db->getAll($sql);
    if(!empty($products)){
        foreach($products as $k=>$v)
        {
            if($v['goods_attr']){
                $goods_attr = explode('|', $v['goods_attr']);
                if(in_array($goods_attr_id, $goods_attr)){
                    $sql = "DELETE FROM".$ecs->table("products")."WHERE product_id = '".$v['product_id']."' AND goods_id = '$goods_id'";
                    $db->query($sql);
                }
            }
        }
    }
    $admin_id = get_admin_id();
    //删除零时货品表
    $sql = "SELECT product_id,goods_attr FROM".$ecs->table('products_changelog')."WHERE goods_id = '$goods_id' AND admin_id = '$admin_id' $attr_where";
    $products_changelog = $db->getAll($sql);
    if(!empty($products_changelog)){
        foreach($products_changelog as $k=>$v)
        {
            if($v['goods_attr']){
                $goods_attr = explode('|', $v['goods_attr']);
                if(in_array($goods_attr_id, $goods_attr)){
                    $sql = "DELETE FROM".$ecs->table("products_changelog")."WHERE product_id = '".$v['product_id']."' AND goods_id = '$goods_id'";
                    $db->query($sql);
                }
            }
        }
    }
    $sql = "DELETE FROM " .$GLOBALS['ecs']->table("goods_attr"). " WHERE $where";
    $GLOBALS['db']->query($sql);
    
    $goods_info = get_admin_goods_info($goods_id, array('model_attr'));
    if($goods_info['model_attr'] == 1){
        $table = 'products_warehouse';
    }elseif($goods_info['model_attr'] == 2){
        $table = 'products_area';
    }else{
        $table = 'products';
    }
    
    $where = " AND goods_id = '$goods_id'";
    $ecs->get_del_find_in_set($goods_attr_id, $where, $table, 'goods_attr', '|');
    
    die($json->encode($result));
}

/* ------------------------------------------------------ */
//-- 添加地区属性价格 //ecmoban模板堂 --zhuo
/* ------------------------------------------------------ */ 
elseif ($_REQUEST['act'] == 'add_area_price') {
    $json = new JSON;
    $result = array('error' => 0, 'message' => '', 'content' => '');
    
    $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
    $attr_id = isset($_REQUEST['attr_id']) && !empty($_REQUEST['attr_id']) ? intval($_REQUEST['attr_id']) : 0;
    $goods_attr_id = isset($_REQUEST['goods_attr_id']) && !empty($_REQUEST['goods_attr_id']) ? intval($_REQUEST['goods_attr_id']) : 0;
    $goods_attr_name = !empty($_REQUEST['goods_attr_name']) ? trim($_REQUEST['goods_attr_name']) : '';

    $action_link = array('href' => 'goods.php?act=edit&goods_id=' . $goods_id . '&extension_code=', 'text' => $_LANG['goods_info']);

    if(empty($goods_attr_id)){
        $goods_attr_id = get_goods_attr_nameId($goods_id, $attr_id, $goods_attr_name); //获取商品的属性ID
    }
    
    if(empty($attr_id)){
        $attr_id = get_goods_attr_nameId($goods_id, $goods_attr_id, $goods_attr_name, 'attr_id', 1);
    }

    $goods_date = array('goods_name');
    $goods_info = get_table_date('goods', "goods_id = '$goods_id'", $goods_date);
    
    $attr_date = array('attr_name');
    $attr_info = get_table_date('attribute', "attr_id = '$attr_id'", $attr_date);

    $warehouse_area_list = get_fine_warehouse_area_all(0, $goods_id, $goods_attr_id);

    $smarty->assign('goods_info', $goods_info);
    $smarty->assign('attr_info', $attr_info);
    $smarty->assign('goods_attr_name', $goods_attr_name);
    $smarty->assign('warehouse_area_list', $warehouse_area_list);
    $smarty->assign('goods_id', $goods_id);
    $smarty->assign('attr_id', $attr_id);
    $smarty->assign('goods_attr_id', $goods_attr_id);
    $smarty->assign('form_action', 'insert_area_price');
    $smarty->assign('action_link', $action_link);

    $result['content'] = $GLOBALS['smarty']->fetch('library/goods_area_price_info.lbi');
    die($json->encode($result));
}

/*------------------------------------------------------ */
//-- 添加地区属性价格 //ecmoban模板堂 --zhuo
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'insert_area_price') {
    $json = new JSON;
    $result = array('error' => 0, 'message' => '', 'content' => '');
    
    $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;

    if(isset($_REQUEST['goods_attr_id']) && is_array($_REQUEST['goods_attr_id'])){
        $goods_attr_id = !empty($_REQUEST['goods_attr_id']) ? $_REQUEST['goods_attr_id'] : array();
    }else{
        $goods_attr_id = !empty($_REQUEST['goods_attr_id']) ? intval($_REQUEST['goods_attr_id']) : 0;
    }
    
    if(isset($_REQUEST['attr_id']) && is_array($_REQUEST['attr_id'])){
        $attr_id = !empty($_REQUEST['attr_id']) ? $_REQUEST['attr_id'] : array();
    }else{
        $attr_id = !empty($_REQUEST['attr_id']) ? intval($_REQUEST['attr_id']) : 0;
    }
    
    if(isset($_REQUEST['area_name']) && is_array($_REQUEST['area_name'])){
        $area_name = !empty($_REQUEST['area_name']) ? $_REQUEST['area_name'] : array();
    }else{
        $area_name = !empty($_REQUEST['area_name']) ? intval($_REQUEST['area_name']) : 0;
    }
    
    $goods_attr_name = !empty($_REQUEST['goods_attr_name']) ? $_REQUEST['goods_attr_name'] : '';
    
    get_warehouse_area_attr_price_insert($area_name, $goods_id, $goods_attr_id, 'warehouse_area_attr');

    $result['goods_attr_id'] = $goods_attr_id;

    die($json->encode($result));
}

/* ------------------------------------------------------ */
//-- 添加商品SKU/库存
/* ------------------------------------------------------ */ 
elseif ($_REQUEST['act'] == 'add_sku') {
    $json = new JSON;
    $result = array('error' => 0, 'message' => '', 'content' => '');
    
    $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
    $user_id = !empty($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
    $warehouse_id = 0;
    $area_id = 0;
    $city_id = 0;
    
    $goods = get_goods_model($goods_id);
    
    $warehouse_list = get_warehouse_list();
    if($warehouse_list){
        $warehouse_id = $warehouse_list[0]['region_id'];
        $sql = "SELECT region_id FROM " .$ecs->table('region_warehouse'). " WHERE parent_id = '" .$warehouse_list[0]['region_id']. "'";
        $area_id = $db->getOne($sql, true);
        
        $sql = "SELECT region_id FROM " .$ecs->table('region_warehouse'). " WHERE parent_id = '$area_id'";
        $city_id = $db->getOne($sql, true);
    }
    
    $smarty->assign('warehouse_id', $warehouse_id);
    $smarty->assign('area_id', $area_id);
    $smarty->assign('city_id', $city_id);
    
    $smarty->assign('goods', $goods);
    
    $smarty->assign('warehouse_list', $warehouse_list);
    $smarty->assign('goods_id', $goods_id);
    $smarty->assign('user_id', $user_id);
    
    $smarty->assign('goods_attr_price', $GLOBALS['_CFG']['goods_attr_price']);

    $product_list = get_goods_product_list($goods_id, $goods['model_attr'], $warehouse_id, $area_id, true, $city_id);
    $smarty->assign('product_list',     $product_list['product_list']);
    $smarty->assign('sku_filter',       $product_list['filter']);
    $smarty->assign('sku_record_count', $product_list['record_count']);
    $smarty->assign('sku_page_count',   $product_list['page_count']);
    $smarty->assign('query',            $product_list['query']);
    $smarty->assign('full_page',    1);
    
    $result['content'] = $GLOBALS['smarty']->fetch('library/goods_attr_list.lbi');
    die($json->encode($result));
}

/* ------------------------------------------------------ */
//-- 添加商品SKU/库存
/* ------------------------------------------------------ */ 
elseif ($_REQUEST['act'] == 'sku_query') {
    $json = new JSON;
    $result = array('error' => 0, 'message' => '', 'content' => '');
    
    $smarty->assign('goods_attr_price', $GLOBALS['_CFG']['goods_attr_price']);
    
    $product_list = get_goods_product_list();
    
    $smarty->assign('product_list',     $product_list['product_list']);
    $smarty->assign('sku_filter',       $product_list['filter']);
    $smarty->assign('sku_record_count', $product_list['record_count']);
    $smarty->assign('sku_page_count',   $product_list['page_count']);
    $smarty->assign('query',            $product_list['query']);
    
    $goods = array(
        'goods_id'          => $product_list['filter']['goods_id'],
        'model_attr'        => $product_list['filter']['model'],
        'warehouse_id'      => $product_list['filter']['warehouse_id'],
        'area_id'           => $product_list['filter']['area_id']
    );
    $smarty->assign('goods',            $goods);
    
    make_json_result($smarty->fetch('goods_attr_list.lbi'), '', array('pb_filter' => $product_list['filter'], 'pb_page_count' => $product_list['page_count'], 'class' => "attrlistDiv"));
}

/* ------------------------------------------------------ */
//-- 添加商品SKU/库存
/* ------------------------------------------------------ */ 
elseif ($_REQUEST['act'] == 'add_attr_sku') {
    $json = new JSON;
    $result = array('error' => 0, 'message' => '', 'content' => '');
    
    $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
    $product_id = !empty($_REQUEST['product_id']) ? intval($_REQUEST['product_id']) : 0;
    
    $goods_info = get_admin_goods_info($goods_id, array('goods_id', 'goods_name', 'goods_sn', 'model_attr'));
    
    $smarty->assign('product_id', $product_id);
    
    $editInput = "";
    $method = "";
    $filed = "";
    if ($goods_info['model_attr'] == 1) {
        $filed = ", warehouse_id";
        $method = "insert_warehouse_price";
    } elseif ($goods_info['model_attr'] == 2) {
        $filed = ", area_id";
        $method = "insert_area_price";
    } else {
        $editInput = "edit_attr_price";
    }

    /* 货品库存 */
    $product = get_product_info($product_id, 'product_id, product_number, goods_id, product_sn, goods_attr' . $filed, $goods_info['model_attr'], 1);
    $smarty->assign('goods_info', $goods_info);
    $smarty->assign('product', $product);
    $smarty->assign('editInput', $editInput);
    $smarty->assign('method', $method);
    
    $warehouse_id = isset($product['warehouse_id']) && !empty($product['warehouse_id']) ? $product['warehouse_id'] : 0;
    $area_id = isset($product['area_id']) && !empty($product['area_id']) ? $product['area_id'] : 0;
    
    if(!empty($warehouse_id)){
        $warehouse_area_id = $warehouse_id;
    }elseif(!empty($area_id)){
        $warehouse_area_id = $area_id;
    }
    
    $warehouse = get_area_info($warehouse_area_id, 1);
    $smarty->assign('warehouse_id', $warehouse_id);
    $smarty->assign('area_id', $area_id);
    $smarty->assign('warehouse', $warehouse);
    
    $result['method'] = $method;
    $result['content'] = $GLOBALS['smarty']->fetch('library/goods_list_product.lbi');
    die($json->encode($result));
}

/* ------------------------------------------------------ */
//-- 页面加载动作
/* ------------------------------------------------------ */ 
 elseif ($_REQUEST['act'] == 'getload_url') {

    $smarty->assign("temp", "load_url");

    $smarty->display("library/dialog.lbi");
}

/*------------------------------------------------------ */
//-- 升级弹窗
/*------------------------------------------------------ */
 else if ($_REQUEST['act'] == 'dialog_upgrade') {
    $json = new JSON;
    $result = array('content' => '', 'sgs' => '');
    
    $smarty->assign("cat_belongs", $GLOBALS['_CFG']['cat_belongs']);
    $smarty->assign("brand_belongs", $GLOBALS['_CFG']['brand_belongs']);
    $result['content'] = $GLOBALS['smarty']->fetch('library/upgrade.lbi');
    die($json->encode($result));
}

/*------------------------------------------------------ */
//-- 商品单选复选属性手工录入
/*------------------------------------------------------ */
 else if ($_REQUEST['act'] == 'attr_input_type') {
    $json = new JSON;
    $result = array('content' => '', 'sgs' => '');
    
    $attr_id = isset($_REQUEST['attr_id']) && !empty($_REQUEST['attr_id']) ? intval($_REQUEST['attr_id']) : 0;
    $goods_id = isset($_REQUEST['goods_id']) && !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
    
    $smarty->assign('attr_id',     $attr_id);
    $smarty->assign('goods_id',     $goods_id);
    
    $goods_attr = get_dialog_goods_attr_type($attr_id, $goods_id);
    $smarty->assign('goods_attr',     $goods_attr);

    $result['content'] = $GLOBALS['smarty']->fetch('library/attr_input_type.lbi');
    die($json->encode($result));
}


/*------------------------------------------------------ */
//-- 商品单选复选属性手工录入
/*------------------------------------------------------ */
 else if ($_REQUEST['act'] == 'insert_attr_input') {
    $json = new JSON;
    $result = array('content' => '', 'sgs' => '');
    
    $attr_id = isset($_REQUEST['attr_id']) ? intval($_REQUEST['attr_id']) : 0;
    $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
    $goods_attr_id = isset($_REQUEST['goods_attr_id']) ? $_REQUEST['goods_attr_id'] : array();
    $attr_value_list = isset($_REQUEST['attr_value_list']) ? $_REQUEST['attr_value_list'] : array();
    
        $goods_attr_id = isset($_REQUEST['attr_id_val']) ? explode(',', $_REQUEST['attr_id_val']) : $goods_attr_id;
    $attr_value_list = isset($_REQUEST['value_list_val']) ? explode(',', $_REQUEST['value_list_val']) : $attr_value_list;
    
    
    if($goods_id){
        $where = " AND goods_id = '$goods_id'";
    }else{
        $where = " AND goods_id = 0 AND admin_id = '$admin_id'";
    }
              
    /* 插入、更新、删除数据 */
    foreach ($attr_value_list as $key => $attr_value)
    {
        if($attr_value){
            if($goods_attr_id[$key]){
                $sql = "UPDATE " .$ecs->table('goods_attr'). " SET attr_value = '$attr_value' WHERE goods_attr_id = '" .$goods_attr_id[$key]. "' LIMIT 1";
                $db->query($sql);
            }else{
                
                $sql = "SELECT MAX(attr_sort) AS attr_sort FROM " .$GLOBALS['ecs']->table('goods_attr'). " WHERE attr_id = '$attr_id'" . $where;
                $max_attr_sort = $GLOBALS['db']->getOne($sql);
                
                if($max_attr_sort){
                    $key = $max_attr_sort + 1;
                }else{
                    $key += 1;
                }
                
                $sql = "SELECT goods_attr_id FROM " . $GLOBALS['ecs']->table('goods_attr') . " WHERE attr_value = '$attr_value' AND attr_id = '$attr_id' AND goods_id = '$goods_id'";
                if (!$GLOBALS['db']->getOne($sql, true)) {
                    $sql = "INSERT INTO " . $ecs->table('goods_attr') . " (attr_id, goods_id, attr_value, attr_sort, admin_id)" .
                            "VALUES ('$attr_id', '$goods_id', '$attr_value', '$key', '$admin_id')";
                    $db->query($sql);
                }
            }
        }
    }
    
    $result['attr_id'] = $attr_id;
    $result['goods_id'] = $goods_id;
    
    $goods_attr = get_dialog_goods_attr_type($attr_id, $goods_id);
    $smarty->assign('goods_attr',     $goods_attr);
    $smarty->assign('attr_id',     $attr_id);
    
    $result['content'] = $GLOBALS['smarty']->fetch('library/attr_input_type_list.lbi');
    
    die($json->encode($result));
}

/*------------------------------------------------------ */
//-- 商品单选复选属性手工录入
/*------------------------------------------------------ */
 else if ($_REQUEST['act'] == 'del_input_type') {
    $json = new JSON;
    $result = array('content' => '', 'sgs' => '');
    
    $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
    $attr_id = isset($_REQUEST['attr_id']) ? intval($_REQUEST['attr_id']) : 0;
    $goods_attr_id = isset($_REQUEST['goods_attr_id']) ? intval($_REQUEST['goods_attr_id']) : 0;
    
    $sql = "DELETE FROM " .$ecs->table('goods_attr'). " WHERE goods_attr_id = '$goods_attr_id'";
    $db->query($sql);
    
    $goods_info = get_admin_goods_info($goods_id, array('model_attr'));
    if($goods_info['model_attr'] == 1){
        $table = 'products_warehouse';
    }elseif($goods_info['model_attr'] == 2){
        $table = 'products_area';
    }else{
        $table = 'products';
    }
    
    $where = " AND goods_id = '$goods_id'";
    $ecs->get_del_find_in_set($goods_attr_id, $where, $table, 'goods_attr', '|');
    
    $goods_attr = get_dialog_goods_attr_type($attr_id, $goods_id);
    $smarty->assign('goods_attr',     $goods_attr);
    $smarty->assign('attr_id',     $attr_id);
    
    $result['attr_id'] = $attr_id;
    
    $result['attr_content'] = $GLOBALS['smarty']->fetch('library/attr_input_type_list.lbi');
    die($json->encode($result));
}

/*------------------------------------------------------ */
//-- 删除商品优惠阶梯价格
/*------------------------------------------------------ */
 else if ($_REQUEST['act'] == 'del_volume') {
    $json = new JSON;
    $result = array('content' => '', 'sgs' => '');
    
    $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
    $volume_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    
    $sql = "DELETE FROM " .$ecs->table('volume_price'). " WHERE id = '$volume_id'";
    $db->query($sql);
    
    $volume_price_list = get_volume_price_list($goods_id);
    if(!$volume_price_list){
        $sql = "UPDATE " .$ecs->table('goods'). " SET is_volume = 0 WHERE goods_id = '$goods_id'";
        $db->query($sql);
    }
    
    die($json->encode($result));
}

/*------------------------------------------------------ */
//-- 删除批发商品优惠阶梯价格
/*------------------------------------------------------ */
 else if ($_REQUEST['act'] == 'del_wholesale_volume') {
    $json = new JSON;
    $result = array('content' => '', 'sgs' => '');
    
    $volume_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    
    $sql = "DELETE FROM " .$ecs->table('wholesale_volume_price'). " WHERE id = '$volume_id'";
    $db->query($sql);
   
    die($json->encode($result));
}

/*------------------------------------------------------ */
//-- 删除满立减优惠价格
/*------------------------------------------------------ */
 else if ($_REQUEST['act'] == 'del_cfull') {
    $json = new JSON;
    $result = array('content' => '', 'sgs' => '');
    
    $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
    $volume_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    
    $sql = "DELETE FROM " .$ecs->table('goods_consumption'). " WHERE id = '$volume_id'";
    $db->query($sql);
    
    $consumption_list = get_goods_con_list($goods_id, 'goods_consumption'); //满减订单金额
    if(!$consumption_list){
        $sql = "UPDATE " .$ecs->table('goods'). " SET is_fullcut = 0 WHERE goods_id = '$goods_id'";
        $db->query($sql);
    }
    
    die($json->encode($result));
}

/*------------------------------------------------------ */
//-- 添加商品图片外链地址
/*------------------------------------------------------ */
 else if ($_REQUEST['act'] == 'add_external_url') {
    $json = new JSON;
    $result = array('content' => '', 'sgs' => '', 'error' => 0);
    
    $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;

    $smarty->assign('goods_id',     $goods_id);
    $result['content'] = $GLOBALS['smarty']->fetch('library/external_url_list.lbi');
    
    $result['goods_id'] = $goods_id;
    die($json->encode($result));
}

/*------------------------------------------------------ */
//-- 插入商品图片外链地址
/*------------------------------------------------------ */
 else if ($_REQUEST['act'] == 'insert_external_url') {
    $json = new JSON;
    $result = array('content' => '', 'sgs' => '', 'error' => 0);
	$is_lib = !empty($_REQUEST['is_lib']) ? intval($_REQUEST['is_lib']) : 0;

    /* 商品库和普通商品相册 */
	$table = "goods_gallery";
	if($is_lib){
		$table = "goods_lib_gallery";
	}	

    $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
    $external_url_list = isset($_REQUEST['external_url_list']) ? $_REQUEST['external_url_list'] : array();
    
    /* 是否处理缩略图 */
    $proc_thumb = (isset($GLOBALS['shop_id']) && $GLOBALS['shop_id'] > 0)? false : true;
    
    //当前域名协议
    $http = $GLOBALS['ecs']->http();
        
    if ($external_url_list) {
        
        $sql = "SELECT MAX(img_desc) FROM " .$ecs->table($table). " WHERE goods_id = '$goods_id'";
        $desc = $db->getOne($sql, true);
        
        $admin_id = get_admin_id();
        $admin_temp_dir = "seller";
        $admin_temp_dir = ROOT_PATH . "temp" . '/' . $admin_temp_dir . '/' . "admin_" . $admin_id;
        
        // 如果目标目录不存在，则创建它 
        if (!file_exists($admin_temp_dir)) {
            make_dir($admin_temp_dir);
        }
        
        foreach ($external_url_list as $key => $image_urls) {
            if($image_urls){
                if (!empty($image_urls) && ($image_urls != $GLOBALS['_LANG']['img_file']) && ($image_urls != 'http://') && (strpos($image_urls, 'http://') !== false || strpos($image_urls, 'https://') !== false)) {
                    if(get_http_basename($image_urls, $admin_temp_dir)){
                        $image_url = trim($image_urls);
                        //定义原图路径
                        $down_img = $admin_temp_dir ."/". basename($image_url);
                        
                        $img_wh = $GLOBALS['image']->get_width_to_height($down_img, $GLOBALS['_CFG']['image_width'], $GLOBALS['_CFG']['image_height']);
                        $GLOBALS['_CFG']['image_width'] = isset($img_wh['image_width']) ? $img_wh['image_width'] : $GLOBALS['_CFG']['image_width'];
                        $GLOBALS['_CFG']['image_height'] = isset($img_wh['image_height']) ? $img_wh['image_height'] : $GLOBALS['_CFG']['image_height'];

                        $goods_img = $image->make_thumb(array('img' => $down_img, 'type' => 1), $GLOBALS['_CFG']['image_width'], $GLOBALS['_CFG']['image_height']);
                        
                        // 生成缩略图
                        if ($proc_thumb) {
                            $thumb_url = $GLOBALS['image']->make_thumb(array('img' => $down_img, 'type' => 1), $GLOBALS['_CFG']['thumb_width'], $GLOBALS['_CFG']['thumb_height']);
                            $thumb_url = reformat_image_name('gallery_thumb', $goods_id, $thumb_url, 'thumb');
                        }else{
                            $thumb_url = $GLOBALS['image']->make_thumb(array('img' => $down_img, 'type' => 1));
                            $thumb_url = reformat_image_name('gallery_thumb', $goods_id, $thumb_url, 'thumb');
                        }
                        
                        $img_original = reformat_image_name('gallery', $goods_id, $down_img, 'source');
                        $img_url = reformat_image_name('gallery', $goods_id, $goods_img, 'goods');
                        
                        $desc += 1;

                        $sql = "INSERT INTO " . $GLOBALS['ecs']->table($table) . " (goods_id, img_url, img_desc, thumb_url, img_original) " .
                                "VALUES ('$goods_id', '$img_url', '$desc', '$thumb_url', '$img_original')";
                        $GLOBALS['db']->query($sql);
                        $thumb_img_id[] = $GLOBALS['db']->insert_id();
                        @unlink($down_img);
                    }
                }

                get_oss_add_file(array($img_url, $thumb_url, $img_original));
            }
        }
        
        if(!empty($_SESSION['thumb_img_id'.$_SESSION['admin_id']])){
            $_SESSION['thumb_img_id'.$_SESSION['admin_id']] = array_merge($thumb_img_id , $_SESSION['thumb_img_id'.$_SESSION['admin_id']]);
        }else{
            $_SESSION['thumb_img_id'.$_SESSION['admin_id']] = $thumb_img_id;
        }
    }

    /* 图片列表 */
     $img_id = $_SESSION['thumb_img_id' . $_SESSION['admin_id']];
    $where = '';
    if ($img_id && $goods_id == 0) {
        $where = "AND img_id " . db_create_in($img_id) . "";
    }
    $sql = "SELECT * FROM " . $ecs->table($table) . " WHERE goods_id = '$goods_id' $where  ORDER BY img_desc";
    $img_list = $db->getAll($sql);

    /* 格式化相册图片路径 */
    if (isset($GLOBALS['shop_id']) && ($GLOBALS['shop_id'] > 0)) {
        foreach ($img_list as $key => $gallery_img) {

            $img_list[$key] = $gallery_img;

            //图片显示
            $gallery_img['img_original'] = get_image_path($gallery_img['goods_id'], $gallery_img['img_original'], true);

            $img_list[$key]['img_url'] = $gallery_img['img_original'];

            $gallery_img['thumb_url'] = get_image_path($gallery_img['goods_id'], $gallery_img['thumb_url'], true);
            $img_list[$key]['thumb_url'] = $gallery_img['thumb_url'];
        }
    } else {
        foreach ($img_list as $key => $gallery_img) {

            $img_list[$key] = $gallery_img;

            if (!empty($gallery_img['external_url'])) {
                $img_list[$key]['img_url'] = $gallery_img['external_url'];
                $img_list[$key]['thumb_url'] = $gallery_img['external_url'];
            } else {
                $gallery_img['thumb_url'] = get_image_path($gallery_img['goods_id'], $gallery_img['thumb_url'], true);
                $img_list[$key]['thumb_url'] = $gallery_img['thumb_url'];
            }
        }
    }

    $smarty->assign('img_list', $img_list);
    $smarty->assign('goods_id', $goods_id);
    $result['content'] = $GLOBALS['smarty']->fetch('library/gallery_img.lbi');

    $result['goods_id'] = $goods_id;
    die($json->encode($result));
}

/*------------------------------------------------------ */
//-- 编辑商品图片外链地址
/*------------------------------------------------------ */
 else if ($_REQUEST['act'] == 'insert_gallery_url') {
    $json = new JSON;
    $result = array('content' => '', 'sgs' => '', 'error' => 0);
    
    //当前域名协议
    $http = $GLOBALS['ecs']->http();
    
    $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
    $img_id = isset($_REQUEST['img_id']) ? intval($_REQUEST['img_id']) : 0;
    $external_url = isset($_REQUEST['external_url']) ? addslashes(trim($_REQUEST['external_url'])) : '';
    
    $sql = "SELECT img_id FROM " .$ecs->table('goods_gallery'). " WHERE external_url = '$external_url' AND goods_id = '$goods_id' AND img_id <> $img_id";
    if($db->getOne($sql, true) && !empty($external_url)){
        $result['error'] = 1;
    }else{
        $sql = "UPDATE " .$ecs->table('goods_gallery'). " SET external_url = '$external_url'" . " WHERE img_id = '$img_id'";
        $db->query($sql);
    }
    
    $result['img_id'] = $img_id;
    
    if(!empty($external_url)){
        $result['external_url'] = $external_url;
    }else{
        $sql = "SELECT thumb_url FROM " .$ecs->table('goods_gallery') . " WHERE img_id = '$img_id'";
        $thumb_url = $db->getOne($sql, true);
        
        $thumb_url = get_image_path($img_id, $thumb_url, true);

        $result['external_url'] = $thumb_url;
    }
    
    die($json->encode($result));
}
/*------------------------------------------------------ */
//-- 添加图片
/*------------------------------------------------------ */
else if ($_REQUEST['act'] == 'pic_album') {
    $json = new JSON;
    $result = array('content' => '', 'sgs' => '');
    $album_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    $smarty->assign('album_id', $album_id);
    $smarty->assign('temp', $_REQUEST['act']);

    $album_info = get_goods_gallery_album(2, $album_id,array('suppliers_id,ru_id'));
    $cat_select = gallery_cat_list(0, 0, false, 0, true, $album_info['ru_id'], $album_info['suppliers_id']);

    /* 简单处理缩进 */
    foreach ($cat_select as $k => $v) {
        if ($v['level']) {
            $level = str_repeat('&nbsp;', $v['level'] * 4);
            $cat_select[$k]['name'] = $level . $v['name'];
        }
    }
    $smarty->assign('cat_select', $cat_select);


    $album_mame = get_goods_gallery_album(0, $album_id, array('album_mame'));
    $smarty->assign('album_mame', $album_mame);

    $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
    die($json->encode($result));
}
/*------------------------------------------------------ */
//-- 转移相册
/*------------------------------------------------------ */
else if ($_REQUEST['act'] == 'album_move') {
    $json = new JSON;
    $result = array('content' => '', 'pic_id' => '', 'old_album_id' => '');
    $pic_id = isset($_REQUEST['pic_id'])  ? intval($_REQUEST['pic_id']) : 0;
    $temp = !empty($_REQUEST['act']) ? $_REQUEST['act'] : '';
    $smarty->assign("temp", $temp);

    /*获取全部相册*/
    $cat_select = gallery_cat_list(0, 0, false, 0, true);   	
    
        /* 简单处理缩进 */
        foreach ($cat_select as $k => $v) {
           
            if ($v['level']) {
                $level = str_repeat('&nbsp;', $v['level'] * 4);
                $cat_select[$k]['name'] = $level . $v['name'];
            }
        }
    
        $smarty->assign('cat_select', $cat_select);
    
    /*获取该图片所属相册*/
	$album_id = gallery_pic_album(0, $pic_id, array('album_id'));
    $smarty->assign('album_id',     $album_id);

    $result['pic_id'] = $pic_id;
    $result['old_album_id'] = $album_id;
    $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
    die($json->encode($result));
}

/*------------------------------------------------------ */
//-- 删除属性图片 //ecmoban模板堂 --zhuo
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'update_review_status')
{
    $json = new JSON;
    $result = array('error' => 0, 'message' => '','content' => '');
    
    $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
    $other['review_status'] = isset($_REQUEST['review_status']) ? intval($_REQUEST['review_status']) : 2;
    $other['review_content'] = !empty($_REQUEST['review_content']) ? addslashes(trim($_REQUEST['review_content'])) : '';
    $type = !empty($_REQUEST['type']) ? addslashes(trim($_REQUEST['type'])) : not_audit;
    
    $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('goods'), $other, "UPDATE", "goods_id = '$goods_id'");
    
    $result['goods_id'] = $goods_id;
    $result['type'] = $type;
    
    die($json->encode($result));
}
/*------------------------------------------------------ */
//-- 专题可视化 广告图轮播 //by kong
/*------------------------------------------------------ */
else if($_REQUEST['act'] == 'shop_banner')
{
	$json = new JSON;
	$result = array('content' => '','sgs'=>'','mode'=>'');
	$smarty->assign("temp","shop_banner");
	
	$json = new JSON;
    $result = array('content' => '', 'mode' => '');
    
    $lift = isset($_REQUEST['lift']) ? trim($_REQUEST['lift']) : '';
    $result['hierarchy'] = isset($_REQUEST['hierarchy']) ? intval($_REQUEST['hierarchy']) : 0;
    $inid = isset($_REQUEST['inid']) ? trim($_REQUEST['inid']) : '';//div标识
    $is_vis = isset($_REQUEST['is_vis']) ? intval($_REQUEST['is_vis']) : 0;
    $image_type = isset($_REQUEST['image_type']) ? intval($_REQUEST['image_type']) : 0;
    //可视化入口
    if ($is_vis == 0) {
        $uploadImage = isset($_REQUEST['uploadImage']) ? intval($_REQUEST['uploadImage']) : 0;
        $titleup = isset($_REQUEST['titleup']) ? intval($_REQUEST['titleup']) : 0;
		$result['suffix'] = isset($_REQUEST['suffix']) ? trim($_REQUEST['suffix']) : '';//模板名称
        $result['mode'] = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';
		
        /* 处理数组 */
        $_REQUEST['spec_attr'] = strip_tags(urldecode($_REQUEST['spec_attr']));
        $_REQUEST['spec_attr'] = json_str_iconv($_REQUEST['spec_attr']);
        $_REQUEST['spec_attr'] = !empty($_REQUEST['spec_attr']) ? stripslashes($_REQUEST['spec_attr']) : '';
        if (!empty($_REQUEST['spec_attr'])) {
            $spec_attr = $json->decode($_REQUEST['spec_attr']);
            $spec_attr = object_to_array($spec_attr);
        }
        $defualt = '';
        if ($result['mode'] == 'lunbo') {
            $defualt = 'shade';
        } elseif ($result['mode'] == 'advImg1') {
            $defualt = 'yesSlide';
        }
		$spec_attr['is_title'] = isset($spec_attr['is_title']) ? $spec_attr['is_title'] : 0;
        $spec_attr['slide_type'] = isset($spec_attr['slide_type']) ? $spec_attr['slide_type'] : $defualt;
        $spec_attr['target'] = isset($spec_attr['target']) ? addslashes($spec_attr['target']) : '_blank';
        $pic_src = (isset($spec_attr['pic_src']) && $spec_attr['pic_src'] != ',') ? $spec_attr['pic_src'] : array();
        $link = (!empty($spec_attr['link']) && $spec_attr['link'] != ',') ? explode(',', $spec_attr['link']) : array();
        $sort = (isset($spec_attr['sort']) && $spec_attr['sort'] != ',') ? $spec_attr['sort'] : array();
        $pic_number = isset($_REQUEST['pic_number']) ? intval($_REQUEST['pic_number']) : 0;
        $bg_color = isset($spec_attr['bg_color']) ? $spec_attr['bg_color'] : array();
        $title = (!empty($spec_attr['title']) && $spec_attr['title'] != ',') ? $spec_attr['title'] : array();
        $subtitle = (!empty($spec_attr['subtitle']) && $spec_attr['subtitle'] != ',') ? $spec_attr['subtitle'] : array();
        $result['diff'] = isset($_REQUEST['diff']) ? intval($_REQUEST['diff']) : 0;
        $count = COUNT($pic_src); //数组长度
        /* 合并数组 */
        $arr = array();
        for ($i = 0; $i < $count; $i++) {
            if ($pic_src[$i]) {
                $arr[$i + 1]['pic_src'] = get_image_path($i + 1, $pic_src[$i]);
                if($link[$i]){
                    $arr[$i + 1]['link'] = str_replace(array('＆'),'&',$link[$i]);
                }else{
                    $arr[$i + 1]['link'] = $link[$i];
                }
                $arr[$i + 1]['sort'] = $sort[$i];
                $arr[$i + 1]['title'] = $title[$i];
                $arr[$i + 1]['bg_color'] = $bg_color[$i];
                $arr[$i + 1]['subtitle'] = $subtitle[$i];
            }
        }
        $smarty->assign('banner_list', $arr);
    }

    $cat_select = gallery_cat_list(0, 0, false, 0, true);	
    
    /* 简单处理缩进 */
    $i = 0;
    $default_album = 0;
    foreach ($cat_select as $k => $v) {
        if($v['level'] == 0 && $i == 0){
            $i++;
            $default_album = $v['album_id'];
        }
            if ($v['level']) {
                    $level = str_repeat('&nbsp;', $v['level'] * 4);
                    $cat_select[$k]['name'] = $level . $v['name'];
            }
    }
    if ($default_album > 0) {
        $pic_list = getAlbumList($default_album);

        $smarty->assign('pic_list', $pic_list['list']);
        $smarty->assign('filter', $pic_list['filter']);
        $smarty->assign('album_id', $default_album);
    }
    $smarty->assign('cat_select', $cat_select);
    
    $smarty->assign('is_vis', $is_vis);
    //可视化入口
    if ($is_vis == 0) {
        if($result['mode'] == 'fh-haohuo' || $result['mode'] == 'h-phb'){
            $titleup = 1;
            $smarty->assign('hierarchy', $result['hierarchy']);
            $smarty->assign('lift', $lift);
        }
        $smarty->assign('pic_number', $pic_number);
        $smarty->assign('mode', $result['mode']);
        $smarty->assign('spec_attr', $spec_attr);
        $smarty->assign('uploadImage', $uploadImage);
        $smarty->assign('titleup', $titleup);
		$smarty->assign('suffix', $result['suffix']);
                
        $result['content'] = $GLOBALS['smarty']->fetch('library/shop_banner.lbi');
    } else {
        $smarty->assign('image_type', 0);
        $smarty->assign('log_type', 'image');
        $smarty->assign('image_type', $image_type);
        $smarty->assign('inid', $inid);

        $result['content'] = $GLOBALS['smarty']->fetch('library/album_dialog.lbi');
    }

    die($json->encode($result));
}
/*添加相册*/
elseif($_REQUEST['act'] == 'add_albun_pic'){
    $json = new JSON;
    $result = array('content' => '', 'pic_id' => '', 'old_album_id' => '');
    $temp = !empty($_REQUEST['act']) ? $_REQUEST['act'] : '';
    $smarty->assign("temp", $temp);
    
    $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
    die($json->encode($result));
}
/*--------------------------------------------------------*/
//商品模块弹窗
/*--------------------------------------------------------*/
else if($_REQUEST['act'] == 'goods_info')
{
    $json = new JSON;
    $result = array('content' => '', 'mode' => '');
    /* 处理数组 */
    $search_type = isset($_REQUEST['search_type']) ? trim($_REQUEST['search_type']) : '';
    $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
    $cat_id = !empty($_REQUEST['cat_id']) ? intval($_REQUEST['cat_id']) : 0;
    $goods_type = isset($_REQUEST['goods_type']) ? intval($_REQUEST['goods_type']) : 0;
    $good_number = isset($_REQUEST['good_number']) ? intval($_REQUEST['good_number']) : 0;
	$suffix = isset($_REQUEST['suffix']) ? trim($_REQUEST['suffix']) : '';//模板名称
    $_REQUEST['spec_attr'] = strip_tags(urldecode($_REQUEST['spec_attr']));
    $_REQUEST['spec_attr'] = json_str_iconv($_REQUEST['spec_attr']);
    $_REQUEST['spec_attr'] = !empty($_REQUEST['spec_attr']) ? stripslashes($_REQUEST['spec_attr']) : '';
    if (!empty($_REQUEST['spec_attr'])) {
        $spec_attr = $json->decode(stripslashes($_REQUEST['spec_attr']));
        $spec_attr = object_to_array($spec_attr);
    }
    
    $spec_attr['ru_id'] = (isset($spec_attr['ru_id']) && $spec_attr['ru_id'] != 'undefined') ? intval($spec_attr['ru_id']) : -1;

    $spec_attr['is_title'] = isset($spec_attr['is_title']) ? $spec_attr['is_title'] : 0;
    $spec_attr['itemsLayout'] = isset($spec_attr['itemsLayout']) ? $spec_attr['itemsLayout'] : 'row4';
    $result['mode'] = isset($_REQUEST['mode']) ? addslashes($_REQUEST['mode']) : '';
    $result['diff'] = isset($_REQUEST['diff']) ? intval($_REQUEST['diff']) : 0;
    $lift = isset($_REQUEST['lift']) ? trim($_REQUEST['lift']) : '';
    //取得商品列表
    $spec_attr['goods_ids'] = resetBarnd($spec_attr['goods_ids']); //重置数据
    if ($spec_attr['goods_ids']) {
        $goods_info = explode(',', $spec_attr['goods_ids']);
        foreach ($goods_info as $k => $v) {
            if (!$v) {
                unset($goods_info[$k]);
            }
        }
        if (!empty($goods_info)) {
            $where = " WHERE g.is_on_sale=1 AND g.is_delete=0 AND g.goods_id" . db_create_in($goods_info);

            if ($spec_attr['ru_id'] != '-1') {
                $where .= " AND g.user_id = '" . $spec_attr['ru_id'] . "' ";
            }

            //ecmoban模板堂 --zhuo start
            if ($GLOBALS['_CFG']['review_goods'] == 1) {
                $where .= ' AND g.review_status > 2 ';
            }
            //ecmoban模板堂 --zhuo end
            //  商品筛选  start
            $adminru = get_admin_ru_id(); 
            
            //判断是否是卖场，是筛选商品
            $where .= get_rs_goods_where('g.user_id',$adminru['rs_id']);
            //  商品筛选  end
            $sql = "SELECT g.goods_name,g.goods_id,g.goods_thumb,g.original_img,g.shop_price FROM " . $ecs->table('goods') . " AS g " . $where;
            $goods_list = $db->getAll($sql);

            foreach ($goods_list as $k => $v) {
                $goods_list[$k]['goods_thumb'] = get_image_path($v['goods_id'], $v['goods_thumb']);
                $goods_list[$k]['shop_price'] = price_format($v['shop_price']);
            }

            $smarty->assign('goods_list', $goods_list);
            $smarty->assign('goods_count', count($goods_list));
        }
    }
    
    if($goods_id && $spec_attr['ru_id'] == -1){
        $goods_info = get_admin_goods_info($goods_id, array('user_id'));
        $adminru['ru_id'] = $goods_info['user_id'];
    }elseif($spec_attr['ru_id'] > -1){
        $adminru['ru_id'] = $spec_attr['ru_id'];
    }

    /* 取得分类列表 */
    //获取下拉列表 by wu start
    set_default_filter(0, $cat_id, $adminru['ru_id']); //设置默认筛选
    
    $smarty->assign('parent_category', get_every_category($cat_id)); //上级分类导航
    $smarty->assign('select_category_html', $select_category_html);
    $smarty->assign('arr', $spec_attr);
    $smarty->assign("temp", "goods_info");
    $smarty->assign("goods_type", $goods_type);
    $smarty->assign("mode", $result['mode']);
    $smarty->assign("cat_id", $cat_id);
    $smarty->assign("lift", $lift);
    $smarty->assign("good_number", $good_number);
    $smarty->assign("search_type", $search_type);
    $smarty->assign("goods_id", $goods_id);
	$smarty->assign("suffix", $suffix);
    $result['content'] = $GLOBALS['smarty']->fetch('library/shop_banner.lbi');
    die($json->encode($result));
}
/*--------------------------------------------------------*/
//自定义模块弹窗
/*--------------------------------------------------------*/
elseif($_REQUEST['act'] == 'custom')
{
    $json = new JSON;
    $result = array('content' => '','mode'=>'');
    $custom_content = isset($_REQUEST['custom_content']) ? unescape($_REQUEST['custom_content']) : '';
    $custom_content = !empty($custom_content) ? stripslashes($custom_content) : '';
	$suffix = isset($_REQUEST['suffix']) ? trim($_REQUEST['suffix']) : '';//模板名称
    $result['mode'] = isset($_REQUEST['mode']) ? addslashes($_REQUEST['mode']) : '';
    $result['diff'] = isset($_REQUEST['diff']) ? intval($_REQUEST['diff']) : 0;
    $lift = isset($_REQUEST['lift']) ? trim($_REQUEST['lift']) : '';
	
	//OSS文件存储ecmoban模板堂 --zhuo start
    if ($GLOBALS['_CFG']['open_oss'] == 1) {
        $bucket_info = get_bucket_info();
        if ($custom_content) {
            $desc_preg = get_goods_desc_images_preg($bucket_info['endpoint'], $custom_content);
            $custom_content = $desc_preg['goods_desc'];
        }
    }
    //OSS文件存储ecmoban模板堂 --zhuo end

    /* 创建 百度编辑器 wang 商家入驻 */
    $FCKeditor = create_ueditor_editor('custom_content',$custom_content,486,1);
    $smarty->assign('FCKeditor',   $FCKeditor);
    $smarty->assign("temp",$_REQUEST['act']);
    $smarty->assign("lift",$lift);
	$smarty->assign("suffix",$suffix);
    $result['content'] = $GLOBALS['smarty']->fetch('library/shop_banner.lbi');
    die($json->encode($result));
}

/*--------------------------------------------------------*/
//首页广告位
/*--------------------------------------------------------*/
elseif($_REQUEST['act'] == 'home_adv'){
    require(ROOT_PATH . '/includes/lib_goods.php');
    $json = new JSON;
    $result = array('content' => '','mode'=>'');
    $spec_attr['needColor'] = '';
	$result['suffix'] = isset($_REQUEST['suffix']) ? trim($_REQUEST['suffix']) : '';//模板名称
    $result['diff'] = isset($_REQUEST['diff']) ? intval($_REQUEST['diff']) : 0;
    $result['hierarchy'] = isset($_REQUEST['hierarchy']) ? intval($_REQUEST['hierarchy']) : 0;
    $result['activity_dialog'] = isset($_REQUEST['activity_dialog']) ? intval($_REQUEST['activity_dialog']) : 0;
    $lift = !empty($_REQUEST['lift']) ? trim($_REQUEST['lift']) : '';
    $masterTitle = (!empty($_REQUEST['masterTitle']) && $_REQUEST['masterTitle'] != 'null')  ?  trim(unescape($_REQUEST['masterTitle'])) : '';
    $_REQUEST['spec_attr'] = !empty($_REQUEST['spec_attr']) ? stripslashes($_REQUEST['spec_attr']) : '';
    $_REQUEST['spec_attr']=urldecode($_REQUEST['spec_attr']);
    $_REQUEST['spec_attr'] = json_str_iconv($_REQUEST['spec_attr']);
    if(!empty($_REQUEST['spec_attr'])){
        $spec_attr = json_decode($_REQUEST['spec_attr'],true);
    }
    $result['mode'] = isset($_REQUEST['mode']) ? trim($_REQUEST['mode']) : '';
    $needColor = '';
    //处理标题颜色
    $needColor = $spec_attr['needColor'];
    unset($spec_attr['needColor']);
    
    //获取品牌列表
    if ($result['mode'] == 'h-brand') {
        //重置选择的品牌
        $spec_attr['brand_ids'] = resetBarnd($spec_attr['brand_ids'],'brand','brand_id');
        
        $brand = getBrandList($spec_attr['brand_ids']);
        $smarty->assign('filter', $brand['filter']);
        $smarty->assign('recommend_brands',$brand['list']);
    }elseif($result['mode'] == 'h-promo'){
        $spec_attr['goods_ids'] = resetBarnd($spec_attr['goods_ids']);//重置选择商品
        
        $time = gmtime();
        $where = "WHERE g.is_on_sale=1 AND g.is_delete=0 ";
        if($GLOBALS['_CFG']['review_goods'] == 1){
            $where .= ' AND g.review_status > 2 ';
        }
	//  start 获取卖场筛选条件
        $adminru = get_admin_ru_id();
        if($adminru['rs_id'] > 0){
            $where .= get_rs_goods_where('g.user_id',$adminru['rs_id']);
        }
        // end
        $where .= " AND promote_start_date <= '$time' AND promote_end_date >=  '$time' AND promote_price > 0";
        $list = getGoodslist($where);
        $goods_list = $list['list'];
        
        $goods_ids =  explode(',', $spec_attr['goods_ids']);
        if(!empty($goods_list)){
            foreach($goods_list as $key=>$val){
                
                $goods_list[$key]['goods_thumb'] = get_image_path($val['goods_id'], $val['goods_thumb']);
            
                if ($val['promote_price'] > 0 && $time >= $val['promote_start_date'] && $time <= $val['promote_end_date']) {
                    $goods_list[$key]['promote_price'] =price_format(bargain_price($val['promote_price'], $val['promote_start_date'], $val['promote_end_date']));
                }else{
                    $goods_list[$key]['promote_price'] = '';
                }
                $goods_list[$key]['shop_price'] = price_format($val['shop_price']);
                if(!empty($goods_ids)){
                    if ($val['goods_id'] > 0 && in_array($val['goods_id'],$goods_ids )) {
                        $goods_list[$key]['is_selected'] = 1;
                    }
                }
                
            }
        }
        $smarty->assign('filter',     $list['filter']);
        $smarty->assign('goods_list',     $goods_list);
        $smarty->assign('goods_count',     count($goods_list));
    }elseif($result['mode'] == 'h-sepmodule'){
        $spec_attr['goods_ids'] = resetBarnd($spec_attr['goods_ids']);//重置选择商品
        $where = " WHERE 1";
        
        //  start 获取卖场筛选条件
        $adminru = get_admin_ru_id();
        if($adminru['rs_id'] > 0){
            $where .= get_rs_goods_where('g.user_id',$adminru['rs_id']);
        }
        // end
        
        $search = '';
        $leftjoin = '';
        $time = gmtime();
        
        if(!empty($spec_attr['PromotionType'])){
            if($spec_attr['PromotionType'] == 'exchange')
            {
                $sql = "SELECT goods_id FROM".$ecs->table('exchange_goods')."WHERE goods_id" . db_create_in($spec_attr['goods_ids']) . " AND review_status = 3  AND is_exchange = 1";
                $spec_attr['goods_ids'] = $db->getCol($sql);
                
                $search = ', ga.exchange_integral';
                $leftjoin = " LEFT JOIN ".$GLOBALS['ecs']->table('exchange_goods')." AS ga ON ga.goods_id=g.goods_id ";
                
                $where .= " AND ga.review_status = 3  AND ga.is_exchange = 1 AND g.is_delete = 0";
            }
            elseif($spec_attr['PromotionType'] == 'presale')
            {
                $sql = "SELECT goods_id FROM".$ecs->table('presale_activity')."WHERE goods_id" . db_create_in($spec_attr['goods_ids']) . " AND start_time <= '$time' AND end_time >= '$time'  AND is_finished =0";
                $spec_attr['goods_ids'] = $db->getCol($sql);
                
                $search = ',ga.act_id, ga.act_name, ga.end_time, ga.start_time ';
                $leftjoin = " LEFT JOIN ".$GLOBALS['ecs']->table('presale_activity')." AS ga ON ga.goods_id=g.goods_id ";
                $where .= " AND ga.review_status = 3 AND ga.start_time <= '$time' AND ga.end_time >= '$time' AND g.goods_id <> '' AND ga.is_finished =0"; 
            }
            elseif($spec_attr['PromotionType'] == 'is_new'){
                $spec_attr['goods_ids'] = resetBarnd($spec_attr['goods_ids']);//重置选择商品
                if($spec_attr['goods_ids']){
                    $spec_attr['goods_ids'] = explode(',', $spec_attr['goods_ids']);
                }
                $where .= " AND g.is_new = 1 AND  g.is_on_sale=1 AND g.is_delete=0";
            }
            elseif($spec_attr['PromotionType'] == 'is_best'){
                $spec_attr['goods_ids'] = resetBarnd($spec_attr['goods_ids']);//重置选择商品
                if($spec_attr['goods_ids']){
                    $spec_attr['goods_ids'] = explode(',', $spec_attr['goods_ids']);
                }
                $where .= " AND g.is_best = 1 AND  g.is_on_sale=1 AND g.is_delete=0 ";
            }
            elseif($spec_attr['PromotionType'] == 'is_hot'){
                $spec_attr['goods_ids'] = resetBarnd($spec_attr['goods_ids']);//重置选择商品、
                if($spec_attr['goods_ids']){
                    $spec_attr['goods_ids'] = explode(',', $spec_attr['goods_ids']);
                }
                $where .= " AND g.is_hot = 1  AND  g.is_on_sale=1 AND g.is_delete=0";
            }
            else
            {
                if($spec_attr['PromotionType'] == 'snatch'){
                    $act_type = GAT_SNATCH;
                }elseif($spec_attr['PromotionType'] == 'auction'){
                    $act_type = GAT_AUCTION;
                }elseif($spec_attr['PromotionType'] == 'group_buy'){
                    $act_type = GAT_GROUP_BUY;
                }
                 $sql = "SELECT goods_id FROM".$ecs->table('goods_activity')."WHERE goods_id" . db_create_in($spec_attr['goods_ids']) . " AND start_time <= '$time' AND end_time >= '$time'  AND is_finished =0 AND act_type=" . $act_type;
                $spec_attr['goods_ids'] = $db->getCol($sql);
                $search = ',ga.act_id, ga.act_name, ga.end_time, ga.start_time, ga.ext_info';
                $leftjoin = " LEFT JOIN ".$GLOBALS['ecs']->table('goods_activity')." AS ga ON ga.goods_id=g.goods_id ";
                 
                $where .= " AND ga.review_status = 3 AND ga.start_time <= '$time' AND ga.end_time >= '$time' AND g.goods_id <> '' AND ga.is_finished =0 AND ga.act_type=" . $act_type; 
            }
			if(!$spec_attr['goods_ids']){
				$spec_attr['goods_ids'] = array();
			}
			$spec_attr['goods_ids'] = implode(',', $spec_attr['goods_ids']);	
            $where .= " AND g.goods_id".db_create_in($spec_attr['goods_ids']);
            $goods_ids =  explode(',', $spec_attr['goods_ids']);
            
            $sql = "SELECT g.promote_start_date, g.promote_end_date, g.promote_price, g.goods_name, g.goods_id, g.goods_thumb, g.shop_price, g.market_price, g.original_img $search FROM " . 
                $GLOBALS['ecs']->table('goods') . " AS g " .$leftjoin. $where ;
            $goods_list = $db->getAll($sql);
            if (!empty($goods_list)) {
                foreach ($goods_list as $key => $val) {
                    
                    $goods_list[$key]['goods_thumb'] = get_image_path($val['goods_id'], $val['goods_thumb']);
                    
                     $goods_list[$key]['is_selected'] = 1;
                    if ($val['promote_price'] > 0 && $time >= $val['promote_start_date'] && $time <= $val['promote_end_date']) {
                        $goods_list[$key]['promote_price'] =price_format(bargain_price($val['promote_price'], $val['promote_start_date'], $val['promote_end_date']));
                    }else{
                        $goods_list[$key]['promote_price'] = '';
                    }
                    $goods_list[$key]['shop_price'] = price_format($val['shop_price']);
                    if (!empty($spec_attr['PromotionType']) && $spec_attr['PromotionType'] != 'exchange') {
                        $goods_list[$key]['goods_name'] = !empty($val['act_name']) ? $val['act_name'] : $val['goods_name'];
                        if ($spec_attr['PromotionType'] == 'auction') {
                            $ext_info = unserialize($val['ext_info']);
                            $auction = array_merge($val, $ext_info);
                            $goods_list[$key]['promote_price'] = price_format($auction['start_price']);
                        } elseif ($spec_attr['PromotionType'] == 'group_buy') {
                            $ext_info = unserialize($val['ext_info']);
                            $group_buy = array_merge($val, $ext_info);
                            $goods_list[$key]['promote_price'] = price_format($group_buy['price_ladder'][0]['price']);
                        }
                    }
                    if($spec_attr['PromotionType'] == 'exchange'){
                        $goods_list[$key]['url'] =   build_uri('exchange_goods', array('gid' => $val['goods_id']), $val['goods_name']);
                        $goods_list[$key]['exchange_integral'] = $_LANG['label_integral'] . $val['exchange_integral'];
                    }
                    $goods_list[$key]['goods_thumb'] = get_image_path($val['goods_id'], $val['goods_thumb']);

                    if (!empty($goods_ids)) {
                        if ($val['goods_id'] > 0 && in_array($val['goods_id'], $goods_ids)) {
                            $goods_list[$key]['is_selected'] = 1;
                        }
                    }
                }
            }
            $smarty->assign('goods_list',     $goods_list);
            $smarty->assign('goods_count',     count($goods_list));
        }
        $smarty->assign('activity_dialog',$result['activity_dialog']);
    }elseif($result['mode'] == 'h-seckill'){
        //验证存储的秒杀id
        
        if(!empty($spec_attr['goods_ids'])){
            foreach($spec_attr['goods_ids'] as $k=>$v){
                $spec_attr['goods_ids'][$k] = resetBarnd($v,'seckill');
            }
        }
        $time_bucket = !empty($spec_attr['time_bucket']) ? intval($spec_attr['time_bucket']) : 0;
        $now = gmtime();
        //获取秒杀时间段
        $sql = " SELECT id,title, begin_time, end_time FROM " . $GLOBALS['ecs']->table('seckill_time_bucket') . " ORDER BY begin_time ASC ";
        $stb = $GLOBALS['db']->getAll($sql);
        if ($stb) {
            foreach ($stb as $k => $v) {
                $v['local_end_time'] = local_strtotime($v['end_time']);
                $arr[$k]['id'] = $v['id'];
                $arr[$k]['title'] = $v['title'];
                $arr[$k]['status'] = false;
                $arr[$k]['is_end'] = false;
                $arr[$k]['soon'] = false;
                $begin_time = local_strtotime($v['begin_time']);
                $end_time = local_strtotime($v['end_time']);
                
                if ($begin_time < $now && $end_time > $now) {
                    $arr[$k]['status'] = true;
                }
                if ($end_time < $now) {
                    $arr[$k]['is_end'] = true;
                }
                $arr[$k]['goods_ids'] = $spec_attr['goods_ids'][$v['id']];
            }
        }
        $smarty->assign('seckill_time_bucket',$arr);
        $smarty->assign('time_bucket',$time_bucket);
    }
     set_default_filter(0, $cat_id); //设置默认筛选
    $smarty->assign('parent_category', get_every_category($cat_id)); //上级分类导航
    $smarty->assign('select_category_html', $select_category_html);
    $smarty->assign('temp',$result['mode']);
    $smarty->assign('lift',$lift);
    $smarty->assign('needColor',$needColor);
    $smarty->assign('spec_attr',$spec_attr );
    $smarty->assign('hierarchy',$result['hierarchy']);
    $smarty->assign('masterTitle',$masterTitle );
	$smarty->assign('suffix',$result['suffix']);
    $result['content'] = $GLOBALS['smarty']->fetch('library/shop_banner.lbi');
    die($json->encode($result)); 
}
//异步促销商品
elseif($_REQUEST['act'] == 'changedpromotegoods'){
    require(ROOT_PATH . '/includes/lib_goods.php');
    $json = new JSON;
    $result = array('content' => '','mode'=>'');
    //传值
    $cat_id = !empty($_REQUEST['cat_id']) ? intval($_REQUEST['cat_id']) : 0;
    $keyword = !empty($_REQUEST['keyword'])  ? trim($_REQUEST['keyword'])  : '';
    $goods_ids = !empty($_REQUEST['goods_ids'])  ? explode(',', $_REQUEST['goods_ids'])  :  array();
    $PromotionType = !empty($_REQUEST['PromotionType'])  ? trim($_REQUEST['PromotionType'])  : '';
    $recommend = !empty($_REQUEST['recommend'])  ? intval($_REQUEST['recommend'])  : 0;
	$brand_id = isset($_REQUEST['brand_id'])  ? intval($_REQUEST['brand_id'])  : 0;
    $type = isset($_REQUEST['type'])  ? intval($_REQUEST['type'])  :  0;
    $time_bucket = isset($_REQUEST['time_bucket'])  ? intval($_REQUEST['time_bucket'])  :  0;//秒杀时间段id
    $temp = !empty($_REQUEST['temp'])  ? trim($_REQUEST['temp'])  : '';
    $activity_dialog = !empty($_REQUEST['activity_dialog'])  ? intval($_REQUEST['activity_dialog'])  : 0;
    $where = " WHERE 1";
    $search = '';
    $leftjoin = '';
    $time = gmtime();
    $spec_attr['goods_ids'] = resetBarnd($spec_attr['goods_ids']);//重置选择商品
    //拼接条件
    if(!empty($PromotionType)){
        $act_type = '';
        if($PromotionType == 'exchange'){
            $search = ', ga.exchange_integral';
             $leftjoin = " LEFT JOIN ".$GLOBALS['ecs']->table('exchange_goods')." AS ga ON ga.goods_id=g.goods_id ";
            if($keyword)
            {
                $where .= " AND g.goods_name  LIKE '%$keyword%'";
            }
            $where .= " AND ga.is_exchange = 1 AND g.is_delete = 0";
        }elseif($PromotionType == 'presale'){
             $search = ', ga.act_name, ga.end_time, ga.start_time ';
            $leftjoin = " LEFT JOIN ".$GLOBALS['ecs']->table('presale_activity')." AS ga ON ga.goods_id=g.goods_id ";
             if($keyword)
            {
                $where .= " AND (ga.act_name LIKE '%$keywords%' OR g.goods_name LIKE '%$keywords%') ";
            }
            $where .= " AND ga.review_status = 3 AND ga.start_time <= '$time' AND ga.end_time >= '$time' AND g.goods_id <> '' AND ga.is_finished =0"; 
        }elseif($PromotionType == 'is_new'){
            $where .= " AND g.is_new = 1 AND  g.is_on_sale=1 AND g.is_delete=0";
        }
        elseif($PromotionType == 'is_best'){
            $where .= " AND g.is_best = 1 AND  g.is_on_sale=1 AND g.is_delete=0 ";
        }
        elseif($PromotionType == 'is_hot'){
            $where .= " AND g.is_hot = 1  AND  g.is_on_sale=1 AND g.is_delete=0";
        }
        else{
            if($PromotionType == 'snatch'){
            $act_type = GAT_SNATCH;
            }elseif($PromotionType == 'auction'){
                $act_type = GAT_AUCTION;
            }elseif($PromotionType == 'group_buy'){
                $act_type = GAT_GROUP_BUY;
            }
            $search = ', ga.act_name, ga.end_time, ga.start_time, ga.ext_info';
            $leftjoin = " LEFT JOIN ".$GLOBALS['ecs']->table('goods_activity')." AS ga ON ga.goods_id=g.goods_id ";
             if($keyword)
            {
                $where .= " AND (ga.act_name LIKE '%$keywords%' OR g.goods_name LIKE '%$keywords%') ";
            }
            $where .= " AND ga.review_status = 3 AND ga.start_time <= '$time' AND ga.end_time >= '$time' AND g.goods_id <> '' AND ga.is_finished =0 AND ga.act_type=" . $act_type; 
        }
        
    }elseif($temp == 'h-seckill'){
        $leftjoin = " LEFT JOIN ".$GLOBALS['ecs']->table('seckill_goods')." AS sg ON sg.goods_id=g.goods_id ";
        $leftjoin .= " LEFT JOIN ".$GLOBALS['ecs']->table('seckill')." AS s ON s.sec_id=sg.sec_id ";
        
         $where .= " AND g.is_on_sale=1 AND g.is_delete=0 ";
        if($GLOBALS['_CFG']['review_goods'] == 1){
            $where .= ' AND g.review_status > 2 ';
        }
        $search = ', sg.sec_price,sg.id';
        $where .= " AND s.review_status = 3 AND s.is_putaway = 1 AND s.begin_time < '$time' AND  s.acti_time > '$time' AND sg.tb_id = '$time_bucket'";
        if($type == 0){
            $where .= " AND sg.id".db_create_in($goods_ids);
        }
    }else{
        $where .= " AND g.is_on_sale=1 AND g.is_delete=0 ";
        if($GLOBALS['_CFG']['review_goods'] == 1){
            $where .= ' AND g.review_status > 2 ';
        }
        $where .= " AND promote_start_date <= '$time' AND promote_end_date >=  '$time' AND promote_price > 0";
        if($keyword)
        {
            $where .= " AND g.goods_name  LIKE '%$keyword%'";
        }
    }
    //  商品筛选  start
    $adminru = get_admin_ru_id();
    //判断是否是卖场，是筛选商品
    if($adminru['rs_id'] > 0){
         $where .= get_rs_goods_where('g.user_id',$adminru['rs_id']);
    }
    //  商品筛选  end
    if($cat_id > 0){
        $where .= " AND ".get_children($cat_id); 
    }
    
    if($type == 0 && $temp != 'h-seckill'){
        $where .= " AND g.goods_id".db_create_in($goods_ids);
    }
    if($brand_id > 0){
        $where .= " AND g.brand_id = '$brand_id'";
    }
    $list = getGoodslist($where,'',$search,$leftjoin);
    $goods_list = $list['list'];
    $filter = $list['filter'];
    $filter['temp'] = $temp;
    $filter['time_bucket'] = $time_bucket;
    $filter['cat_id'] = $cat_id;
    $filter['keyword'] = $keyword;
    $filter['PromotionType'] = $PromotionType;
    
    if(!empty($goods_list)){
        foreach($goods_list as $key=>$val){
            if($temp == 'h-seckill'){
                $goods_list[$key]['promote_price'] = price_format($val['sec_price']);//秒杀价格
                $goods_list[$key]['goods_id'] = $val['id'];//秒杀价格
                $val['goods_id'] = $val['id'];
            }else{
                if ($val['promote_price'] > 0 && $time >= $val['promote_start_date'] && $time <= $val['promote_end_date']) {
                    $goods_list[$key]['promote_price'] =price_format(bargain_price($val['promote_price'], $val['promote_start_date'], $val['promote_end_date']));
                }else{
                    $goods_list[$key]['promote_price'] = '';
                }
                $goods_list[$key]['shop_price'] = price_format($val['shop_price']);
            }
            
            if(!empty($PromotionType) && $PromotionType != 'exchange'){
                $goods_list[$key]['goods_name'] = !empty($val['act_name']) ? $val['act_name'] : $val['goods_name'];
                if($PromotionType == 'auction'){
                    $ext_info = unserialize($val['ext_info']);
                    $auction = array_merge($val, $ext_info);
                    $goods_list[$key]['promote_price'] = price_format($auction['start_price']);
                }elseif($PromotionType == 'group_buy'){
                    $ext_info = unserialize($val['ext_info']);
                    $group_buy = array_merge($val, $ext_info);
                    $goods_list[$key]['promote_price'] = price_format($group_buy['price_ladder'][0]['price']);
                }
            }
            if($PromotionType == 'exchange'){
                $goods_list[$key]['exchange_integral'] = $_LANG['label_integral'] . $val['exchange_integral'];
            }
            $goods_list[$key]['goods_thumb'] = get_image_path($val['goods_id'], $val['goods_thumb']);
            
            if(!empty($goods_ids)){
                if ($val['goods_id'] > 0 && in_array($val['goods_id'], $goods_ids)) {
                    $goods_list[$key]['is_selected'] = 1;
                }
            }
        }
    }
    $smarty->assign('goods_count',     count($goods_list));
    $smarty->assign('goods_list',     $goods_list);
    $smarty->assign('filter',     $filter);
    $smarty->assign('PromotionType',     $PromotionType);
    $smarty->assign('action',     "changedpromotegoods");
    $smarty->assign('url',     "dialog.php");
    $smarty->assign('temp',"goods_list");
    $smarty->assign('recommend',$recommend);
    $smarty->assign('activity_dialog',$activity_dialog);
    
    $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
    die($json->encode($result)); 
}

//品牌分页回调
elseif($_REQUEST['act'] == 'brand_query'){
    $json = new JSON;
    $result = array('content' => '','mode'=>'');
    $brand_ids = !empty($_REQUEST['brand_ids']) ? trim($_REQUEST['brand_ids']) : '';
    $brand = getBrandList($brand_ids);
    $smarty->assign('filter', $brand['filter']);
    $smarty->assign('recommend_brands',$brand['list']);
    $smarty->assign('temp','brand_query');
    $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
    die($json->encode($result)); 
}

/*--------------------------------------------------------*/
//首页楼层
/*--------------------------------------------------------*/
elseif($_REQUEST['act'] == 'homeFloor'){
    $json = new JSON;
    $result = array('content' => '','mode'=>'');
    $result['act'] = $_REQUEST['act'];
    $lift = isset($_REQUEST['lift']) ? trim($_REQUEST['lift']) : '';
	$suffix = isset($_REQUEST['suffix']) ? trim($_REQUEST['suffix']) : '';//模板名称
	$result['hierarchy'] = isset($_REQUEST['hierarchy']) ? trim($_REQUEST['hierarchy']) : '';
    $result['diff'] = isset($_REQUEST['diff']) ? intval($_REQUEST['diff']) : 0;
    $result['mode'] = isset($_REQUEST['mode']) ? trim($_REQUEST['mode']) : '';
    $_REQUEST['spec_attr'] = !empty($_REQUEST['spec_attr']) ? stripslashes($_REQUEST['spec_attr']) : '';
    
    $_REQUEST['spec_attr']=urldecode($_REQUEST['spec_attr']);
    $_REQUEST['spec_attr'] = json_str_iconv($_REQUEST['spec_attr']);
    if(!empty($_REQUEST['spec_attr'])){
        $spec_attr = json_decode($_REQUEST['spec_attr'],true);
    }

    //处理图片链接
    if($spec_attr['leftBannerLink']){
        foreach($spec_attr['leftBannerLink'] as $k=>$v){
            $spec_attr['leftBannerLink'][$k] = str_replace(array('＆'),'&',$v);
        }
    }
    if($spec_attr['rightAdvLink']){
        foreach($spec_attr['rightAdvLink'] as $k=>$v){
            $spec_attr['rightAdvLink'][$k] = str_replace(array('＆'),'&',$v);
        }
    }
    if($spec_attr['leftAdvLink']){
        foreach($spec_attr['leftAdvLink'] as $k=>$v){
            $spec_attr['leftAdvLink'][$k] = str_replace(array('＆'),'&',$v);
        }
    }
    
    //验证品牌
    $spec_attr['brand_ids'] = resetBarnd($spec_attr['brand_ids'],'brand');
    $brand_ids = !empty($spec_attr['brand_ids']) ?  trim($spec_attr['brand_ids']) : '';
    $cat_id = !empty($spec_attr['cat_id']) ?  intval($spec_attr['cat_id']) : 0;
    $parent = '';
    $spec_attr['catChild'] = '';
    $spec_attr['Selected'] = '';
    if($cat_id > 0){
        $parent = get_cat_info($spec_attr['cat_id'],array('parent_id'));
        
        if($parent['parent_id'] > 0){
            $spec_attr['catChild'] = cat_list($parent['parent_id']);
            $spec_attr['Selected'] = $parent['parent_id'];
        }else{
            $spec_attr['catChild'] = cat_list($spec_attr['cat_id']);
            $spec_attr['Selected'] = $cat_id;
        }
        $spec_attr['juniorCat'] = cat_list($cat_id);
    }
    $arr = array();
    //处理商品id和分类id关系
    if($spec_attr['cateValue']){
        foreach($spec_attr['cateValue'] as $k=>$v){
            $arr[$k]['cat_id'] = $v;
            $arr[$k]['cat_goods'] = $spec_attr['cat_goods'][$k];
        }
    }
    $spec_attr['catInfo'] = $arr;
    
    //处理标题特殊字符
    if($spec_attr['rightAdvTitle']){
        foreach($spec_attr['rightAdvTitle'] as $k=>$v){
            if($v){
            	$spec_attr['rightAdvTitle'][$k] = $v;
            }
            
        }
    }
    if($spec_attr['rightAdvSubtitle']){
        foreach($spec_attr['rightAdvSubtitle'] as $k=>$v){
            if($v){
                $spec_attr['rightAdvSubtitle'][$k] = $v;
            }
        }
    }
	//获取楼层模板广告模式数组
	$floor_style = array();
	$floor_style = get_floor_style($result['mode']);
	
    //获取分类
    $cat_list = cat_list();
    
	//初始化模块图片数量
	$imgNumberArr = getAdvNum($result['mode']);
	$imgNumberArr = json_encode($imgNumberArr);
    $smarty->assign('cat_list',$cat_list);
    $smarty->assign('temp',$_REQUEST['act']);
    $smarty->assign('mode',$result['mode']);
    $smarty->assign('lift',$lift);
    $smarty->assign('spec_attr',$spec_attr);
    $smarty->assign('hierarchy',$result['hierarchy']);
	$smarty->assign('floor_style',$floor_style);
	$smarty->assign('imgNumberArr',$imgNumberArr);
	$smarty->assign('suffix',$suffix);
    $result['content'] = $GLOBALS['smarty']->fetch('library/shop_banner.lbi');
    die($json->encode($result)); 
}
//cms频道头部广告
elseif ($_REQUEST['act'] == 'edit_cmsAdv') {
    $json = new JSON;
    $result = array('content' => '', 'mode' => '');
    $spec_attr['needColor'] = '';
    $result['diff'] = isset($_REQUEST['diff']) ? intval($_REQUEST['diff']) : 0;
    $result['hierarchy'] = isset($_REQUEST['hierarchy']) ? intval($_REQUEST['hierarchy']) : 0;
    $lift = !empty($_REQUEST['lift']) ? trim($_REQUEST['lift']) : '';
    $masterTitle = (!empty($_REQUEST['masterTitle']) && $_REQUEST['masterTitle'] != 'null') ? trim(unescape($_REQUEST['masterTitle'])) : '';
    $_REQUEST['spec_attr'] = !empty($_REQUEST['spec_attr']) ? stripslashes($_REQUEST['spec_attr']) : '';
    $_REQUEST['spec_attr'] = urldecode($_REQUEST['spec_attr']);
    $_REQUEST['spec_attr'] = json_str_iconv($_REQUEST['spec_attr']);
    if (!empty($_REQUEST['spec_attr'])) {
        $spec_attr = json_decode($_REQUEST['spec_attr'], true);
    }
    $result['mode'] = isset($_REQUEST['mode']) ? trim($_REQUEST['mode']) : '';

    //处理图片链接
    if ($spec_attr['leftBannerLink']) {
        foreach ($spec_attr['leftBannerLink'] as $k => $v) {
            $spec_attr['leftBannerLink'][$k] = str_replace(array('＆'), '&', $v);
        }
    }
    if ($spec_attr['rightAdvLink']) {
        foreach ($spec_attr['rightAdvLink'] as $k => $v) {
            $spec_attr['rightAdvLink'][$k] = str_replace(array('＆'), '&', $v);
        }
    }
    //处理标题特殊字符
    if($spec_attr['leftBannerTitle']){
        foreach($spec_attr['leftBannerTitle'] as $k=>$v){
            if($v){
            	$spec_attr['leftBannerTitle'][$k] = $v;
            }
            
        }
    }
    if($spec_attr['leftAdvTitle']){
        foreach($spec_attr['leftAdvTitle'] as $k=>$v){
            if($v){
                $spec_attr['leftAdvTitle'][$k] = $v;
            }
        }
    }
    //获取楼层模板广告模式数组
    $floor_style = array();
    $floor_style = get_floor_style($result['mode']);

    $imgNumberArr = getAdvNum($result['mode']);
    $imgNumberArr = json_encode($imgNumberArr);
    $smarty->assign('imgNumberArr', $imgNumberArr);
    $smarty->assign('floor_style', $floor_style);
    $smarty->assign('temp', $_REQUEST['act']);
    $smarty->assign('mode', $result['mode']);
    $smarty->assign('lift', $lift);
    $smarty->assign('spec_attr', $spec_attr);
    $smarty->assign('hierarchy', $result['hierarchy']);
    $result['content'] = $GLOBALS['smarty']->fetch('library/shop_banner.lbi');
    die($json->encode($result));
}
//CMS频道资讯
elseif ($_REQUEST['act'] == 'edit_cmsarti') {
     $json = new JSON;
    $result = array('content' => '', 'mode' => '');
    $result['diff'] = isset($_REQUEST['diff']) ? intval($_REQUEST['diff']) : 0;
    $_REQUEST['spec_attr'] = !empty($_REQUEST['spec_attr']) ? stripslashes($_REQUEST['spec_attr']) : '';
    $_REQUEST['spec_attr'] = urldecode($_REQUEST['spec_attr']);
    $_REQUEST['spec_attr'] = json_str_iconv($_REQUEST['spec_attr']);
    if (!empty($_REQUEST['spec_attr'])) {
        $spec_attr = json_decode($_REQUEST['spec_attr'], true);
    }
    $result['mode'] = isset($_REQUEST['mode']) ? trim($_REQUEST['mode']) : '';
    //处理数组
    if (!empty($spec_attr['sort'])) {
        foreach ($spec_attr['sort'] as $k => $v) {
            $arr[$k]['cat_id'] = $k;
            $arr[$k]['article_id'] = $spec_attr['article_id'][$k];
            $sql = "SELECT cat_name FROM" . $ecs->table('article_cat') . "WHERE cat_id = '$k'";
            $arr[$k]['cat_name'] = $db->getOne($sql);
            $arr[$k]['sort'] = $spec_attr['sort'][$k];
            $arr[$k]['def_article_id'] = $spec_attr['def_article_id'][$k];
            $sort_vals[$k] = isset($spec_attr['sort'][$k]) ? $spec_attr['sort'][$k] : 0;
        }
    }
    $smarty->assign('cat_select',  article_cat_list_new(0,0,true,1));//获取文章分类
    $smarty->assign('temp', $_REQUEST['act']);
    $smarty->assign('mode', $result['mode']);
    $smarty->assign('spec_attr', $arr);
    $result['content'] = $GLOBALS['smarty']->fetch('library/shop_banner.lbi');
    die($json->encode($result));
}
//CMS频道  商品
elseif($_REQUEST['act'] == 'edit_cmsgoods'){
    $json = new JSON;
    $result = array('content' => '', 'mode' => '');
    $result['diff'] = isset($_REQUEST['diff']) ? intval($_REQUEST['diff']) : 0;
    $cat_id = !empty($_REQUEST['cat_id']) ? intval($_REQUEST['cat_id']) : 0;
    $_REQUEST['spec_attr'] = !empty($_REQUEST['spec_attr']) ? stripslashes($_REQUEST['spec_attr']) : '';
    $_REQUEST['spec_attr'] = urldecode($_REQUEST['spec_attr']);
    $_REQUEST['spec_attr'] = json_str_iconv($_REQUEST['spec_attr']);
    if (!empty($_REQUEST['spec_attr'])) {
        $spec_attr = json_decode($_REQUEST['spec_attr'], true);
    }
    $result['mode'] = isset($_REQUEST['mode']) ? trim($_REQUEST['mode']) : '';
    
    /* 取得分类列表 */
    //获取下拉列表 by wu start
    set_default_filter(0, $cat_id, $adminru['ru_id']); //设置默认筛选
    
    $smarty->assign('parent_category', get_every_category($spec_attr['cat_id'])); //上级分类导航
    $smarty->assign('select_category_html', $select_category_html);
    
    $smarty->assign('cat_select',  article_cat_list_new(0,0,true,1));//获取文章分类
    
    $smarty->assign('temp', $_REQUEST['act']);
    $smarty->assign('mode', $result['mode']);
    $smarty->assign('spec_attr', $spec_attr);
    $result['content'] = $GLOBALS['smarty']->fetch('library/shop_banner.lbi');
    die($json->encode($result));
}
/*--------------------------------------------------------*/
//耧播右侧
/*--------------------------------------------------------*/
elseif($_REQUEST['act'] == 'vipEdit'){
    $json = new JSON;
    $result = array('content' => '','mode'=>'');
    $result['act'] = $_REQUEST['act'];
    $result['suffix'] = isset($_REQUEST['suffix']) ? trim($_REQUEST['suffix']) : '';//模板名称
	
    $_REQUEST['spec_attr'] = !empty($_REQUEST['spec_attr']) ? stripslashes($_REQUEST['spec_attr']) : '';
    
    $_REQUEST['spec_attr']=urldecode($_REQUEST['spec_attr']);
    $_REQUEST['spec_attr'] = json_str_iconv($_REQUEST['spec_attr']);
    if(!empty($_REQUEST['spec_attr'])){
        $spec_attr = json_decode($_REQUEST['spec_attr'],true);
    }
    $index_article_cat = isset($spec_attr['index_article_cat'])  ? trim($spec_attr['index_article_cat']) : '';
    $quick_url = isset($spec_attr['quick_url'])  ? explode(',', $spec_attr['quick_url']) : array();
    $quick_name = isset($spec_attr['quick_name'])  ? $spec_attr['quick_name'] : array();
    $style_icon = isset($spec_attr['style_icon']) ? $spec_attr['style_icon'] : array();
    //获取快捷入口数组
    $count = COUNT($quick_url);//数组长度
    /*合并数组*/
    $arr=array();
    for($i=0;$i<$count;$i++){
        $arr[$i]['quick_url'] = $quick_url[$i];
        $arr[$i]['quick_name'] = $quick_name[$i];
        $arr[$i]['style_icon'] = $style_icon[$i];
        switch ($i) {
            case '0':
                $arr[$i]['zh_cn'] = $_LANG['num_1'];
                break;

            case '1' :
                $arr[$i]['zh_cn'] = $_LANG['num_2'];
                break;

            case '2' :
                $arr[$i]['zh_cn'] = $_LANG['num_3'];
                break;

            case '3' :
                $arr[$i]['zh_cn'] = $_LANG['num_4'];
                break;

            case '4' :
                $arr[$i]['zh_cn'] = $_LANG['num_5'];
                break;

            case '5' :
                $arr[$i]['zh_cn'] = $_LANG['num_6'];
                break;
        }
    }
    $smarty->assign('temp',$_REQUEST['act']);
	$smarty->assign('suffix', $result['suffix']);
    $smarty->assign('quick',$arr);
    $smarty->assign('index_article_cat',$index_article_cat);
    $result['content'] = $GLOBALS['smarty']->fetch('library/shop_banner.lbi');
    die($json->encode($result)); 
}

/*--------------------------------------------------------*/
//头部导航
/*--------------------------------------------------------*/
elseif($_REQUEST['act'] == 'nav_mode') {
    $json = new JSON;
    $result = array('content' => '', 'mode' => '');
    $result['mode'] = isset($_REQUEST['mode']) ? trim($_REQUEST['mode']) : '';
    $result['topic'] = isset($_REQUEST['topic']) ? intval($_REQUEST['topic']) : 0;
	$suffix = isset($_REQUEST['suffix']) ? trim($_REQUEST['suffix']) : '';//模板名称
    $smarty->assign('temp', $result['mode']);

    $_REQUEST['spec_attr'] = !empty($_REQUEST['spec_attr']) ? stripslashes($_REQUEST['spec_attr']) : '';
    $_REQUEST['spec_attr'] = urldecode($_REQUEST['spec_attr']);
    $_REQUEST['spec_attr'] = json_str_iconv($_REQUEST['spec_attr']);
    if (!empty($_REQUEST['spec_attr'])) {
        $spec_attr = json_decode($_REQUEST['spec_attr'], true);
    }
     /* 获取导航数据 */
    $sql = "SELECT id, name,  vieworder, url".
               " FROM ".$GLOBALS['ecs']->table('nav')."WHERE type = 'middle'";
    $system = $db->getAll($sql);
    $smarty->assign('system', $system);
    $smarty->assign('topic_type', $result['topic']);
    $smarty->assign('navigator', $spec_attr);
	$smarty->assign('suffix', $suffix);
    $result['content'] = $GLOBALS['smarty']->fetch('library/shop_banner.lbi');
    die($json->encode($result));
}
/*--------------------------------------------------------*/
//模板信息弹框
/*--------------------------------------------------------*/
elseif($_REQUEST['act'] == 'template_information')
{
    $json = new JSON;
    $result = array('content' => '','mode'=>'');
    $code = !empty($_REQUEST['code'])  ?  addslashes($_REQUEST['code'])  : '';
    $check = isset($_REQUEST['check'])  ?  intval($_REQUEST['check'])  : 0;
    $action = isset($_REQUEST['action'])  ?  trim($_REQUEST['action'])  : '';
    $temp_id = isset($_REQUEST['temp_id'])  ?  intval($_REQUEST['temp_id'])  : 0;
    $template_type = isset($_REQUEST['template_type'])  ?  trim($_REQUEST['template_type'])  : '';
    $template_mall_info = array();
    $theme = $GLOBALS['_CFG']['template'];
    if($temp_id > 0 && $template_type == 'seller'){ 
        $sql = "SELECT temp_mode,temp_cost FROM".$ecs->table('template_mall')."WHERE temp_id = '".$temp_id."' LIMIT 1";
        $template_mall_info = $db->getRow($sql);
        $theme = '';
    }
    if($code){
        $smarty->assign('template', get_seller_template_info($code,0,$theme));
    }
    $smarty->assign('template_mall_info',$template_mall_info);
    $smarty->assign('template_type',$template_type);
    $smarty->assign('code',$code);
    $smarty->assign("temp",$_REQUEST['act']);
    $smarty->assign("check",$check);
    $smarty->assign("temp_id",$temp_id);
    $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
    die($json->encode($result));
}

/*------------------------------------------------------ */
//-- 地图弹出窗口  by kong
/*------------------------------------------------------ */
elseif($_REQUEST['act'] == 'getmap_html') {
    $json = new JSON;
    $result = array('content' => '', 'sgs' => '');
    $temp = !empty($_REQUEST['act']) ? $_REQUEST['act'] : '';
    $smarty->assign("temp", $temp);
    $result['sgs'] = $temp;
    $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
    die($json->encode($result));
}

/*--------------------------------------------------------*/
//头部模块弹窗
/*--------------------------------------------------------*/
elseif($_REQUEST['act'] == 'header')
{
    $json = new JSON;
    $result = array('content' => '','mode'=>'');
    $arr =array();
    $smarty->assign("temp",$_REQUEST['act']);
    $_REQUEST['spec_attr'] = !empty($_REQUEST['spec_attr']) ? stripslashes($_REQUEST['spec_attr']) : '';

    $_REQUEST['spec_attr']=urldecode($_REQUEST['spec_attr']);
    $_REQUEST['spec_attr'] = json_str_iconv($_REQUEST['spec_attr']);
    if(!empty($_REQUEST['spec_attr'])){
        $spec_attr = json_decode($_REQUEST['spec_attr'],true);
    }
    $spec_attr['header_type']  = isset($spec_attr['header_type']) ? $spec_attr['header_type'] : 'defalt_type';
    $custom_content = (isset($_REQUEST['custom_content']) && $_REQUEST['custom_content'] != 'undefined')  ? unescape($_REQUEST['custom_content'])  : '';
    $custom_content = !empty($custom_content) ? stripslashes($custom_content) : '';
    $result['mode'] = isset($_REQUEST['mode'])  ? addslashes($_REQUEST['mode'])  : '';
    $spec_attr['suffix'] = isset($_REQUEST['suffix'])  ? addslashes($_REQUEST['suffix'])  : '';
    /* 创建 百度编辑器 wang 商家入驻 */
    $FCKeditor = create_ueditor_editor('custom_content',$custom_content,486,1);
    $smarty->assign('FCKeditor',   $FCKeditor);
    $smarty->assign('content',   $spec_attr);
    $result['content'] = $GLOBALS['smarty']->fetch('library/shop_banner.lbi');
    die($json->encode($result));
}
/*--------------------------------------------------------*/
//导航模块弹窗
/*--------------------------------------------------------*/
elseif($_REQUEST['act'] == 'navigator')
{
    $json = new JSON;
    $result = array('content' => '','mode'=>'');
    $topic_type = isset($_REQUEST['topic_type']) ? trim($_REQUEST['topic_type']) : '';
    /*处理数组*/
    $spec_attr['target'] = '';
    $_REQUEST['spec_attr'] = !empty($_REQUEST['spec_attr']) ? stripslashes($_REQUEST['spec_attr']) : '';
    $_REQUEST['spec_attr']=urldecode($_REQUEST['spec_attr']);
    $_REQUEST['spec_attr'] = json_str_iconv($_REQUEST['spec_attr']);
    if(!empty($_REQUEST['spec_attr'])){
        $spec_attr = json_decode($_REQUEST['spec_attr'],true);
    }
    $result['diff'] = isset($_REQUEST['diff'])  ?  intval($_REQUEST['diff'])  : 0;
    unset($spec_attr['target']);
    $navigator = $spec_attr;
   
    $spec_attr['target']  = isset($spec_attr['target']) ? $spec_attr['target'] : '_blank';
    $smarty->assign("temp",$_REQUEST['act']);
    $smarty->assign('attr',$spec_attr);
    $result['mode'] = isset($_REQUEST['mode'])  ? addslashes($_REQUEST['mode'])  : '';
    $result['content'] = $GLOBALS['smarty']->fetch('library/shop_banner.lbi');
    die($json->encode($result));
}
//商家订单列表导出弹窗
elseif($_REQUEST['act'] == 'merchant_download'){
    $json = new JSON;
    $result = array('content' => '');
    $page_count = isset($_REQUEST['page_count']) ? intval($_REQUEST['page_count']) : 0;//总页数
    $filename = !empty($_REQUEST['filename']) ? trim($_REQUEST['filename']) : '';//处理导出数据的文件
    $fileaction = !empty($_REQUEST['fileaction']) ? trim($_REQUEST['fileaction']) : '';//处理导出数据的入口
    $lastfilename = !empty($_REQUEST['lastfilename']) ? trim($_REQUEST['lastfilename']) : '';//最后处理导出的文件
    $lastaction = !empty($_REQUEST['lastaction']) ? trim($_REQUEST['lastaction']) : '';//最后处理导出的程序入口
    
    $smarty->assign('page_count',$page_count);
    $smarty->assign('filename',$filename);
    $smarty->assign('fileaction',$fileaction);
    $smarty->assign('lastfilename',$lastfilename);
    $smarty->assign('lastaction',$lastaction);
    
    unset($_SESSION['merchants_download_content']);//初始化导出对象
    $result['content'] = $GLOBALS['smarty']->fetch('library/merchant_download.lbi');
    die($json->encode($result));
}

//包邮券设置不包邮地区弹窗
elseif($_REQUEST['act'] == 'set_free_shipping'){
    $json = new JSON;
    $result = array('content' => '');
    
    $region_ids = !empty($_REQUEST['region_ids']) ? explode(',',trim($_REQUEST['region_ids'])) : array();
    $sql = "SELECT ra_id, ra_name ".
                " FROM " . $GLOBALS['ecs']->table('merchants_region_area') ;
    $region_list = $GLOBALS['db']->getAll($sql);

    $count = count($region_list);
    for ($i=0; $i<$count; $i++)
    {
        $region_list[$i]['add_time'] = local_date("Y-m-d H:i:s", $region_list[$i]['add_time']);
		$area = ajax_get_area_list($region_list[$i]['ra_id'],$region_ids);
		$region_list[$i]['area_list'] = $area;
    }
    $smarty->assign('region_list',$region_list);
    $smarty->assign('temp','set_free_shipping');
    $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
    
    die($json->encode($result));
}
//商品详情页  添加属性分类，添加类型，添加属性弹窗
elseif($_REQUEST['act'] == 'add_goods_type_cat'){
    $json = new JSON;
    $result = array('content' => '');
    
    $type = !empty($_REQUEST['type']) ? trim($_REQUEST['type']) : '';
    $goods_id = !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
    if($goods_id > 0){
        $sql = "SELECT user_id FROM".$ecs->table('goods')."WHERE goods_id = '$goods_id' LIMIT 1";
        $user_id = $db->getOne($sql);
    }else{
        $user_id = $adminru['ru_id'];
    }
    if($type == 'add_goods_type_cat' || $type == 'add_goods_type'){
        $cat_level = get_type_cat_arr(0,0,0,$user_id);
        $smarty->assign("cat_level",$cat_level);
    }elseif($type == 'attribute_add'){
        require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/' .ADMIN_PATH. '/attribute.php');
        $smarty->assign('lang', $_LANG);
        
        $add_edit_cenetent = $_LANG['temporary_not_attr_power'];
        $goods_type = isset($_REQUEST['goods_type']) ? intval($_REQUEST['goods_type']) : 0;
        $attr = array(
            'attr_id' => 0,
            'cat_id' => $goods_type,
            'attr_cat_type' => 0, //by zhang
            'attr_name' => '',
            'attr_input_type' => 0,
            'attr_index'  => 0,
            'attr_values' => '',
            'attr_type' => 0,
            'is_linked' => 0,
        );
        $smarty->assign('attr', $attr);
        $smarty->assign('attr_groups', get_attr_groups($attr['cat_id']));
        /* 取得商品分类列表 */
        $smarty->assign('goods_type_list', goods_type_list($attr['cat_id']));
    }
    $smarty->assign('user_id',$user_id);
    $smarty->assign('goods_id',$goods_id);
    $smarty->assign('temp',$type);
    $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
    
    die($json->encode($result));
}

/*--------------------------------------------------------*/
// ajax添加品牌
/*--------------------------------------------------------*/
elseif($_REQUEST['act'] == 'ajaxBrand')
{
    require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/' .ADMIN_PATH. '/brand.php');
    $json = new JSON;
    $result = array('content' => '','mode'=>'');
    
    $smarty->assign('is_need',     $_CFG['template'] == 'ecmoban_dsc2017' ? 1:0);
    $smarty->assign("temp",$_REQUEST['act']);
    $smarty->assign("lang",$_LANG);
    $smarty->assign('form_action', 'brand_insert');
    $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
    die($json->encode($result));
}

/*--------------------------------------------------------*/
// ajax添加品牌操作
/*--------------------------------------------------------*/
elseif($_REQUEST['act'] == 'brand_insert')
{
    $json = new JSON;
    $result = array('content' => '','message'=>'','error' => 0);
    $exc = new exchange($ecs->table("brand"), $db, 'brand_id', 'brand_name');

    $is_show = isset($_REQUEST['is_show']) ? intval($_REQUEST['is_show']) : 0;
    $_POST['brand_name'] = isset($_POST['brand_name']) && !empty($_POST['brand_name']) ? dsc_addslashes($_POST['brand_name']) : '';
    
    $is_only = $exc->is_only('brand_name', $_POST['brand_name']);
    if (!$is_only)
    {
        $result['error'] = 2;
        $result['message'] = $_LANG['brand_name_repeat'];
        die($json->encode($result));
    }

    /*对描述处理*/
    if (!empty($_POST['brand_desc']))
    {
        $_POST['brand_desc'] = $_POST['brand_desc'];
    }

     /*处理图片*/
    $img_name = basename($image->upload_image($_FILES['brand_logo'],'brandlogo'));
    
    get_oss_add_file(array(DATA_DIR . '/brandlogo/' . $img_name));
	/*品牌专区大图 by wu start*/
    $index_img = basename($image->upload_image($_FILES['index_img'],'indeximg'));
    get_oss_add_file(array(DATA_DIR . '/indeximg/' . $index_img));	
	/*品牌专区大图 by wu end*/
	
	/*品牌背景图 start*/
	$brand_bg = basename($image->upload_image($_FILES['brand_bg'],'brandbg'));
	get_oss_add_file(array(DATA_DIR . '/brandbg/' . $brand_bg));	
	/*品牌背景图 end*/

     /*处理URL*/
    $site_url = sanitize_url( $_POST['site_url'] );

    /*插入数据*/
    $sql = "INSERT INTO " . $ecs->table('brand') . "(brand_name, brand_letter, brand_first_char, site_url, brand_desc, brand_logo, index_img, brand_bg, is_show, sort_order) " .
            "VALUES ('$_POST[brand_name]', '$_POST[brand_letter]', '" . strtoupper($_POST['brand_first_char']) . "', '$site_url', '$_POST[brand_desc]', '$img_name', '$index_img', '$brand_bg', '$is_show', '$_POST[sort_order]')";
    if($db->query($sql)){
        if ($brand_id = $db->insert_id()) {
            $is_recommend = !empty($_POST['is_recommend']) ? intval($_POST['is_recommend']) : 0;
            $extend_sql = "INSERT INTO " . $ecs->table('brand_extend') . " (brand_id,is_recommend) values ('$brand_id','$is_recommend')";
            $db->query($extend_sql);
        }

        admin_log($_POST['brand_name'],'add','brand');

        /* 清除缓存 */
        clear_cache_files(); 
        $result['message'] = $_LANG['add_brand_success'];
    }else{
        $result['error'] = 1;
        $result['message'] = $_LANG['add_brand_fail'];
    }
    die($json->encode($result));
}

/*--------------------------------------------------------*/
// ajax添加运费模板
/*--------------------------------------------------------*/
elseif($_REQUEST['act'] == 'ajaxTransport')
{
    require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/' .ADMIN_PATH. '/goods_transport.php');
    require_once(ROOT_PATH . 'includes/lib_order.php');
    $json = new JSON;
    $result = array('content' => '','mode'=>'');

    $tid = empty($_REQUEST['tid']) ? 0 : intval($_REQUEST['tid']);
    $shipping_id = 0;
    
    $transport_info = array();
    $shipping_tpl = array();
    if ($tid) {
        $form_action = 'transport_update';  
        
        $trow = get_goods_transport($tid);
        
        if($tid > 0){
            $transport_info = $trow; 
            $shipping_tpl = get_transport_shipping_list($tid, $adminru['ru_id']);
        }        
    } else {
        $form_action = 'transport_insert';
        
        $sql = "DELETE FROM" . $ecs->table("goods_transport_tpl") . "WHERE tid = 0 AND admin_id = '$admin_id'";
        $db->query($sql);
    }
    
    $smarty->assign('shipping_tpl', $shipping_tpl);
    $smarty->assign('tid', $tid);
    $smarty->assign('transport_info', $transport_info);
    $smarty->assign('transport_area', get_transport_area($tid));
    $smarty->assign('transport_express', get_transport_express($tid));
    
    //快递列表
    $shipping_list = shipping_list();
    foreach ($shipping_list as $key => $val) {
        //剔除手机快递
        if (substr($row['shipping_code'], 0, 5) == 'ship_') {
            unset($arr[$key]);
            continue;
        }
        /* 剔除上门自提 */
        if ($val['shipping_id'] == 17) {
            unset($shipping_list[$key]);
        }
    }
    $smarty->assign('shipping_list', $shipping_list);
    
    $smarty->assign("temp",$_REQUEST['act']);
    $smarty->assign("lang",$_LANG);
    $smarty->assign('form_action', $form_action);
    $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
    die($json->encode($result));
}

/*--------------------------------------------------------*/
// ajax添加运费模板操作
/*--------------------------------------------------------*/
elseif($_REQUEST['act'] == 'transport_insert' || $_REQUEST['act'] == 'transport_update')
{
    $json = new JSON;
    $result = array('content' => '','message'=>'','error' => 0);

    $data = array();
    $data['tid'] = !isset($_REQUEST['tid']) && empty($_REQUEST['tid']) ? 0 : intval($_REQUEST['tid']);
    $data['ru_id'] = $adminru['ru_id'];
    $data['type'] = empty($_REQUEST['type']) ? 0 : intval($_REQUEST['type']);
    $data['title'] = empty($_REQUEST['title']) ? '' : trim($_REQUEST['title']);
    $data['freight_type'] = empty($_REQUEST['freight_type']) ? 0 : intval($_REQUEST['freight_type']);
    $data['update_time'] = gmtime();
    $data['free_money'] = empty($_REQUEST['free_money']) ? 0 : floatval($_REQUEST['free_money']);
    $data['shipping_title'] = empty($_REQUEST['shipping_title']) ? 0 : trim($_REQUEST['shipping_title']);
    
    $s_tid = $data['tid'];
        
    if ($_REQUEST['act'] == 'transport_update') {
        $result['message'] = $_LANG['edit_freight_template_success'];
        $db->autoExecute($ecs->table('goods_transport'), $data, "UPDATE", "tid = '$data[tid]'");
        $tid = $s_tid;
        
        $where = " tid = '$tid'";
    } else {
        $result['message'] = $_LANG['edit_freight_template_success'];
        $db->autoExecute($ecs->table('goods_transport'), $data, 'INSERT');
        $tid = $db->insert_id();
        $db->autoExecute($ecs->table('goods_transport_extend'), array('tid' => $tid), "UPDATE", "tid = '0' AND admin_id = '$admin_id' ");
        $db->autoExecute($ecs->table('goods_transport_express'), array('tid' => $tid), "UPDATE", "tid = '0' AND admin_id = '$admin_id' ");
        
        $where = " admin_id = '$admin_id' AND tid = 0";
    }
        
    //处理运费模板
    if ($data['freight_type'] > 0) {
        
        if(!isset($_SESSION[$s_tid]['tpl_id']) && empty($_SESSION[$s_tid]['tpl_id'])){
            $sql = "SELECT GROUP_CONCAT(id) AS id FROM " .$GLOBALS['ecs']->table('goods_transport_tpl'). " WHERE " . $where;
            $tpl_id = $GLOBALS['db']->getOne($sql);
        }else{
            $tpl_id = $_SESSION[$s_tid]['tpl_id'];
        }
        
        if (!empty($tpl_id)) {
            $sql = "UPDATE" . $ecs->table("goods_transport_tpl") . " SET tid = '$tid' WHERE admin_id = '$admin_id' AND tid = 0 AND id " . db_create_in($tpl_id);
            $db->query($sql);
            
            unset($_SESSION[$s_tid]['tpl_id']);
        }
    }

    //处理地区数据
    if (count($_REQUEST['sprice']) > 0) {
        foreach ($_REQUEST['sprice'] as $key => $val) {
            $info = array();
            $info['sprice'] = $val;
            $db->autoExecute($ecs->table('goods_transport_extend'), $info, "UPDATE", "id = '$key'");
        }
    }

    //处理快递数据
    if (count($_REQUEST['shipping_fee']) > 0) {
        foreach ($_REQUEST['shipping_fee'] as $key => $val) {
            $info = array();
            $info['shipping_fee'] = $val;
            $db->autoExecute($ecs->table('goods_transport_express'), $info, "UPDATE", "id = '$key'");
        }
    }
    
    $smarty->assign("temp","transport_reload");
    $smarty->assign('transport_list', get_table_date("goods_transport", "ru_id='{$adminru['ru_id']}'", array('tid, title'), 1));
    $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');

    die($json->encode($result));
}

/*--------------------------------------------------------*/
// ajax添加文章
/*--------------------------------------------------------*/
elseif($_REQUEST['act'] == 'ajaxArticle')
{
    require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/' .ADMIN_PATH. '/article.php');
    $json = new JSON;
    $result = array('content' => '','mode'=>'');

    /* 创建 html editor */
    create_html_editor('FCKeditor1');

    /*初始化*/
    $article = array();
    $article['is_open'] = 1;

    /* 取得分类、品牌 */
    set_default_filter(); //设置默认筛选

    /* 清理关联商品 */
    $sql = "DELETE FROM " . $ecs->table('goods_article') . " WHERE article_id = 0";
    $db->query($sql);
    
    $smarty->assign('filter_category_list', get_category_list());
    
    if (isset($_GET['id']))
    {
        $smarty->assign('cur_id',  $_GET['id']);
    }
    $smarty->assign('article',     $article);
    $smarty->assign('cat_select',  article_cat_list_new(0));
    $smarty->assign("temp",$_REQUEST['act']);
    $smarty->assign("lang",$_LANG);
    $smarty->assign('form_action', 'article_insert');
    $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
    die($json->encode($result));
}

/*--------------------------------------------------------*/
// ajax添加文章操作
/*--------------------------------------------------------*/
elseif($_REQUEST['act'] == 'article_insert')
{
    $json = new JSON;
    $result = array('content' => '','message'=>'','error' => 0);
    $exc   = new exchange($ecs->table("article"), $db, 'article_id', 'title');

    $target_select = !empty($_REQUEST['target_select'])   ?   $_REQUEST['target_select'] : array();
    /*检查是否重复*/
    $is_only = $exc->is_only('title', $_POST['title'],0, " cat_id ='$_POST[article_cat]'");

    if (!$is_only)
    {
        $result['error'] = 2;
        $result['message'] = $_LANG['article_title_repeat'];
        die($json->encode($result));
    }

    /* 取得文件地址 */
    $file_url = '';
    if ((isset($_FILES['file']['error']) && $_FILES['file']['error'] == 0) || (!isset($_FILES['file']['error']) && isset($_FILES['file']['tmp_name']) && $_FILES['file']['tmp_name'] != 'none'))
    {
        // 检查文件格式
        if (!check_file_type($_FILES['file']['tmp_name'], $_FILES['file']['name'], $allow_file_types))
        {
            sys_msg($_LANG['invalid_file']);
        }

        // 复制文件
        $res = upload_article_file($_FILES['file']);
        if ($res != false)
        {
            $file_url = $res;
        }
    }

    if ($file_url == '')
    {
        $file_url = $_POST['file_url'];
    }else{
        get_oss_add_file(array($file_url));
    }

    /* 计算文章打开方式 */
    if ($file_url == '')
    {
        $open_type = 0;
    }
    else
    {
        $open_type = $_POST['FCKeditor1'] == '' ? 1 : 2;
    }

    /*插入数据*/
    $add_time = gmtime();
    if (empty($_POST['cat_id']))
    {
        $_POST['cat_id'] = 0;
    }
    $sql = "INSERT INTO ".$ecs->table('article')."(title, cat_id, article_type, is_open, author, ".
                "author_email, keywords, content, add_time, file_url, open_type, link, description) ".
            "VALUES ('$_POST[title]', '$_POST[article_cat]', '$_POST[article_type]', '$_POST[is_open]', ".
                "'$_POST[author]', '$_POST[author_email]', '$_POST[keywords]', '$_POST[FCKeditor1]', ".
                "'$add_time', '$file_url', '$open_type', '$_POST[link_url]', '$_POST[description]')";
    if($db->query($sql)){
        /* 处理关联商品 */
        $article_id = $db->insert_id();

        if(!empty($target_select)){
           foreach($target_select as $k=>$val){
               $sql = 'INSERT INTO ' . $ecs->table('goods_article') . ' (goods_id, article_id) '.
                   "VALUES ('$val', '$article_id')";
               $db->query($sql);
           }
        }

        admin_log($_POST['title'],'add','article');

        clear_cache_files(); // 清除相关的缓存文件    
        $result['message'] = $_LANG['add_article_success'];
    }else{
        $result['error'] = 1;
        $result['message'] = $_LANG['add_article_fail'];
    }

    die($json->encode($result));
}

/*--------------------------------------------------------*/
// ajax添加地区
/*--------------------------------------------------------*/
elseif($_REQUEST['act'] == 'ajaxArea')
{
    require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/' .ADMIN_PATH. '/area_manage.php');
    $exc = new exchange($ecs->table('region'), $db, 'region_id', 'region_name');
    $json = new JSON;
    $result = array('content' => '','mode'=>'');
    $is_ajax = 1;

    /* 取得参数：上级地区id */
    $region_id = empty($_REQUEST['pid']) ? 0 : intval($_REQUEST['pid']);
    $smarty->assign('parent_id',    $region_id);

    /* 取得列表显示的地区的类型 */
    if ($region_id == 0)
    {
        $region_type = 0;
    }
    else
    {
        $region_type = $exc->get_name($region_id, 'region_type') + 1;
    }
    $smarty->assign('region_type',  $region_type);

    /* 获取地区列表 */
    $region_arr = area_list($region_id);
    $smarty->assign('region_arr',   $region_arr);
    $area_top = '-';
    /* 当前的地区名称 */
    if ($region_id > 0)
    {
        $area_name = $exc->get_name($region_id);
        $area_top =  $area_name ;
        if ($region_arr)
        {
            $area = $region_arr[0]['type'];
        }
    }
    else
    {
        $area = $_LANG['country'];
    }
    $smarty->assign('area_top',    $area_top);
    $smarty->assign('area_here',    $area);

    /* 返回上一级的链接 */
    if ($region_id > 0)
    {
        $parent_id = $exc->get_name($region_id, 'parent_id');
        $is_ajax = 0;
        $action_link = array('text' => $_LANG['back_page'], 'href' => 'javascript:;', 'pid' => $parent_id, 'type'=>1);
    }

    $smarty->assign('is_ajax', $is_ajax);
    $smarty->assign('action_link',  $action_link);
    $smarty->assign("temp",$_REQUEST['act']);
    $smarty->assign("lang",$_LANG);
    $smarty->assign('form_action', 'area_insert');
    $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
    die($json->encode($result));
}

/*--------------------------------------------------------*/
// ajax添加地区操作
/*--------------------------------------------------------*/
elseif($_REQUEST['act'] == 'area_insert')
{
    $json = new JSON;
    $result = array('content' => '','message'=>'','error' => 0);
    $exc = new exchange($ecs->table('region'), $db, 'region_id', 'region_name');

    $parent_id      = intval($_POST['parent_id']);
    $region_name    = json_str_iconv(trim($_POST['region_name']));
    $region_type    = intval($_POST['region_type']);
    
    /* 获取地区列表 */
    $region_arr = area_list($region_id);
    $smarty->assign('region_arr',   $region_arr);
    $area_top = '-';
    /* 当前的地区名称 */
    if ($region_id > 0)
    {
        $area_name = $exc->get_name($region_id);
        $area_top =  $area_name ;
        if ($region_arr)
        {
            $area = $region_arr[0]['type'];
        }
    }
    else
    {
        $area = $_LANG['country'];
    }
    $smarty->assign('area_top',    $area_top);
    $smarty->assign('area_here',    $area);
    
    if (empty($region_name))
    {
        $result['error'] = 2;
        $result['message'] = $_LANG['region_name_not_null'];
        die($json->encode($result));
    }

    /* 查看区域是否重复 */
    if (!$exc->is_only('region_name', $region_name, 0, "parent_id = '$parent_id'"))
    {
        $result['error'] = 2;
        $result['message'] = $_LANG['region_name_repeat'];
        die($json->encode($result));
    }

    $sql = "INSERT INTO " . $ecs->table('region') . " (parent_id, region_name, region_type) ".
           "VALUES ('$parent_id', '$region_name', '$region_type')";
    if ($GLOBALS['db']->query($sql, 'SILENT'))
    {
        admin_log($region_name, 'add','area');

        /* 获取地区列表 */
        $region_arr = area_list($parent_id);
		
		foreach($region_arr as $k=>$v){
			$region_arr[$k]['parent_name'] = $exc->get_name($v['parent_id']);
		}
		
        $smarty->assign('region_arr',   $region_arr);

        $smarty->assign('region_type', $region_type);

        $smarty->assign("temp", "ajaxArea");
        $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
    }
    else
    {
        $result['error'] = 1;
        $result['message'] = $_LANG['add_region_fail'];
    }    
    
    die($json->encode($result));
}

/*--------------------------------------------------------*/
// ajax添加分类
/*--------------------------------------------------------*/
elseif($_REQUEST['act'] == 'ajaxCate')
{
    require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/' .ADMIN_PATH. '/category.php');
    $json = new JSON;
    $result = array('content' => '','mode'=>'');
    
    $parent_id = empty($_REQUEST['parent_id']) ? 0 : intval($_REQUEST['parent_id']);
    if (!empty($parent_id)) {
        set_default_filter(0, $parent_id); //设置默认筛选
        $smarty->assign('parent_category', get_every_category($parent_id)); //上级分类
        $smarty->assign('parent_id', $parent_id); //上级分类
    } else {
        set_default_filter(); //设置默认筛选
    }
	//属性分类
    $type_level = get_type_cat_arr(0,0,0,$adminru['ru_id']);
    $smarty->assign('type_level',    $type_level);	

    //获取属性列表
    $sql = "SELECT a.attr_id, a.cat_id, a.attr_name ".
           " FROM " . $GLOBALS['ecs']->table('attribute'). " AS a,  ".
           $GLOBALS['ecs']->table('goods_type') . " AS c ".
           " WHERE  a.cat_id = c.cat_id AND c.enabled = 1 ".
           " ORDER BY a.cat_id , a.sort_order";

    $arr = $GLOBALS['db']->getAll($sql);

    $list = array();

    foreach ($arr as $val)
    {
        $list[$val['cat_id']][] = array($val['attr_id']=>$val['attr_name']);
    }
    
    /* 模板赋值 */    
    $smarty->assign('goods_type_list', goods_type_list(0)); // 取得商品类型
    $smarty->assign('attr_list', $list); // 取得商品属性
    $smarty->assign('cat_info', array('is_show' => 1));
    $smarty->assign('ru_id', $adminru['ru_id']);    
    
    $smarty->assign("temp",$_REQUEST['act']);
    $smarty->assign("lang",$_LANG);
    $smarty->assign('form_action', 'cate_insert');
    $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
    die($json->encode($result));
}

/*--------------------------------------------------------*/
// ajax添加分类操作
/*--------------------------------------------------------*/
elseif($_REQUEST['act'] == 'cate_insert')
{
    $json = new JSON;
    $result = array('content' => '','message'=>'','error' => 0);
    $exc = new exchange($ecs->table("category"), $db, 'cat_id', 'cat_name');
    
    /* 初始化变量 */
    $cat['cat_id'] = !empty($_POST['cat_id']) ? intval($_POST['cat_id']) : 0;
    $cat['parent_id'] = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
    $cat['level'] = count(get_select_category($cat['parent_id'], 1, true)) - 2;

    if ($cat['level'] > 1 && $adminru['ru_id'] == 0) {
        $link[0]['text'] = $_LANG['go_back'];

        if ($cat['cat_id'] > 0) {
            $link[0]['href'] = 'category.php?act=edit&cat_id=' . $cat['cat_id'];
        } else {
            $link[0]['href'] = 'category.php?act=add&parent_id=' . $cat['parent_id'];
        }
        
        $result['error'] = 2;
        $result['message'] = $_LANG['cat_prompt_notic_one'];
        die($json->encode($result));
    }

    //ecmoban模板堂 --zhuo start 
    if ($cat['level'] < 2 && $adminru['ru_id'] > 0) {

        $link[0]['text'] = $_LANG['go_back'];

        if ($cat['cat_id'] > 0) {
            $link[0]['href'] = 'category.php?act=edit&cat_id=' . $cat['cat_id'];
        } else {
            $link[0]['href'] = 'category.php?act=add&parent_id=' . $cat['parent_id'];
        }
        
        $result['error'] = 2;
        $result['message'] = $_LANG['cat_prompt_notic_two'];
        die($json->encode($result));
    }
    //ecmoban模板堂 --zhuo end
    //上传分类菜单图标 by wu start
    if (!empty($_FILES['cat_icon']['name'])) {
        if ($_FILES["cat_icon"]["size"] > 200000) {
            $result['error'] = 2;
            $result['message'] = $_LANG['cat_prompt_file_size'];
            die($json->encode($result));            
        }

        $icon_name = explode('.', $_FILES['cat_icon']['name']);
        $key = count($icon_name);
        $type = $icon_name[$key - 1];

        if ($type != 'jpg' && $type != 'png' && $type != 'gif' && $type != 'jpeg') {
            $result['error'] = 2;
            $result['message'] = $_LANG['cat_prompt_file_type'];
            die($json->encode($result));              
        }
        $imgNamePrefix = time() . mt_rand(1001, 9999);
        //文件目录
        $imgDir = ROOT_PATH . "images/cat_icon";
        if (!file_exists($imgDir)) {
            mkdir($imgDir);
        }
        //保存文件
        $imgName = $imgDir . "/" . $imgNamePrefix . '.' . $type;
        $saveDir = "images/cat_icon" . "/" . $imgNamePrefix . '.' . $type;
        move_uploaded_file($_FILES["cat_icon"]["tmp_name"], $imgName);
        $cat['cat_icon'] = $saveDir;
        get_oss_add_file(array($cat['cat_icon'])); //oss存储图片
    }
    //上传分类菜单图标 by wu end
    //上传手机菜单图标 by kong start
    if (!empty($_FILES['touch_icon']['name'])) {
        if ($_FILES["touch_icon"]["size"] > 200000) {
            $result['error'] = 2;
            $result['message'] = $_LANG['cat_prompt_file_size'];
            die($json->encode($result));  
        }

        $icon_name = explode('.', $_FILES['touch_icon']['name']);
        $key = count($icon_name);
        $type = $icon_name[$key - 1];

        if ($type != 'jpg' && $type != 'png' && $type != 'gif' && $type != 'jpeg') {
            $result['error'] = 2;
            $result['message'] = $_LANG['cat_prompt_file_type'];
            die($json->encode($result));  
        }
        $touch_iconPrefix = time() . mt_rand(1001, 9999);
        //文件目录
        $touch_iconDir = "../" . DATA_DIR . "/touch_icon";
        if (!file_exists($touch_iconDir)) {
            mkdir($touch_iconDir);
        }
        //保存文件
        $touchimgName = $touch_iconDir . "/" . $touch_iconPrefix . '.' . $type;
        $touchsaveDir = DATA_DIR . "/touch_icon" . "/" . $touch_iconPrefix . '.' . $type;
        move_uploaded_file($_FILES["touch_icon"]["tmp_name"], $touchimgName);
        $cat['touch_icon'] = $touchsaveDir; 
        get_oss_add_file(array($cat['touch_icon'])); //oss存储图片
    }

    //上传手机菜单图标 by kong end
    //佣金比率 by wu
    $cat['commission_rate'] = !empty($_POST['commission_rate']) ? intval($_POST['commission_rate']) : 0;
    if ($cat['commission_rate'] > 100 || $cat['commission_rate'] < 0) {
        $result['error'] = 2;
        $result['message'] = $_LANG['commission_rate_prompt'];
        die($json->encode($result));          
    }

    $cat['style_icon'] = !empty($_POST['style_icon']) ? trim($_POST['style_icon']) : 'other'; //分类菜单图标
    $cat['sort_order'] = !empty($_POST['sort_order']) ? intval($_POST['sort_order']) : 0;
    $cat['keywords'] = !empty($_POST['keywords']) ? trim($_POST['keywords']) : '';
    $cat['cat_desc'] = !empty($_POST['cat_desc']) ? $_POST['cat_desc'] : '';
    $cat['measure_unit'] = !empty($_POST['measure_unit']) ? trim($_POST['measure_unit']) : '';
    $cat['cat_name'] = !empty($_POST['cat_name']) ? trim($_POST['cat_name']) : '';
    $cat['cat_alias_name'] = !empty($_POST['cat_alias_name']) ? trim($_POST['cat_alias_name']) : '';

    //by guan start
    $pin = new pin();
    $pinyin = $pin->Pinyin($cat['cat_name'], 'UTF8');
    $cat['pinyin_keyword'] = $pinyin;
    //by guan end

    $cat['show_in_nav'] = !empty($_POST['show_in_nav']) ? intval($_POST['show_in_nav']) : 0;
    $cat['style'] = !empty($_POST['style']) ? trim($_POST['style']) : '';
    $cat['is_show'] = !empty($_POST['is_show']) ? intval($_POST['is_show']) : 0;

    /* by zhou */
    $cat['is_top_show'] = !empty($_POST['is_top_show']) ? intval($_POST['is_top_show']) : 0;
    $cat['is_top_style'] = !empty($_POST['is_top_style']) ? intval($_POST['is_top_style']) : 0;
    $cat['top_style_tpl'] = !empty($_POST['top_style_tpl']) ? $_POST['top_style_tpl'] : 0; //顶级分类页模板 by wu

    /* by zhou */
    $cat['grade'] = !empty($_POST['grade']) ? intval($_POST['grade']) : 0;
    $cat['filter_attr'] = !empty($_POST['filter_attr']) ? implode(',', array_unique(array_diff($_POST['filter_attr'], array(0)))) : 0;

    $cat['cat_recommend'] = !empty($_POST['cat_recommend']) ? $_POST['cat_recommend'] : array();

    if (cat_exists($cat['cat_name'], $cat['parent_id'])) {
        /* 同级别下不能有重复的分类名称 */
        $result['error'] = 2;
        $result['message'] = $_LANG['cate_name_not_repeat'];
        die($json->encode($result));
    }

    if ($cat['grade'] > 10 || $cat['grade'] < 0) {
        /* 价格区间数超过范围 */
        $result['error'] = 2;
        $result['message'] = $_LANG['price_section_range'];
        die($json->encode($result));     
    }

    /* 入库的操作 */
    $cat_name = explode(',', $cat['cat_name']);

    if (count($cat_name) > 1) {

        $cat['is_show_merchants'] = !empty($_POST['is_show_merchants']) ? intval($_POST['is_show_merchants']) : 0;

        get_bacth_category($cat_name, $cat, $adminru['ru_id']);

        clear_cache_files();    // 清除缓存
        
    } else {
        if ($db->autoExecute($ecs->table('category'), $cat) !== false) {
            $cat_id = $db->insert_id();
            
            $cache['category']['type'] = "add_edit";
            $cache['category']['is_show'] = 1;
            $cache['category']['cache_path'] = "data/sc_file/category/";
            get_admin_seller_static_cache($cache);

            if ($cat['show_in_nav'] == 1) {
                $vieworder = $db->getOne("SELECT max(vieworder) FROM " . $ecs->table('nav') . " WHERE type = 'middle'");
                $vieworder += 2;
                //显示在自定义导航栏中
                $sql = "INSERT INTO " . $ecs->table('nav') .
                        " (name,ctype,cid,ifshow,vieworder,opennew,url,type)" .
                        " VALUES('" . $cat['cat_name'] . "', 'c', '" . $db->insert_id() . "','1','$vieworder','0', '" . build_uri('category', array('cid' => $cat_id), $cat['cat_name']) . "','middle')";
                $db->query($sql);
            }

            admin_log($_POST['cat_name'], 'add', 'category');   // 记录管理员操作
            //ecmoban模板堂 --zhuo start
            $dt_list = isset($_POST['document_title']) ? $_POST['document_title'] : array();
            $dt_id = isset($_POST['dt_id']) ? $_POST['dt_id'] : array();
            get_documentTitle_insert_update($dt_list, $cat_id, $dt_id);
            //ecmoban模板堂 --zhuo end

            clear_cache_files();    // 清除缓存
        }
    }
    
    $level_limit = 3;
    $category_level = array();

    for ($i = 1; $i <= $level_limit; $i++) {
        $category_list = array();
        if ($i == 1) {
            $category_list = get_category_list();
        }
        $smarty->assign('cat_level', $i);
        $smarty->assign('category_list', $category_list);
        $category_level[$i] = $smarty->fetch('templates/library/get_select_category.lbi');
    }
    
    $smarty->assign('category_level', $category_level);    
    $smarty->assign("temp", "cate_reload");
    $result['message'] = $_LANG['cat_add_success'];
    $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');
    die($json->encode($result));
}
/*--------------------------------------------------------*/
// 上传视频
/*--------------------------------------------------------*/
elseif ($_REQUEST['act'] == 'video_box') {
    $json = new JSON;
    $result = array('error' => 0, 'message' => '', 'content' => '');

    $smarty->assign("temp", "video_box_load");
    $result['content'] = $GLOBALS['smarty']->fetch('library/dialog.lbi');

    die($json->encode($result));
}

//查询区域地区列表
function ajax_get_area_list($ra_id = 0 , $region_ids = array()){
	$sql = "select r.region_id, r.region_name from " .$GLOBALS['ecs']->table('merchants_region_info'). " as mri" . 
			" left join " .$GLOBALS['ecs']->table('region'). " as r on mri.region_id = r.region_id" .
			" where mri.ra_id = '$ra_id'";
	$res = $GLOBALS['db']->getAll($sql);		
        if(!empty($region_ids) && !empty($res)){
            foreach($res as $k=>$v){
                if(in_array($v['region_id'], $region_ids)){
                    $res[$k]['is_checked'] = 1;
                }
            }
        }		
	return $res;
}
//获取品牌列表
function getBrandList($brand_ids){ 
        $where = ' WHERE be.is_recommend=1 AND b.is_show ORDER BY b.sort_order asc ';
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('brand') . " as b left join " . $GLOBALS['ecs']->table('brand_extend') . " AS be on b.brand_id=be.brand_id " . $where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);
        $filter = page_and_size($filter,4);
        $where .= "LIMIT " . $filter['start'].",".$filter['page_size'];
        $sql = "SELECT b.brand_id,b.brand_name,b.brand_logo FROM " . $GLOBALS['ecs']->table('brand') . " as b left join " . $GLOBALS['ecs']->table('brand_extend') . " AS be on b.brand_id=be.brand_id " . $where;
        $val = '';
        $recommend_brands = $GLOBALS['db']->getAll($sql);
        if($brand_ids){
            $brand_ids = explode(',', $brand_ids);
        }
        foreach ($recommend_brands AS $key => $val) {
            $val['brand_logo'] = DATA_DIR . '/brandlogo/' . $val['brand_logo'];
            $recommend_brands[$key]['brand_logo'] = get_image_path($val['brand_id'], $val['brand_logo']);
            //OSS文件存储ecmoban模板堂 --zhuo end   
            $recommend_brands[$key]['selected'] = 0;
            if(!empty($brand_ids)){
                foreach($brand_ids as $v){
                    if($v == $val['brand_id']){
                       $recommend_brands[$key]['selected'] = 1 ;
                    }
                }
            }
        }
        $filter['page_arr'] = seller_page($filter,$filter['page'],14);
        return array('list' => $recommend_brands, 'filter' => $filter);
}

/* 地区列表 */

function get_transport_area($tid = 0) {
    $where = "";
    if ($tid == 0) {
        global $admin_id;
        $where .= " AND admin_id = '$admin_id' ";
    }
    $sql = " SELECT * FROM " . $GLOBALS['ecs']->table('goods_transport_extend') . " WHERE tid = '$tid' $where ORDER BY id DESC ";
    $transport_area = $GLOBALS['db']->getAll($sql);
    foreach ($transport_area as $key => $val) {
        if (!empty($val['top_area_id']) && !empty($val['area_id'])) {
            $area_map = array();
            $top_area_arr = explode(',', $val['top_area_id']);
            foreach ($top_area_arr as $k => $v) {
                $top_area = get_table_date("region", "region_id='$v'", array('region_name'), 2);
                $sql = " SELECT region_name FROM " . $GLOBALS['ecs']->table('region') . " WHERE parent_id = '$v' AND region_id IN ($val[area_id]) ";
                $area_arr = $GLOBALS['db']->getCol($sql);
                $area_list = implode(',', $area_arr);
                $area_map[$k]['top_area'] = $top_area;
                $area_map[$k]['area_list'] = $area_list;
            }
            $transport_area[$key]['area_map'] = $area_map;
        }
    }
    return $transport_area;
}

/* 获取地区 */

function get_area_list($area_id = '') {
    $area_list = '';
    if (!empty($area_id)) {
        $sql = " SELECT region_name FROM " . $GLOBALS['ecs']->table('region') . " WHERE region_id IN ($area_id) ";
        $area_list = $GLOBALS['db']->getCol($sql);
        $area_list = implode(',', $area_list);
    }
    return $area_list;
}

/* 快递列表 */

function get_transport_express($tid = 0) {
    $where = "";
    if ($tid == 0) {
        global $admin_id;
        $where .= " AND admin_id = '$admin_id' ";
    }
    $sql = " SELECT * FROM " . $GLOBALS['ecs']->table('goods_transport_express') . " WHERE tid = '$tid' $where ORDER BY id DESC ";
    $transport_express = $GLOBALS['db']->getAll($sql);
    foreach ($transport_express as $key => $val) {
        $transport_express[$key]['express_list'] = get_express_list($val['shipping_id']);
    }
    return $transport_express;
}

/* 获取快递 */

function get_express_list($shipping_id = '') {
    $express_list = '';
    if (!empty($shipping_id)) {
        $sql = " SELECT shipping_name FROM " . $GLOBALS['ecs']->table('shipping') . " WHERE shipping_id IN ($shipping_id) ";
        $express_list = $GLOBALS['db']->getCol($sql);
        $express_list = implode(',', $express_list);
    }
    return $express_list;
}


?>