<?php

/**
 * DSC 商品库管理程序
 * ============================================================================
 * * 版权所有 2005-2017 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liu $
 * $Id: goods_lib.php 17217 2017-07-12 09:29:08 liu $
*/
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . '/' . ADMIN_PATH . '/includes/lib_goods.php');
include_once(ROOT_PATH . '/includes/cls_image.php');
$image = new cls_image($_CFG['bgcolor']);
$exc = new exchange($ecs->table('goods_lib'), $db, 'goods_id', 'goods_name');
//$exc_extend = new exchange($ecs->table('goods_lib_extend'), $db, 'goods_id', 'extend_id');
$exc_gallery = new exchange($ecs->table('goods_lib_gallery'), $db, 'img_id', 'goods_id');

/* 管理员ID */
$admin_id = get_admin_id();

//ecmoban模板堂 --zhuo start
$adminru = get_admin_ru_id();
if($adminru['ru_id'] == 0){
        $smarty->assign('priv_ru',   1);
}else{
        $smarty->assign('priv_ru',   0);
}
$smarty->assign('review_goods',   $GLOBALS['_CFG']['review_goods']);
//ecmoban模板堂 --zhuo end

/*------------------------------------------------------ */
//-- 商品列表
/*------------------------------------------------------ */

if ($_REQUEST['act'] == 'list')
{
    admin_priv('goods_lib_list');
    
	lib_get_del_goodsimg_null();
	lib_get_del_goods_gallery();

    $cat_id = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
    $smarty->assign('menu_select', array('action' => '02_cat_and_goods', 'current' => '01_goods_list'));

    /* 模板赋值 */
    $smarty->assign('ur_here', $_LANG['20_goods_lib']);
    $smarty->assign('lang',         $_LANG);
    $smarty->assign('list_type',    $_REQUEST['act'] == 'list' ? 'goods' : 'trash');

    $goods_list = lib_goods_list();
    $smarty->assign('goods_list',   $goods_list['goods']);
    $smarty->assign('filter',       $goods_list['filter']);
    $smarty->assign('record_count', $goods_list['record_count']);
    $smarty->assign('page_count',   $goods_list['page_count']);
    $smarty->assign('full_page',    1);
    
    /* 排序标记 */
    $sort_flag  = sort_flag($goods_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);
	
    $smarty->assign('nowTime', gmtime());
    set_default_filter(); //设置默认筛选
    
    $smarty->assign('cfg', $_CFG);
    $smarty->display('goods_lib_list.dwt');
}

/*------------------------------------------------------ */
//-- 添加新商品 编辑商品
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit') 
{
	admin_priv('goods_lib_list');
	lib_get_del_goodsimg_null();
	lib_get_del_goods_gallery();
	
    $is_add = $_REQUEST['act'] == 'add'; // 添加还是编辑的标识
	
    include_once(ROOT_PATH . 'includes/fckeditor/fckeditor.php'); // 包含 html editor 类文件

    $properties = empty($_REQUEST['properties']) ? 0 : intval($_REQUEST['properties']);
    $smarty->assign('properties', $properties);

    /* 如果是安全模式，检查目录是否存在 */
    if (ini_get('safe_mode') == 1 && (!file_exists('../' . IMAGE_DIR . '/' . date('Ym')) || !is_dir('../' . IMAGE_DIR . '/' . date('Ym')))) {
        if (@!mkdir('../' . IMAGE_DIR . '/' . date('Ym'), 0777)) {
            $warning = sprintf($_LANG['safe_mode_warning'], '../' . IMAGE_DIR . '/' . date('Ym'));
            $smarty->assign('warning', $warning);
        }
    }

    /* 如果目录存在但不可写，提示用户 */ elseif (file_exists('../' . IMAGE_DIR . '/' . date('Ym')) && file_mode_info('../' . IMAGE_DIR . '/' . date('Ym')) < 2) {
        $warning = sprintf($_LANG['not_writable_warning'], '../' . IMAGE_DIR . '/' . date('Ym'));
        $smarty->assign('warning', $warning);
    }
    
    $adminru = get_admin_ru_id();
    
    $goods_id = isset($_REQUEST['goods_id']) && !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
	
    /* 取得商品信息 */
    if ($is_add) {
        
        $goods = array(
            'goods_id' => 0,
            'user_id' => 0,
            'goods_desc' => '',
            'goods_shipai' => '',
            'cat_id' => '0',
            'brand_id' => 0,
            'is_on_sale' => '1',
            'is_alone_sale' => '1',
            'is_shipping' => '0',
            'other_cat' => array(), // 扩展分类
            'goods_type' => 0, // 商品类型
            'shop_price' => 0,
            'market_price' => 0,
            'goods_weight' => 0,
            'goods_extend' => array('is_reality' => 0, 'is_return' => 0, 'is_fast' => 0)//by wang
        );

        /* 图片列表 */
        $img_list = array();
    } else {
        /* 商品信息 */
        $goods = $db->getRow(" SELECT * FROM ".$ecs->table('goods_lib')." WHERE goods_id = '$goods_id' LIMIT 1 ");

        if (empty($goods)) {
            $link[] = array('href' => 'goods_lib.php?act=list', 'text' => $_LANG['back_goods_list']);
            sys_msg($_LANG['lab_not_goods'], 0, $link);
        }

        //当前域名协议
        $http = $GLOBALS['ecs']->http();
        
        //图片显示
        $goods['goods_thumb'] = get_image_path($goods['goods_id'], $goods['goods_thumb'], true);
        if(strpos($goods['goods_thumb'], $http) === false){
            $goods['goods_thumb'] = $GLOBALS['ecs']->url() . $goods['goods_thumb'];
        }
		
        if (empty($goods) === true) {
            /* 默认值 */
            $goods = array(
                'goods_id' => 0,
                'user_id' => 0,
                'goods_desc' => '',
                'cat_id' => 0,
                'other_cat' => array(), // 扩展分类
                'goods_type' => 0, // 商品类型
                'shop_price' => 0,
                'market_price' => 0,
                'goods_weight' => 0,
                'goods_extend' => array('is_reality' => 0, 'is_return' => 0, 'is_fast' => 0)
            );
        }

        $goods['goods_extend'] = get_goods_extend($goods['goods_id']);

        /* 获取商品类型存在规格的类型 */
        $specifications = get_goods_type_specifications();
        $goods['specifications_id'] = $specifications[$goods['goods_type']];
        $_attribute = get_goods_specifications_list($goods['goods_id']);
        $goods['_attribute'] = empty($_attribute) ? '' : 1;

        /* 根据商品重量的单位重新计算 */
        if ($goods['goods_weight'] > 0) {
            $goods['goods_weight_by_unit'] = ($goods['goods_weight'] >= 1) ? $goods['goods_weight'] : ($goods['goods_weight'] / 0.001);
        }

        if (!empty($goods['goods_brief'])) {
            $goods['goods_brief'] = $goods['goods_brief'];
        }
        if (!empty($goods['keywords'])) {
            $goods['keywords'] = $goods['keywords'];
        }

        /* 商品图片路径 */
        if (isset($GLOBALS['shop_id']) && ($GLOBALS['shop_id'] > 10) && !empty($goods['original_img'])) {
            $goods['goods_img'] = get_image_path($goods_id, $goods['goods_img']);
            $goods['goods_thumb'] = get_image_path($goods_id, $goods['goods_thumb'], true);
        }

        /* 图片列表 */
        $sql = "SELECT * FROM " . $ecs->table('goods_lib_gallery') . " WHERE goods_id = '$goods_id' ORDER BY img_desc";
        $img_list = $db->getAll($sql);
        
        //当前域名协议
        $http = $GLOBALS['ecs']->http();

        /* 格式化相册图片路径 */
        if (isset($GLOBALS['shop_id']) && ($GLOBALS['shop_id'] > 0)) {
            foreach ($img_list as $key => $gallery_img) {
                
                $img_list[$key] = $gallery_img;
                
                if(!empty($gallery_img['external_url'])){
                    $img_list[$key]['img_url'] = $gallery_img['external_url'];
                    $img_list[$key]['thumb_url'] = $gallery_img['external_url'];
                }else{
                    
                    //图片显示
                    $gallery_img['img_original'] = get_image_path($gallery_img['goods_id'], $gallery_img['img_original'], true);
                    if (strpos($gallery_img['img_original'], $http) === false) {
                        $gallery_img['img_original'] = $GLOBALS['ecs']->url() . $gallery_img['img_original'];
                    }

                    $img_list[$key]['img_url'] = $gallery_img['img_original'];
                    
                    $gallery_img['thumb_url'] = get_image_path($gallery_img['goods_id'], $gallery_img['thumb_url'], true);
                    if (strpos($gallery_img['thumb_url'], $http) === false) {
                        $gallery_img['thumb_url'] = $GLOBALS['ecs']->url() . $gallery_img['thumb_url'];
                    }
                    
                    $img_list[$key]['thumb_url'] = $gallery_img['thumb_url'];
                }
            }
        } else {
            foreach ($img_list as $key => $gallery_img) {
                
                $img_list[$key] = $gallery_img;
                
                if(!empty($gallery_img['external_url'])){
                    $img_list[$key]['img_url'] = $gallery_img['external_url'];
                    $img_list[$key]['thumb_url'] = $gallery_img['external_url'];
                }else{
                    $gallery_img['thumb_url'] = get_image_path($gallery_img['goods_id'], $gallery_img['thumb_url'], true);
                    if (strpos($gallery_img['thumb_url'], $http) === false) {
                        $gallery_img['thumb_url'] = $GLOBALS['ecs']->url() . $gallery_img['thumb_url'];
                    }
                    
                    $img_list[$key]['thumb_url'] = $gallery_img['thumb_url'];
                }
            }
        }
        $img_desc = array();
        foreach ($img_list as $k => $v) {
            $img_desc[] = $v['img_desc'];
        }
        
        @$img_default = min($img_desc);
        $min_img_id = $db->getOne(" SELECT img_id   FROM " . $ecs->table("goods_lib_gallery") . " WHERE goods_id = '".$goods_id."' AND img_desc = '$img_default' ORDER BY img_desc   LIMIT 1");
        $smarty->assign('min_img_id', $min_img_id);
    }
	
	$smarty->assign('ru_id', $adminru['ru_id']);

    /* 拆分商品名称样式 */
    $goods_name_style = explode('+', empty($goods['goods_name_style']) ? '+' : $goods['goods_name_style']);
	
	//OSS文件存储ecmoban模板堂 --zhuo start
    if ($GLOBALS['_CFG']['open_oss'] == 1) {
        $bucket_info = get_bucket_info();
        if ($goods['goods_desc']) {
            $desc_preg = get_goods_desc_images_preg($bucket_info['endpoint'], $goods['goods_desc']);
            $goods['goods_desc'] = $desc_preg['goods_desc'];
        }
    }
    //OSS文件存储ecmoban模板堂 --zhuo end

    /* 创建 html editor */
    create_html_editor('goods_desc', $goods['goods_desc']);
    create_html_editor2('goods_shipai', 'goods_shipai', $goods['goods_shipai']);
	
    $smarty->assign('integral_scale', $_CFG['integral_scale']);
	
	//取得商品品牌名称
	$sql = 'SELECT brand_name FROM ' . $GLOBALS['ecs']->table('brand') . " WHERE brand_id = '".$goods['brand_id']."' ORDER BY sort_order ";
	$brand_name = addslashes($GLOBALS['db']->getOne($sql));

    /* 模板赋值 */
    $smarty->assign('code', $code);
    $smarty->assign('ur_here', $is_add ? (empty($code) ? $_LANG['02_goods_add'] : $_LANG['51_virtual_card_add']) : ($_REQUEST['act'] == 'edit' ? $_LANG['edit_goods'] : $_LANG['copy_goods']));
    $smarty->assign('action_link', list_link($is_add, $code));
    $smarty->assign('goods', $goods);
    $smarty->assign('goods_name_color', $goods_name_style[0]);
    $smarty->assign('goods_name_style', $goods_name_style[1]);
	$smarty->assign('brand_list', search_brand_list($goods_id));
    $smarty->assign('brand_name', $brand_name);
    $smarty->assign('unit_list', get_unit_list());
    $smarty->assign('weight_unit', $is_add ? '1' : ($goods['goods_weight'] >= 1 ? '1' : '0.001'));
    $smarty->assign('cfg', $_CFG);
    $smarty->assign('form_act', $is_add ? 'insert' : ($_REQUEST['act'] == 'edit' ? 'update' : 'insert'));
    $smarty->assign('is_add', true);
    $smarty->assign('img_list', $img_list);
    $smarty->assign('goods_type_list', goods_type_list($goods['goods_type'], $goods['goods_id'], 'array'));
    $smarty->assign('goods_type_name', $GLOBALS['db']->getOne(" SELECT cat_name FROM " . $GLOBALS['ecs']->table('goods_type') . " WHERE cat_id = '$goods[goods_type]' "));
    $smarty->assign('gd', gd_version());
    $smarty->assign('thumb_width', $_CFG['thumb_width']);
    $smarty->assign('thumb_height', $_CFG['thumb_height']);
    
    /* 获取下拉列表 by wu start */
    //设置商品分类
    $level_limit = 3;
    $category_level = array();

    if ($is_add) {
        for ($i = 1; $i <= $level_limit; $i++) {
            $category_list = array();
            if ($i == 1) {
                $category_list = get_category_list();
            }
            $smarty->assign('cat_level', $i);
            $smarty->assign('category_list', $category_list);
            $category_level[$i] = $smarty->fetch('templates/library/get_select_category.lbi');
        }
    }else{
        $parent_cat_list = get_select_category($goods['cat_id'], 1, true);
        
        for ($i = 1; $i <= $level_limit; $i++) {
            $category_list = array();
            if (isset($parent_cat_list[$i])) {
                $category_list = get_category_list($parent_cat_list[$i], 0, '', 0, $i);
            } elseif ($i == 1) {
                if($goods['user_id']){
                    $category_list = get_category_list(0, 0, '', 0, $i);
                }else{
                    $category_list = get_category_list();
                }
            }
            $smarty->assign('cat_level', $i);
            $smarty->assign('category_list', $category_list);
            $category_level[$i] = $smarty->fetch('templates/library/get_select_category.lbi');
        }		
	}

	$cat_list = get_goods_lib_cat(0, $goods['cat_id'], false);
	$smarty->assign('goods_lib_cat', $cat_list);
    $smarty->assign('category_level', $category_level);
    /* 获取下拉列表 by wu end */

    set_default_filter($goods_id, 0, 0); //设置默认筛选
    
    /* 显示商品信息页面 */
    assign_query_info();
    $smarty->display('goods_lib_info.dwt');
}

