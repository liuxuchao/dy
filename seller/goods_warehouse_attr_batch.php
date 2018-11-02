<?php

/**
 * ECSHOP 商品批量上传、修改
 * ============================================================================
 * * 版权所有 2005-2016 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: goods_batch.php 17217 2011-01-19 06:29:08Z liubo $
 */
define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require('includes/lib_goods.php');

/* ------------------------------------------------------ */
//-- 批量上传
/* ------------------------------------------------------ */
 
if ($_REQUEST['act'] == 'add') {
    /* 检查权限 */
    admin_priv('goods_manage');
    $smarty->assign('menu_select', array('action' => '02_cat_and_goods', 'current' => 'warehouse_attr_batch'));
    $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
    $attr_name = isset($_REQUEST['attr_name']) ? $_REQUEST['attr_name'] : '';

    if ($goods_id > 0) {
        $smarty->assign('action_link', array('text' => $_LANG['goto_goods'], 'href' => 'goods.php?act=edit&goods_id=' . $goods_id . '&extension_code='));
    }

    /* 取得可选语言 */
    $dir = opendir('../languages');
    $lang_list = array(
        'UTF8' => $_LANG['charset']['utf8'],
        'GB2312' => $_LANG['charset']['zh_cn'],
        'BIG5' => $_LANG['charset']['zh_tw'],
    );
    $download_list = array();
    while (@$file = readdir($dir)) {
        if ($file != '.' && $file != '..' && $file != ".svn" && $file != "_svn" && is_dir('../languages/' . $file) == true) {
            $download_list[$file] = sprintf($_LANG['download_file'], isset($_LANG['charset'][$file]) ? $_LANG['charset'][$file] : $file);
        }
    }
    @closedir($dir);
    $smarty->assign('lang_list', $lang_list);
    $smarty->assign('download_list', $download_list);
    $smarty->assign('goods_id', $goods_id);
    $smarty->assign('attr_name', $attr_name);

    $goods_date = array('goods_name');
    $where = "goods_id = '$goods_id'";
    $goods_name = get_table_date('goods', $where, $goods_date, 2);
    $smarty->assign('goods_name', $goods_name);

    /* 参数赋值 */
    $smarty->assign('primary_cat',     $_LANG['02_cat_and_goods']);
    $smarty->assign('ur_here', $_LANG['13_batch_add']);

    /* 显示模板 */
    assign_query_info();
    $smarty->display('goods_warehouse_attr_batch.dwt');
}

/* ------------------------------------------------------ */
//-- 批量上传：处理
/* ------------------------------------------------------ */ 
elseif ($_REQUEST['act'] == 'upload') {
    /* 检查权限 */
    admin_priv('goods_manage');
    
    $smarty->assign('menu_select', array('action' => '02_cat_and_goods', 'current' => 'warehouse_attr_batch'));
    
    //ecmoban模板堂 --zhuo start 仓库
    if ($_FILES['file']['name']) {
        $line_number = 0;
        $arr = array();
        $goods_list = array();
        $field_list = array_keys($_LANG['upload_warehouse_attr']); // 字段列表
        $_POST['charset'] = 'GB2312';
        $data = file($_FILES['file']['tmp_name']);

        if (count($data) > 0) {
            foreach ($data AS $line) {
                // 跳过第一行
                if ($line_number == 0) {
                    $line_number++;
                    continue;
                }

                // 转换编码
                if (($_POST['charset'] != 'UTF8') && (strpos(strtolower(EC_CHARSET), 'utf') === 0)) {
                    $line = ecs_iconv($_POST['charset'], 'UTF8', $line);
                }

                // 初始化
                $arr = array();
                $buff = '';
                $quote = 0;
                $len = strlen($line);
                for ($i = 0; $i < $len; $i++) {
                    $char = $line[$i];

                    if ('\\' == $char) {
                        $i++;
                        $char = $line[$i];

                        switch ($char) {
                            case '"':
                                $buff .= '"';
                                break;
                            case '\'':
                                $buff .= '\'';
                                break;
                            case ',';
                                $buff .= ',';
                                break;
                            default:
                                $buff .= '\\' . $char;
                                break;
                        }
                    } elseif ('"' == $char) {
                        if (0 == $quote) {
                            $quote++;
                        } else {
                            $quote = 0;
                        }
                    } elseif (',' == $char) {
                        if (0 == $quote) {
                            if (!isset($field_list[count($arr)])) {
                                continue;
                            }
                            $field_name = $field_list[count($arr)];
                            $arr[$field_name] = trim($buff);
                            $buff = '';
                            $quote = 0;
                        } else {
                            $buff .= $char;
                        }
                    } else {
                        $buff .= $char;
                    }

                    if ($i == $len - 1) {
                        if (!isset($field_list[count($arr)])) {
                            continue;
                        }
                        $field_name = $field_list[count($arr)];
                        $arr[$field_name] = trim($buff);
                    }
                }
                
                $goods_list[] = $arr;
            }

            $goods_list = get_goods_bacth_warehouse_attr_list($goods_list);
        }
    }

    $_SESSION['goods_list'] = $goods_list;
    
    $smarty->assign('full_page', 2);
    $smarty->assign('page', 1);
    $smarty->assign('attr_names', $attr_names); //属性名称;

    /* 显示模板 */
    assign_query_info();
    $smarty->assign('ur_here', $_LANG['13_batch_add']);
    $smarty->display('goods_warehouse_attr_batch_add.dwt');
}

