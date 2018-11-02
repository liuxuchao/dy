<?php

/**
 * ECSHOP 批发函数文件
 * ============================================================================
 * * 版权所有 2005-2016 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liuhui $
 * $Id: lib_wholesale.php 2009-07-24 09:31:42Z liuhui $
 */
/* * **************************************求购部分函数*************************************** */

/*
 * 移动一组临时文件，并返回新路径数组
 * ids 临时文件id，字符串如：1,2,3...
 * dir 需要移动到的目录
 */
function move_temporary_files($ids = "", $dir = "") {
    if (empty($ids) || empty($dir)) {
        return false;
    }
    $arr = array();
    $sql = " SELECT path FROM " . $GLOBALS['ecs']->table('temporary_files') . " WHERE id IN ($ids) ";
    $path_list = $GLOBALS['db']->getCol($sql);
    foreach ($path_list as $key => $val) {
        $new_path = move_files($val, $dir);
        $arr[] = $new_path;
    }
    $sql = " DELETE FROM " . $GLOBALS['ecs']->table('temporary_files') . " WHERE id IN ($ids) ";
    $GLOBALS['db']->query($sql);
    return $arr;
}

/*
 * 移动文件，并返回新路径
 * path 临时文件路径
 * dir 	需要移动到的目录
 */

function move_files($path = "", $dir = "") {
    if (empty($path) || empty($dir)) {
        return false;
    }
    //创建目录
    if (!file_exists($dir)) {
        make_dir($dir);
    }
    //获取文件名
    $parts = explode('/', $path);
    $new_path = $dir . '/' . end($parts);
    @copy($path, $new_path);
    @unlink($path);
    return $new_path;
}

/*
 * 获取求购单列表
 */

function get_purchase_list($filter = array(), $size = 10, $page = 1, $sort = "add_time", $order = "DESC") {
    
    $where = "";
    //会员编号
    if (isset($filter['user_id'])) {
        $where .= " AND user_id = '$filter[user_id]' ";
    }
    //求购单操作状态
    if (isset($filter['status'])) {
        $where .= " AND status = '$filter[status]' ";
    }
    //求购单审核状态
    if (isset($filter['review_status'])) {
        $where .= " AND review_status = '$filter[review_status]' ";
    }
    //求购单结束状态
    if (isset($filter['is_finished'])) {
        $now = gmtime();
        if ($filter['is_finished'] == 0) {
            $where .= " AND end_time > '$now' ";
        } elseif ($filter['is_finished'] == 1) {
            $where .= " AND end_time < '$now' ";
        }elseif ($filter['is_finished'] == 2) {
            //此处只做标记不作筛选
        }
    }
    //关键词
    if (isset($filter['keyword'])) {
        $where .= " AND subject LIKE '%$filter[keyword]%' ";
    }
    //查询开始时间
    if (isset($filter['start_date'])) {
        $where .= " AND add_time > '$filter[start_date]' ";
    }
    //查询结束时间
    if (isset($filter['end_date'])) {
        $where .= " AND add_time < '$filter[end_date]' ";
    }
    //总数
    $sql = " SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('wholesale_purchase') . " WHERE 1 $where";
    $record_count = $GLOBALS['db']->getOne($sql);
    $page_count = $record_count > 0 ? ceil($record_count / $size) : 1;
    //查询
    $sql = " SELECT * FROM " . $GLOBALS['ecs']->table('wholesale_purchase') . " WHERE 1 $where ORDER BY $sort $order ";
    //分页
    $start = ($page - 1) * $size;
    $res = $GLOBALS['db']->selectLimit($sql, $size, $start);
    //处理
    $arr = array();
    while ($row = $GLOBALS['db']->fetchRow($res)) {
        $add_time = $row['add_time'];
        $end_time = $row['end_time'];
        $row['left_day'] = ceil(($end_time - gmtime()) / 86400);
        $row['left_day'] = $row['left_day'] > 0 ? intval($row['left_day']) : 0;
        $row['add_time'] = local_date('Y-m-d', $add_time);
        $row['add_time_complete'] = local_date('Y-m-d H:i:s', $add_time);
        $row['end_time_complete'] = local_date('Y-m-d H:i:s', $end_time);
        //所有商品数量之和
        $row['goods_number'] = get_table_date('wholesale_purchase_goods', "purchase_id = '" .$row['purchase_id']. "'", array('SUM(goods_number)'), 2);
        //获取商品的图片
        $sql = " SELECT goods_img FROM " . $GLOBALS['ecs']->table('wholesale_purchase_goods') . " WHERE purchase_id = '" .$row['purchase_id']. "' AND goods_img != '' ORDER BY goods_id ASC LIMIT 1 ";
        $goods_img = $GLOBALS['db']->getOne($sql);
        if ($goods_img) {
            $goods_img = unserialize($goods_img);
            $row['img'] = reset($goods_img);
        }
        //商家名称
        $row['shop_name'] = get_shop_name($row['user_id'], 1);
        //是否认证
        $row['is_verified'] = check_users_real($row['user_id'], 1);
        //商家地区
        $row['area_info'] = get_seller_area_info($row['user_id']);
        $row['url'] = build_uri('wholesale_purchase', array('gid' => $row['purchase_id'], 'act' => 'info'), $row['subject']);
        $arr[] = $row;
    }
    return array('purchase_list' => $arr, 'page_count' => $page_count, 'record_count' => $record_count);
}

/*
 * 获取求购单信息
 */

function get_purchase_info($purchase_id = 0) {
    $sql = " SELECT * FROM " . $GLOBALS['ecs']->table('wholesale_purchase') . " WHERE purchase_id = '$purchase_id' ";
    $purchase_info = $GLOBALS['db']->getRow($sql);
    if ($purchase_info) {
        $sql = " SELECT * FROM " . $GLOBALS['ecs']->table('wholesale_purchase_goods') . " WHERE purchase_id = '$purchase_id' ";
        $goods_list = $GLOBALS['db']->getAll($sql);
        foreach ($goods_list as $key => $val) {
            
            $goods_img = $val['goods_img'] ? unserialize($val['goods_img']) : [];
            
            if($goods_img){
                foreach ($goods_img as $key => $row) {
                    $goods_img[$key] = get_image_path($key, $row);
                }
            }

            $goods_list[$key]['goods_img'] = $goods_img;
            
            $cat_info = get_cat_info($val['cat_id'], array('cat_name'), 'wholesale_cat');
            $goods_list[$key]['cat_name'] = $cat_info['cat_name'];
        }
        $purchase_info['goods_list'] = $goods_list;
        $purchase_info['left_day'] = floor(($purchase_info['end_time'] - gmtime()) / 86400);
        $purchase_info['left_day'] = $purchase_info['left_day'] > 0 ? $purchase_info['left_day'] : 0;
        $purchase_info['user_name'] = get_table_date('users', "user_id = '$purchase_info[user_id]'", array('user_name'), 2);
        //商家名称
        $purchase_info['shop_name'] = get_shop_name($purchase_info['user_id'], 1);
        //是否认证
        $purchase_info['is_verified'] = check_users_real($purchase_info['user_id'], 1);
        //商家地区
        $purchase_info['area_info'] = get_seller_area_info($purchase_info['user_id']);
        //收货地区
        $purchase_info['consignee_region'] = get_every_region_name($purchase_info['consignee_region']);
    }
    return $purchase_info;
}

/*
 * 判断商家或者会员是否通过实名认证
 * user_id 会员编号
 * user_type 会员类型：0普通会员，1商家
 * return bool
 */

function check_users_real($user_id = 0, $user_type = 0) {
    $data = get_table_date('users_real', "user_id='$user_id' AND user_type='$user_type' AND review_status=1", array('real_id'), 2);
    if ($data) {
        return true;
    } else {
        return false;
    }
}

/*
 * 判断会员是否是商家
 */

function check_user_is_merchant($user_id = 0) {
    $sql = "SELECT user_id FROM " . $GLOBALS['ecs']->table('admin_user') . " WHERE ru_id = '" . $user_id . "'";
    $is_merchant = 0;
    if ($GLOBALS['db']->getOne($sql, true)) {
        $is_merchant = 1;
    }

    return $is_merchant;
}

/*
 * 返回商家组合地区信息，如：江苏 南京
 * type 类型（暂时无用，后期可根据不同类型返回对应组合）
 */

function get_seller_area_info($ru_id = 0, $type = 0) {
    $data = array();
    switch ($type) {
        case 0:$data = array('province', 'city');
            break;
        default:$data = array('country', 'province', 'city');
            break;
    }
    $area_info = get_table_date('seller_shopinfo', "ru_id='$ru_id'", $data);
    if ($area_info) {
        $area_info = implode(',', $area_info);
        $sql = " SELECT region_name FROM " . $GLOBALS['ecs']->table('region') . " WHERE region_id IN ($area_info) ";
        $region_name = $GLOBALS['db']->getCol($sql);
        if ($region_name) {
            return implode(' ', $region_name);
        }
    }
    return '';
}

/*
 * 返回地区逐级名称，如：中国 上海 上海 普陀区 中山北路
 */

function get_every_region_name($region_id = 0) {
    $arr = array();
    $arr[] = $region_id;
    $parent_id = get_table_date('region', "region_id='$region_id'", array('parent_id'), 2);
    while ($parent_id) {
        $arr[] = $parent_id;
        $parent_id = get_table_date('region', "region_id='$parent_id'", array('parent_id'), 2);
    }
    krsort($arr);
    //获取地区
    $area_info = implode(',', $arr);
    $sql = " SELECT region_name FROM " . $GLOBALS['ecs']->table('region') . " WHERE region_id IN ($area_info) ";
    $region_name = $GLOBALS['db']->getCol($sql);
    if ($region_name) {
        return implode(' ', $region_name);
    }
    return '';
}

/* * **************************************采购部分函数*************************************** */

/*
 * 取得批发活动列表
 * @return   array
 */

function wholesale_list($ru_id) {
    /* 查询会员等级 */
    $rank_list = array();
    $sql = "SELECT rank_id, rank_name FROM " . $GLOBALS['ecs']->table('user_rank');
    $res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchRow($res)) {
        $rank_list[$row['rank_id']] = $row['rank_name'];
    }

    $result = get_filter();
    if ($result === false) {
        /* 过滤条件 */
        $filter['keyword'] = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keyword'] = urldecode($filter['keyword']);
        }
        // var_dump($filter['keyword']);die;
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'w.act_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        $filter['seller_list'] = isset($_REQUEST['seller_list']) && !empty($_REQUEST['seller_list']) ? 1 : 0;  //商家和自营订单标识
        $filter['review_status'] = empty($_REQUEST['review_status']) ? 0 : intval($_REQUEST['review_status']);
        
        //卖场 start
        $filter['rs_id'] = empty($_REQUEST['rs_id']) ? 0 : intval($_REQUEST['rs_id']);
        $adminru = get_admin_ru_id();
        if($adminru['rs_id'] > 0){
            $filter['rs_id'] = $adminru['rs_id'];
        }
        //卖场 end

        $where = "";
        if (!empty($filter['keyword'])) {
            $where .= " AND w.goods_name LIKE '%" . mysql_like_quote($filter['keyword']) . "%'";
        }

        //ecmoban模板堂 --zhuo start
        if ($ru_id > 0) {
            $where .= " AND w.user_id = '$ru_id' ";
        }
        //ecmoban模板堂 --zhuo end

        if ($filter['review_status']) {
            $where .= " AND w.review_status = '" . $filter['review_status'] . "' ";
        }
        
        //卖场
        $where .= get_rs_null_where('w.user_id', $filter['rs_id']);

        //管理员查询的权限 -- 店铺查询 start
        $filter['store_search'] = !isset($_REQUEST['store_search']) ? -1 : intval($_REQUEST['store_search']);
        $filter['merchant_id'] = isset($_REQUEST['merchant_id']) ? intval($_REQUEST['merchant_id']) : 0;
        $filter['store_keyword'] = isset($_REQUEST['store_keyword']) ? trim($_REQUEST['store_keyword']) : '';

        $store_where = '';
        $store_search_where = '';
        if ($filter['store_search'] > -1) {
            if ($ru_id == 0) {
                if ($filter['store_search'] > 0) {
                    if ($_REQUEST['store_type']) {
                        $store_search_where = "AND msi.shopNameSuffix = '" . $_REQUEST['store_type'] . "'";
                    }

                    if ($filter['store_search'] == 1) {
                        $where .= " AND w.user_id = '" . $filter['merchant_id'] . "' ";
                    } elseif ($filter['store_search'] == 2) {
                        $store_where .= " AND msi.rz_shopName LIKE '%" . mysql_like_quote($filter['store_keyword']) . "%'";
                    } elseif ($filter['store_search'] == 3) {
                        $store_where .= " AND msi.shoprz_brandName LIKE '%" . mysql_like_quote($filter['store_keyword']) . "%' " . $store_search_where;
                    }

                    if ($filter['store_search'] > 1) {
                        $where .= " AND (SELECT msi.user_id FROM " . $GLOBALS['ecs']->table('merchants_shop_information') . ' as msi ' .
                                " WHERE msi.user_id = w.user_id $store_where) > 0 ";
                    }
                } else {
                    $where .= " AND w.user_id = 0";
                }
            }
        }
        //管理员查询的权限 -- 店铺查询 end
        $where .= !empty($filter['seller_list']) ? " AND w.user_id > 0 " : " AND w.user_id = 0 "; //区分商家和自营

        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('wholesale') . " AS w " .
                " WHERE 1 $where";
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        /* 分页大小 */
        $filter = page_and_size($filter);

        /* 查询 */
        $sql = "SELECT w.* " .
                "FROM " . $GLOBALS['ecs']->table('wholesale') . " AS w " .
                " WHERE 1 $where " .
                " ORDER BY $filter[sort_by] $filter[sort_order] " .
                " LIMIT " . $filter['start'] . ", $filter[page_size]";

        $filter['keyword'] = stripslashes($filter['keyword']);
        set_filter($filter, $sql);
    } else {
        $sql = $result['sql'];
        $filter = $result['filter'];
    }
    $res = $GLOBALS['db']->query($sql);

    $list = array();
    while ($row = $GLOBALS['db']->fetchRow($res)) {
        $rank_name_list = array();
        if ($row['rank_ids']) {
            $rank_id_list = explode(',', $row['rank_ids']);
            foreach ($rank_id_list as $id) {
                if (isset($rank_list[$id])) {
                    $rank_name_list[] = $rank_list[$id];
                }
            }
        }
        $row['rank_names'] = join(',', $rank_name_list);
        //$row['price_list'] = unserialize($row['prices']);
        $row['ru_name'] = get_shop_name($row['user_id'], 1); //ecmoban模板堂 --zhuo

        $list[] = $row;
    }

    return array('item' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}