/*------------------------------------------------------ */
//-- 获取分类列表
/*------------------------------------------------------ */

 elseif ($_REQUEST['act'] == 'get_select_category_pro') 
{
    $goods_id = empty($_REQUEST['goods_id']) ? 0 : intval($_REQUEST['goods_id']);
    $cat_id = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
    $cat_level = empty($_REQUEST['cat_level']) ? 0 : intval($_REQUEST['cat_level']);
    $result = array('error' => 0, 'message' => '', 'content' => '');

    $goods = get_admin_goods_info($goods_id, array('user_id'));
    $seller_shop_cat = seller_shop_cat($goods['user_id']);
    
    $smarty->assign('cat_id', $cat_id);
    $smarty->assign('cat_level', $cat_level + 1);
    $smarty->assign('category_list', get_category_list($cat_id, 2, $seller_shop_cat, $goods['user_id'], $cat_level + 1));
    $result['content'] = $smarty->fetch('templates/library/get_select_category.lbi');
    die(json_encode($result));
}

/* 设置常用分类 */
 elseif ($_REQUEST['act'] == 'set_common_category_pro') {
    $cat_id = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
    $result = array('error' => 0, 'message' => '', 'content' => '');

    $level_limit = 3;
    $category_level = array();
    $parent_cat_list = get_select_category($cat_id, 1, true);

    for ($i = 1; $i <= $level_limit; $i++) {
        $category_list = array();
        if (isset($parent_cat_list[$i])) {
            $category_list = get_category_list($parent_cat_list[$i]);
        } elseif ($i == 1) {
            $category_list = get_category_list();
        }
        $smarty->assign('cat_level', $i);
        $smarty->assign('category_list', $category_list);
        $category_level[$i] = $smarty->fetch('templates/library/get_select_category.lbi');
    }

    $smarty->assign('cat_id', $cat_id);
    $result['content'] = $category_level;
    die(json_encode($result));
}