/* ------------------------------------------------------ */
//-- 动态添加数据入库;
/* ------------------------------------------------------ */ 
elseif ($_REQUEST['act'] == 'ajax_insert') {
    /* 检查权限 */
    admin_priv('goods_manage');

    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON();
    
    $result = array('list' => array(), 'is_stop' => 0);
    $page = !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
    $page_size = isset($_REQUEST['page_size']) ? intval($_REQUEST['page_size']) : 1;

    /* 设置最长执行时间为5分钟 */
    @set_time_limit(300);

    if (isset($_SESSION['goods_list']) && $_SESSION['goods_list']) {
        $goods_list = $_SESSION['goods_list'];
        $goods_list = $ecs->page_array($page_size, $page, $goods_list);

        $result['list'] = $goods_list['list'][0];
        $result['page'] = $goods_list['filter']['page'] + 1;
        $result['page_size'] = $goods_list['filter']['page_size'];
        $result['record_count'] = $goods_list['filter']['record_count'];
        $result['page_count'] = $goods_list['filter']['page_count'];

        $result['is_stop'] = 1;
        if ($page > $goods_list['filter']['page_count']) {
            $result['is_stop'] = 0;
        }

        $other['goods_id'] = $result['list']['goods_id'];
        $other['warehouse_id'] = $result['list']['warehouse_id'];
        $other['goods_attr_id'] = $result['list']['goods_attr_id'];
        $other['attr_price'] = $result['list']['attr_price'];
        
        //查询数据是否已经存在;
        $sql = "SELECT id FROM " . $GLOBALS['ecs']->table('warehouse_attr') . " WHERE goods_id = '" . $result['list']['goods_id'] . "' AND warehouse_id = '" .$result['list']['warehouse_id']. "'" .
                " AND goods_attr_id = '" . $result['list']['goods_attr_id'] . "'";
        
        if ($GLOBALS['db']->getOne($sql, true)) {
            
            $where = "";
            if(empty($result['list']['goods_id'])){
                $where = " AND admin_id = '" .$_SESSION['seller_id']. "'";
            }
            
            $db->autoExecute($ecs->table('warehouse_attr'), $other, 'UPDATE', "goods_id = '" . $result['list']['goods_id'] . "' AND warehouse_id = '" .$result['list']['warehouse_id']. "' AND goods_attr_id = '" . $result['list']['goods_attr_id'] . "' $where");
            $result['status_lang'] = '<span style="color: red;">'.$_LANG['updated_data_success'].'</span>';
        } else {
            
            $other['admin_id'] = $_SESSION['seller_id'];
            
            $db->autoExecute($ecs->table('warehouse_attr'), $other, 'INSERT');
            $result['status_lang'] = '<span style="color: red;">'.$_LANG['added_data_success'].'</span>';
        }
    }
    die($json->encode($result));
}