function wholesale_attr_group($warehouse_info = array(), $attr_info, $attr_num, $type = 0) {

    $arr = array();

    //格式化数组;
    if ($attr_info) {
        foreach ($attr_info as $k => $v) {
            foreach ($v as $k2 => $v2) {
                if ($k2 == 'attr_values') {
                    $attr[$k] = $v2;
                }
            }
        }
    }

    if ($attr) {
        $comb = combination(array_keys($attr), $attr_num);
        $res = array();
        foreach ($comb as $r) {
            $t = array();
            foreach ($r as $k) {
                $t[$k] = $attr[$k];
            }

            $res = array_merge($res, attrs_group($t));
        }

        //组合数据;
        foreach ($res as $k => $v) {
            if ($type == 1) {
                $arr[$k]['attr_value'] = implode('-', $v);
            } else {
                $arr[$k]['attr_value'] = $v;
            }
        }
    }

    return $arr;
}

/**
 * 商品属性组合
 */
function attrs_group() {
    $t = func_get_args();
    if (func_num_args() == 1) {
        return call_user_func_array(__FUNCTION__, $t[0]);
    }
    $a = array_shift($t);
    if (!is_array($a))
        $a = array($a);
    $a = array_chunk($a, 1, true);
    do {
        $r = array();
        $b = array_shift($t);

        if (!is_array($b))
            $b = array($b);
        foreach ($a as $p)
            foreach (array_chunk($b, 1, true) as $q)
                $r[key($p) . '_' . key($q)] = array_merge($p, $q);
        $a = $r;
    }while ($t);
    return $r;
}

/* 对象转数组 */

function sec_object_to_array($obj) {
    $_arr = is_object($obj) ? get_object_vars($obj) : $obj;
    if ($_arr) {
        foreach ($_arr as $key => $val) {
            $val = (is_array($val)) || is_object($val) ? object_to_array($val) : $val;
            $arr[$key] = $val;
        }
    } else {
        $arr = array();
    }

    return $arr;
}

/**
 * 保存某商品的优惠价格
 * @param   int     $goods_id    商品编号
 * @param   array   $number_list 优惠数量列表
 * @param   array   $price_list  价格列表
 * @return  void
 */
function handle_wholesale_volume_price($goods_id, $is_volume, $number_list, $price_list, $id_list) {
    if ($is_volume) {
        /* 循环处理每个优惠价格 */
        foreach ($price_list AS $key => $price) {
            /* 价格对应的数量上下限 */
            $volume_number = $number_list[$key];
            $volume_id = isset($id_list[$key]) && !empty($id_list[$key]) ? $id_list[$key] : 0;
            if (!empty($price)) {
                if ($volume_id) {
                    $sql = "SELECT id FROM " . $GLOBALS['ecs']->table('wholesale_volume_price') . " WHERE goods_id = '$goods_id' AND (volume_price = '$price' OR volume_number = '$volume_number')";
                    if ($GLOBALS['db']->getOne($sql)) {
                        $sql = "UPDATE " . $GLOBALS['ecs']->table('wholesale_volume_price') . " SET volume_number = '$volume_number', volume_price = '$price' WHERE id = '$volume_id'";
                        $GLOBALS['db']->query($sql);
                    }
                } else {
                    $sql = "SELECT id FROM " . $GLOBALS['ecs']->table('wholesale_volume_price') . " WHERE goods_id = '$goods_id' AND (volume_price = '$price' OR volume_number = '$volume_number')";
                    if (!$GLOBALS['db']->getOne($sql)) {
                        $sql = "INSERT INTO " . $GLOBALS['ecs']->table('wholesale_volume_price') .
                                " (price_type, goods_id, volume_number, volume_price) " .
                                "VALUES ('1', '$goods_id', '$volume_number', '$price')";
                        $GLOBALS['db']->query($sql);
                    }
                }
            }
        }
    } else {
        $sql = "DELETE FROM " . $GLOBALS['ecs']->table('wholesale_volume_price') .
                " WHERE price_type = '1' AND goods_id = '$goods_id'";
        $GLOBALS['db']->query($sql);
    }
}

/**
 * 取得商品优惠价格列表
 *
 * @param   string  $goods_id    商品编号
 * @param   string  $price_type  价格类别(0为全店优惠比率，1为商品优惠价格，2为分类优惠比率)
 *
 * @return  优惠价格列表
 */
function get_wholesale_volume_price_list($goods_id, $price_type = '1') {
    $volume_price = array();
    $temp_index = '0';

    $sql = "SELECT `id` , `volume_number` , `volume_price`" .
            " FROM " . $GLOBALS['ecs']->table('wholesale_volume_price') . "" .
            " WHERE `goods_id` = '" . $goods_id . "' AND `price_type` = '" . $price_type . "'" .
            " ORDER BY `volume_number`";

    $res = $GLOBALS['db']->getAll($sql);

    foreach ($res as $k => $v) {
        $volume_price[$temp_index]['id'] = $v['id'];
        $volume_price[$temp_index]['number'] = $v['volume_number'];
        $volume_price[$temp_index]['price'] = $v['volume_price'];
        $volume_price[$temp_index]['format_price'] = price_format($v['volume_price']);
        $temp_index ++;
    }
    return $volume_price;
}

function set_wholesale_goods_attribute($goods_type = 0, $goods_id = 0, $goods_model = 0) {
    //获取属性列表
    $sql = " SELECT a.attr_id, a.attr_name, a.attr_input_type, a.attr_type, a.attr_values " .
            " FROM " . $GLOBALS['ecs']->table('attribute') . " AS a " .
            " WHERE a.cat_id = " . intval($goods_type) . " AND a.cat_id <> 0 " . " AND a.attr_type <> 2 " . //过滤复选属性
            " ORDER BY a.sort_order, a.attr_type, a.attr_id ";
    $attribute_list = $GLOBALS['db']->getAll($sql);

    //获取商品属性
    $sql = " SELECT v.goods_attr_id, v.attr_id, v.attr_value, v.attr_price, v.attr_sort, v.attr_checked, v.attr_img_flie, v.attr_gallery_flie  " .
            " FROM " . $GLOBALS['ecs']->table('wholesale_goods_attr') . " AS v " .
            " WHERE v.goods_id = '$goods_id' ORDER BY v.attr_sort, v.goods_attr_id ";
    $attr_list = $GLOBALS['db']->getAll($sql);

    foreach ($attribute_list as $key => $val) {
        $is_selected = 0; //属性是否被选择
        $this_value = ""; //唯一属性的值

        if ($val['attr_type'] > 0) {
            if ($val['attr_values']) {
                $attr_values = preg_replace("/\r\n/", ",", $val['attr_values']); //替换空格回车换行符为英文逗号
                $attr_values = explode(',', $attr_values);
            } else {
                $sql = "SELECT attr_value FROM " . $GLOBALS['ecs']->table('wholesale_goods_attr') . " WHERE goods_id = '$goods_id' AND attr_id = '" . $val['attr_id'] . "' ORDER BY attr_sort, goods_attr_id";
                $attr_values = $GLOBALS['db']->getAll($sql);
                $attribute_list[$key]['attr_values'] = get_attr_values_arr($attr_values);
                $attr_values = $attribute_list[$key]['attr_values'];
            }

            $attr_values_arr = array();
            for ($i = 0; $i < count($attr_values); $i++) {
                $goods_attr = $GLOBALS['db']->getRow("SELECT goods_attr_id, attr_price, attr_sort FROM " . $GLOBALS['ecs']->table('wholesale_goods_attr') . " WHERE goods_id = '$goods_id' AND attr_value = '" . $attr_values[$i] . "' AND attr_id = '" . $val['attr_id'] . "' LIMIT 1");
                $attr_values_arr[$i] = array('is_selected' => 0, 'goods_attr_id' => $goods_attr['goods_attr_id'], 'attr_value' => $attr_values[$i], 'attr_price' => $goods_attr['attr_price'], 'attr_sort' => $goods_attr['attr_sort']);
            }
            $attribute_list[$key]['attr_values_arr'] = $attr_values_arr;
        }

        foreach ($attr_list as $k => $v) {
            if ($val['attr_id'] == $v['attr_id']) {
                $is_selected = 1;
                if ($val['attr_type'] == 0) {
                    $this_value = $v['attr_value'];
                } else {
                    foreach ($attribute_list[$key]['attr_values_arr'] as $a => $b) {
                        if ($goods_id) {
                            if ($b['attr_value'] == $v['attr_value']) {
                                $attribute_list[$key]['attr_values_arr'][$a]['is_selected'] = 1;
                            }
                        } else {
                            if ($b['attr_value'] == $v['attr_value']) {
                                $attribute_list[$key]['attr_values_arr'][$a]['is_selected'] = 1;
                                break;
                            }
                        }
                    }
                }
            }
        }

        $attribute_list[$key]['is_selected'] = $is_selected;
        $attribute_list[$key]['this_value'] = $this_value;
        if ($val['attr_input_type'] == 1) {
            $attribute_list[$key]['attr_values'] = preg_split('/\r\n/', $val['attr_values']);
        }
    }

    $attribute_list = get_new_goods_attr($attribute_list);

    $GLOBALS['smarty']->assign('goods_id', $goods_id);
    $GLOBALS['smarty']->assign('goods_model', $goods_model);

    $GLOBALS['smarty']->assign('attribute_list', $attribute_list);
    $goods_attribute = $GLOBALS['smarty']->fetch('templates/library/goods_attribute.lbi');

    $goods_attr_gallery = '';

    $attr_spec = $attribute_list['spec'];

    if ($attr_spec) {
        $arr['is_spec'] = 1;
    } else {
        $arr['is_spec'] = 0;
    }

    $GLOBALS['smarty']->assign('attr_spec', $attr_spec);
    $GLOBALS['smarty']->assign('goods_attr_price', $GLOBALS['_CFG']['goods_attr_price']);
    $goods_attr_gallery = $GLOBALS['smarty']->fetch('templates/library/goods_attr_gallery.lbi');

    $arr['goods_attribute'] = $goods_attribute;
    $arr['goods_attr_gallery'] = $goods_attr_gallery;

    return $arr;
}

/**
 * 单条数据
 * 获取商品属性ID
 * goods_attr_id
 * $where_select 查询条件
 * $select 查询内容
 * $attr_type 唯一属性、单选属性、复选属性
 * $retuen_db 返回值模式（0-单条、1-单组、2-多组）
 */
function get_wholesale_goods_attr_id($where_select = array(), $select = array(), $attr_type = 0, $retuen_db = 0) {

    if ($select) {
        $select = implode(",", $select);
    } else {
        $select = "ga.*, a.*";
    }

    $where = '';
    if (isset($where_select['goods_id'])) {
        $where .= " AND ga.goods_id = '" . $where_select['goods_id'] . "'";
    }

    if (isset($where_select['attr_value']) && !empty($where_select['attr_value'])) {
        $where .= " AND ga.attr_value = '" . $where_select['attr_value'] . "'";
    }

    if (isset($where_select['attr_id']) && !empty($where_select['attr_id'])) {
        $where .= " AND ga.attr_id = '" . $where_select['attr_id'] . "'";
    }

    if (isset($where_select['goods_attr_id']) && !empty($where_select['goods_attr_id'])) {
        $where .= " AND ga.goods_attr_id = '" . $where_select['goods_attr_id'] . "'";
    }

    if (isset($where_select['admin_id']) && !empty($where_select['admin_id'])) {
        $where .= " AND ga.admin_id = '" . $where_select['admin_id'] . "'";
    }

    if ($attr_type && is_array($attr_type)) {
        $attr_type = implode(",", $attr_type);
        $where .= " AND a.attr_type IN($attr_type)";
    } else {
        if ($attr_type) {
            $where .= " AND a.attr_type = '$attr_type'";
        }
    }

    $where .= " ORDER BY a.sort_order, a.attr_id, ga.goods_attr_id";
    if ($retuen_db == 1) {
        $where .= " LIMIT 1";
    }

    $sql = " SELECT $select FROM " . $GLOBALS['ecs']->table('wholesale_goods_attr') . " AS ga, " .
            $GLOBALS['ecs']->table('attribute') . " AS a" .
            " WHERE ga.attr_id = a.attr_id $where";

    if ($retuen_db == 1) {
        return $GLOBALS['db']->getRow($sql);
    } elseif ($retuen_db == 2) {
        return $GLOBALS['db']->getAll($sql);
    } else {
        return $GLOBALS['db']->getOne($sql, true);
    }
}

/**
 * 取货品信息
 *
 * @access  public
 * @param   int         $product_id     货品id
 * @param   int         $filed          字段
 * @param   int         $is_attr        属性组
 * @return  array
 */
function get_wholesale_product_info($product_id, $filed = '', $goods_model = 0, $is_attr = 0) {
    $return_array = array();

    if (empty($product_id)) {
        return $return_array;
    }

    $filed = trim($filed);
    if (empty($filed)) {
        $filed = '*';
    }

    if ($goods_model == 1) {
        $table = "products_warehouse";
    } elseif ($goods_model == 2) {
        $table = "products_area";
    } else {
        $table = "wholesale_products";
    }

    $sql = "SELECT $filed FROM  " . $GLOBALS['ecs']->table($table) . " WHERE product_id = '$product_id'";
    $return_array = $GLOBALS['db']->getRow($sql);

    if ($is_attr == 1) {
        if ($return_array['goods_attr']) {
            $goods_attr_id = str_replace("|", ",", $return_array['goods_attr']);
            $return_array['goods_attr'] = get_wholesale_product_attr_list($goods_attr_id, $return_array['goods_id'], $goods_model, $return_array['warehouse_id'], $return_array['area_id']);
        }
    }

    return $return_array;
}