/*------------------------------------------------------ */
//-- 插入商品 更新商品
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update')
{
    $code = empty($_REQUEST['extension_code']) ? '' : trim($_REQUEST['extension_code']);

    /* 是否处理缩略图 */
    $proc_thumb = (isset($GLOBALS['shop_id']) && $GLOBALS['shop_id'] > 0)? false : true;

    admin_priv('goods_lib_list'); // 检查权限

    /* 插入还是更新的标识 */
    $is_insert = $_REQUEST['act'] == 'insert';
    
    $original_img = empty($_REQUEST['original_img']) ? '' : trim($_REQUEST['original_img']);
    $goods_img = empty($_REQUEST['goods_img']) ? '' : trim($_REQUEST['goods_img']);
    $goods_thumb = empty($_REQUEST['goods_thumb']) ? '' : trim($_REQUEST['goods_thumb']);
    
    /* 商品外链图 start */
    $is_img_url = empty($_REQUEST['is_img_url']) ? 0 : intval($_REQUEST['is_img_url']);
    $_POST['goods_img_url'] = isset($_POST['goods_img_url']) && !empty($_POST['goods_img_url']) ? trim($_POST['goods_img_url']) : '';
    
    if (!empty($_POST['goods_img_url']) && ($_POST['goods_img_url'] != 'http://') && (strpos($_POST['goods_img_url'], 'http://') !== false || strpos($_POST['goods_img_url'], 'https://') !== false) && $is_img_url == 1) {
        $admin_temp_dir = "seller";
        $admin_temp_dir = ROOT_PATH . "temp" . '/' . $admin_temp_dir . '/' . "admin_" . $admin_id;

        if (!file_exists($admin_temp_dir)) {
            make_dir($admin_temp_dir);
        }
        if(get_http_basename($_POST['goods_img_url'], $admin_temp_dir)){
            $original_img = $admin_temp_dir ."/". basename($_POST['goods_img_url']);
        }
        if ($original_img === false) {
            sys_msg($image->error_msg(), 1, array(), false);
        }
        
        $goods_img = $original_img;   // 商品图片
		
        /* 复制一份相册图片 */
        /* 添加判断是否自动生成相册图片 */
        if ($_CFG['auto_generate_gallery']) {
            $img = $original_img;   // 相册图片
            $pos = strpos(basename($img), '.');
            $newname = dirname($img) . '/' . $image->random_filename() . substr(basename($img), $pos);
            if (!copy($img, $newname)) {
                sys_msg('fail to copy file: ' . realpath('../' . $img), 1, array(), false);
            }
            $img = $newname;

            $gallery_img = $img;
            $gallery_thumb = $img;
        }

        // 如果系统支持GD，缩放商品图片，且给商品图片和相册图片加水印
        if ($proc_thumb && $image->gd_version() > 0 || $is_url_goods_img) {

            if (empty($is_url_goods_img)) {

                $img_wh = $image->get_width_to_height($goods_img, $GLOBALS['_CFG']['image_width'], $GLOBALS['_CFG']['image_height']);
                $GLOBALS['_CFG']['image_width'] = isset($img_wh['image_width']) ? $img_wh['image_width'] : $GLOBALS['_CFG']['image_width'];
                $GLOBALS['_CFG']['image_height'] = isset($img_wh['image_height']) ? $img_wh['image_height'] : $GLOBALS['_CFG']['image_height'];
                
                // 如果设置大小不为0，缩放图片
                $goods_img = $image->make_thumb(array('img' => $goods_img, 'type' => 1), $GLOBALS['_CFG']['image_width'], $GLOBALS['_CFG']['image_height']);
                if ($goods_img === false) {
                    sys_msg($image->error_msg(), 1, array(), false);
                }
                
                $gallery_img = $image->make_thumb(array('img' => $gallery_img, 'type' => 1), $GLOBALS['_CFG']['image_width'], $GLOBALS['_CFG']['image_height']);
                
                if ($gallery_img === false) {
                    sys_msg($image->error_msg(), 1, array(), false);
                }
                
                // 加水印
                if (intval($_CFG['watermark_place']) > 0 && !empty($GLOBALS['_CFG']['watermark'])) {
                    if ($image->add_watermark($goods_img, '', $GLOBALS['_CFG']['watermark'], $GLOBALS['_CFG']['watermark_place'], $GLOBALS['_CFG']['watermark_alpha']) === false) {
                        sys_msg($image->error_msg(), 1, array(), false);
                    }
                    /* 添加判断是否自动生成相册图片 */
                    if ($_CFG['auto_generate_gallery']) {
                        if ($image->add_watermark($img, '', $GLOBALS['_CFG']['watermark'], $GLOBALS['_CFG']['watermark_place'], $GLOBALS['_CFG']['watermark_alpha']) === false) {
                            sys_msg($image->error_msg(), 1, array(), false);
                        }
                    }
                }
            }

            // 相册缩略图
            /* 添加判断是否自动生成相册图片 */
            if ($_CFG['auto_generate_gallery']) {
                if ($_CFG['thumb_width'] != 0 || $_CFG['thumb_height'] != 0) {
                    $gallery_thumb = $image->make_thumb(array('img' => $img, 'type' => 1), $GLOBALS['_CFG']['thumb_width'], $GLOBALS['_CFG']['thumb_height']);
                    if ($gallery_thumb === false) {
                        sys_msg($image->error_msg(), 1, array(), false);
                    }
                }
            }
        }
        
        // 未上传，如果自动选择生成，且上传了商品图片，生成所略图
        if ($proc_thumb && !empty($original_img))
        {
            // 如果设置缩略图大小不为0，生成缩略图
            if ($_CFG['thumb_width'] != 0 || $_CFG['thumb_height'] != 0)
            {
                $goods_thumb = $image->make_thumb(array('img' => $original_img, 'type' => 1), $GLOBALS['_CFG']['thumb_width'],  $GLOBALS['_CFG']['thumb_height']);
                if ($goods_thumb === false)
                {
                    sys_msg($image->error_msg(), 1, array(), false);
                }
            }
            else
            {
                $goods_thumb = $original_img;
            }
        }
    }
    /* 商品外链图 end */
	
    $goods_img_id=!empty($_REQUEST['img_id'])  ? $_REQUEST['img_id']:''; //相册

    /* 处理商品数据 */
    $shop_price = !empty($_POST['shop_price']) ? trim($_POST['shop_price']) : 0;
    $shop_price = floatval($shop_price);
    $market_price = !empty($_POST['market_price']) ? trim($_POST['market_price']) : 0;
    $market_price = floatval($market_price);
    $cost_price = !empty($_POST['cost_price']) ? trim($_POST['cost_price']) : 0;
    $cost_price = floatval($cost_price);
    $review_status = isset($_POST['review_status']) ? intval($_POST['review_status']) : 5;
    $review_content = isset($_POST['review_content']) && !empty($_POST['review_content']) ? addslashes(trim($_POST['review_content'])) : '';
    $goods_weight = !empty($_POST['goods_weight']) ? $_POST['goods_weight'] * $_POST['weight_unit'] : 0;
    $bar_code = isset($_POST['bar_code']) && !empty($_POST['bar_code']) ? trim($_POST['bar_code']) : '';
    $goods_name_style = $_POST['goods_name_color'] . '+' . $_POST['goods_name_style'];
    $other_catids = isset($_POST['other_catids']) ? trim($_POST['other_catids']) : '';
    $lib_cat_id = isset($_POST['lib_cat_id']) ? intval($_POST['lib_cat_id']) : 0;
    $is_on_sale = isset($_POST['is_on_sale']) ? intval($_POST['is_on_sale']) : 0;

    $catgory_id = empty($_POST['cat_id']) ? '' : intval($_POST['cat_id']);
    //常用分类 by wu
    if (empty($catgory_id) && !empty($_POST['common_category'])) {
        $catgory_id = intval($_POST['common_category']);
    }

    $brand_id = empty($_POST['brand_id']) ? '' : intval($_POST['brand_id']);

    $adminru = get_admin_ru_id();

    $model_price = isset($_POST['model_price']) ? intval($_POST['model_price']) : 0;
    $model_inventory = isset($_POST['model_inventory']) ? intval($_POST['model_inventory']) : 0;
    $model_attr = isset($_POST['model_attr']) ? intval($_POST['model_attr']) : 0;
	$goods_name = trim($_POST['goods_name']);
    //by guan start
    $pin = new pin();
    $pinyin = $pin->Pinyin($goods_name, 'UTF8');
    //by guan end

    /* 入库 */
    if ($is_insert)
    {
        $sql = "INSERT INTO " . $ecs->table('goods_lib') . " (goods_name, goods_name_style, bar_code, " .
                " cat_id, lib_cat_id, brand_id, shop_price, market_price, cost_price, goods_img, goods_thumb, original_img, keywords, goods_brief, " .
                " goods_weight, goods_desc, desc_mobile, add_time, last_update, goods_type, pinyin_keyword, is_on_sale " .
                ")" .
                "VALUES ('$goods_name', '$goods_name_style', '$bar_code', '$catgory_id', '$lib_cat_id', " .
                " '$brand_id', '$shop_price', '$market_price', '$cost_price', '$goods_img', '$goods_thumb', '$original_img', '$_POST[keywords]', '$_POST[goods_brief]', " .
                " '$goods_weight', '$_POST[goods_desc]', '$_POST[desc_mobile]', '" . gmtime() . "', '" . gmtime() . "', '$goods_type', '$pinyin', '$is_on_sale' " .
                ")";

        //库存日志
        $not_number = !empty($goods_number) ? 1 : 0;
        $number = "+ " . $goods_number;
        $use_storage = 7;
    }
    else
    {
        $_REQUEST['goods_id'] = isset($_REQUEST['goods_id']) && !empty($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;

		
        get_goods_file_content($_REQUEST['goods_id'], $GLOBALS['_CFG']['goods_file'], $adminru['ru_id'], $review_goods, $model_attr); //编辑商品需审核通过

        $sql = "UPDATE " . $ecs->table('goods_lib') . " SET " .
                "goods_name = '$goods_name', " .
                "goods_name_style = '$goods_name_style', " .
                "bar_code = '$bar_code', " .
                "cat_id = '$catgory_id', " .
				"lib_cat_id = '$lib_cat_id', " .
                "brand_id = '$brand_id', " .
                "shop_price = '$shop_price', " .
                "market_price = '$market_price', " .
				"cost_price = '$cost_price', " .
                "pinyin_keyword = '$pinyin', " .
				"is_on_sale = '$is_on_sale', " ;

        /* 如果有上传图片，需要更新数据库 */
        if ($goods_img)
        {
            $sql .= "goods_img = '$goods_img', original_img = '$original_img', ";
        }
        if ($goods_thumb)
        {
            $sql .= "goods_thumb = '$goods_thumb', ";
        }
        if ($code != '')
        {
            $sql .= "is_real=0, extension_code='$code', ";
        }
        
        $sql .= "keywords = '$_POST[keywords]', " .
                "goods_brief = '$_POST[goods_brief]', " .
                "goods_weight = '$goods_weight'," .
                "goods_desc = '$_POST[goods_desc]', " .
                "desc_mobile = '$_POST[desc_mobile]', " .
                "last_update = '". gmtime() ."' ".
                "WHERE goods_id = '" .$_REQUEST['goods_id']. "' LIMIT 1 ";
    }
    $res = $db->query($sql);

    $goods_id = $is_insert ? $db->insert_id() : $_REQUEST['goods_id'];
	
    //扩展信息 by wu start
    $extend_arr = array();
    $extend_arr['width'] = isset($_POST['width']) ? trim($_POST['width']) : ''; //宽度
    $extend_arr['height'] = isset($_POST['height']) ? trim($_POST['height']) : ''; //高度
    $extend_arr['depth'] = isset($_POST['depth']) ? trim($_POST['depth']) : ''; //深度
    $extend_arr['origincountry'] = isset($_POST['origincountry']) ? trim($_POST['origincountry']) : ''; //产国
    $extend_arr['originplace'] = isset($_POST['originplace']) ? trim($_POST['originplace']) : ''; //产地
    $extend_arr['assemblycountry'] = isset($_POST['assemblycountry']) ? trim($_POST['assemblycountry']) : ''; //组装国
    $extend_arr['barcodetype'] = isset($_POST['barcodetype']) ? trim($_POST['barcodetype']) : ''; //条码类型
    $extend_arr['catena'] = isset($_POST['catena']) ? trim($_POST['catena']) : ''; //产品系列
    $extend_arr['isbasicunit'] = isset($_POST['isbasicunit']) ? intval($_POST['isbasicunit']) : 0; //是否是基本单元
    $extend_arr['packagetype'] = isset($_POST['packagetype']) ? trim($_POST['packagetype']) : ''; //包装类型
    $extend_arr['grossweight'] = isset($_POST['grossweight']) ? trim($_POST['grossweight']) : ''; //毛重
    $extend_arr['netweight'] = isset($_POST['netweight']) ? trim($_POST['netweight']) : ''; //净重
    $extend_arr['netcontent'] = isset($_POST['netcontent']) ? trim($_POST['netcontent']) : ''; //净含量
    $extend_arr['licensenum'] = isset($_POST['licensenum']) ? trim($_POST['licensenum']) : ''; //生产许可证
    $extend_arr['healthpermitnum'] = isset($_POST['healthpermitnum']) ? trim($_POST['healthpermitnum']) : ''; //卫生许可证
    $db->autoExecute($ecs->table('goods_extend'), $extend_arr, "UPDATE", "goods_id = '$goods_id'");
    //扩展信息 by wu end	

    /* 记录日志 */
    if ($is_insert) {    
        admin_log($_POST['goods_name'], 'add', 'goods_lib');
    } else {
        admin_log($_POST['goods_name'], 'edit', 'goods_lib');
    }

    if ($is_insert)
    {
        /* 处理相册图片 by wu */
        $thumb_img_id = $_SESSION['thumb_img_id'.$_SESSION['admin_id']];//处理添加商品时相册图片串图问题   by kong
        if($thumb_img_id){
            $sql = " UPDATE " . $ecs->table('goods_lib_gallery') . " SET goods_id = '" . $goods_id . "' WHERE goods_id = 0 AND img_id " . db_create_in($thumb_img_id) ;
            $db->query($sql);
        }
        unset($_SESSION['thumb_img_id'.$_SESSION['admin_id']]);//清楚临时$_COOKIE
    }
    
    /* 如果有图片，把商品图片加入图片相册 */
    if (!empty($_POST['goods_img_url']) && $is_img_url == 1) {
        /* 重新格式化图片名称 */
        $original_img = reformat_image_name('goods', $goods_id, $original_img, 'source');
        $goods_img = reformat_image_name('goods', $goods_id, $goods_img, 'goods');
        $goods_thumb = reformat_image_name('goods_thumb', $goods_id, $goods_thumb, 'thumb');
        
        // 处理商品图片
        $sql = " UPDATE " . $ecs->table('goods_lib') . " SET goods_thumb = '$goods_thumb', goods_img = '$goods_img', original_img = '$original_img' WHERE goods_id = '$goods_id' ";
        $db->query($sql);
    
        if (isset($img))
        {
            // 重新格式化图片名称
            if (empty($is_url_goods_img))
            {
                $img = reformat_image_name('gallery', $goods_id, $img, 'source');
                $gallery_img = reformat_image_name('gallery', $goods_id, $gallery_img, 'goods');
            }
            else
            {
                $img = $original_img;
                $gallery_img = $goods_img;
            }

            $gallery_thumb = reformat_image_name('gallery_thumb', $goods_id, $gallery_thumb, 'thumb');

            $sql = "INSERT INTO " . $ecs->table('goods_lib_gallery') . " (goods_id, img_url, thumb_url, img_original) " .
                    "VALUES ('$goods_id', '$gallery_img', '$gallery_thumb', '$img')";
            $db->query($sql);
        }

        get_oss_add_file(array($goods_img, $goods_thumb, $original_img, $gallery_img, $gallery_thumb, $img));
    }else{
        get_oss_add_file(array($goods_img, $goods_thumb, $original_img));
    }
    
    /* 清空缓存 */
    clear_cache_files();

    /* 提示页面 */
    $link = array();

    if ($is_insert) {
        $link[2] = add_link($code);
    }
    $link[3] = list_link($is_insert, $code);

    for ($i = 0; $i < count($link); $i++) {
        $key_array[] = $i;
    }
    krsort($link);
    $link = array_combine($key_array, $link);
    
    sys_msg($is_insert ? $_LANG['add_goods_ok'] : $_LANG['edit_goods_ok'], 0, $link);
}

/*------------------------------------------------------ */
//-- 批量操作 
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'batch')
{
    $code = empty($_REQUEST['extension_code'])? '' : trim($_REQUEST['extension_code']);

    /* 取得要操作的商品编号 */
    $goods_id = !empty($_POST['checkboxes']) ? join(',', $_POST['checkboxes']) : 0;
    
    if (isset($_POST['type']))
    {
        /* 上架 */
        if ($_POST['type'] == 'on_sale')
        {
            /* 检查权限 */
            admin_priv('goods_lib_list');
            lib_update_goods($goods_id, 'is_on_sale', '1');
        }

        /* 下架 */
        elseif ($_POST['type'] == 'not_on_sale')
        {
            /* 检查权限 */
            admin_priv('goods_lib_list');
            lib_update_goods($goods_id, 'is_on_sale', '0');
        }
		
        /* 删除 */
        elseif ($_POST['type'] == 'drop')
        {
            /* 检查权限 */
            admin_priv('goods_lib_list');

            lib_delete_goods($goods_id);

            /* 记录日志 */
            admin_log('', 'batch_remove', 'goods_lib');
        }		
    }

    /* 清除缓存 */
    clear_cache_files();

    if ($_POST['type'] == 'drop')
    {
        $link[] = array('href' => 'goods_lib.php?act=list', 'text' => $_LANG['20_goods_lib']);
    }
    else
    {
        $link[] = list_link(true, $code);
    }
    sys_msg($_LANG['batch_handle_ok'], 0, $link);
}