/* ------------------------------------------------------ */
//-- 下载文件
/* ------------------------------------------------------ */ 
elseif ($_REQUEST['act'] == 'download') {
    /* 检查权限 */
    admin_priv('goods_manage');

    $goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
    $attr_name = isset($_REQUEST['attr_name']) ? $_REQUEST['attr_name'] : '';
    
    $goods_attr_list = get_goods_attr_list($goods_id);
    
    // 文件标签
    // Header("Content-type: application/octet-stream");
    header("Content-type: application/vnd.ms-excel; charset=utf-8");
    Header("Content-Disposition: attachment; filename=warehouse_attr_info_list" .$goods_id. ".csv");

    // 下载
    if ($_GET['charset'] != $_CFG['lang']) {
        $lang_file = '../languages/' . $_GET['charset'] . '/' . ADMIN_PATH . '/goods_area_attr_batch.php';
        if (file_exists($lang_file)) {
            unset($_LANG['upload_warehouse_attr']);
            require($lang_file);
        }
    }
    if (isset($_LANG['upload_warehouse_attr'])) {
        /* 创建字符集转换对象 */
        if ($_GET['charset'] == 'zh_cn' || $_GET['charset'] == 'zh_tw') {
            $to_charset = $_GET['charset'] == 'zh_cn' ? 'GB2312' : 'BIG5';
            $data = join(',', $_LANG['upload_warehouse_attr']) . "\t\n";
            
            $area_date = array('region_name');
            $where = "region_type = 0";
            $area_info = get_table_date('region_warehouse', $where, $area_date, 1);
            
            if($goods_id){
                $goods_info = get_admin_goods_info($goods_id, array('goods_name', 'goods_sn', 'user_id'));
            }else{
                $adminru = get_admin_ru_id();
                
                $goods_info['user_id'] = $adminru['ru_id'];
                $goods_info['shop_name'] = get_shop_name($adminru['ru_id'], 1);
            }

            if ($area_info) {
                for ($i = 0; $i < count($area_info); $i++) {
                    $data .= "" . ',';
                    $data .= "" . ',';
                    $data .= "" . ',';
                    $data .= "" . ',';
                    $data .= "" . ',';
                    $data .= "" . ',';
                    $data .= "" . "\t\n";
                    
                    if($goods_attr_list){
                        for ($j = 0; $j < count($goods_attr_list); $j++) {
                            
                            $data .= $goods_id . ',';
                            $data .= $goods_info['goods_name'] . ',';
                            $data .= $goods_info['shop_name'] . ',';
                            $data .= $goods_info['user_id'] . ',';
                            $data .= $area_info[$i]['region_name'] . ',';

                            $attr_price = !empty($goods_attr_list[$j]['attr_price']) ? $goods_attr_list[$j]['attr_price'] : 0;
                            
                            $data .= $goods_attr_list[$j]['attr_value'] . ',';
                            $data .= $attr_price . "\t\n";
                        }
                    }
                }
            }
            
            echo ecs_iconv(EC_CHARSET, $to_charset, $data);
        } else {
            echo join(',', $_LANG['upload_warehouse_attr']);
        }
    } else {
        echo 'error: $_LANG[upload_warehouse_attr] not exists';
    }
}

/**
 * 商品属性列表
 */
function get_goods_attr_list($goods_id){
    $where = "";
    if(empty($goods_id)){
        $where = " AND ga.admin_id = '" .$_SESSION['seller_id']. "'";
    }
    
    $sql = "SELECT ga.goods_attr_id, ga.goods_id, ga.attr_id, ga.attr_value, ga.attr_sort, ga.admin_id, war.attr_price AS attr_price FROM " .$GLOBALS['ecs']->table("goods_attr") ." AS ga " . 
            " LEFT JOIN " .$GLOBALS['ecs']->table("warehouse_attr"). " AS war ON ga.goods_attr_id = war.goods_attr_id AND ga.goods_id = war.goods_id" .
            " WHERE ga.goods_id = '$goods_id'" . $where . " ORDER BY ga.attr_sort, ga.goods_attr_id";
    $goods_attr_list = $GLOBALS['db']->getAll($sql);
    
    return $goods_attr_list;
}

?>