function get_wholesale_product_attr_list($goods_attr_id = 0, $goods_id = 0, $goods_model = 0, $warehouse_id = 0, $area_id = 0) {

    $leftJion = '';
    if ($goods_model == 1) {
        $where = " AND wa.goods_id = ga.goods_id AND warehouse_id = '$warehouse_id' ";
        $leftJion = ' LEFT JOIN ' . $GLOBALS['ecs']->table('warehouse_attr') . ' AS wa ON wa.goods_attr_id = ga.goods_attr_id ' . $where;
        $select = ", wa.attr_price AS attr_price, warehouse_id, wa.id";
    } elseif ($goods_model == 2) {
        $where = " AND waa.goods_id = ga.goods_id AND area_id = '$area_id' ";
        $leftJion = ' LEFT JOIN ' . $GLOBALS['ecs']->table('warehouse_area_attr') . ' AS waa ON waa.goods_attr_id = ga.goods_attr_id ' . $where;
        $select = ", waa.attr_price AS attr_price, area_id, waa.id";
    } else {
        $select = ", ga.attr_price AS attr_price";
    }

    $sql = "SELECT  ga.goods_attr_id, ga.attr_id, ga.attr_value $select FROM  " . $GLOBALS['ecs']->table('wholesale_goods_attr') . " AS ga " .
            ' LEFT JOIN ' . $GLOBALS['ecs']->table('attribute') . ' AS a ON a.attr_id = ga.attr_id ' .
            $leftJion .
            " WHERE ga.goods_attr_id IN($goods_attr_id) AND ga.goods_id = '$goods_id'" .
            ' ORDER BY a.sort_order, a.attr_id, ga.goods_attr_id';
    $res = $GLOBALS['db']->getAll($sql);

    return $res;
}

//通过一组属性获取货品的相关信息 by wu
function get_wholesale_product_info_by_attr($goods_id = 0, $attr_arr = array(), $goods_model = 0, $region_id = 0) {
    if (!empty($attr_arr)) {
        $where = "";
        //判断商品类型
        if ($goods_model == 1) {
            $table = "products_warehouse";
            $where .= " AND warehouse_id = '$region_id' ";
        } elseif ($goods_model == 2) {
            $table = "products_area";
            $where .= " AND area_id = '$region_id' ";
        } else {
            $table = "wholesale_products";
        }

        $where_select = array('goods_id' => $goods_id);

        if (empty($goods_id)) {
            $admin_id = get_admin_id();
            $where_select['admin_id'] = $admin_id;
        }

        //获取属性组合
        $attr = array();
        foreach ($attr_arr as $key => $val) {
            $where_select['attr_value'] = $val;
            $goods_attr_id = get_wholesale_goods_attr_id($where_select, array('ga.goods_attr_id'), 1);

            if ($goods_attr_id) {
                $attr[] = $goods_attr_id;
            }
        }

        $set = get_find_in_set($attr);
        $sql = " SELECT * FROM " . $GLOBALS['ecs']->table($table) . " WHERE 1 $set AND goods_id = '$goods_id' " . $where . " LIMIT 1 ";
        $product_info = $GLOBALS['db']->getRow($sql);
        return $product_info;
    } else {
        return false;
    }
}

/**
 * 插入或更新商品属性
 *
 * @param   int     $goods_id           商品编号
 * @param   array   $id_list            属性编号数组
 * @param   array   $is_spec_list       是否规格数组 'true' | 'false'
 * @param   array   $value_price_list   属性值数组
 * @return  array                       返回受到影响的goods_attr_id数组
 */
function handle_wholesale_goods_attr($goods_id, $id_list, $is_spec_list, $value_price_list) {
    $goods_attr_id = array();

    /* 循环处理每个属性 */
    foreach ($id_list AS $key => $id) {
        $is_spec = $is_spec_list[$key];
        if ($is_spec == 'false') {
            $value = $value_price_list[$key];
            $price = '';
        } else {
            $value_list = array();
            $price_list = array();
            if ($value_price_list[$key]) {
                $vp_list = explode(chr(13), $value_price_list[$key]);
                foreach ($vp_list AS $v_p) {
                    $arr = explode(chr(9), $v_p);
                    $value_list[] = $arr[0];
                    $price_list[] = $arr[1];
                }
            }
            $value = join(chr(13), $value_list);
            $price = join(chr(13), $price_list);
        }

        // 插入或更新记录
        $sql = "SELECT goods_attr_id FROM " . $GLOBALS['ecs']->table('wholesale_goods_attr') . " WHERE goods_id = '$goods_id' AND attr_id = '$id' AND attr_value = '$value' LIMIT 0, 1";
        $result_id = $GLOBALS['db']->getOne($sql);
        if (!empty($result_id)) {
            $sql = "UPDATE " . $GLOBALS['ecs']->table('wholesale_goods_attr') . "
                    SET attr_value = '$value'
                    WHERE goods_id = '$goods_id'
                    AND attr_id = '$id'
                    AND goods_attr_id = '$result_id'";

            $goods_attr_id[$id] = $result_id;
        } else {
            $sql = "INSERT INTO " . $GLOBALS['ecs']->table('wholesale_goods_attr') . " (goods_id, attr_id, attr_value, attr_price) " .
                    "VALUES ('$goods_id', '$id', '$value', '$price')";
        }

        $GLOBALS['db']->query($sql);

        if ($goods_attr_id[$id] == '') {
            $goods_attr_id[$id] = $GLOBALS['db']->insert_id();
        }
    }

    return $goods_attr_id;
}

/**
 * 将 goods_attr_id 的序列按照 attr_id 重新排序
 *
 * 注意：非规格属性的id会被排除
 *
 * @access      public
 * @param       array       $goods_attr_id_array        一维数组
 * @param       string      $sort                       序号：asc|desc，默认为：asc
 *
 * @return      string
 */
function sort_wholesale_goods_attr_id_array($goods_attr_id_array, $sort = 'asc') {
    if (empty($goods_attr_id_array)) {
        return $goods_attr_id_array;
    }

    //重新排序
    $sql = "SELECT a.attr_type, v.attr_value, v.goods_attr_id, attr_checked
            FROM " . $GLOBALS['ecs']->table('attribute') . " AS a
            LEFT JOIN " . $GLOBALS['ecs']->table('wholesale_goods_attr') . " AS v
                ON v.attr_id = a.attr_id
                AND a.attr_type = 1
            WHERE v.goods_attr_id " . db_create_in($goods_attr_id_array) . "
            ORDER BY a.sort_order, a.attr_id, v.goods_attr_id $sort";

    $row = $GLOBALS['db']->GetAll($sql);

    $return_arr = array();
    foreach ($row as $value) {
        $return_arr['sort'][] = $value['goods_attr_id'];
        $return_arr['row'][$value['goods_attr_id']] = $value;
    }

    return $return_arr;
}

/**
 * 商品的货品规格是否存在
 *
 * @param   string     $goods_attr        商品的货品规格
 * @param   string     $goods_id          商品id
 * @param   int        $product_id        商品的货品id；默认值为：0，没有货品id
 * @return  bool                          true，重复；false，不重复
 */
function check_wholesale_goods_attr_exist($goods_attr, $goods_id, $product_id = 0, $region_id = 0) {
    //判断商品类型 by wu
    $where_products = "";
    $goods_model = $GLOBALS['db']->getOne(" SELECT model_price FROM " . $GLOBALS['ecs']->table("goods") . " WHERE goods_id = '$goods_id' ");
    if ($goods_model == 1) {
        $table = "products_warehouse";
        $where_products .= " AND warehouse_id = '$region_id' ";
    } elseif ($goods_model == 2) {
        $table = "products_area";
        $where_products .= " AND area_id = '$region_id' ";
    } else {
        $table = "wholesale_products";
    }

    $goods_id = intval($goods_id);
    if (strlen($goods_attr) == 0 || empty($goods_id)) {
        return true;    //重复
    }

    if (empty($product_id)) {
        $sql = "SELECT product_id FROM " . $GLOBALS['ecs']->table($table) . "
                WHERE goods_attr = '$goods_attr'
                AND goods_id = '$goods_id'" . $where_products;
    } else {
        $sql = "SELECT product_id FROM " . $GLOBALS['ecs']->table($table) . "
                WHERE goods_attr = '$goods_attr'
                AND goods_id = '$goods_id'
                AND product_id <> '$product_id'" . $where_products;
    }

    $res = $GLOBALS['db']->getOne($sql);

    if (empty($res)) {
        return false;    //不重复
    } else {
        return true;    //重复
    }
}

/**
 * 获得商品的属性和规格
 *
 * @access  public
 * @param   integer $goods_id
 * @return  array
 */
function get_wholesale_goods_properties($goods_id, $warehouse_id = 0, $area_id = 0, $goods_attr_id = '', $attr_type = 0) {
    $attr_array = array();
    if (!empty($goods_attr_id)) {
        $attr_array = explode(',', $goods_attr_id);
    }

    /* 对属性进行重新排序和分组 */
    $sql = "SELECT attr_group " .
            "FROM " . $GLOBALS['ecs']->table('goods_type') . " AS gt, " . $GLOBALS['ecs']->table('wholesale') . " AS g " .
            "WHERE g.goods_id='$goods_id' AND gt.cat_id=g.goods_type";
    $grp = $GLOBALS['db']->getOne($sql);

    if (!empty($grp)) {
        $groups = explode("\n", strtr($grp, "\r", ''));
    }

    //ecmoban模板堂 --zhuo satrt
    $model_attr = get_table_date("goods", "goods_id = '$goods_id'", array('model_attr'), 2);
    $leftJoin = '';
    $select = '';
    /* if ($model_attr == 1) {
      $select = " wap.attr_price as warehouse_attr_price, ";
      $leftJoin = 'LEFT JOIN ' . $GLOBALS['ecs']->table('warehouse_attr') . " AS wap ON g.goods_attr_id = wap.goods_attr_id AND wap.warehouse_id = '$warehouse_id' ";
      } elseif ($model_attr == 2) {
      $select = " waa.attr_price as area_attr_price, ";
      $leftJoin = 'LEFT JOIN ' . $GLOBALS['ecs']->table('warehouse_area_attr') . " AS waa ON g.goods_attr_id = waa.goods_attr_id AND area_id = '$area_id' ";
      } */
    //ecmoban模板堂 --zhuo end

    $goodsAttr = '';
    if ($attr_type == 1 && !empty($goods_attr_id)) {
        $goodsAttr = " and g.goods_attr_id in($goods_attr_id) ";
    }

    //补充 by wu
    $where = "";
    $goods_type = get_table_date('wholesale', "goods_id='$goods_id'", array('goods_type'), 2);
    $where .= " AND a.cat_id = '$goods_type' ";

    /* 获得商品的规格 */
    $sql = "SELECT a.attr_id, a.attr_name, a.attr_group, a.is_linked, a.attr_type, " .
            $select .
            "g.goods_attr_id, g.attr_value, g.attr_price, g.attr_img_flie, g.attr_img_site, g.attr_checked, g.attr_sort " .
            'FROM ' . $GLOBALS['ecs']->table('wholesale_goods_attr') . ' AS g ' .
            'LEFT JOIN ' . $GLOBALS['ecs']->table('attribute') . ' AS a ON a.attr_id = g.attr_id ' .
            $leftJoin .
            "WHERE g.goods_id = '$goods_id' " . $goodsAttr . $where . " AND a.attr_type <> 2 " . //过滤复选属性 by wu
            'ORDER BY a.sort_order, a.attr_id, g.goods_attr_id';

    $res = $GLOBALS['db']->getAll($sql);

    $arr['pro'] = array();     // 属性
    $arr['spe'] = array();     // 规格
    $arr['lnk'] = array();     // 关联的属性

    foreach ($res AS $row) {
        $row['attr_value'] = str_replace("\n", '<br />', $row['attr_value']);

        if ($row['attr_type'] == 0) {
            $group = (isset($groups[$row['attr_group']])) ? $groups[$row['attr_group']] : $GLOBALS['_LANG']['goods_attr'];

            $arr['pro'][$group][$row['attr_id']]['name'] = $row['attr_name'];
            $arr['pro'][$group][$row['attr_id']]['value'] = $row['attr_value'];
        } else {
            //ecmoban模板堂 --zhuo satrt
            if ($model_attr == 1) {
                $attr_price = $row['warehouse_attr_price'];
            } elseif ($model_attr == 2) {
                $attr_price = $row['area_attr_price'];
            } else {
                $attr_price = $row['attr_price'];
            }
            //ecmoban模板堂 --zhuo end

            $img_site = array(
                'attr_img_flie' => $row['attr_img_flie'],
                'attr_img_site' => $row['attr_img_site']
            );

            $attr_info = get_has_attr_info($row['attr_id'], $row['attr_value'], $img_site);
            $row['img_flie'] = !empty($attr_info['attr_img']) ? get_image_path($row['attr_id'], $attr_info['attr_img'], true) : '';
            $row['img_site'] = $attr_info['attr_site'];

            $arr['spe'][$row['attr_id']]['attr_type'] = $row['attr_type'];
            $arr['spe'][$row['attr_id']]['name'] = $row['attr_name'];
            $arr['spe'][$row['attr_id']]['values'][] = array(
                'label' => $row['attr_value'],
                //ecmoban模板堂 --zhuo start
                'img_flie' => $row['img_flie'],
                'img_site' => $row['img_site'],
                'checked' => $row['attr_checked'],
                'attr_sort' => $row['attr_sort'],
                'combo_checked' => get_combo_godos_attr($attr_array, $row['goods_attr_id']),
                //ecmoban模板堂 --zhuo end
                'price' => $attr_price,
                'format_price' => price_format(abs($attr_price), false),
                'id' => $row['goods_attr_id']
            );
        }

        if ($row['is_linked'] == 1) {
            /* 如果该属性需要关联，先保存下来 */
            $arr['lnk'][$row['attr_id']]['name'] = $row['attr_name'];
            $arr['lnk'][$row['attr_id']]['value'] = $row['attr_value'];
        }

        //ecmoban模板堂 --zhuo start
        $arr['spe'][$row['attr_id']]['values'] = get_array_sort($arr['spe'][$row['attr_id']]['values'], 'attr_sort');
        $arr['spe'][$row['attr_id']]['is_checked'] = get_attr_values($arr['spe'][$row['attr_id']]['values']);
        //ecmoban模板堂 --zhuo end
    }

    return $arr;
}