/*------------------------------------------------------ */
//-- 修改商品名称
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_goods_name')
{
    check_authz_json('goods_lib');

    $goods_id   = intval($_POST['id']);
    $goods_name = json_str_iconv(trim($_POST['val']));

    if ($exc->edit("goods_name = '$goods_name', last_update=" .gmtime(), $goods_id))
    {
        clear_cache_files();
        make_json_result(stripslashes($goods_name));
    }
}

elseif ($_REQUEST['act'] == 'check_goods_sn')
{
    check_authz_json('goods_lib');

    $goods_id = intval($_REQUEST['goods_id']);
    $goods_sn = htmlspecialchars(json_str_iconv(trim($_REQUEST['goods_sn'])));

    /* 检查是否重复 */
    if (!$exc->is_only('goods_sn', $goods_sn, $goods_id))
    {
        make_json_error($_LANG['goods_sn_exists']);
    }
    if(!empty($goods_sn))
    {
        $sql="SELECT goods_id FROM ". $ecs->table('products')."WHERE product_sn='$goods_sn'";
        if($db->getOne($sql))
        {
            make_json_error($_LANG['goods_sn_exists']);
        }
    }
    make_json_result('');
}

/*------------------------------------------------------ */
//-- 修改商品价格
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_goods_price')
{
    check_authz_json('goods_lib');

    $goods_id       = intval($_POST['id']);
    $goods_price    = floatval($_POST['val']);
    $price_rate     = floatval($_CFG['market_price_rate'] * $goods_price);

    if ($goods_price < 0 || $goods_price == 0 && $_POST['val'] != "$goods_price")
    {
        make_json_error($_LANG['shop_price_invalid']);
    }
    else
    {
        if ($exc->edit("shop_price = '$goods_price', market_price = '$price_rate', last_update=" .gmtime(), $goods_id))
        {
            clear_cache_files();
            make_json_result(number_format($goods_price, 2, '.', ''));
        }
    }
}

/*------------------------------------------------------ */
//-- 修改上架状态
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'toggle_on_sale')
{
    check_authz_json('goods_lib');

    $goods_id       = intval($_POST['id']);
    $on_sale        = intval($_POST['val']);

    if ($exc->edit("is_on_sale = '$on_sale', last_update=" .gmtime(), $goods_id))
    {   
        clear_cache_files();
        make_json_result($on_sale);
    }
}

/*------------------------------------------------------ */
//-- 修改相册排序
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_img_desc')
{
    check_authz_json('goods_lib');

    $img_id       = intval($_POST['id']);
    $img_desc     = intval($_POST['val']);

    if ($exc_gallery->edit("img_desc = '$img_desc'", $img_id))
    {
        clear_cache_files();
        make_json_result($img_desc);
    }
}


elseif ($_REQUEST['act'] == 'main_dsc') {
    $data = read_static_cache('seller_goods_str');
    if ($data === false){
        $shop_url = urlencode($ecs->url());
        $shop_info = get_shop_info_content(0);
        if($shop_info){
            $shop_country   = $shop_info['country'];
            $shop_province  = $shop_info['province'];
            $shop_city      = $shop_info['city'];
            $shop_address   = $shop_info['shop_address'];
        }else{
            $shop_country   = $_CFG['shop_country'];
            $shop_province  = $_CFG['shop_province'];
            $shop_city      = $_CFG['shop_city'];
            $shop_address   = $_CFG['shop_address'];
        }
        
        $qq = !empty($_CFG['qq']) ? $_CFG['qq'] : $shop_info['kf_qq'];
        $ww = !empty($_CFG['ww']) ? $_CFG['ww'] : $shop_info['kf_ww'];
        $service_email = !empty($_CFG['service_email']) ? $_CFG['service_email'] : $shop_info['seller_email'];
        $service_phone = !empty($_CFG['service_phone']) ? $_CFG['service_phone'] : $shop_info['kf_tel'];

        $shop_country   = $db->getOne("SELECT region_name FROM ".$ecs->table('region')." WHERE region_id='$shop_country'");
        $shop_province  = $db->getOne("SELECT region_name FROM ".$ecs->table('region')." WHERE region_id='$shop_province'");
        $shop_city      = $db->getOne("SELECT region_name FROM ".$ecs->table('region')." WHERE region_id='$shop_city'");

        $httpData = array(
                    'domain'            =>  $ecs->get_domain(), //当前域名
                    'url'               =>  urldecode($shop_url), //当前url
                    'shop_name'         =>  $_CFG['shop_name'],
                    'shop_title'        =>  $_CFG['shop_title'],
                    'shop_desc'         =>  $_CFG['shop_desc'],
                    'shop_keywords'     =>  $_CFG['shop_keywords'],
                    'country'           =>  $shop_country,
                    'province'          =>  $shop_province,
                    'city'              =>  $shop_city,
                    'address'           =>  $shop_address,
                    'qq'                =>  $qq,
                    'ww'                =>  $ww,
                    'ym'                =>  $service_phone, //客服电话
                    'msn'               =>  $_CFG['msn'],
                    'email'             =>  $service_email,
                    'phone'             =>  $_CFG['sms_shop_mobile'], //手机号
                    'icp'               =>  $_CFG['icp_number'],
                    'version'           =>  VERSION,
                    'release'           =>  RELEASE,
                    'language'          =>  $_CFG['lang'],
                    'php_ver'           =>  PHP_VERSION,
                    'mysql_ver'         =>  $db->version(),
                    'charset'           =>  EC_CHARSET
            );
		
        $Http = new Http();
        $Http->doPost($_CFG['certi'], $httpData); 
        
        write_static_cache('seller_goods_str', $httpData);
    }  
}

/*------------------------------------------------------ */
//-- 修改商品排序
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_sort_order')
{
    check_authz_json('goods_lib');

    $goods_id       = intval($_POST['id']);
    $sort_order     = intval($_POST['val']);

    if ($exc->edit("sort_order = '$sort_order', last_update=" .gmtime(), $goods_id))
    {
        clear_cache_files();
        make_json_result($sort_order);
    }
}

/*------------------------------------------------------ */
//-- 排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    $code = empty($_REQUEST['extension_code']) ? '' : trim($_REQUEST['extension_code']);
    $goods_list = lib_goods_list();
    $smarty->assign('code',         $code);
    $smarty->assign('goods_list',   $goods_list['goods']);
    $smarty->assign('filter',       $goods_list['filter']);
    $smarty->assign('record_count', $goods_list['record_count']);
    $smarty->assign('page_count',   $goods_list['page_count']);
    $smarty->assign('use_storage',  empty($_CFG['use_storage']) ? 0 : 1);

    /* 排序标记 */
    $sort_flag  = sort_flag($goods_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    /* 获取商品类型存在规格的类型 */
    $specifications = get_goods_type_specifications();
    $smarty->assign('specifications', $specifications);
        
    $store_list = get_common_store_list();
    $smarty->assign('store_list',        $store_list);
    
    $smarty->assign('nowTime', gmtime());
	
	set_default_filter(); //设置默认筛选
	
    make_json_result($smarty->fetch('goods_lib_list.dwt'), '',
    array('filter' => $goods_list['filter'], 'page_count' => $goods_list['page_count']));
}

/*------------------------------------------------------ */
//-- 删除商品库商品
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    $goods_id = intval($_REQUEST['id']);
	
    /* 取得商品信息 */
    $sql = "SELECT goods_id, goods_name, goods_thumb, goods_img, original_img " .
            "FROM " . $ecs->table('goods_lib') .
            " WHERE goods_id = '$goods_id'";
    $goods = $db->getRow($sql);
	
    if (empty($goods))
    {
        make_json_error($_LANG['goods_not_exist']);
    }
	
    $arr = array();
    /* 删除商品图片和轮播图片 */
    if (!empty($goods['goods_thumb']) && strpos($goods['goods_thumb'], "data/gallery_album" )=== false)
    {
        $arr[] = $goods['goods_thumb'];
        @unlink('../' . $goods['goods_thumb']);
    }
    if (!empty($goods['goods_img']) && strpos($goods['goods_img'], "data/gallery_album" )=== false)
    {
        $arr[] = $goods['goods_img'];
        @unlink('../' . $goods['goods_img']);
    }
    if (!empty($goods['original_img']) && strpos($goods['original_img'], "data/gallery_album" )=== false)
    {
        $arr[] = $goods['original_img'];
        @unlink('../' . $goods['original_img']);
    }
    if(!empty($arr)){
        get_oss_del_file($arr);
    }

    /* 检查权限 */
    check_authz_json('goods_lib');

    if ($exc->drop($goods_id))
    {
		//删除商品扩展信息
		$sql="DELETE FROM ".$ecs->table('goods_extend')." where goods_id='$goods_id'";
		$db->query($sql);

		/* 删除商品相册 */
		$sql = "SELECT img_url, thumb_url, img_original " .
				"FROM " . $ecs->table('goods_lib_gallery') .
				" WHERE goods_id = '$goods_id'";
		$res = $db->query($sql);
		while ($row = $db->fetchRow($res))
		{
			$arr = array();
			if (!empty($row['img_url']) && strpos($row['img_url'], "data/gallery_album" )=== false)
			{
				$arr[] = $row['img_url'];
				@unlink('../' . $row['img_url']);
			}
			if (!empty($row['thumb_url']) && strpos($row['thumb_url'], "data/gallery_album" )=== false)
			{
				$arr[] = $row['thumb_url'];
				@unlink('../' . $row['thumb_url']);
			}
			if (!empty($row['img_original']) && strpos($row['img_original'], "data/gallery_album" )=== false)
			{
				$arr[] = $row['img_original'];
				@unlink('../' . $row['img_original']);
			}
			if(!empty($arr)){
				get_oss_del_file($arr);
			}
		}
		
        clear_cache_files();

        $url = 'goods_lib.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

        ecs_header("Location: $url\n");
        exit;
    }
}

/*------------------------------------------------------ */
//-- 搜索商品，仅返回名称及ID
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'get_goods_list')
{
    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON;

    $filters = $json->decode($_GET['JSON']);

    $arr = get_goods_list($filters);
    $opt = array();

    foreach ($arr AS $key => $val)
    {
        $opt[] = array('value' => $val['goods_id'],
                        'text' => $val['goods_name'],
                        'data' => $val['shop_price']);
    }

    make_json_result($opt);
}

/*------------------------------------------------------ */
//-- 上传商品相册 ecmoban模板堂 --zhuo
/*------------------------------------------------------ */
 elseif ($_REQUEST['act'] == 'addImg') {
    require(ROOT_PATH . '/includes/cls_json.php');
    $json = new JSON;
    $result = array('content' => '', 'error' => 0, 'massege' => '');
    $goods_id = !empty($_REQUEST['goods_id_img']) ? $_REQUEST['goods_id_img'] : '';
    $img_desc = !empty($_REQUEST['img_desc']) ? $_REQUEST['img_desc'] : '';
    $img_file = !empty($_REQUEST['img_file']) ? $_REQUEST['img_file'] : '';
    $php_maxsize = ini_get('upload_max_filesize');
    $htm_maxsize = '2M';
    if ($_FILES['img_url']) {
        foreach ($_FILES['img_url']['error'] AS $key => $value) {
            if ($value == 0) {
                if (!$image->check_img_type($_FILES['img_url']['type'][$key])) {
                    $result['error'] = '1';
                    $result['massege'] = sprintf($_LANG['invalid_img_url'], $key + 1);
                } else {
                    $goods_pre = 1;
                }
            } elseif ($value == 1) {
                $result['error'] = '1';
                $result['massege'] = sprintf($_LANG['img_url_too_big'], $key + 1, $php_maxsize);
            } elseif ($_FILES['img_url']['error'] == 2) {
                $result['error'] = '1';
                $result['massege'] = sprintf($_LANG['img_url_too_big'], $key + 1, $htm_maxsize);
            }
        }
    }
    handle_gallery_image_add($goods_id, $_FILES['img_url'], $img_desc, $img_file, '', '', 'ajax');
    clear_cache_files();
    if ($goods_id > 0) {
        /* 图片列表 */
        $sql = "SELECT * FROM " . $ecs->table('goods_lib_gallery') . " WHERE goods_id = '$goods_id' ORDER BY img_desc ASC";
    } else {
        $img_id = $_SESSION['thumb_img_id' . $_SESSION['admin_id']];
        $where = '';
        if ($img_id) {
            $where = "AND img_id " . db_create_in($img_id) . "";
        }
        $sql = "SELECT * FROM " . $ecs->table('goods_lib_gallery') . " WHERE goods_id='' $where ORDER BY img_desc ASC";
    }
    $img_list = $db->getAll($sql);
    /* 格式化相册图片路径 */
    if (isset($GLOBALS['shop_id']) && ($GLOBALS['shop_id'] > 0)) {
        foreach ($img_list as $key => $gallery_img) {
            $gallery_img[$key]['img_url'] = get_image_path($gallery_img['goods_id'], $gallery_img['img_original'], false, 'gallery');
            $gallery_img[$key]['thumb_url'] = get_image_path($gallery_img['goods_id'], $gallery_img['img_original'], true, 'gallery');
        }
    } else {
        foreach ($img_list as $key => $gallery_img) {
            $gallery_img[$key]['thumb_url'] = '../' . (empty($gallery_img['thumb_url']) ? $gallery_img['img_url'] : $gallery_img['thumb_url']);
        }
    }
    $goods['goods_id'] = $goods_id;
    $smarty->assign('img_list', $img_list);
    $img_desc = array();
    foreach ($img_list as $k => $v) {
        $img_desc[] = $v['img_desc'];
    }
    $img_default = min($img_desc);
    $min_img_id = $db->getOne(" SELECT img_id   FROM " . $ecs->table("goods_gallery") . " WHERE goods_id = '$goods_id' AND img_desc = '$img_default' ORDER BY img_desc   LIMIT 1");
    $smarty->assign('min_img_id', $min_img_id);
    $smarty->assign('goods', $goods);
    $result['error'] = '2';
    $result['content'] = $GLOBALS['smarty']->fetch('gallery_img.lbi');
    die($json->encode($result));
}