/*
 * 获取批发阶梯价
 */

function get_wholesale_volume_price($goods_id = 0, $goods_number = 0) {
    $sql = " SELECT price_model, goods_price FROM " . $GLOBALS['ecs']->table('wholesale') . " WHERE goods_id = '$goods_id' ";
    $res = $GLOBALS['db']->getRow($sql);
    if ($res['price_model']) {
        //按数量排序
        $sql = " SELECT volume_number, volume_price FROM " . $GLOBALS['ecs']->table('wholesale_volume_price') . " WHERE goods_id = '$goods_id' ORDER BY volume_number ASC ";
        $res['volume_price'] = $GLOBALS['db']->getAll($sql);
        //按数量排序
        /* usort($res['volume_price'], function($a, $b){
          $a1 = $a['volume_number'];
          $b1 = $a['volume_number'];
          if($a1 == $b1) return 0;
          return ($a1 < $b1)? -1:1;
          }); */
        //设置数量阶段
        foreach ($res['volume_price'] as $key => $val) {
            if ($key < count($res['volume_price']) - 1) {
                $range_number = $res['volume_price'][$key + 1]['volume_number'] - 1;
                $res['volume_price'][$key]['range_number'] = $range_number;
            }
            if ($goods_number >= $val['volume_number']) {
                $res['volume_price'][$key]['is_reached'] = 1; //当前达成
                if (isset($res['volume_price'][$key - 1]['is_reached'])) {
                    unset($res['volume_price'][$key - 1]['is_reached']);
                }
            }
        }
    }

    return $res['volume_price'];
}

/*
 * 通过批发ID获取批发商品详情
 */

function get_wholesale_goods_info($act_id, $warehouse_id = 0, $area_id = 0, $select = array()) {
    $left_join = '';
    $left_join .= " LEFT JOIN " . $GLOBALS['ecs']->table('wholesale_cat') . " AS wc ON w.wholesale_cat_id = wc.cat_id ";
    $left_join .= " LEFT JOIN " . $GLOBALS['ecs']->table('goods') . " AS g ON w.goods_id = g.goods_id ";

    $goods_price = " IF(w.price_model=0, w.goods_price, (SELECT MAX(vp.volume_price) FROM " . $GLOBALS['ecs']->table('wholesale_volume_price') . " AS vp WHERE vp.goods_id = w.goods_id)) AS goods_price ";
    $where = " WHERE 1 ";
    $where .= " AND w.act_id = '$act_id' ";
    $sql = " SELECT w.goods_id, w.goods_name, w.goods_type, w.rank_ids, w.price_model, $goods_price, w.goods_number, w.moq, " .
            " wc.cat_id, " .
            " g.user_id, g.market_price, g.shop_price, g.goods_desc, g.goods_img, g.brand_id, g.goods_sn, g.goods_weight, g.goods_unit FROM " .
            $GLOBALS['ecs']->table('wholesale') . " AS w " . $left_join . $where;

    $row = $GLOBALS['db']->getRow($sql);
    
    if ($row !== false) {
        $row['goods_price_formatted'] = price_format($row['goods_price']);
        $row['volume_price'] = get_wholesale_volume_price($row['goods_id']);
        
        //OSS文件存储ecmoban模板堂 --zhuo start
        if($GLOBALS['_CFG']['open_oss'] == 1){
            $bucket_info = get_bucket_info();
            if($row['goods_desc']){
                $desc_preg = get_goods_desc_images_preg($bucket_info['endpoint'], $row['goods_desc']);
                $row['goods_desc'] = $desc_preg['goods_desc'];
            }
        }
        //OSS文件存储ecmoban模板堂 --zhuo end
        
        $row['goods_extend'] = get_wholesale_extend($row['goods_id']); //获取批发商品标识
        $row['goods_img'] = get_image_path($row['goods_id'], $row['goods_img']);
        $row['shopinfo'] = get_shop_name($row['user_id'], 2);
        $row['shopinfo']['logo_thumb'] = str_replace(array('../'), '', $row['shopinfo']['logo_thumb']);
        $row['goods_weight'] = (intval($row['goods_weight']) > 0) ?
                $row['goods_weight'] . $GLOBALS['_LANG']['kilogram'] :
                ($row['goods_weight'] * 1000) . $GLOBALS['_LANG']['gram'];
        
        $brand_info = get_brand_url($row['brand_id']);
        $row['goods_brand_url'] = !empty($brand_info) ? $brand_info['url'] : '';
        $row['brand_thumb'] = !empty($brand_info) ? $brand_info['brand_logo'] : '';
        
        $row['rz_shopName'] = get_shop_name($row['user_id'], 1); //店铺名称	
        $row['goods_unit'] = $row['goods_unit'];

        $build_uri = array(
            'urid' => $row['user_id'],
            'append' => $row['rz_shopName']
        );

        $domain_url = get_seller_domain_url($row['user_id'], $build_uri);
        $row['store_url'] = $domain_url['domain_name'];

        //OSS文件存储ecmoban模板堂 --zhuo start
        if ($GLOBALS['_CFG']['open_oss'] == 1) {
            $bucket_info = get_bucket_info();
            $row['shopinfo']['brand_thumb'] = $bucket_info['endpoint'] . $row['shopinfo']['brand_thumb'];
        }
        //OSS文件存储ecmoban模板堂 --zhuo end
    }

    return $row;
}

/**
 * 商品价格阶梯
 * @param   int     $goods_id     商品ID
 * @return  array
 */
//function get_price_ladder($goods_id)
//{
//    /* 显示商品规格 */
//    $goods_attr_list = array_values(get_goods_attr($goods_id));
//    $sql = "SELECT prices FROM " . $GLOBALS['ecs']->table('wholesale') .
//            "WHERE review_status = 3 AND goods_id = " . $goods_id;
//    $row = $GLOBALS['db']->getRow($sql);
//
//    $arr = array();
//    $_arr = unserialize($row['prices']);
//    if (is_array($_arr))
//    {
//        foreach(unserialize($row['prices']) as $key => $val)
//        {
//            // 显示属性
//            if (!empty($val['attr']))
//            {
//                foreach ($val['attr'] as $attr_key => $attr_val)
//                {
//                    // 获取当前属性 $attr_key 的信息
//                    $goods_attr = array();
//                    foreach ($goods_attr_list as $goods_attr_val)
//                    {
//                        if ($goods_attr_val['attr_id'] == $attr_key)
//                        {
//                            $goods_attr = $goods_attr_val;
//                            break;
//                        }
//                    }
//
//                    // 重写商品规格的价格阶梯信息
//                    if (!empty($goods_attr))
//                    {
//                        $arr[$key]['attr'][] = array(
//                            'attr_id'       => $goods_attr['attr_id'],
//                            'attr_name'     => $goods_attr['attr_name'],
//                            'attr_val'      => (isset($goods_attr['goods_attr_list'][$attr_val]) ? $goods_attr['goods_attr_list'][$attr_val] : ''),
//                            'attr_val_id'   => $attr_val
//                        );
//                    }
//                }
//            }
//
//            // 显示数量与价格
//            foreach($val['qp_list'] as $index => $qp)
//            {
//                $arr[$key]['qp_list'][$qp['quantity']] = price_format($qp['price']);
//            }
//        }
//    }
//
//    return $arr;
//}

/**
 * 获取商品ajax属性是否都选中
 */
function get_wholesale_goods_attr_ajax($goods_id, $goods_attr, $goods_attr_id) {

    $arr = array();
    $arr['attr_id'] = '';
    $where = "";
    if ($goods_attr) {

        $goods_attr = implode(",", $goods_attr);
        $where .= " AND ga.attr_id IN($goods_attr)";

        if ($goods_attr_id) {
            $goods_attr_id = implode(",", $goods_attr_id);
            $where .= " AND ga.goods_attr_id IN($goods_attr_id)";
        }

        $sql = "SELECT ga.goods_attr_id, ga.attr_id, ga.attr_value  FROM " . $GLOBALS['ecs']->table('wholesale_goods_attr') . " AS ga" .
                " LEFT JOIN " . $GLOBALS['ecs']->table('attribute') . " AS a ON ga.attr_id = a.attr_id " .
                " WHERE  ga.goods_id = '$goods_id' $where AND a.attr_type > 0 ORDER BY a.sort_order, a.attr_id, ga.goods_attr_id";
        $res = $GLOBALS['db']->getAll($sql);

        foreach ($res as $key => $row) {
            $arr[$row['attr_id']][$row['goods_attr_id']] = $row;

            $arr['attr_id'] .= $row['attr_id'] . ",";
        }

        if ($arr['attr_id']) {
            $arr['attr_id'] = substr($arr['attr_id'], 0, -1);
            $arr['attr_id'] = explode(",", $arr['attr_id']);
        } else {
            $arr['attr_id'] = array();
        }
    }

    return $arr;
}

function see_more_goods($user_id, $act_id) {
    $goods_price = " IF(w.price_model=0, w.goods_price, (SELECT MAX(vp.volume_price) FROM " . $GLOBALS['ecs']->table('wholesale_volume_price') . " AS vp WHERE vp.goods_id = w.goods_id)) AS price ";
    $sql = " SELECT w.act_id, $goods_price, g.goods_name, g.goods_thumb, g.user_id FROM " . $GLOBALS['ecs']->table('wholesale') . " AS w " .
            " LEFT JOIN " . $GLOBALS['ecs']->table('goods') . " AS g ON g.goods_id = w.goods_id " .
            " WHERE g.user_id = '$user_id' AND w.act_id <> '$act_id' AND enabled = 1 ORDER BY act_id DESC LIMIT 5 ";
    $res = $GLOBALS['db']->getAll($sql);
    if ($res) {
        $arr = array();
        foreach ($res as $k => $v) {
            $arr[$k]['goods_url'] = build_uri('wholesale_goods', array('aid' => $v['act_id']), $v['goods_name']);
            $arr[$k]['goods_name'] = $v['goods_name'];
            $arr[$k]['goods_thumb'] = get_image_path(0, $v['goods_thumb']); //处理图片地址
        }
    }
    return $arr;
}

//function get_min_price($res){
//	if(is_array(unserialize($res['prices']))){
//		$arr = unserialize($res['prices']);
//		foreach($arr['qp_list'] as $v){
//			$price[] = $v['price'];
//		}
//	}
//	return price_format(min($price));	
//}
//货品查询语句处理
function get_find_in_set($attr = array(), $col = 'goods_attr', $sign = '|') {
    $set = "";
    foreach ($attr as $key => $val) {
        $set .= " AND FIND_IN_SET('$val', REPLACE($col, '$sign', ',')) ";
    }
    return $set;
}

//获取主属性列表
function get_wholesale_main_attr_list($goods_id = 0, $attr = array()) {
    $goods_type = get_table_date('wholesale', "goods_id='$goods_id'", array('goods_type'), 2);
    //获取设置属性
    $sql = " SELECT DISTINCT attr_id FROM " . $GLOBALS['ecs']->table('wholesale_goods_attr') . " WHERE goods_id = '$goods_id' ";
    $attr_ids = $GLOBALS['db']->getCol($sql);
    if (!empty($attr_ids)) {
        $attr_ids = implode(',', $attr_ids);
        //获取主属性组
        $sort_order = " ORDER BY sort_order DESC, attr_id DESC ";
        $sql = " SELECT attr_id FROM " . $GLOBALS['ecs']->table('attribute') . " WHERE cat_id = '$goods_type' AND attr_id IN ($attr_ids) $sort_order LIMIT 1 ";
        $attr_id = $GLOBALS['db']->getOne($sql);
        $sql = " SELECT goods_attr_id, attr_value FROM " . $GLOBALS['ecs']->table('wholesale_goods_attr') . " WHERE goods_id = '$goods_id' AND attr_id = '$attr_id' ORDER BY goods_attr_id ";
        $data = $GLOBALS['db']->getAll($sql);
        //处理货品数据
        if ($data) {
            foreach ($data as $key => $val) {
                $new_arr = array_merge($attr, array($val['goods_attr_id']));
                $data[$key]['attr_group'] = implode(',', $new_arr); //属性组合
                $set = get_find_in_set($new_arr);
                $product_info = get_table_date('wholesale_products', "goods_id='$goods_id' $set", array('product_number'));
                $data[$key] = array_merge($data[$key], $product_info);
                if (empty($data[$key])) {
                    unset($data[$key]);
                }
            }
            return $data;
        }
    }

    return false;
}

//计算价格
function calculate_goods_price($goods_id = 0, $goods_number = 0) {
    $goods = get_table_date('goods', "goods_id='$goods_id'", array('market_price'));
    $data = get_table_date('wholesale', "goods_id='$goods_id'", array('price_model', 'goods_price'));
    //合并
    $data = array_merge($data, $goods);
    if ($data['price_model'] == 0) {
        $unit_price = $data['goods_price'];
    } elseif ($data['price_model'] == 1) {
        $sql = " SELECT MIN(volume_price) FROM " . $GLOBALS['ecs']->table('wholesale_volume_price') . " WHERE goods_id = '$goods_id' AND volume_number <= $goods_number ";
        $unit_price = $GLOBALS['db']->getOne($sql);
        //如果不满足阶梯价的最小数量要求，则依然展示最大价格
        if (empty($unit_price)) {
            $sql = " SELECT MAX(volume_price) FROM " . $GLOBALS['ecs']->table('wholesale_volume_price') . " WHERE goods_id = '$goods_id' ";
            $unit_price = $GLOBALS['db']->getOne($sql);
        }
    }
    $data['total_number'] = $goods_number;
    $data['unit_price'] = $unit_price;
    $data['unit_price_formatted'] = price_format($data['unit_price']);
    $data['total_price'] = $unit_price * $goods_number;
    //$data['total_price_formatted'] = price_format($data['total_price']);
    $data['total_price_formatted'] = sprintf('%0.2f', $data['total_price']);
    return $data;
}