/*------------------------------------------------------ */
//-- 修改默认相册 ecmoban模板堂 --zhuo
/*------------------------------------------------------ */
elseif($_REQUEST['act'] == 'img_default'){
    require(ROOT_PATH . '/includes/cls_json.php');
    $json = new JSON;
    $result = array('content' => '','error'=>0, 'massege' => '', 'img_id' => '');
    $img_id=!empty($_REQUEST['img_id'])   ?  intval($_REQUEST['img_id']):'0';
    
    /* 是否处理缩略图 */
    $proc_thumb = (isset($GLOBALS['shop_id']) && $GLOBALS['shop_id'] > 0)? false : true;
    
    if($img_id > 0){
        $goods_gallery=$db->getRow(" SELECT goods_id,img_desc FROM".$ecs->table('goods_lib_gallery')." WHERE img_id= '$img_id'");
        $goods_id = $goods_gallery['goods_id'];
        /*获取最小的排序*/
        $sql = "SELECT MIN(img_desc) FROM".$ecs->table('goods_lib_gallery')." WHERE  goods_id = '$goods_id'";
        $least_img_desc = $db->getOne($sql);
        /*排序互换*/
        $db->query("UPDATE".$ecs->table('goods_lib_gallery')." SET img_desc = '".$goods_gallery['img_desc']."' WHERE img_desc = '$least_img_desc' AND goods_id = '$goods_id' ");
        $sql=$db->query("UPDATE".$ecs->table('goods_lib_gallery')." SET img_desc = '$least_img_desc' WHERE img_id = '$img_id'");
        if($sql = true){
           if ($goods_id > 0) {
                $where = " goods_id = '$goods_id' ";
            } else {
                $where = " img_id " . db_create_in($_SESSION['thumb_img_id' . $_SESSION['admin_id']]) . " and goods_id = 0 ";
            }
            $sql = "SELECT * FROM " . $ecs->table('goods_lib_gallery') . " WHERE $where  ORDER BY img_desc ASC ";
            $img_list = $db->getAll($sql);
            /* 格式化相册图片路径 */
            if (isset($GLOBALS['shop_id']) && ($GLOBALS['shop_id'] > 0))
            {
                foreach ($img_list as $key => $gallery_img)
                {
                    $img_list[$key] = $gallery_img;
                    if(!empty($gallery_img['external_url'])){
                        $img_list[$key]['img_url'] = $gallery_img['external_url'];
                        $img_list[$key]['thumb_url'] = $gallery_img['external_url'];
                    }else{
                        $img_list[$key]['img_url'] = get_image_path($gallery_img['goods_id'], $gallery_img['img_original'], false, 'gallery');
                        $img_list[$key]['thumb_url'] = get_image_path($gallery_img['goods_id'], $gallery_img['img_original'], true, 'gallery');
                    }
                }
            }
            else
            {
                foreach ($img_list as $key => $gallery_img)
                {
                    $img_list[$key] = $gallery_img;
                    if(!empty($gallery_img['external_url'])){
                        $img_list[$key]['img_url'] = $gallery_img['external_url'];
                        $img_list[$key]['thumb_url'] = $gallery_img['external_url'];
                    }else{
                        
                        if($proc_thumb){
                            $img_list[$key]['thumb_url'] = '../' . (empty($gallery_img['thumb_url']) ? $gallery_img['img_url'] : $gallery_img['thumb_url']);
                        }else{
                            $img_list[$key]['thumb_url'] = (empty($gallery_img['thumb_url']) ? $gallery_img['img_url'] : $gallery_img['thumb_url']);
                        }
                    }
                }
            }
            $img_desc=array();
           
            if(!empty($img_list)){
				foreach($img_list as $k=>$v){
					$img_desc[]=$v['img_desc'];
				}	
			}
            if(!empty($img_desc)){
				
				$img_default = min($img_desc);
			}
            
            $min_img_id=$db->getOne(" SELECT img_id   FROM ".$ecs->table("goods_lib_gallery")." WHERE goods_id = '$goods_id' AND img_desc = '" .$img_default. "' ORDER BY img_desc LIMIT 1");
            $smarty->assign('min_img_id',$min_img_id);
            $smarty->assign('img_list',$img_list);
            $result['error']=1;
            $result['content']=$GLOBALS['smarty']->fetch('gallery_img.lbi');
        }else{
            $result['error']=2;
            $result['massege']= $_LANG['modify_failure'];
        }
    }
    die($json->encode($result));
}
elseif($_REQUEST['act'] == 'remove_consumption'){
    require(ROOT_PATH . '/includes/cls_json.php');
    $json = new JSON;
    $result = array('error'=>0, 'massege' => '', 'con_id' => '');
    
    $con_id=!empty($_REQUEST['con_id'])   ?  intval($_REQUEST['con_id']) : '0';
    $goods_id=!empty($_REQUEST['goods_id'])   ?  intval($_REQUEST['goods_id']) : '0';
    if($con_id > 0){
        $sql="DELETE FROM".$ecs->table('goods_consumption')." WHERE id = '$con_id' AND goods_id = '$goods_id'";
        if($db->query($sql)){
            $result['error']=2;
            $result['con_id']=$con_id;
        }
    }else{
        $result['error']=1;
        $result['massege']=$_LANG['select_delete_target'];
    }
    die($json->encode($result));
}

// mobile商品详情 添加图片 qin
elseif($_REQUEST['act'] == 'gallery_album_dialog')
{
    require(ROOT_PATH . '/includes/cls_json.php');
    $json = new JSON;
    $result = array('error'=>0, 'message' => '', 'log_type' => '', 'content' => '');
    
    // 获取相册信息 qin
    $sql = "SELECT album_id,ru_id,album_mame,album_cover,album_desc,sort_order FROM " . $ecs->table('gallery_album') . " "
            . " WHERE ru_id = 0 ORDER BY sort_order";
    $gallery_album_list = $db->getAll($sql);
    $smarty->assign('gallery_album_list', $gallery_album_list);
    
    $log_type = !empty($_GET['log_type']) ? trim($_GET['log_type']) : 'image';
    $result['log_type'] = $log_type;
    $smarty->assign('log_type', $log_type);
    
    $sql = "SELECT * FROM " .$GLOBALS['ecs']->table('pic_album'). " WHERE ru_id = 0";
    $res = $GLOBALS['db']->getAll($sql);
    $smarty->assign('pic_album', $res);
    $result['content'] = $smarty->fetch('templates/library/album_dialog.lbi');
    
    die($json->encode($result));
}

// 异步查询相册的图片 qin
elseif($_REQUEST['act'] == 'gallery_album_pic')
{
    require(ROOT_PATH . '/includes/cls_json.php');
    $json = new JSON;
    $result = array('error'=>0, 'message' => '', 'content' => '');
    
    $album_id = !empty($_GET['album_id']) ? intval($_GET['album_id']) : 0;
    if (empty($album_id))
    {
        $result['error'] = 1;
        die($json->encode($result));
    }
    
    $sql = "SELECT * FROM " .$GLOBALS['ecs']->table('pic_album'). " WHERE album_id = '$album_id' ";
    $res = $GLOBALS['db']->getAll($sql);
    $smarty->assign('pic_album', $res);
    $result['content'] = $smarty->fetch('templates/library/album_pic.lbi');
    die($json->encode($result));
}

/*------------------------------------------------------ */
//-- 扫码入库 by wu
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'scan_code')
{
    check_authz_json('goods_lib');
    require(ROOT_PATH . '/includes/cls_json.php');
    $json = new JSON;
    $result = array('error'=>0, 'massege' => '', 'content' => '');

    $bar_code = empty($_REQUEST['bar_code'])? '':trim($_REQUEST['bar_code']);
	$config = get_scan_code_config($adminru['ru_id']);
	$data = get_jsapi(array('appkey'=>$config['js_appkey'], 'barcode'=>$bar_code));
	
	if($data['status'] != 0){
		$result['error'] = 1;
		$result['message'] = $data['msg'];
	}else{
		//重量（用毛重）
		$goods_weight = 0;	
		if(strpos($data['result']['grossweight'], $_LANG['unit_kg']) !== false){
			$goods_weight = floatval(str_replace($_LANG['unit_kg'], '', $data['result']['grossweight']));
		}elseif(strpos($data['result']['grossweight'], $_LANG['unit_g']) !== false){
			$goods_weight = floatval(str_replace($_LANG['unit_kg'], '', $data['result']['grossweight']))/1000;
		}
		//详情
		$goods_desc = "";
		if(!empty($data['result']['description'])){
			create_html_editor('goods_desc', trim($data['result']['description']));
			$goods_desc = $smarty->get_template_vars('FCKeditor');
		}
		
		//初始商品信息
		$goods_info = array();
		$goods_info['goods_name'] = isset($data['result']['name'])? trim($data['result']['name']):''; //名称
		$goods_info['goods_name'] .= isset($data['result']['type'])? trim($data['result']['type']):''; //规格
		$goods_info['shop_price'] = isset($data['result']['price'])? floatval($data['result']['price']):'0.00'; //价格
		$goods_info['goods_img_url'] = isset($data['result']['pic'])? trim($data['result']['pic']):''; //价格
		$goods_info['goods_desc'] = $goods_desc; //描述
		$goods_info['goods_weight'] = $goods_weight; //重量
		$goods_info['keywords'] = isset($data['result']['keyword'])? trim($data['result']['keyword']):''; //关键词
		$goods_info['width'] = isset($data['result']['width'])? trim($data['result']['width']):''; //宽度
		$goods_info['height'] = isset($data['result']['height'])? trim($data['result']['height']):''; //高度
		$goods_info['depth'] = isset($data['result']['depth'])? trim($data['result']['depth']):''; //深度
		$goods_info['origincountry'] = isset($data['result']['origincountry'])? trim($data['result']['origincountry']):''; //产国
		$goods_info['originplace'] = isset($data['result']['originplace'])? trim($data['result']['originplace']):''; //产地
		$goods_info['assemblycountry'] = isset($data['result']['assemblycountry'])? trim($data['result']['assemblycountry']):''; //组装国
		$goods_info['barcodetype'] = isset($data['result']['barcodetype'])? trim($data['result']['barcodetype']):''; //条码类型
		$goods_info['catena'] = isset($data['result']['catena'])? trim($data['result']['catena']):''; //产品系列
		$goods_info['isbasicunit'] = isset($data['result']['isbasicunit'])? intval($data['result']['isbasicunit']):0; //是否是基本单元
		$goods_info['packagetype'] = isset($data['result']['packagetype'])? trim($data['result']['packagetype']):''; //包装类型
		$goods_info['grossweight'] = isset($data['result']['grossweight'])? trim($data['result']['grossweight']):''; //毛重
		$goods_info['netweight'] = isset($data['result']['netweight'])? trim($data['result']['netweight']):''; //净重
		$goods_info['netcontent'] = isset($data['result']['netcontent'])? trim($data['result']['netcontent']):''; //净含量
		$goods_info['licensenum'] = isset($data['result']['licensenum'])? trim($data['result']['licensenum']):''; //生产许可证
		$goods_info['healthpermitnum'] = isset($data['result']['healthpermitnum'])? trim($data['result']['healthpermitnum']):''; //卫生许可证
		$result['goods_info'] = $goods_info;
	}

	die($json->encode($result));   
}

/*------------------------------------------------------ */
//-- 删除图片
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'drop_image')
{
    check_authz_json('goods_lib');

    $img_id = empty($_REQUEST['img_id']) ? 0 : intval($_REQUEST['img_id']);

    /* 删除图片文件 */
    $sql = "SELECT img_url, thumb_url, img_original " .
            " FROM " . $GLOBALS['ecs']->table('goods_lib_gallery') .
            " WHERE img_id = '$img_id'";
    $row = $GLOBALS['db']->getRow($sql);
    
    $img_url = ROOT_PATH . $row['img_url'];
    $thumb_url = ROOT_PATH . $row['thumb_url'];
    $img_original = ROOT_PATH . $row['img_original'];
    $arr = array();
    if ($row['img_url'] != '' && is_file($img_url) && strpos($row['img_url'], "data/gallery_album") === false)
    {
        $arr[] = $row['img_url'];
        @unlink($img_url);
    }
    if ($row['thumb_url'] != '' && is_file($thumb_url) && strpos($row['img_url'], "data/gallery_album") === false)
    {
        $arr[] = $row['thumb_url'];
        @unlink($thumb_url);
    }
    if ($row['img_original'] != '' && is_file($img_original) && strpos($row['img_url'], "data/gallery_album") === false)
    {
        $arr[] = $row['img_original'];
        @unlink($img_original);
    }
    if(!empty($arr)){
        get_oss_del_file($arr);
    }
    
    /* 删除数据 */
    $sql = "DELETE FROM " . $GLOBALS['ecs']->table('goods_lib_gallery') . " WHERE img_id = '$img_id' LIMIT 1";
    $GLOBALS['db']->query($sql);

    clear_cache_files();
    make_json_result($img_id);
}

/*------------------------------------------------------ */
//-- 导入商家商品
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'import_seller_goods')
{
    admin_priv('goods_lib_list');
    $action_link = array('href' => 'goods_lib.php?act=list', 'text' => $_LANG['goods_lib_list']);
    $smarty->assign('action_link', $action_link);
	$smarty->assign('ur_here', $_LANG['import_seller_goods']);
	$sql = " SELECT user_id FROM ".$ecs->table('merchants_shop_information');
	$seller_ids = $db->getCol($sql);
	foreach($seller_ids as $k=>$v){
		$seller_list[$k]['shop_name'] = get_shop_name($v,1);
		$seller_list[$k]['user_id'] = $v;
	}
	$smarty->assign('seller_list',	$seller_list);
	$smarty->display('goods_lib_import.dwt');
}

/*------------------------------------------------------ */
//-- 导入商家商品执行程序
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'import_action')
{
    admin_priv('goods_lib_list');
	$user_id = $_REQUEST['user_id'] ? intval($_REQUEST['user_id']): 0 ;
    $record_count = $db->getOne(" SELECT COUNT(*) FROM ".$ecs->table('goods')." WHERE user_id = '$user_id' ");
    $smarty->assign('ur_here',      $_LANG['import_seller_goods']);
    $smarty->assign('record_count', $record_count);
	$smarty->assign('user_id', $user_id);
    $smarty->assign('page', 1);
    assign_query_info();
    $smarty->display('import_action_list.dwt');
}
/*------------------------------------------------------ */
//-- 导入商家商品执行程序
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'import_action_list')
{
    admin_priv('goods_lib_list');
	
	$user_id = $_REQUEST['user_id'] ? intval($_REQUEST['user_id']): 0 ;
    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON();
	
    $page = !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
    $page_size = isset($_REQUEST['page_size']) ? intval($_REQUEST['page_size']) : 1;
    
    $goods_list = get_import_goods_list($user_id);

    $goods_list = $ecs->page_array($page_size, $page, $goods_list);
    $result['list'] = $goods_list['list'][0];

    if ($result['list']) {
		$sql = " SELECT goods_id, cat_id, bar_code, goods_name, goods_name_style, brand_id, goods_weight, market_price,".
			   " cost_price, shop_price, keywords, goods_brief, goods_desc, desc_mobile, goods_thumb, goods_img, original_img, ".
			   " is_real, extension_code, sort_order, goods_type, is_check, largest_amount, pinyin_keyword FROM ".
				$ecs->table('goods')." WHERE user_id = '$user_id' AND goods_id = '".$result['list']['goods_id']."' ";
		$goods_info = $db->getRow($sql);
		$sql = " SELECT goods_id FROM ".$ecs->table('goods_lib')." WHERE lib_goods_id = '$goods_info[goods_id]' ";

		if(!$GLOBALS['db']->getOne($sql)){
			$goods_thumb = copy_img($goods_info['goods_thumb']);
			$goods_img = copy_img($goods_info['goods_img']);
			$original_img = copy_img($goods_info['original_img']);
			$sql = "INSERT INTO " . $ecs->table('goods_lib') .
					"(cat_id, bar_code, goods_name, goods_name_style, brand_id, goods_weight, market_price,".
					" cost_price, shop_price, keywords, goods_brief, goods_desc, desc_mobile, goods_thumb, goods_img, original_img, ".
					" is_real, extension_code, sort_order, goods_type, is_check, largest_amount, pinyin_keyword, lib_goods_id, from_seller ) " .
					" VALUES " .
					"('$goods_info[cat_id]', '$goods_info[bar_code]', '$goods_info[goods_name]', '$goods_info[goods_name_style]', '$goods_info[brand_id]', '$goods_info[goods_weight]', '$goods_info[market_price]', ".
					" '$goods_info[cost_price]', '$goods_info[shop_price]', '$goods_info[keywords]', '$goods_info[goods_brief]', '".addslashes($goods_info['goods_desc'])."', '$goods_info[desc_mobile]', '$goods_thumb', '$goods_img', '$original_img', ".
					" '$goods_info[is_real]', '$goods_info[extension_code]', '$goods_info[sort_order]', '$goods_info[goods_type]', '$goods_info[is_check]', '$goods_info[largest_amount]', '$goods_info[pinyin_keyword]', '$goods_info[goods_id]', '$user_id' )";
			try{
				$db ->query($sql);
				$new_goods_id = $db->insert_id();
				$res = $db->getAll(" SELECT img_desc, img_url, thumb_url, img_original FROM ".$ecs->table('goods_gallery')." WHERE goods_id = '$goods_info[goods_id]' ");
				if($res){
					foreach($res as $k=>$v){
						$img_url 		= copy_img($v['img_url']);
						$thumb_url 		= copy_img($v['thumb_url']);
						$img_original 	= copy_img($v['img_original']);
						$sql = " INSERT INTO ".$ecs->table('goods_lib_gallery').
								" ( goods_id, img_desc, img_url, thumb_url, img_original ) ".
								" VALUES ".
								" ( '$new_goods_id', '$v[img_desc]', '$img_url', '$thumb_url', '$img_original' ) ";	
						if(!$db->query($sql)){
							$result['list']['status'] = $_LANG['img_import_fail'];
						}
					}
				}
				$result['list']['status'] = $_LANG['prompt_import_success'];						
			}catch(Exception $e){
				$result['list']['status'] = $_LANG['prompt_import_fail'];
			}
		}else{
			$result['list']['status'] = $_LANG['repeat_import'];
		}
    }

    $result['page'] = $goods_list['filter']['page'] + 1;
    $result['page_size'] = $goods_list['filter']['page_size'];
    $result['record_count'] = $goods_list['filter']['record_count'];
    $result['page_count'] = $goods_list['filter']['page_count'];
        
    $result['is_stop'] = 1;
    if ($page > $goods_list['filter']['page_count']) {
        $result['is_stop'] = 0;
    }else{
        $result['filter_page'] = $goods_list['filter']['page'];
    }
    die($json->encode($result));
}