//刷新购物车商品价格
function calculate_cart_goods_price($goods_id = 0, $rec_ids = '') {
    if (!empty($_SESSION['user_id'])) {
        $sess_id = " c.user_id = '" . $_SESSION['user_id'] . "' ";
        $sess = "";
    } else {
        $sess_id = " c.session_id = '" . real_cart_mac_ip() . "' ";
        $sess = real_cart_mac_ip();
    }

    if (!empty($goods_id)) {
        $sess_id .= " AND c.goods_id = '$goods_id' ";
    }

    if (!empty($rec_ids)) {
        $sess_id .= " AND c.rec_id IN ($rec_ids) ";
    }

    //重新计算价格并更新价格
    $sql = " SELECT SUM(c.goods_number) FROM " . $GLOBALS['ecs']->table('wholesale_cart') . " AS c WHERE $sess_id ";
    $total_number = $GLOBALS['db']->getOne($sql);
    $price_info = calculate_goods_price($goods_id, $total_number);
    $sql = " UPDATE " . $GLOBALS['ecs']->table('wholesale_cart') . " AS c SET c.goods_price = '" . $price_info['unit_price'] . "' WHERE $sess_id ";
    if ($GLOBALS['db']->query($sql)) {
        return true;
    } else {
        return false;
    }
}

//获取批发购物车商品列表
function wholesale_cart_goods($goods_id = 0, $rec_ids = '') {
    if (!empty($_SESSION['user_id'])) {
        $sess_id = " c.user_id = '" . $_SESSION['user_id'] . "' ";
        $sess = "";
    } else {
        $sess_id = " c.session_id = '" . real_cart_mac_ip() . "' ";
        $sess = real_cart_mac_ip();
    }

    if (!empty($goods_id)) {
        $sess_id .= " AND c.goods_id = '$goods_id' ";
    }

    if (!empty($rec_ids)) {
        $sess_id .= " AND c.rec_id IN ($rec_ids) ";
    }

    $cart_goods = array();
    //区分商家
    $sql = " SELECT DISTINCT ru_id FROM " . $GLOBALS['ecs']->table('wholesale_cart') . " AS c WHERE $sess_id ";
    $ru_ids = $GLOBALS['db']->getCol($sql);
    foreach ($ru_ids as $key => $val) {
        $data = array();
        $data['ru_id'] = $val;
        $data['shop_name'] = get_shop_name($val, 1);

        /* 客服部分 start */
        /*  @author-bylu 判断当前商家是否允许"在线客服" start  */
        $shop_information = get_shop_name($val); //通过ru_id获取到店铺信息;
        $data['is_IM'] = $shop_information['is_IM']; //平台是否允许商家使用"在线客服";
        //判断当前商家是平台,还是入驻商家 bylu
        if ($val == 0) {
            //判断平台是否开启了IM在线客服
            if ($GLOBALS['db']->getOne("SELECT kf_im_switch FROM " . $GLOBALS['ecs']->table('seller_shopinfo') . "WHERE ru_id = 0", true)) {
                $data['is_dsc'] = true;
            } else {
                $data['is_dsc'] = false;
            }
        } else {
            $data['is_dsc'] = false;
        }
        /*  @author-bylu  end  */
        //自营有自提点--val=ru_id
        $sql = "select * from " . $GLOBALS['ecs']->table('seller_shopinfo') . " where ru_id='" . $val . "'";
        $basic_info = $GLOBALS['db']->getRow($sql);
        $data['kf_type'] = $basic_info['kf_type'];

        /* 处理客服旺旺数组 by kong */
        if ($basic_info['kf_ww']) {
            $kf_ww = array_filter(preg_split('/\s+/', $basic_info['kf_ww']));
            $kf_ww = explode("|", $kf_ww[0]);
            if (!empty($kf_ww[1])) {
                $data['kf_ww'] = $kf_ww[1];
            } else {
                $data['kf_ww'] = "";
            }
        } else {
            $data['kf_ww'] = "";
        }
        /* 处理客服QQ数组 by kong */
        if ($basic_info['kf_qq']) {
            $kf_qq = array_filter(preg_split('/\s+/', $basic_info['kf_qq']));
            $kf_qq = explode("|", $kf_qq[0]);
            if (!empty($kf_qq[1])) {
                $data['kf_qq'] = $kf_qq[1];
            } else {
                $data['kf_qq'] = "";
            }
        } else {
            $data['kf_qq'] = "";
        }
        /* 客服部分 end */

        //区分商品
        $sql = " SELECT DISTINCT goods_id FROM " . $GLOBALS['ecs']->table('wholesale_cart') . " AS c WHERE $sess_id AND c.ru_id = '$val' ";
        $goods_ids = $GLOBALS['db']->getCol($sql);
        foreach ($goods_ids as $a => $g) {
            //先更新购物车数据
            calculate_cart_goods_price($g, $rec_ids);
            //查询购物车数据
            $sql = " SELECT c.rec_id, c.goods_price, c.goods_number, c.goods_attr_id " .
                    " FROM " . $GLOBALS['ecs']->table('wholesale_cart') . " AS c " .
                    " WHERE $sess_id AND c.ru_id = '$val' AND c.goods_id = '$g' ORDER BY c.goods_attr_id"; //按属性序号排序
            $res = $GLOBALS['db']->getAll($sql);
            //商品属性数据
            $total_number = 0;
            $total_price = 0;
            foreach ($res as $k => $v) {
                $res[$k]['goods_price_formatted'] = price_format($v['goods_price']);
                $res[$k]['total_price'] = $v['goods_price'] * $v['goods_number'];
                $res[$k]['total_price_formatted'] = price_format($res[$k]['total_price']);
                $res[$k]['goods_attr'] = get_goods_attr_array($v['goods_attr_id']);
                //统计数量和价格
                $total_number += $v['goods_number'];
                $total_price += $res[$k]['total_price'];
            }
            //补充商品数据
            $goods_data = get_table_date('wholesale', "goods_id='$g'", array('act_id', 'goods_id, goods_name, price_model, goods_price', 'moq'));
            $goods_data['goods_thumb'] = get_table_date('goods', "goods_id='$g'", array('goods_thumb'), 2);
            $goods_data['total_number'] = $total_number;
            $goods_data['total_price'] = $total_price;
            if (empty($goods_data['price_model'])) {
                if ($total_number >= $goods_data['moq']) {
                    $goods_data['is_reached'] = 1;
                }
            } else {
                $goods_data['volume_price'] = get_wholesale_volume_price($g, $total_number);
            }
            $goods_data['list'] = $res;
            $goods_data['count'] = count($res); //记录数量
            $data['goods_list'][] = $goods_data;
        }
        $cart_goods[] = $data;
    }

    return $cart_goods;
}

//获取购物车商品数量和价格
function wholesale_cart_info($goods_id = 0, $rec_ids = '') {
    if (!empty($_SESSION['user_id'])) {
        $sess_id = " c.user_id = '" . $_SESSION['user_id'] . "' ";
        $sess = "";
    } else {
        $sess_id = " c.session_id = '" . real_cart_mac_ip() . "' ";
        $sess = real_cart_mac_ip();
    }

    if (!empty($goods_id)) {
        $sess_id .= " AND c.goods_id = '$goods_id' ";
    }

    if (!empty($rec_ids)) {
        $sess_id .= " AND c.rec_id IN ($rec_ids) ";
    }

    $cart_info = array(
        'rec_count' => 0,
        'total_number' => 0,
        'total_price' => 0.00,
        'total_price_formatted' => ''
    );
    $sql = " SELECT goods_price, goods_number FROM " . $GLOBALS['ecs']->table('wholesale_cart') . " AS c WHERE $sess_id ";
    $data = $GLOBALS['db']->getAll($sql);
    foreach ($data as $key => $val) {
        $cart_info['rec_count'] += 1;
        $cart_info['total_number'] += $val['goods_number'];
        $total_price = $val['goods_number'] * $val['goods_price'];
        $cart_info['total_price'] += $total_price;
    }
    $cart_info['total_price_formatted'] = price_format($cart_info['total_price']);
    return $cart_info;
}

/*
 * 处理属性，返回数组
 * goods_attr_id 字符串，如 1,2,3
 * 返回数组
 */

function get_goods_attr_array($goods_attr_id = '') {
    if (empty($goods_attr_id)) {
        return false;
    }
    $sort_order = " ORDER BY a.sort_order ASC, a.attr_id ASC ";
    $sql = " SELECT a.attr_name, ga.attr_value FROM " . $GLOBALS['ecs']->table('wholesale_goods_attr') . " AS ga " .
            " LEFT JOIN " . $GLOBALS['ecs']->table('attribute') . " AS a ON a.attr_id = ga.attr_id " .
            " WHERE ga.goods_attr_id IN ($goods_attr_id) " . $sort_order;
    $res = $GLOBALS['db']->getAll($sql);

    return $res;
}

/*
 * 处理属性，返回数组
 * goods_attr_id 字符串，如 1,2,3
 * 返回数组
 */

function get_select_record_data($goods_id = 0, $attr_num_array = array()) {
    //处理数组
    $new_array = array();
    foreach ($attr_num_array as $key => $val) {
        $arr = explode(',', $val['attr']); //转成数组
        $end_attr = end($arr); //取出最后一个属性
        array_pop($arr); //销毁最后一个元素
        $attr_key = implode(',', $arr); //生成一个不包含最后元素的键名
        $new_array[$attr_key][$end_attr] = $val['num']; //加入数组
    }
    //给数组赋值
    $record_data = array();
    foreach ($new_array as $key => $val) {
        $data = array();
        $data['main_attr'] = get_goods_attr_array($key); //获取除最后一个属性信息
        foreach ($val as $k => $v) {
            $a = array();
            $a['attr_num'] = $v;
            $b = get_goods_attr_array($k); //获取最后元素的属性
            $c = $b[0]; //取第一条记录
            $a = array_merge($a, $c); //合并数据
            $data['end_attr'][] = $a;
        }
        $record_data[$key] = $data;
    }
    //print_arr($record_data);
    return $record_data;
}

//获取成交量
function get_sale($goods_id = 0) {
    $sql = "SELECT SUM(og.goods_number) FROM " . $GLOBALS['ecs']->table('wholesale_order_info') . " AS oi "
            . " LEFT JOIN " . $GLOBALS['ecs']->table('wholesale_order_goods') . " AS og ON og.order_id = oi.order_id "
            . " WHERE oi.main_order_id > 0 AND oi.is_delete = 0 AND oi.main_order_id > 0 AND og.goods_id=" . $goods_id;
    $count = $GLOBALS['db']->getOne($sql);
    return $count;
}

/**
 * 查询当前商品属性
 */
function get_dialog_wholesale_goods_attr_type($attr_id = 0, $goods_id = 0) {
    $sql = "SELECT goods_attr_id, attr_id, attr_value FROM " . $GLOBALS['ecs']->table('wholesale_goods_attr') . " WHERE attr_id = '$attr_id' AND goods_id = '$goods_id' ORDER BY attr_sort";
    $res = $GLOBALS['db']->getAll($sql);

    if ($res) {
        foreach ($res as $key => $row) {
            if ($goods_id) {
                $res[$key]['is_selected'] = 1;
            } else {
                $res[$key]['is_selected'] = 0;
            }
        }
    }

    return $res;
}

//操作新数组attr_id --应用后台 start
function get_new_wholesale_goods_attribute($goods_id, $_attribute = array()) {

    $arr = array();
    foreach ($_attribute as $key => $row) {
        $arr[$key] = $row;
        $arr[$key]['attr_valuesId'] = get_goods_attr_values_id($row['attr_values'], $row['goods_attr_id']);
        $arr[$key]['goods_attr'] = get_wholesale_attribute_goods_attr($row['attr_id']);
        $arr[$key]['goods_attr'] = wholesale_product_list($goods_id, '', $arr[$key]['goods_attr']['goods_attr_id']);
    }

    return $arr;
}

function get_wholesale_attribute_goods_attr($attr_id = 0) {
    $sql = "select goods_attr_id from " . $GLOBALS['ecs']->table('wholesale_goods_attr') . " where attr_id = '$attr_id'";
    $res = $GLOBALS['db']->getAll($sql);

    $arr = array();
    foreach ($res as $key => $row) {
        $arr[$key] = $row;
        $arr['goods_attr_id'] .= $row['goods_attr_id'] . ",";
    }

    if (!empty($arr['goods_attr_id'])) {
        $arr['goods_attr_id'] = substr($arr['goods_attr_id'], 0, -1);
    }

    return $arr;
}

/**
 * 获得商品的货品列表
 *
 * @access  public
 * @params  integer $goods_id
 * @params  string  $conditions
 * @return  array
 */
function wholesale_product_list($goods_id, $conditions = '') {
    /* 过滤条件 */
    $param_str = '-' . $goods_id;
    $result = get_filter($param_str);
    if ($result === false) {
        $day = getdate();
        $today = local_mktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']);

        $filter['goods_id'] = $goods_id;
        $filter['keyword'] = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
        $filter['stock_warning'] = empty($_REQUEST['stock_warning']) ? 0 : intval($_REQUEST['stock_warning']);

        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'product_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        $filter['extension_code'] = empty($_REQUEST['extension_code']) ? '' : trim($_REQUEST['extension_code']);
        $filter['page_count'] = isset($filter['page_count']) ? $filter['page_count'] : 1;

        $where = '';

        /* 库存警告 */
        if ($filter['stock_warning']) {
            $where .= ' AND goods_number <= warn_number ';
        }

        /* 关键字 */
        if (!empty($filter['keyword'])) {
            $where .= " AND (product_sn LIKE '%" . $filter['keyword'] . "%')";
        }

        $where .= $conditions;

        /* 记录总数 */
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('wholesale_products') . " AS p WHERE goods_id = $goods_id $where";
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $sql = "SELECT product_id, goods_id, goods_attr, product_sn, bar_code, product_price, product_number
                FROM " . $GLOBALS['ecs']->table('wholesale_products') . " AS g
                WHERE goods_id = $goods_id $where
                ORDER BY $filter[sort_by] $filter[sort_order]";

        $filter['keyword'] = stripslashes($filter['keyword']);
        //set_filter($filter, $sql, $param_str);
    } else {
        $sql = $result['sql'];
        $filter = $result['filter'];
    }
    $row = $GLOBALS['db']->getAll($sql);

    /* 处理规格属性 */
    $goods_attr = wholesale_product_goods_attr_list($goods_id);
    foreach ($row as $key => $value) {
        $_goods_attr_array = explode('|', $value['goods_attr']);
        if (is_array($_goods_attr_array)) {
            $_temp = '';
            foreach ($_goods_attr_array as $_goods_attr_value) {
                $_temp[] = $goods_attr[$_goods_attr_value];
            }
            $row[$key]['goods_attr'] = $_temp;
        }
    }

    return array('product' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}

/**
 * 获得商品的规格属性值列表
 *
 * @access      public
 * @params      integer         $goods_id
 * @return      array
 */
function wholesale_product_goods_attr_list($goods_id) {
    if (empty($goods_id)) {
        return array();  //$goods_id不能为空
    }

    $sql = "SELECT goods_attr_id, attr_value FROM " . $GLOBALS['ecs']->table('wholesale_goods_attr') . " WHERE goods_id = '$goods_id'";
    $results = $GLOBALS['db']->getAll($sql);

    $return_arr = array();
    foreach ($results as $value) {
        $return_arr[$value['goods_attr_id']] = $value['attr_value'];
    }

    return $return_arr;
}

/**
 * 获得商品已添加的规格列表
 *
 * @access      public
 * @params      integer         $goods_id
 * @return      array
 */
function get_wholesale_goods_specifications_list($goods_id) {
    $where = "";
    $admin_id = get_admin_id();
    if (empty($goods_id)) {
        if ($admin_id) {
            $where .= " AND admin_id = '$admin_id'";
        } else {
            return array();  //$goods_id不能为空
        }
    }

    $sql = "SELECT g.goods_attr_id, g.attr_value, g.attr_id, a.attr_name
            FROM " . $GLOBALS['ecs']->table('wholesale_goods_attr') . " AS g
                LEFT JOIN " . $GLOBALS['ecs']->table('attribute') . " AS a
                    ON a.attr_id = g.attr_id
            WHERE goods_id = '$goods_id'
            AND a.attr_type = 1" . $where .
            " ORDER BY a.sort_order, a.attr_id, g.goods_attr_id";
    $results = $GLOBALS['db']->getAll($sql);

    return $results;
}

/**
 * 批量添加货号(默认模式) bylu
 * @param $goods_list 数据列表
 * @param $attr_nums 属性个数
 * @return array 添加货号后属性
 */
function get_wholesale_produts_list2($goods_list, $attr_num = 0) {

    $arr = array();
    for ($i = 0; $i < count($goods_list); $i++) {

        $goods_where = array(
            'id' => $goods_list[$i]['goods_id'],
            'name' => $goods_list[$i]['goods_name'],
            'sn_name' => $goods_list[$i]['goods_sn'],
            'seller_id' => $goods_list[$i]['seller_id'],
        );

        $arr[$i]['goods_id'] = get_products_name($goods_where, 'goods');
        $arr[$i]['warehouse_id'] = 0;

        for ($j = 0; $j < $attr_num; $j++) {

            if ($j == $attr_num - 1) {
                $attr_name[$j] = $goods_list[$i]['goods_attr' . $j]; //属性名称 bylu;
                $sql = "SELECT goods_attr_id FROM " . $GLOBALS['ecs']->table('wholesale_goods_attr') . " WHERE attr_value='" . $goods_list[$i]['goods_attr' . $j] . "' AND goods_id = '" . $arr[$i]['goods_id'] . "'";
                $attr[$j] = $GLOBALS['db']->getOne($sql); //属性id bylu;
            } else {
                $attr_name[$j] = !empty($goods_list[$i]['goods_attr' . $j]) ? $goods_list[$i]['goods_attr' . $j] . '|' : ''; //属性名称 bylu;
                $sql = "SELECT goods_attr_id FROM " . $GLOBALS['ecs']->table('wholesale_goods_attr') . " WHERE attr_value='" . $goods_list[$i]['goods_attr' . $j] . "' AND goods_id = '" . $arr[$i]['goods_id'] . "'";
                $goods_attr_id = $GLOBALS['db']->getOne($sql);
                $attr[$j] = !empty($goods_attr_id) ? $goods_attr_id . '|' : ''; //属性id bylu;
            }
        }

        $arr[$i]['goods_attr'] = implode('', $attr); //拼凑属性ID;
        $arr[$i]['goods_attr_name'] = implode('', $attr_name); //拼凑属性名称;

        /* if ($GLOBALS['_CFG']['goods_attr_price'] == 1) {
          $arr[$i]['product_price'] = $goods_list[$i]['product_price'];
          } */

        $arr[$i]['product_number'] = $goods_list[$i]['product_number'];
        //$arr[$i]['product_warn_number'] = $goods_list[$i]['product_warn_number'];
        //如果货品编号为空,自动生成货品编号;
        if (empty($goods_list[$i]['product_sn'])) {
            $arr[$i]['product_sn'] = $goods_list[$i]['goods_sn'] . 'g_p' . $i;
        } else {
            $arr[$i]['product_sn'] = $goods_list[$i]['product_sn'];
        }

        //$arr[$i]['bar_code'] = $goods_list[$i]['bar_code'];
    }

    return $arr;
}

/**
 *  获取用户指定范围的批发订单列表
 *
 * @access  public
 * @param   int         $user_id        用户ID号
 * @param   int         $num            列表最大数量
 * @param   int         $start          列表起始位置
 * @return  array       $order_list     订单列表
 */
function get_wholesale_orders($user_id, $record_count, $page, $where = '', $order = '', $pagesize = 10) {
    require_once('includes/cls_pager.php');

    if ($order) {
        $idTxt = $order->idTxt;
        $keyword = $order->keyword;
        $action = $order->action;
        $type = $order->type;
        $status_keyword = $order->status_keyword;
        $date_keyword = $order->date_keyword;

        $id = '"';
        $id .= $user_id . "=";
        $id .= "idTxt@" . $idTxt . "|";
        $id .= "keyword@" . $keyword . "|";
        $id .= "action@" . $action . "|";
        $id .= "type@" . $type . "|";

        if ($status_keyword) {
            $id .= "status_keyword@" . $status_keyword . "|";
        }

        if ($date_keyword) {
            $id .= "date_keyword@" . $date_keyword;
        }

        $substr = substr($id, -1);
        if ($substr == "|") {
            $id = substr($id, 0, -1);
        }

        $id .= '"';
    } else {
        $id = $user_id;
    }

    $config = array('header' => $GLOBALS['_LANG']['pager_2'], "prev" => "<i><<</i>" . $GLOBALS['_LANG']['page_prev'], "next" => "" . $GLOBALS['_LANG']['page_next'] . "<i>>></i>", "first" => $GLOBALS['_LANG']['page_first'], "last" => $GLOBALS['_LANG']['page_last']);
    $user_order = new Pager($record_count, $pagesize, '', $id, 0, $page, 'wholesale_order_gotoPage', 1, 0, 0, $config);
    $limit = $user_order->limit;
    $pager = $user_order->fpage(array(0, 4, 5, 6, 9));

    $left_join = '';
    if (defined('THEME_EXTENSION')) {
        $left_join = " LEFT JOIN " . $GLOBALS['ecs']->table('goods') . " AS g on og.goods_id = g.goods_id "; //关联查找评论用 liu   
    }

    $select = " (SELECT count(*) FROM " . $GLOBALS['ecs']->table('comment') . " AS c WHERE c.comment_type = 0 AND c.id_value = og.goods_id AND c.rec_id = og.rec_id AND c.parent_id = 0 AND c.user_id = '$user_id') AS sign1, " .
            "(SELECT count(*) FROM " . $GLOBALS['ecs']->table('comment_img') . " AS ci, " . $GLOBALS['ecs']->table('comment') . " AS c" . " WHERE c.comment_type = 0 AND c.id_value = og.goods_id AND ci.rec_id = og.rec_id AND c.parent_id = 0 AND c.user_id = '$user_id' AND ci.comment_id = c.comment_id )  AS sign2, ";

    /* 取得订单列表 */
    $arr = array();
    $sql = "SELECT og.ru_id,og.goods_attr ,og.goods_number, oi.inv_type, oi.inv_payee,oi.tax_id, oi.postscript, oi.main_order_id, oi.consignee, oi.order_id,oi.mobile, oi.order_sn, oi.order_status, oi.add_time,oi.pay_id,oi.pay_fee,oi.pay_time,oi.pay_status, " .
			"(oi.order_amount) AS total_fee, ".
            "og.goods_id, oi.email, oi.address, oi.province, oi.city, oi.district " .
            " FROM " . $GLOBALS['ecs']->table('wholesale_order_info') . " as oi" .
            " left join " . $GLOBALS['ecs']->table('wholesale_order_goods') . " as og on oi.order_id = og.order_id" .
            $left_join .
            " WHERE oi.user_id = '$user_id' and oi.is_delete = 0 " . $where .
            " and (select count(*) from " . $GLOBALS['ecs']->table('wholesale_order_info') . " as oi2 where oi2.main_order_id = oi.order_id) = 0 " . //主订单下有子订单时，则主订单不显示
            "group by oi.order_id ORDER BY oi.add_time DESC " . $limit;

    $res = $GLOBALS['db']->query($sql);

    while ($row = $GLOBALS['db']->fetchRow($res)) {

        $row['pay_name'] = $GLOBALS['db']->getOne("SELECT pay_name FROM".$GLOBALS['ecs']->table('payment')."WHERE pay_id = '" . $row['pay_id']. "'");
        
        
        //ecmoban模板堂 --zhuo start
        $row['user_order'] = $row['order_status'];

        if ($row['user_order'] == OS_UNCONFIRMED) { //未确认
            $row['delete_yes'] = 0;
        } elseif ($row['user_order'] == OS_CONFIRMED) { //已确认
            $row['delete_yes'] = 1;
        } else {
            $row['delete_yes'] = 0;
        }

        //ecmoban模板堂 --zhuo end
        //$row['shipping_status'] = ($row['shipping_status'] == SS_SHIPPED_ING) ? SS_PREPARING : $row['shipping_status'];
        $row['order_status'] = $GLOBALS['_LANG']['os'][$row['order_status']];

        $br = '';
        $order_over = 0;
        if ($row['user_order'] == OS_CONFIRMED) {
            $order_over = 1; //订单完成
            $row['order_status'] = $GLOBALS['_LANG']['ss_received'];

            $row['handler_order_status'] = false;

            $row['handler_act'] = 'commented_view';
            $row['handler'] = "<a href=\"user.php?act=commented_view&order_id=" . $row['order_id'] . $sign . '">' . $row['original_handler'] . '</a><br/>';
            @$row['original_handler_return'] = $GLOBALS['_LANG']['return'];
            @$row['handler_return_act'] = 'goods_order';
            @$row['handler_return'] = "<a href=\"user.php?act=goods_order&order_id=" . $row['order_id'] . '" style="margin-left:5px;" >' . $GLOBALS['_LANG']['return'] . "</a><br/>";
        } else {
            if ($row['user_order'] == OS_UNCONFIRMED) {
                $order_over = 0; //订单完成
                $row['handler_order_status'] = false;
                $row['handler'] = '';
            } else {
                $br = "<br/>";
            }
        }



        //ecmoban模板堂 --zhuo start
        $ru_id = $row['ru_id'];

        $row['order_goods'] = get_wholesale_order_goods_toInfo($row['order_id']);
        $row['order_goods_count'] = count($row['order_goods']);

        $order_id = $row['order_id'];
        $date = array('order_id');
        $order_child = count(get_table_date('wholesale_order_info', "main_order_id='$order_id'", $date, 1));
        $row[$key]['order_child'] = $order_child;

        $sql = "select order_id from " . $GLOBALS['ecs']->table('wholesale_order_info') . " where main_order_id = '" . $row['main_order_id'] . "' and main_order_id > 0";
        $order_count = count($GLOBALS['db']->getAll($sql));

        $sql = "select kf_type, kf_ww, kf_qq  from " . $GLOBALS['ecs']->table('seller_shopinfo') . " where ru_id='$ru_id'";
        $basic_info = $GLOBALS['db']->getRow($sql);



        $province = get_order_region_name($row['province']);
        $city = get_order_region_name($row['city']);
        $district = get_order_region_name($row['district']);

        if ($district['region_name']) {
            $district_name = $district['region_name'];
        }

        $address_detail = $province['region_name'] . "&nbsp;" . $city['region_name'] . "市" . "&nbsp;" . $district_name;

        $delivery['delivery_time'] = local_date($GLOBALS['_CFG']['time_format'], $delivery['update_time']);

        if ($handle_tyoe == 1) {
            $row['order_status'] = str_replace(array('<br />'), '', $row['order_status']);
        }

        if (defined('THEME_EXTENSION')) {
            $row['order_status'] = str_replace(array('<br>', '<br />'), array('', '，'), $row['order_status']);
        }

        $row['shop_name'] = get_shop_name($ru_id, 1);

        $build_uri = array(
            'urid' => $ru_id,
            'append' => $row['shop_name']
        );

        $domain_url = get_seller_domain_url($ru_id, $build_uri);
        $row['shop_url'] = $domain_url['domain_name'];

        /* 处理客服QQ数组 by kong */
        if ($basic_info['kf_qq']) {
            $kf_qq = array_filter(preg_split('/\s+/', $basic_info['kf_qq']));
            $kf_qq = explode("|", $kf_qq[0]);
            if (!empty($kf_qq[1])) {
                $kf_qq_one = $kf_qq[1];
            } else {
                $kf_qq_one = "";
            }
        } else {
            $kf_qq_one = "";
        }
        /* 处理客服旺旺数组 by kong */
        if ($basic_info['kf_ww']) {
            $kf_ww = array_filter(preg_split('/\s+/', $basic_info['kf_ww']));
            $kf_ww = explode("|", $kf_ww[0]);
            if (!empty($kf_ww[1])) {
                $kf_ww_one = $kf_ww[1];
            } else {
                $kf_ww_one = "";
            }
        } else {
            $kf_ww_one = "";
        }

        /*  @author-bylu 判断当前商家是否允许"在线客服" start  */
        $shop_information = get_shop_name($ru_id); //通过ru_id获取到店铺信息;
        //判断当前商家是平台,还是入驻商家 bylu
        if ($ru_id == 0) {
            //判断平台是否开启了IM在线客服
            if ($GLOBALS['db']->getOne("SELECT kf_im_switch FROM " . $GLOBALS['ecs']->table('seller_shopinfo') . " WHERE ru_id = 0", true)) {
                $row['is_dsc'] = true;
            } else {
                $row['is_dsc'] = false;
            }
        } else {
            $row['is_dsc'] = false;
        }



        /*  @author-bylu  end  */
        $arr[] = array('order_id' => $row['order_id'],
            'order_sn' => $row['order_sn'],
            'order_time' => local_date($GLOBALS['_CFG']['time_format'], $row['add_time']),
            'sign' => $row['sign'],
            'sign' => $shop_information['is_IM'], //平台是否允许商家使用"在线客服";
            'is_dsc' => $row['is_dsc'],
            'order_status' => $row['order_status'],
            'goods_attr' => $row['goods_attr'],
            //ecmoban模板堂 --zhuo start
            'status_number' => $status_number,
            'pay_name' => $row['pay_name'],
            'consignee' => $row['consignee'],
            'postscript' => $row['postscript'],
            'inv_type' => $row['inv_type'],
            'inv_payee' => $row['inv_payee'],
            'tax_id' => $row['tax_id'],
            'main_order_id' => $row['main_order_id'],
            'shop_name' => $row['shop_name'], //店铺名称	,
            'mobile' => $row['mobile'],
            'shop_url' => $row['shop_url'], //店铺名称	,
            'order_goods' => $row['order_goods'],
            'order_goods_count' => $row['order_goods_count'],
            'order_child' => $order_child,
            'no_picture' => $GLOBALS['_CFG']['no_picture'],
            'order_child' => $order_child,
            'delete_yes' => $row['delete_yes'],
            'invoice_no' => $row['invoice_no'],
            'shipping_name' => $row['shipping_name'],
            'pay_name' => $row['pay_name'],
            'email' => $row['email'],
            'address_detail' => $row['address_detail'],
            'address' => $row['address'],
            'address_detail' => $address_detail,
            'tel' => $row['tel'],
            'delivery_time' => $delivery['delivery_time'],
            'order_count' => $order_count,
            'kf_type' => $basic_info['kf_type'],
            'kf_ww' => $kf_ww_one,
            'kf_qq' => $kf_qq_one,
            //ecmoban模板堂 --zhuo end
            'total_fee' => price_format($row['total_fee'], false),
            'handler_return' => $row['handler_return'],
            'handler' => $row['handler'],
            'original_handler' => $row['original_handler'],
            'original_handler_return' => $row['original_handler_return'],
            'handler_act' => $row['handler_act'],
            'handler_return_act' => $row['handler_return_act'],
            'return_url' => $row['return_url'],
            'remind' => $row['remind'] ? $row['remind'] : '',
            'handler_order_status' => $row['handler_order_status'] ? true : false,
            'order_over' => $order_over,
            'pay_time' => local_date($GLOBALS['_CFG']['time_format'], $row['pay_time']),
            'pay_status' => $row['pay_status'],
            'pay_name' => $row['pay_name'],
            'pay_fee' => $row['pay_fee'],
        );
    }

    $order_list = array('order_list' => $arr, 'pager' => $pager, 'record_count' => $record_count);
    return $order_list;
}

//根据订单商品查询批发商品信息
function get_wholesale_order_goods_toInfo($order_id = 0) {
    $sql = "SELECT w.act_id, g.goods_id, g.goods_name, g.goods_thumb, og.goods_number, og.goods_price,og.goods_attr, og.goods_price, og.goods_name AS extension_name, oi.order_sn FROM " . $GLOBALS['ecs']->table('wholesale_order_goods') . " as og " .
            " LEFT JOIN " . $GLOBALS['ecs']->table('wholesale_order_info') . " AS oi ON og.order_id = oi.order_id " .
            " LEFT JOIN " . $GLOBALS['ecs']->table('wholesale') . " AS w ON w.goods_id = og.goods_id " .
            "left join " . $GLOBALS['ecs']->table('goods') . " as g on og.goods_id = g.goods_id " .
            " WHERE og.order_id = '$order_id'  order by g.goods_id";
    $res = $GLOBALS['db']->getAll($sql);

    $arr = array();
    foreach ($res as $key => $row) {
        $arr[$key]['goods_id'] = $row['goods_id'];
        $arr[$key]['goods_name'] = $row['goods_name'];
        $arr[$key]['goods_number'] = $row['goods_number'];
        $arr[$key]['goods_attr'] = $row['goods_attr'];
        $arr[$key]['goods_price'] = $row['goods_price'];
        $arr[$key]['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $arr[$key]['url'] = build_uri('wholesale_goods', array('aid' => $row['act_id']), $row['goods_name']);
    }

    return $arr;
}

/**
 * 取得采购订单信息
 * @param   int     $order_id   订单id（如果order_id > 0 就按id查，否则按sn查）
 * @param   string  $order_sn   订单号
 * @return  array   订单信息（金额都有相应格式化的字段，前缀是formated_）
 */
function wholesale_order_info($order_id, $order_sn = '') {
    /* 计算订单各种费用之和的语句 */
    $order_id = intval($order_id);
    if ($order_id > 0) {
        $sql = "SELECT o.* FROM " . $GLOBALS['ecs']->table('wholesale_order_info') . " AS o WHERE o.order_id = '$order_id'";
    } else {
        $sql = "SELECT o.* FROM " . $GLOBALS['ecs']->table('wholesale_order_info') . " AS o WHERE o.order_sn = '$order_sn'";
    }
    $order = $GLOBALS['db']->getRow($sql);
    // if ($order['cost_amount'] <= 0) {
        // $order['cost_amount'] = goods_cost_price($order['order_id']);
    // }

    /* 格式化金额字段 */
    if ($order) {
        $order['formated_goods_amount'] = price_format($order['order_amount'], false);
        $order['formated_order_amount'] = price_format(abs($order['order_amount']), false);
        $order['formated_add_time'] = local_date($GLOBALS['_CFG']['time_format'], $order['add_time']);
        $order_id = !empty($order['order_id']) ? $order['order_id'] : 0;
        $order_goods = get_order_seller_id($order_id);
        $order['ru_id'] = $order_goods['ru_id'];
        $payment = $GLOBALS['db']->getRow("SELECT pay_name ,pay_code FROM".$GLOBALS['ecs']->table('payment')."WHERE pay_id = '" . $order['pay_id']. "'");
        $order['pay_name'] = $payment['pay_name'];
        $order['pay_code'] = $payment['pay_code'];
        $order['pay_time'] = local_date($GLOBALS['_CFG']['time_format'], $order['pay_time']);
    }
    return $order;
}

/**
 * 获得指定分类下的子分类的数组
 *
 * @access  public
 * @param   int     $cat_id     分类的ID
 * @param   int     $selected   当前选中分类的ID
 * @param   boolean $re_type    返回的类型: 值为真时返回下拉列表,否则返回数组
 * @param   int     $level      限定返回的级数。为0时返回所有级数
 * @param   int     $is_show_all 如果为true显示所有分类，如果为false隐藏不可见分类。
 * @return  mix
 */
function wholesale_cat_list($cat_id = 0, $selected = 0, $re_type = true, $level = 0, $is_show_all = true) {
    static $res = NULL;

    if ($res === NULL) {
        $data = read_static_cache('wholesale_cat_releate');
        if ($data === false) {
            $sql = "SELECT w.cat_id, w.cat_name,w.is_show, w.parent_id, w.sort_order, COUNT(s.parent_id) AS has_children " .
                    'FROM ' . $GLOBALS['ecs']->table('wholesale_cat') . " AS w " .
                    "LEFT JOIN " . $GLOBALS['ecs']->table('wholesale_cat') . " AS s ON s.parent_id = w.cat_id " .
                    "GROUP BY w.cat_id " .
                    'ORDER BY w.parent_id, w.sort_order ASC';
            $res = $GLOBALS['db']->getAll($sql);

            //如果数组过大，不采用静态缓存方式
            if (count($res) <= 1000) {
                write_static_cache('wholesale_cat_releate', $res);
            }
        } else {
			
            $res = $data;

        }
    }

    if (empty($res) == true) {
        return $re_type ? '' : array();
    }

    $options = wholesale_cat_options($cat_id, $res); // 获得指定分类下的子分类的数组

    $children_level = 99999; //大于这个分类的将被删除
    if ($is_show_all == false) {
        foreach ($options as $key => $val) {
            if ($val['level'] > $children_level) {
                unset($options[$key]);
            } else {
                if ($val['is_show'] == 0) {
                    unset($options[$key]);
                    if ($children_level > $val['level']) {
                        $children_level = $val['level']; //标记一下，这样子分类也能删除
                    }
                } else {
                    $children_level = 99999; //恢复初始值
                }
            }
        }
    }

    /* 截取到指定的缩减级别 */
    if ($level > 0) {
        if ($cat_id == 0) {
            $end_level = $level;
        } else {
            $first_item = reset($options); // 获取第一个元素
            $end_level = $first_item['level'] + $level;
        }

        /* 保留level小于end_level的部分 */
        foreach ($options AS $key => $val) {
            if ($val['level'] >= $end_level) {
                unset($options[$key]);
            }
        }
    }

    if ($re_type == true) {
        $select = '';
        foreach ($options AS $var) {
            $select .= '<option value="' . $var['cat_id'] . '" ';
            $select .= ($selected == $var['cat_id']) ? "selected='ture'" : '';
            $select .= '>';
            if ($var['level'] > 0) {
                $select .= str_repeat('&nbsp;', $var['level'] * 4);
            }
            $select .= htmlspecialchars(addslashes($var['cat_name']), ENT_QUOTES) . '</option>';
        }

        return $select;
    } else {
        foreach ($options AS $key => $value) {
            //$options[$key]['url'] = build_uri('category', array('cid' => $value['cid']), $value['c_name']);
        }

        return $options;
    }
}

/**
 * 过滤和排序所有分类，返回一个带有缩进级别的数组
 *
 * @access  private
 * @param   int     $cat_id     上级分类ID
 * @param   array   $arr        含有所有分类的数组
 * @param   int     $level      级别
 * @return  void
 */
function wholesale_cat_options($spec_cat_id, $arr) {
    static $cat_options = array();

    if (isset($cat_options[$spec_cat_id])) {
        return $cat_options[$spec_cat_id];
    }

    if (!isset($cat_options[0])) {
        $level = $last_cat_id = 0;
        $options = $cat_id_array = $level_array = array();
        $data = read_static_cache('wholesale_cat_option_static');
        if ($data === false) {
            while (!empty($arr)) {
                foreach ($arr AS $key => $value) {
                    $cat_id = $value['cat_id'];
                    if ($level == 0 && $last_cat_id == 0) {
                        if ($value['parent_id'] > 0) {
                            break;
                        }

                        $options[$cat_id] = $value;
                        $options[$cat_id]['level'] = $level;
                        $options[$cat_id]['id'] = $cat_id;
                        $options[$cat_id]['name'] = $value['cat_name'];
                        unset($arr[$key]);

                        if ($value['has_children'] == 0) {
                            continue;
                        }
                        $last_cat_id = $cat_id;
                        $cat_id_array = array($cat_id);
                        $level_array[$last_cat_id] = ++$level;
                        continue;
                    }

                    if ($value['parent_id'] == $last_cat_id) {
                        $options[$cat_id] = $value;
                        $options[$cat_id]['level'] = $level;
                        $options[$cat_id]['id'] = $cat_id;
                        $options[$cat_id]['name'] = $value['cat_name'];
                        unset($arr[$key]);

                        if ($value['has_children'] > 0) {
                            if (end($cat_id_array) != $last_cat_id) {
                                $cat_id_array[] = $last_cat_id;
                            }
                            $last_cat_id = $cat_id;
                            $cat_id_array[] = $cat_id;
                            $level_array[$last_cat_id] = ++$level;
                        }
                    } elseif ($value['parent_id'] > $last_cat_id) {
                        break;
                    }
                }

                $count = count($cat_id_array);
                if ($count > 1) {
                    $last_cat_id = array_pop($cat_id_array);
                } elseif ($count == 1) {
                    if ($last_cat_id != end($cat_id_array)) {
                        $last_cat_id = end($cat_id_array);
                    } else {
                        $level = 0;
                        $last_cat_id = 0;
                        $cat_id_array = array();
                        continue;
                    }
                }

                if ($last_cat_id && isset($level_array[$last_cat_id])) {
                    $level = $level_array[$last_cat_id];
                } else {
                    $level = 0;
                }
            }
            //如果数组过大，不采用静态缓存方式
            if (count($options) <= 2000) {
                write_static_cache('wholesale_cat_option_static', $options);
            }
        } else {
            $options = $data;
        }
        $cat_options[0] = $options;
    } else {
        $options = $cat_options[0];
    }

    if (!$spec_cat_id) {
        return $options;
    } else {
        if (empty($options[$spec_cat_id])) {
            return array();
        }

        $spec_cat_id_level = $options[$spec_cat_id]['level'];

        foreach ($options AS $key => $value) {
            if ($key != $spec_cat_id) {
                unset($options[$key]);
            } else {
                break;
            }
        }

        $spec_cat_id_array = array();
        foreach ($options AS $key => $value) {
            if (($spec_cat_id_level == $value['level'] && $value['cat_id'] != $spec_cat_id) ||
                    ($spec_cat_id_level > $value['level'])) {
                break;
            } else {
                $spec_cat_id_array[$key] = $value;
            }
        }
        $cat_options[$spec_cat_id] = $spec_cat_id_array;

        return $spec_cat_id_array;
    }
}

/**
 * 获得所有批发下级分类属于指定分类的所有商品ID
 *
 * @access  public
 * @param   string $cat_id     分类查询字符串
 * @return  string
 */
function get_wholesale_extension_goods($cats, $alias = 'w.') {
    $extension_goods_array = '';
    $sql = 'SELECT goods_id FROM ' . $GLOBALS['ecs']->table('wholesale') . " AS w WHERE $cats";
    $extension_goods_array = $GLOBALS['db']->getCol($sql);
    return db_create_in($extension_goods_array, $alias . 'goods_id');
}

/**
 * 获取批发商品标识
 */
function get_wholesale_extend($goods_id) {
    //获得商品的扩展信息by wang
    $extend_sql = "SELECT * FROM " . $GLOBALS['ecs']->table('wholesale_extend') . " WHERE goods_id = '$goods_id'";
    return $GLOBALS['db']->getRow($extend_sql);
}

/**
 * 确认一个用户订单
 *
 * @access  public
 * @param   int         $order_id       订单ID
 * @param   int         $user_id        用户ID
 *
 * @return  bool        $bool
 */
function wholesale_affirm_received($order_id, $user_id = 0)
{
    /* 查询订单信息，检查状态 */
    $sql = "SELECT user_id, order_sn , order_status FROM ".$GLOBALS['ecs']->table('wholesale_order_info') ." WHERE order_id = '$order_id'";

    $order = $GLOBALS['db']->GetRow($sql);
    // 如果用户ID大于 0 。检查订单是否属于该用户
    if ($user_id > 0 && $order['user_id'] != $user_id)
    {
        $GLOBALS['err'] -> add($GLOBALS['_LANG']['no_priv']);

        return false;
    }
    /* 检查订单 */
    elseif ($order['order_status'] == OS_CONFIRMED)
    {
        $GLOBALS['err'] ->add($GLOBALS['_LANG']['order_already_received']);

        return false;
    }
    elseif ($order['order_status'] == OS_UNCONFIRMED)
    {
        $sql = "UPDATE " . $GLOBALS['ecs']->table('wholesale_order_info') . " SET order_status = '" . OS_CONFIRMED . "' WHERE order_id = '$order_id'";
        if ($GLOBALS['db']->query($sql))
        {
            /* 记录日志 */
            order_action($order['order_sn'], $order['order_status'], OS_CONFIRMED, $GLOBALS['_LANG']['buyer']);

            return true;
        }
        else
        {
            die($GLOBALS['db']->errorMsg());
        }
    }
}


/**
 * 获得指定分类下的子分类的数组
 */
function get_wholesale_child_cat($cat_id = 0, $type = 0) {    
   if ($cat_id > 0) {
        $sql = 'SELECT parent_id FROM ' . $GLOBALS['ecs']->table('wholesale_cat') . " WHERE cat_id = '$cat_id' LIMIT 1";
        $parent_id = $GLOBALS['db']->getOne($sql);
    } else {
        $parent_id = 0;
    }  
	
	$sql = 'SELECT cat_id FROM ' . $GLOBALS['ecs']->table('wholesale_cat') . " WHERE parent_id = '$parent_id' AND is_show = 1 LIMIT 1";
    if ($GLOBALS['db']->getOne($sql) || $parent_id == 0) {
        /* 获取当前分类及其子分类 */
        $sql = 'SELECT cat_id,cat_name ,parent_id,is_show, style_icon, cat_icon ' .
                'FROM ' . $GLOBALS['ecs']->table('wholesale_cat') .
                "WHERE parent_id = '$parent_id' AND is_show = 1 ORDER BY sort_order ASC, cat_id ASC";

        $res = $GLOBALS['db']->getAll($sql);
        $cat_arr = array();
        foreach ($res AS $row) {
			$cat_arr[$row['cat_id']]['id'] = $row['cat_id'];
            $cat_arr[$row['cat_id']]['name'] = $row['cat_name'];
			$cat_arr[$row['cat_id']]['style_icon'] = $row['style_icon'];
			$cat_arr[$row['cat_id']]['cat_icon'] = $row['cat_icon'];

            $cat_arr[$row['cat_id']]['url'] = build_uri('wholesale_cat', array('act' => 'list', 'cid' => $row['cat_id']), $row['cat_name']);

            if (isset($row['cat_id']) != NULL) {
                $cat_arr[$row['cat_id']]['cat_id'] = get_wholesale_child_tree($row['cat_id']);
            }
        }
    }
    return $cat_arr;
}

/**
 * 获取子分类
 */
function get_wholesale_child_tree($tree_id = 0, $ru_id = 0) {
    $three_arr = array();
    $sql = 'SELECT count(*) FROM ' . $GLOBALS['ecs']->table('wholesale_cat') . " WHERE parent_id = '$tree_id' AND is_show = 1 ";
    if ($GLOBALS['db']->getOne($sql) || $tree_id == 0) {
        $child_sql = 'SELECT cat_id, cat_name, parent_id, is_show ' .
                'FROM ' . $GLOBALS['ecs']->table('wholesale_cat') .
                "WHERE parent_id = '$tree_id' AND is_show = 1 ORDER BY sort_order ASC, cat_id ASC";
        $res = $GLOBALS['db']->getAll($child_sql);
        foreach ($res AS $row) {
            if ($row['is_show'])
                $three_arr[$row['cat_id']]['id'] = $row['cat_id'];
            $three_arr[$row['cat_id']]['name'] = $row['cat_name'];

            if ($ru_id) {

                $build_uri = array(
                    'cid' => $row['cat_id'],
                    'urid' => $ru_id,
                    'append' => $row['cat_name']
                );

                $domain_url = get_seller_domain_url($ru_id, $build_uri);
                $three_arr[$row['cat_id']]['url'] = $domain_url['domain_name'];
            } else {
                $three_arr[$row['cat_id']]['url'] = build_uri('wholesale_cat', array('act' => 'list', 'cid' => $row['cat_id']), $row['cat_name']);
            }

            if (isset($row['cat_id']) != NULL) {
                $three_arr[$row['cat_id']]['cat_id'] = get_wholesale_child_tree($row['cat_id']);
            }
        }
    }
    return $three_arr;
}

//分类是否显示在导航上
function get_wholsale_navigator(){
	$cur_url = substr(strrchr($_SERVER['REQUEST_URI'],'/'),1);
	preg_match('/\d+/',$cur_url,$matches);
	$curr_id = $matches[0];
	
	
	$sql = "SELECT * FROM " . $GLOBALS['ecs']->table('wholesale_cat') . " WHERE is_show = 1 AND show_in_nav = 1 ORDER BY sort_order";
	$res = $GLOBALS['db']->getAll($sql);
	foreach($res AS $k => $row){
		$res[$k]['url'] = build_uri('wholesale_cat', array('act' => 'list', 'cid' => $row['cat_id']), $row['cat_name']);
		$res[$k]['active'] = $curr_id;
	}
	return $res;
}

/**
 * 批发商品的货品货号是否重复
 *
 * @param   string     $product_sn        商品的货品货号；请在传入本参数前对本参数进行SQl脚本过滤
 * @param   int        $product_id        商品的货品id；默认值为：0，没有货品id
 * @param   int        $goods_model       商品模式；0为默认，1为仓库，2为地区
 * @return  bool                          true，重复；false，不重复
 */
function check_wholsale_product_sn_exist($product_sn, $product_id = 0, $ru_id = 0, $goods_model = 0) {

    $product_sn = trim($product_sn);
    $product_id = intval($product_id);
    if (strlen($product_sn) == 0) {
        return true;    //重复
    }
    
    if (!empty($product_id)) {
        $sql = "SELECT w.user_id FROM " . $GLOBALS['ecs']->table('wholesale_products') . " AS wp, " .
                $GLOBALS['ecs']->table('wholesale') . " AS w" .
                " WHERE wp.goods_id = w.goods_id AND product_id = '$product_id'";
        $ru_id = $GLOBALS['db']->getOne($sql, true);
    }else{
        $ru_id = 0;
    }

    $sql = "SELECT wp.goods_id FROM " . $GLOBALS['ecs']->table('wholesale_products') . " AS wp WHERE wp.product_sn='$product_sn' AND wp.admin_id = '$ru_id'";
    if ($GLOBALS['db']->getOne($sql)) {
        return true;    //重复
    }

    $where = " AND (SELECT w.user_id FROM " . $GLOBALS['ecs']->table('wholesale') . " AS w WHERE w.goods_id = wp.goods_id LIMIT 1) = '$ru_id'";

    if (empty($product_id))
    {
        $sql = "SELECT wp.product_id FROM " . $GLOBALS['ecs']->table('wholesale_products')  ." AS wp "."
                WHERE product_sn = '$product_sn'" . $where;
    }
    else
    {
        $sql = "SELECT wp.product_id FROM " . $GLOBALS['ecs']->table('wholesale_products') ." AS wp "."
                WHERE product_sn = '$product_sn'
                AND product_id <> '$product_id'" . $where;
    }
    
    $res = $GLOBALS['db']->getOne($sql);

    if (empty($res)) {
        return false;    //不重复
    } else {
        return true;    //重复
    }
}

//批发订单搜索条件
function get_wholesale_order_search_keyword($order = array()) {
    $where = '';
    if (isset($order->keyword)) {

        if ($order->type == 'text') { //订单编号、商品编号、商品名称模糊查询
            if ($order->keyword == $GLOBALS['_LANG']['user_keyword']) {
                $order->keyword = '';
            }

            $where .= " AND (oi.order_sn LIKE '%" . mysql_like_quote($order->keyword) .
                    "%' or og.goods_name LIKE '%" . mysql_like_quote($order->keyword) .
                    "%' or og.goods_sn LIKE '%" . mysql_like_quote($order->keyword) . "%')";
        } elseif ($order->type == 'dateTime' || $order->type == 'order_status') {

            if ($order->idTxt == 'wholesale_submitDate') { //订单时间范围
                $date_keyword = $order->keyword;
                $status_keyword = -1;
            } elseif ($order->idTxt == 'wholesale_status_list') { //订单状态
                $date_keyword = $order->date_keyword;
                $status_keyword = $order->keyword;
            }
            // var_dump($date_keyword);
            $firstSecToday = local_mktime(0, 0, 0, date("m"), date("d"), date("Y")); //当天开始返回时间戳 比如1369814400 2013-05-30 00:00:00
            $lastSecToday = local_mktime(0, 0, 0, date("m"), date("d") + 1, date("Y")) - 1; //当天结束返回时间戳 比如1369900799  2013-05-30 00:00:00

            if ($date_keyword == 'today') {
                $where .= " AND oi.add_time >= '$firstSecToday' and oi.add_time <= '$lastSecToday'";
            } elseif ($date_keyword == 'three_today') {
                $firstSecToday = $firstSecToday - 24 * 3600 * 2;
                $where .= " AND oi.add_time >= '$firstSecToday' and oi.add_time <= '$lastSecToday'";
            } elseif ($date_keyword == 'aweek') {
                $firstSecToday = $firstSecToday - 24 * 3600 * 6;
                $where .= " AND oi.add_time >= '$firstSecToday' and oi.add_time <= '$lastSecToday'";
            } elseif ($date_keyword == 'thismonth') {
                $first_month_day = strtotime("-1 month"); //上个月的今天
                $last_month_day = gmtime(); //今天

                $where .= " AND oi.add_time >= '$first_month_day' and oi.add_time <= '$last_month_day'";
            } elseif ($date_keyword == 'allDate') {
                $where .= "";
            }
            //综合状态
            switch ($status_keyword) {
                case -1 :
                    $where .= "";
                    break;

                case 0 :
                    $where .= " AND oi.order_status = 0";
                    break;

                case 1 :
                    $where .= " AND oi.order_status = 1";
                    break;
                default:
                    $where .= "";
            }
        }
    }
    return $where;
}

/**
 * 判断会员是否有购买权利
 */
function get_user_wholesale_rank($rank_ids) {
    $wholesale_rank = array();
    if ($goods['rank_ids']) {
        $sql = "SELECT rank_id, rank_name FROM " . $GLOBALS['ecs']->table('user_rank') .
                " WHERE rank_id " . db_create_in($goods['rank_ids']);
        $wholesale_rank = $GLOBALS['db']->getAll($sql);
    }
    
    return $wholesale_rank;
}

/**
 * 判断会员是否是商家
 */
function get_is_seller() {
    
    $user_id = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
    
    $is_jurisdiction = 0;
    if ($user_id > 0) {
        //判断是否是商家
        $sql = "SELECT id FROM " . $GLOBALS['ecs']->table('seller_shopinfo') . " WHERE ru_id = '$user_id'";
        if ($GLOBALS['db']->getOne($sql, true)) {
            $is_jurisdiction = 1;
        }
        
        //判断是否是厂商
        $sql = "SELECT fid FROM " . $GLOBALS['ecs']->table('merchants_steps_fields') . " WHERE user_id = '$user_id' AND company_type = '厂商'";
        $is_chang = $GLOBALS['db']->getOne($sql, true);

        if ($is_chang) {
            $is_jurisdiction = 0;
        }
    }
    
    return $is_jurisdiction;
}

/**
 * 取得某订单商品总重量和总金额（对应 cart_weight_price）
 * @param   int     $order_id   订单id
 * @return  array   ('weight' => **, 'amount' => **, 'formated_weight' => **)
 */
function wholesale_order_weight_price($order_id)
{
    $sql = "SELECT SUM(g.goods_weight * o.goods_number) AS weight, " .
                "SUM(o.goods_price * o.goods_number) AS amount ," .
                "SUM(o.goods_number) AS number " .
            "FROM " . $GLOBALS['ecs']->table('wholesale_order_goods') . " AS o, " .
                $GLOBALS['ecs']->table('goods') . " AS g " .
            "WHERE o.order_id = '$order_id' " .
            "AND o.goods_id = g.goods_id";

    $row = $GLOBALS['db']->getRow($sql);
    $row['weight'] = floatval($row['weight']);
    $row['amount'] = floatval($row['amount']);
    $row['number'] = intval($row['number']);

    /* 格式化重量 */
    $row['formated_weight'] = formated_weight($row['weight']);

    return $row;
}

?>