/*------------------------------------------------------ */
//-- 搜索店铺名称
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'get_shopname')
{
    check_authz_json('goods_lib');

    $shop_name = empty($_REQUEST['shop_name']) ? '' : trim($_REQUEST['shop_name']);

    /* 获取会员列表信息 */
	$sql = " SELECT user_id FROM ".$ecs->table('merchants_shop_information');
	$seller_ids = $db->getCol($sql);
	foreach($seller_ids as $k=>$v){
		if(is_numeric(stripos(get_shop_name($v,1),$shop_name)) || empty($shop_name)){
			$seller_list[$k]['shop_name'] = get_shop_name($v,1);
			$seller_list[$k]['user_id'] = $v;			
		}
	}
	
    $res = get_search_shopname_list($seller_list);
    
    clear_cache_files();
    make_json_result($res);
}
/*-

/**
 * 列表链接
 * @param   bool    $is_add         是否添加（插入）
 * @param   string  $extension_code 虚拟商品扩展代码，实体商品为空
 * @return  array('href' => $href, 'text' => $text)
 */
function list_link($is_add = true, $extension_code = '')
{
    $href = 'goods_lib.php?act=list';
    if (!empty($extension_code))
    {
        $href .= '&extension_code=' . $extension_code;
    }
    if (!$is_add)
    {
        $href .= '&' . list_link_postfix();
    }

    if ($extension_code == 'virtual_card')
    {
        $text = $GLOBALS['_LANG']['50_virtual_card_list'];
    }
    else
    {
        $text = $GLOBALS['_LANG']['01_goods_list'];
    }

    return array('href' => $href, 'text' => $text);
}

/**
 * 添加链接
 * @param   string  $extension_code 虚拟商品扩展代码，实体商品为空
 * @return  array('href' => $href, 'text' => $text)
 */
function add_link($extension_code = '')
{
    $href = 'goods_lib.php?act=add';
    if (!empty($extension_code))
    {
        $href .= '&extension_code=' . $extension_code;
    }

    if ($extension_code == 'virtual_card')
    {
        $text = $GLOBALS['_LANG']['51_virtual_card_add'];
    }
    else
    {
        $text = $GLOBALS['_LANG']['02_goods_add'];
    }

    return array('href' => $href, 'text' => $text);
}


function lib_is_mer($goods_id){
	$sql = " SELECT user_id FROM ".$GLOBALS['ecs']->table('goods_lib'). " WHERE goods_id = '$goods_id' ";
	$one = $GLOBALS['db']->getOne($sql);
	if($one == 0){
		return false;
	}else{
		return $one;
	}
}

/*
* 复制商品图片
*/
function copy_img($image = ''){
    if(stripos($image,"http://")!== false ||stripos($image,"https://")!== false){//外链图片
        return $image;
    }
	$newname = '';
	if($image){
		$img = ROOT_PATH. $image;
		$pos = strripos(basename($img), '.');
		$newname = dirname($img) . '/' . cls_image::random_filename() . substr(basename($img), $pos);
		//开启OSS 则先下载图片 否则拷贝图片
		if ($GLOBALS['_CFG']['open_oss'] == 1) {
			$bucket_info = get_bucket_info();
            $url = $bucket_info['endpoint']. $image; 
            //如果目标目录不存在，则创建它 
            if (!file_exists(dirname($img))) {
                make_dir(dirname($img));
            }             
			@get_http_basename($url, $newname, 1);				
		}
		elseif (!@copy($img, $newname)) 
		{
			return;
		}		
	}
	$new_name = str_replace(ROOT_PATH, '', $newname);
	get_oss_add_file(array($new_name));
	return $new_name;
}

/**
 * 修改商品某字段值
 * @param   string  $goods_id   商品编号，可以为多个，用 ',' 隔开
 * @param   string  $field      字段名
 * @param   string  $value      字段值
 * @return  bool
 */
function lib_update_goods($goods_id, $field, $value, $content = '', $type = '') { //ecmoban模板堂 --zhuo  $content = ''
    if ($goods_id) {
        /* 清除缓存 */
        clear_cache_files();

        $date = array('model_attr');
        $where = "goods_id = '$goods_id'";
        $table = "goods_lib";

        $sql = "UPDATE " . $GLOBALS['ecs']->table($table) .
                " SET $field = '$value' , " . $content . " last_update = '" . gmtime() . "' " .
                "WHERE goods_id " . db_create_in($goods_id);
        return $GLOBALS['db']->query($sql);
    } else {
        return false;
    }
}

/**
 * 从回收站删除多个商品
 * @param   mix     $goods_id   商品id列表：可以逗号格开，也可以是数组
 * @return  void
 */
function lib_delete_goods($goods_id)
{
    if (empty($goods_id))
    {
        return;
    }

    /* 取得有效商品id */
    $sql = "SELECT DISTINCT goods_id FROM " . $GLOBALS['ecs']->table('goods_lib') .
            " WHERE goods_id " . db_create_in($goods_id) ;
    $goods_id = $GLOBALS['db']->getCol($sql);
    if (empty($goods_id))
    {
        return;
    }
    
    //OSS文件存储ecmoban模板堂 --zhuo start
    if($GLOBALS['_CFG']['open_oss'] == 1){
        $bucket_info = get_bucket_info();
        $urlip = get_ip_url($GLOBALS['ecs']->url());
        $url = $urlip . "oss.php?act=del_file";
        $Http = new Http();
    }
    //OSS文件存储ecmoban模板堂 --zhuo end

    /* 删除商品图片和轮播图片文件 */
    $sql = "SELECT goods_thumb, goods_img, original_img " .
            "FROM " . $GLOBALS['ecs']->table('goods_lib') .
            " WHERE goods_id " . db_create_in($goods_id);
    $res = $GLOBALS['db']->query($sql);
    while ($goods = $GLOBALS['db']->fetchRow($res))
    {
        if (!empty($goods['goods_thumb']))
        {
            @unlink('../' . $goods['goods_thumb']);
        }
        if (!empty($goods['goods_img']))
        {
            @unlink('../' . $goods['goods_img']);
        }
        if (!empty($goods['original_img']))
        {
            @unlink('../' . $goods['original_img']);
        }
        
        //OSS文件存储ecmoban模板堂 --zhuo start
        if($GLOBALS['_CFG']['open_oss'] == 1){
            $post_data = array(
                'bucket'        => $bucket_info['bucket'],
                'keyid'         => $bucket_info['keyid'],
                'keysecret'     => $bucket_info['keysecret'],
                'is_cname'      => $bucket_info['is_cname'],
                'endpoint'      => $bucket_info['outside_site'],
                'object' => array(
                    $goods['goods_thumb'],
                    $goods['goods_img'],
                    $goods['original_img']
                    ) 
            );

            $Http->doPost($url, $post_data);
        }
        //OSS文件存储ecmoban模板堂 --zhuo end
    }

    /* 删除商品 */
    $sql = "DELETE FROM " . $GLOBALS['ecs']->table('goods_lib') .
            " WHERE goods_id " . db_create_in($goods_id);
    $GLOBALS['db']->query($sql);

    /* 删除商品相册的图片文件 */
    $sql = "SELECT img_url, thumb_url, img_original " .
            "FROM " . $GLOBALS['ecs']->table('goods_lib_gallery') .
            " WHERE goods_id " . db_create_in($goods_id);
    $res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        if (!empty($row['img_url']))
        {
            @unlink('../' . $row['img_url']);
        }
        if (!empty($row['thumb_url']))
        {
            @unlink('../' . $row['thumb_url']);
        }
        if (!empty($row['img_original']))
        {
            @unlink('../' . $row['img_original']);
        }
        
        //OSS文件存储ecmoban模板堂 --zhuo start
        if($GLOBALS['_CFG']['open_oss'] == 1){
            $post_data = array(
                'bucket'        => $bucket_info['bucket'],
                'keyid'         => $bucket_info['keyid'],
                'keysecret'     => $bucket_info['keysecret'],
                'is_cname'      => $bucket_info['is_cname'],
                'endpoint'      => $bucket_info['outside_site'],
                'object' => array(
                    $row['img_url'],
                    $row['thumb_url'],
                    $row['img_original']
                    ) 
            );

            $Http->doPost($url, $post_data);
        }
        //OSS文件存储ecmoban模板堂 --zhuo end
    }

    /* 删除商品相册 */
    $sql = "DELETE FROM " . $GLOBALS['ecs']->table('goods_lib_gallery') . " WHERE goods_id " . db_create_in($goods_id);
    $GLOBALS['db']->query($sql);

    /* 清除缓存 */
    clear_cache_files();
}

/**
 * 取得店铺导入商品列表
 * @return array 
 */
function get_import_goods_list($ru_id = 0) {

	$sql = " SELECT goods_id, goods_name FROM " . $GLOBALS['ecs']->table('goods') . " WHERE user_id = '$ru_id' ORDER BY sort_order ";
	$res = $GLOBALS['db']->getAll($sql);

	$goods_list = array();
	foreach ($res AS $key=>$row) {
		$goods_list[$key]['goods_id'] = $row['goods_id'];
		$goods_list[$key]['goods_name'] = addslashes($row['goods_name']);
	}
    return $goods_list;
}

//获取会员信息列表
function get_search_shopname_list($user_list){
    
    $html = '';
    if($user_list){
		
        $html .= "<ul>";

        foreach($user_list as $key=>$user){
            $html .= "<li data-name='".$user['shop_name']."' data-id='".$user['user_id']."'>".$user['shop_name']."</li>";
        }

        $html .= '</ul>';
    }else{
        $html = '<span class="red">'+$GLOBALS['_LANG']['query_wu_user']+'</span><input name="user_id" value="0" type="hidden" />';
    }
    
    return $html;
}

?>